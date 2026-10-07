<?php
declare(strict_types=1);

// Shared setup for web requests (public/index.php) and CLI scripts (bin/*.php)

const BACKOFFICE_ROOT = __DIR__ . '/..';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Backoffice\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$configFile = BACKOFFICE_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit("config.php fehlt – siehe config.example.php\n");
}
Backoffice\Config::load(require $configFile);

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);
ini_set('display_errors', Backoffice\Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

// Escape helper for templates
function e(mixed $value): string
{
    return Backoffice\View::e($value);
}
