<?php
/** @var string $title @var string $content @var ?array $flash */
use Backoffice\Auth;
use Backoffice\Config;
use Backoffice\Csrf;

$appName = (string) Config::get('app.name', 'Imperiales Sicherheitsbüro');
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? $appName) ?> · <?= e($appName) ?></title>
  <link rel="stylesheet" href="/assets/fonts.css">
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="topbar">
    <a class="brand" href="/">Bricksn<span>Bytes</span> <small><?= e($appName) ?></small></a>
    <?php if (Auth::check()): ?>
      <nav class="mainnav" aria-label="Hauptnavigation">
        <a href="/"<?= $current === '/' ? ' aria-current="page"' : '' ?>>Übersicht</a>
        <a href="/kunden"<?= str_starts_with($current, '/kunden') ? ' aria-current="page"' : '' ?>>Kunden</a>
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
