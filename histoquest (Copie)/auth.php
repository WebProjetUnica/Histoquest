<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

require_once 'php/config.php';
require_once 'php/auth_functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: lobby.php');
    exit;
}

$error_login    = '';
$error_register = '';
$action = $_GET['action'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo   = trim($_POST['pseudo']   ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($action === 'register') {
        $result = register_user($pseudo, $password);
        if ($result['ok']) {
            login_user($pseudo, $password);
            header('Location: lobby.php');
            exit;
        }
        $error_register = $result['error'];
    }

    if ($action === 'login') {
        $user = login_user($pseudo, $password);
        if ($user) {
            header('Location: lobby.php');
            exit;
        }
        $error_login = 'Pseudo ou mot de passe incorrect.';
    }
}

$page_title = 'Connexion';
require_once 'php/header.php';
?>

<main class="page-shell auth-pro">
  <section class="auth-visual">
    <span class="hq-pill">🛡️ Accès joueur sécurisé</span>
    <h1 class="hq-title" style="font-size:clamp(2.1rem,4.8vw,4.2rem)">Rejoins la quête.<span class="accent">Mène ton équipe.</span></h1>
    <p class="hq-subtitle">Connexion simple, compte persistant et progression enregistrée dans le classement général.</p>

    <div class="auth-feature"><span class="stat-icon">✓</span><div><b>Compte validé</b><p>Pseudo unique et mot de passe protégé côté serveur.</p></div></div>
    <div class="auth-feature"><span class="stat-icon">⚔</span><div><b>Lobby immédiat</b><p>Une fois connecté, tu peux créer une partie ou rejoindre une équipe.</p></div></div>
  </section>

  <section class="auth-container">
    <div class="auth-tabs">
      <a href="auth.php?action=login" class="auth-tab <?= $action === 'login' ? 'auth-tab--active' : '' ?>">Connexion</a>
      <a href="auth.php?action=register" class="auth-tab <?= $action === 'register' ? 'auth-tab--active' : '' ?>">Inscription</a>
    </div>

    <?php if ($action === 'login'): ?>
      <form class="auth-form" method="POST" action="auth.php?action=login">
        <h2 class="auth-form__title">Player Login</h2>
        <p class="text-muted">Retourne à ta campagne et continue la bataille.</p>

        <?php if ($error_login): ?><div class="form-error"><?= htmlspecialchars($error_login) ?></div><?php endif; ?>

        <div class="form-group">
          <label for="login-pseudo">Pseudo</label>
          <input type="text" id="login-pseudo" name="pseudo" required minlength="3" placeholder="Ton pseudo" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="login-password">Mot de passe</label>
          <input type="password" id="login-password" name="password" required minlength="8" placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Accéder au lobby</button>
        <p class="auth-switch">Pas encore de compte ? <a href="auth.php?action=register">S'inscrire</a></p>
      </form>
    <?php else: ?>
      <form class="auth-form" method="POST" action="auth.php?action=register">
        <h2 class="auth-form__title">Create Account</h2>
        <p class="text-muted">Crée ton profil et commence ton parcours à travers le temps.</p>

        <?php if ($error_register): ?><div class="form-error"><?= htmlspecialchars($error_register) ?></div><?php endif; ?>

        <div class="form-group">
          <label for="register-pseudo">Pseudo</label>
          <input type="text" id="register-pseudo" name="pseudo" required minlength="3" placeholder="Historian_99" value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="register-password">Mot de passe</label>
          <input type="password" id="register-password" name="password" required minlength="8" placeholder="8 caractères minimum">
        </div>
        <button type="submit" class="btn btn-primary btn-full">Start Your Quest →</button>
        <p class="auth-switch">Déjà un compte ? <a href="auth.php?action=login">Se connecter</a></p>
      </form>
    <?php endif; ?>
  </section>
</main>

<?php require_once 'php/footer.php'; ?>
