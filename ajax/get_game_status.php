<?php
// ajax/get_game_status.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php/config.php';
require_once '../php/game_functions.php';

$game_id = $_GET['game_id'] ?? '';
$game    = get_game($game_id);

json_response([
    'ok'     => true,
    'status' => $game['status'] ?? 'unknown',
    'tour'   => $game['tour']   ?? 0,
]);