<?php
require_once 'php/config.php';
$page_title = 'Partie en cours';
require_once 'php/header.php';

// Variables PHP injectées dans le HTML pour JavaScript
// Remplies en étape 2 avec les vraies données de session
$game_id  = $_SESSION['game_id']  ?? 'test';
$team_id  = $_SESSION['team_id']  ?? 'equipe_1';
$user_id  = $_SESSION['user_id']  ?? '';
$pseudo   = $_SESSION['pseudo']   ?? '';
?>

<!-- Variables PHP accessibles en JavaScript -->
<script>
  const GAME_ID  = '<?= htmlspecialchars($game_id) ?>';
  const TEAM_ID  = '<?= htmlspecialchars($team_id) ?>';
  const USER_ID  = '<?= htmlspecialchars($user_id) ?>';
  const PSEUDO   = '<?= htmlspecialchars($pseudo)  ?>';
  const WS_URL   = 'ws://localhost:8080';
</script>

<div class="game-layout">

  <!-- PANNEAU GAUCHE -->
  <aside class="game-panel-left">

    <!-- Carte événement -->
    <div class="event-card" id="event-card">
      <div class="event-card__year" id="event-year">Année</div>
      <h2 class="event-card__title" id="event-title">
        Titre de l'événement
      </h2>
      <p class="event-card__desc" id="event-desc">
        Description de l'événement historique...
      </p>
    </div>

    <!-- Indice (masqué par défaut) -->
    <div class="hint-zone hidden" id="hint-zone">
      <p class="hint-label">Indice</p>
      <p class="hint-text" id="hint-text"></p>
    </div>

    <!-- Timer -->
    <div class="timer-zone">
      <div class="timer-text" id="timer-text">30</div>
      <div class="timer-bar">
        <div class="timer-bar__fill" id="timer-fill"
             style="width: 100%"></div>
      </div>
    </div>

    <!-- Boutons de vote -->
    <div class="vote-buttons" id="vote-buttons">
      <!-- Générés dynamiquement par JS à chaque tour -->
      <button class="btn vote-btn" data-pays="Pays 1">Pays 1</button>
      <button class="btn vote-btn" data-pays="Pays 2">Pays 2</button>
      <button class="btn vote-btn" data-pays="Pays 3">Pays 3</button>
      <button class="btn vote-btn" data-pays="Pays 4">Pays 4</button>
    </div>

    <!-- Compteur de votes -->
    <p class="vote-count" id="vote-count">0/5 joueurs ont voté</p>

    <!-- Boutons d'action -->
    <button class="btn btn-amber btn-full" id="hint-btn">
      Demander un indice
    </button>
    <button class="btn btn-danger btn-full hidden" id="contest-btn">
      Contester la réponse
    </button>

  </aside>

  <!-- CARTE DU MONDE -->
  <div class="game-map" id="game-map">
    <!-- Carte SVG ou Leaflet — intégrée par ton binôme -->
    <p style="padding:2rem;color:#888">Carte du monde ici</p>
  </div>

  <!-- PANNEAU DROIT -->
  <aside class="game-panel-right">
    <h3 class="panel-title">Scores</h3>
    <div class="scores-list" id="scores-list">
      <!-- Mis à jour par AJAX -->
    </div>

    <h3 class="panel-title">Mon équipe</h3>
    <ul class="team-list" id="team-list">
      <!-- Mis à jour par WebSocket -->
    </ul>
  </aside>

</div>

<!-- OVERLAY VOTE D'INDICE -->
<div class="overlay" id="overlay-hint">
  <div class="modal">
    <p class="modal__title">Vote pour l'indice</p>
    <p id="hint-requester"></p>
    <div class="hint-vote-count" id="hint-vote-count">
      OUI : 0 — NON : 0
    </div>
    <div class="modal-actions">
      <button class="btn btn-primary" id="hint-yes">OUI</button>
      <button class="btn btn-ghost"   id="hint-no">NON</button>
    </div>
    <div class="timer-bar">
      <div class="timer-bar__fill" id="hint-timer-fill"
           style="width:100%"></div>
    </div>
  </div>
</div>

<!-- OVERLAY CONTESTATION -->
<div class="overlay" id="overlay-contest">
  <div class="modal">
    <p class="modal__title" id="contest-title">
      Argument de contestation
    </p>
    <!-- Zone de saisie — visible uniquement pour le contestataire -->
    <textarea class="contest-input hidden" id="contest-input"
              maxlength="150"
              placeholder="Explique pourquoi tu contestes...">
    </textarea>
    <!-- Message — visible pour les autres -->
    <p class="contest-text hidden" id="contest-text"></p>
    <div class="contest-votes" id="contest-divergent"></div>
    <button class="btn btn-primary hidden" id="contest-submit">
      Envoyer mon argument
    </button>
  </div>
</div>

<!-- OVERLAY SECOND VOTE -->
<div class="overlay" id="overlay-revote">
  <div class="modal">
    <p class="modal__title">Second vote — 20 secondes</p>
    <p id="contest-argument"></p>
    <div class="vote-buttons" id="revote-buttons">
      <!-- Mêmes boutons que le vote initial, générés par JS -->
    </div>
    <div class="timer-bar">
      <div class="timer-bar__fill" id="revote-timer-fill"
           style="width:100%"></div>
    </div>
  </div>
</div>

<script src="js/timer.js"></script>
<script src="js/websocket.js"></script>
<script src="js/game.js"></script>

<?php require_once 'php/footer.php'; ?>
