<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auth;

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
            Router::redirect('/');
        }

        $error = $result === Auth::LOGIN_LOCKED
            ? 'Zu viele Fehlversuche. Der Login ist vorübergehend gesperrt – bitte versuche es in 15 Minuten erneut.'
            : 'Benutzername oder Passwort ist falsch.';
        View::render('login', ['title' => 'Anmelden', 'username' => $username, 'error' => $error], 401);
    }

    public static function logout(): void
    {
        Auth::logout();
        session_start();
        Session::flash('info', 'Du bist abgemeldet.');
        Router::redirect('/login');
    }
}
