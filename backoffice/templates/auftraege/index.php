<?php
/** Order list. @var array $rows @var int $total @var int $page @var int $pages */
use Backoffice\Modules\Auftraege\Auftragsdaten;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\StatusBadge;

$here = '/auftraege' . ($page > 1 ? '?seite=' . $page : '');
?>
<div class="page-head">
  <h1>Aufträge <span class="count"><?= e($total) ?></span></h1>
  <div class="actions">
    <a class="btn" href="/auftraege/neu">Neuer Auftrag</a>
  </div>
</div>

<?php if (!$rows): ?>
  <p class="empty">Noch keine Aufträge. Lege den ersten über „Neuer Auftrag“ an – oder direkt beim Kunden.</p>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th scope="col" class="col-wrap">Name</th>
          <th scope="col">Typ</th>
          <th scope="col">Status</th>
          <th scope="col" class="col-wrap">Neueste Datei</th>
          <th scope="col" class="col-actions">Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
            $kunde = Kundentypen::displayName(['typ' => $r['kunde_typ'], 'name' => $r['kunde_name'], 'email' => $r['kunde_email'],
                'hk_vorname' => $r['hk_vorname'], 'hk_nachname' => $r['hk_nachname']]); ?>
          <tr>
            <td class="col-wrap">
              <a class="order-title" href="/auftraege/ansehen?id=<?= e($r['id']) ?>"><?= e($r['titel']) ?></a>
              <span class="order-customer"><?= e($kunde) ?></span>
            </td>
            <td><?= e(Auftragsdaten::typLabel($r['format'])) ?></td>
            <td><?= StatusBadge::pill(Auftragsdaten::statusLabel($r['status']), Auftragsdaten::statusFarbe($r['status'])) ?></td>
            <td class="col-wrap">
              <?php if ($r['datei_id']): ?>
                <a href="/auftraege/datei?id=<?= e($r['datei_id']) ?>" target="_blank" rel="noopener"><?= e($r['datei_name']) ?></a>
                <span class="muted file-date"><?= e(KundenController::germanDate(substr($r['datei_am'], 0, 10))) ?></span>
              <?php else: ?>
                <span class="muted">–</span>
              <?php endif; ?>
            </td>
            <td class="col-actions">
              <a class="icon-btn" href="/auftraege/bearbeiten?id=<?= e($r['id']) ?>" title="Bearbeiten" aria-label="<?= e($r['titel']) ?> bearbeiten" data-stop-row>
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg>
              </a>
              <button type="button" class="icon-btn quick-open" data-id="<?= e($r['id']) ?>"
                      title="Schnellansicht" aria-label="Schnellansicht für <?= e($r['titel']) ?>" aria-haspopup="dialog" data-stop-row>
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
      <?php if ($page > 1): ?><a href="/auftraege?seite=<?= e($page - 1) ?>">‹ Zurück</a><?php endif; ?>
      <span>Seite <?= e($page) ?> von <?= e($pages) ?></span>
      <?php if ($page < $pages): ?><a href="/auftraege?seite=<?= e($page + 1) ?>">Weiter ›</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<dialog class="quick-dialog" id="quick-view" aria-labelledby="quick-title" data-list-url="<?= e($here) ?>" data-quick-url="/auftraege/schnellansicht">
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
<script src="/assets/kunden-schnellansicht.js" defer></script>
