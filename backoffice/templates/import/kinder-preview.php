<?php
/** @var array $a @var string $file */
use Backoffice\Csrf;

$delimiterNames = [';' => 'Semikolon ( ; )', ',' => 'Komma ( , )', "\t" => 'Tabulator'];
$newFamilyChildren = array_sum(array_map(static fn ($f) => count($f['kinder']), $a['families']));
$toImport = $newFamilyChildren + count($a['newChildren']);
$name = static fn (array $k): string => trim($k['vorname'] . ' ' . ($k['nachname'] ?? ''));
$year = static fn (array $k): string => $k['geburtsjahr'] !== null ? '≈ ' . $k['geburtsjahr'] : '–';
$parents = static fn (array $list): string => implode(', ', array_map(static fn ($p) => trim(($p['vorname'] ?? '') . ' ' . ($p['nachname'] ?? '')), $list)) ?: '–';
?>
<p class="breadcrumb"><a href="/kinder/import">← Andere Datei wählen</a></p>
<h1>Kinder-Import prüfen</h1>

<section class="card">
  <h2>Datei: <?= e($file) ?></h2>
  <dl class="facts">
    <dt>Zeichensatz</dt><dd><?= e($a['encoding']) ?></dd>
    <dt>Trennzeichen</dt><dd><?= e($delimiterNames[$a['delimiter']] ?? $a['delimiter']) ?></dd>
    <dt>Datenzeilen</dt><dd><?= e($a['total']) ?> (Buchungen)</dd>
  </dl>
</section>

<div class="stats">
  <div class="stat"><span class="stat-value"><?= e(count($a['families'])) ?></span><span class="stat-label">neue Familien (<?= e($newFamilyChildren) ?> Kinder)</span></div>
  <div class="stat"><span class="stat-value"><?= e(count($a['newChildren'])) ?></span><span class="stat-label">Kinder für bestehende Kunden</span></div>
  <div class="stat"><span class="stat-value"><?= e(count($a['existing'])) ?></span><span class="stat-label">schon vorhanden, übersprungen</span></div>
  <div class="stat"><span class="stat-value<?= $a['check'] ? ' stat-error' : '' ?>"><?= e(count($a['check'])) ?></span><span class="stat-label">bitte prüfen, nicht importiert</span></div>
</div>

<?php if ($a['check']): ?>
  <h2>Bitte prüfen – wird nicht importiert</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Zeile</th><th>E-Mail</th><th>Name Kind</th><th>Name Eltern</th><th>Grund</th></tr></thead>
    <tbody>
      <?php foreach ($a['check'] as $r): ?>
        <tr><td><?= e($r['zeile']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['kind']) ?></td><td><?= e($r['eltern']) ?></td><td><?= e($r['grund']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="hint">Diese Kinder kannst du bei Bedarf von Hand beim Kunden ergänzen.</p>
<?php endif; ?>

<?php if ($a['otherEmails']): ?>
  <h2>Hinweis: gleiche Kinder unter verschiedenen E-Mail-Adressen</h2>
  <p class="hint">Diese Kinder kommen unter mehreren Adressen vor (z. B. zweite Adresse eines Elternteils). Sie werden je Adresse angelegt – nach dem Import bei Bedarf beim Kunden zusammenführen.</p>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Kind</th><th>E-Mail-Adressen</th></tr></thead>
    <tbody>
      <?php foreach ($a['otherEmails'] as $o): ?>
        <tr><td><?= e($o['kind']) ?></td><td><?= e(implode(', ', $o['emails'])) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['families']): ?>
  <h2>Neue Familien</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>E-Mail</th><th>Eltern</th><th>Kinder (Geburtsjahr geschätzt)</th></tr></thead>
    <tbody>
      <?php foreach ($a['families'] as $f): ?>
        <tr>
          <td><?= e($f['email']) ?></td>
          <td><?= e($parents($f['eltern'])) ?></td>
          <td><?= e(implode(', ', array_map(static fn ($k) => $name($k) . ' (' . $year($k) . ')', $f['kinder']))) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['newChildren']): ?>
  <h2>Kinder für bestehende Kunden</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>E-Mail des Kunden</th><th>Kind</th><th>Geburtsjahr</th><th>Hinweis</th></tr></thead>
    <tbody>
      <?php foreach ($a['newChildren'] as $k): ?>
        <tr><td><a href="/kunden/ansehen?id=<?= e($k['kunde_id']) ?>"><?= e($k['email']) ?></a></td><td><?= e($name($k)) ?></td><td><?= e($year($k)) ?></td><td><?= e(str_contains($k['notizen'], 'prüfen') ? 'Alter prüfen' : '') ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($toImport > 0): ?>
  <form method="post" action="/kinder/import/ausfuehren" class="form-actions import-actions">
    <?= Csrf::field() ?>
    <button type="submit" class="btn"><?= e($toImport) ?> Kinder importieren</button>
    <a href="/kinder">Abbrechen</a>
  </form>
<?php else: ?>
  <p class="empty">Es gibt nichts Neues zu importieren.</p>
<?php endif; ?>
