<?php
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

$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

if (empty($votes_data)) {
    json_response(['ok' => false, 'error' => 'Aucun vote trouvé pour ce tour'], 404);
}

// Guard — si majority déjà calculée on retourne juste le résultat sans recalculer
if (!empty($votes_data['majority'])) {
    $game  = get_game($game_id);
    $team  = $game['teams'][$team_id] ?? [];
    $total = count($team['members'] ?? []);
    json_response([
        'ok'         => true,
        'majority'   => $votes_data['majority'],
        'correct'    => $votes_data['correct'] ?? null,
        'points'     => $votes_data['points']  ?? [],
        'hint_used'  => $votes_data['hint_used'] ?? false,
        'is_correct' => ($votes_data['majority'] === ($votes_data['correct'] ?? null)),
        'total'      => $total,
    ]);
}

$votes     = $votes_data['votes']    ?? [];
$majority  = compute_majority($votes);
$correct   = $votes_data['correct']  ?? null;
$hint_used = $votes_data['hint_used'] ?? false;
$contest   = $votes_data['contest']  ?? null;

$tour_points = compute_tour_points($votes, $majority, $correct, $hint_used, $contest);

// Sauvegarder + reset état indice pour le prochain tour
$votes_data['majority']       = $majority;
$votes_data['points']         = $tour_points;
$votes_data['hint_vote_open'] = false;
$votes_data['hint_voters']    = [];
write_json($votes_file, $votes_data);

apply_points($tour_points);

$game  = get_game($game_id);
$team  = $game['teams'][$team_id] ?? [];
$total = count($team['members'] ?? []);

json_response([
    'ok'         => true,
    'majority'   => $majority,
    'correct'    => $correct,
    'points'     => $tour_points,
    'hint_used'  => $hint_used,
    'is_correct' => ($majority === $correct),
    'total'      => $total,
]);