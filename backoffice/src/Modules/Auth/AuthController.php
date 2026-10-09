<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auth;

use Backoffice\Audit;
use Backoffice\Auth;
use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;

final class AuthController
{
    public static function register(Router $router): void
    {
        $router->get('/login', [self::class, 'form'], public: true);
        $router->post('/login', [self::class, 'login'], public: true);
        $router->post('/logout', [self::class, 'logout']);
        $router->get('/passwort', [self::class, 'passwordForm']);
        $router->post('/passwort', [self::class, 'changePassword']);
    }

    public static function form(): void
    {
        if (Auth::check()) {
            Router::redirect('/');
        }
        View::render('login', ['title' => 'Anmelden', 'username' => '']);
    }

    public static function login(): void
    {
        $username = is_string($_POST['benutzername'] ?? null) ? $_POST['benutzername'] : '';
        $password = is_string($_POST['passwort'] ?? null) ? $_POST['passwort'] : '';

        $result = Auth::attempt($username, $password, $_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($result === Auth::LOGIN_OK) {
            Audit::purge();
            Router::redirect('/');
        }

        $error = $result === Auth::LOGIN_LOCKED
            ? 'Zu viele Fehlversuche. Der Login ist vorübergehend gesperrt – bitte versuche es in 15 Minuten erneut.'
            : 'Benutzername oder Passwort ist falsch.';
        View::render('login', ['title' => 'Anmelden', 'username' => $username, 'error' => $error], 401);
    }

    public static function passwordForm(): void
    {
        View::render('passwort', ['title' => 'Passwort ändern', 'errors' => []]);
    }

    public static function changePassword(): void
    {
        $field = static fn (string $name): string => is_string($_POST[$name] ?? null) ? $_POST[$name] : '';
        $current = $field('aktuell');
        $new = $field('neu');

        $errors = $new !== $field('wiederholen')
            ? ['wiederholen' => 'Die beiden neuen Passwörter stimmen nicht überein.']
            : Auth::changePassword($current, $new, $_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($errors) {
            View::render('passwort', ['title' => 'Passwort ändern', 'errors' => $errors], 422);
            return;
        }
        Session::flash('ok', 'Dein Passwort wurde geändert.');
        Router::redirect('/');
    }

    public static function logout(): void
    {
        Auth::logout();
        session_start();
        Session::flash('info', 'Du bist abgemeldet.');
        Router::redirect('/login');
    }
}
