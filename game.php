<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

// Rediriger si pas dans une partie
if (!isset($_SESSION['game_id'])) {
    header('Location: lobby.php');
    exit;
}

require_once 'php/config.php';
require_once 'php/game_functions.php';

// Vérifier que la partie existe encore
$game = get_game($_SESSION['game_id']);
if (!$game) {
    unset($_SESSION['game_id']);
    unset($_SESSION['team_id']);
    header('Location: lobby.php');
    exit;
}

$game_id  = $_SESSION['game_id'];
$team_id  = $_SESSION['team_id'];
$user_id  = $_SESSION['user_id'];
$pseudo   = $_SESSION['pseudo'];

// Charger l'événement du tour en cours
$tour       = $game['tour'] ?? 1;
$votes_file = GAMES_DIR . $game_id . '/tour_' . $tour . '_votes.json';
$votes_data = read_json($votes_file);

// Si pas encore d'événement pour ce tour — en générer un
if (empty($votes_data['event'])) {
    $event = get_random_event();
    $votes_data = [
        'tour'           => $tour,
        'game_id'        => $game_id,
        'team_id'        => $team_id,
        'votes'          => [],
        'hint_used'      => false,
        'hint_vote_open' => false,
        'hint_votes'     => ['oui' => 0, 'non' => 0],
        'hint_voters'    => [],
        'contest'        => null,
        'majority'       => null,
        'correct'        => $event['pays_correct'],
        'indice'         => $event['indice'],
        'points'         => [],
        'event'          => $event,
    ];
    write_json($votes_file, $votes_data);
} else {
    $event = $votes_data['event'];
}

$pays_candidats = $event['pays_candidats'];
shuffle($pays_candidats);

$page_title = 'Partie en cours';
require_once 'php/header.php';
?>

<!-- Variables PHP accessibles en JavaScript -->
<script>
  const GAME_ID   = '<?= htmlspecialchars($game_id) ?>';
  const TEAM_ID   = '<?= htmlspecialchars($team_id) ?>';
  const USER_ID   = '<?= htmlspecialchars($user_id) ?>';
  const PSEUDO    = '<?= htmlspecialchars($pseudo)  ?>';
  const TOUR      = <?= (int)$tour ?>;
  const WS_URL    = 'ws://localhost:8080';
  const PAYS_LIST = <?= json_encode($pays_candidats) ?>;
</script>

<div class="game-layout">

  <!-- PANNEAU GAUCHE -->
  <aside class="game-panel-left">

    <!-- Carte événement -->
    <div class="event-card" id="event-card">
      <div class="event-card__year" id="event-year">
        <?= htmlspecialchars($event['annee']) ?>
      </div>
      <h2 class="event-card__title" id="event-title">
        <?= htmlspecialchars($event['titre']) ?>
      </h2>
      <p class="event-card__desc" id="event-desc">
        <?= htmlspecialchars($event['description']) ?>
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
      <?php foreach ($pays_candidats as $pays): ?>
        <button class="btn vote-btn"
                data-pays="<?= htmlspecialchars($pays) ?>">
          <?= htmlspecialchars($pays) ?>
        </button>
      <?php endforeach; ?>
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
    <p style="padding:2rem;color:#888">Carte du monde ici</p>
  </div>

  <!-- PANNEAU DROIT -->
  <aside class="game-panel-right">
    <h3 class="panel-title">Scores</h3>
    <div class="scores-list" id="scores-list">
    </div>

    <h3 class="panel-title">Mon équipe</h3>
    <ul class="team-list" id="team-list">
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
    <textarea class="contest-input hidden" id="contest-input"
              maxlength="150"
              placeholder="Explique pourquoi tu contestes...">
    </textarea>
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