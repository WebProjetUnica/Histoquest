<?php
require_once 'php/config.php';
$page_title = 'Accueil';
require_once 'php/header.php';
?>

<section class="hero">
  <h1 class="hero__title">HistoQuest</h1>
  <p class="hero__subtitle">
    Le quiz historique multijoueur en équipe
  </p>
  <a href="lobby.php" class="btn btn-primary">Jouer maintenant</a>
</section>

<section class="leaderboard-preview">
  <h2>Classement général</h2>
  <p class="text-muted">Chargement en cours...</p>
  <!-- Ton binôme stylisera ce bloc, tu le rempliras en étape 2 -->
</section>

<?php require_once 'php/footer.php'; ?>