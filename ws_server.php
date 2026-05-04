<?php
// ws_server.php
// Lancer avec : php ws_server.php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/php/config.php';
require __DIR__ . '/php/game_functions.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class HistoQuestServer implements MessageComponentInterface {

    // Toutes les connexions actives
    private \SplObjectStorage $clients;

    // Métadonnées par connexion : user_id, game_id, team_id, pseudo
    private array $meta = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        echo "Serveur HistoQuest démarré...\n";
    }

    // ── Connexion d'un nouveau client ─────────────────────────────────────
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "[+] Nouvelle connexion : #{$conn->resourceId}\n";
    }

    // ── Réception d'un message ────────────────────────────────────────────
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!$data || !isset($data['type'])) {
            echo "[!] Message invalide de #{$from->resourceId}\n";
            return;
        }

        echo "[MSG] #{$from->resourceId} → type: {$data['type']}\n";

        switch ($data['type']) {

            // ── Identification du joueur ───────────────────────────────────
            case 'join':
                $this->meta[$from->resourceId] = [
                    'user_id' => $data['user_id'] ?? '',
                    'game_id' => $data['game_id'] ?? '',
                    'team_id' => $data['team_id'] ?? '',
                    'pseudo'  => $data['pseudo']  ?? '',
                ];
                echo "[ID] {$data['pseudo']} identifié dans game:{$data['game_id']} team:{$data['team_id']}\n";

                // Confirmer l'identification au client
                $from->send(json_encode([
                    'type'    => 'joined',
                    'message' => 'Connexion établie',
                ]));
                break;
            // ── Identification depuis le lobby ────────────────────────────────────
            case 'lobby_join':
                $this->meta[$from->resourceId] = [
                    'user_id' => $data['user_id'] ?? '',
                    'game_id' => $data['game_id'] ?? '',
                    'team_id' => $data['team_id'] ?? '',
                    'pseudo'  => $data['pseudo']  ?? '',
                    'context' => 'lobby',
                ];
                echo "[LOBBY] {$data['pseudo']} connecté au lobby\n";

                $from->send(json_encode([
                    'type'    => 'lobby_joined',
                    'message' => 'Connecté au lobby',
                ]));
                break;

            // ── Vote soumis — broadcaster le compteur à l'équipe ──────────
            case 'vote_update':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'  => 'vote_update',
                        'voted' => $data['voted'],
                        'total' => $data['total'],
                    ]
                );
                break;

            // ── Indice disponible — broadcaster à l'équipe ────────────────
            case 'hint_available':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type' => 'hint_available',
                        'text' => $data['text'],
                    ]
                );
                break;

            // ── Vote d'indice — broadcaster le mini-vote ──────────────────
            case 'hint_vote_opened':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'      => 'hint_vote_opened',
                        'requester' => $data['requester'],
                        'timer'     => $data['timer'],
                    ],
                    $from  // exclure l'expéditeur
                );
                break;

            // ── Contestation ouverte ───────────────────────────────────────
            case 'contest_opened':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'   => 'contest_opened',
                        'pseudo' => $data['pseudo'],
                        'timer'  => $data['timer'],
                    ],
                    $from
                );
                break;

            // ── Argument de contestation soumis ───────────────────────────
            case 'contest_message':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'            => 'contest_message',
                        'text'            => $data['text'],
                        'pays_defendu'    => $data['pays_defendu'],
                        'divergent_votes' => $data['divergent_votes'],
                        'majority'        => $data['majority'],
                        'revote'          => $data['revote'],
                        'timer'           => $data['timer'] ?? null,
                    ]
                );
                break;

            // ── Revote démarré ─────────────────────────────────────────────
            case 'revote_started':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'  => 'revote_started',
                        'timer' => $data['timer'],
                    ]
                );
                break;

            // ── Résultat du revote ─────────────────────────────────────────
            case 'revote_result':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'         => 'revote_result',
                        'answer'       => $data['answer'],
                        'correct'      => $data['correct'],
                        'scores'       => $data['scores'] ?? [],
                    ]
                );
                break;

            // ── Invitation envoyée à un joueur ────────────────────────────
            case 'invitation':
                $this->sendToUser(
                    $data['target_id'],
                    [
                        'type'        => 'invitation',
                        'invite_id'   => $data['invite_id'],
                        'from_pseudo' => $data['from_pseudo'],
                        'team_name'   => $data['team_name'],
                        'game_id'     => $data['game_id'],
                        'team_id'     => $data['team_id'],
                    ]
                );
                break;

            // ── Mise à jour de l'équipe dans le lobby ─────────────────────
            case 'team_update':
                $this->broadcastToTeam(
                    $data['game_id'],
                    $data['team_id'],
                    [
                        'type'    => 'team_update',
                        'members' => $data['members'],
                    ]
                );
                break;

            default:
                echo "[?] Type inconnu : {$data['type']}\n";
        }
    }

    // ── Fermeture d'une connexion ─────────────────────────────────────────
    public function onClose(ConnectionInterface $conn) {
        $meta = $this->meta[$conn->resourceId] ?? null;
        $who  = $meta ? $meta['pseudo'] : "#{$conn->resourceId}";
        echo "[-] Déconnexion : $who\n";

        $this->clients->detach($conn);
        unset($this->meta[$conn->resourceId]);
    }

    // ── Erreur de connexion ───────────────────────────────────────────────
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[ERR] {$e->getMessage()}\n";
        $conn->close();
    }

    // ── Diffuser un message à tous les membres d'une équipe ───────────────
    public function broadcastToTeam(
        string $game_id,
        string $team_id,
        array  $event,
        ?ConnectionInterface $exclude = null
    ): void {
        $json = json_encode($event);

        foreach ($this->clients as $client) {
            $m = $this->meta[$client->resourceId] ?? null;
            if (!$m) continue;
            if ($m['game_id'] !== $game_id) continue;
            if ($m['team_id'] !== $team_id) continue;
            if ($exclude && $client === $exclude) continue;
            $client->send($json);
        }
    }

    // ── Envoyer un message à un joueur spécifique (par user_id) ───────────
    public function sendToUser(string $user_id, array $event): void {
        $json = json_encode($event);

        foreach ($this->clients as $client) {
            $m = $this->meta[$client->resourceId] ?? null;
            if ($m && $m['user_id'] === $user_id) {
                $client->send($json);
                return;
            }
        }
        echo "[!] Utilisateur $user_id non connecté au WebSocket\n";
    }
}

// ── Démarrer le serveur sur le port 8080 ──────────────────────────────────
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new HistoQuestServer()
        )
    ),
    8080
);

echo "Serveur WebSocket HistoQuest démarré sur le port 8080\n";
echo "En attente de connexions...\n";
$server->run();