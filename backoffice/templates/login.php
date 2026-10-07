<?php
/** @var string $username @var ?string $error */
use Backoffice\Csrf;
?>
<section class="card login-card">
  <h1>Anmelden</h1>
  <?php if (!empty($error)): ?>
    <p class="flash flash-error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="/login" class="form">
    <?= Csrf::field() ?>
    <label for="benutzername">Benutzername</label>
    <input id="benutzername" name="benutzername" type="text" autocomplete="username" required autofocus value="<?= e($username) ?>">
    <label for="passwort">Passwort</label>
    <input id="passwort" name="passwort" type="password" autocomplete="current-password" required>
    <button type="submit" class="btn">Anmelden</button>
  </form>
</section>
