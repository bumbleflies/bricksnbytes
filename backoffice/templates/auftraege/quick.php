<?php
/** Read-only summary for the quick-view modal in the order list. @var array $auftrag @var array $dateien @var array $verlauf */
use Backoffice\Modules\Auftraege\Auftragsdaten;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\StatusBadge;

$a = $auftrag;
$kunde = Kundentypen::displayName(['typ' => $a['kunde_typ'], 'name' => $a['kunde_name'], 'email' => $a['kunde_email'],
    'hk_vorname' => $a['hk_vorname'], 'hk_nachname' => $a['hk_nachname']]);
$contact = $a['ansprechpartner_id'] !== null ? trim(($a['ap_vorname'] ?? '') . ' ' . ($a['ap_nachname'] ?? '')) : trim(($a['hk_vorname'] ?? '') . ' ' . ($a['hk_nachname'] ?? ''));
$last = $verlauf[0] ?? null;
?>
<div class="quick" data-edit-href="/auftraege/bearbeiten?id=<?= e($a['id']) ?>" data-detail-href="/auftraege/ansehen?id=<?= e($a['id']) ?>">
  <h2 class="quick-title" id="quick-title"><?= e($a['titel']) ?> <span class="badge"><?= e(Auftragsdaten::typLabel($a['format'])) ?></span></h2>
  <dl class="facts">
    <dt>Kunde</dt><dd><?= e($kunde) ?></dd>
    <dt>Ansprechpartner</dt><dd><?= e($contact !== '' ? $contact : '–') ?></dd>
    <dt>Status</dt><dd><?= StatusBadge::pill(Auftragsdaten::statusLabel($a['status']), Auftragsdaten::statusFarbe($a['status'])) ?>
      <?php if ($last && $last['kommentar']): ?><br><span class="muted"><?= e($last['kommentar']) ?></span><?php endif; ?></dd>
    <dt>Preis</dt><dd><?= e(Auftragsdaten::preisText((int) $a['summe_cent'], $a['preis_einheit']) ?: '–') ?></dd>
    <dt>Klassen</dt><dd><?= e($a['klassen_anzahl'] ?? '–') ?></dd>
    <dt>Rhythmus</dt><dd><?= $a['regelmaessig'] ? 'Regelmäßig' : 'Einmalig' ?><?= $a['termine'] ? ' · ' . e($a['termine']) : '' ?></dd>
  </dl>
  <h3>Dokumente</h3>
  <?php if (!$dateien): ?>
    <p class="muted">Noch keine Dokumente.</p>
  <?php else: ?>
    <ul class="plain-list">
      <?php foreach (array_slice($dateien, 0, 5) as $d): ?>
        <li><a href="/auftraege/datei?id=<?= e($d['id']) ?>" target="_blank" rel="noopener"><?= e($d['dateiname']) ?></a>
          <span class="muted">· <?= e(KundenController::germanDate(substr($d['hochgeladen_am'], 0, 10))) ?></span></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
