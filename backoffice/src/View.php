<?php
declare(strict_types=1);

namespace Backoffice;

// Renders templates/<name>.php inside templates/layout.php. Templates must print
// every dynamic value through View::e() (alias e()).
final class View
{
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $content = self::capture($template, $data);
        echo self::capture('layout', $data + ['content' => $content, 'flash' => Session::takeFlash()]);
    }

    private static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BACKOFFICE_ROOT . '/templates/' . $template . '.php';
        return (string) ob_get_clean();
    }
}

