<?php
// POST api/answer.php {pin, token, selected 0-3}
// Server-validated: membership, single answer, time window. Computes score itself.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only.', 405);

$in = $_POST;
if (!isset($in['pin'])) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $in = array_merge($in, $raw);
}
$pin = (string)($in['pin'] ?? '');
$token = (string)($in['token'] ?? '');
$sel = isset($in['selected']) ? (int)$in['selected'] : -1;

if (!e_valid_pin($pin)) json_error('Invalid PIN.', 400);
if ($token === '' || $sel < 0 || $sel > 3) json_error('Invalid answer.', 400);

try {
    $pdo = db();
    $game = e_get_game($pdo, $pin);
    if (!$game) json_error('Game not found.', 404);
    $game = e_maybe_expire($pdo, $game);

    if ($game['status'] !== 'question') json_error('Not accepting answers right now.', 409);
    $now = e_now();
    if (!empty($game['current_ends_at']) && $now >= (int)$game['current_ends_at']) json_error('Time is up.', 409);

    $st = $pdo->prepare("SELECT id, lives FROM players WHERE game_id = ? AND token = ? LIMIT 1");
    $st->execute([$game['id'], $token]);
    $player = $st->fetch();
    if (!$player) json_error('Player not in this game.', 403);
    $pid = (int)$player['id'];
    if ((int)$player['lives'] <= 0) json_error('Eliminated.', 403);

    $gq = e_current_gq($pdo, $game);
    if (!$gq) json_error('No active question.', 409);

    // already answered?
    $st = $pdo->prepare("SELECT id FROM answers WHERE game_question_id = ? AND player_id = ? LIMIT 1");
    $st->execute([$gq['id'], $pid]);
    if ($st->fetch()) json_error('Already answered.', 409);

    $correct = ((int)$gq['correct_option'] === $sel);
    $elapsed_ms = max(0, $now * 1000 - (int)$game['current_started_at'] * 1000);
    $points = e_calc_points($correct, $elapsed_ms, (int)$gq['time_limit']);

    $pdo->beginTransaction();
    $st = $pdo->prepare("INSERT INTO answers (game_id, game_question_id, player_id, selected_option, is_correct, points, response_ms, answered_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    try {
        $st->execute([$game['id'], $gq['id'], $pid, $sel, $correct ? 1 : 0, $points, $elapsed_ms, $now]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (($e->errorInfo[1] ?? 0) == 1062) json_error('Already answered.', 409);
        throw $e;
    }
    if ($correct) {
        $pdo->prepare("UPDATE players SET score = score + ?, correct_count = correct_count + 1 WHERE id = ?")->execute([$points, $pid]);
    } else {
        // wrong answer costs one life
        $pdo->prepare("UPDATE players SET lives = GREATEST(0, lives - 1) WHERE id = ?")->execute([$pid]);
    }
    $pdo->commit();

    json_out(['ok' => true, 'accepted' => true]);
} catch (Throwable $e) {
    if ($e instanceof PDOException && isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $msg = ($e instanceof RuntimeException) ? $e->getMessage() : 'Server error.';
    // json_error already exited on validation paths; reaching here = 500
    if (!headers_sent()) json_error($msg, 500);
}
