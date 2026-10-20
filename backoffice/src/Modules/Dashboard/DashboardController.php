<?php
declare(strict_types=1);

namespace Backoffice\Modules\Dashboard;

use Backoffice\Db;
use Backoffice\Modules\Kinder\Geburtstage;
use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Router;
use Backoffice\View;

final class DashboardController
{
    public static function register(Router $router): void
    {
        $router->get('/', [self::class, 'index']);
    }

    public static function index(): void
    {
        View::render('dashboard', [
            'title' => 'Dashboard',
            'counts' => KundenRepository::countsByType(),
            'kinder' => (int) Db::query('SELECT COUNT(*) FROM kinder')->fetchColumn(),
            'newsletter' => (int) Db::query(
                "SELECT COUNT(*) FROM einwilligungen WHERE art = 'newsletter' AND erteilt_am IS NOT NULL AND widerrufen_am IS NULL AND kind_id IS NULL"
            )->fetchColumn(),
            'recent' => KundenRepository::recent(5),
            'geburtstage' => self::birthdays(),
        ]);
    }

    /** The next birthdays of all children with a birthdate (Geburtstage::ANZAHL) */
    private static function birthdays(): array
    {
        $kinder = Db::query(
            'SELECT id, kunde_id, vorname, nachname, geburtsdatum FROM kinder WHERE geburtsdatum IS NOT NULL'
        )->fetchAll();
        return Geburtstage::naechste($kinder, new \DateTimeImmutable('today'));
    }
}
