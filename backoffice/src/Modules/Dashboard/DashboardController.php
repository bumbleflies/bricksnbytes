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

    /** Birthdays per period of the tile's switch, e.g. ['woche' => [...], 'monat' => [...]] */
    private static function birthdays(): array
    {
        $kinder = Db::query(
            'SELECT id, kunde_id, vorname, nachname, geburtsdatum FROM kinder WHERE geburtsdatum IS NOT NULL'
        )->fetchAll();
        $today = new \DateTimeImmutable('today');
        $lists = [];
        foreach (array_keys(Geburtstage::ZEITRAEUME) as $zeitraum) {
            [$from, $to] = Geburtstage::zeitraum($zeitraum, $today);
            $lists[$zeitraum] = Geburtstage::imZeitraum($kinder, $from, $to, $today);
        }
        return $lists;
    }
}
