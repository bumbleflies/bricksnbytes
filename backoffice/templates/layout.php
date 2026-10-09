<?php
/** @var string $title @var string $content @var ?array $flash */
use Backoffice\Auth;
use Backoffice\Config;
use Backoffice\Csrf;
use Backoffice\Navigation;

$appName = (string) Config::get('app.name', 'Imperiales Sicherheitsbüro');
$current = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$signedIn = Auth::check();
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
<body class="<?= $signedIn ? 'app' : 'guest' ?>">
<?php if ($signedIn): ?>
  <aside class="sidebar">
    <a class="brand" href="/">Bricksn<span>Bytes</span><small><?= e($appName) ?></small></a>
    <nav class="sidenav" aria-label="Module">
      <?php foreach (Navigation::ITEMS as [$href, $label]): ?>
        <a href="<?= e($href) ?>"<?= Navigation::isActive($href, $current) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <form class="logout" method="post" action="/logout">
      <?= Csrf::field() ?>
      <span class="user"><?= e(Auth::userName()) ?><a class="user-link" href="/passwort"<?= $current === '/passwort' ? ' aria-current="page"' : '' ?>>Passwort ändern</a></span>
      <button type="submit" class="btn btn-ghost btn-small">Abmelden</button>
    </form>
  </aside>
  <div class="main">
    <header class="topbar">
      <form class="global-search" method="get" action="/suche" role="search">
        <label for="global-q" class="visually-hidden">Suche</label>
        <input id="global-q" name="q" type="search" placeholder="Kunde oder Kind suchen" value="<?= e($current === '/suche' ? ($_GET['q'] ?? '') : '') ?>">
      </form>
      <a class="btn" href="/kunden/neu">+ Neu</a>
    </header>
    <main class="page">
      <?php if (!empty($flash)): ?>
        <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['text']) ?></p>
      <?php endif; ?>
      <?= $content /* already escaped by the inner template */ ?>
    </main>
  </div>
<?php else: ?>
  <header class="guest-bar"><span class="brand">Bricksn<span>Bytes</span><small><?= e($appName) ?></small></span></header>
  <main class="page">
    <?php if (!empty($flash)): ?>
      <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['text']) ?></p>
    <?php endif; ?>
    <?= $content ?>
  </main>
<?php endif; ?>
</body>
</html>
