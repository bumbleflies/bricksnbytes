<?php
/** @var array $a @var string $file */
use Backoffice\Csrf;
use Backoffice\Modules\Kunden\Kundentypen;

$delimiterNames = [';' => 'Semikolon ( ; )', ',' => 'Komma ( , )', "\t" => 'Tabulator'];
$person = static fn (array $c): string => trim(($c['vorname'] ?? '') . ' ' . ($c['nachname'] ?? ''));
$newContacts = array_sum(array_map(static fn ($k) => count($k['contacts']), $a['addContacts']));
$toImport = count($a['new']) + $newContacts;
?>
<p class="breadcrumb"><a href="/kunden/import/kontakte">← Andere Datei wählen</a></p>
<h1>Kontakt-Import prüfen</h1>

<section class="card">
  <h2>Datei: <?= e($file) ?></h2>
  <dl class="facts">
    <dt>Zeichensatz</dt><dd><?= e($a['encoding']) ?></dd>
    <dt>Trennzeichen</dt><dd><?= e($delimiterNames[$a['delimiter']] ?? $a['delimiter']) ?></dd>
    <dt>Datenzeilen</dt><dd><?= e($a['total']) ?></dd>
  </dl>
</section>

<div class="stats">
  <div class="stat"><span class="stat-value"><?= e(count($a['new'])) ?></span><span class="stat-label">neue Kunden</span></div>
  <div class="stat"><span class="stat-value"><?= e($newContacts) ?></span><span class="stat-label">neue Ansprechpartner bei bestehenden Kunden</span></div>
  <div class="stat"><span class="stat-value"><?= e(count($a['existing'])) ?></span><span class="stat-label">E-Mail schon vorhanden, übersprungen</span></div>
  <div class="stat"><span class="stat-value<?= $a['check'] ? ' stat-error' : '' ?>"><?= e(count($a['check'])) ?></span><span class="stat-label">bitte prüfen, nicht importiert</span></div>
</div>

<?php if ($a['check']): ?>
  <h2>Bitte prüfen – wird nicht importiert</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Zeile</th><th>E-Mail</th><th>Name</th><th>Organisation</th><th>Typ</th><th>Grund</th></tr></thead>
    <tbody>
      <?php foreach ($a['check'] as $r): ?>
        <tr><td><?= e($r['zeile']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['organisation']) ?></td><td><?= e($r['typ_datei']) ?></td><td><?= e($r['grund']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="hint">Diese Kontakte kannst du bei Bedarf von Hand anlegen.</p>
<?php endif; ?>

<?php if ($a['hints']): ?>
  <h2>Hinweise</h2>
  <ul class="hint-list">
    <?php foreach ($a['hints'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if ($a['new']): ?>
  <h2>Neue Kunden</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Typ</th><th>Status</th><th>E-Mail</th><th>Ansprechpartner</th><th>Zeilen</th></tr></thead>
    <tbody>
      <?php foreach ($a['new'] as $k): $d = $k['data']; ?>
        <tr>
          <td><?= e($k['anzeige']) ?></td>
          <td><?= e(Kundentypen::label($d['typ'])) ?></td>
          <td<?= in_array($d['status'], Kundentypen::STATUS_STOP, true) ? ' class="status-stop"' : '' ?>><?= e(Kundentypen::statusLabel($d['status']) ?: '–') ?></td>
          <td><?= e($d['email'] ?? '–') ?></td>
          <td><?= e(implode(', ', array_map(static fn ($c) => $person($c) . ($c['email'] ? ' <' . $c['email'] . '>' : ''), $k['contacts'])) ?: '–') ?></td>
          <td><?= e(implode(', ', $k['zeilen'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['addContacts']): ?>
  <h2>Neue Ansprechpartner bei bestehenden Kunden</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Kunde</th><th>Neue Ansprechpartner</th><th>Status (nur falls noch leer)</th><th>Zeilen</th></tr></thead>
    <tbody>
      <?php foreach ($a['addContacts'] as $k): ?>
        <tr>
          <td><a href="/kunden/ansehen?id=<?= e($k['kunde_id']) ?>"><?= e($k['anzeige']) ?></a></td>
          <td><?= e(implode(', ', array_map(static fn ($c) => $person($c) . ' <' . $c['email'] . '>', $k['contacts']))) ?></td>
          <td><?= e(Kundentypen::statusLabel($k['status']) ?: '–') ?></td>
          <td><?= e(implode(', ', $k['zeilen'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php if ($a['existing']): ?>
  <details class="card">
    <summary>Schon vorhanden (<?= e(count($a['existing'])) ?>) – werden nicht verändert</summary>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Zeile</th><th>E-Mail</th><th>Name</th><th>Organisation</th></tr></thead>
      <tbody>
        <?php foreach ($a['existing'] as $r): ?>
          <tr><td><?= e($r['zeile']) ?></td><td><a href="/kunden/ansehen?id=<?= e($r['kunde_id']) ?>"><?= e($r['email']) ?></a></td><td><?= e($r['name']) ?></td><td><?= e($r['organisation']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </details>
<?php endif; ?>

<?php if ($toImport > 0): ?>
  <form method="post" action="/kunden/import/kontakte/ausfuehren" class="form-actions import-actions">
    <?= Csrf::field() ?>
    <button type="submit" class="btn">Importieren (<?= e(count($a['new'])) ?> Kunden, <?= e($newContacts) ?> Ansprechpartner)</button>
    <a href="/kunden">Abbrechen</a>
  </form>
<?php else: ?>
  <p class="empty">Es gibt nichts Neues zu importieren.</p>
<?php endif; ?>
