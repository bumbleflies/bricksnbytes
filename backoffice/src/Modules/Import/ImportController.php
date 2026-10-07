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

    public static function register(Router $router): void
    {
        $router->get('/kunden/import', [self::class, 'form']);
        $router->post('/kunden/import', [self::class, 'preview']);
        $router->post('/kunden/import/ausfuehren', [self::class, 'run']);
    }

    public static function form(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        View::render('import/upload', ['title' => 'CSV importieren']);
    }

    public static function preview(): void
    {
        $file = $_FILES['datei'] ?? null;
        $error = match (true) {
            !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE => 'Bitte eine CSV-Datei auswählen.',
            $file['error'] === UPLOAD_ERR_INI_SIZE, $file['error'] === UPLOAD_ERR_FORM_SIZE, $file['size'] > self::MAX_BYTES => 'Die Datei ist zu groß (höchstens 2 MB).',
            $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) => 'Die Datei konnte nicht hochgeladen werden.',
            !preg_match('/\.(csv|txt)$/i', (string) $file['name']) => 'Bitte eine Datei mit der Endung .csv hochladen.',
            default => null,
        };
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
}
