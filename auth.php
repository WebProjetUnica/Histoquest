<?php
require_once 'php/config.php';
$page_title = 'Connexion';
require_once 'php/header.php';
?>

<div class="auth-container">

  <!-- Onglets CSS — ton binôme les animera -->
  <div class="auth-tabs">
    <button class="auth-tab auth-tab--active" data-tab="login">
      Connexion
    </button>
    <button class="auth-tab" data-tab="register">
      Inscription
    </button>
  </div>

  <!-- Formulaire connexion -->
  <form class="auth-form" id="form-login"
        method="POST" action="auth.php?action=login">
    <h2 class="auth-form__title">Se connecter</h2>

    <!-- Zone d'erreur — remplie par PHP en étape 2 -->
    <div class="form-error" id="login-error"></div>

    <div class="form-group">
      <label for="login-pseudo">Pseudo</label>
      <input type="text" id="login-pseudo" name="pseudo"
             required minlength="3" placeholder="Ton pseudo">
    </div>

    <div class="form-group">
      <label for="login-password">Mot de passe</label>
      <input type="password" id="login-password" name="password"
             required minlength="8" placeholder="••••••••">
    </div>

    <button type="submit" class="btn btn-primary btn-full">
      Se connecter
    </button>
  </form>

  <!-- Formulaire inscription -->
  <form class="auth-form auth-form--hidden" id="form-register"
        method="POST" action="auth.php?action=register">
    <h2 class="auth-form__title">Créer un compte</h2>

    <div class="form-error" id="register-error"></div>

    <div class="form-group">
      <label for="register-pseudo">Pseudo</label>
      <input type="text" id="register-pseudo" name="pseudo"
             required minlength="3" placeholder="Choisis un pseudo">
    </div>

    <div class="form-group">
      <label for="register-password">Mot de passe</label>
      <input type="password" id="register-password" name="password"
             required minlength="8" placeholder="8 caractères minimum">
    </div>

    <button type="submit" class="btn btn-primary btn-full">
      Créer mon compte
    </button>
  </form>

</div>

<?php require_once 'php/footer.php'; ?>