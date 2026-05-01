<?php
// ajax/submit_contest.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body         = json_decode(file_get_contents('php://input'), true);
$game_id      = htmlspecialchars($body['game_id']      ?? '', ENT_QUOTES);
$team_id      = htmlspecialchars($body['team_id']      ?? '', ENT_QUOTES);
$tour         = (int)($body['tour']                    ?? 0);
$text         = htmlspecialchars($body['text']         ?? '', ENT_QUOTES);
$pays_defendu = htmlspecialchars($body['pays_defendu'] ?? '', ENT_QUOTES);

if (empty($game_id) || empty($team_id) || $tour <= 0 || empty($pays_defendu)) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

// Limiter le texte à 150 caractères
$text = mb_substr($text, 0, 150);

$user_id    = $_SESSION['user_id'];
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Vérifier que la contestation est ouverte et appartient à ce joueur
if (empty($votes_data['contest']) || !$votes_data['contest']['open']) {
    json_response(['ok' => false, 'error' => 'Pas de contestation ouverte'], 400);
}

if ($votes_data['contest']['player_id'] !== $user_id) {
    json_response(['ok' => false, 'error' => 'Tu n\'es pas le contestataire'], 403);
}

// Enregistrer l'argument
$votes_data['contest']['text']         = $text;
$votes_data['contest']['pays_defendu'] = $pays_defendu;
$votes_data['contest']['open']         = false;
$votes_data['contest']['submitted_at'] = time();

// Calculer le taux de désaccord pour décider si revote
$majority = $votes_data['majority'];
$discord  = compute_discord($votes_data['votes'], $majority);
$revote   = $discord >= DISCORD_THRESHOLD;

if ($revote) {
    // Initialiser le revote
    $votes_data['revote'] = [
        'open'    => true,
        'votes'   => [],
        'opened_at' => time(),
    ];
}

write_json($votes_file, $votes_data);

json_response([
    'ok'              => true,
    'text'            => $text,
    'pays_defendu'    => $pays_defendu,
    'revote'          => $revote,
    'discord'         => round($discord * 100),
    'divergent_votes' => $votes_data['votes'],
    'majority'        => $majority,
    'timer'           => $revote ? TIMER_REVOTE : null,
]);