<?php
/** @var string $q @var array $kunden @var int $totalKunden @var array $kinder */
use Backoffice\Modules\Kinder\Alter;
use Backoffice\Modules\Kunden\Kundentypen;
?>
<h1>Suche<?= $q !== '' ? ': „' . e($q) . '“' : '' ?></h1>
<?php if (mb_strlen($q) < 2): ?>
  <p class="empty">Bitte mindestens zwei Zeichen in das Suchfeld oben eingeben.</p>
<?php else: ?>
  <div class="detail-grid">
    <section class="card">
      <h2>Kunden <span class="count"><?= e($totalKunden) ?></span></h2>
      <?php if (!$kunden): ?><p class="muted">Keine Treffer.</p><?php else: ?>
        <ul class="plain-list">
          <?php foreach ($kunden as $k): ?>
            <li><a href="/kunden/ansehen?id=<?= e($k['id']) ?>"><?= e(Kundentypen::displayName($k)) ?></a>
              <span class="muted">· <?= e(Kundentypen::label($k['typ'])) ?><?= $k['ort'] ? ' · ' . e($k['ort']) : '' ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($totalKunden > count($kunden)): ?><p><a href="/kunden?q=<?= e(rawurlencode($q)) ?>">Alle <?= e($totalKunden) ?> Treffer anzeigen</a></p><?php endif; ?>
      <?php endif; ?>
    </section>
    <section class="card">
      <h2>Kinder <span class="count"><?= e(count($kinder)) ?></span></h2>
      <?php if (!$kinder): ?><p class="muted">Keine Treffer.</p><?php else: ?>
        <ul class="plain-list">
          <?php foreach ($kinder as $k): $eltern = trim(($k['hk_vorname'] ?? '') . ' ' . ($k['hk_nachname'] ?? '')); ?>
            <li><a href="/kunden/ansehen?id=<?= e($k['kunde_id']) ?>"><?= e(trim($k['vorname'] . ' ' . ($k['nachname'] ?? ''))) ?></a>
              <span class="muted">· <?= e(Alter::text($k['geburtsdatum'], $k['geburtsjahr'])) ?><?= $eltern !== '' ? ' · Eltern: ' . e($eltern) : '' ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
<?php endif; ?>
