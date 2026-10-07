<?php
/** @var array $kunde @var array $protokoll */
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kinder\Alter;

$privat = Kundentypen::isPrivat($kunde['typ']);
$actionLabels = ['angelegt' => 'angelegt', 'geaendert' => 'geändert', 'geloescht' => 'gelöscht', 'importiert' => 'importiert'];
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<div class="page-head">
  <h1><?= e(Kundentypen::displayName($kunde)) ?> <span class="badge"><?= e(Kundentypen::label($kunde['typ'])) ?></span></h1>
  <div class="actions">
    <a class="btn" href="/kunden/bearbeiten?id=<?= e($kunde['id']) ?>">Bearbeiten</a>
    <a class="btn btn-danger" href="/kunden/loeschen?id=<?= e($kunde['id']) ?>">Löschen</a>
  </div>
</div>

<div class="detail-grid">
  <section class="card">
    <h2>Stammdaten</h2>
    <dl class="facts">
      <?php if (!$privat): ?><dt>Name</dt><dd><?= e($kunde['name']) ?></dd><?php endif; ?>
      <dt>E-Mail</dt><dd><?php if ($kunde['email']): ?><a href="mailto:<?= e($kunde['email']) ?>"><?= e($kunde['email']) ?></a><?php else: ?>–<?php endif; ?></dd>
      <dt>Telefon</dt><dd><?= $kunde['telefon'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $kunde['telefon'])) . '">' . e($kunde['telefon']) . '</a>' : '–' ?></dd>
      <dt>Adresse</dt><dd><?= e(trim(($kunde['adresse'] ?? '') . ', ' . trim(($kunde['plz'] ?? '') . ' ' . ($kunde['ort'] ?? '')), ', ')) ?: '–' ?></dd>
      <dt>Quelle</dt><dd><?= e($kunde['quelle'] ?: '–') ?></dd>
      <dt>Angelegt</dt><dd><?= e(KundenController::germanDate($kunde['erstellt_am'])) ?></dd>
      <dt>Geändert</dt><dd><?= e(KundenController::germanDate($kunde['geaendert_am'])) ?></dd>
    </dl>
  </section>

  <section class="card">
    <h2><?= $privat ? 'Eltern' : 'Ansprechpartner' ?></h2>
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
  </section>

  <?php if ($privat): ?>
    <section class="card">
      <h2>Kinder</h2>
      <?php if (!$kunde['kinder']): ?>
        <p class="muted">Noch keine Kinder hinterlegt.</p>
      <?php else: ?>
        <ul class="plain-list">
          <?php foreach ($kunde['kinder'] as $k): ?>
            <li>
              <strong><?= e(trim($k['vorname'] . ' ' . ($k['nachname'] ?? ''))) ?></strong>
              <span class="muted">· <?= e(Alter::text($k['geburtsdatum'], $k['geburtsjahr'])) ?></span>
              <?php if ($k['abholberechtigte']): ?><br><span class="muted">Abholberechtigt: <?= e($k['abholberechtigte']) ?></span><?php endif; ?>
              <?php if ($k['notizen']): ?><br><span class="muted">Notiz: <?= e($k['notizen']) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <section class="card">
    <h2>Einwilligungen</h2>
    <ul class="plain-list">
      <?php foreach (Kundentypen::EINWILLIGUNGEN as $art => $label): $c = $kunde['einwilligungen'][$art] ?? null; ?>
        <li><?= e($label) ?>:
          <?php if ($c && $c['widerrufen_am']): ?>
            <span class="badge badge-error">widerrufen am <?= e(KundenController::germanDate($c['widerrufen_am'])) ?></span>
          <?php elseif ($c && $c['erteilt_am']): ?>
            <span class="badge badge-ok">erteilt am <?= e(KundenController::germanDate($c['erteilt_am'])) ?></span>
          <?php else: ?>
            <span class="badge">keine</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<section class="card">
  <h2>Notizen</h2>
  <p class="prewrap"><?= $kunde['notizen'] ? e($kunde['notizen']) : '<span class="muted">Keine Notizen.</span>' ?></p>
</section>

<div class="detail-grid">
  <section class="card"><h2>Aufträge</h2><p class="muted">Noch keine Aufträge – das Modul kommt bald.</p></section>
  <section class="card"><h2>Buchungen</h2><p class="muted">Noch keine Buchungen – das Modul kommt bald.</p></section>
  <section class="card"><h2>Rechnungen</h2><p class="muted">Noch keine Rechnungen – das Modul kommt bald.</p></section>
</div>

<section class="card">
  <h2>Änderungsprotokoll</h2>
  <?php if (!$protokoll): ?>
    <p class="muted">Keine Einträge.</p>
  <?php else: ?>
    <ul class="plain-list log">
      <?php foreach ($protokoll as $p):
          $fields = $p['aenderungen'] ? array_keys(json_decode($p['aenderungen'], true) ?: []) : []; ?>
        <li>
          <span class="muted"><?= e(KundenController::germanDate($p['zeit'])) ?></span>
          · <?= e($p['benutzername'] ?? 'unbekannt') ?> hat <?= e($actionLabels[$p['aktion']] ?? $p['aktion']) ?>
          <?php if ($fields && $p['aktion'] === 'geaendert'): ?><span class="muted">(<?= e(implode(', ', $fields)) ?>)</span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
