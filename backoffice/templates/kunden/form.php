<?php
/** @var ?int $id @var array $kunde @var array $contacts @var array $kinder @var array $consents @var array $errors */
use Backoffice\Csrf;
use Backoffice\Modules\Kunden\Kundentypen;

$typ = $kunde['typ'] ?? 'privat';
$privat = Kundentypen::isPrivat($typ);
$action = $id === null ? '/kunden/neu' : '/kunden/bearbeiten?id=' . $id;
$heading = $id === null ? 'Neuer Kunde: ' . Kundentypen::label($typ) : 'Bearbeiten: ' . Kundentypen::displayName($kunde);
$hasChildData = (bool) array_filter($kinder, static fn ($k) => !empty($k['vorname']) || !empty($k['id']));

$field = static function (string $name, string $label, string $type = 'text', array $opts = []) use ($kunde, $errors): string {
    $err = $errors[$name] ?? null;
    $value = $kunde[$name] ?? '';
    $attrs = ' id="f-' . e($name) . '" name="' . e($name) . '"' . ($err ? ' aria-invalid="true" aria-describedby="err-' . e($name) . '"' : '')
        . (isset($opts['autocomplete']) ? ' autocomplete="' . e($opts['autocomplete']) . '"' : '')
        . (isset($opts['placeholder']) ? ' placeholder="' . e($opts['placeholder']) . '"' : '');
    $input = $type === 'textarea'
        ? '<textarea' . $attrs . ' rows="4">' . e($value) . '</textarea>'
        : '<input' . $attrs . ' type="' . e($type) . '" value="' . e($value) . '">';
    return '<div class="field' . ($err ? ' has-error' : '') . '"><label for="f-' . e($name) . '">' . e($label) . '</label>' . $input
        . ($err ? '<p class="field-error" id="err-' . e($name) . '">' . e($err) . '</p>' : '') . '</div>';
};
$sub = static fn (string $group, int $i, string $name): string => $group . '[' . $i . '][' . $name . ']';
?>
<p class="breadcrumb"><a href="<?= $id === null ? '/kunden' : '/kunden/ansehen?id=' . e($id) ?>">← Zurück</a></p>
<h1><?= e($heading) ?></h1>

