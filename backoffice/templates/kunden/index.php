<?php
/** @var array $rows @var int $total @var array $typeOptions @var array $places @var string $q @var ?string $typ @var string $sort @var string $dir @var int $page @var int $pages
 *  @var ?string $newsletter @var ?string $status @var ?string $ort @var list<string> $filteredAddresses @var list<string> $newsletterAddresses @var ?string $ownEmail */
use Backoffice\Csrf;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\StatusBadge;
use Backoffice\Modules\Kunden\Verteiler;

// Search and dropdown filters of the list (also kept by sort links, paging, export and bulk actions)
$filter = array_filter(['typ' => $typ, 'newsletter' => $newsletter, 'ort' => $ort, 'status' => $status, 'q' => $q], static fn ($v) => $v !== '' && $v !== null);
$url = static function (array $changes) use ($filter, $sort, $dir, $page): string {
    $params = $filter + ['sort' => $sort, 'dir' => $dir, 'seite' => $page];
    return '/kunden?' . http_build_query(array_filter(array_merge($params, $changes), static fn ($v) => $v !== null && $v !== ''));
};

// Columns depend on the filter: families, organisations, or mixed. Telefon and Ort are not listed
// (detail page and form); the Ort filter and the search still use them.
if ($typ === 'privat') {
    $columns = ['vorname' => 'Vorname', 'nachname' => 'Nachname', 'email' => 'E-Mail', 'newsletter' => 'Newsletter', 'status' => 'Status',
        'adresse' => 'Adresse', 'plz' => 'PLZ', 'kinder' => 'Kinder'];
} elseif ($typ !== null) {
    $columns = ['name' => 'Name', 'newsletter' => 'Newsletter', 'status' => 'Status', 'typ' => 'Typ', 'ansprechpartner' => 'Ansprechpartner'];
} else {
    $columns = ['name' => 'Name', 'typ' => 'Typ', 'status' => 'Status', 'email' => 'E-Mail', 'newsletter' => 'Newsletter', 'kinder' => 'Kinder / Ansprechpartner'];
}
// Longer text columns wrap instead of widening the table (the actions column stays visible)
$wrapColumns = ['kinder', 'ansprechpartner', 'adresse'];
$here = $url([]);
$exportQuery = http_build_query($filter);
$all = array_sum(array_column($typeOptions, 1));
// The place of the current filter stays selectable even if no customer has it any more
$places += $ort !== null && !isset($places[$ort]) ? [$ort => 0] : [];
?>
<div class="page-head">
  <h1>Kunden <span class="count"><?= e($total) ?></span></h1>
  <div class="actions">
    <a class="btn btn-secondary" href="/kunden/export<?= $exportQuery !== '' ? '?' . e($exportQuery) : '' ?>">CSV exportieren</a>
    <a class="btn btn-secondary" href="/kunden/import">Newsletter-Liste importieren</a>
    <a class="btn btn-secondary" href="/kunden/import/kontakte">Kontakte importieren</a>
    <a class="btn btn-secondary" href="/kunden/importe">Importe</a>
    <a class="btn" href="/kunden/neu">Neuer Kunde</a>
  </div>
</div>

