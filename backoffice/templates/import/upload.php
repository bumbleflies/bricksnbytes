<?php
/** @var ?string $error */
use Backoffice\Csrf;
?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1>CSV importieren</h1>
<?php if (!empty($error)): ?>
  <p class="flash flash-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>
<section class="card">
  <p>Die Datei wird zuerst nur <strong>geprüft</strong>. Du siehst eine Vorschau mit neuen Kunden, Dubletten und fehlerhaften Zeilen und entscheidest dann, ob importiert wird.</p>
  <ul class="hint-list">
    <li>Pflicht ist eine Spalte <code>email</code> (oder „E-Mail“). Optional: <code>first_name</code>/<code>vorname</code>, <code>last_name</code>/<code>nachname</code>, <code>status</code> (subscribed/unsubscribed) oder <code>newsletter</code> (1/0).</li>
    <li>Trennzeichen (Semikolon, Komma, Tab) und Zeichensatz werden automatisch erkannt.</li>
    <li>Alle anderen Spalten (z. B. IP-Adressen, Statistiken) werden ignoriert und nicht gespeichert.</li>
    <li>E-Mail-Adressen, die es schon gibt, werden übersprungen – bestehende Kunden werden nicht verändert.</li>
  </ul>
  <form method="post" action="/kunden/import" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>
    <label for="datei">CSV-Datei (höchstens 2 MB)</label>
    <input id="datei" name="datei" type="file" accept=".csv,text/csv" required>
    <button type="submit" class="btn">Datei prüfen</button>
  </form>
</section>
