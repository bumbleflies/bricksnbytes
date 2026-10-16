<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Audit;
use Backoffice\Router;
use Backoffice\Session;
use Backoffice\View;

final class KundenController
{
    private const PER_PAGE = 25;

    public static function register(Router $router): void
    {
        $router->get('/kunden', [self::class, 'index']);
        $router->get('/kunden/ansehen', [self::class, 'show']);
        $router->get('/kunden/neu', [self::class, 'createForm']);
        $router->post('/kunden/neu', [self::class, 'create']);
        $router->get('/kunden/bearbeiten', [self::class, 'editForm']);
        $router->post('/kunden/bearbeiten', [self::class, 'update']);
        $router->get('/kunden/loeschen', [self::class, 'confirmDelete']);
        $router->post('/kunden/loeschen', [self::class, 'delete']);
        $router->get('/kunden/export', [self::class, 'export']);
        $router->post('/kunden/newsletter', [self::class, 'toggleNewsletter']);
        $router->get('/kunden/namen', [self::class, 'namesPreview']);
        $router->post('/kunden/namen', [self::class, 'namesSave']);
    }

    public static function index(): void
    {
        $q = self::str($_GET['q'] ?? '');
        $typ = Kundentypen::valid(self::str($_GET['typ'] ?? '')) ? self::str($_GET['typ']) : null;
        $sort = array_key_exists(self::str($_GET['sort'] ?? ''), KundenRepository::SORTS) ? self::str($_GET['sort']) : ($typ === 'privat' ? 'nachname' : 'name');
        $dir = self::str($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $page = max(1, (int) ($_GET['seite'] ?? 1));
        $newsletter = in_array(self::str($_GET['newsletter'] ?? ''), KundenRepository::NEWSLETTER_FILTER, true) ? self::str($_GET['newsletter']) : null;
        $status = self::statusFilter();

        $result = KundenRepository::search($q, $typ, $sort, $dir, $page, self::PER_PAGE, $newsletter, $status);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        if ($page > $pages) {
            $page = $pages;
            $result = KundenRepository::search($q, $typ, $sort, $dir, $page, self::PER_PAGE, $newsletter, $status);
        }
        $ownEmail = Verteiler::ownEmail();

        View::render('kunden/index', [
            'title' => 'Kunden',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'counts' => KundenRepository::countsByType(),
            'q' => $q,
            'typ' => $typ,
            'sort' => $sort,
            'dir' => $dir,
            'page' => $page,
            'pages' => $pages,
            'newsletter' => $newsletter,
            'status' => $status,
            // Recipients for the mail buttons: every filtered customer (all pages) and all subscribers
            'filteredAddresses' => Verteiler::clean(KundenRepository::filteredAddresses($q, $typ, $newsletter, $status)),
            'newsletterAddresses' => Verteiler::clean(KundenRepository::newsletterAddresses()),
            'ownEmail' => $ownEmail,
        ]);
    }

    // Ja/Nein switch in the list: saves at once and returns to the same list view
    public static function toggleNewsletter(): void
    {
        $kunde = KundenRepository::find((int) ($_POST['id'] ?? 0));
        $back = self::str($_POST['zurueck'] ?? '');
        // Only back into the customer list, never to another site
        $back = preg_match('#^/kunden(\?[^\s]*)?$#', $back) ? $back : '/kunden';
        if ($kunde === null) {
            Session::flash('error', 'Diesen Kunden gibt es nicht mehr.');
            Router::redirect($back);
        }
        $on = self::str($_POST['wert'] ?? '') === 'ja';
        KundenRepository::setNewsletter((int) $kunde['id'], $on);
        Session::flash('ok', 'Newsletter für „' . Kundentypen::displayName($kunde) . '“: ' . ($on ? 'Ja' : 'Nein') . '.');
        Router::redirect($back);
    }

    public static function show(): void
    {
        $kunde = self::findOr404();
        View::render('kunden/show', [
            'title' => Kundentypen::displayName($kunde),
            'kunde' => $kunde,
            'vorschlag' => self::nameSuggestion($kunde),
            'protokoll' => Audit::forRecord('kunden', (int) $kunde['id'], 10),
        ]);
    }

    public static function createForm(): void
    {
        $typ = self::str($_GET['typ'] ?? '');
        if (!Kundentypen::valid($typ)) {
            View::render('kunden/choose', ['title' => 'Neuer Kunde']);
            return;
        }
        self::renderForm(null, ['typ' => $typ], [], [], [], []);
    }

    public static function create(): void
    {
        $r = KundenValidator::validate($_POST, null);
        if (isset($_POST['_typ_wechseln'])) {
            self::renderForm(null, $r['data'], $r['contacts'], $r['kinder'], $r['consents'], []);
            return;
        }
        if ($r['errors']) {
            self::renderForm(null, $r['data'], $r['contacts'], $r['kinder'], $r['consents'], $r['errors'], 422);
            return;
        }
        $id = KundenRepository::save(null, $r['data'], $r['contacts'], Kundentypen::isPrivat($r['data']['typ']) ? $r['kinder'] : [], $r['consents']);
        Session::flash('ok', 'Kunde angelegt.');
        Router::redirect('/kunden/ansehen?id=' . $id);
    }

    public static function editForm(): void
    {
        $kunde = self::findOr404();
        $consents = array_map(static fn ($c) => ['erteilt_am' => $c['erteilt_am'], 'widerrufen_am' => $c['widerrufen_am']], $kunde['einwilligungen']);
        // Empty names: prefilled from the e-mail address, saved only when the user saves the form
        $vorschlag = self::nameSuggestion($kunde);
        if ($vorschlag !== null) {
            $kunde['vorname'] = $vorschlag['vorname'];
            $kunde['nachname'] = $vorschlag['nachname'];
        }
        self::renderForm((int) $kunde['id'], $kunde, $kunde['ansprechpartner'], $kunde['kinder'], $consents, [], 200, $vorschlag['herkunft'] ?? null);
    }

    public static function update(): void
    {
        $kunde = self::findOr404();
        $id = (int) $kunde['id'];
        // A type change in the select only re-renders the form for the new type
        if (isset($_POST['_typ_wechseln'])) {
            $r = KundenValidator::validate($_POST, $id);
            self::renderForm($id, $r['data'] + $kunde, $r['contacts'], $r['kinder'], $r['consents'], []);
            return;
        }
        $r = KundenValidator::validate($_POST, $id);
        if ($r['errors']) {
            self::renderForm($id, $r['data'] + $kunde, $r['contacts'], $r['kinder'], $r['consents'], $r['errors'], 422);
            return;
        }
        KundenRepository::save($id, $r['data'], $r['contacts'], Kundentypen::isPrivat($r['data']['typ']) ? $r['kinder'] : [], $r['consents']);
        Session::flash('ok', 'Änderungen gespeichert.');
        Router::redirect('/kunden/ansehen?id=' . $id);
    }

    // "Namen aus E-Mail übernehmen": every customer without names that gets a suggestion, at most one page
    private const NAMES_PER_PAGE = 200;

    public static function namesPreview(): void
    {
        $rows = [];
        foreach (KundenRepository::withoutNames() as $kunde) {
            $vorschlag = self::nameSuggestion($kunde);
            if ($vorschlag !== null) {
                $rows[] = $kunde + ['vorschlag' => $vorschlag];
            }
        }
        View::render('kunden/namen', [
            'title' => 'Namen aus E-Mail übernehmen',
            'rows' => array_slice($rows, 0, self::NAMES_PER_PAGE),
            'total' => count($rows),
        ]);
    }

    public static function namesSave(): void
    {
        $saved = 0;
        $skipped = 0;
        $selected = is_array($_POST['uebernehmen'] ?? null) ? $_POST['uebernehmen'] : [];
        $names = is_array($_POST['namen'] ?? null) ? $_POST['namen'] : [];
        foreach (array_keys($selected) as $id) {
            $row = is_array($names[$id] ?? null) ? $names[$id] : [];
            $clean = static fn ($v) => is_string($v) && trim($v) !== '' ? mb_substr(trim((string) preg_replace('/\s+/u', ' ', $v)), 0, 100) : null;
            $vorname = $clean($row['vorname'] ?? null);
            $nachname = $clean($row['nachname'] ?? null);
            // Only while both are still empty: names entered in the meantime are never overwritten
            if (($vorname !== null || $nachname !== null) && KundenRepository::setNamesIfEmpty((int) $id, $vorname, $nachname)) {
                $saved++;
            } else {
                $skipped++;
            }
        }
        Session::flash('ok', ($saved === 1 ? '1 Name übernommen.' : "$saved Namen übernommen.")
            . ($skipped > 0 ? " $skipped übersprungen (inzwischen ausgefüllt oder leer)." : ''));
        Router::redirect('/kunden/namen');
    }

    /**
     * While first and last name are empty: the main contact's name if there is one, else a suggestion
     * from the e-mail (the customer's, else the main contact's).
     *
     * @return array{vorname: ?string, nachname: ?string, herkunft: string}|null
     */
    private static function nameSuggestion(array $kunde): ?array
    {
        if (($kunde['vorname'] ?? null) !== null || ($kunde['nachname'] ?? null) !== null) {
            return null;
        }
        if (($kunde['hk_vorname'] ?? null) !== null || ($kunde['hk_nachname'] ?? null) !== null) {
            return ['vorname' => $kunde['hk_vorname'], 'nachname' => $kunde['hk_nachname'], 'herkunft' => 'dem Hauptkontakt'];
        }
        $fromEmail = Namensvorschlag::fromEmail($kunde['mail_adresse'] ?? $kunde['email'] ?? null);
        return $fromEmail === null ? null : $fromEmail + ['herkunft' => 'der E-Mail-Adresse'];
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
        Session::flash('ok', 'Kunde mit Ansprechpartnern, Kindern und Einwilligungen gelöscht.');
        Router::redirect('/kunden');
    }

    public static function export(): void
    {
        $q = self::str($_GET['q'] ?? '');
        $typ = Kundentypen::valid(self::str($_GET['typ'] ?? '')) ? self::str($_GET['typ']) : null;
        $rows = KundenRepository::exportRows($q, $typ, self::statusFilter());

        $consent = static function (?array $c): string {
            if (!$c || !$c['erteilt_am']) {
                return '';
            }
            return $c['widerrufen_am']
                ? 'widerrufen ' . self::germanDate($c['widerrufen_am'])
                : 'ja seit ' . self::germanDate($c['erteilt_am']);
        };

        $lines = [];
        foreach ($rows as $r) {
            $kinder = implode(', ', array_map(
                static fn ($k) => trim($k['vorname'] . ' ' . ($k['nachname'] ?? ''))
                    . ($k['geburtsdatum'] ? ' (' . self::germanDate($k['geburtsdatum']) . ')' : ($k['geburtsjahr'] ? ' (' . $k['geburtsjahr'] . ')' : '')),
                $r['kinder']
            ));
            $lines[] = [
                Kundentypen::label($r['typ']), $r['name'], Kundentypen::statusLabel($r['status']), $r['hk_vorname'], $r['hk_nachname'], $r['hk_rolle'],
                $r['email'], $r['telefon'], $r['adresse'], $r['plz'], $r['ort'], $r['quelle'], $kinder,
                $consent($r['einwilligungen']['datenschutz'] ?? null),
                $consent($r['einwilligungen']['foto_video'] ?? null),
                $consent($r['einwilligungen']['newsletter'] ?? null),
                self::germanDate($r['erstellt_am']), self::germanDate($r['geaendert_am']),
            ];
        }
        self::sendCsv('kunden', ['Typ', 'Name (Einrichtung)', 'Status', 'Kontakt Vorname', 'Kontakt Nachname', 'Rolle', 'E-Mail', 'Telefon',
            'Adresse', 'PLZ', 'Ort', 'Quelle', 'Kinder', 'Einwilligung Datenschutz', 'Einwilligung Fotos/Video', 'Newsletter',
            'Angelegt am', 'Geändert am'], $lines);
    }

    /** Shared by the customer and children export */
    public static function sendCsv(string $name, array $header, array $lines): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel opens UTF-8 (umlauts) correctly
        fputcsv($out, $header, ';', '"', '');
        foreach ($lines as $line) {
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

    public static function germanDate(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $date = new \DateTimeImmutable($value);
        return strlen($value) > 10 ? $date->format('d.m.Y H:i') : $date->format('d.m.Y');
    }

    /** @param ?string $vorschlag where the prefilled names come from ("der E-Mail-Adresse"), null = nothing prefilled */
    private static function renderForm(?int $id, array $kunde, array $contacts, array $kinder, array $consents, array $errors, int $status = 200, ?string $vorschlag = null): void
    {
        $privat = Kundentypen::isPrivat($kunde['typ'] ?? 'privat');

        // Keep posted indexes for error lookup, then add empty slots
        $contactRows = [];
        foreach ($contacts as $i => $c) {
            $contactRows[] = $c + ['_error' => $errors["ansprechpartner.$i"] ?? null];
        }
        $minContacts = $privat ? 2 : 1;
        while (count($contactRows) < $minContacts || count($contactRows) < count($contacts) + 1) {
            $contactRows[] = ['id' => null, 'ist_hauptkontakt' => $contactRows ? 0 : 1];
        }

        $childRows = [];
        foreach ($kinder as $i => $k) {
            $childRows[] = $k + ['_error' => $errors["kinder.$i"] ?? null];
        }
        if ($privat) {
            $childRows[] = ['id' => null];
            $childRows[] = ['id' => null];
        }

        View::render('kunden/form', [
            'title' => $id === null ? 'Neuer Kunde' : 'Kunde bearbeiten',
            'id' => $id,
            'kunde' => $kunde,
            'contacts' => $contactRows,
            'kinder' => $childRows,
            'consents' => $consents,
            'errors' => $errors,
            'vorschlag' => $vorschlag,
        ], $status);
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

    // Status filter of list and export (URL ?status=<key>|ohne)
    private static function statusFilter(): ?string
    {
        $status = self::str($_GET['status'] ?? '');
        return $status === 'ohne' || Kundentypen::validStatus($status) ? $status : null;
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
