<?php
// results.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once 'php/config.php';
require_once 'php/game_functions.php';
require_once 'php/score_functions.php';

$user_id = $_SESSION['user_id'];
$game_id = $_GET['game_id'] ?? $_SESSION['game_id'] ?? null;

// Rediriger si pas de partie
if (!$game_id) {
    header('Location: lobby.php');
    exit;
}

$game = get_game($game_id);
if (!$game) {
    header('Location: lobby.php');
    exit;
}

// Compiler les scores finaux de la partie
$final_scores = get_final_scores($game_id);
$users        = read_json(USERS_FILE);

// Construire le tableau des résultats avec pseudos et équipes
$results = [];
foreach ($users as $u) {
    if (isset($final_scores[$u['id']])) {
        $team_id  = get_player_team($game_id, $u['id']);
        $team_name = $game['teams'][$team_id]['name'] ?? 'Inconnue';
        $results[] = [
            'user_id'   => $u['id'],
            'pseudo'    => $u['pseudo'],
            'score'     => $final_scores[$u['id']],
            'team_id'   => $team_id,
            'team_name' => $team_name,
            'is_me'     => $u['id'] === $user_id,
        ];
    }
}

// Trier par score décroissant
usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

// Trouver le meilleur contestataire
$best_contestant = null;
$best_contest_pts = -1;

for ($t = 1; $t <= GAME_TOURS; $t++) {
    $path = GAMES_DIR . $game_id . '/tour_' . $t . '_votes.json';
    if (!file_exists($path)) continue;

    $votes_data = read_json($path);
    $contest    = $votes_data['contest'] ?? null;
    if (!$contest) continue;

    $contest_pts = $votes_data['points'][$contest['player_id']] ?? 0;
    if ($contest_pts > $best_contest_pts) {
        $best_contest_pts = $contest_pts;
        $best_contestant  = $contest['pseudo'];
    }
}

// Nettoyer la session de jeu
unset($_SESSION['game_id']);
unset($_SESSION['team_id']);

$page_title = 'Résultats';
require_once 'php/header.php';
?>

<div class="results-container">

  <h1 class="results-title">Résultats de la partie</h1>
  <p class="results-subtitle">
    Partie du <?= date('d/m/Y', strtotime($game['created_at'])) ?>
    — <?= GAME_TOURS ?> tours
  </p>

  <!-- Podium top 3 -->
  <?php if (count($results) >= 3): ?>
  <div class="podium">
    <!-- 2ème place -->
    <div class="podium__place podium__place--2">
      <div class="podium__pseudo">
        <?= htmlspecialchars($results[1]['pseudo']) ?>
      </div>
      <div class="podium__score"><?= $results[1]['score'] ?> pts</div>
      <div class="podium__block">2</div>
    </div>
    <!-- 1ère place -->
    <div class="podium__place podium__place--1">
      <div class="podium__crown">👑</div>
      <div class="podium__pseudo">
        <?= htmlspecialchars($results[0]['pseudo']) ?>
      </div>
      <div class="podium__score"><?= $results[0]['score'] ?> pts</div>
      <div class="podium__block">1</div>
    </div>
    <!-- 3ème place -->
    <div class="podium__place podium__place--3">
      <div class="podium__pseudo">
        <?= htmlspecialchars($results[2]['pseudo']) ?>
      </div>
      <div class="podium__score"><?= $results[2]['score'] ?> pts</div>
      <div class="podium__block">3</div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Meilleur contestataire -->
  <?php if ($best_contestant): ?>
  <div class="best-contestant">
    <span class="best-contestant__label">🏆 Meilleur contestataire</span>
    <span class="best-contestant__name">
      <?= htmlspecialchars($best_contestant) ?>
    </span>
  </div>
  <?php endif; ?>

  <!-- Classement complet -->
  <table class="results-table">
    <thead>
      <tr>
        <th>Rang</th>
        <th>Joueur</th>
        <th>Équipe</th>
        <th>Score</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($results as $i => $r): ?>
        <tr class="
          <?= $r['is_me']  ? 'results-table__row--me'  : '' ?>
          <?= $i === 0     ? 'results-table__row--gold'   : '' ?>
          <?= $i === 1     ? 'results-table__row--silver' : '' ?>
          <?= $i === 2     ? 'results-table__row--bronze' : '' ?>
        ">
          <td class="results-table__rank"><?= $i + 1 ?></td>
          <td class="results-table__pseudo">
            <?= htmlspecialchars($r['pseudo']) ?>
            <?= $r['is_me'] ? '<span class="badge-me">(toi)</span>' : '' ?>
          </td>
          <td class="results-table__team">
            <?= htmlspecialchars($r['team_name']) ?>
          </td>
          <td class="results-table__score"><?= $r['score'] ?> pts</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Actions -->
  <div class="results-actions">
    <a href="lobby.php"       class="btn btn-primary">Nouvelle partie</a>
    <a href="leaderboard.php" class="btn btn-ghost">Voir le classement</a>
  </div>

</div>

<?php require_once 'php/footer.php'; ?>