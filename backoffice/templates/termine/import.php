<?php
/** Planner take-over. @var ?array $vorschau @var int $jahr @var int $vorhanden @var ?string $fehler */
use Backoffice\Csrf;
use Backoffice\Modules\Termine\Kalender;
?>
<p class="breadcrumb"><a href="/termine">← Zurück zu den Terminen</a></p>
<h1>Planer übernehmen</h1>
<?php if ($fehler !== null): ?><p class="flash flash-error" role="alert"><?= e($fehler) ?></p><?php endif; ?>

<?php if ($vorschau === null): ?>
  <section class="card">
    <p>Übernimmt die Einträge aus der Planer-Tabelle (ein Reiter pro Jahr) als eigene Termine. In Google Sheets:
      Reiter öffnen → <em>Datei → Herunterladen → Kommagetrennte Werte (.csv)</em>. Jede Zeile einer Zelle wird ein Termin,
      Uhrzeiten am Anfang („14-16 …“, „8:00 - 9:00 …“) werden erkannt.</p>
    <form method="post" action="/termine/import" enctype="multipart/form-data" class="form-grid">
      <?= Csrf::field() ?>
      <div class="grid-2">
        <div class="field">
          <label for="f-datei">CSV-Datei des Jahres-Reiters</label>
          <input id="f-datei" type="file" name="datei" accept=".csv,text/csv" required>
        </div>
        <div class="field">
          <label for="f-jahr">Jahr (falls nicht in der Datei)</label>
          <input id="f-jahr" name="jahr" value="<?= e($jahr) ?>" inputmode="numeric">
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn">Vorschau</button></div>
    </form>
  </section>
<?php else:
    $list = $vorschau['eintraege'];
    $withTime = count(array_filter($list, static fn ($x) => $x['beginn'] !== null)); ?>
  <section class="card">
    <h2>Vorschau <?= e($jahr) ?></h2>
    <p><strong><?= e(count($list)) ?> Termine</strong>, davon <?= e($withTime) ?> mit Uhrzeit. Noch ist nichts gespeichert.</p>
    <?php if ($vorschau['probleme']): ?>
      <p class="warn">Nicht übernommen:</p>
      <ul class="hint-list"><?php foreach ($vorschau['probleme'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" action="/termine/import/uebernehmen" class="form-grid">
      <?= Csrf::field() ?>
      <?php if ($vorhanden > 0): ?>
        <label class="check"><input type="checkbox" name="ersetzen" value="1" checked> Die <?= e($vorhanden) ?> Termine aus der früheren Übernahme für <?= e($jahr) ?> vorher entfernen (von Hand eingetragene bleiben)</label>
      <?php endif; ?>
      <div class="form-actions">
        <button type="submit" class="btn">Übernehmen</button>
        <a href="/termine/import">Andere Datei</a>
      </div>
    </form>
  </section>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Datum</th><th scope="col">Uhrzeit</th><th scope="col" class="col-wrap">Termin</th></tr></thead>
      <tbody>
        <?php foreach (array_slice($list, 0, 60) as $x): ?>
          <tr><td><?= e(date('d.m.Y', strtotime($x['datum']))) ?></td><td><?= e(Kalender::zeit($x['beginn'], $x['ende'])) ?></td><td class="col-wrap"><?= e($x['titel']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($list) > 60): ?><p class="hint">Die ersten 60 von <?= e(count($list)) ?> Terminen.</p><?php endif; ?>
<?php endif; ?>
