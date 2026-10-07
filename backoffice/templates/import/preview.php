<?php
/** @var array $a @var string $file */
use Backoffice\Csrf;

$delimiterNames = [';' => 'Semikolon ( ; )', ',' => 'Komma ( , )', "\t" => 'Tabulator'];
$fieldNames = ['email' => 'E-Mail', 'vorname' => 'Vorname', 'nachname' => 'Nachname', 'status' => 'Newsletter (status)', 'newsletter' => 'Newsletter'];
$newsletterCount = count(array_filter($a['new'], static fn ($r) => $r['newsletter'] === 1));
?>
<p class="breadcrumb"><a href="/kunden/import">← Andere Datei wählen</a></p>
<h1>Import prüfen</h1>

<section class="card">
  <h2>Datei: <?= e($file) ?></h2>
  <dl class="facts">
    <dt>Zeichensatz</dt><dd><?= e($a['encoding']) ?></dd>
    <dt>Trennzeichen</dt><dd><?= e($delimiterNames[$a['delimiter']] ?? $a['delimiter']) ?></dd>
    <dt>Datenzeilen</dt><dd><?= e($a['total']) ?></dd>
    <dt>Spalten in der Datei</dt><dd><?= e(implode(', ', $a['header'])) ?></dd>
    <dt>Übernommen werden</dt>
    <dd><?= e(implode(', ', array_map(static fn ($f) => $fieldNames[$f] . ' ← „' . $a['header'][$a['mapping'][$f]] . '“', array_keys($a['mapping'])))) ?> – alle anderen Spalten werden ignoriert.</dd>
  </dl>
</section>

<div class="stats">
  <div class="stat"><span class="stat-value"><?= e(count($a['new'])) ?></span><span class="stat-label">werden neu angelegt</span></div>
  <div class="stat"><span class="stat-value"><?= e(count($a['duplicates'])) ?></span><span class="stat-label">Dubletten, werden übersprungen</span></div>
  <div class="stat"><span class="stat-value<?= $a['invalid'] ? ' stat-error' : '' ?>"><?= e(count($a['invalid'])) ?></span><span class="stat-label">fehlerhaft, werden nicht importiert</span></div>
</div>
<?php if ($a['new']): ?>
  <p>Davon <?= e($newsletterCount) ?> für den Newsletter angemeldet und <?= e(count($a['new']) - $newsletterCount) ?> nicht bzw. abgemeldet.</p>
<?php endif; ?>

<?php if ($a['invalid']): ?>
  <h2>Fehlerhafte Zeilen</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Zeile</th><th>E-Mail</th><th>Name</th><th>Problem</th></tr></thead>
    <tbody>
      <?php foreach ($a['invalid'] as $r): ?>
        <tr><td><?= e($r['zeile']) ?></td><td><?= e($r['email']) ?></td><td><?= e(trim($r['vorname'] . ' ' . $r['nachname'])) ?></td><td><?= e($r['grund']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['duplicates']): ?>
  <h2>Dubletten</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Zeile</th><th>E-Mail</th><th>Grund</th></tr></thead>
    <tbody>
      <?php foreach ($a['duplicates'] as $r): ?>
        <tr><td><?= e($r['zeile']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['grund']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['new']): ?>
  <h2>Neue Kunden (Vorschau der ersten 10)</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>E-Mail</th><th>Vorname</th><th>Nachname</th><th>Newsletter</th></tr></thead>
    <tbody>
      <?php foreach (array_slice($a['new'], 0, 10) as $r): ?>
        <tr><td><?= e($r['email']) ?></td><td><?= e($r['vorname']) ?></td><td><?= e($r['nachname']) ?></td><td><?= $r['newsletter'] ? 'angemeldet' : 'abgemeldet' ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>

  <form method="post" action="/kunden/import/ausfuehren" class="form-actions import-actions">
    <?= Csrf::field() ?>
    <button type="submit" class="btn"><?= e(count($a['new'])) ?> Kunden importieren</button>
    <a href="/kunden">Abbrechen</a>
  </form>
<?php else: ?>
  <p class="empty">Es gibt nichts Neues zu importieren.</p>
<?php endif; ?>
