<?php
// ajax/get_scores.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/score_functions.php';
require_once '../php/game_functions.php';

// Vérifier que le joueur est connecté
if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

// Récupérer le game_id depuis l'URL (?game_id=...)
$game_id = htmlspecialchars($_GET['game_id'] ?? '', ENT_QUOTES);

if (empty($game_id)) {
    json_response(['ok' => false, 'error' => 'game_id manquant'], 400);
}

// Vérifier que la partie existe
$game = get_game($game_id);
if (!$game) {
    json_response(['ok' => false, 'error' => 'Partie introuvable'], 404);
}

// Récupérer les scores avec les pseudos
$scores = get_scores_display($game_id);

// Ajouter les infos d'équipe pour chaque joueur
foreach ($scores as &$entry) {
    $entry['team_id'] = get_player_team($game_id, $entry['user_id']);
}

// Récupérer l'indice du tour en cours
$tour       = $game['tour'] ?? 1;
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$tour_data  = read_json($votes_file);
$indice     = $tour_data['indice'] ?? null;

// Ajouter les infos d'équipe pour chaque joueur
foreach ($scores as &$entry) {
    $entry['team_id'] = get_player_team($game_id, $entry['user_id']);
}

json_response([
    'ok'     => true,
    'scores' => $scores,
    'tour'   => $game['tour'],
    'status' => $game['status'],
    'indice' => $indice,
]);