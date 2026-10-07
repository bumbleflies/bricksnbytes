<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;

final class KundenController
{
    private const PER_PAGE = 25;
    // Empty child rows offered in the form (no JavaScript needed; save and edit again for more)
    private const EMPTY_CHILD_ROWS = 2;

    public static function register(Router $router): void
    {
        $router->get('/kunden', [self::class, 'index']);
        $router->get('/kunden/neu', [self::class, 'createForm']);
        $router->post('/kunden/neu', [self::class, 'create']);
        $router->get('/kunden/bearbeiten', [self::class, 'editForm']);
        $router->post('/kunden/bearbeiten', [self::class, 'update']);
        $router->get('/kunden/loeschen', [self::class, 'confirmDelete']);
        $router->post('/kunden/loeschen', [self::class, 'delete']);
        $router->get('/kunden/export', [self::class, 'export']);
    }

    public static function index(): void
    {
        $q = self::str($_GET['q'] ?? '');
        $sort = array_key_exists(self::str($_GET['sort'] ?? ''), KundenRepository::SORTS) ? self::str($_GET['sort']) : 'nachname';
        $dir = self::str($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $page = max(1, (int) ($_GET['seite'] ?? 1));
        $nl = ($_GET['nl'] ?? '') === '1';

        $result = KundenRepository::search($q, $sort, $dir, $page, self::PER_PAGE, $nl);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        if ($page > $pages) {
            $page = $pages;
            $result = KundenRepository::search($q, $sort, $dir, $page, self::PER_PAGE, $nl);
        }

        View::render('kunden/index', [
            'title' => 'Kunden',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'q' => $q,
            'nl' => $nl,
            'sort' => $sort,
            'dir' => $dir,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public static function createForm(): void
    {
        self::renderForm(null, array_fill_keys(KundenRepository::FIELDS, null) + ['newsletter' => 0], [], []);
    }

    public static function create(): void
    {
        $result = KundenValidator::validate($_POST, null);
        if ($result['errors']) {
            self::renderForm(null, $result['data'], self::postedKinder(), $result['errors'], 422);
            return;
        }
        $id = KundenRepository::save(null, $result['data'], $result['kinder']);
        Session::flash('ok', 'Kunde angelegt.');
        Router::redirect('/kunden/bearbeiten?id=' . $id);
    }

    public static function editForm(): void
    {
        $kunde = self::findOr404();
        self::renderForm((int) $kunde['id'], $kunde, $kunde['kinder'], []);
    }

    public static function update(): void
    {
        $kunde = self::findOr404();
        $id = (int) $kunde['id'];
        $result = KundenValidator::validate($_POST, $id);
        if ($result['errors']) {
            self::renderForm($id, $result['data'] + $kunde, self::postedKinder(), $result['errors'], 422);
            return;
        }
        KundenRepository::save($id, $result['data'], $result['kinder']);
        Session::flash('ok', 'Änderungen gespeichert.');
        Router::redirect('/kunden/bearbeiten?id=' . $id);
    }

    public static function confirmDelete(): void
    {
        $kunde = self::findOr404();
        View::render('kunden/delete', ['title' => 'Kunde löschen', 'kunde' => $kunde]);
    }

    public static function delete(): void
    {
        $kunde = self::findOr404();
        KundenRepository::delete((int) $kunde['id']);
        Session::flash('ok', 'Kunde und zugehörige Kinder wurden gelöscht.');
        Router::redirect('/kunden');
    }

    public static function export(): void
    {
        $q = self::str($_GET['q'] ?? '');
        $rows = KundenRepository::exportRows($q, ($_GET['nl'] ?? '') === '1');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kunden-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel opens UTF-8 (umlauts) correctly
        $header = ['Vorname', 'Nachname', 'E-Mail', 'Telefon', 'Adresse', 'PLZ', 'Ort',
            'Newsletter', 'Kind 1', 'Kind 1 Geburtsdatum', 'Kind 2', 'Kind 2 Geburtsdatum', 'Weitere Kinder', 'Angelegt am', 'Geändert am'];
        fputcsv($out, $header, ';', '"', '');
        foreach ($rows as $r) {
            $kinder = $r['kinder'];
            $more = array_map(
                static fn ($k) => $k['vorname'] . ($k['geburtsdatum'] ? ' (' . self::germanDate($k['geburtsdatum']) . ')' : ''),
                array_slice($kinder, 2)
            );
            $line = [
                $r['vorname'], $r['nachname'], $r['email'], $r['telefon'], $r['adresse'], $r['plz'], $r['ort'],
                $r['newsletter'] ? 'ja' : 'nein',
                $kinder[0]['vorname'] ?? '', self::germanDate($kinder[0]['geburtsdatum'] ?? null),
                $kinder[1]['vorname'] ?? '', self::germanDate($kinder[1]['geburtsdatum'] ?? null),
                implode(', ', $more),
                self::germanDate($r['erstellt_am']), self::germanDate($r['geaendert_am']),
            ];
            fputcsv($out, array_map([self::class, 'csvSafe'], $line), ';', '"', '');
        }
        fclose($out);
    }

    // Spreadsheet apps execute cells starting with = + - @ as formulas (CSV injection)
    private static function csvSafe(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private static function germanDate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $date = new \DateTimeImmutable($value);
        return strlen($value) > 10 ? $date->format('d.m.Y H:i') : $date->format('d.m.Y');
    }

    private static function renderForm(?int $id, array $kunde, array $kinder, array $errors, int $status = 200): void
    {
        $rows = array_values(array_map(static fn ($k) => [
            'id' => $k['id'] ?? null,
            'vorname' => $k['vorname'] ?? '',
            'geburtsdatum' => $k['geburtsdatum'] ?? '',
        ], $kinder));
        $errorKeys = array_keys($kinder);
        // Map child errors from posted index to row position
        $kinderErrors = [];
        foreach ($errorKeys as $pos => $key) {
            if (isset($errors["kinder.$key"])) {
                $kinderErrors[$pos] = $errors["kinder.$key"];
            }
        }
        for ($i = 0; $i < self::EMPTY_CHILD_ROWS; $i++) {
            $rows[] = ['id' => null, 'vorname' => '', 'geburtsdatum' => ''];
        }

        View::render('kunden/form', [
            'title' => $id === null ? 'Neuer Kunde' : 'Kunde bearbeiten',
            'id' => $id,
            'kunde' => $kunde,
            'kinder' => $rows,
            'errors' => $errors,
            'kinderErrors' => $kinderErrors,
        ], $status);
    }

    // Posted child rows without the empty slots, keeping their original index
    private static function postedKinder(): array
    {
        $rows = is_array($_POST['kinder'] ?? null) ? $_POST['kinder'] : [];
        return array_filter($rows, static fn ($r) => is_array($r)
            && (trim((string) ($r['vorname'] ?? '')) !== '' || trim((string) ($r['geburtsdatum'] ?? '')) !== ''));
    }

    private static function findOr404(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $kunde = $id > 0 ? KundenRepository::find($id) : null;
        if ($kunde === null) {
            View::render('error', ['title' => 'Nicht gefunden', 'message' => 'Diesen Kunden gibt es nicht (mehr).'], 404);
            exit;
        }
        return $kunde;
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
