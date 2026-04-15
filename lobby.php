<?php
require_once 'php/config.php';
$page_title = 'Lobby';
require_once 'php/header.php';
?>

<div class="lobby-layout">

  <!-- Colonne gauche : créer une partie -->
  <section class="lobby-create">
    <h2>Créer une partie</h2>
    <form method="POST" action="lobby.php?action=create">
      <button type="submit" class="btn btn-primary btn-full">
        Nouvelle partie
      </button>
    </form>
  </section>

  <!-- Colonne droite : parties disponibles -->
  <section class="lobby-games">
    <h2>Parties en attente</h2>
    <div class="games-list" id="games-list">
      <!-- Rempli dynamiquement par AJAX en étape 3 -->
      <p class="text-muted">Aucune partie en attente.</p>
    </div>
  </section>

  <!-- Zone d'équipe — visible quand on a rejoint une partie -->
  <section class="lobby-team" id="lobby-team">
    <h2>Mon équipe</h2>

    <div class="team-members" id="team-members">
      <!-- Rempli par WebSocket en étape 4 -->
    </div>

    <!-- Invitation par pseudo -->
    <div class="invite-zone">
      <input type="text" id="invite-input"
             placeholder="Pseudo du joueur à inviter">
      <button class="btn btn-primary" id="invite-btn">
        Inviter
      </button>
    </div>

    <button class="btn btn-primary btn-full" id="start-btn" disabled>
      Démarrer la partie
    </button>
  </section>

</div>

<!-- Overlay invitation reçue — affiché par WebSocket -->
<div class="overlay" id="overlay-invitation">
  <div class="modal">
    <p class="modal__title">Invitation reçue</p>
    <p>
      <strong id="invite-from"></strong>
      t'invite à rejoindre
      <strong id="invite-team"></strong>
    </p>
    <div class="modal-actions">
      <button class="btn btn-primary" id="invite-accept">
        Accepter
      </button>
      <button class="btn btn-ghost" id="invite-refuse">
        Refuser
      </button>
    </div>
  </div>
</div>

<?php require_once 'php/footer.php'; ?>
