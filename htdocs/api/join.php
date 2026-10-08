<?php
// POST api/join.php  {pin, name}  -> {token, player_id}
// Players can only join while game is in lobby.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only.', 405);

$in = $_POST;
if (empty($in['pin']) || empty($in['name'])) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) $in = array_merge($in, $raw);
}
$pin = (string)($in['pin'] ?? '');
$name = e_clean_name($in['name'] ?? '');

if (!e_valid_pin($pin)) json_error('Invalid game PIN. It has 6 digits.', 400);
if (!e_valid_name($name)) json_error('Name must be 2–20 letters/numbers.', 400);

try {
    $pdo = db();
    $game = e_get_game($pdo, $pin);
    if (!$game) json_error('Game not found. Check the PIN.', 404);
    if ($game['status'] !== 'lobby') json_error('This game already started.', 409);

    // duplicate name?
    $st = $pdo->prepare("SELECT id FROM players WHERE game_id = ? AND name = ? LIMIT 1");
    $st->execute([$game['id'], $name]);
    if ($st->fetch()) json_error('Name taken, pick another one.', 409);

    $token = bin2hex(random_bytes(24));
    $st = $pdo->prepare("INSERT INTO players (game_id, name, token, lives, last_seen) VALUES (?, ?, ?, ?, ?)");
    $st->execute([$game['id'], $name, $token, e_start_lives(), e_now()]);

    json_out(['ok' => true, 'token' => $token, 'player_id' => (int)$pdo->lastInsertId(), 'name' => $name, 'pin' => $pin]);
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) == 1062) json_error('Name taken, pick another one.', 409);
    json_error('Server error.', 500);
} catch (Throwable $e) {
    json_error('Server error.', 500);
}
