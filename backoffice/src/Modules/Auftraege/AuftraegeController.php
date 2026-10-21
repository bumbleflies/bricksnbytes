<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auftraege;

use Backoffice\Auth;
use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;
use Backoffice\Modules\Kunden\Kundentypen;

final class AuftraegeController
{
    private const PER_PAGE = 25;

    public static function register(Router $router): void
    {
        $router->get('/auftraege', [self::class, 'index']);
        $router->get('/auftraege/ansehen', [self::class, 'show']);
        $router->get('/auftraege/schnellansicht', [self::class, 'quickView']);
        $router->get('/auftraege/neu', [self::class, 'createForm']);
        $router->post('/auftraege/neu', [self::class, 'create']);
        $router->get('/auftraege/bearbeiten', [self::class, 'editForm']);
        $router->post('/auftraege/bearbeiten', [self::class, 'update']);
        $router->get('/auftraege/datei', [self::class, 'download']);
        $router->post('/auftraege/datei/loeschen', [self::class, 'deleteFile']);
        $router->get('/auftraege/loeschen', [self::class, 'confirmDelete']);
        $router->post('/auftraege/loeschen', [self::class, 'delete']);
    }

    public static function index(): void
    {
        $page = max(1, (int) ($_GET['seite'] ?? 1));
        $result = AuftragRepository::page($page, self::PER_PAGE);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        View::render('auftraege/index', ['title' => 'Aufträge', 'rows' => $result['rows'], 'total' => $result['total'], 'page' => min($page, $pages), 'pages' => $pages]);
    }

    public static function show(): void
    {
        $auftrag = self::findOr404();
        View::render('auftraege/show', [
            'title' => $auftrag['titel'],
            'auftrag' => $auftrag,
            'dateien' => AuftragRepository::files((int) $auftrag['id']),
            'verlauf' => AuftragRepository::history((int) $auftrag['id']),
        ]);
    }

    // Read-only summary for the modal in the list (HTML fragment without the layout)
    public static function quickView(): void
    {
        $auftrag = AuftragRepository::find((int) ($_GET['id'] ?? 0));
        if ($auftrag === null) {
            View::partial('kunden/quick-missing', [], 404);
            return;
        }
        View::partial('auftraege/quick', [
            'auftrag' => $auftrag,
            'dateien' => AuftragRepository::files((int) $auftrag['id']),
            'verlauf' => AuftragRepository::history((int) $auftrag['id']),
        ]);
    }

    public static function createForm(): void
    {
        $kundeId = (int) ($_GET['kunde'] ?? 0);
        $data = ['status' => 'anfrage', 'preis_einheit' => 'gesamt', 'kunde_id' => AuftragRepository::customerExists($kundeId) ? $kundeId : null];
        self::renderForm(null, $data, [], null);
    }

    public static function create(): void
    {
        $kundeId = (int) ($_POST['kunde_id'] ?? 0);
        $r = self::validate($kundeId);
        // "Kunde übernehmen": only reload the contact persons of the chosen customer
        if (isset($_POST['_kunde_wechseln'])) {
            self::renderForm(null, $r['data'], [], $r['kommentar']);
            return;
        }
        if ($r['errors']) {
            self::renderForm(null, $r['data'], $r['errors'], $r['kommentar'], 422);
            return;
        }
        $id = AuftragRepository::create($r['data'], $r['kommentar'], Auth::userName());
        Session::flash(...self::saveUploads($id, 'Auftrag angelegt.'));
        Router::redirect('/auftraege/ansehen?id=' . $id);
    }

    public static function editForm(): void
    {
        $auftrag = self::findOr404();
        self::renderForm((int) $auftrag['id'], $auftrag, [], null);
    }

    public static function update(): void
    {
        $auftrag = self::findOr404();
        $id = (int) $auftrag['id'];
        $kundeId = (int) ($_POST['kunde_id'] ?? 0);
        $r = self::validate($kundeId);
        if (isset($_POST['_kunde_wechseln'])) {
            self::renderForm($id, $r['data'] + $auftrag, [], $r['kommentar']);
            return;
        }
        if ($r['errors']) {
            self::renderForm($id, $r['data'] + $auftrag, $r['errors'], $r['kommentar'], 422);
            return;
        }
        AuftragRepository::update($id, $auftrag, $r['data'], $r['kommentar'], Auth::userName());
        Session::flash(...self::saveUploads($id, 'Änderungen gespeichert.'));
        Router::redirect('/auftraege/ansehen?id=' . $id);
    }

    public static function download(): void
    {
        $file = AuftragRepository::file((int) ($_GET['id'] ?? 0));
        if ($file === null) {
            View::render('error', ['title' => 'Nicht gefunden', 'message' => 'Diese Datei gibt es nicht mehr.'], 404);
            return;
        }
        $name = Auftragsdaten::dateiname($file['dateiname']);
        $ascii = (string) preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);
        header('Content-Type: ' . $file['mime_typ']);
        header('Content-Length: ' . strlen($file['inhalt']));
        header('Content-Disposition: ' . (Auftragsdaten::inline($file['mime_typ']) ? 'inline' : 'attachment')
            . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        // A shown file (PDF, image) may not run scripts or reach the backoffice
        header("Content-Security-Policy: sandbox; default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; object-src 'self'");
        header('Cache-Control: private, no-store');
        echo $file['inhalt'];
    }

