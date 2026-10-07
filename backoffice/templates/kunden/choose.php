<?php use Backoffice\Modules\Kunden\Kundentypen; ?>
<p class="breadcrumb"><a href="/kunden">← Alle Kunden</a></p>
<h1>Neuer Kunde</h1>
<p>Welche Art von Kunde möchtest du anlegen?</p>
<div class="choose-grid">
  <?php foreach (Kundentypen::ALLE as $key => [$label]): ?>
    <a class="choose-card" href="/kunden/neu?typ=<?= e($key) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>
