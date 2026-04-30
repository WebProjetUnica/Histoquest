<?php
// ajax/vote_contest.php
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
$pays    = htmlspecialchars($body['pays']    ?? '', ENT_QUOTES);

if (empty($game_id) || empty($team_id) || $tour <= 0 || empty($pays)) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

$user_id    = $_SESSION['user_id'];
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Vérifier que le revote est ouvert
if (empty($votes_data['revote']['open'])) {
    json_response(['ok' => false, 'error' => 'Pas de revote en cours'], 400);
}

// Vérifier que ce joueur n'a pas déjà voté au revote
if (isset($votes_data['revote']['votes'][$user_id])) {
    json_response(['ok' => false, 'error' => 'Tu as déjà voté'], 409);
}

// Enregistrer le vote du revote
$votes_data['revote']['votes'][$user_id] = $pays;

// Calculer la nouvelle majorité
$game        = read_json(GAMES_DIR . $game_id . '/game.json');
$team        = $game['teams'][$team_id];
$total       = count($team['members']);
$voted_count = count($votes_data['revote']['votes']);

$new_majority = null;
$revote_done  = false;

if ($voted_count >= $total) {
    // Tous ont voté — calculer la nouvelle majorité
    $new_majority = compute_majority($votes_data['revote']['votes']);
    $votes_data['majority']      = $new_majority;
    $votes_data['revote']['open'] = false;
    $revote_done = true;
}

write_json($votes_file, $votes_data);

json_response([
    'ok'          => true,
    'voted'       => $voted_count,
    'total'       => $total,
    'revote_done' => $revote_done,
    'new_majority'=> $new_majority,
    'correct'     => $votes_data['correct'] ?? null,
]);