<?php
declare(strict_types=1);

namespace Backoffice;

// Minimal router: exact paths per method. Every route needs a login unless marked public,
// and every POST is CSRF-checked before the handler runs.
final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['GET'][$path] = [$handler, $public];
    }

    public function post(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['POST'][$path] = [$handler, $public];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        $route = $this->routes[$method][$path] ?? null;

        // Without login nothing but the public routes answers – not even a 404
        if (!($route[1] ?? false) && !Auth::check()) {
            self::redirect('/login');
        }

        if ($route === null) {
            $allowed = array_keys(array_filter($this->routes, static fn ($r) => isset($r[$path])));
            if ($allowed) {
                header('Allow: ' . implode(', ', $allowed));
                View::render('error', ['title' => 'Nicht erlaubt', 'message' => 'Diese Aktion ist hier nicht möglich.'], 405);
                return;
            }
            View::render('error', ['title' => 'Nicht gefunden', 'message' => 'Diese Seite gibt es nicht.'], 404);
            return;
        }

        [$handler] = $route;

        if ($method === 'POST' && !Csrf::verify()) {
            View::render('error', ['title' => 'Formular abgelaufen', 'message' => 'Bitte lade die Seite neu und versuche es noch einmal.'], 400);
            return;
        }

        $handler();
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }
}
