<?php
// ajax/accept_invite.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Non connecté'], 401);
}

$body      = json_decode(file_get_contents('php://input'), true);
$invite_id = htmlspecialchars($body['invite_id'] ?? '', ENT_QUOTES);
$accept    = (bool)($body['accept'] ?? false);

if (empty($invite_id)) {
    json_response(['ok' => false, 'error' => 'invite_id manquant'], 400);
}

$user_id = $_SESSION['user_id'];

// Chercher l'invitation dans tous les dossiers de parties
$invitation  = null;
$invite_file = null;

foreach (scandir(GAMES_DIR) as $dir) {
    if ($dir === '.' || $dir === '..') continue;
    $path = GAMES_DIR . $dir . '/invites.json';
    if (!file_exists($path)) continue;

    $invites = read_json($path);
    foreach ($invites as $inv) {
        if ($inv['id'] === $invite_id && $inv['target_id'] === $user_id) {
            $invitation  = $inv;
            $invite_file = $path;
            break 2;
        }
    }
}

if (!$invitation) {
    json_response(['ok' => false, 'error' => 'Invitation introuvable'], 404);
}

if ($invitation['status'] !== 'pending') {
    json_response(['ok' => false, 'error' => 'Invitation déjà traitée'], 409);
}

// Mettre à jour le statut de l'invitation
$invites = read_json($invite_file);
foreach ($invites as &$inv) {
    if ($inv['id'] === $invite_id) {
        $inv['status'] = $accept ? 'accepted' : 'refused';
        break;
    }
}
write_json($invite_file, $invites);

// Si accepté — rejoindre l'équipe
if ($accept) {
    $result = join_team(
        $invitation['game_id'],
        $invitation['team_id'],
        $user_id,
        $_SESSION['pseudo']
    );

    if (!$result['ok']) {
        json_response(['ok' => false, 'error' => $result['error']], 400);
    }

    // Mettre à jour la session
    $_SESSION['game_id'] = $invitation['game_id'];
    $_SESSION['team_id'] = $invitation['team_id'];

    json_response([
        'ok'       => true,
        'accepted' => true,
        'game_id'  => $invitation['game_id'],
        'team_id'  => $invitation['team_id'],
        'team_name'=> $invitation['team_name'],
    ]);
}

json_response(['ok' => true, 'accepted' => false]);