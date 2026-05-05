<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/config.php';

$users = read_json(USERS_FILE);
$players = array_filter($users, fn($u) => $u['parties'] > 0 || $u['score_total'] > 0);
usort($players, fn($a, $b) => $b['score_total'] <=> $a['score_total']);
$top20 = array_slice($players, 0, 20);

if (empty($top20)) {
    usort($users, fn($a, $b) => $b['score_total'] <=> $a['score_total']);
    $top20 = array_slice($users, 0, 20);
}

$my_rank = null;
if (isset($_SESSION['user_id'])) {
    foreach ($players as $i => $u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $my_rank = $i + 1;
            break;
        }
    }
}
$top3 = array_slice($top20, 0, 3);
$rest = array_slice($top20, 3);

$page_title = 'Classement général';
require_once 'php/header.php';
?>

<main class="page-shell leaderboard-pro">
  <aside class="lb-pro-side">
    <section class="pro-card lb-nav-card">
      <h3>Rank Categories</h3>
      <div class="lb-nav-item active">🌍 Global Ranking</div>
      <div class="lb-nav-item">👥 Friends & Rivals</div>
      <div class="lb-nav-item">🎯 Regional Masters</div>
      <div class="lb-nav-item">↗ Rising Stars</div>
    </section>

    <section class="pro-card pro-card-pad">
      <p class="stat-label">Ton rang</p>
      <p class="lb-my-standing__rank">#<?= $my_rank ?: '—' ?></p>
      <p class="text-muted"><?= $my_rank ? 'Continue à jouer pour monter.' : 'Joue une partie pour apparaître.' ?></p>
    </section>
  </aside>

  <section class="lb-pro-main">
    <div class="pro-header">
      <div>
        <h1 class="pro-title">Global Hall of Fame</h1>
        <p class="pro-sub">Les meilleurs explorateurs historiques, toutes parties confondues.</p>
      </div>
      <a href="lobby.php" class="btn btn-primary">Play</a>
    </div>

    <?php if ($my_rank && $my_rank > 20): ?>
      <div class="my-rank-banner">Ton rang : <strong>#<?= $my_rank ?></strong> sur <?= count($players) ?> joueurs</div>
    <?php endif; ?>

    <?php if (empty($top20)): ?>
      <div class="lobby-empty">Aucune partie jouée pour l'instant. Soyez les premiers.</div>
    <?php else: ?>
      <?php if (!empty($top3)): ?>
        <div class="lb-top3">
          <?php foreach ($top3 as $i => $u): ?>
            <?php $rank = $i + 1; $is_me = isset($_SESSION['user_id']) && $u['id'] === $_SESSION['user_id']; ?>
            <article class="pro-card lb-hero-card <?= $rank === 1 ? 'is-first' : '' ?>">
              <div class="lb-rank-bubble"><?= $rank ?></div>
              <div class="lb-avatar"><?= strtoupper(substr($u['pseudo'], 0, 2)) ?></div>
              <h3><?= htmlspecialchars($u['pseudo']) ?><?= $is_me ? ' <span class="badge-me">toi</span>' : '' ?></h3>
              <p><strong><?= $u['score_total'] ?></strong> points</p>
              <p class="text-muted"><?= $u['parties'] ?> parties · <?= $u['contests_won'] ?> contestations</p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <table class="leaderboard-table">
        <thead>
          <tr>
            <th>Rang</th>
            <th>Explorateur</th>
            <th>Total points</th>
            <th>Parties</th>
            <th>Contestations</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rest as $i => $u): ?>
            <?php
            $rank = $i + 4;
            $is_me = isset($_SESSION['user_id']) && $u['id'] === $_SESSION['user_id'];
            $rowClass = $is_me ? 'leaderboard-table__row--me' : '';
            ?>
            <tr class="<?= $rowClass ?>">
              <td class="leaderboard-table__rank">#<?= $rank ?></td>
              <td class="leaderboard-table__pseudo"><?= htmlspecialchars($u['pseudo']) ?><?= $is_me ? '<span class="badge-me">toi</span>' : '' ?></td>
              <td class="leaderboard-table__score"><?= $u['score_total'] ?> pts</td>
              <td class="leaderboard-table__parties"><?= $u['parties'] ?></td>
              <td class="leaderboard-table__contests"><?= $u['contests_won'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <div class="leaderboard-actions">
      <a href="lobby.php" class="btn btn-primary">Jouer maintenant</a>
    </div>
  </section>
</main>

<?php require_once 'php/footer.php'; ?>
