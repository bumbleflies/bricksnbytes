<?php
/** @var array $kunde */
use Backoffice\Csrf;

$name = trim(($kunde['vorname'] ?? '') . ' ' . ($kunde['nachname'] ?? '')) ?: ($kunde['email'] ?? 'diesen Kunden');
?>
<section class="card confirm">
  <h1>Kunde löschen?</h1>
  <p>Soll <strong><?= e($name) ?></strong><?php if ($kunde['email']): ?> (<?= e($kunde['email']) ?>)<?php endif; ?> wirklich gelöscht werden?</p>
  <?php if ($kunde['kinder']): ?>
    <p>Dabei werden auch <?= count($kunde['kinder']) === 1 ? 'das Kind' : 'die ' . e(count($kunde['kinder'])) . ' Kinder' ?>
      <?= e(implode(', ', array_column($kunde['kinder'], 'vorname'))) ?> gelöscht.</p>
  <?php endif; ?>
  <p class="warn">Das lässt sich nicht rückgängig machen.</p>
  <form method="post" action="/kunden/loeschen?id=<?= e($kunde['id']) ?>" class="form-actions">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
    <a href="/kunden/bearbeiten?id=<?= e($kunde['id']) ?>">Abbrechen</a>
  </form>
</section>
