<?php
// ajax/invite_player.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/auth_functions.php';
require_once '../php/game_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body          = json_decode(file_get_contents('php://input'), true);
$game_id       = htmlspecialchars($body['game_id']       ?? '', ENT_QUOTES);
$team_id       = htmlspecialchars($body['team_id']       ?? '', ENT_QUOTES);
$target_pseudo = htmlspecialchars($body['target_pseudo'] ?? '', ENT_QUOTES);

if (empty($game_id) || empty($team_id) || empty($target_pseudo)) {
    json_response(['ok' => false, 'error' => 'Données manquantes'], 400);
}

// Vérifier que l'invitant appartient à cette équipe
$player_team = get_player_team($game_id, $_SESSION['user_id']);
if ($player_team !== $team_id) {
    json_response(['ok' => false, 'error' => 'Non autorisé'], 403);
}

// Vérifier que le pseudo cible existe
if (!pseudo_exists($target_pseudo)) {
    json_response(['ok' => false, 'error' => 'Joueur introuvable'], 404);
}

// Vérifier que le joueur cible n'est pas déjà dans cette partie
$users = read_json(USERS_FILE);
$target_user = null;
foreach ($users as $u) {
    if (strtolower($u['pseudo']) === strtolower($target_pseudo)) {
        $target_user = $u;
        break;
    }
}

$already_in = get_player_team($game_id, $target_user['id']);
if ($already_in) {
    json_response(['ok' => false, 'error' => 'Ce joueur est déjà dans cette partie'], 409);
}

// Vérifier que l'équipe n'est pas pleine
$game = get_game($game_id);
$team = $game['teams'][$team_id];
if (count($team['members']) >= GAME_TEAM_SIZE) {
    json_response(['ok' => false, 'error' => 'L\'équipe est complète'], 409);
}

// Enregistrer l'invitation dans un fichier
$invite_file = GAMES_DIR . $game_id . '/invites.json';
$invites     = read_json($invite_file);

// Vérifier qu'une invitation n'est pas déjà en attente pour ce joueur
foreach ($invites as $inv) {
    if ($inv['target_id'] === $target_user['id'] && $inv['status'] === 'pending') {
        json_response(['ok' => false, 'error' => 'Une invitation est déjà en attente pour ce joueur'], 409);
    }
}

// Créer l'invitation
$invitation = [
    'id'          => uniqid('inv_'),
    'game_id'     => $game_id,
    'team_id'     => $team_id,
    'team_name'   => $team['name'],
    'from_id'     => $_SESSION['user_id'],
    'from_pseudo' => $_SESSION['pseudo'],
    'target_id'   => $target_user['id'],
    'target_pseudo'=> $target_pseudo,
    'status'      => 'pending',
    'created_at'  => time(),
];

$invites[] = $invitation;
write_json($invite_file, $invites);

json_response([
    'ok'        => true,
    'invitation'=> $invitation,
]);