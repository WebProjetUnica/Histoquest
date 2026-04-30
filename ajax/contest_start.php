<?php
// ajax/contest_start.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';
require_once '../php/score_functions.php';

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

$user_id = $_SESSION['user_id'];

// Vérifier que le joueur appartient à cette équipe
$player_team = get_player_team($game_id, $user_id);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Vérifier qu'une contestation n'est pas déjà ouverte
if (!empty($votes_data['contest'])) {
    json_response(['ok' => false, 'error' => 'Une contestation est déjà ouverte'], 409);
}

// Vérifier que le joueur avait voté différemment de la majorité
$majority   = $votes_data['majority'] ?? null;
$player_vote = $votes_data['votes'][$user_id] ?? null;

if (!$player_vote) {
    json_response(['ok' => false, 'error' => 'Tu n\'as pas voté ce tour'], 400);
}

if ($player_vote === $majority) {
    json_response(['ok' => false, 'error' => 'Tu ne peux pas contester — tu faisais partie de la majorité'], 403);
}

// Ouvrir la contestation
$votes_data['contest'] = [
    'player_id'    => $user_id,
    'pseudo'       => $_SESSION['pseudo'],
    'pays_defendu' => null,   // rempli par submit_contest
    'text'         => null,   // rempli par submit_contest
    'open'         => true,
    'opened_at'    => time(),
];

write_json($votes_file, $votes_data);

json_response([
    'ok'     => true,
    'pseudo' => $_SESSION['pseudo'],
    'timer'  => TIMER_ARGUMENT,
]);