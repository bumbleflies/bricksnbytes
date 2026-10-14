<?php
/** @var list<array> $runs */
use Backoffice\Modules\Import\ImportUndo;
use Backoffice\Modules\Kunden\KundenController;
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1>Importe</h1>
<p class="hint">Alle Importe, aus denen noch Kunden vorhanden sind – der neueste zuerst. „Rückgängig machen“ löscht nur Kunden, die seit dem Import <strong>nicht verändert</strong> wurden; bearbeitete Kunden, ergänzte Kinder oder Ansprechpartner und Kunden mit Buchungen, Aufträgen oder Rechnungen bleiben erhalten.</p>

<?php if (!$runs): ?>
  <p class="empty">Noch keine importierten Kunden.</p>
<?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Zeitpunkt</th><th>Art</th><th>Quelle</th><th>Kunden</th><th>davon unverändert</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($runs as $run): $unchanged = count($run['pruefung']['unchanged']); ?>
        <tr>
          <td><?= e(KundenController::germanDate($run['von'])) ?><?= $run['bis'] !== $run['von'] ? ' – ' . e(substr(KundenController::germanDate($run['bis']), -5)) : '' ?></td>
          <td><?= e(ImportUndo::label($run['quelle'])) ?></td>
          <td><?= e($run['quelle'] ?? '–') ?></td>
          <td><?= e(count($run['ids'])) ?></td>
          <td><?= e($unchanged) ?></td>
          <td><?php if ($unchanged > 0): ?><a href="/kunden/importe/rueckgaengig?lauf=<?= e($run['id']) ?>">Rückgängig machen …</a><?php else: ?><span class="muted">–</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
