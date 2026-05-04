<?php
// ajax/get_games.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$waiting_games = get_waiting_games();

// Formater les données pour le JavaScript
$games_data = [];
foreach ($waiting_games as $game) {
    $teams_data = [];
    foreach ($game['teams'] as $team_id => $team) {
        $teams_data[$team_id] = [
            'name'    => $team['name'],
            'members' => $team['members'],
            'count'   => count($team['members']),
            'full'    => count($team['members']) >= GAME_TEAM_SIZE,
        ];
    }
    $games_data[] = [
        'id'         => $game['id'],
        'created_at' => $game['created_at'],
        'teams'      => $teams_data,
    ];
}

json_response([
    'ok'    => true,
    'games' => $games_data,
]);