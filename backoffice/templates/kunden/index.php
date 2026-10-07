<?php
/** @var array $rows @var int $total @var array $counts @var string $q @var ?string $typ @var string $sort @var string $dir @var int $page @var int $pages */
use Backoffice\Modules\Kunden\Kundentypen;

$url = static function (array $changes) use ($q, $typ, $sort, $dir, $page): string {
    $params = array_filter(['typ' => $typ, 'q' => $q, 'sort' => $sort, 'dir' => $dir, 'seite' => $page], static fn ($v) => $v !== '' && $v !== null);
    return '/kunden?' . http_build_query(array_filter(array_merge($params, $changes), static fn ($v) => $v !== null && $v !== ''));
};

// Columns depend on the filter: families, organisations, or mixed
if ($typ === 'privat') {
    $columns = ['vorname' => 'Vorname', 'nachname' => 'Nachname', 'email' => 'E-Mail', 'telefon' => 'Telefon',
        'adresse' => 'Adresse', 'plz' => 'PLZ', 'ort' => 'Ort', 'kinder' => 'Kinder'];
} elseif ($typ !== null) {
    $columns = ['name' => 'Name', 'typ' => 'Typ', 'ansprechpartner' => 'Ansprechpartner', 'ort' => 'Ort'];
} else {
    $columns = ['name' => 'Name', 'typ' => 'Typ', 'email' => 'E-Mail', 'telefon' => 'Telefon', 'ort' => 'Ort', 'kinder' => 'Kinder / Ansprechpartner'];
}
$exportQuery = http_build_query(array_filter(['typ' => $typ, 'q' => $q]));
$all = array_sum($counts);
?>
<div class="page-head">
  <h1>Kunden <span class="count"><?= e($total) ?></span></h1>
  <div class="actions">
    <a class="btn btn-secondary" href="/kunden/export<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>">CSV exportieren</a>
    <a class="btn btn-secondary" href="/kunden/import">CSV importieren</a>
    <a class="btn" href="/kunden/neu">Neuer Kunde</a>
  </div>
</div>

<nav class="tabs" aria-label="Kundentyp">
  <a href="<?= e($url(['typ' => null, 'seite' => null, 'sort' => null])) ?>"<?= $typ === null ? ' aria-current="page"' : '' ?>>Alle <span><?= e($all) ?></span></a>
  <?php foreach (Kundentypen::ALLE as $key => [$singular, $plural]): ?>
    <a href="<?= e($url(['typ' => $key, 'seite' => null, 'sort' => null])) ?>"<?= $typ === $key ? ' aria-current="page"' : '' ?>><?= e($plural) ?> <span><?= e($counts[$key] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="search" method="get" action="/kunden" role="search">
  <?php if ($typ !== null): ?><input type="hidden" name="typ" value="<?= e($typ) ?>"><?php endif; ?>
  <label for="q" class="visually-hidden">Suche</label>
  <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name, E-Mail, Ort, Ansprechpartner oder Kind">
  <button type="submit" class="btn btn-secondary">Suchen</button>
  <?php if ($q !== ''): ?><a href="<?= e($url(['q' => null, 'seite' => null])) ?>">Suche zurücksetzen</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
  <p class="empty"><?= $q !== '' ? 'Keine Kunden gefunden.' : 'Noch keine Kunden in dieser Ansicht.' ?></p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <?php foreach ($columns as $key => $label):
              $active = $sort === $key;
              $nextDir = $active && $dir === 'asc' ? 'desc' : 'asc'; ?>
            <th scope="col"<?= $active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
              <a href="<?= e($url(['sort' => $key, 'dir' => $nextDir, 'seite' => 1])) ?>"><?= e($label) ?><?php if ($active): ?> <span aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span><?php endif; ?></a>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
            $link = '/kunden/ansehen?id=' . $r['id'];
            $contact = trim(($r['hk_vorname'] ?? '') . ' ' . ($r['hk_nachname'] ?? '')); ?>
          <tr>
            <?php foreach (array_keys($columns) as $i => $key): ?>
              <td>
                <?php
                $value = match ($key) {
                    'name' => Kundentypen::displayName($r),
                    'vorname' => $r['hk_vorname'],
                    'nachname' => $r['hk_nachname'],
                    'typ' => Kundentypen::label($r['typ']),
                    'ansprechpartner' => $contact . ($r['hk_rolle'] ? ' (' . $r['hk_rolle'] . ')' : ''),
                    'kinder' => Kundentypen::isPrivat($r['typ']) || $typ === 'privat' ? $r['kinder_namen'] : $contact,
                    default => $r[$key],
                };
                ?>
                <?php if ($i === 0): ?>
                  <a href="<?= e($link) ?>"><?= e($value !== null && $value !== '' ? $value : '–') ?></a>
                <?php elseif ($key === 'email' && $value): ?>
                  <a href="mailto:<?= e($value) ?>"><?= e($value) ?></a>
                <?php else: ?>
                  <?= e($value) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
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
