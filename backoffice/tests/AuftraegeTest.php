<?php
declare(strict_types=1);

// Orders: price parsing, file checks, form validation and the list / detail templates (no database).

use Backoffice\Modules\Auftraege\Auftragsdaten;
use Backoffice\Modules\Auftraege\AuftragValidator;

if (!defined('BACKOFFICE_ROOT')) {
    define('BACKOFFICE_ROOT', dirname(__DIR__));
}
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Backoffice\View::e($value);
    }
}

$render = static function (string $template, array $vars): string {
    $_SESSION ??= [];
    extract($vars, EXTR_SKIP);
    ob_start();
    require BACKOFFICE_ROOT . '/templates/' . $template . '.php';
    return (string) ob_get_clean();
};
$valid = ['titel' => 'Projekttag Robotik 3a', 'kunde_id' => '5', 'format' => 'projekttag', 'status' => 'anfrage'];
$auftrag = [
    'id' => 9, 'nummer' => '2026-001', 'titel' => 'Projekttag Robotik 3a', 'format' => 'projekttag', 'status' => 'angebot_verschickt',
    'kunde_id' => 5, 'kunde_typ' => 'schule', 'kunde_name' => 'Grundschule Test', 'kunde_email' => 'gs@example.de', 'kunde_telefon' => null,
    'kunde_adresse' => 'Schulweg 1', 'kunde_plz' => '80331', 'kunde_ort' => 'München', 'hk_vorname' => 'Petra', 'hk_nachname' => 'Leitung',
    'ansprechpartner_id' => 3, 'ap_vorname' => 'Tom', 'ap_nachname' => 'Lehrer', 'ap_rolle' => 'Klassenleitung', 'ap_email' => 'tom@example.de', 'ap_telefon' => null,
    'summe_cent' => 45000, 'preis_einheit' => 'pro_termin', 'klassen_anzahl' => 2, 'teilnehmer_anzahl' => 48, 'regelmaessig' => 0,
    'termine' => '12.05. 8–12 Uhr', 'einsatzort' => null, 'leistung_von' => '2026-05-12', 'leistung_bis' => null, 'notizen' => null,
];
$dateien = [
    ['id' => 21, 'dateiname' => 'Angebot_v2.pdf', 'mime_typ' => 'application/pdf', 'groesse' => 2048, 'hochgeladen_am' => '2026-10-20 10:00:00', 'hochgeladen_von' => 'admin'],
    ['id' => 20, 'dateiname' => 'Angebot_v1.pdf', 'mime_typ' => 'application/pdf', 'groesse' => 1024, 'hochgeladen_am' => '2026-10-01 10:00:00', 'hochgeladen_von' => 'admin'],
];
$verlauf = [
    ['status' => 'angebot_verschickt', 'kommentar' => 'Per Mail an die Schulleitung', 'zeit' => '2026-10-20 10:00:00', 'benutzername' => 'admin'],
    ['status' => 'anfrage', 'kommentar' => null, 'zeit' => '2026-10-01 09:00:00', 'benutzername' => 'admin'],
];

