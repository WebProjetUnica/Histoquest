<?php
// Démarrer la session en tout premier
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

$user_id = $_SESSION['user_id'];
$pseudo  = $_SESSION['pseudo'];

// ── Traitement POST — créer une partie ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $game = create_game($user_id, $pseudo);
        // Enregistrer la partie et l'équipe en session
        $_SESSION['game_id'] = $game['id'];
        $_SESSION['team_id'] = 'equipe_1';
        header('Location: lobby.php');
        exit;
    }

    if ($action === 'join') {
        $game_id = $_POST['game_id'] ?? '';
        $team_id = $_POST['team_id'] ?? '';
        $result  = join_team($game_id, $team_id, $user_id, $pseudo);
        if ($result['ok']) {
            $_SESSION['game_id'] = $game_id;
            $_SESSION['team_id'] = $team_id;
        }
        header('Location: lobby.php');
        exit;
    }
}

// ── Données pour l'affichage ───────────────────────────────────────────────
$waiting_games   = get_waiting_games();
$current_game_id = $_SESSION['game_id'] ?? null;
$current_team_id = $_SESSION['team_id'] ?? null;
$current_game    = $current_game_id ? get_game($current_game_id) : null;
$current_team    = $current_game ? $current_game['teams'][$current_team_id] ?? null : null;

$page_title = 'Lobby';
require_once 'php/header.php';
?>

<div class="lobby-layout">

  <!-- COLONNE GAUCHE : créer une partie -->
  <section class="lobby-create">
    <h2>Créer une partie</h2>

    <?php if (!$current_game): ?>
      <form method="POST" action="lobby.php">
        <input type="hidden" name="action" value="create">
        <button type="submit" class="btn btn-primary btn-full">
          Nouvelle partie
        </button>
      </form>
    <?php else: ?>
      <p class="text-muted">
        Tu es déjà dans une partie en attente.
      </p>
    <?php endif; ?>
  </section>

  <!-- COLONNE CENTRALE : parties en attente -->
  <section class="lobby-games">
    <h2>Parties en attente</h2>

    <?php if (empty($waiting_games)): ?>
      <p class="text-muted">Aucune partie en attente.</p>
    <?php else: ?>
      <?php foreach ($waiting_games as $game): ?>
        <div class="game-card">
          <div class="game-card__header">
            <span class="game-card__id">
              Partie #<?= substr($game['id'], -6) ?>
            </span>
            <span class="game-card__date">
              <?= $game['created_at'] ?>
            </span>
          </div>

          <?php foreach ($game['teams'] as $team_id => $team): ?>
            <div class="game-card__team">
              <span class="team-name"><?= $team['name'] ?></span>
              <span class="team-count">
                <?= count($team['members']) ?>/<?= GAME_TEAM_SIZE ?>
              </span>

              <!-- Liste des membres -->
              <ul class="team-members-list">
                <?php foreach ($team['members'] as $member): ?>
                  <li><?= htmlspecialchars($member['pseudo']) ?></li>
                <?php endforeach; ?>
              </ul>

              <!-- Bouton rejoindre si place disponible -->
              <?php
              $is_full    = count($team['members']) >= GAME_TEAM_SIZE;
              $already_in = $current_game_id === $game['id'];
              ?>
              <?php if (!$is_full && !$already_in): ?>
                <form method="POST" action="lobby.php">
                  <input type="hidden" name="action"  value="join">
                  <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                  <input type="hidden" name="team_id" value="<?= $team_id ?>">
                  <button type="submit" class="btn btn-primary">
                    Rejoindre
                  </button>
                </form>
              <?php elseif ($is_full): ?>
                <span class="badge badge--full">Complet</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- COLONNE DROITE : mon équipe -->
  <section class="lobby-team">
    <h2>Mon équipe</h2>

    <?php if ($current_team): ?>
      <p class="team-name-display">
        <?= htmlspecialchars($current_team['name']) ?>
      </p>

      <ul class="team-members" id="team-members">
        <?php foreach ($current_team['members'] as $member): ?>
          <li class="team-member <?= $member['id'] === $user_id ? 'team-member--me' : '' ?>">
            <?= htmlspecialchars($member['pseudo']) ?>
            <?= $member['id'] === $user_id ? '(toi)' : '' ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Invitation par pseudo -->
      <div class="invite-zone">
        <input type="text" id="invite-input"
               placeholder="Pseudo du joueur à inviter">
        <button class="btn btn-primary" id="invite-btn">
          Inviter
        </button>
        <p class="invite-feedback" id="invite-feedback"></p>
      </div>

      <!-- Bouton démarrer -->
      <?php $can_start = can_start_game($current_game_id); ?>
      <form method="POST" action="lobby.php">
        <input type="hidden" name="action"  value="start">
        <button type="submit"
                class="btn btn-primary btn-full"
                <?= !$can_start ? 'disabled' : '' ?>>
          <?= $can_start ? 'Démarrer la partie' : 'En attente de joueurs...' ?>
        </button>
      </form>

    <?php else: ?>
      <p class="text-muted">
        Crée ou rejoins une partie pour voir ton équipe ici.
      </p>
    <?php endif; ?>
  </section>

</div>

<!-- Overlay invitation reçue — activé par WebSocket en étape 4 -->
<div class="overlay" id="overlay-invitation">
  <div class="modal">
    <p class="modal__title">Invitation reçue</p>
    <p>
      <strong id="invite-from"></strong>
      t'invite à rejoindre
      <strong id="invite-team"></strong>
    </p>
    <div class="modal-actions">
      <button class="btn btn-primary" id="invite-accept">Accepter</button>
      <button class="btn btn-ghost"   id="invite-refuse">Refuser</button>
    </div>
  </div>
</div>

<script src="js/lobby.js"></script>
<?php require_once 'php/footer.php'; ?>