<?php if ($errors): ?>
  <p class="flash flash-error" role="alert">Bitte prüfe die markierten Felder.</p>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="card form-grid" novalidate>
  <?= Csrf::field() ?>

  <fieldset>
    <legend>Kundentyp</legend>
    <div class="inline-row">
      <div class="field">
        <label for="f-typ" class="visually-hidden">Kundentyp</label>
        <select id="f-typ" name="typ">
          <?php foreach (Kundentypen::ALLE as $key => [$label]): ?>
            <option value="<?= e($key) ?>"<?= $key === $typ ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="_typ_wechseln" value="1" class="btn btn-ghost-dark">Typ übernehmen</button>
    </div>
    <p class="hint">Nach einem Wechsel „Typ übernehmen“ klicken – das Formular passt sich an, gespeichert wird erst mit „Speichern“.</p>
  </fieldset>

  <fieldset>
    <legend><?= $privat ? 'Familie' : 'Einrichtung' ?></legend>
    <?php if (!$privat): ?>
      <?= $field('name', 'Name der Einrichtung', 'text', ['placeholder' => 'z. B. Grundschule an der Isar']) ?>
    <?php endif; ?>
    <div class="grid-2">
      <?= $field('email', $privat ? 'E-Mail' : 'E-Mail (allgemein)', 'email', ['autocomplete' => 'off']) ?>
      <?= $field('telefon', 'Telefon', 'tel') ?>
    </div>
    <?= $field('adresse', 'Straße und Hausnummer') ?>
    <div class="grid-plz">
      <?= $field('plz', 'PLZ') ?>
      <?= $field('ort', 'Ort') ?>
    </div>
    <?= $field('quelle', 'Quelle', 'text', ['placeholder' => 'z. B. Empfehlung, Website, Schulfest']) ?>
  </fieldset>

  <fieldset>
    <legend><?= $privat ? 'Eltern' : 'Ansprechpartner' ?></legend>
    <?php if (!empty($errors['ansprechpartner'])): ?><p class="field-error"><?= e($errors['ansprechpartner']) ?></p><?php endif; ?>
    <p class="hint">Zum Entfernen alle Felder einer Zeile leeren. „Haupt“ ist der Kontakt, der in Listen und auf Rechnungen erscheint.</p>
    <?php foreach ($contacts as $i => $c): ?>
      <div class="repeat-row<?= !empty($c['_error']) ? ' has-error' : '' ?>">
        <?php if (!empty($c['id'])): ?><input type="hidden" name="<?= e($sub('ansprechpartner', $i, 'id')) ?>" value="<?= e($c['id']) ?>"><?php endif; ?>
        <div class="grid-contact">
          <div class="field"><label for="a-<?= e($i) ?>-v">Vorname</label><input id="a-<?= e($i) ?>-v" name="<?= e($sub('ansprechpartner', $i, 'vorname')) ?>" value="<?= e($c['vorname'] ?? '') ?>"></div>
          <div class="field"><label for="a-<?= e($i) ?>-n">Nachname</label><input id="a-<?= e($i) ?>-n" name="<?= e($sub('ansprechpartner', $i, 'nachname')) ?>" value="<?= e($c['nachname'] ?? '') ?>"></div>
          <div class="field"><label for="a-<?= e($i) ?>-r">Rolle</label><input id="a-<?= e($i) ?>-r" name="<?= e($sub('ansprechpartner', $i, 'rolle')) ?>" value="<?= e($c['rolle'] ?? '') ?>" placeholder="<?= $privat ? 'z. B. Mutter' : 'z. B. Schulleitung' ?>"></div>
          <div class="field"><label for="a-<?= e($i) ?>-e">E-Mail</label><input id="a-<?= e($i) ?>-e" type="email" name="<?= e($sub('ansprechpartner', $i, 'email')) ?>" value="<?= e($c['email'] ?? '') ?>"></div>
          <div class="field"><label for="a-<?= e($i) ?>-t">Telefon</label><input id="a-<?= e($i) ?>-t" type="tel" name="<?= e($sub('ansprechpartner', $i, 'telefon')) ?>" value="<?= e($c['telefon'] ?? '') ?>"></div>
          <label class="check main-check"><input type="radio" name="hauptkontakt" value="<?= e($i) ?>"<?= !empty($c['ist_hauptkontakt']) ? ' checked' : '' ?>> Haupt</label>
        </div>
        <?php if (!empty($c['_error'])): ?><p class="field-error"><?= e($c['_error']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </fieldset>

  <?php if ($privat || $hasChildData): ?>
    <fieldset>
      <legend>Kinder</legend>
      <?php if (!empty($errors['kinder'])): ?><p class="field-error"><?= e($errors['kinder']) ?></p><?php endif; ?>
      <p class="hint">Geburtsdatum oder – wenn unbekannt – nur das Geburtsjahr. Für mehr Kinder speichern und erneut bearbeiten. Zum Entfernen alle Felder leeren.</p>
      <?php foreach ($kinder as $i => $k): ?>
        <div class="repeat-row<?= !empty($k['_error']) ? ' has-error' : '' ?>">
          <?php if (!empty($k['id'])): ?><input type="hidden" name="<?= e($sub('kinder', $i, 'id')) ?>" value="<?= e($k['id']) ?>"><?php endif; ?>
          <div class="grid-child">
            <div class="field"><label for="k-<?= e($i) ?>-v">Vorname</label><input id="k-<?= e($i) ?>-v" name="<?= e($sub('kinder', $i, 'vorname')) ?>" value="<?= e($k['vorname'] ?? '') ?>"></div>
            <div class="field"><label for="k-<?= e($i) ?>-n">Nachname</label><input id="k-<?= e($i) ?>-n" name="<?= e($sub('kinder', $i, 'nachname')) ?>" value="<?= e($k['nachname'] ?? '') ?>"></div>
            <div class="field"><label for="k-<?= e($i) ?>-d">Geburtsdatum</label><input id="k-<?= e($i) ?>-d" type="date" name="<?= e($sub('kinder', $i, 'geburtsdatum')) ?>" value="<?= e($k['geburtsdatum'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>"></div>
            <div class="field"><label for="k-<?= e($i) ?>-j">oder Jahr</label><input id="k-<?= e($i) ?>-j" inputmode="numeric" name="<?= e($sub('kinder', $i, 'geburtsjahr')) ?>" value="<?= e($k['geburtsjahr'] ?? '') ?>" placeholder="2017"></div>
          </div>
          <div class="grid-2">
            <div class="field"><label for="k-<?= e($i) ?>-a">Abholberechtigte</label><textarea id="k-<?= e($i) ?>-a" rows="2" name="<?= e($sub('kinder', $i, 'abholberechtigte')) ?>"><?= e($k['abholberechtigte'] ?? '') ?></textarea></div>
            <div class="field"><label for="k-<?= e($i) ?>-x">Notizen</label><textarea id="k-<?= e($i) ?>-x" rows="2" name="<?= e($sub('kinder', $i, 'notizen')) ?>"><?= e($k['notizen'] ?? '') ?></textarea></div>
          </div>
          <?php if (!empty($k['_error'])): ?><p class="field-error"><?= e($k['_error']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </fieldset>
  <?php endif; ?>

  <fieldset>
    <legend>Einwilligungen</legend>
    <p class="hint">Datum der Einwilligung und ggf. des Widerrufs. Leer = keine Einwilligung.</p>
    <table class="consent-table">
      <thead><tr><th scope="col">Art</th><th scope="col">Erteilt am</th><th scope="col">Widerrufen am</th></tr></thead>
      <tbody>
        <?php foreach (Kundentypen::EINWILLIGUNGEN as $art => $label): $c = $consents[$art] ?? []; ?>
          <tr>
            <th scope="row"><?= e($label) ?></th>
            <td><input type="date" aria-label="<?= e($label) ?> erteilt am" name="einwilligungen[<?= e($art) ?>][erteilt_am]" value="<?= e($c['erteilt_am'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>"></td>
            <td><input type="date" aria-label="<?= e($label) ?> widerrufen am" name="einwilligungen[<?= e($art) ?>][widerrufen_am]" value="<?= e($c['widerrufen_am'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>"></td>
          </tr>
          <?php if (!empty($errors["einwilligungen.$art"])): ?><tr><td colspan="3"><p class="field-error"><?= e($errors["einwilligungen.$art"]) ?></p></td></tr><?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </fieldset>

  <fieldset>
    <legend>Notizen</legend>
    <?= $field('notizen', 'Interne Notizen', 'textarea') ?>
  </fieldset>

  <div class="form-actions">
    <button type="submit" class="btn">Speichern</button>
    <a href="<?= $id === null ? '/kunden' : '/kunden/ansehen?id=' . e($id) ?>">Abbrechen</a>
  </div>
</form>
