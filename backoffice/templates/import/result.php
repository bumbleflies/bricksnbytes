<?php /** @var string $file @var int $imported @var int $skipped @var int $invalid */ ?>
<h1>Import abgeschlossen</h1>
<p>Datei: <?= e($file) ?></p>
<div class="stats">
  <div class="stat"><span class="stat-value"><?= e($imported) ?></span><span class="stat-label">importiert</span></div>
  <div class="stat"><span class="stat-value"><?= e($skipped) ?></span><span class="stat-label">übersprungen (Dubletten)</span></div>
  <div class="stat"><span class="stat-value<?= $invalid ? ' stat-error' : '' ?>"><?= e($invalid) ?></span><span class="stat-label">fehlerhaft, nicht importiert</span></div>
</div>
<p>Fehlende Angaben (Name, Adresse, Kinder) kannst du jederzeit beim jeweiligen Kunden ergänzen.</p>
<p><a class="btn" href="/kunden">Zu den Kunden</a></p>