    public static function deleteFile(): void
    {
        $auftragId = (int) ($_POST['auftrag'] ?? 0);
        $ok = AuftragRepository::deleteFile((int) ($_POST['id'] ?? 0), $auftragId);
        Session::flash($ok ? 'ok' : 'error', $ok ? 'Datei gelöscht.' : 'Diese Datei gibt es nicht mehr.');
        Router::redirect('/auftraege/bearbeiten?id=' . $auftragId . '#dateien');
    }

    public static function confirmDelete(): void
    {
        $auftrag = self::findOr404();
        View::render('auftraege/delete', ['title' => 'Auftrag löschen', 'auftrag' => $auftrag, 'dateien' => AuftragRepository::files((int) $auftrag['id'])]);
    }

    public static function delete(): void
    {
        $auftrag = AuftragRepository::find((int) ($_POST['id'] ?? 0));
        if ($auftrag !== null) {
            AuftragRepository::delete((int) $auftrag['id']);
            Session::flash('ok', 'Auftrag mit allen Dateien gelöscht.');
        }
        Router::redirect('/auftraege');
    }

    /**
     * Stores the files of the form field "dateien[]" (after the order is saved). Rejected files are
     * listed in the message; the order itself stays saved.
     *
     * @return array{0: string, 1: string} flash type and text
     */
    private static function saveUploads(int $auftragId, string $message): array
    {
        $files = $_FILES['dateien'] ?? null;
        $saved = 0;
        $problems = [];
        if (is_array($files) && is_array($files['name'] ?? null)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            foreach ($files['name'] as $i => $original) {
                $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $name = Auftragsdaten::dateiname((string) $original);
                if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                    $problems[] = "„{$name}“ ist größer als 10 MB.";
                    continue;
                }
                $tmp = (string) ($files['tmp_name'][$i] ?? '');
                if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                    $problems[] = "„{$name}“ konnte nicht hochgeladen werden.";
                    continue;
                }
                [$mime, $problem] = Auftragsdaten::pruefeDatei($name, (int) filesize($tmp), $finfo->file($tmp) ?: null);
                if ($problem !== null) {
                    $problems[] = $problem;
                    continue;
                }
                AuftragRepository::addFile($auftragId, $name, $mime, (string) file_get_contents($tmp), Auth::userName());
                $saved++;
            }
        } elseif (($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === []) {
            $problems[] = 'Die Dateien waren zusammen zu groß.';
        }
        if ($saved > 0) {
            $message .= ' ' . ($saved === 1 ? '1 Datei hochgeladen.' : "$saved Dateien hochgeladen.");
        }
        if ($problems) {
            return ['error', $message . ' Nicht hochgeladen: ' . implode(' ', $problems)];
        }
        return ['ok', $message];
    }

    private static function renderForm(?int $id, array $auftrag, array $errors, ?string $kommentar, int $status = 200): void
    {
        $kundeId = isset($auftrag['kunde_id']) ? (int) $auftrag['kunde_id'] : 0;
        $customers = AuftragRepository::customers();
        $options = [];
        foreach ($customers as $c) {
            $options[(int) $c['id']] = Kundentypen::displayName($c) . ' (' . Kundentypen::label($c['typ']) . ')';
        }
        uasort($options, static fn ($a, $b) => strcasecmp($a, $b));
        View::render('auftraege/form', [
            'title' => $id === null ? 'Neuer Auftrag' : 'Auftrag bearbeiten',
            'id' => $id,
            'auftrag' => $auftrag,
            'kunden' => $options,
            'contacts' => $kundeId > 0 ? AuftragRepository::contacts($kundeId) : [],
            'dateien' => $id !== null ? AuftragRepository::files($id) : [],
            'kommentar' => $kommentar,
            'errors' => $errors,
        ], $status);
    }

    // Form input checked; the typed price is kept as text for showing the form again
    private static function validate(int $kundeId): array
    {
        $r = AuftragValidator::validate($_POST, self::contactIds($kundeId));
        if ($r['data']['kunde_id'] !== null && !AuftragRepository::customerExists($r['data']['kunde_id'])) {
            $r['errors']['kunde_id'] = 'Diesen Kunden gibt es nicht mehr.';
        }
        $r['data']['preis'] = is_string($_POST['preis'] ?? null) ? $_POST['preis'] : '';
        return $r;
    }

    /** @return list<int> */
    private static function contactIds(int $kundeId): array
    {
        return $kundeId > 0 ? array_map(static fn ($c) => (int) $c['id'], AuftragRepository::contacts($kundeId)) : [];
    }

    private static function findOr404(): array
    {
        $auftrag = AuftragRepository::find((int) ($_GET['id'] ?? 0));
        if ($auftrag === null) {
            View::render('error', ['title' => 'Nicht gefunden', 'message' => 'Diesen Auftrag gibt es nicht.'], 404);
            exit;
        }
        return $auftrag;
    }
}
