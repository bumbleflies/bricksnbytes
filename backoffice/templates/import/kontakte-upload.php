<?php
/** @var ?string $error */
use Backoffice\Csrf;
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1>Kontakte importieren</h1>
<?php if (!empty($error)): ?>
  <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<section class="card">
  <p>Für eine Kontaktliste mit einer Zeile pro Person bzw. Adresse. Die Datei wird zuerst nur <strong>geprüft</strong> – gespeichert wird erst nach deiner Bestätigung.</p>
  <ul class="hint-list">
    <li>Benötigt werden die Spalten <code>E-Mail</code> und <code>Typ</code>; genutzt werden außerdem <code>Name</code>, <code>Telefon</code>, <code>Organisation</code>, <code>Status</code>, <code>Letzter Kontakt</code>, <code>Anzahl Mails</code> und <code>Notizen</code>.</li>
    <li>Importiert wird nur, wessen E-Mail es im Backoffice noch nicht gibt (beim Kunden oder bei einem Ansprechpartner).</li>
    <li>Zeilen mit derselben <strong>Organisation</strong> werden ein Kunde mit mehreren Ansprechpartnern. Gibt es die Organisation unter genau diesem Namen schon, werden dort nur die neuen Ansprechpartner ergänzt.</li>
    <li>Typen: Eltern → Eltern (privat), Kita/Familienzentrum → Kita, Hort/Förderverein → Hort, Unternehmen/Sponsor und Hotel → Firma, Veranstalter/Partner und Raumgeber/Partner → Partner, Bibliothek → Bibliothek. Kursleiter werden nicht importiert.</li>
    <li>Der Status kommt ins Feld „Status“ (Abgelehnt und Kein Auftrag → Kein Interesse). Bei mehreren Personen einer Organisation zählt die mit dem jüngsten Kontakt.</li>
    <li>Notizen, Anzahl Mails und unbekannte Status landen in den Notizen des Kunden. Newsletter-Einwilligungen werden nicht gesetzt.</li>
  </ul>
  <form method="post" action="/kunden/import/kontakte" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>
    <label for="datei">CSV-Datei (höchstens 2 MB)</label>
    <input id="datei" name="datei" type="file" accept=".csv,text/csv" required>
    <button type="submit" class="btn">Datei prüfen</button>
  </form>
</section>
