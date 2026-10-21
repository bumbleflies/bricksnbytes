<?php
/** New / edit order. @var ?int $id @var array $auftrag @var array<int, string> $kunden @var array $contacts @var array $dateien
 *  @var ?string $kommentar @var array $errors */
use Backoffice\Csrf;
use Backoffice\Modules\Auftraege\Auftragsdaten;
use Backoffice\Modules\Kunden\KundenController;

$a = $auftrag;
$action = $id === null ? '/auftraege/neu' : '/auftraege/bearbeiten?id=' . $id;
$back = $id === null ? (!empty($a['kunde_id']) ? '/kunden/ansehen?id=' . $a['kunde_id'] : '/auftraege') : '/auftraege/ansehen?id=' . $id;
$val = static fn (string $k): string => (string) ($a[$k] ?? '');
$err = static fn (string $k): string => isset($errors[$k]) ? '<p class="field-error" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';
$cls = static fn (string $k): string => 'field' . (isset($errors[$k]) ? ' has-error' : '');
$preis = isset($a['preis']) ? (string) $a['preis'] : ((int) ($a['summe_cent'] ?? 0) > 0 ? number_format((int) $a['summe_cent'] / 100, 2, ',', '.') : '');
?>
<p class="breadcrumb"><a href="<?= e($back) ?>">← Zurück</a></p>
<h1><?= $id === null ? 'Neuer Auftrag' : 'Bearbeiten: ' . e($a['titel'] ?? '') ?></h1>

