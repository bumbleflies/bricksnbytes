<?php
/** @var array $kunde */
use Backoffice\Csrf;
use Backoffice\Modules\Kunden\Kundentypen;

$kinder = array_map(static fn ($k) => trim($k['vorname'] . ' ' . ($k['nachname'] ?? '')), $kunde['kinder']);
?>
<section class="card confirm">
  <h1>Kunde löschen?</h1>
  <p>Soll <strong><?= e(Kundentypen::displayName($kunde)) ?></strong><?php if ($kunde['email']): ?> (<?= e($kunde['email']) ?>)<?php endif; ?> wirklich gelöscht werden?</p>
  <p>Mit gelöscht werden:</p>
  <ul>
    <li><?= e(count($kunde['ansprechpartner'])) ?> Ansprechpartner</li>
    <?php if ($kinder): ?><li><?= e(count($kinder)) ?> <?= count($kinder) === 1 ? 'Kind' : 'Kinder' ?>: <?= e(implode(', ', $kinder)) ?></li><?php endif; ?>
    <li>alle Einwilligungen</li>
  </ul>
  <p class="warn">Das lässt sich nicht rückgängig machen. Im Änderungsprotokoll bleibt nur vermerkt, dass gelöscht wurde – ohne Inhalte.</p>
  <form method="post" action="/kunden/loeschen?id=<?= e($kunde['id']) ?>" class="form-actions">
    <?= Csrf::field() ?>
    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
    <a href="/kunden/ansehen?id=<?= e($kunde['id']) ?>">Abbrechen</a>
  </form>
</section>
