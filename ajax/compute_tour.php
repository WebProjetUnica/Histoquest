<?php
// ajax/compute_tour.php
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

// Vérifier que le joueur appartient à cette équipe
$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

// Charger les votes du tour
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

if (empty($votes_data)) {
    json_response(['ok' => false, 'error' => 'Aucun vote trouvé pour ce tour'], 404);
}

// Calculer la majorité
$votes    = $votes_data['votes'] ?? [];
$majority = compute_majority($votes);
$correct  = $votes_data['correct'] ?? null;
$hint_used = $votes_data['hint_used'] ?? false;
$contest  = $votes_data['contest'] ?? null;

// Calculer les points pour chaque joueur
$tour_points = compute_tour_points(
    $votes,
    $majority,
    $correct,
    $hint_used,
    $contest
);

// Sauvegarder les points dans le fichier de votes
$votes_data['majority'] = $majority;
$votes_data['points']   = $tour_points;
write_json($votes_file, $votes_data);

// Appliquer les points aux scores globaux dans users.json
apply_points($tour_points);

// Retourner le résultat complet
json_response([
    'ok'       => true,
    'majority' => $majority,
    'correct'  => $correct,
    'points'   => $tour_points,
    'hint_used'=> $hint_used,
    'is_correct' => ($majority === $correct),
]);