<?php
/** @var array $counts @var int $kinder @var int $newsletter @var array $recent */
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;

$months = [];
$names = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
for ($i = 0; $i < 6; $i++) {
    $months[] = $names[((int) date('n') - 1 + $i) % 12];
}
?>
<h1>Dashboard</h1>

<!-- Key figures: filled once invoices, orders and dates exist -->
<div class="kpis">
  <div class="kpi"><span class="kpi-label">Umsatz <?= e($names[(int) date('n') - 1]) ?>. (bezahlt)</span><span class="kpi-empty">Noch keine Daten</span></div>
  <div class="kpi"><span class="kpi-label">Offene Rechnungen</span><span class="kpi-empty">Noch keine Daten</span></div>
  <div class="kpi"><span class="kpi-label">Forecast Q<?= e((int) ceil((int) date('n') / 3)) ?></span><span class="kpi-empty">Noch keine Daten</span></div>
  <div class="kpi"><span class="kpi-label">Offene Angebote</span><span class="kpi-empty">Noch keine Daten</span></div>
</div>

<div class="kpis">
  <a class="kpi kpi-link" href="/kunden"><span class="kpi-label">Kunden</span><span class="kpi-value"><?= e(array_sum($counts)) ?></span>
    <span class="kpi-sub"><?= e(implode(' · ', array_map(static fn ($t) => Kundentypen::plural($t) . ' ' . ($counts[$t] ?? 0), array_keys(array_filter(Kundentypen::ALLE, static fn ($v, $k) => ($counts[$k] ?? 0) > 0, ARRAY_FILTER_USE_BOTH))))) ?: 'noch keine' ?></span></a>
  <a class="kpi kpi-link" href="/kinder"><span class="kpi-label">Kinder</span><span class="kpi-value"><?= e($kinder) ?></span></a>
  <div class="kpi"><span class="kpi-label">Newsletter-Einwilligungen</span><span class="kpi-value"><?= e($newsletter) ?></span></div>
</div>

<section class="card">
  <div class="card-head">
    <h2>Umsatz-Forecast</h2>
    <span class="legend"><span class="swatch swatch-fix"></span> bezahlt/bestätigt <span class="swatch swatch-open"></span> Angebote (gewichtet)</span>
  </div>
  <div class="chart-empty" role="img" aria-label="Noch keine Forecast-Daten">
    <?php foreach ($months as $m): ?><div class="chart-col"><div class="chart-bar"></div><span><?= e($m) ?></span></div><?php endforeach; ?>
    <p class="chart-note">Noch keine Daten – erscheint, sobald es Aufträge und Rechnungen gibt.</p>
  </div>
</section>

<div class="detail-grid">
  <section class="card"><h2>Offene Angebote</h2><p class="muted">Noch keine Daten.</p></section>
  <section class="card"><h2>Rechnungen</h2><p class="muted">Noch keine Daten.</p></section>
</div>

<div class="detail-grid">
  <section class="card"><h2>Nächste Termine</h2><p class="muted">Noch keine Daten.</p></section>
  <section class="card">
    <h2>Zuletzt angelegte Kunden</h2>
    <?php if (!$recent): ?><p class="muted">Noch keine Kunden.</p><?php else: ?>
      <ul class="plain-list rows">
        <?php foreach ($recent as $k): ?>
          <li><a href="/kunden/ansehen?id=<?= e($k['id']) ?>"><?= e(Kundentypen::displayName($k)) ?></a>
            <span class="badge"><?= e(Kundentypen::label($k['typ'])) ?></span>
            <span class="muted push-right"><?= e(KundenController::germanDate(substr($k['erstellt_am'], 0, 10))) ?></span></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>
