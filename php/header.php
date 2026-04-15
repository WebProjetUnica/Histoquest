<?php
// Ce fichier est inclus en haut de chaque page
// Il suppose que config.php a déjà été appelé avant lui
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HistoQuest — <?= htmlspecialchars($page_title ?? 'Accueil') ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/components.css">
</head>
<body>

<nav class="main-nav">
  <a href="index.php" class="nav-logo">HistoQuest</a>

  <ul class="nav-links">
    <li><a href="lobby.php">Jouer</a></li>
    <li><a href="leaderboard.php">Classement</a></li>
  </ul>

  <div class="nav-user">
    <?php if (isset($_SESSION['user_id'])): ?>
      <span class="nav-pseudo">
        <?= htmlspecialchars($_SESSION['pseudo']) ?>
      </span>
      <a href="auth.php?action=logout" class="btn btn-ghost">
        Déconnexion
      </a>
    <?php else: ?>
      <a href="auth.php" class="btn btn-primary">Connexion</a>
    <?php endif; ?>
  </div>
</nav>

<main class="main-content">