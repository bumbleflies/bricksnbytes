<?php
/** @var string $title @var string $content @var ?array $flash */
use Backoffice\Auth;
use Backoffice\Csrf;
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? 'Backoffice') ?> · BricksnBytes Backoffice</title>
  <link rel="stylesheet" href="/assets/fonts.css">
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/">Bricksn<span>Bytes</span> <small>Backoffice</small></a>
    <?php if (Auth::check()): ?>
      <nav class="mainnav" aria-label="Hauptnavigation">
        <a href="/">Übersicht</a>
      </nav>
      <form class="logout" method="post" action="/logout">
        <?= Csrf::field() ?>
        <span class="user"><?= e(Auth::userName()) ?></span>
        <button type="submit" class="btn btn-ghost">Abmelden</button>
      </form>
    <?php endif; ?>
  </header>

  <main class="page">
    <?php if (!empty($flash)): ?>
      <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['text']) ?></p>
    <?php endif; ?>
    <?= $content /* already escaped by the inner template */ ?>
  </main>
</body>
</html>
