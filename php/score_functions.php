<?php
// php/score_functions.php
require_once __DIR__ . '/config.php';

// ── Calculer la réponse majoritaire de l'équipe ────────────────────────────
function compute_majority(array $votes): string|null {
    if (empty($votes)) return null;

    $counts = array_count_values($votes);
    $max    = max($counts);

    // Vérifier qu'il n'y a pas d'égalité en tête
    $leaders = array_keys(array_filter($counts, fn($c) => $c === $max));
    if (count($leaders) > 1) return null; // égalité → pas de majorité

    return $leaders[0];
}

// ── Calculer le taux de désaccord ─────────────────────────────────────────
function compute_discord(array $votes, string $majority): float {
    if (empty($votes)) return 0.0;
    $divergents = count(array_filter($votes, fn($v) => $v !== $majority));
    return $divergents / count($votes);
}

// ── Calculer les points d'un tour pour chaque joueur ──────────────────────
function compute_tour_points(
    array       $votes,       // [user_id => pays_choisi]
    string|null $majority,    // réponse majoritaire de l'équipe
    string      $correct,     // bonne réponse
    bool        $hint_used,   // indice utilisé ce tour ?
    array|null  $contest      // données de contestation ou null
): array {                    // retourne [user_id => points]

    $points = [];

    // Barème de base
    $base_pts = 0;
    if ($majority === $correct) {
        $base_pts = $hint_used ? PTS_HINT : PTS_CORRECT;
    }

    foreach ($votes as $user_id => $pays_vote) {

        // Cas normal — pas de contestation ou pas le contestataire
        if (!$contest || $contest['player_id'] !== $user_id) {
            $points[$user_id] = $base_pts;
            continue;
        }

        // ── Pondération du contestataire ───────────────────────────────
        $contestation_juste = ($contest['pays_defendu'] === $correct);
        $equipe_correcte    = ($majority === $correct);

        if ($contestation_juste && $equipe_correcte) {
            // Juste + équipe a suivi → bonus +1
            $points[$user_id] = $base_pts + 1;

        } elseif ($contestation_juste && !$equipe_correcte) {
            // Juste + équipe a refusé → points pleins (il avait raison)
            $points[$user_id] = PTS_CORRECT;

        } elseif (!$contestation_juste && $equipe_correcte) {
            // Mauvaise + équipe garde le bon → malus -1
            $points[$user_id] = max(0, $base_pts - 1);

        } else {
            // Mauvaise + équipe aussi dans l'erreur → moitié
            $points[$user_id] = PTS_HALF;
        }
    }

    return $points;
}

// ── Appliquer les points au fichier users.json ────────────────────────────
function apply_points(array $tour_points): void {
    $users = read_json(USERS_FILE);

    foreach ($users as &$user) {
        if (isset($tour_points[$user['id']])) {
            $pts = $tour_points[$user['id']];
            $user['score_total'] += $pts;
        }
    }

    write_json(USERS_FILE, $users);
}

// ── Sauvegarder les points d'un tour dans le fichier de votes ─────────────
function save_tour_points(
    string $game_id,
    int    $tour,
    array  $tour_points
): void {
    $path       = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
    $votes_data = read_json($path);
    $votes_data['points'] = $tour_points;
    write_json($path, $votes_data);
}

// ── Compiler les scores finaux d'une partie ───────────────────────────────
function get_final_scores(string $game_id): array {
    $scores = [];
    $game   = read_json(GAMES_DIR . $game_id . '/game.json');

    if (!$game) return $scores;

    for ($t = 1; $t <= GAME_TOURS; $t++) {
        $path = GAMES_DIR . $game_id . '/tour_' . $t . '_votes.json';
        if (!file_exists($path)) continue;

        $votes_data = read_json($path);
        foreach ($votes_data['points'] ?? [] as $user_id => $pts) {
            $scores[$user_id] = ($scores[$user_id] ?? 0) + $pts;
        }
    }

    return $scores; // [user_id => score_total_cette_partie]
}

// ── Récupérer les scores avec les pseudos pour l'affichage ────────────────
function get_scores_display(string $game_id): array {
    $final  = get_final_scores($game_id);
    $users  = read_json(USERS_FILE);
    $result = [];

    foreach ($users as $user) {
        if (isset($final[$user['id']])) {
            $result[] = [
                'user_id' => $user['id'],
                'pseudo'  => $user['pseudo'],
                'score'   => $final[$user['id']],
            ];
        }
    }

    // Trier par score décroissant
    usort($result, fn($a, $b) => $b['score'] <=> $a['score']);

    return $result;
}