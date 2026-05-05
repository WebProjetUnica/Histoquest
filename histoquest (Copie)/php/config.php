<?php
// php/config.php

// Démarrer la session sur toutes les pages qui incluent ce fichier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Chemins vers les données ───────────────────────────────────────────────
define('DATA_DIR',    __DIR__ . '/../data/');
define('USERS_FILE',  DATA_DIR . 'users.json');
define('GAMES_DIR',   DATA_DIR . 'games/');
define('SCORES_FILE', DATA_DIR . 'scores.json');

// ── Paramètres fixes du jeu ────────────────────────────────────────────────
define('GAME_TEAM_SIZE',     5);
define('GAME_TOURS',         7);
define('GAME_CHOICES',       4);
define('TIMER_VOTE',        30);
define('TIMER_CONTEST',     15);
define('TIMER_ARGUMENT',    30);
define('TIMER_REVOTE',      20);
define('TIMER_HINT_VOTE',   10);
define('DISCORD_THRESHOLD', 0.40);

// ── Points ─────────────────────────────────────────────────────────────────
define('PTS_CORRECT', 3);
define('PTS_HINT',    1);
define('PTS_HALF',    1);

// ── Créer les dossiers de données s'ils n'existent pas ────────────────────
if (!is_dir(DATA_DIR))  mkdir(DATA_DIR,  0755, true);
if (!is_dir(GAMES_DIR)) mkdir(GAMES_DIR, 0755, true);

// ── Fonctions utilitaires JSON ─────────────────────────────────────────────

function read_json(string $path): array {
    if (!file_exists($path)) return [];
    $fp   = fopen($path, 'r');
    flock($fp, LOCK_SH);
    $data = json_decode(stream_get_contents($fp), true) ?? [];
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}

function write_json(string $path, array $data): void {
    $fp = fopen($path, 'c');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}