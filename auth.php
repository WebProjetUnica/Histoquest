<?php
// Démarrer la session en tout premier — avant tout require_once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déconnexion — traité EN PREMIER avant tout le reste
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Maintenant on charge le reste
require_once 'php/config.php';
require_once 'php/auth_functions.php';

// Rediriger vers le lobby si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: lobby.php');
    exit;
}

$error_login    = '';
$error_register = '';
$action = $_GET['action'] ?? 'login';

// ── Traitement POST ────────────────────────────────────────────────────────
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

// ── Affichage HTML ─────────────────────────────────────────────────────────
$page_title = 'Connexion';
require_once 'php/header.php';
?>

<div class="auth-container">

  <div class="auth-tabs">
    <a href="auth.php?action=login"
       class="auth-tab <?= $action === 'login' ? 'auth-tab--active' : '' ?>">
      Connexion
    </a>
    <a href="auth.php?action=register"
       class="auth-tab <?= $action === 'register' ? 'auth-tab--active' : '' ?>">
      Inscription
    </a>
  </div>

  <?php if ($action === 'login'): ?>

    <form class="auth-form" method="POST" action="auth.php?action=login">

      <h2 class="auth-form__title">Se connecter</h2>

      <?php if ($error_login): ?>
        <div class="form-error"><?= htmlspecialchars($error_login) ?></div>
      <?php endif; ?>

      <div class="form-group">
        <label for="login-pseudo">Pseudo</label>
        <input type="text" id="login-pseudo" name="pseudo"
               required minlength="3"
               placeholder="Ton pseudo"
               value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="login-password">Mot de passe</label>
        <input type="password" id="login-password" name="password"
               required minlength="8"
               placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        Se connecter
      </button>

      <p class="auth-switch">
        Pas encore de compte ?
        <a href="auth.php?action=register">S'inscrire</a>
      </p>

    </form>

  <?php else: ?>

    <form class="auth-form" method="POST" action="auth.php?action=register">

      <h2 class="auth-form__title">Créer un compte</h2>

      <?php if ($error_register): ?>
        <div class="form-error"><?= htmlspecialchars($error_register) ?></div>
      <?php endif; ?>

      <div class="form-group">
        <label for="register-pseudo">Pseudo</label>
        <input type="text" id="register-pseudo" name="pseudo"
               required minlength="3"
               placeholder="Choisis un pseudo"
               value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="register-password">Mot de passe</label>
        <input type="password" id="register-password" name="password"
               required minlength="8"
               placeholder="8 caractères minimum">
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        Créer mon compte
      </button>

      <p class="auth-switch">
        Déjà un compte ?
        <a href="auth.php?action=login">Se connecter</a>
      </p>

    </form>

  <?php endif; ?>

</div>

<?php require_once 'php/footer.php'; ?>