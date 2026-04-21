<?php
// php/auth_functions.php
require_once __DIR__ . '/config.php';

// ── Inscrire un nouvel utilisateur ─────────────────────────────────────────
function register_user(string $pseudo, string $password): array {

    // Valider les données
    if (strlen($pseudo) < 3) {
        return ['ok' => false, 'error' => 'Le pseudo doit faire au moins 3 caractères.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Le mot de passe doit faire au moins 8 caractères.'];
    }

    // Vérifier que le pseudo n'existe pas déjà
    $users = read_json(USERS_FILE);
    foreach ($users as $u) {
        if (strtolower($u['pseudo']) === strtolower($pseudo)) {
            return ['ok' => false, 'error' => 'Ce pseudo est déjà utilisé.'];
        }
    }

    // Créer le nouvel utilisateur
    $user = [
        'id'              => uniqid('u_'),
        'pseudo'          => htmlspecialchars($pseudo, ENT_QUOTES),
        'password'        => password_hash($password, PASSWORD_DEFAULT),
        'score_total'     => 0,
        'parties'         => 0,
        'contests_won'    => 0,
        'created_at'      => date('Y-m-d H:i:s'),
    ];

    $users[] = $user;
    write_json(USERS_FILE, $users);

    return ['ok' => true, 'user' => $user];
}

// ── Connecter un utilisateur ───────────────────────────────────────────────
function login_user(string $pseudo, string $password): array|false {

    $users = read_json(USERS_FILE);

    foreach ($users as $u) {
        if (strtolower($u['pseudo']) === strtolower($pseudo)) {
            // Pseudo trouvé — vérifier le mot de passe
            if (password_verify($password, $u['password'])) {
                // Ouvrir la session
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['pseudo']  = $u['pseudo'];
                return $u;
            }
            // Pseudo correct mais mauvais mot de passe
            return false;
        }
    }

    // Pseudo introuvable
    return false;
}

// ── Récupérer un utilisateur par son ID ───────────────────────────────────
function get_user_by_id(string $user_id): array|null {
    $users = read_json(USERS_FILE);
    foreach ($users as $u) {
        if ($u['id'] === $user_id) return $u;
    }
    return null;
}

// ── Vérifier si un pseudo existe (pour les invitations) ───────────────────
function pseudo_exists(string $pseudo): bool {
    $users = read_json(USERS_FILE);
    foreach ($users as $u) {
        if (strtolower($u['pseudo']) === strtolower($pseudo)) return true;
    }
    return false;
}