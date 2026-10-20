<?php
/** @var list<array> $rows @var int $total */
use Backoffice\Csrf;
use Backoffice\Modules\Kunden\Kundentypen;
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1>Namen aus E-Mail übernehmen</h1>
<p class="hint">Kunden ohne Vor- und Nachnamen mit einem Vorschlag: der Name des Hauptkontakts, sonst aus der E-Mail-Adresse abgeleitet. Vorschläge kannst du hier noch korrigieren; gespeichert werden nur die angehakten Zeilen. Nur Eltern (bei Einrichtungen stehen die Namen beim Ansprechpartner). Namen, die inzwischen jemand eingetragen hat, werden nie überschrieben.</p>

<?php if (!$rows): ?>
  <p class="empty">Keine Kunden ohne Namen mit einem Vorschlag.</p>
<?php else: ?>
  <?php if ($total > count($rows)): ?>
    <p class="hint">Es werden die ersten <?= e(count($rows)) ?> von <?= e($total) ?> angezeigt – nach dem Speichern erscheinen die nächsten.</p>
  <?php endif; ?>
  <form method="post" action="/kunden/namen" class="names-form">
    <?= Csrf::field() ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th scope="col">Übernehmen</th><th scope="col">Kunde</th><th scope="col">Typ</th><th scope="col">E-Mail</th><th scope="col">Vorschlag aus</th><th scope="col">Vorname</th><th scope="col">Nachname</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $id = (int) $r['id']; $v = $r['vorschlag']; ?>
          <tr>
            <td><input type="checkbox" name="uebernehmen[<?= e($id) ?>]" value="1"<?= Kundentypen::isPrivat($r['typ']) ? ' checked' : '' ?> aria-label="Namen für <?= e(Kundentypen::displayName($r)) ?> übernehmen"></td>
            <td><a href="/kunden/ansehen?id=<?= e($id) ?>"><?= e(Kundentypen::displayName($r)) ?></a></td>
            <td><?= e(Kundentypen::label($r['typ'])) ?></td>
            <td><?= e($r['mail_adresse']) ?></td>
            <td><?= e($v['herkunft'] === 'dem Hauptkontakt' ? 'Hauptkontakt' : 'E-Mail') ?></td>
            <td><input name="namen[<?= e($id) ?>][vorname]" value="<?= e($v['vorname'] ?? '') ?>" maxlength="100" aria-label="Vorname"></td>
            <td><input name="namen[<?= e($id) ?>][nachname]" value="<?= e($v['nachname'] ?? '') ?>" maxlength="100" aria-label="Nachname"></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="form-actions">
      <button type="submit" class="btn">Angehakte Namen speichern</button>
      <a href="/kunden">Abbrechen</a>
    </div>
  </form>
<?php endif; ?>
