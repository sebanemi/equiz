<?php
// POST api/host_action.php {pin, action}
// Actions: create | start | next | reveal (question->review early) | to_leaderboard | finish
// Auth: PHP session — the browser that created the game owns it.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only.', 405);
$in = $_POST;
if (empty($in['action']) && empty($in['pin'])) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $in = array_merge($in, $raw);
}
$action = (string)($in['action'] ?? '');
$pin = (string)($in['pin'] ?? '');

function host_owns(string $pin): bool {
    return isset($_SESSION['host_pins']) && is_array($_SESSION['host_pins']) && in_array($pin, $_SESSION['host_pins'], true);
}
function host_claim(string $pin): void {
    if (!isset($_SESSION['host_pins']) || !is_array($_SESSION['host_pins'])) $_SESSION['host_pins'] = [];
    if (!in_array($pin, $_SESSION['host_pins'], true)) $_SESSION['host_pins'][] = $pin;
}

try {
    $pdo = db();

    if ($action === 'create') {
        // snapshot active questions into a new game
        $qs = $pdo->query("SELECT question_text, option_a, option_b, option_c, option_d, correct_option, time_limit FROM questions WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
        if (count($qs) < 1) json_error('No active questions. Add some in admin.php first.', 400);
        $pin = e_generate_pin($pdo);
        $pdo->beginTransaction();
        $st = $pdo->prepare("INSERT INTO games (pin, status, current_order, total_questions) VALUES (?, 'lobby', 0, ?)");
        $st->execute([$pin, count($qs)]);
        $gid = (int)$pdo->lastInsertId();
        $st = $pdo->prepare("INSERT INTO game_questions (game_id, q_order, question_text, option_a, option_b, option_c, option_d, correct_option, time_limit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $o = 0;
        foreach ($qs as $q) {
            $o++;
            $st->execute([$gid, $o, $q['question_text'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], (int)$q['correct_option'], max(5, min(120, (int)$q['time_limit']))]);
        }
        $pdo->commit();
        host_claim($pin);
        json_out(['ok' => true, 'pin' => $pin, 'total' => $o]);
    }

    if (!e_valid_pin($pin)) json_error('Invalid PIN.', 400);
    if (!host_owns($pin)) json_error('Only the host browser that created this game can control it.', 403);
    $game = e_get_game($pdo, $pin);
    if (!$game) json_error('Game not found.', 404);
    $game = e_maybe_expire($pdo, $game);
    $gid = (int)$game['id'];
    $now = e_now();

    switch ($action) {
        case 'start': // lobby -> countdown intro -> Q1
        case 'next':  // review/leaderboard -> countdown intro -> next question
            if ($action === 'start' && $game['status'] !== 'lobby') json_error('Game already started.', 409);
            if ($action === 'next' && !in_array($game['status'], ['review', 'leaderboard'], true)) json_error('Finish the current question first.', 409);
            $next = ($action === 'start') ? 1 : ((int)$game['current_order'] + 1);
            if ($next > (int)$game['total_questions']) json_error('No more questions.', 409);
            $st = $pdo->prepare("SELECT id FROM game_questions WHERE game_id = ? AND q_order = ? LIMIT 1");
            $st->execute([$gid, $next]);
            if (!$st->fetch()) json_error('Question not found.', 404);
            $cd = e_countdown_seconds();
            $st = $pdo->prepare("UPDATE games SET status = 'countdown', current_order = ?, current_started_at = ?, current_ends_at = ? WHERE id = ?");
            $st->execute([$next, $now, $now + $cd, $gid]);
            json_out(['ok' => true, 'order' => $next, 'countdown' => $cd]);
            break;

        case 'reveal': // end timer early -> review (no-answers lose a life too)
            if ($game['status'] !== 'question') json_error('No active question.', 409);
            $gq = e_current_gq($pdo, $game);
            $pdo->prepare("UPDATE games SET status = 'review' WHERE id = ?")->execute([$gid]);
            if ($gq) e_apply_noanswer_penalty($pdo, $gid, (int)$gq['id']);
            json_out(['ok' => true]);
            break;

        case 'to_leaderboard':
            if (!in_array($game['status'], ['review', 'question'], true)) json_error('Nothing to rank yet.', 409);
            $pdo->prepare("UPDATE games SET status = 'leaderboard' WHERE id = ?")->execute([$gid]);
            json_out(['ok' => true, 'leaderboard' => e_leaderboard($pdo, $gid, 10)]);
            break;

        case 'finish':
            $pdo->prepare("UPDATE games SET status = 'finished' WHERE id = ?")->execute([$gid]);
            json_out(['ok' => true, 'leaderboard' => e_leaderboard($pdo, $gid, 50)]);
            break;

        default:
            json_error('Unknown action.', 400);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    json_error('Server error.', 500);
}
