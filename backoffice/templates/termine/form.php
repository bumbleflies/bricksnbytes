<?php
/** Own calendar entry. @var ?int $id @var array $eintrag @var array $errors */
use Backoffice\Csrf;

$v = static fn (string $k): string => (string) ($eintrag[$k] ?? '');
$time = static fn (string $k): string => substr($v($k), 0, 5);
$err = static fn (string $k): string => isset($errors[$k]) ? '<p class="field-error" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';
$cls = static fn (string $k): string => 'field' . (isset($errors[$k]) ? ' has-error' : '');
$back = '/termine?ansicht=monat&ab=' . substr($v('datum') ?: date('Y-m-d'), 0, 7);
?>
<p class="breadcrumb"><a href="<?= e($back) ?>">← Zurück zu den Terminen</a></p>
<h1><?= $id === null ? 'Neuer Termin' : 'Termin bearbeiten' ?></h1>
<?php if ($errors): ?><p class="flash flash-error" role="alert">Bitte prüfe die markierten Felder.</p><?php endif; ?>

<form method="post" action="<?= e($id === null ? '/termine/neu' : '/termine/bearbeiten?id=' . $id) ?>" class="card form-grid" novalidate>
  <?= Csrf::field() ?>
  <div class="<?= $cls('titel') ?>">
    <label for="f-titel">Was</label>
    <input id="f-titel" name="titel" value="<?= e($v('titel')) ?>" maxlength="255" placeholder="z. B. Yoga, Elternabend, Urlaub"<?= $inv('titel') ?>>
    <?= $err('titel') ?>
  </div>
  <div class="grid-2">
    <div class="<?= $cls('datum') ?>">
      <label for="f-datum">Datum</label>
      <input id="f-datum" type="date" name="datum" value="<?= e($v('datum')) ?>"<?= $inv('datum') ?>>
      <?= $err('datum') ?>
    </div>
    <div class="<?= $cls('bis_datum') ?>">
      <label for="f-bis">bis (mehrtägig)</label>
      <input id="f-bis" type="date" name="bis_datum" value="<?= e($v('bis_datum')) ?>"<?= $inv('bis_datum') ?>>
      <?= $err('bis_datum') ?>
    </div>
  </div>
  <div class="grid-2">
    <div class="<?= $cls('beginn') ?>">
      <label for="f-beginn">Uhrzeit von</label>
      <input id="f-beginn" type="time" name="beginn" value="<?= e($time('beginn')) ?>"<?= $inv('beginn') ?>>
      <?= $err('beginn') ?>
    </div>
    <div class="<?= $cls('ende') ?>">
      <label for="f-ende">bis</label>
      <input id="f-ende" type="time" name="ende" value="<?= e($time('ende')) ?>"<?= $inv('ende') ?>>
      <?= $err('ende') ?>
    </div>
  </div>
  <p class="hint">Ohne Uhrzeit gilt der Termin für den ganzen Tag.</p>
  <div class="field">
    <label for="f-notizen">Notizen</label>
    <textarea id="f-notizen" name="notizen" rows="3" maxlength="5000"><?= e($v('notizen')) ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn">Speichern</button>
    <a href="<?= e($back) ?>">Abbrechen</a>
  </div>
</form>

<?php if ($id !== null): ?>
  <form method="post" action="/termine/loeschen" class="form-actions delete-row" data-confirm="Diesen Termin löschen?">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <button type="submit" class="btn btn-danger">Termin löschen</button>
  </form>
  <script src="/assets/auftraege.js" defer></script>
<?php endif; ?>
