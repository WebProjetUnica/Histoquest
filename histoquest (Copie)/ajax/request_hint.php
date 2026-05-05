<?php
// ajax/request_hint.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

// Vérifier que le joueur est connecté
if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body    = json_decode(file_get_contents('php://input'), true);
$game_id = htmlspecialchars($body['game_id'] ?? '', ENT_QUOTES);
$team_id = htmlspecialchars($body['team_id'] ?? '', ENT_QUOTES);
$tour    = (int)($body['tour'] ?? 0);

if (empty($game_id) || empty($team_id) || $tour <= 0) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

// Vérifier que le joueur appartient à cette équipe
$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

// Charger le fichier de votes du tour
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Vérifier qu'un vote d'indice n'est pas déjà en cours
if (!empty($votes_data['hint_vote_open'])) {
    json_response(['ok' => false, 'error' => 'Un vote d\'indice est déjà en cours'], 409);
}

// Vérifier que l'indice n'a pas déjà été utilisé
if (!empty($votes_data['hint_used'])) {
    json_response(['ok' => false, 'error' => 'L\'indice a déjà été utilisé'], 409);
}

// Ouvrir le vote d'indice
$votes_data['hint_vote_open']    = true;
$votes_data['hint_votes']        = ['oui' => 0, 'non' => 0];
$votes_data['hint_voters']       = [];
$votes_data['hint_requester_id'] = $_SESSION['user_id'];
$votes_data['hint_vote_time']    = time();
write_json($votes_file, $votes_data);

json_response([
    'ok'          => true,
    'requester'   => $_SESSION['pseudo'],
    'timer'       => TIMER_HINT_VOTE,
]);