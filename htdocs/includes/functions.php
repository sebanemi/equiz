<?php
// equiz — shared game logic. Server is the authority on time/score/state.

function e_now(): int { return time(); }

/** Generate a numeric PIN that does not collide with an active game. */
function e_generate_pin(PDO $pdo): string {
    for ($i = 0; $i < 20; $i++) {
        $pin = '';
        for ($j = 0; $j < GAME_PIN_LENGTH; $j++) $pin .= (string)random_int(0, 9);
        // avoid leading zero for nicer display
        if ($pin[0] === '0') $pin[0] = (string)random_int(1, 9);
        $st = $pdo->prepare("SELECT id FROM games WHERE pin = ? AND status <> 'finished' LIMIT 1");
        $st->execute([$pin]);
        if (!$st->fetch()) return $pin;
    }
    // fallback: allow reuse of finished pins
    return (string)random_int(100000, 999999);
}

function e_valid_pin($pin): bool {
    return is_string($pin) && preg_match('/^\d{4,10}$/', $pin) === 1;
}

function e_clean_name($name): string {
    $name = is_string($name) ? trim(preg_replace('/\s+/', ' ', $name)) : '';
    return mb_substr($name, 0, 20);
}

function e_valid_name(string $name): bool {
    // letters, numbers, spaces and a few safe symbols; 2-20 chars
    return (bool)preg_match('/^[\p{L}\p{N} _.\-]{2,20}$/u', $name);
}

function e_get_game(PDO $pdo, string $pin) {
    $st = $pdo->prepare("SELECT * FROM games WHERE pin = ? LIMIT 1");
    $st->execute([$pin]);
    return $st->fetch() ?: null;
}

/** Kahoot-style speed points — kept as TIEBREAK for the battle royale ranking. */
function e_calc_points(bool $correct, int $elapsed_ms, int $time_limit_s): int {
    if (!$correct) return 0;
    $total_ms = max(1000, $time_limit_s * 1000);
    $elapsed_ms = max(0, min($elapsed_ms, $total_ms));
    $ratio = 1 - ($elapsed_ms / $total_ms); // 1 = instant, 0 = last ms
    return (int)round(500 + 500 * $ratio);
}

/** Seconds of "GET READY" intro before each question. Configurable in config/database.php. */
function e_countdown_seconds(): int {
    $s = defined('COUNTDOWN_SECONDS') ? (int)COUNTDOWN_SECONDS : 5;
    return max(3, min(10, $s));
}

/** Lives every player starts with. Configurable in config/database.php. */
function e_start_lives(): int {
    $n = defined('START_LIVES') ? (int)START_LIVES : 5;
    return max(1, min(10, $n));
}

/** Players who gave NO answer when a question closes lose 1 life. */
function e_apply_noanswer_penalty(PDO $pdo, int $game_id, int $gq_id): void {
    $st = $pdo->prepare("UPDATE players p LEFT JOIN answers a ON a.player_id = p.id AND a.game_question_id = ? SET p.lives = GREATEST(0, p.lives - 1) WHERE p.game_id = ? AND p.lives > 0 AND a.id IS NULL");
    $st->execute([$gq_id, $game_id]);
}

/**
 * Lazy expiry: countdown -> question, question -> review.
 * Called by state.php / answer.php so no cron/process is needed.
 * Returns the (possibly updated) game row.
 */
function e_maybe_expire(PDO $pdo, array $game): array {
    $now = e_now();
    if ($game['status'] === 'countdown' && !empty($game['current_ends_at']) && $now >= (int)$game['current_ends_at']) {
        // intro over -> open the question with its own time limit
        $st = $pdo->prepare("SELECT time_limit FROM game_questions WHERE game_id = ? AND q_order = ? LIMIT 1");
        $st->execute([$game['id'], (int)$game['current_order']]);
        $row = $st->fetch();
        $tl = $row ? max(5, min(120, (int)$row['time_limit'])) : 20;
        $st = $pdo->prepare("UPDATE games SET status = 'question', current_started_at = ?, current_ends_at = ? WHERE id = ? AND status = 'countdown'");
        $st->execute([$now, $now + $tl, $game['id']]);
        $game['status'] = 'question';
        $game['current_started_at'] = $now;
        $game['current_ends_at'] = $now + $tl;
    } elseif ($game['status'] === 'question' && !empty($game['current_ends_at']) && $now >= (int)$game['current_ends_at']) {
        $st = $pdo->prepare("SELECT id FROM game_questions WHERE game_id = ? AND q_order = ? LIMIT 1");
        $st->execute([$game['id'], (int)$game['current_order']]);
        $gqrow = $st->fetch();
        $st = $pdo->prepare("UPDATE games SET status = 'review' WHERE id = ? AND status = 'question'");
        $st->execute([$game['id']]);
        if ($gqrow) e_apply_noanswer_penalty($pdo, (int)$game['id'], (int)$gqrow['id']);
        $game['status'] = 'review';
    }
    return $game;
}

function e_current_gq(PDO $pdo, array $game) {
    if ((int)$game['current_order'] < 1) return null;
    $st = $pdo->prepare("SELECT * FROM game_questions WHERE game_id = ? AND q_order = ? LIMIT 1");
    $st->execute([$game['id'], (int)$game['current_order']]);
    return $st->fetch() ?: null;
}

function e_leaderboard(PDO $pdo, int $game_id, int $limit = 50): array {
    $limit = max(1, min(100, $limit));
    $st = $pdo->prepare("SELECT id, name, score, lives, correct_count FROM players WHERE game_id = ? ORDER BY lives DESC, correct_count DESC, score DESC, id ASC LIMIT $limit");
    $st->execute([$game_id]);
    return $st->fetchAll();
}

function e_question_stats(PDO $pdo, int $game_question_id): array {
    $st = $pdo->prepare("SELECT selected_option, COUNT(*) c FROM answers WHERE game_question_id = ? GROUP BY selected_option");
    $st->execute([$game_question_id]);
    $dist = [0, 0, 0, 0];
    $total = 0;
    foreach ($st->fetchAll() as $r) {
        $o = (int)$r['selected_option'];
        if ($o >= 0 && $o <= 3) { $dist[$o] = (int)$r['c']; $total += (int)$r['c']; }
    }
    $st = $pdo->prepare("SELECT COUNT(*) c, SUM(is_correct) ok FROM answers WHERE game_question_id = ?");
    $st->execute([$game_question_id]);
    $row = $st->fetch();
    return [
        'total' => $total,
        'correct' => (int)($row['ok'] ?? 0),
        'incorrect' => $total - (int)($row['ok'] ?? 0),
        'dist' => $dist,
    ];
}

function e_player_rank(PDO $pdo, int $game_id, int $player_id): ?int {
    $st = $pdo->prepare("SELECT id FROM players WHERE game_id = ? ORDER BY lives DESC, correct_count DESC, score DESC, id ASC");
    $st->execute([$game_id]);
    $rank = 0;
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $rank++;
        if ((int)$id === $player_id) return $rank;
    }
    return null;
}
