<?php
declare(strict_types=1);

namespace Backoffice;

// Login with throttling: too many failures for a user name or an IP lock further attempts
// for the configured window. Failures are stored in login_versuche and purged after 24 h.
final class Auth
{
    public const LOGIN_OK = 'ok';
    public const LOGIN_FAILED = 'failed';
    public const LOGIN_LOCKED = 'locked';

    // Real argon2id hash of a random string, only used to spend the same time on unknown users
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$RDljRFUyUXRzSk9Zek1ySQ$Iie0TLxENjXyyiFnK9RudJX4b9B6/ybrIkwmwrtllHE';

    public static function hash(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($password, $algo);
    }

    public static function attempt(string $username, string $password, string $ip): string
    {
        $username = trim($username);
        $window = (int) Config::get('login.window_minutes', 15);

        Db::query('DELETE FROM login_versuche WHERE zeit < NOW() - INTERVAL 1 DAY');

        $byUser = (int) Db::query(
            'SELECT COUNT(*) FROM login_versuche WHERE benutzername = ? AND zeit > NOW() - INTERVAL ? MINUTE',
            [$username, $window]
        )->fetchColumn();
        $byIp = (int) Db::query(
            'SELECT COUNT(*) FROM login_versuche WHERE ip = ? AND zeit > NOW() - INTERVAL ? MINUTE',
            [$ip, $window]
        )->fetchColumn();

        if ($byUser >= (int) Config::get('login.max_failures_per_user', 5)
            || $byIp >= (int) Config::get('login.max_failures_per_ip', 20)) {
            return self::LOGIN_LOCKED;
        }

        $user = Db::query('SELECT id, benutzername, passwort_hash FROM benutzer WHERE benutzername = ?', [$username])->fetch();

        // Verify against a dummy hash for unknown users so timing does not reveal valid names
        $hash = $user['passwort_hash'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash) && $user !== false;

        if (!$valid) {
            Db::query('INSERT INTO login_versuche (benutzername, ip) VALUES (?, ?)', [mb_substr($username, 0, 100), $ip]);
            return self::LOGIN_FAILED;
        }

        if (password_needs_rehash($user['passwort_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            Db::query('UPDATE benutzer SET passwort_hash = ? WHERE id = ?', [self::hash($password), $user['id']]);
        }
        Db::query('DELETE FROM login_versuche WHERE benutzername = ?', [$username]);
        Db::query('UPDATE benutzer SET letzter_login = NOW() WHERE id = ?', [$user['id']]);

        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['benutzername'];
        return self::LOGIN_OK;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
