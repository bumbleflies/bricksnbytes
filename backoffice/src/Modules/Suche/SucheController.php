<?php
declare(strict_types=1);

namespace Backoffice\Modules\Suche;

use Backoffice\Db;
use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Router;
use Backoffice\View;

// Global search (top bar). For now: customers and children; later also orders and invoices.
final class SucheController
{
    private const LIMIT = 20;

    public static function register(Router $router): void
    {
        $router->get('/suche', [self::class, 'index']);
    }

    public static function index(): void
    {
        $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $kunden = $kinder = [];
        $totalKunden = 0;
        if (mb_strlen($q) >= 2) {
            $result = KundenRepository::search($q, null, 'name', 'asc', 1, self::LIMIT);
            $kunden = $result['rows'];
            $totalKunden = $result['total'];

            $like = '%' . addcslashes($q, '%_\\') . '%';
            $kinder = Db::query(
                "SELECT ki.id, ki.vorname, ki.nachname, ki.geburtsdatum, ki.geburtsjahr, k.id AS kunde_id, k.ort,
                        hk.vorname AS hk_vorname, hk.nachname AS hk_nachname
                 FROM kinder ki JOIN kunden k ON k.id = ki.kunde_id
                 LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1
                 WHERE ki.vorname LIKE ? OR ki.nachname LIKE ? OR CONCAT_WS(' ', ki.vorname, ki.nachname) LIKE ?
                    OR CONCAT_WS(' ', ki.vorname, hk.nachname) LIKE ?
                    OR EXISTS (SELECT 1 FROM ansprechpartner a WHERE a.kunde_id = k.id AND a.nachname LIKE ?)
                 ORDER BY ki.vorname, ki.nachname LIMIT " . self::LIMIT,
                [$like, $like, $like, $like, $like]
            )->fetchAll();
        }
        View::render('suche', ['title' => 'Suche', 'q' => $q, 'kunden' => $kunden, 'totalKunden' => $totalKunden, 'kinder' => $kinder]);
    }
}
