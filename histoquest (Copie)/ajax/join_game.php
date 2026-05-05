<?php
// ajax/join_game.php
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

$user_id = $_SESSION['user_id'];
$pseudo  = $_SESSION['pseudo'];

// Rejoindre l'équipe
$result = join_team($game_id, $team_id, $user_id, $pseudo);
if (!$result['ok']) {
    json_response(['ok' => false, 'error' => $result['error']], 400);
}

// Mettre à jour la session
$_SESSION['game_id'] = $game_id;
$_SESSION['team_id'] = $team_id;

// Récupérer la liste des membres mise à jour
$game    = get_game($game_id);
$members = $game['teams'][$team_id]['members'] ?? [];

// Broadcaster team_update à tous les membres de l'équipe
broadcast_to_ws([
    'type'    => 'team_update',
    'game_id' => $game_id,
    'team_id' => $team_id,
    'members' => $members,
]);

json_response([
    'ok'      => true,
    'game_id' => $game_id,
    'team_id' => $team_id,
    'members' => $members,
]);