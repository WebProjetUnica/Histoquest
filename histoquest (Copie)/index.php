<?php
require_once 'php/config.php';
$page_title = 'Accueil';
require_once 'php/header.php';
?>

<main class="page-shell hq-split">
  <section class="hero-copy">
    <span class="hq-pill">✦ Nouvelle expédition historique</span>
    <h1 class="hq-title">Master History.<span class="accent">Conquer the Map.</span></h1>
    <p class="hq-subtitle">
      HistoQuest est un quiz historique multijoueur en équipe. Identifie les lieux,
      débat avec ton équipe et grimpe au classement.
    </p>
    <div class="hq-actions">
      <a href="lobby.php" class="btn btn-primary">▶ Jouer gratuitement</a>
      <a href="leaderboard.php" class="btn btn-ghost">Voir le classement</a>
    </div>
    <div class="hq-live" aria-label="Joueurs actifs">
      <div class="hq-live-avatars" aria-hidden="true"><span>🏛️</span><span>🧭</span><span>⚔️</span><span>🗺️</span></div>
      <span><strong>1 240+</strong> historiens jouent actuellement</span>
    </div>
  </section>

  <section class="hero-map-card" aria-label="Carte historique HistoQuest">
    <div class="floating-card floating-card--top">
      <span class="stat-icon">⏱</span>
      <span><small>Round actuel</small>Âge des découvertes</span>
    </div>
    <div class="floating-card floating-card--bottom">
      <span class="stat-icon">🏆</span>
      <span><small>Top contester</small>Explorer_99</span>
    </div>
  </section>
</main>

<?php require_once 'php/footer.php'; ?>
