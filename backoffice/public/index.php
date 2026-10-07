<?php
declare(strict_types=1);

// Single entry point: every request is rewritten here by .htaccess

// Local development (php -S): let the built-in server deliver existing static files
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../src/bootstrap.php';

use Backoffice\Modules;
use Backoffice\Router;
use Backoffice\Session;

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; font-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Cache-Control: no-store');

Session::start();

$router = new Router();
// Modules register their routes here; new modules (Kurse, Buchungen, …) are added the same way
Modules\Auth\AuthController::register($router);
Modules\Dashboard\DashboardController::register($router);
Modules\Kunden\KundenController::register($router);
Modules\Import\ImportController::register($router);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
