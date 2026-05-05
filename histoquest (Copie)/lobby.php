<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once 'php/config.php';
require_once 'php/game_functions.php';
require_once 'php/ws_functions.php';

$user_id = $_SESSION['user_id'];
$pseudo  = $_SESSION['pseudo'];

// Si la session contient une ancienne partie inexistante ou terminée,
// on la nettoie pour permettre de créer/rejoindre une nouvelle partie.
if (isset($_SESSION['game_id'])) {
    $session_game = get_game($_SESSION['game_id']);
    if (!$session_game || (($session_game['status'] ?? 'waiting') !== 'waiting')) {
        unset($_SESSION['game_id'], $_SESSION['team_id']);
    }
}

// Si le joueur n'a pas de partie en session, on vérifie s'il est déjà
// dans une partie en attente. Ne touche pas aux parties terminées/en jeu.
if (!isset($_SESSION['game_id'])) {
    foreach (get_waiting_games() as $game) {
        $team_id = get_player_team($game['id'], $user_id);
        if ($team_id) {
            $_SESSION['game_id'] = $game['id'];
            $_SESSION['team_id'] = $team_id;
            break;
        }
    }
}

// ── Traitement POST natif PHP : pas d'AJAX ici pour ne pas casser le backend ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $game = create_game($user_id, $pseudo);
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

    if ($action === 'leave') {
        unset($_SESSION['game_id'], $_SESSION['team_id']);
        header('Location: lobby.php');
        exit;
    }

    if ($action === 'start') {
        $game_id = $_SESSION['game_id'] ?? '';
        if ($game_id && can_start_game($game_id)) {
            $result = start_game($game_id);
            if ($result['ok']) {
                broadcast_game_started($game_id);
                header('Location: game.php');
                exit;
            }
        }
        header('Location: lobby.php');
        exit;
    }
}

$waiting_games   = get_waiting_games();
$current_game_id = $_SESSION['game_id'] ?? null;
$current_team_id = $_SESSION['team_id'] ?? null;
$current_game    = $current_game_id ? get_game($current_game_id) : null;
$current_team    = $current_game ? ($current_game['teams'][$current_team_id] ?? null) : null;

$total_players = 0;
foreach ($waiting_games as $g) {
    foreach (($g['teams'] ?? []) as $t) {
        $total_players += count($t['members'] ?? []);
    }
}

$page_title = 'Lobby';
require_once 'php/header.php';
?>

