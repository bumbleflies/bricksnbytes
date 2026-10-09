<?php
/** @var array<string, string> $errors */
use Backoffice\Auth;
use Backoffice\Csrf;

$fields = [
    'aktuell' => ['Aktuelles Passwort', 'current-password'],
    'neu' => ['Neues Passwort', 'new-password'],
    'wiederholen' => ['Neues Passwort wiederholen', 'new-password'],
];
?>
<h1>Passwort ändern</h1>

<section class="card password-card">
  <?php if ($errors): ?>
    <p class="flash flash-error" role="alert">Das Passwort wurde nicht geändert. Bitte prüfe die markierten Felder.</p>
  <?php endif; ?>
  <form method="post" action="/passwort" class="form" novalidate>
    <?= Csrf::field() ?>
    <?php foreach ($fields as $name => [$label, $autocomplete]): $err = $errors[$name] ?? null; ?>
      <label for="pw-<?= e($name) ?>"><?= e($label) ?></label>
      <input id="pw-<?= e($name) ?>" name="<?= e($name) ?>" type="password" autocomplete="<?= e($autocomplete) ?>" required
        <?= $name === 'neu' ? 'minlength="' . e(Auth::PASSWORD_MIN_LENGTH) . '" aria-describedby="pw-hint' . ($err ? ' err-' . e($name) : '') . '"' : ($err ? 'aria-describedby="err-' . e($name) . '"' : '') ?>
        <?= $err ? 'aria-invalid="true"' : '' ?>>
      <?php if ($name === 'neu'): ?>
        <p class="hint" id="pw-hint">Mindestens <?= e(Auth::PASSWORD_MIN_LENGTH) ?> Zeichen – am besten ein langes, zufälliges Passwort aus einem Passwort-Manager.</p>
      <?php endif; ?>
      <?php if ($err): ?><p class="field-error" id="err-<?= e($name) ?>"><?= e($err) ?></p><?php endif; ?>
    <?php endforeach; ?>
    <button type="submit" class="btn">Passwort ändern</button>
  </form>
</section>
