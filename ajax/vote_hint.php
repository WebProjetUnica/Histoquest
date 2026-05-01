<?php
// ajax/vote_hint.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body    = json_decode(file_get_contents('php://input'), true);
$game_id = htmlspecialchars($body['game_id'] ?? '', ENT_QUOTES);
$team_id = htmlspecialchars($body['team_id'] ?? '', ENT_QUOTES);
$tour    = (int)($body['tour']    ?? 0);
$vote    = htmlspecialchars($body['vote']    ?? '', ENT_QUOTES); // 'oui' ou 'non'

if (empty($game_id) || empty($team_id) || $tour <= 0 || !in_array($vote, ['oui', 'non'])) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

// Vérifier que le joueur appartient à cette équipe
$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Vérifier que le vote d'indice est bien ouvert
if (empty($votes_data['hint_vote_open'])) {
    json_response(['ok' => false, 'error' => 'Pas de vote d\'indice en cours'], 400);
}

$user_id = $_SESSION['user_id'];

// Vérifier que ce joueur n'a pas déjà voté pour l'indice
if (in_array($user_id, $votes_data['hint_voters'])) {
    json_response(['ok' => false, 'error' => 'Tu as déjà voté'], 409);
}

// Enregistrer le vote
$votes_data['hint_votes'][$vote]++;
$votes_data['hint_voters'][] = $user_id;

// Calculer le total de votants dans l'équipe
$game        = read_json(GAMES_DIR . $game_id . '/game.json');
$team        = $game['teams'][$team_id];
$total       = count($team['members']);
$voted_count = count($votes_data['hint_voters']);

// Vérifier si la majorité est atteinte
$oui_count = $votes_data['hint_votes']['oui'];
$non_count = $votes_data['hint_votes']['non'];
$result    = null;

if ($oui_count > intdiv($total, 2)) {
    // Majorité OUI — indice accordé
    $result                       = 'accepted';
    $votes_data['hint_used']      = true;
    $votes_data['hint_vote_open'] = false;

} elseif ($non_count > intdiv($total, 2) || $voted_count >= $total) {
    // Majorité NON ou tous ont voté — indice refusé
    $result                       = 'rejected';
    $votes_data['hint_vote_open'] = false;
}

write_json($votes_file, $votes_data);

json_response([
    'ok'          => true,
    'result'      => $result,        // 'accepted', 'rejected', ou null si pas encore décidé
    'oui'         => $oui_count,
    'non'         => $non_count,
    'voted'       => $voted_count,
    'total'       => $total,
]);