return [
    ['Preis: deutsche und einfache Schreibweisen', static function (): void {
        foreach (['450' => 45000, '450,5' => 45050, '1.234,50' => 123450, '1234.50' => 123450, '99 €' => 9900, '' => 0, '0' => 0] as $in => $cents) {
            assertSame($cents, Auftragsdaten::parsePreis((string) $in), (string) $in);
        }
        foreach (['abc', '12,345', '1.23.4', '-5', '1,2,3'] as $bad) {
            assertSame(null, Auftragsdaten::parsePreis($bad), $bad);
        }
        assertSame('1.234,50 €', Auftragsdaten::formatPreis(123450));
        assertSame('450,00 € pro Termin', Auftragsdaten::preisText(45000, 'pro_termin'));
        assertSame('450,00 €', Auftragsdaten::preisText(45000, 'gesamt'));
        assertSame('', Auftragsdaten::preisText(0, 'gesamt'));
    }],
    ['Typen und Status wie besprochen', static function (): void {
        assertSame(['Projekttag', 'Hort', 'Workshop', 'Ferienkurs', 'Kita', 'Geburtstag', 'Sonstiges'], array_values(Auftragsdaten::TYPEN));
        assertSame(['Anfrage', 'Angebot verschickt', 'Bestätigt', 'Durchgeführt', 'Abgerechnet', 'Abgesagt'], array_column(Auftragsdaten::STATUS, 0));
        assertSame('AG', Auftragsdaten::typLabel('ag'));
        assertSame('grau', Auftragsdaten::statusFarbe('unbekannt'));
    }],
    ['Dateien: erlaubte Typen, Größe, Inhalt passend zur Endung', static function (): void {
        assertSame(['application/pdf', null], Auftragsdaten::pruefeDatei('Angebot.PDF', 1000, 'application/pdf'));
        assertSame(['application/vnd.openxmlformats-officedocument.wordprocessingml.document', null], Auftragsdaten::pruefeDatei('Vertrag.docx', 1000, 'application/zip'));
        assertSame(['image/png', null], Auftragsdaten::pruefeDatei('Foto.png', 1000, 'image/png'));
        assertSame(null, Auftragsdaten::pruefeDatei('seite.html', 1000, 'text/html')[0]);
        assertSame(null, Auftragsdaten::pruefeDatei('tool.exe', 1000, 'application/x-dosexec')[0]);
        assertSame(null, Auftragsdaten::pruefeDatei('fake.pdf', 1000, 'text/html')[0], 'HTML als .pdf');
        assertSame(null, Auftragsdaten::pruefeDatei('gross.pdf', Auftragsdaten::MAX_BYTES + 1, 'application/pdf')[0]);
        assertSame(null, Auftragsdaten::pruefeDatei('leer.pdf', 0, 'application/pdf')[0]);
    }],
    ['Dateinamen: ohne Pfad und Steuerzeichen; nur PDF/Bilder im Browser', static function (): void {
        assertSame('angebot.pdf', Auftragsdaten::dateiname('../../etc/angebot.pdf'));
        assertSame('a b.pdf', Auftragsdaten::dateiname("C:\\temp\\a\"\n b.pdf"));
        assertSame('datei', Auftragsdaten::dateiname('...'));
        assertSame(true, Auftragsdaten::inline('application/pdf'));
        assertSame(false, Auftragsdaten::inline('text/plain'));
        assertSame('1,5 KB', Auftragsdaten::groesse(1536));
    }],
    ['Formular: Pflichtfelder Titel, Kunde, Typ', static function (): void {
        $r = AuftragValidator::validate([], []);
        assertSame(['titel', 'kunde_id', 'format', 'status'], array_keys($r['errors']));
    }],
    ['Formular: gültiger Auftrag mit Preis, Klassen, regelmäßig, Kommentar', static function () use ($valid): void {
        $r = AuftragValidator::validate($valid + ['ansprechpartner_id' => '3', 'preis' => '1.234,50', 'preis_einheit' => 'pro_monat', 'klassen_anzahl' => '2',
            'teilnehmer_anzahl' => '', 'regelmaessig' => '1', 'termine' => ' dienstags 14–15 Uhr ', 'leistung_von' => '2026-09-15', 'status_kommentar' => 'Zusage per Telefon'], [3, 4]);
        assertSame([], $r['errors']);
        assertSame([5, 3, 123450, 'pro_monat', 2, null, 1, 'dienstags 14–15 Uhr', '2026-09-15'],
            [$r['data']['kunde_id'], $r['data']['ansprechpartner_id'], $r['data']['summe_cent'], $r['data']['preis_einheit'], $r['data']['klassen_anzahl'],
             $r['data']['teilnehmer_anzahl'], $r['data']['regelmaessig'], $r['data']['termine'], $r['data']['leistung_von']]);
        assertSame('Zusage per Telefon', $r['kommentar']);
    }],
    ['Formular: fremder Ansprechpartner, falscher Preis, Zahlen, Datum', static function () use ($valid): void {
        $r = AuftragValidator::validate($valid + ['ansprechpartner_id' => '99', 'preis' => 'viel', 'klassen_anzahl' => 'zwei', 'leistung_von' => '2026-05-12', 'leistung_bis' => '2026-05-01'], [3]);
        assertSame(null, $r['data']['ansprechpartner_id']);
        assertSame(['preis', 'klassen_anzahl', 'leistung_bis'], array_keys($r['errors']));
        assertSame(true, isset(AuftragValidator::validate(array_merge($valid, ['format' => 'ag']), [])['errors']['format']), 'alter Typ nicht mehr wählbar');
    }],
    ['Liste: Name (Titel + Kunde), Typ, Status, neueste Datei, Aktionen', static function () use ($render): void {
        $row = ['id' => 9, 'nummer' => '2026-001', 'titel' => 'Projekttag Robotik', 'format' => 'hort', 'status' => 'bestaetigt', 'kunde_id' => 5, 'erstellt_am' => '2026-10-01',
            'kunde_typ' => 'schule', 'kunde_name' => 'Grundschule Test', 'kunde_email' => null, 'hk_vorname' => null, 'hk_nachname' => null,
            'datei_id' => 21, 'datei_name' => 'Angebot_v2.pdf', 'datei_am' => '2026-10-20 10:00:00'];
        $html = $render('auftraege/index', ['rows' => [$row], 'total' => 1, 'page' => 1, 'pages' => 1]);
        preg_match_all('~<th[\s>][^>]*>(.*?)</th>~s', $html, $th);
        assertSame(['Name', 'Typ', 'Status', 'Neueste Datei', 'Aktionen'], array_map(static fn ($t) => trim(strip_tags($t)), $th[1]));
        assertSame(true, str_contains($html, '>Projekttag Robotik</a>') && str_contains($html, '<span class="order-customer">Grundschule Test</span>'));
        assertSame(true, str_contains($html, '<td>Hort</td>') && str_contains($html, 'status-badge--gruen">Bestätigt'));
        assertSame(true, str_contains($html, 'href="/auftraege/datei?id=21"') && str_contains($html, '/auftraege/bearbeiten?id=9') && str_contains($html, 'quick-open" data-id="9"'));
        assertSame(true, str_contains($html, 'data-quick-url="/auftraege/schnellansicht"'));
    }],
    ['Detailseite: Kunde, Auftrag, Status mit Kommentar, Dokumente neueste zuerst', static function () use ($render, $auftrag, $dateien, $verlauf): void {
        $html = $render('auftraege/show', ['auftrag' => $auftrag, 'dateien' => $dateien, 'verlauf' => $verlauf]);
        preg_match_all('~<h2>(.*?)</h2>~s', $html, $h);
        assertSame(['Kunde', 'Auftrag', 'Status', 'Dokumente', 'Notizen'], $h[1]);
        assertSame(true, str_contains($html, 'Grundschule Test') && str_contains($html, 'Tom Lehrer (Klassenleitung)') && str_contains($html, 'Schulweg 1, 80331 München'));
        assertSame(true, str_contains($html, '450,00 € pro Termin') && str_contains($html, '<dt>Klassen</dt><dd>2</dd>') && str_contains($html, 'Einmalig · 12.05. 8–12 Uhr'));
        assertSame(true, strpos($html, 'Angebot_v2.pdf') < strpos($html, 'Angebot_v1.pdf'), 'neueste oben');
        assertSame(true, str_contains($html, 'Per Mail an die Schulleitung'));
    }],
    ['Formular: mehrere Dateien hochladbar, vorhandene Dateien mit Löschen', static function () use ($render, $auftrag, $dateien): void {
        $html = $render('auftraege/form', ['id' => 9, 'auftrag' => $auftrag, 'kunden' => [5 => 'Grundschule Test (Schule)'], 'contacts' => [],
            'dateien' => $dateien, 'kommentar' => null, 'errors' => []]);
        assertSame(true, str_contains($html, 'enctype="multipart/form-data"') && str_contains($html, 'name="dateien[]" multiple'));
        assertSame(2, substr_count($html, 'action="/auftraege/datei/loeschen"'));
        assertSame(true, str_contains($html, 'value="450,00"'), 'Preis vorausgefüllt');
    }],
];
