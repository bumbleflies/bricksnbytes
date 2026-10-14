<?php /** @var string $file @var int $families @var int $children @var int $skipped */ ?>
<h1>Kinder-Import abgeschlossen</h1>
<p>Datei: <?= e($file) ?></p>
<div class="stats">
  <div class="stat"><span class="stat-value"><?= e($children) ?></span><span class="stat-label">Kinder angelegt</span></div>
  <div class="stat"><span class="stat-value"><?= e($families) ?></span><span class="stat-label">neue Familien</span></div>
  <div class="stat"><span class="stat-value"><?= e($skipped) ?></span><span class="stat-label">übersprungen (inzwischen vorhanden)</span></div>
</div>
<p>Die Geburtsjahre sind aus dem Alter bei der Buchung geschätzt – das steht jeweils in den Notizen des Kindes. Wo „Alter bitte prüfen“ steht, gab es mehrere Kinder in einer Buchung.</p>
<p><a class="btn" href="/kinder">Zu den Kindern</a> <a class="btn btn-secondary" href="/kunden">Zu den Kunden</a></p>