<main class="page-shell lobby-pro lobby-safe">
  <section class="lobby-main">
    <div class="pro-header">
      <div>
        <h1 class="pro-title">Game Lobby</h1>
        <p class="pro-sub">Crée une salle, invite ton équipe, puis lance la partie.</p>
      </div>
      <a href="leaderboard.php" class="btn btn-ghost">🏆 Classement</a>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><span class="stat-icon">👥</span><div><p class="stat-label">Joueurs lobby</p><p class="stat-value"><?= max(1, $total_players) ?></p></div></div>
      <div class="stat-card"><span class="stat-icon">⚡</span><div><p class="stat-label">Parties ouvertes</p><p class="stat-value"><?= count($waiting_games) ?></p></div></div>
      <div class="stat-card"><span class="stat-icon">🎯</span><div><p class="stat-label">Format</p><p class="stat-value">Équipe</p></div></div>
      <div class="stat-card"><span class="stat-icon">⏱</span><div><p class="stat-label">Tours</p><p class="stat-value"><?= GAME_TOURS ?></p></div></div>
    </div>

    <?php if ($current_game): ?>
      <section class="pro-card pro-card-pad current-room-card">
        <div class="current-room-head">
          <div>
            <p class="stat-label">Salle active</p>
            <h2>Partie #<?= htmlspecialchars(substr($current_game['id'], -6)) ?></h2>
          </div>
          <span class="hq-pill">En attente</span>
        </div>
        <p class="text-muted">Tu es dans <?= htmlspecialchars($current_team['name'] ?? 'une équipe') ?>. Lance la partie quand les conditions sont remplies.</p>
      </section>
    <?php endif; ?>

    <section>
      <div class="pro-header compact-header">
        <div><h2 class="results-section-title">Public Matchmaking</h2><p class="text-muted">Parties disponibles actuellement.</p></div>
      </div>

      <div class="match-list">
        <?php if (empty($waiting_games)): ?>
          <div class="lobby-empty">Aucune partie en attente. Crée la première salle.</div>
        <?php else: ?>
          <?php foreach ($waiting_games as $game): ?>
            <article class="game-card">
              <div class="game-card__header">
                <span class="game-card__id">Partie #<?= htmlspecialchars(substr($game['id'], -6)) ?></span>
                <span class="game-card__date"><?= htmlspecialchars($game['created_at'] ?? '') ?></span>
              </div>

              <?php foreach ($game['teams'] as $team_id => $team): ?>
                <?php
                  $members    = $team['members'] ?? [];
                  $is_full    = count($members) >= GAME_TEAM_SIZE;
                  $already_in = $current_game_id === $game['id'];
                  $is_my_team = ($current_game_id === $game['id'] && $current_team_id === $team_id);
                ?>
                <div class="game-card__team">
                  <div class="team-row-main">
                    <span class="team-name"><?= htmlspecialchars($team['name']) ?></span>
                    <span class="team-count"><?= count($members) ?>/<?= GAME_TEAM_SIZE ?></span>
                  </div>

                  <ul class="team-members-list">
                    <?php foreach ($members as $member): ?>
                      <li><?= htmlspecialchars($member['pseudo']) ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?><li class="empty-slot">Place libre</li><?php endif; ?>
                  </ul>

                  <?php if (!$is_full && !$already_in && !$is_my_team): ?>
                    <form method="POST" action="lobby.php" class="team-action-form">
                      <input type="hidden" name="action" value="join">
                      <input type="hidden" name="game_id" value="<?= htmlspecialchars($game['id']) ?>">
                      <input type="hidden" name="team_id" value="<?= htmlspecialchars($team_id) ?>">
                      <button type="submit" class="btn btn-primary">Rejoindre</button>
                    </form>
                  <?php elseif ($is_my_team): ?>
                    <span class="badge badge--mine">Ton équipe</span>
                  <?php elseif ($is_full): ?>
                    <span class="badge badge--full">Complet</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </section>

  <aside class="lobby-side">
    <section class="pro-card pro-card-pad lobby-create">
      <h2>＋ Créer une partie</h2>
      <p class="text-muted">Crée une salle d'attente et invite tes coéquipiers.</p>
      <?php if (!$current_game): ?>
        <form method="POST" action="lobby.php" id="create-game-form">
          <input type="hidden" name="action" value="create">
          <button type="submit" class="btn btn-primary btn-full">Créer la partie</button>
        </form>
      <?php else: ?>
        <p class="text-muted">Tu es déjà dans une partie.</p>
        <form method="POST" action="lobby.php">
          <input type="hidden" name="action" value="leave">
          <button type="submit" class="btn btn-ghost btn-full">Quitter cette salle</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="pro-card pro-card-pad lobby-team">
      <h2>Mon équipe</h2>
      <?php if ($current_team): ?>
        <p class="team-name-display"><?= htmlspecialchars($current_team['name']) ?></p>
        <ul class="team-members" id="team-members">
          <?php foreach ($current_team['members'] as $member): ?>
            <li class="team-member <?= $member['id'] === $user_id ? 'team-member--me' : '' ?>">
              <?= htmlspecialchars($member['pseudo']) ?><?= $member['id'] === $user_id ? ' (toi)' : '' ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="invite-zone">
          <input type="text" id="invite-input" placeholder="Pseudo à inviter">
          <button type="button" class="btn btn-primary" id="invite-btn">Inviter</button>
          <p class="invite-feedback" id="invite-feedback"></p>
        </div>

        <?php $can_start = can_start_game($current_game_id); ?>
        <form method="POST" action="lobby.php" id="start-game-form">
          <input type="hidden" name="action" value="start">
          <button type="submit" class="btn btn-primary btn-full" <?= !$can_start ? 'disabled' : '' ?>>
            <?= $can_start ? 'Démarrer la partie' : 'En attente de joueurs...' ?>
          </button>
        </form>
      <?php else: ?>
        <p class="text-muted">Crée ou rejoins une partie pour afficher ton équipe.</p>
      <?php endif; ?>
    </section>
  </aside>
</main>

<div class="overlay" id="overlay-invitation">
  <div class="modal">
    <p class="modal__title">Invitation reçue</p>
    <p><strong id="invite-from"></strong> t'invite à rejoindre <strong id="invite-team"></strong></p>
    <div class="modal-actions">
      <button type="button" class="btn btn-primary" id="invite-accept">Accepter</button>
      <button type="button" class="btn btn-ghost" id="invite-refuse">Refuser</button>
    </div>
  </div>
</div>

<script>
  const WS_URL  = 'ws://localhost:8080';
  const USER_ID = '<?= htmlspecialchars($user_id) ?>';
  const PSEUDO  = '<?= htmlspecialchars($pseudo) ?>';
  const GAME_ID = '<?= htmlspecialchars($_SESSION['game_id'] ?? '') ?>';
  const TEAM_ID = '<?= htmlspecialchars($_SESSION['team_id'] ?? '') ?>';
</script>
<script src="js/lobby.js"></script>
<?php require_once 'php/footer.php'; ?>
