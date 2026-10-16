<?php
/** Read-only summary for the quick-view modal in the customer list. @var array $kunde @var ?string $zurueck */
use Backoffice\Modules\Kinder\Alter;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;

$privat = Kundentypen::isPrivat($kunde['typ']);
$nl = $kunde['einwilligungen']['newsletter'] ?? null;
$address = trim(($kunde['adresse'] ?? '') . ', ' . trim(($kunde['plz'] ?? '') . ' ' . ($kunde['ort'] ?? '')), ', ');
$editHref = '/kunden/bearbeiten?id=' . $kunde['id'] . ($zurueck !== null ? '&zurueck=' . rawurlencode($zurueck) : '');
?>
<div class="quick" data-edit-href="<?= e($editHref) ?>" data-detail-href="/kunden/ansehen?id=<?= e($kunde['id']) ?>">
  <h2 class="quick-title" id="quick-title"><?= e(Kundentypen::displayName($kunde)) ?> <span class="badge"><?= e(Kundentypen::label($kunde['typ'])) ?></span></h2>
  <dl class="facts">
    <?php if (!$privat): ?><dt>Name</dt><dd><?= e($kunde['name'] ?: '–') ?></dd><?php endif; ?>
    <dt>Vorname</dt><dd><?= e($kunde['vorname'] ?: '–') ?></dd>
    <dt>Nachname</dt><dd><?= e($kunde['nachname'] ?: '–') ?></dd>
    <dt>Status</dt><dd><?php if ($kunde['status']): ?><span<?= in_array($kunde['status'], Kundentypen::STATUS_STOP, true) ? ' class="status-stop"' : '' ?>><?= e(Kundentypen::statusLabel($kunde['status'])) ?></span><?php else: ?>–<?php endif; ?></dd>
    <dt>E-Mail</dt><dd><?php if ($kunde['email']): ?><a href="mailto:<?= e($kunde['email']) ?>"><?= e($kunde['email']) ?></a><?php else: ?>–<?php endif; ?></dd>
    <dt>Telefon</dt><dd><?= $kunde['telefon'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $kunde['telefon'])) . '">' . e($kunde['telefon']) . '</a>' : '–' ?></dd>
    <dt>Adresse</dt><dd><?= e($address ?: '–') ?></dd>
    <dt>Newsletter</dt><dd>
      <?php if ($nl && $nl['erteilt_am'] && !$nl['widerrufen_am']): ?>
        <span class="badge badge-ok">Ja, seit <?= e(KundenController::germanDate($nl['erteilt_am'])) ?></span>
      <?php elseif ($nl && $nl['widerrufen_am']): ?>
        <span class="badge badge-error">Nein, widerrufen am <?= e(KundenController::germanDate($nl['widerrufen_am'])) ?></span>
      <?php else: ?>
        <span class="badge">Nein</span>
      <?php endif; ?>
    </dd>
  </dl>

  <h3><?= $privat ? 'Eltern' : 'Ansprechpartner' ?></h3>
  <?php if (!$kunde['ansprechpartner']): ?>
    <p class="muted">Noch keine hinterlegt.</p>
  <?php else: ?>
    <ul class="plain-list">
      <?php foreach ($kunde['ansprechpartner'] as $a): ?>
        <li>
          <strong><?= e(trim(($a['vorname'] ?? '') . ' ' . ($a['nachname'] ?? ''))) ?></strong>
          <?php if ($a['rolle']): ?><span class="muted">· <?= e($a['rolle']) ?></span><?php endif; ?>
          <?php if ($a['ist_hauptkontakt']): ?><span class="badge badge-ok">Haupt</span><?php endif; ?>
          <?php if ($a['email'] || $a['telefon']): ?><br><span class="muted"><?= e(implode(' · ', array_filter([$a['email'], $a['telefon']]))) ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($privat): ?>
    <h3>Kinder</h3>
    <?php if (!$kunde['kinder']): ?>
      <p class="muted">Noch keine Kinder hinterlegt.</p>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($kunde['kinder'] as $k): ?>
          <li><strong><?= e(trim($k['vorname'] . ' ' . ($k['nachname'] ?? ''))) ?></strong> <span class="muted">· <?= e(Alter::text($k['geburtsdatum'], $k['geburtsjahr'])) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>
</div>
