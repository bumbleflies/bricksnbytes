<?php
/** @var array $rows @var int $total @var string $q @var string $sort @var string $dir @var int $page @var int $pages */
use Backoffice\Modules\Kinder\Alter;

$url = static function (array $changes) use ($q, $sort, $dir, $page): string {
    $params = array_merge(['q' => $q, 'sort' => $sort, 'dir' => $dir, 'seite' => $page], $changes);
    return '/kinder?' . http_build_query(array_filter($params, static fn ($v) => $v !== '' && $v !== null));
};
$columns = ['name' => 'Name', 'alter' => 'Alter', 'eltern' => 'Eltern', 'ort' => 'Ort'];
?>
<div class="page-head">
  <h1>Kinder <span class="count"><?= e($total) ?></span></h1>
  <div class="actions">
    <a class="btn btn-secondary" href="/kinder/export<?= $q !== '' ? '?q=' . e(rawurlencode($q)) : '' ?>">CSV exportieren</a>
  </div>
</div>
<p class="hint">Kinder werden bei der jeweiligen Familie angelegt und bearbeitet.</p>

<form class="search" method="get" action="/kinder" role="search">
  <label for="q" class="visually-hidden">Suche</label>
  <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name des Kindes, der Eltern oder Ort">
  <button type="submit" class="btn btn-secondary">Suchen</button>
  <?php if ($q !== ''): ?><a href="/kinder">Zurücksetzen</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <p class="empty"><?= $q !== '' ? 'Keine Kinder gefunden.' : 'Noch keine Kinder angelegt.' ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr>
        <?php foreach ($columns as $key => $label): $active = $sort === $key; ?>
          <th scope="col"<?= $active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
            <a href="<?= e($url(['sort' => $key, 'dir' => $active && $dir === 'asc' ? 'desc' : 'asc', 'seite' => 1])) ?>"><?= e($label) ?><?php if ($active): ?> <span aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span><?php endif; ?></a>
          </th>
        <?php endforeach; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="/kunden/ansehen?id=<?= e($r['kunde_id']) ?>"><?= e(trim($r['vorname'] . ' ' . ($r['nachname'] ?? ''))) ?></a></td>
            <td><?= e(Alter::text($r['geburtsdatum'], $r['geburtsjahr'])) ?></td>
            <?php $eltern = trim(($r['hk_vorname'] ?? '') . ' ' . ($r['hk_nachname'] ?? '')); ?>
            <td><?= e($eltern !== '' ? $eltern : ($r['kunde_email'] ?? '–')) ?></td>
            <td><?= e($r['ort']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Seiten">
      <?php if ($page > 1): ?><a href="<?= e($url(['seite' => $page - 1])) ?>">‹ Zurück</a><?php endif; ?>
      <span>Seite <?= e($page) ?> von <?= e($pages) ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($url(['seite' => $page + 1])) ?>">Weiter ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
