<?php
/** @var array $run @var array $pruefung */
use Backoffice\Csrf;
use Backoffice\Modules\Import\ImportUndo;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;

$unchanged = $pruefung['unchanged'];
$changed = $pruefung['changed'];
$list = static function (array $rows): string {
    $items = array_map(static fn ($r) => '<li><a href="/kunden/ansehen?id=' . e($r['id']) . '">' . e($r['anzeige']) . '</a> <span class="muted">· '
        . e(Kundentypen::label($r['typ'])) . ($r['email'] && $r['email'] !== $r['anzeige'] ? ' · ' . e($r['email']) : '') . '</span></li>', $rows);
    return '<ul class="plain-list">' . implode('', $items) . '</ul>';
};
?>
<p class="breadcrumb"><a href="/kunden/importe">← Alle Importe</a></p>
<h1>Import rückgängig machen</h1>

<section class="card">
  <dl class="facts">
    <dt>Art</dt><dd><?= e(ImportUndo::label($run['quelle'])) ?> (Quelle „<?= e($run['quelle'] ?? '–') ?>“)</dd>
    <dt>Zeitpunkt</dt><dd><?= e(KundenController::germanDate($run['von'])) ?></dd>
    <dt>Kunden</dt><dd><?= e(count($run['ids'])) ?>, davon <?= e(count($unchanged)) ?> unverändert</dd>
  </dl>
</section>

<?php if ($changed): ?>
  <details class="card">
    <summary>Bleiben erhalten (<?= e(count($changed)) ?>) – seit dem Import geändert oder in Gebrauch</summary>
    <?= $list($changed) ?>
  </details>
<?php endif; ?>

<?php if ($unchanged): ?>
  <details class="card" open>
    <summary>Werden gelöscht (<?= e(count($unchanged)) ?>) – samt Ansprechpartnern, Kindern und Einwilligungen aus dem Import</summary>
    <?= $list($unchanged) ?>
  </details>
  <form method="post" action="/kunden/importe/rueckgaengig" class="form-actions">
    <?= Csrf::field() ?>
    <input type="hidden" name="lauf" value="<?= e($run['id']) ?>">
    <button type="submit" class="btn btn-danger"><?= e(count($unchanged)) ?> Kunden löschen</button>
    <a href="/kunden/importe">Abbrechen</a>
  </form>
  <p class="hint">Das lässt sich nicht rückgängig machen. Die Datei kannst du danach erneut (über den richtigen Import) hochladen.</p>
<?php else: ?>
  <p class="empty">Alle Kunden dieses Imports wurden inzwischen geändert – es gibt nichts zu löschen.</p>
<?php endif; ?>
