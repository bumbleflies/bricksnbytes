<?php /** @var string $file @var int $customers @var int $contacts @var int $skipped */ ?>
<h1>Kontakt-Import abgeschlossen</h1>
<p>Datei: <?= e($file) ?></p>
<div class="stats">
  <div class="stat"><span class="stat-value"><?= e($customers) ?></span><span class="stat-label">Kunden angelegt</span></div>
  <div class="stat"><span class="stat-value"><?= e($contacts) ?></span><span class="stat-label">Ansprechpartner bei bestehenden Kunden ergänzt</span></div>
  <div class="stat"><span class="stat-value"><?= e($skipped) ?></span><span class="stat-label">übersprungen (inzwischen vorhanden)</span></div>
</div>
<p>Die importierten Kunden haben als Quelle „Import Kontakte“; Notizen und Anzahl der Mails stehen in den Notizen. Newsletter-Einwilligungen wurden nicht gesetzt.</p>
<p><a class="btn" href="/kunden">Zu den Kunden</a></p>
