<?php
/** @var ?string $error */
use Backoffice\Csrf;
?>
<p class="breadcrumb"><a href="/kinder">← Alle Kinder</a></p>
<h1>Kinder importieren</h1>
<?php if (!empty($error)): ?>
  <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<section class="card">
  <p>Für die Bestell-Übersicht aus pretix (eine Zeile pro Kind und Buchung). Die Datei wird zuerst nur <strong>geprüft</strong> – gespeichert wird erst nach deiner Bestätigung.</p>
  <ul class="hint-list">
    <li>Benötigt werden die Spalten <code>E-Mail</code> und <code>Name Kind</code>; genutzt werden außerdem <code>Name Eltern</code>, <code>Alter Kind</code>, <code>Bestellung</code>, <code>Bestellt am</code> und <code>Hinweis</code>. Alle anderen Spalten (z. B. Veranstaltung, Termin) werden ignoriert.</li>
    <li>Google Sheets: den Reiter „Übersicht“ öffnen → <em>Datei → Herunterladen → CSV</em>.</li>
    <li>Eltern und Kind werden über die <strong>E-Mail</strong> verbunden. Gibt es den Kunden schon, werden nur fehlende Kinder ergänzt (gleicher Vorname = schon vorhanden); sonst entsteht ein neuer Privatkunde mit Elternteil(en) und Kind(ern).</li>
    <li>Statt eines Geburtsdatums wird aus Alter und Buchungsdatum ein <strong>geschätztes Geburtsjahr</strong> gespeichert und in den Notizen des Kindes vermerkt.</li>
    <li>Newsletter-Einwilligungen werden nicht verändert – eine Buchung ist keine Einwilligung.</li>
  </ul>
  <form method="post" action="/kinder/import" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>
    <label for="datei">CSV-Datei (höchstens 2 MB)</label>
    <input id="datei" name="datei" type="file" accept=".csv,text/csv" required>
    <button type="submit" class="btn">Datei prüfen</button>
  </form>
</section>
