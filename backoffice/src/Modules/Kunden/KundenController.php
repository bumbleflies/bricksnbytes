<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Audit;
use Backoffice\Modules\Auftraege\AuftragRepository;
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
        $router->get('/kunden/schnellansicht', [self::class, 'quickView']);
        $router->get('/kunden/neu', [self::class, 'createForm']);
        $router->post('/kunden/neu', [self::class, 'create']);
        $router->get('/kunden/bearbeiten', [self::class, 'editForm']);
        $router->post('/kunden/bearbeiten', [self::class, 'update']);
        $router->get('/kunden/loeschen', [self::class, 'confirmDelete']);
        $router->post('/kunden/loeschen', [self::class, 'delete']);
        $router->get('/kunden/export', [self::class, 'export']);
        $router->post('/kunden/newsletter', [self::class, 'toggleNewsletter']);
        $router->post('/kunden/typ', [self::class, 'changeType']);
        $router->post('/kunden/typ-auswahl', [self::class, 'changeTypeBulk']);
        $router->get('/kunden/namen', [self::class, 'namesPreview']);
        $router->post('/kunden/namen', [self::class, 'namesSave']);
    }

    public static function index(): void
    {
        [$q, $typ, $newsletter, $status, $ort] = self::listFilter($_GET);
        $sort = array_key_exists(self::str($_GET['sort'] ?? ''), KundenRepository::SORTS) ? self::str($_GET['sort']) : ($typ === 'privat' ? 'nachname' : 'name');
        $dir = self::str($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
        $page = max(1, (int) ($_GET['seite'] ?? 1));

        $result = KundenRepository::search($q, $typ, $sort, $dir, $page, self::PER_PAGE, $newsletter, $status, $ort);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));
        if ($page > $pages) {
            $page = $pages;
            $result = KundenRepository::search($q, $typ, $sort, $dir, $page, self::PER_PAGE, $newsletter, $status, $ort);
        }
        $ownEmail = Verteiler::ownEmail();

        View::render('kunden/index', [
            'title' => 'Kunden',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'typeOptions' => self::typeOptions(KundenRepository::countsByType(), $typ),
            'places' => KundenRepository::placeCounts(),
            'q' => $q,
            'typ' => $typ,
            'sort' => $sort,
            'dir' => $dir,
            'page' => $page,
            'pages' => $pages,
            'newsletter' => $newsletter,
            'status' => $status,
            'ort' => $ort,
            // Recipients for the mail buttons: every filtered customer (all pages) and all subscribers
            'filteredAddresses' => Verteiler::clean(KundenRepository::filteredAddresses($q, $typ, $newsletter, $status, $ort)),
            'newsletterAddresses' => Verteiler::clean(KundenRepository::newsletterAddresses()),
            'ownEmail' => $ownEmail,
        ]);
    }

    /**
     * Typ dropdown: the types that occur (plural label and count), alphabetical with umlauts sorted
     * like their base letter; the current type stays listed even if no customer has it any more
     *
     * @return array<string, array{0: string, 1: int}>
     */
    private static function typeOptions(array $counts, ?string $current): array
    {
        $options = [];
        foreach (Kundentypen::ALLE as $key => [, $plural]) {
            if (($counts[$key] ?? 0) > 0 || $key === $current) {
                $options[$key] = [$plural, (int) ($counts[$key] ?? 0)];
            }
        }
        $sortKey = static fn (string $label): string => strtr(mb_strtolower($label), ['ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss']);
        uasort($options, static fn ($a, $b) => strcmp($sortKey($a[0]), $sortKey($b[0])));
        return $options;
    }

    // Ja/Nein switch in the list: saves at once and returns to the same list view
    public static function toggleNewsletter(): void
    {
        $kunde = KundenRepository::find((int) ($_POST['id'] ?? 0));
        $back = self::listUrl($_POST['zurueck'] ?? null) ?? '/kunden';
        if ($kunde === null) {
            Session::flash('error', 'Diesen Kunden gibt es nicht mehr.');
            Router::redirect($back);
        }
        $on = self::str($_POST['wert'] ?? '') === 'ja';
        KundenRepository::setNewsletter((int) $kunde['id'], $on);
        Session::flash('ok', 'Newsletter für „' . Kundentypen::displayName($kunde) . '“: ' . ($on ? 'Ja' : 'Nein') . '.');
        Router::redirect($back);
    }

    // Type dropdown in the list: saves at once and returns to the same list view
    public static function changeType(): void
    {
        $kunde = KundenRepository::find((int) ($_POST['id'] ?? 0));
        $back = self::listUrl($_POST['zurueck'] ?? null) ?? '/kunden';
        $typ = self::str($_POST['typ'] ?? '');
        if ($kunde === null) {
            Session::flash('error', 'Diesen Kunden gibt es nicht mehr.');
            Router::redirect($back);
        }
        if (!Kundentypen::valid($typ) || $typ === $kunde['typ']) {
            Router::redirect($back);
        }
        $name = Kundentypen::displayName($kunde);
        $problem = self::typeChangeProblem($kunde, $typ);
        if ($problem !== null) {
            $why = $problem === 'kinder' ? 'hat Kinder – die gibt es nur bei Eltern.' : 'hat keinen Namen der Einrichtung.';
            Session::flash('error', "„{$name}“ {$why} Typ bitte über „Bearbeiten“ ändern.");
            Router::redirect($back);
        }
        KundenRepository::patch((int) $kunde['id'], ['typ' => $typ]);
        Session::flash('ok', "Typ für „{$name}“: " . Kundentypen::label($typ) . '.');
        Router::redirect($back);
    }

    // "Typ für Auswahl ändern": the ticked customers, or every customer of the filtered list (all pages)
    public static function changeTypeBulk(): void
    {
        $back = self::listUrl($_POST['zurueck'] ?? null) ?? '/kunden';
        $typ = self::str($_POST['neuer_typ'] ?? '');
        if (!Kundentypen::valid($typ)) {
            Session::flash('error', 'Bitte einen Typ wählen.');
            Router::redirect($back);
        }
        $ids = self::str($_POST['alle'] ?? '') === '1'
            ? KundenRepository::filteredIds(...self::listFilter($_POST))
            : array_values(array_unique(array_filter(array_map('intval', explode(',', self::str($_POST['ids'] ?? ''))))));

        $changed = 0;
        $skipped = [];
        foreach ($ids as $id) {
            $kunde = KundenRepository::find($id);
            if ($kunde === null || $kunde['typ'] === $typ) {
                continue;
            }
            $problem = self::typeChangeProblem($kunde, $typ);
            if ($problem !== null) {
                $skipped[$problem] = ($skipped[$problem] ?? 0) + 1;
                continue;
            }
            KundenRepository::patch($id, ['typ' => $typ]);
            $changed++;
        }

        $text = ($changed === 1 ? '1 Kunde' : "$changed Kunden") . ' auf „' . Kundentypen::label($typ) . '“ umgestellt.';
        $reasons = ['kinder' => 'mit Kindern (die gibt es nur bei Eltern)', 'name' => 'ohne Namen der Einrichtung'];
        foreach ($skipped as $problem => $n) {
            $text .= " $n übersprungen " . $reasons[$problem] . '.';
        }
        Session::flash($skipped && $changed === 0 ? 'error' : 'ok', $text . ($skipped ? ' Diese bitte über „Bearbeiten“ ändern.' : ''));
        Router::redirect($back);
    }

    /** Same rules as the form: children only for families, organisations need their own name ("kinder" / "name") */
    private static function typeChangeProblem(array $kunde, string $typ): ?string
    {
        if (!Kundentypen::isPrivat($typ) && $kunde['kinder']) {
            return 'kinder';
        }
        if (!Kundentypen::isPrivat($typ) && trim((string) $kunde['name']) === '') {
            return 'name';
        }
        return null;
    }

    public static function show(): void
    {
        $kunde = self::findOr404();
        View::render('kunden/show', [
            'title' => Kundentypen::displayName($kunde),
            'kunde' => $kunde,
            'auftraege' => AuftragRepository::forKunde((int) $kunde['id']),
            'protokoll' => Audit::forRecord('kunden', (int) $kunde['id'], 10),
        ]);
    }

    // Eye icon in the list: read-only summary for the modal (HTML fragment, no layout)
    public static function quickView(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $kunde = $id > 0 ? KundenRepository::find($id) : null;
        if ($kunde === null) {
            View::partial('kunden/quick-missing', [], 404);
            return;
        }
        View::partial('kunden/quick', ['kunde' => $kunde, 'zurueck' => self::listUrl($_GET['zurueck'] ?? null)]);
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
        if (isset($_POST['_typ_wechseln']) || isset($_POST['_kind_neu'])) {
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
        self::renderForm((int) $kunde['id'], $kunde, $kunde['ansprechpartner'], $kunde['kinder'], $consents, []);
    }

    public static function update(): void
    {
        $kunde = self::findOr404();
        $id = (int) $kunde['id'];
        // Fields the form does not show (family: name, e-mail, phone) keep their stored values
        $r = KundenValidator::keepUnsentFields(KundenValidator::validate($_POST, $id), $_POST, $kunde);
        // A type change in the select or "+ Kind hinzufügen" without JavaScript only re-renders the form
        if (isset($_POST['_typ_wechseln']) || isset($_POST['_kind_neu'])) {
            self::renderForm($id, $r['data'] + $kunde, $r['contacts'], $r['kinder'], $r['consents'], []);
            return;
        }
        if ($r['errors']) {
            self::renderForm($id, $r['data'] + $kunde, $r['contacts'], $r['kinder'], $r['consents'], $r['errors'], 422);
            return;
        }
        KundenRepository::save($id, $r['data'], $r['contacts'], Kundentypen::isPrivat($r['data']['typ']) ? $r['kinder'] : [], $r['consents']);
        Session::flash('ok', 'Änderungen gespeichert.');
        // Opened via the pencil in the list: back to that list view (search, filter, page kept)
        Router::redirect(self::listUrl($_POST['zurueck'] ?? null) ?? '/kunden/ansehen?id=' . $id);
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
        [$q, $typ, $newsletter, $status, $ort] = self::listFilter($_GET);
        $rows = KundenRepository::exportRows($q, $typ, $status, $newsletter, $ort);

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

    private static function renderForm(?int $id, array $kunde, array $contacts, array $kinder, array $consents, array $errors, int $status = 200): void
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
        // Families: only the stored children; an empty form for one more only on request
        // ("+ Kind hinzufügen" without JavaScript, or the button on the detail page)
        $newChild = $privat && (isset($_POST['_kind_neu']) || ($_GET['kind'] ?? null) === 'neu');

        View::render('kunden/form', [
            'title' => $id === null ? 'Neuer Kunde' : 'Kunde bearbeiten',
            'id' => $id,
            'kunde' => $kunde,
            'contacts' => $contactRows,
            'kinder' => $childRows,
            'newChild' => $newChild,
            'consents' => $consents,
            'errors' => $errors,
            // Where "Abbrechen"/"Zurück" and saving lead when the form was opened from the list
            'zurueck' => self::listUrl($_GET['zurueck'] ?? $_POST['zurueck'] ?? null),
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

    /**
     * Filter of the customer list from URL or form parameters: search and the dropdowns
     * Typ, Newsletter, Status, Ort ("Alle" = empty = no filter)
     *
     * @return array{0: string, 1: ?string, 2: ?string, 3: ?string, 4: ?string}
     */
    private static function listFilter(array $input): array
    {
        $typ = self::str($input['typ'] ?? '');
        $newsletter = self::str($input['newsletter'] ?? '');
        $status = self::str($input['status'] ?? '');
        $ort = self::str($input['ort'] ?? '');
        return [
            self::str($input['q'] ?? ''),
            Kundentypen::valid($typ) ? $typ : null,
            in_array($newsletter, KundenRepository::NEWSLETTER_FILTER, true) ? $newsletter : null,
            $status === 'ohne' || Kundentypen::validStatus($status) ? $status : null,
            $ort !== '' ? mb_substr($ort, 0, 100) : null,
        ];
    }

    /** A customer-list URL ("/kunden?typ=schule&q=…") to return to — never anything else (no open redirect) */
    public static function listUrl(mixed $value): ?string
    {
        $value = self::str($value);
        return preg_match('~^/kunden(\?[^\s<>"\'#]*)?$~', $value) ? $value : null;
    }

    private static function str(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
