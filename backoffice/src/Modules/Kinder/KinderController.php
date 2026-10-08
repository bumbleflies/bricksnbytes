<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kinder;

use Backoffice\Db;
use Backoffice\Modules\Kunden\KundenController;
use Backoffice\Router;
use Backoffice\View;

// Children overview across all families (children are edited on their family's page)
final class KinderController
{
    private const PER_PAGE = 25;

    // Age sorts by birthdate, or mid-year of the birth year when only the year is known
    private const SORTS = [
        'name' => "CONCAT_WS(' ', ki.vorname, ki.nachname)",
        'alter' => "COALESCE(ki.geburtsdatum, MAKEDATE(ki.geburtsjahr, 183))",
        'eltern' => "CONCAT_WS(' ', hk.nachname, hk.vorname)",
        'ort' => 'k.ort',
    ];

    private const FROM = "FROM kinder ki
        JOIN kunden k ON k.id = ki.kunde_id
        LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1";

    public static function register(Router $router): void
    {
        $router->get('/kinder', [self::class, 'index']);
        $router->get('/kinder/export', [self::class, 'export']);
    }

    public static function index(): void
    {
        $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $sort = is_string($_GET['sort'] ?? null) && array_key_exists($_GET['sort'], self::SORTS) ? $_GET['sort'] : 'name';
        // "Alter aufsteigend" means youngest first = latest birthdate first
        $dir = ($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $page = max(1, (int) ($_GET['seite'] ?? 1));

        [$where, $params] = self::filter($q);
        $total = (int) Db::query('SELECT COUNT(*) ' . self::FROM . " $where", $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $expr = self::SORTS[$sort];
        $sqlDir = $sort === 'alter' ? ($dir === 'asc' ? 'DESC' : 'ASC') : strtoupper($dir);
        $rows = Db::query(
            'SELECT ki.*, k.id AS kunde_id, k.ort, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname, k.email AS kunde_email '
            . self::FROM . " $where ORDER BY ($expr) IS NULL, $expr $sqlDir, ki.id LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        )->fetchAll();

        View::render('kinder/index', [
            'title' => 'Kinder', 'rows' => $rows, 'total' => $total, 'q' => $q,
            'sort' => $sort, 'dir' => $dir, 'page' => $page, 'pages' => $pages,
        ]);
    }

    public static function export(): void
    {
        $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        [$where, $params] = self::filter($q);
        $rows = Db::query(
            'SELECT ki.*, k.ort, k.email AS kunde_email, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname '
            . self::FROM . " $where ORDER BY ki.nachname, ki.vorname, ki.id",
            $params
        )->fetchAll();

        $lines = array_map(static fn ($r) => [
            $r['vorname'], $r['nachname'], KundenController::germanDate($r['geburtsdatum']), $r['geburtsjahr'],
            Alter::years($r['geburtsdatum'], $r['geburtsjahr']),
            trim(($r['hk_vorname'] ?? '') . ' ' . ($r['hk_nachname'] ?? '')), $r['kunde_email'], $r['ort'],
            $r['abholberechtigte'], $r['notizen'],
        ], $rows);
        KundenController::sendCsv('kinder', ['Vorname', 'Nachname', 'Geburtsdatum', 'Geburtsjahr', 'Alter', 'Eltern', 'E-Mail Eltern', 'Ort', 'Abholberechtigte', 'Notizen'], $lines);
    }

    private static function filter(string $q): array
    {
        if ($q === '') {
            return ['', []];
        }
        $like = '%' . addcslashes($q, '%_\\') . '%';
        return [
            "WHERE ki.vorname LIKE ? OR ki.nachname LIKE ? OR CONCAT_WS(' ', ki.vorname, ki.nachname) LIKE ?
               OR hk.vorname LIKE ? OR hk.nachname LIKE ? OR k.ort LIKE ?",
            array_fill(0, 6, $like),
        ];
    }
}
