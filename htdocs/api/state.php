<?php
// GET api/state.php?pin=123456[&token=...]
// Single lightweight polling endpoint for host + players.
// Host gets question text/options/stats; players NEVER get correct answer or text.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pin = $_GET['pin'] ?? '';
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';

if (!e_valid_pin((string)$pin)) json_error('Invalid PIN.', 400);

try {
    $pdo = db();
    $game = e_get_game($pdo, (string)$pin);
    if (!$game) json_error('Game not found.', 404);
    $game = e_maybe_expire($pdo, $game);

    $now = e_now();
    $out = [
        'ok' => true,
        'now' => $now,
        'status' => $game['status'],
        'pin' => $game['pin'],
        'order' => (int)$game['current_order'],
        'total' => (int)$game['total_questions'],
    ];

    // Lobby: player count + names (cap list at 60 for lightness)
    if ($game['status'] === 'lobby') {
        $st = $pdo->prepare("SELECT COUNT(*) c FROM players WHERE game_id = ?");
        $st->execute([$game['id']]);
        $out['players_count'] = (int)$st->fetchColumn();
        $st = $pdo->prepare("SELECT name FROM players WHERE game_id = ? ORDER BY id ASC LIMIT 60");
        $st->execute([$game['id']]);
        $out['players'] = array_column($st->fetchAll(), 'name');
        json_out($out);
    }

    $gq = e_current_gq($pdo, $game);
    if (!$gq && $game['status'] !== 'countdown') { // finished with no current question
        $out['leaderboard'] = e_leaderboard($pdo, (int)$game['id']);
        json_out($out);
    }

    // GET READY intro: nobody sees the question yet, only the countdown.
    if ($game['status'] === 'countdown') {
        $out['countdown_ms'] = max(0, (int)$game['current_ends_at'] * 1000 - $now * 1000);
        $out['countdown_total'] = e_countdown_seconds();
        json_out($out);
    }

    $ends = (int)($game['current_ends_at'] ?? 0);
    $starts = (int)($game['current_started_at'] ?? 0);
    $out['time_left_ms'] = max(0, $ends * 1000 - $now * 1000);
    $out['time_limit'] = (int)$gq['time_limit'];
    $out['question_ends_at'] = $ends;

    $is_host = isset($_GET['host']) && $_GET['host'] === '1';

    if ($is_host) {
        $out['question'] = [
            'text' => $gq['question_text'],
            'options' => [$gq['option_a'], $gq['option_b'], $gq['option_c'], $gq['option_d']],
            'correct' => $game['status'] === 'question' ? null : (int)$gq['correct_option'],
            'time_limit' => (int)$gq['time_limit'],
        ];
        $stats = e_question_stats($pdo, (int)$gq['id']);
        $out['stats'] = $stats;
        // live answered count during question
        if ($game['status'] === 'question') {
            $st = $pdo->prepare("SELECT COUNT(*) FROM players WHERE game_id = ?");
            $st->execute([$game['id']]);
            $out['stats']['players_total'] = (int)$st->fetchColumn();
        }
    } else {
        // PLAYER view: never leak text or correct option
        $out['question_active'] = $game['status'] === 'question';
        if ($token !== '') {
            $st = $pdo->prepare("SELECT id, score, lives, correct_count FROM players WHERE game_id = ? AND token = ? LIMIT 1");
            $st->execute([$game['id'], $token]);
            $me = $st->fetch();
            if ($me) {
                $pdo->prepare("UPDATE players SET last_seen = ? WHERE id = ?")->execute([$now, $me['id']]);
                $dead = ((int)$me['lives'] <= 0);
                $out['lives'] = (int)$me['lives'];
                $out['eliminated'] = $dead;
                $st = $pdo->prepare("SELECT selected_option, is_correct, points FROM answers WHERE game_question_id = ? AND player_id = ? LIMIT 1");
                $st->execute([$gq['id'], $me['id']]);
                $ans = $st->fetch();
                $out['answered'] = $ans ? true : false;
                if ($ans) {
                    $out['my'] = [
                        'selected' => (int)$ans['selected_option'],
                        'points' => $game['status'] === 'question' ? null : (int)$ans['points'],
                        'correct' => $game['status'] === 'question' ? null : (bool)$ans['is_correct'],
                    ];
                }
                $out['my_score'] = (int)$me['score'];
                $out['my_rank'] = e_player_rank($pdo, (int)$game['id'], (int)$me['id']);
            } else {
                $out['kicked'] = true;
            }
        }
        // During review/leaderboard players may see their result + top list (no answers text)
        if (in_array($game['status'], ['review', 'leaderboard', 'finished'], true)) {
            $out['stats_summary'] = e_question_stats($pdo, (int)$gq['id']);
            unset($out['stats_summary']['dist']); // keep phones light; host shows full bars
        }
    }

    if (in_array($game['status'], ['leaderboard', 'finished', 'review'], true)) {
        $out['leaderboard'] = e_leaderboard($pdo, (int)$game['id'], 10);
    }

    json_out($out);
} catch (Throwable $e) {
    json_error('Server error.', 500);
}
