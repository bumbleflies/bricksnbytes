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
    private const KONTAKTE_KEY = 'kontakte_import';

    public static function register(Router $router): void
    {
        $router->get('/kunden/import', [self::class, 'form']);
        $router->post('/kunden/import', [self::class, 'preview']);
        $router->post('/kunden/import/ausfuehren', [self::class, 'run']);
        $router->get('/kunden/importe', [self::class, 'runs']);
        $router->get('/kunden/importe/rueckgaengig', [self::class, 'confirmUndo']);
        $router->post('/kunden/importe/rueckgaengig', [self::class, 'undo']);
        $router->get('/kunden/import/kontakte', [self::class, 'kontakteForm']);
        $router->post('/kunden/import/kontakte', [self::class, 'kontaktePreview']);
        $router->post('/kunden/import/kontakte/ausfuehren', [self::class, 'kontakteRun']);
        $router->get('/kinder/import', [self::class, 'kinderForm']);
        $router->post('/kinder/import', [self::class, 'kinderPreview']);
        $router->post('/kinder/import/ausfuehren', [self::class, 'kinderRun']);
    }

    public static function form(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        View::render('import/upload', ['title' => 'Newsletter-Liste importieren']);
    }

    public static function preview(): void
    {
        $file = $_FILES['datei'] ?? null;
        $error = self::uploadError($file);
        if ($error !== null) {
            View::render('import/upload', ['title' => 'Newsletter-Liste importieren', 'error' => $error], 422);
            return;
        }

        $analysis = CsvImport::analyse((string) file_get_contents($file['tmp_name']));
        // A contact list (types, organisations) belongs into the contact import, which keeps those columns
        $header = array_map('mb_strtolower', $analysis['header']);
        if (in_array('typ', $header, true) || in_array('organisation', $header, true)) {
            View::render('import/upload', [
                'title' => 'Newsletter-Liste importieren',
                'error' => 'Das sieht nach einer Kontaktliste aus (Spalte „Typ“ bzw. „Organisation“). Hier würden alle als Eltern ohne Namen angelegt – bitte „Kontakte importieren“ verwenden.',
                'kontakteListe' => true,
            ], 422);
            return;
        }
        if (!isset($analysis['mapping']['email'])) {
            View::render('import/upload', [
                'title' => 'Newsletter-Liste importieren',
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

    public static function runs(): void
    {
        $runs = array_map(static fn ($run) => $run + ['pruefung' => ImportUndo::check($run['ids'])], ImportUndo::runs());
        View::render('import/runs', ['title' => 'Importe', 'runs' => $runs]);
    }

    public static function confirmUndo(): void
    {
        $run = ImportUndo::find((int) ($_GET['lauf'] ?? 0));
        if ($run === null) {
            Session::flash('info', 'Diesen Import gibt es nicht (mehr).');
            Router::redirect('/kunden/importe');
        }
        View::render('import/undo', ['title' => 'Import rückgängig machen', 'run' => $run, 'pruefung' => ImportUndo::check($run['ids'])]);
    }

    public static function undo(): void
    {
        $run = ImportUndo::find((int) ($_POST['lauf'] ?? 0));
        if ($run === null) {
            Session::flash('info', 'Diesen Import gibt es nicht (mehr).');
            Router::redirect('/kunden/importe');
        }
        $deleted = ImportUndo::undo($run);
        $kept = count($run['ids']) - $deleted;
        Session::flash('ok', "Import rückgängig gemacht: $deleted Kunden gelöscht" . ($kept > 0 ? ", $kept geänderte behalten." : '.'));
        Router::redirect('/kunden/importe');
    }

    public static function kontakteForm(): void
    {
        unset($_SESSION[self::KONTAKTE_KEY]);
        View::render('import/kontakte-upload', ['title' => 'Kontakte importieren']);
    }

    public static function kontaktePreview(): void
    {
        $file = $_FILES['datei'] ?? null;
        $error = self::uploadError($file);
        if ($error !== null) {
            View::render('import/kontakte-upload', ['title' => 'Kontakte importieren', 'error' => $error], 422);
            return;
        }
        $analysis = KontakteImport::analyse((string) file_get_contents($file['tmp_name']));
        if (!isset($analysis['mapping']['email'], $analysis['mapping']['typ'])) {
            View::render('import/kontakte-upload', [
                'title' => 'Kontakte importieren',
                'error' => 'In der Datei fehlen die Spalten „E-Mail“ und/oder „Typ“. Gefundene Spalten: ' . implode(', ', $analysis['header']),
            ], 422);
            return;
        }
        $_SESSION[self::KONTAKTE_KEY] = ['new' => $analysis['new'], 'addContacts' => $analysis['addContacts'], 'file' => (string) $file['name']];
        View::render('import/kontakte-preview', ['title' => 'Kontakt-Import prüfen', 'a' => $analysis, 'file' => (string) $file['name']]);
    }

    public static function kontakteRun(): void
    {
        $pending = $_SESSION[self::KONTAKTE_KEY] ?? null;
        unset($_SESSION[self::KONTAKTE_KEY]);
        if (!is_array($pending)) {
            Session::flash('info', 'Der Import ist abgelaufen. Bitte die Datei noch einmal hochladen.');
            Router::redirect('/kunden/import/kontakte');
        }

        $customers = 0;
        $contacts = 0;
        $skipped = 0;
        // Taken in the meantime (second tab, earlier run): leave out, never overwrite
        $taken = static fn (?string $email): bool => $email !== null && KundenRepository::findIdByAnyEmail($email) !== null;

        foreach ($pending['new'] as $k) {
            $k['contacts'] = array_values(array_filter($k['contacts'], static fn ($c) => !$taken($c['email'])));
            $orgTaken = $k['data']['typ'] !== 'privat' && KundenRepository::findOrgIdByName((string) $k['data']['name']) !== null;
            if ($orgTaken || $taken($k['data']['email']) || ($k['data']['email'] === null && !$k['contacts'])) {
                $skipped++;
                continue;
            }
            if ($k['contacts'] && !in_array(1, array_column($k['contacts'], 'ist_hauptkontakt'), true)) {
                $k['contacts'][0]['ist_hauptkontakt'] = 1;
            }
            try {
                KundenRepository::save(null, $k['data'], $k['contacts'], [], [], 'importiert');
                $customers++;
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {
                    throw $e;
                }
                $skipped++;
            }
        }

        foreach ($pending['addContacts'] as $k) {
            $kunde = KundenRepository::find((int) $k['kunde_id']);
            if ($kunde === null) {
                $skipped += count($k['contacts']);
                continue;
            }
            $hasMain = (bool) array_filter($kunde['ansprechpartner'], static fn ($a) => (int) $a['ist_hauptkontakt'] === 1);
            $added = 0;
            foreach ($k['contacts'] as $c) {
                if ($taken($c['email'])) {
                    $skipped++;
                    continue;
                }
                $c['ist_hauptkontakt'] = $hasMain ? 0 : 1;
                KundenRepository::addContact((int) $kunde['id'], $c);
                $hasMain = true;
                $added++;
            }
            if ($added > 0) {
                $notes = trim(($kunde['notizen'] ?? '') . "\n\n" . $k['notizen']);
                KundenRepository::patch((int) $kunde['id'], array_filter([
                    'notizen' => mb_substr($notes, 0, 5000),
                    'status' => $kunde['status'] === null ? $k['status'] : null,
                ], static fn ($v) => $v !== null));
                $contacts += $added;
            }
        }

        View::render('import/kontakte-result', [
            'title' => 'Kontakt-Import abgeschlossen',
            'file' => $pending['file'],
            'customers' => $customers,
            'contacts' => $contacts,
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
