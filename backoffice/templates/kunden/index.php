<?php
/** @var array $rows @var int $total @var string $q @var string $sort @var string $dir @var int $page @var int $pages */

$url = static function (array $changes) use ($q, $sort, $dir, $page): string {
    $params = array_filter(['q' => $q, 'sort' => $sort, 'dir' => $dir, 'seite' => $page] + [], static fn ($v) => $v !== '' && $v !== null);
    return '/kunden?' . http_build_query(array_merge($params, $changes));
};
$columns = [
    'vorname' => 'Vorname', 'nachname' => 'Nachname', 'email' => 'E-Mail', 'telefon' => 'Telefon',
    'adresse' => 'Adresse', 'plz' => 'PLZ', 'ort' => 'Ort', 'kind1' => 'Kind 1', 'kind2' => 'Kind 2',
];
?>
<div class="page-head">
  <h1>Kunden <span class="count"><?= e($total) ?></span></h1>
  <div class="actions">
    <a class="btn btn-secondary" href="/kunden/export<?= $q !== '' ? '?q=' . e(rawurlencode($q)) : '' ?>">CSV exportieren</a>
    <a class="btn" href="/kunden/neu">Neuer Kunde</a>
  </div>
</div>

<form class="search" method="get" action="/kunden" role="search">
  <label for="q" class="visually-hidden">Suche</label>
  <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name, E-Mail oder Ort">
  <input type="hidden" name="sort" value="<?= e($sort) ?>">
  <input type="hidden" name="dir" value="<?= e($dir) ?>">
  <button type="submit" class="btn btn-secondary">Suchen</button>
  <?php if ($q !== ''): ?><a href="/kunden">Zurücksetzen</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <p class="empty"><?= $q !== '' ? 'Keine Kunden gefunden.' : 'Noch keine Kunden angelegt.' ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <?php foreach ($columns as $key => $label):
              $active = $sort === $key;
              $nextDir = $active && $dir === 'asc' ? 'desc' : 'asc'; ?>
            <th scope="col"<?= $active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
              <a href="<?= e($url(['sort' => $key, 'dir' => $nextDir, 'seite' => 1])) ?>">
                <?= e($label) ?><?php if ($active): ?> <span aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span><?php endif; ?>
              </a>
            </th>
          <?php endforeach; ?>
          <th scope="col"><span class="visually-hidden">Aktionen</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['vorname']) ?></td>
            <td><?= e($r['nachname']) ?></td>
            <td><?php if ($r['email']): ?><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php endif; ?></td>
            <td><?= e($r['telefon']) ?></td>
            <td><?= e($r['adresse']) ?></td>
            <td><?= e($r['plz']) ?></td>
            <td><?= e($r['ort']) ?></td>
            <td><?= e($r['kind1']) ?></td>
            <td><?= e($r['kind2']) ?><?php if ($r['kinder_anzahl'] > 2): ?> <span class="muted">+<?= e($r['kinder_anzahl'] - 2) ?></span><?php endif; ?></td>
            <td class="row-actions">
              <a href="/kunden/bearbeiten?id=<?= e($r['id']) ?>">Bearbeiten</a>
              <a class="danger" href="/kunden/loeschen?id=<?= e($r['id']) ?>">Löschen</a>
            </td>
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
