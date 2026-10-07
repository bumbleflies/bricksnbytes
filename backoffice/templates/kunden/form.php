<?php
/** @var ?int $id @var array $kunde @var array $kinder @var array $errors @var array $kinderErrors */
use Backoffice\Csrf;

$action = $id === null ? '/kunden/neu' : '/kunden/bearbeiten?id=' . $id;
$heading = $id === null
    ? 'Neuer Kunde'
    : (trim(($kunde['vorname'] ?? '') . ' ' . ($kunde['nachname'] ?? '')) ?: ($kunde['email'] ?? 'Kunde bearbeiten'));
$field = static function (string $name, string $label, string $type = 'text', string $autocomplete = 'off') use ($kunde, $errors): string {
    $err = $errors[$name] ?? null;
    return '<div class="field' . ($err ? ' has-error' : '') . '">'
        . '<label for="f-' . e($name) . '">' . e($label) . '</label>'
        . '<input id="f-' . e($name) . '" name="' . e($name) . '" type="' . e($type) . '" autocomplete="' . e($autocomplete) . '"'
        . ' value="' . e($kunde[$name] ?? '') . '"' . ($err ? ' aria-invalid="true" aria-describedby="err-' . e($name) . '"' : '') . '>'
        . ($err ? '<p class="field-error" id="err-' . e($name) . '">' . e($err) . '</p>' : '')
        . '</div>';
};
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1><?= e($heading) ?></h1>

<?php if ($errors): ?>
  <p class="flash flash-error" role="alert">Bitte prüfe die markierten Felder.</p>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="card form-grid" novalidate>
  <?= Csrf::field() ?>

  <fieldset>
    <legend>Kontakt</legend>
    <div class="grid-2">
      <?= $field('vorname', 'Vorname') ?>
      <?= $field('nachname', 'Nachname') ?>
      <?= $field('email', 'E-Mail', 'email') ?>
      <?= $field('telefon', 'Telefon', 'tel') ?>
    </div>
    <label class="check">
      <input type="checkbox" name="newsletter" value="1"<?= !empty($kunde['newsletter']) ? ' checked' : '' ?>>
      Für den Newsletter angemeldet
    </label>
  </fieldset>

  <fieldset>
    <legend>Adresse</legend>
    <?= $field('adresse', 'Straße und Hausnummer') ?>
    <div class="grid-plz">
      <?= $field('plz', 'PLZ') ?>
      <?= $field('ort', 'Ort') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Kinder</legend>
    <p class="hint">Zum Entfernen eines Kindes Vorname und Geburtsdatum leeren. Für mehr als zwei neue Kinder erst speichern, dann kommen weitere freie Zeilen.</p>
    <?php foreach ($kinder as $i => $kind): $err = $kinderErrors[$i] ?? null; ?>
      <div class="grid-kind<?= $err ? ' has-error' : '' ?>">
        <?php if ($kind['id']): ?><input type="hidden" name="kinder[<?= e($i) ?>][id]" value="<?= e($kind['id']) ?>"><?php endif; ?>
        <div class="field">
          <label for="k-<?= e($i) ?>-vorname">Vorname Kind <?= e($i + 1) ?></label>
          <input id="k-<?= e($i) ?>-vorname" name="kinder[<?= e($i) ?>][vorname]" type="text" value="<?= e($kind['vorname']) ?>">
        </div>
        <div class="field">
          <label for="k-<?= e($i) ?>-geburt">Geburtsdatum</label>
          <input id="k-<?= e($i) ?>-geburt" name="kinder[<?= e($i) ?>][geburtsdatum]" type="date" value="<?= e($kind['geburtsdatum']) ?>" max="<?= e(date('Y-m-d')) ?>">
        </div>
        <?php if ($err): ?><p class="field-error"><?= e($err) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn">Speichern</button>
    <a href="/kunden">Abbrechen</a>
    <?php if ($id !== null): ?>
      <a class="danger push-right" href="/kunden/loeschen?id=<?= e($id) ?>">Kunde löschen</a>
    <?php endif; ?>
  </div>
  <?php if ($id !== null): ?>
    <p class="meta">Angelegt am <?= e(date('d.m.Y H:i', strtotime($kunde['erstellt_am']))) ?> · zuletzt geändert am <?= e(date('d.m.Y H:i', strtotime($kunde['geaendert_am']))) ?></p>
  <?php endif; ?>
</form>
