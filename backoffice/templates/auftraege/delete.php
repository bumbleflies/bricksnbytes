<?php
/** @var array $auftrag @var array $dateien */
use Backoffice\Csrf;
?>
<section class="card confirm">
  <h1>Auftrag löschen?</h1>
  <p>Soll <strong><?= e($auftrag['titel']) ?></strong><?php if ($auftrag['nummer']): ?> (<?= e($auftrag['nummer']) ?>)<?php endif; ?> wirklich gelöscht werden?</p>
  <p>Mit gelöscht werden:</p>
  <ul>
    <li><?= e(count($dateien)) ?> <?= count($dateien) === 1 ? 'Datei' : 'Dateien' ?></li>
    <li>der Status-Verlauf mit allen Kommentaren</li>
  </ul>
  <p class="warn">Das lässt sich nicht rückgängig machen. Der Kunde bleibt erhalten.</p>
  <form method="post" action="/auftraege/loeschen" class="form-actions">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= e($auftrag['id']) ?>">
    <button type="submit" class="btn btn-danger">Endgültig löschen</button>
    <a href="/auftraege/ansehen?id=<?= e($auftrag['id']) ?>">Abbrechen</a>
  </form>
</section>
