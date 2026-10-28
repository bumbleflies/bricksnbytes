<?php
/**
 * Dates overview. @var string $ansicht @var DateTimeImmutable $from @var DateTimeImmutable $to
 * @var array<string, list<array>> $tage (already filtered) @var string $kategorie filter ('' = all)
 */
use Backoffice\Modules\Termine\Kalender;

$kategorie = $kategorie ?? '';
// Links keep the view's filter unless another one is given
$url = static fn (string $view, DateTimeImmutable $d, ?string $filter = null): string => '/termine?ansicht=' . $view . '&ab=' . $d->format('Y-m')
    . (($filter ?? $kategorie) !== '' ? '&kategorie=' . urlencode($filter ?? $kategorie) : '');
$today = date('Y-m-d');
$now = date('H:i');
$labels = ['kurs' => 'Kurs', 'auftrag' => 'Auftrag', 'geburtstag' => 'Geburtstag'];
$arten = array_diff_key(Kalender::ARTEN, ['' => true]);   // the categories shown as a prefix
// One entry as a small line: time, title; own entries open the form, the others their page
$entry = static function (array $e, bool $past = false) use ($labels, $arten): string {
    $time = Kalender::zeit($e['beginn'], $e['ende']);
    $text = ($e['quelle'] === 'geburtstag' ? '🎂 ' : '') . $e['titel'];
    $label = isset($labels[$e['quelle']]) ? $labels[$e['quelle']] . ': ' : (isset($arten[$e['art'] ?? '']) ? $arten[$e['art'] ?? ''] . ': ' : '');
    $inner = ($time !== '' ? '<span class="cal-time">' . e($time) . '</span> ' : '') . '<span class="cal-text">' . e($text) . '</span>';
    $attrs = ' class="cal-entry cal-entry--' . e(Kalender::farbe(Kalender::kategorie($e))) . ($past ? ' is-past' : '') . '" title="' . e($label . trim($time . ' ' . $e['titel']) . ($past ? ' (vorbei)' : '')) . '"';
    return $e['link'] !== null ? '<a href="' . e($e['link']) . '"' . $attrs . '>' . $inner . '</a>' : '<span' . $attrs . '>' . $inner . '</span>';
};
?>
<div class="page-head">
  <h1>Termine</h1>
  <div class="actions">
    <a class="btn btn-secondary" href="/termine/import">Planer übernehmen</a>
    <a class="btn" href="/termine/neu">+ Termin</a>
  </div>
</div>

<div class="cal-bar">
  <nav class="cal-views" aria-label="Ansicht">
    <?php foreach (Kalender::ANSICHTEN as $key => [$label]): ?>
      <a href="<?= e($url($key, $from)) ?>"<?= $key === $ansicht ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="cal-nav">
    <a class="icon-btn" href="<?= e($url($ansicht, Kalender::blaettern($ansicht, $from, -1))) ?>" aria-label="Zurück" title="Zurück">‹</a>
    <h2 class="cal-title"><?= e(Kalender::titel($ansicht, $from, $to)) ?></h2>
    <a class="icon-btn" href="<?= e($url($ansicht, Kalender::blaettern($ansicht, $from, 1))) ?>" aria-label="Weiter" title="Weiter">›</a>
    <a class="btn-link" href="<?= e($url($ansicht, new DateTimeImmutable('today'))) ?>">Heute</a>
  </div>
</div>
<nav class="cal-filter" aria-label="Kategorie">
  <a href="<?= e($url($ansicht, $from, '')) ?>"<?= $kategorie === '' ? ' aria-current="true"' : '' ?>>Alle</a>
  <?php foreach (Kalender::FILTER as $key => $label): ?>
    <a class="cal-entry--<?= e(Kalender::farbe($key)) ?>" href="<?= e($url($ansicht, $from, $key)) ?>"<?= $key === $kategorie ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php if ($kategorie !== '' && !$tage): ?>
  <p class="muted">Keine Termine „<?= e(Kalender::FILTER[$kategorie]) ?>“ in diesem Zeitraum.</p>
<?php endif; ?>

<?php if ($ansicht === 'monat'): ?>
  <div class="table-wrap">
    <table class="cal-month">
      <thead><tr><?php foreach (Kalender::WOCHENTAGE as $wd): ?><th scope="col"><?= e($wd) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach (Kalender::monatsRaster((int) $from->format('Y'), (int) $from->format('n')) as $week): ?>
          <tr>
            <?php foreach ($week as $i => $date):
                $out = substr($date, 0, 7) !== $from->format('Y-m'); ?>
              <td class="<?= trim(($out ? 'is-out ' : '') . ($i >= 5 ? 'is-weekend ' : '') . ($date < $today ? 'is-past ' : '') . ($date === $today ? 'is-today' : '')) ?>">
                <div class="cal-day">
                  <span class="cal-daynum"><?= e((int) substr($date, 8)) ?>.</span>
                  <a class="cal-add" href="/termine/neu?datum=<?= e($date) ?>" aria-label="Termin am <?= e(date('d.m.Y', strtotime($date))) ?> eintragen" title="Termin eintragen">+</a>
                </div>
                <?php foreach ($tage[$date] ?? [] as $e) { echo $entry($e, Kalender::vorbei($date, $e, $today, $now)); } ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else:
    $months = [];
    for ($d = $from; $d <= $to; $d = $d->modify('+1 month')) {
        $months[] = $d;
    } ?>
  <div class="table-wrap">
    <table class="cal-plan cal-plan--<?= e(count($months)) ?>">
      <thead><tr><?php foreach ($months as $m): ?><th scope="col"><a href="<?= e($url('monat', $m)) ?>"><?= e(Kalender::MONATE[(int) $m->format('n') - 1]) ?></a></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php for ($day = 1; $day <= 31; $day++): ?>
          <tr>
            <?php foreach ($months as $m):
                $y = (int) $m->format('Y');
                $mo = (int) $m->format('n');
                if (!checkdate($mo, $day, $y)): ?>
                <td class="is-none"></td>
              <?php else:
                $date = sprintf('%04d-%02d-%02d', $y, $mo, $day);
                $wd = (int) date('N', strtotime($date)); ?>
                <td class="<?= trim(($wd >= 6 ? 'is-weekend ' : '') . ($date < $today ? 'is-past ' : '') . ($date === $today ? 'is-today' : '')) ?>">
                  <a class="cal-daylabel" href="/termine/neu?datum=<?= e($date) ?>" title="Termin am <?= e(date('d.m.Y', strtotime($date))) ?> eintragen"><?= e($day) ?> <?= e(Kalender::WOCHENTAGE[$wd - 1]) ?></a>
                  <?php foreach ($tage[$date] ?? [] as $e) { echo $entry($e, Kalender::vorbei($date, $e, $today, $now)); } ?>
                </td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
