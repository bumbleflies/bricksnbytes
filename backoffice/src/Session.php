<?php
declare(strict_types=1);

namespace Backoffice;

// Hardened session: cookie only over HTTPS, not readable by JS, same-site only,
// idle and absolute timeout.
final class Session
{
    public static function start(): void
    {
        $secure = (bool) Config::get('app.secure_cookies', true);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        // __Host- prefix: browser enforces Secure, Path=/ and no Domain attribute
        session_name($secure ? '__Host-bbo' : 'bbo');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();

        $now = time();
        $idle = (int) Config::get('app.session_idle_minutes', 30) * 60;
        $max = (int) Config::get('app.session_max_hours', 8) * 3600;

        $expired = isset($_SESSION['last_activity'])
            && ($now - $_SESSION['last_activity'] > $idle || $now - ($_SESSION['started_at'] ?? $now) > $max);

        if ($expired) {
            self::destroy();
            session_start();
            $_SESSION['flash'] = ['type' => 'info', 'text' => 'Deine Sitzung ist abgelaufen. Bitte melde dich erneut an.'];
        }

        $_SESSION['started_at'] ??= $now;
        $_SESSION['last_activity'] = $now;
    }

    // New session id after login so a planted id cannot be reused (session fixation)
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['started_at'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_destroy();
    }

    public static function flash(string $type, string $text): void
    {
        $_SESSION['flash'] = ['type' => $type, 'text' => $text];
    }

    public static function takeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
