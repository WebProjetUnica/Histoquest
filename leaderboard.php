<?php
// leaderboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/config.php';

$users = read_json(USERS_FILE);

// Filtrer les joueurs qui ont joué au moins une partie
$players = array_filter($users, fn($u) => $u['parties'] > 0 || $u['score_total'] > 0);

// Trier par score total décroissant
usort($players, fn($a, $b) => $b['score_total'] <=> $a['score_total']);

// Limiter aux 20 premiers
$top20 = array_slice($players, 0, 20);

// Si aucun joueur n'a encore joué, afficher quand même tous les inscrits
if (empty($top20)) {
    usort($users, fn($a, $b) => $b['score_total'] <=> $a['score_total']);
    $top20 = array_slice($users, 0, 20);
}

// Trouver le rang du joueur connecté
$my_rank = null;
if (isset($_SESSION['user_id'])) {
    foreach ($players as $i => $u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $my_rank = $i + 1;
            break;
        }
    }
}

$page_title = 'Classement général';
require_once 'php/header.php';
?>

<div class="leaderboard-container">

  <h1 class="leaderboard-title">Classement général</h1>
  <p class="leaderboard-subtitle">
    Les meilleurs joueurs toutes parties confondues
  </p>

  <!-- Ton rang si connecté -->
  <?php if ($my_rank && $my_rank > 20): ?>
  <div class="my-rank-banner">
    Ton rang : <strong>#<?= $my_rank ?></strong>
    sur <?= count($players) ?> joueurs
  </div>
  <?php endif; ?>

  <?php if (empty($top20)): ?>
    <p class="text-muted">
      Aucune partie jouée pour l'instant. Soyez les premiers !
    </p>
  <?php else: ?>

  <table class="leaderboard-table">
    <thead>
      <tr>
        <th>Rang</th>
        <th>Joueur</th>
        <th>Score total</th>
        <th>Parties jouées</th>
        <th>Contestations réussies</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($top20 as $i => $u): ?>
        <?php
        $rank    = $i + 1;
        $is_me   = isset($_SESSION['user_id']) && $u['id'] === $_SESSION['user_id'];
        $rowClass = '';
        if ($rank === 1) $rowClass = 'leaderboard-table__row--gold';
        if ($rank === 2) $rowClass = 'leaderboard-table__row--silver';
        if ($rank === 3) $rowClass = 'leaderboard-table__row--bronze';
        if ($is_me)      $rowClass .= ' leaderboard-table__row--me';
        ?>
        <tr class="<?= $rowClass ?>">
          <td class="leaderboard-table__rank">
            <?php if ($rank === 1): ?>🥇
            <?php elseif ($rank === 2): ?>🥈
            <?php elseif ($rank === 3): ?>🥉
            <?php else: ?>#<?= $rank ?>
            <?php endif; ?>
          </td>
          <td class="leaderboard-table__pseudo">
            <?= htmlspecialchars($u['pseudo']) ?>
            <?= $is_me ? '<span class="badge-me">(toi)</span>' : '' ?>
          </td>
          <td class="leaderboard-table__score">
            <?= $u['score_total'] ?> pts
          </td>
          <td class="leaderboard-table__parties">
            <?= $u['parties'] ?>
          </td>
          <td class="leaderboard-table__contests">
            <?= $u['contests_won'] ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php endif; ?>

  <div class="leaderboard-actions">
    <a href="lobby.php" class="btn btn-primary">Jouer maintenant</a>
  </div>

</div>

<?php require_once 'php/footer.php'; ?>