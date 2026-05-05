<?php
// php/ws_functions.php
// Fonctions pour communiquer avec le serveur WebSocket depuis PHP

function broadcast_to_ws(array $event): void {
    $socket = @stream_socket_client(
        'tcp://127.0.0.1:8080',
        $errno, $errstr, 2
    );

    if (!$socket) {
        return; // WS non accessible — on ignore silencieusement
    }

    $key = base64_encode(random_bytes(16));
    $handshake = "GET / HTTP/1.1\r\n"
        . "Host: 127.0.0.1:8080\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Key: $key\r\n"
        . "Sec-WebSocket-Version: 13\r\n\r\n";

    fwrite($socket, $handshake);
    fread($socket, 1500); // lire la réponse du handshake

    $json  = json_encode($event);
    $len   = strlen($json);
    $frame = chr(0x81);
    if ($len <= 125) {
        $frame .= chr($len);
    } elseif ($len <= 65535) {
        $frame .= chr(126) . pack('n', $len);
    } else {
        $frame .= chr(127) . pack('J', $len);
    }
    $frame .= $json;

    fwrite($socket, $frame);
    fclose($socket);
}

function broadcast_game_started(string $game_id): void {
    require_once __DIR__ . '/config.php';
    $game = read_json(GAMES_DIR . $game_id . '/game.json');
    if (!$game) return;

    foreach ($game['teams'] as $team_id => $team) {
        broadcast_to_ws([
            'type'    => 'game_started',
            'game_id' => $game_id,
            'team_id' => $team_id,
        ]);
    }
}