<form class="filters" method="get" action="/kunden" role="search" aria-label="Kunden suchen und filtern">
  <?php if (isset($_GET['sort'])): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><input type="hidden" name="dir" value="<?= e($dir) ?>"><?php endif; ?>
  <div class="filter filter-search">
    <label for="q">Suche</label>
    <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name, E-Mail, Ort, Ansprechpartner oder Kind">
  </div>
  <div class="filter">
    <label for="filter-typ">Typ</label>
    <select id="filter-typ" name="typ" data-autosubmit>
      <option value="">Alle (<?= e($all) ?>)</option>
      <?php foreach ($typeOptions as $key => [$label, $n]): ?>
        <option value="<?= e($key) ?>"<?= $typ === $key ? ' selected' : '' ?>><?= e($label) ?> (<?= e($n) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter">
    <label for="filter-newsletter">Newsletter</label>
    <select id="filter-newsletter" name="newsletter" data-autosubmit>
      <option value="">Alle</option>
      <option value="ja"<?= $newsletter === 'ja' ? ' selected' : '' ?>>Ja</option>
      <option value="nein"<?= $newsletter === 'nein' ? ' selected' : '' ?>>Nein</option>
    </select>
  </div>
  <div class="filter">
    <label for="filter-ort">Ort</label>
    <select id="filter-ort" name="ort" data-autosubmit>
      <option value="">Alle</option>
      <?php foreach ($places as $place => $n): ?>
        <option value="<?= e($place) ?>"<?= $ort === (string) $place ? ' selected' : '' ?>><?= e($place) ?> (<?= e($n) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="filter">
    <label for="filter-status">Status</label>
    <select id="filter-status" name="status" data-autosubmit>
      <option value="">Alle</option>
      <?php foreach (Kundentypen::STATUS as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
      <option value="ohne"<?= $status === 'ohne' ? ' selected' : '' ?>>Ohne Status</option>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-secondary">Suchen</button>
    <?php if ($filter): ?><a href="/kunden">Zurücksetzen</a><?php endif; ?>
  </div>
</form>

<?php if (!$rows): ?>
  <p class="empty"><?= $filter ? 'Keine Kunden gefunden – Suche oder Filter anpassen.' : 'Noch keine Kunden.' ?></p>
<?php endif; ?>

<div class="mail-tools" id="mail-tools"
     data-own-email="<?= e($ownEmail ?? '') ?>"
     data-filtered="<?= e(json_encode($filteredAddresses, JSON_UNESCAPED_UNICODE)) ?>"
     data-newsletter="<?= e(json_encode($newsletterAddresses, JSON_UNESCAPED_UNICODE)) ?>"
     data-total="<?= e($total) ?>">
  <div class="mail-tools-row">
    <span class="sel-count" id="sel-count" aria-live="polite">0 ausgewählt</span>
    <button type="button" class="btn-link" id="select-filtered" hidden>Alle <?= e($total) ?> gefilterten Kunden auswählen</button>
    <span class="mail-tools-spacer"></span>
    <button type="button" class="btn" id="mail-selection" disabled>E-Mail an Auswahl</button>
    <button type="button" class="btn-link" id="copy-selection" disabled>Adressen kopieren</button>
    <button type="button" class="btn btn-secondary" id="mail-newsletter"<?= $newsletterAddresses ? '' : ' disabled' ?>>Newsletter verschicken (<?= e(count($newsletterAddresses)) ?>)</button>
    <button type="button" class="btn-link" id="copy-newsletter"<?= $newsletterAddresses ? '' : ' disabled' ?>>Adressen kopieren</button>
  </div>
  <form class="mail-tools-row bulk-type" method="post" action="/kunden/typ-auswahl" id="bulk-type">
    <?= Csrf::field() ?>
    <input type="hidden" name="zurueck" value="<?= e($here) ?>">
    <input type="hidden" name="ids" value="" id="bulk-ids">
    <input type="hidden" name="alle" value="" id="bulk-all">
    <?php foreach ($filter as $name => $value): ?>
      <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
    <?php endforeach; ?>
    <label for="bulk-typ">Typ für Auswahl ändern</label>
    <select id="bulk-typ" name="neuer_typ">
      <?php foreach (Kundentypen::ALLE as $key => [$label]): ?>
        <option value="<?= e($key) ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary" id="bulk-type-submit" disabled>Ändern</button>
  </form>
  <?php if ($ownEmail === null): ?>
    <p class="hint">Die Mail-Buttons brauchen deine eigene Adresse für das „An“-Feld. Sie fehlt noch (Einstellung <code>eigene_email</code> bzw. <code>app.eigene_email</code>/<code>APP_OWNER_EMAIL</code> in der Konfiguration). „Adressen kopieren“ funktioniert trotzdem.</p>
  <?php endif; ?>
  <p class="mail-notice" id="mail-notice" role="status" hidden></p>
</div>

<?php if ($rows): ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th scope="col" class="col-select"><input type="checkbox" id="select-page" aria-label="Alle Kunden auf dieser Seite auswählen"></th>
          <?php foreach ($columns as $key => $label):
              $active = $sort === $key;
              $nextDir = $active && $dir === 'asc' ? 'desc' : 'asc'; ?>
            <th scope="col"<?= in_array($key, $wrapColumns, true) ? ' class="col-wrap"' : '' ?><?= $active ? ' aria-sort="' . ($dir === 'asc' ? 'ascending' : 'descending') . '"' : '' ?>>
              <a href="<?= e($url(['sort' => $key, 'dir' => $nextDir, 'seite' => 1])) ?>"><?= e($label) ?><?php if ($active): ?> <span aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span><?php endif; ?></a>
            </th>
          <?php endforeach; ?>
          <th scope="col" class="col-actions">Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
            $link = '/kunden/ansehen?id=' . $r['id'];
            $contact = trim(($r['hk_vorname'] ?? '') . ' ' . ($r['hk_nachname'] ?? ''));
            $address = Verteiler::one($r['mail_adresse']);
            $nlOn = $r['newsletter_seit'] !== null;
            $display = Kundentypen::displayName($r); ?>
          <tr>
            <td class="col-select">
              <input type="checkbox" class="row-select" value="<?= e($r['id']) ?>" data-email="<?= e($address) ?>"
                     aria-label="<?= e($display) ?> auswählen<?= $address === '' ? ' (keine gültige E-Mail)' : '' ?>">
            </td>
            <?php foreach (array_keys($columns) as $i => $key): ?>
              <td<?= in_array($key, $wrapColumns, true) ? ' class="col-wrap"' : '' ?>>
                <?php
                $value = match ($key) {
                    'name' => Kundentypen::displayName($r),
                    'vorname' => $r['hk_vorname'],
                    'nachname' => $r['hk_nachname'],
                    'typ' => Kundentypen::label($r['typ']),
                    'status' => Kundentypen::statusLabel($r['status']),
                    'ansprechpartner' => $contact . ($r['hk_rolle'] ? ' (' . $r['hk_rolle'] . ')' : ''),
                    'kinder' => Kundentypen::isPrivat($r['typ']) || $typ === 'privat' ? $r['kinder_namen'] : $contact,
                    'newsletter' => null,
                    default => $r[$key],
                };
                ?>
                <?php if ($key === 'newsletter'): ?>
                  <form method="post" action="/kunden/newsletter" class="nl-toggle">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                    <input type="hidden" name="wert" value="<?= $nlOn ? 'nein' : 'ja' ?>">
                    <input type="hidden" name="zurueck" value="<?= e($here) ?>">
                    <button type="submit" class="switch" role="switch" aria-checked="<?= $nlOn ? 'true' : 'false' ?>"
                            aria-label="Newsletter für <?= e($display) ?>"
                            title="<?= $nlOn ? 'Einwilligung am ' . e(KundenController::germanDate($r['newsletter_seit'])) . ' – klicken für Nein' : 'Klicken für Ja (Einwilligung mit heutigem Datum)' ?>">
                      <span class="switch-track" aria-hidden="true"></span><span class="switch-label"><?= $nlOn ? 'Ja' : 'Nein' ?></span>
                    </button>
                  </form>
                <?php elseif ($i === 0): ?>
                  <a href="<?= e($link) ?>"><?= e($value !== null && $value !== '' ? $value : '–') ?></a>
                <?php elseif ($key === 'typ'): ?>
                  <form method="post" action="/kunden/typ" class="type-change" data-stop-row>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                    <input type="hidden" name="zurueck" value="<?= e($here) ?>">
                    <label class="visually-hidden" for="typ-<?= e($r['id']) ?>">Typ von <?= e($display) ?></label>
                    <select id="typ-<?= e($r['id']) ?>" name="typ" data-autosubmit>
                      <?php foreach (Kundentypen::ALLE as $tk => [$tl]): ?>
                        <option value="<?= e($tk) ?>"<?= $tk === $r['typ'] ? ' selected' : '' ?>><?= e($tl) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="btn-link">Ändern</button></noscript>
                  </form>
                <?php elseif ($key === 'status'): ?>
                  <?= StatusBadge::html($r['status']) ?>
                <?php elseif ($key === 'email' && $value): ?>
                  <a href="mailto:<?= e($value) ?>"><?= e($value) ?></a>
                <?php else: ?>
                  <?= e($value) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="col-actions">
              <a class="icon-btn" href="/kunden/bearbeiten?id=<?= e($r['id']) ?>&amp;zurueck=<?= e(rawurlencode($here)) ?>"
                 title="Bearbeiten" aria-label="<?= e($display) ?> bearbeiten" data-stop-row>
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg>
              </a>
              <button type="button" class="icon-btn quick-open" data-id="<?= e($r['id']) ?>"
                      title="Schnellansicht" aria-label="Schnellansicht für <?= e($display) ?>" aria-haspopup="dialog" data-stop-row>
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              </button>
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

<dialog class="quick-dialog" id="quick-view" aria-labelledby="quick-title" data-list-url="<?= e($here) ?>">
  <div class="quick-dialog-inner">
    <form method="dialog" class="quick-close-form">
      <button type="submit" class="quick-close" aria-label="Schließen" title="Schließen">×</button>
    </form>
    <div class="quick-body" id="quick-body" aria-live="polite"></div>
    <div class="quick-actions">
      <a class="btn" id="quick-edit" href="#">Bearbeiten</a>
      <a class="btn btn-secondary" id="quick-detail" href="#">Zur Detailseite</a>
    </div>
  </div>
</dialog>

<script src="/assets/kunden.js" defer></script>
<script src="/assets/kunden-schnellansicht.js" defer></script>
