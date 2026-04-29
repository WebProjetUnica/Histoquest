<?php
// ajax/submit_vote.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

// Vérifier que le joueur est connecté
if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

// Lire le corps JSON de la requête
$body    = json_decode(file_get_contents('php://input'), true);
$game_id = htmlspecialchars($body['game_id'] ?? '', ENT_QUOTES);
$team_id = htmlspecialchars($body['team_id'] ?? '', ENT_QUOTES);
$tour    = (int)($body['tour']    ?? 0);
$pays    = htmlspecialchars($body['pays']    ?? '', ENT_QUOTES);

// Vérifications de base
if (empty($game_id) || empty($team_id) || empty($pays) || $tour <= 0) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

// Vérifier que la partie existe
$game = get_game($game_id);
if (!$game) {
    json_response(['ok' => false, 'error' => 'Partie introuvable'], 404);
}

// Vérifier que le joueur appartient bien à cette équipe
$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Tu n\'appartiens pas à cette équipe'], 403);
}

// Charger ou créer le fichier de votes du tour
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Initialiser la structure si le fichier est vide
if (empty($votes_data)) {
    $votes_data = [
        'tour'       => $tour,
        'game_id'    => $game_id,
        'team_id'    => $team_id,
        'votes'      => [],
        'hint_used'  => false,
        'contest'    => null,
        'majority'   => null,
        'correct'    => null,
        'points'     => [],
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

// Vérifier que ce joueur n'a pas déjà voté
$user_id = $_SESSION['user_id'];
if (isset($votes_data['votes'][$user_id])) {
    json_response(['ok' => false, 'error' => 'Tu as déjà voté ce tour'], 409);
}

// Enregistrer le vote
$votes_data['votes'][$user_id] = $pays;
write_json($votes_file, $votes_data);

// Calculer le nombre de votants pour le broadcast WebSocket
$team        = $game['teams'][$team_id];
$total       = count($team['members']);
$voted_count = count($votes_data['votes']);

// Répondre avec le statut du vote
json_response([
    'ok'          => true,
    'voted'       => $voted_count,
    'total'       => $total,
    'all_voted'   => $voted_count >= $total,
]);