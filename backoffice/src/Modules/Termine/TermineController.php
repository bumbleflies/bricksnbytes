<?php
declare(strict_types=1);

namespace Backoffice\Modules\Termine;

use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;
use DateTimeImmutable;

final class TermineController
{
    private const IMPORT_KEY = 'planer_import';

    public static function register(Router $router): void
    {
        $router->get('/termine', [self::class, 'index']);
        $router->get('/termine/neu', [self::class, 'createForm']);
        $router->post('/termine/neu', [self::class, 'create']);
        $router->get('/termine/bearbeiten', [self::class, 'editForm']);
        $router->post('/termine/bearbeiten', [self::class, 'update']);
        $router->post('/termine/loeschen', [self::class, 'delete']);
        $router->get('/termine/import', [self::class, 'importForm']);
        $router->post('/termine/import', [self::class, 'importPreview']);
        $router->post('/termine/import/uebernehmen', [self::class, 'importSave']);
    }

    // Overview: ?ansicht=monat|quartal|halbjahr|jahr&ab=YYYY-MM (default: this month)&kategorie=<Kalender::FILTER>
    public static function index(): void
    {
        $ansicht = isset(Kalender::ANSICHTEN[$_GET['ansicht'] ?? '']) ? $_GET['ansicht'] : 'monat';
        $ab = is_string($_GET['ab'] ?? null) && preg_match('/^(\d{4})-(\d{2})$/', $_GET['ab'], $m) && checkdate((int) $m[2], 1, (int) $m[1])
            ? new DateTimeImmutable($_GET['ab'] . '-01') : new DateTimeImmutable('today');
        $kategorie = is_string($_GET['kategorie'] ?? null) && isset(Kalender::FILTER[$_GET['kategorie']]) ? $_GET['kategorie'] : '';
        [$from, $to] = Kalender::zeitraum($ansicht, $ab);
        $f = $from->format('Y-m-d');
        $t = $to->format('Y-m-d');
        View::render('termine/index', [
            'title' => 'Termine',
            'ansicht' => $ansicht,
            'from' => $from,
            'to' => $to,
            'kategorie' => $kategorie,
            'tage' => Kalender::filtern(Kalender::tage($from, $to, TermineRepository::eigene($f, $t), TermineRepository::kursTermine($f, $t),
                TermineRepository::auftraege($f, $t), TermineRepository::kinder()), $kategorie),
        ]);
    }

    public static function createForm(): void
    {
        $date = is_string($_GET['datum'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['datum']) ? $_GET['datum'] : date('Y-m-d');
        self::renderForm(null, ['datum' => $date], []);
    }

    public static function create(): void
    {
        $r = TerminValidator::validate($_POST);
        if ($r['errors']) {
            self::renderForm(null, $r['data'], $r['errors'], 422);
            return;
        }
        TermineRepository::create($r['data']);
        Session::flash('ok', 'Termin eingetragen.');
        Router::redirect(self::monthUrl($r['data']['datum']));
    }

    public static function editForm(): void
    {
        $entry = self::findOr404((int) ($_GET['id'] ?? 0));
        self::renderForm((int) $entry['id'], $entry, []);
    }

    public static function update(): void
    {
        $entry = self::findOr404((int) ($_GET['id'] ?? 0));
        $r = TerminValidator::validate($_POST);
        if ($r['errors']) {
            self::renderForm((int) $entry['id'], $r['data'], $r['errors'], 422);
            return;
        }
        TermineRepository::update((int) $entry['id'], $entry, $r['data']);
        Session::flash('ok', 'Termin gespeichert.');
        Router::redirect(self::monthUrl($r['data']['datum']));
    }

    public static function delete(): void
    {
        $entry = TermineRepository::find((int) ($_POST['id'] ?? 0));
        if ($entry !== null) {
            TermineRepository::delete((int) $entry['id']);
            Session::flash('ok', 'Termin gelöscht.');
        }
        Router::redirect($entry !== null ? self::monthUrl($entry['datum']) : '/termine');
    }

    public static function importForm(): void
    {
        unset($_SESSION[self::IMPORT_KEY]);
        View::render('termine/import', ['title' => 'Planer übernehmen', 'vorschau' => null, 'jahr' => (int) date('Y'), 'vorhanden' => 0, 'fehler' => null]);
    }

    // Upload of the planner tab as CSV → preview (kept in the session until confirmed)
    public static function importPreview(): void
    {
        $year = (int) ($_POST['jahr'] ?? date('Y'));
        $file = $_FILES['datei'] ?? null;
        $error = null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            $error = 'Bitte die CSV-Datei des Jahres-Reiters auswählen.';
        } else {
            $rows = PlanerImport::csvRows((string) file_get_contents((string) $file['tmp_name']));
            $year = PlanerImport::year($rows) ?? $year;
            $month = mb_strtolower(trim((string) ($rows[1][0] ?? '')));
            if ($month !== 'januar') {
                $error = 'Das sieht nicht nach dem Planer aus: In Zeile 2 sollte „Januar“ stehen (Reiter als CSV herunterladen).';
            }
        }
        if ($error !== null || $year < 2000 || $year > 2100) {
            View::render('termine/import', ['title' => 'Planer übernehmen', 'vorschau' => null, 'jahr' => $year, 'vorhanden' => 0, 'fehler' => $error ?? 'Ungültiges Jahr.'], 422);
            return;
        }
        $result = PlanerImport::parse($rows, $year);
        $_SESSION[self::IMPORT_KEY] = ['jahr' => $year, 'eintraege' => $result['eintraege']];
        View::render('termine/import', ['title' => 'Planer übernehmen', 'vorschau' => $result, 'jahr' => $year,
            'vorhanden' => TermineRepository::importedCount($year), 'fehler' => null]);
    }

    public static function importSave(): void
    {
        $pending = $_SESSION[self::IMPORT_KEY] ?? null;
        unset($_SESSION[self::IMPORT_KEY]);
        if (!is_array($pending) || !isset($pending['jahr'], $pending['eintraege'])) {
            Session::flash('error', 'Die Vorschau ist abgelaufen – bitte die Datei erneut hochladen.');
            Router::redirect('/termine/import');
        }
        [$inserted, $removed] = TermineRepository::importPlaner((int) $pending['jahr'], $pending['eintraege'], ($_POST['ersetzen'] ?? '') === '1');
        Session::flash('ok', "$inserted Termine aus dem Planer {$pending['jahr']} übernommen." . ($removed ? " $removed aus der früheren Übernahme ersetzt." : ''));
        Router::redirect('/termine?ansicht=jahr&ab=' . $pending['jahr'] . '-01');
    }

    private static function renderForm(?int $id, array $entry, array $errors, int $status = 200): void
    {
        View::render('termine/form', ['title' => $id === null ? 'Neuer Termin' : 'Termin bearbeiten', 'id' => $id, 'eintrag' => $entry, 'errors' => $errors], $status);
    }

    private static function monthUrl(?string $date): string
    {
        return '/termine?ansicht=monat&ab=' . substr($date ?? date('Y-m-d'), 0, 7);
    }

    private static function findOr404(int $id): array
    {
        $entry = TermineRepository::find($id);
        if ($entry === null) {
            View::render('error', ['title' => 'Nicht gefunden', 'message' => 'Diesen Termin gibt es nicht.'], 404);
            exit;
        }
        return $entry;
    }
}
