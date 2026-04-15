<?php
require_once 'php/config.php';
$page_title = 'Résultats';
require_once 'php/header.php';
?>

<div class="results-container">

  <h1 class="results-title">Résultats de la partie</h1>

  <!-- Podium top 3 -->
  <div class="podium" id="podium">
    <!-- Rempli par PHP en étape 5 -->
  </div>

  <!-- Classement complet -->
  <table class="results-table" id="results-table">
    <thead>
      <tr>
        <th>Rang</th>
        <th>Joueur</th>
        <th>Équipe</th>
        <th>Score</th>
        <th>Contestations réussies</th>
      </tr>
    </thead>
    <tbody id="results-body">
      <!-- Rempli par PHP en étape 5 -->
    </tbody>
  </table>

  <a href="lobby.php" class="btn btn-primary">
    Retour au lobby
  </a>

</div>

<?php require_once 'php/footer.php'; ?>