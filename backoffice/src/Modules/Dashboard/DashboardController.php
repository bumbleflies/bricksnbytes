<?php
declare(strict_types=1);

namespace Backoffice\Modules\Dashboard;

use Backoffice\Db;
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
        $kunden = (int) Db::query('SELECT COUNT(*) FROM kunden')->fetchColumn();
        $kinder = (int) Db::query('SELECT COUNT(*) FROM kinder')->fetchColumn();
        $newsletter = (int) Db::query('SELECT COUNT(*) FROM kunden WHERE newsletter = 1')->fetchColumn();
        View::render('dashboard', ['title' => 'Übersicht', 'kunden' => $kunden, 'kinder' => $kinder, 'newsletter' => $newsletter]);
    }
}
