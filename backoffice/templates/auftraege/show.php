<?php
/** Order detail page. @var array $auftrag @var array $dateien @var array $verlauf */
use Backoffice\Modules\Auftraege\Auftragsdaten;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\StatusBadge;

$a = $auftrag;
$kunde = Kundentypen::displayName(['typ' => $a['kunde_typ'], 'name' => $a['kunde_name'], 'email' => $a['kunde_email'],
    'hk_vorname' => $a['hk_vorname'], 'hk_nachname' => $a['hk_nachname']]);
$address = trim(($a['kunde_adresse'] ?? '') . ', ' . trim(($a['kunde_plz'] ?? '') . ' ' . ($a['kunde_ort'] ?? '')), ', ');
// Contact person of the order, else the customer's main contact
$contact = $a['ansprechpartner_id'] !== null
    ? trim(($a['ap_vorname'] ?? '') . ' ' . ($a['ap_nachname'] ?? '')) . ($a['ap_rolle'] ? ' (' . $a['ap_rolle'] . ')' : '')
    : trim(($a['hk_vorname'] ?? '') . ' ' . ($a['hk_nachname'] ?? ''));
$contactLine = $a['ansprechpartner_id'] !== null ? implode(' · ', array_filter([$a['ap_email'], $a['ap_telefon']])) : '';
$date = static fn (?string $d): string => $d ? KundenController::germanDate(substr($d, 0, 10)) : '';
$period = trim($date($a['leistung_von']) . ($a['leistung_bis'] ? ' – ' . $date($a['leistung_bis']) : ''));
?>
<p class="breadcrumb"><a href="/auftraege">← Alle Aufträge</a></p>
<div class="page-head">
  <h1><?= e($a['titel']) ?> <span class="badge"><?= e(Auftragsdaten::typLabel($a['format'])) ?></span>
    <?= StatusBadge::pill(Auftragsdaten::statusLabel($a['status']), Auftragsdaten::statusFarbe($a['status'])) ?></h1>
  <div class="actions">
    <a class="btn" href="/auftraege/bearbeiten?id=<?= e($a['id']) ?>">Bearbeiten</a>
    <a class="btn btn-danger" href="/auftraege/loeschen?id=<?= e($a['id']) ?>">Löschen</a>
  </div>
</div>

<div class="detail-grid">
  <section class="card">
    <h2>Kunde</h2>
    <dl class="facts">
      <dt>Kunde</dt><dd><a href="/kunden/ansehen?id=<?= e($a['kunde_id']) ?>"><?= e($kunde) ?></a> <span class="badge"><?= e(Kundentypen::label($a['kunde_typ'])) ?></span></dd>
      <dt>Ansprechpartner</dt><dd><?= e($contact !== '' ? $contact : '–') ?><?php if ($contactLine !== ''): ?><br><span class="muted"><?= e($contactLine) ?></span><?php endif; ?></dd>
      <dt>Adresse</dt><dd><?= e($address !== '' ? $address : '–') ?></dd>
    </dl>
  </section>

  <section class="card">
    <h2>Auftrag</h2>
    <dl class="facts">
      <dt>Nummer</dt><dd><?= e($a['nummer'] ?: '–') ?></dd>
      <dt>Preis</dt><dd><?= e(Auftragsdaten::preisText((int) $a['summe_cent'], $a['preis_einheit']) ?: '–') ?></dd>
      <dt>Klassen</dt><dd><?= e($a['klassen_anzahl'] ?? '–') ?></dd>
      <dt>Kinder</dt><dd><?= e($a['teilnehmer_anzahl'] ?? '–') ?></dd>
      <dt>Rhythmus</dt><dd><?= $a['regelmaessig'] ? 'Regelmäßig' : 'Einmalig' ?><?= $a['termine'] ? ' · ' . e($a['termine']) : '' ?></dd>
      <dt>Zeitraum</dt><dd><?= e($period !== '' ? $period : '–') ?></dd>
      <dt>Ort</dt><dd><?= e($a['einsatzort'] ?: '–') ?></dd>
    </dl>
  </section>

  <section class="card">
    <h2>Status</h2>
    <?php if (!$verlauf): ?>
      <p class="muted">Noch kein Verlauf.</p>
    <?php else: ?>
      <ul class="plain-list status-log">
        <?php foreach ($verlauf as $v): ?>
          <li>
            <?= StatusBadge::pill(Auftragsdaten::statusLabel($v['status']), Auftragsdaten::statusFarbe($v['status'])) ?>
            <span class="muted"><?= e(KundenController::germanDate(substr($v['zeit'], 0, 10))) ?><?= $v['benutzername'] ? ' · ' . e($v['benutzername']) : '' ?></span>
            <?php if ($v['kommentar']): ?><p class="prewrap status-comment"><?= e($v['kommentar']) ?></p><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<section class="card" id="dokumente">
  <div class="card-head">
    <h2>Dokumente</h2>
    <a class="btn btn-ghost-dark" href="/auftraege/bearbeiten?id=<?= e($a['id']) ?>#dateien">Dateien hochladen</a>
  </div>
  <?php if (!$dateien): ?>
    <p class="muted">Noch keine Dokumente.</p>
  <?php else: ?>
    <ul class="plain-list rows file-list">
      <?php foreach ($dateien as $d): ?>
        <li>
          <a href="/auftraege/datei?id=<?= e($d['id']) ?>" target="_blank" rel="noopener"><?= e($d['dateiname']) ?></a>
          <span class="muted"><?= e(Auftragsdaten::groesse((int) $d['groesse'])) ?></span>
          <span class="muted push-right"><?= e(KundenController::germanDate(substr($d['hochgeladen_am'], 0, 10))) ?><?= $d['hochgeladen_von'] ? ' · ' . e($d['hochgeladen_von']) : '' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Notizen</h2>
  <p class="prewrap"><?= $a['notizen'] ? e($a['notizen']) : '<span class="muted">Keine Notizen.</span>' ?></p>
</section>
