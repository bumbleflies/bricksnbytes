<?php
declare(strict_types=1);

namespace Backoffice\Modules\Import;

use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;
use PDOException;

// Upload → preview (nothing saved yet) → import. The analysed rows wait in the session
// between preview and import and are removed right after; the uploaded file itself is
// never stored (PHP deletes the temporary upload at the end of the request).
final class ImportController
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const SESSION_KEY = 'kunden_import';
    private const KINDER_KEY = 'kinder_import';

    public static function register(Router $router): void
    {
        $router->get('/kunden/import', [self::class, 'form']);
        $router->post('/kunden/import', [self::class, 'preview']);
        $router->post('/kunden/import/ausfuehren', [self::class, 'run']);
        $router->get('/kinder/import', [self::class, 'kinderForm']);
        $router->post('/kinder/import', [self::class, 'kinderPreview']);
        $router->post('/kinder/import/ausfuehren', [self::class, 'kinderRun']);
    }

    public static function form(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        View::render('import/upload', ['title' => 'CSV importieren']);
    }

    public static function preview(): void
    {
        $file = $_FILES['datei'] ?? null;
        $error = self::uploadError($file);
        if ($error !== null) {
            View::render('import/upload', ['title' => 'CSV importieren', 'error' => $error], 422);
            return;
        }

        $analysis = CsvImport::analyse((string) file_get_contents($file['tmp_name']));
        if (!isset($analysis['mapping']['email'])) {
            View::render('import/upload', [
                'title' => 'CSV importieren',
                'error' => 'In der Datei wurde keine Spalte für die E-Mail-Adresse gefunden (erwartet z. B. „email“ oder „E-Mail“). Gefundene Spalten: ' . implode(', ', $analysis['header']),
            ], 422);
            return;
        }

        $_SESSION[self::SESSION_KEY] = ['rows' => $analysis['new'], 'duplicates' => count($analysis['duplicates']),
            'invalid' => count($analysis['invalid']), 'file' => (string) $file['name']];

        View::render('import/preview', ['title' => 'Import prüfen', 'a' => $analysis, 'file' => (string) $file['name']]);
    }

    public static function run(): void
    {
        $pending = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);
        if (!is_array($pending)) {
            Session::flash('info', 'Der Import ist abgelaufen. Bitte die Datei noch einmal hochladen.');
            Router::redirect('/kunden/import');
        }

        $imported = 0;
        $skipped = (int) $pending['duplicates'];
        $today = date('Y-m-d');
        foreach ($pending['rows'] as $row) {
            $contacts = $row['vorname'] !== '' || $row['nachname'] !== ''
                ? [['id' => null, 'vorname' => $row['vorname'] ?: null, 'nachname' => $row['nachname'] ?: null,
                    'rolle' => 'Elternteil', 'email' => null, 'telefon' => null, 'ist_hauptkontakt' => 1]]
                : [];
            $consents = [];
            if ($row['newsletter'] === 1 || $row['abgemeldet_am'] !== null) {
                $consents['newsletter'] = [
                    'erteilt_am' => $row['angemeldet_am'] ?? $today,
                    'widerrufen_am' => $row['newsletter'] === 1 ? null : ($row['abgemeldet_am'] ?? $today),
                    'quelle' => 'Import ' . $pending['file'],
                ];
            }
            try {
                KundenRepository::save(
                    null,
                    ['typ' => 'privat', 'email' => $row['email'], 'quelle' => 'Import alte Datenbank'],
                    $contacts,
                    [],
                    $consents,
                    'importiert'
                );
                $imported++;
            } catch (PDOException $e) {
                // Created in the meantime (e.g. a second import tab): count as duplicate
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                $skipped++;
            }
        }

        View::render('import/result', [
            'title' => 'Import abgeschlossen',
            'file' => $pending['file'],
            'imported' => $imported,
            'skipped' => $skipped,
            'invalid' => (int) $pending['invalid'],
        ]);
    }

    public static function kinderForm(): void
    {
        unset($_SESSION[self::KINDER_KEY]);
        View::render('import/kinder-upload', ['title' => 'Kinder importieren']);
    }

    public static function kinderPreview(): void
    {
        $file = $_FILES['datei'] ?? null;
        $error = self::uploadError($file);
        if ($error !== null) {
            View::render('import/kinder-upload', ['title' => 'Kinder importieren', 'error' => $error], 422);
            return;
        }
        $analysis = KinderImport::analyse((string) file_get_contents($file['tmp_name']));
        if (!isset($analysis['mapping']['email'], $analysis['mapping']['kind'])) {
            View::render('import/kinder-upload', [
                'title' => 'Kinder importieren',
                'error' => 'In der Datei fehlen die Spalten „E-Mail“ und/oder „Name Kind“. Gefundene Spalten: ' . implode(', ', $analysis['header']),
            ], 422);
            return;
        }
        $_SESSION[self::KINDER_KEY] = ['families' => $analysis['families'], 'children' => $analysis['newChildren'], 'file' => (string) $file['name']];
        View::render('import/kinder-preview', ['title' => 'Kinder-Import prüfen', 'a' => $analysis, 'file' => (string) $file['name']]);
    }

    public static function kinderRun(): void
    {
        $pending = $_SESSION[self::KINDER_KEY] ?? null;
        unset($_SESSION[self::KINDER_KEY]);
        if (!is_array($pending)) {
            Session::flash('info', 'Der Import ist abgelaufen. Bitte die Datei noch einmal hochladen.');
            Router::redirect('/kinder/import');
        }

        $families = 0;
        $children = 0;
        $skipped = 0;
        foreach ($pending['families'] as $f) {
            // Created in the meantime (second tab, earlier run): add the children to that customer instead
            $existing = KundenRepository::findIdByAnyEmail($f['email']);
            if ($existing !== null) {
                foreach ($f['kinder'] as $kind) {
                    $pending['children'][] = $kind + ['email' => $f['email'], 'kunde_id' => $existing];
                }
                continue;
            }
            $contacts = [];
            foreach ($f['eltern'] as $i => $p) {
                $contacts[] = ['id' => null, 'vorname' => $p['vorname'], 'nachname' => $p['nachname'], 'rolle' => 'Elternteil',
                    'email' => null, 'telefon' => null, 'ist_hauptkontakt' => $i === 0 ? 1 : 0];
            }
            $kinder = array_map(static fn ($k) => self::childRow($k), $f['kinder']);
            try {
                KundenRepository::save(null, ['typ' => 'privat', 'email' => $f['email'], 'quelle' => 'Import pretix'], $contacts, $kinder, [], 'importiert');
                $families++;
                $children += count($kinder);
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                $skipped += count($kinder);
            }
        }

        foreach ($pending['children'] as $kind) {
            $known = array_map('mb_strtolower', KundenRepository::childFirstNames((int) $kind['kunde_id']));
            if (in_array(mb_strtolower($kind['vorname']), $known, true) || KundenRepository::find((int) $kind['kunde_id']) === null) {
                $skipped++;
                continue;
            }
            KundenRepository::addChild((int) $kind['kunde_id'], self::childRow($kind));
            $children++;
        }

        View::render('import/kinder-result', [
            'title' => 'Kinder-Import abgeschlossen',
            'file' => $pending['file'],
            'families' => $families,
            'children' => $children,
            'skipped' => $skipped,
        ]);
    }

    private static function childRow(array $kind): array
    {
        return ['id' => null, 'vorname' => $kind['vorname'], 'nachname' => $kind['nachname'], 'geburtsdatum' => null,
            'geburtsjahr' => $kind['geburtsjahr'], 'abholberechtigte' => null, 'notizen' => $kind['notizen']];
    }

    private static function uploadError(mixed $file): ?string
    {
        return match (true) {
            !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE => 'Bitte eine CSV-Datei auswählen.',
            $file['error'] === UPLOAD_ERR_INI_SIZE, $file['error'] === UPLOAD_ERR_FORM_SIZE, $file['size'] > self::MAX_BYTES => 'Die Datei ist zu groß (höchstens 2 MB).',
            $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) => 'Die Datei konnte nicht hochgeladen werden.',
            !preg_match('/\.(csv|txt)$/i', (string) $file['name']) => 'Bitte eine Datei mit der Endung .csv hochladen.',
            default => null,
        };
    }
}
