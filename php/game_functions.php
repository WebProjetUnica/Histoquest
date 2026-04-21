<?php
// php/game_functions.php
require_once __DIR__ . '/config.php';

// ── Créer une nouvelle partie ──────────────────────────────────────────────
function create_game(string $creator_id, string $creator_pseudo): array {
    $game_id  = uniqid('game_');
    $game_dir = GAMES_DIR . $game_id . '/';
    mkdir($game_dir, 0755, true);

    $game = [
        'id'         => $game_id,
        'status'     => 'waiting',   // waiting | playing | finished
        'tour'       => 0,
        'max_tours'  => GAME_TOURS,
        'created_at' => date('Y-m-d H:i:s'),
        'teams'      => [
            'equipe_1' => [
                'name'    => 'Équipe 1',
                'members' => [
                    ['id' => $creator_id, 'pseudo' => $creator_pseudo]
                ]
            ],
            'equipe_2' => [
                'name'    => 'Équipe 2',
                'members' => []
            ]
        ]
    ];

    write_json($game_dir . 'game.json', $game);
    return $game;
}

// ── Récupérer une partie par son ID ───────────────────────────────────────
function get_game(string $game_id): array|null {
    $path = GAMES_DIR . $game_id . '/game.json';
    if (!file_exists($path)) return null;
    return read_json($path);
}

// ── Récupérer toutes les parties en attente ────────────────────────────────
function get_waiting_games(): array {
    $games = [];
    if (!is_dir(GAMES_DIR)) return $games;

    foreach (scandir(GAMES_DIR) as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        $game = get_game($dir);
        if ($game && $game['status'] === 'waiting') {
            $games[] = $game;
        }
    }
    return $games;
}

// ── Rejoindre une équipe ───────────────────────────────────────────────────
function join_team(
    string $game_id,
    string $team_id,
    string $user_id,
    string $pseudo
): array {
    $game_dir = GAMES_DIR . $game_id . '/';
    $game     = read_json($game_dir . 'game.json');

    if (!$game) {
        return ['ok' => false, 'error' => 'Partie introuvable.'];
    }

    if ($game['status'] !== 'waiting') {
        return ['ok' => false, 'error' => 'La partie a déjà commencé.'];
    }

    if (!isset($game['teams'][$team_id])) {
        return ['ok' => false, 'error' => 'Équipe introuvable.'];
    }

    // Vérifier que le joueur n'est pas déjà dans cette partie
    foreach ($game['teams'] as $team) {
        foreach ($team['members'] as $member) {
            if ($member['id'] === $user_id) {
                return ['ok' => false, 'error' => 'Tu es déjà dans cette partie.'];
            }
        }
    }

    // Vérifier que l'équipe n'est pas pleine
    $team = $game['teams'][$team_id];
    if (count($team['members']) >= GAME_TEAM_SIZE) {
        return ['ok' => false, 'error' => 'Cette équipe est complète.'];
    }

    // Ajouter le joueur
    $game['teams'][$team_id]['members'][] = [
        'id'     => $user_id,
        'pseudo' => $pseudo
    ];

    write_json($game_dir . 'game.json', $game);

    return ['ok' => true, 'game' => $game];
}

// ── Vérifier si une partie peut démarrer ──────────────────────────────────
function can_start_game(string $game_id): bool {
    $game = get_game($game_id);
    if (!$game) return false;

    foreach ($game['teams'] as $team) {
        // Chaque équipe doit avoir au moins 3 joueurs
        if (count($team['members']) < 3) return false;
    }
    return true;
}

// ── Démarrer une partie ────────────────────────────────────────────────────
function start_game(string $game_id): array {
    $game_dir = GAMES_DIR . $game_id . '/';
    $game     = read_json($game_dir . 'game.json');

    if (!$game) {
        return ['ok' => false, 'error' => 'Partie introuvable.'];
    }

    if (!can_start_game($game_id)) {
        return ['ok' => false, 'error' => 'Pas assez de joueurs.'];
    }

    $game['status'] = 'playing';
    $game['tour']   = 1;
    write_json($game_dir . 'game.json', $game);

    return ['ok' => true, 'game' => $game];
}

// ── Trouver l'équipe d'un joueur dans une partie ───────────────────────────
function get_player_team(string $game_id, string $user_id): string|null {
    $game = get_game($game_id);
    if (!$game) return null;

    foreach ($game['teams'] as $team_id => $team) {
        foreach ($team['members'] as $member) {
            if ($member['id'] === $user_id) return $team_id;
        }
    }
    return null;
}