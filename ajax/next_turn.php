<?php
// ajax/next_turn.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';
require_once '../php/ws_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body    = json_decode(file_get_contents('php://input'), true);
$game_id = htmlspecialchars($body['game_id'] ?? '', ENT_QUOTES);
$team_id = htmlspecialchars($body['team_id'] ?? '', ENT_QUOTES);

if (empty($game_id) || empty($team_id)) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

$result = next_turn($game_id);

if (!$result['ok']) {
    json_response(['ok' => false, 'error' => $result['error']], 400);
}

if ($result['finished']) {
    // Partie terminée — broadcaster game_over
    broadcast_to_ws([
        'type'    => 'game_over',
        'game_id' => $game_id,
        'team_id' => $team_id,
    ]);

    json_response([
        'ok'       => true,
        'finished' => true,
        'redirect' => 'results.php?game_id=' . $game_id,
    ]);
}

// Broadcaster new_turn à toute l'équipe
broadcast_to_ws([
    'type'    => 'new_turn',
    'game_id' => $game_id,
    'team_id' => $team_id,
    'tour'    => $result['tour'],
]);

json_response([
    'ok'       => true,
    'finished' => false,
    'tour'     => $result['tour'],
]);