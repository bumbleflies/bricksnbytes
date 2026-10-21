<?php
declare(strict_types=1);

// Newsletter unsubscribe from the website: token check and request body of POST /api/newsletter-abmelden,
// the route without login/CSRF, and the unsubscribe link in "Newsletter verschicken". Without database.

use Backoffice\Config;
use Backoffice\Modules\Kunden\Verteiler;
use Backoffice\Modules\Newsletter\AbmeldungController;
use Backoffice\Router;

$token = str_repeat('a1', 32);

return [
    ['Abmeldung: Token muss exakt passen', static function () use ($token): void {
        assertSame(true, AbmeldungController::tokenValid($token, $token));
        assertSame(false, AbmeldungController::tokenValid($token . 'x', $token), 'falsches Token');
        assertSame(false, AbmeldungController::tokenValid(null, $token), 'kein Header');
        assertSame(false, AbmeldungController::tokenValid('', $token), 'leerer Header');
        assertSame(false, AbmeldungController::tokenValid(['x'], $token), 'kein String');
    }],
    ['Abmeldung: ohne oder mit zu kurzem Token ist der Endpoint aus', static function (): void {
        assertSame(false, AbmeldungController::tokenValid('', ''), 'leer konfiguriert');
        assertSame(false, AbmeldungController::tokenValid(null, null), 'nicht konfiguriert');
        assertSame(false, AbmeldungController::tokenValid('kurz', 'kurz'), 'zu kurz');
    }],
    ['Abmeldung: E-Mail aus dem JSON-Body', static function (): void {
        assertSame('anna@example.de', AbmeldungController::email('{"email":"  anna@example.de "}'));
        assertSame('Anna@Example.de', AbmeldungController::email('{"email":"Anna@Example.de"}'), 'Schreibweise bleibt, verglichen wird ohne Groß/klein');
        assertSame(null, AbmeldungController::email('{"email":"kaputt"}'), 'ungültig');
        assertSame(null, AbmeldungController::email('{"email":["a@b.de"]}'), 'kein String');
        assertSame(null, AbmeldungController::email('{}'), 'fehlt');
        assertSame(null, AbmeldungController::email('kein json'), 'kein JSON');
        assertSame(null, AbmeldungController::email('{"email":"a@b.de","x":"' . str_repeat('x', 5000) . '"}'), 'zu groß');
    }],
    ['Abmeldung: Route ohne Login und ohne CSRF-Token', static function (): void {
        $_SESSION = [];
        $_POST = [];
        $called = false;
        $router = new Router();
        $router->post('/api/test', static function () use (&$called): void {
            $called = true;
        }, public: true, csrf: false);
        $router->dispatch('POST', '/api/test');
        assertSame(true, $called);
    }],
    ['Newsletter verschicken: Abmeldelink am Ende der Mail', static function (): void {
        Config::load([]);
        assertSame(true, str_contains(Verteiler::newsletterFooter(), 'abmelden: https://bricksnbytes.de/newsletter-abmelden'), 'Standard');
        Config::load(['newsletter' => ['abmelde_url' => 'https://example.de/ab']]);
        assertSame(true, str_contains(Verteiler::newsletterFooter(), 'abmelden: https://example.de/ab'), 'aus der Konfiguration');
        Config::load([]);
    }],
];
