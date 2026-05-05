<?php
// ajax/start_game.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';
require_once '../php/ws_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$game_id = $_SESSION['game_id'] ?? '';

if (empty($game_id)) {
    json_response(['ok' => false, 'error' => 'Pas de partie en session'], 400);
}

if (!can_start_game($game_id)) {
    json_response(['ok' => false, 'error' => 'Pas assez de joueurs'], 400);
}

$result = start_game($game_id);

if (!$result['ok']) {
    json_response(['ok' => false, 'error' => $result['error']], 400);
}

// Broadcaster game_started à tous les joueurs
broadcast_game_started($game_id);

json_response([
    'ok'      => true,
    'game_id' => $game_id,
]);