<?php if ($errors): ?>
  <p class="flash flash-error" role="alert">Bitte prüfe die markierten Felder.<?= isset($_FILES['dateien']) ? ' Ausgewählte Dateien bitte erneut auswählen.' : '' ?></p>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="card form-grid" enctype="multipart/form-data" novalidate>
  <?= Csrf::field() ?>

  <fieldset>
    <legend>Kunde</legend>
    <div class="inline-row">
      <div class="<?= $cls('kunde_id') ?>">
        <label for="f-kunde">Kunde</label>
        <select id="f-kunde" name="kunde_id"<?= $inv('kunde_id') ?>>
          <option value="">– bitte wählen –</option>
          <?php foreach ($kunden as $kid => $label): ?>
            <option value="<?= e($kid) ?>"<?= (string) $kid === $val('kunde_id') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('kunde_id') ?>
      </div>
      <button type="submit" name="_kunde_wechseln" value="1" class="btn btn-ghost-dark inline-row-btn">Kunde übernehmen</button>
    </div>
    <div class="field">
      <label for="f-ansprechpartner">Ansprechpartner</label>
      <select id="f-ansprechpartner" name="ansprechpartner_id">
        <option value="">– Hauptkontakt des Kunden –</option>
        <?php foreach ($contacts as $c): ?>
          <option value="<?= e($c['id']) ?>"<?= (string) $c['id'] === $val('ansprechpartner_id') ? ' selected' : '' ?>><?= e(trim(($c['vorname'] ?? '') . ' ' . ($c['nachname'] ?? '')) . ($c['rolle'] ? ' (' . $c['rolle'] . ')' : '')) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="hint">Nach einem Kundenwechsel „Kunde übernehmen“ klicken – dann stehen dessen Ansprechpartner zur Wahl.</p>
    </div>
  </fieldset>

  <fieldset>
    <legend>Auftrag</legend>
    <div class="<?= $cls('titel') ?>">
      <label for="f-titel">Titel</label>
      <input id="f-titel" name="titel" value="<?= e($val('titel')) ?>" maxlength="200" placeholder="z. B. Projekttag Robotik 3a/3b"<?= $inv('titel') ?>>
      <?= $err('titel') ?>
    </div>
    <div class="grid-2">
      <div class="<?= $cls('format') ?>">
        <label for="f-format">Typ</label>
        <select id="f-format" name="format"<?= $inv('format') ?>>
          <option value="">– bitte wählen –</option>
          <?php foreach (Auftragsdaten::TYPEN as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $key === $val('format') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('format') ?>
      </div>
      <div class="field">
        <label for="f-ort">Ort</label>
        <input id="f-ort" name="einsatzort" value="<?= e($val('einsatzort')) ?>" maxlength="200" placeholder="falls nicht beim Kunden, z. B. Turnhalle">
      </div>
    </div>
    <div class="grid-2">
      <div class="<?= $cls('preis') ?>">
        <label for="f-preis">Vereinbarter Preis (€)</label>
        <input id="f-preis" name="preis" value="<?= e($preis) ?>" inputmode="decimal" placeholder="z. B. 450 oder 1.234,50"<?= $inv('preis') ?>>
        <?= $err('preis') ?>
      </div>
      <div class="field">
        <label for="f-einheit">Preis gilt</label>
        <select id="f-einheit" name="preis_einheit">
          <?php foreach (Auftragsdaten::PREIS_EINHEITEN as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $key === ($val('preis_einheit') ?: 'gesamt') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="grid-2">
      <div class="<?= $cls('klassen_anzahl') ?>">
        <label for="f-klassen">Anzahl Klassen</label>
        <input id="f-klassen" name="klassen_anzahl" value="<?= e($val('klassen_anzahl')) ?>" inputmode="numeric"<?= $inv('klassen_anzahl') ?>>
        <?= $err('klassen_anzahl') ?>
      </div>
      <div class="<?= $cls('teilnehmer_anzahl') ?>">
        <label for="f-kinder">Anzahl Kinder</label>
        <input id="f-kinder" name="teilnehmer_anzahl" value="<?= e($val('teilnehmer_anzahl')) ?>" inputmode="numeric"<?= $inv('teilnehmer_anzahl') ?>>
        <?= $err('teilnehmer_anzahl') ?>
      </div>
    </div>
    <label class="check"><input type="checkbox" name="regelmaessig" value="1"<?= !empty($a['regelmaessig']) ? ' checked' : '' ?>> Regelmäßig (z. B. wöchentlich im Hort)</label>
    <div class="field">
      <label for="f-termine">Termine</label>
      <input id="f-termine" name="termine" value="<?= e($val('termine')) ?>" maxlength="200" placeholder="z. B. dienstags 14–15 Uhr oder 12.05. 8–12 Uhr">
    </div>
    <div class="grid-2">
      <div class="<?= $cls('leistung_von') ?>">
        <label for="f-von">Zeitraum von</label>
        <input id="f-von" type="date" name="leistung_von" value="<?= e($val('leistung_von')) ?>"<?= $inv('leistung_von') ?>>
        <?= $err('leistung_von') ?>
      </div>
      <div class="<?= $cls('leistung_bis') ?>">
        <label for="f-bis">bis</label>
        <input id="f-bis" type="date" name="leistung_bis" value="<?= e($val('leistung_bis')) ?>"<?= $inv('leistung_bis') ?>>
        <?= $err('leistung_bis') ?>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Status</legend>
    <div class="grid-2">
      <div class="<?= $cls('status') ?>">
        <label for="f-status">Status</label>
        <select id="f-status" name="status"<?= $inv('status') ?>>
          <?php foreach (Auftragsdaten::STATUS as $key => [$label]): ?>
            <option value="<?= e($key) ?>"<?= $key === ($val('status') ?: 'anfrage') ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('status') ?>
      </div>
    </div>
    <div class="<?= $cls('status_kommentar') ?>">
      <label for="f-kommentar">Kommentar zum Status</label>
      <textarea id="f-kommentar" name="status_kommentar" rows="3" maxlength="2000" placeholder="z. B. Angebot per Mail an die Schulleitung, Rückmeldung bis 15.11."><?= e($kommentar ?? '') ?></textarea>
      <?= $err('status_kommentar') ?>
      <p class="hint">Neuer Status oder Kommentar erscheinen mit Datum im Verlauf auf der Detailseite.</p>
    </div>
  </fieldset>

  <fieldset id="dateien">
    <legend>Dateien hochladen</legend>
    <div class="field">
      <label for="f-dateien">Dateien (mehrere möglich)</label>
      <input id="f-dateien" type="file" name="dateien[]" multiple accept="<?= e(implode(',', array_map(static fn ($x) => '.' . $x, array_keys(Auftragsdaten::ERLAUBT)))) ?>">
      <p class="hint">PDF, Word, Excel, PowerPoint, OpenDocument, Text, JPG oder PNG, je höchstens 10 MB. Gespeichert mit „Speichern“.</p>
    </div>
  </fieldset>

  <fieldset>
    <legend>Notizen</legend>
    <div class="field">
      <label for="f-notizen">Interne Notizen</label>
      <textarea id="f-notizen" name="notizen" rows="4" maxlength="5000"><?= e($val('notizen')) ?></textarea>
    </div>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn">Speichern</button>
    <a href="<?= e($back) ?>">Abbrechen</a>
  </div>
</form>

<?php if ($dateien): ?>
  <section class="card">
    <h2>Vorhandene Dateien</h2>
    <ul class="plain-list rows file-list">
      <?php foreach ($dateien as $d): ?>
        <li>
          <a href="/auftraege/datei?id=<?= e($d['id']) ?>" target="_blank" rel="noopener"><?= e($d['dateiname']) ?></a>
          <span class="muted"><?= e(Auftragsdaten::groesse((int) $d['groesse'])) ?> · <?= e(KundenController::germanDate(substr($d['hochgeladen_am'], 0, 10))) ?></span>
          <form method="post" action="/auftraege/datei/loeschen" class="push-right" data-confirm="„<?= e($d['dateiname']) ?>“ löschen?">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= e($d['id']) ?>">
            <input type="hidden" name="auftrag" value="<?= e($id) ?>">
            <button type="submit" class="btn-link">Löschen</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
<script src="/assets/auftraege.js" defer></script>
