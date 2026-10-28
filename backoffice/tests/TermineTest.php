<?php
declare(strict_types=1);

// Dates overview: planner import (lines, sheet layout), periods, month grid, merged entries per day,
// form validation and the views. Made-up data only.

use Backoffice\Modules\Termine\Kalender;
use Backoffice\Modules\Termine\PlanerImport;
use Backoffice\Modules\Termine\TerminValidator;

if (!defined('BACKOFFICE_ROOT')) {
    define('BACKOFFICE_ROOT', dirname(__DIR__));
}
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Backoffice\View::e($value);
    }
}

$d = static fn (string $s): DateTimeImmutable => new DateTimeImmutable($s);
$line = static function (string $text): string {
    $r = PlanerImport::line($text);
    return ($r['beginn'] ?? '') . '|' . ($r['ende'] ?? '') . '|' . $r['titel'];
};
// Planner rows: header, month names, days 1–31 (3 columns per month)
$sheet = static function (array $cells): array {
    $rows = [array_fill(0, 36, ''), []];
    $rows[0][2] = '2026';
    foreach (Kalender::MONATE as $i => $name) {
        $rows[1][$i * 3] = $name;
        $rows[1][$i * 3 + 1] = $rows[1][$i * 3 + 2] = '';
    }
    for ($day = 1; $day <= 31; $day++) {
        $row = array_fill(0, 36, '');
        for ($m = 1; $m <= 12; $m++) {
            if (checkdate($m, $day, 2026)) {
                $row[($m - 1) * 3] = (string) $day;
                $row[($m - 1) * 3 + 1] = 'Mo.';
                $row[($m - 1) * 3 + 2] = $cells["$m-$day"] ?? '';
            }
        }
        $rows[] = $row;
    }
    return $rows;
};

return [
    ['Planer-Zeile: Zeitformate', static function () use ($line): void {
        assertSame('14:00|16:00|Programmierkurs (P) 5/5', $line('14-16 Programmierkurs (P) 5/5'));
        assertSame('08:00|09:00|Treffen', $line('8:00 - 9:00 Treffen'));
        assertSame('09:00|16:30|Workshop', $line('9-16.30 Workshop'));
        assertSame('13:00|15:30|Kick-Off', $line('13:00 15:30 Kick-Off'));
        assertSame('09:00|13:00|Tagesheim', $line('9-11 & 11-13 Tagesheim'));
        assertSame('17:15|18:45|Sport', $line('17:15 - 18-45 Sport'));
        assertSame('11:00||Yoga', $line('11:00 Yoga'));
        assertSame('00:20||Flug', $line('00:20 Flug'));
        assertSame('18:00|22:00|Schwimmen', $line('18 - 22 Schwimmen'));
    }],
    ['Planer-Zeile: ohne Uhrzeit bleibt der ganze Text', static function () use ($line): void {
        assertSame('||11 Geburtstag Tom', $line('11 Geburtstag Tom'), 'Zahl ohne Doppelpunkt ist keine Uhrzeit');
        assertSame('||Urlaub', $line('  Urlaub '));
        assertSame('||Schule 1-2', $line('Schule 1-2'));
        assertSame('||Monatsfeier 8:00', $line('Monatsfeier 8:00'));
        assertSame('||25:00 Quatsch', $line('25:00 Quatsch'));
        assertSame(null, PlanerImport::line('   '));
    }],
    ['Planer-Tabelle: Monate als Spalten, mehrere Zeilen je Zelle, ungültige Tage gemeldet', static function () use ($sheet): void {
        $rows = $sheet(['1-7' => "13:45-16 Schule\nNotiz", '2-28' => 'Fasching', '12-31' => '19:00 Silvester']);
        $rows[32][4] = '31';      // 31. Februar mit Text
        $rows[32][5] = 'Mo.';
        $rows[32][5 + 0] = 'Mo.';
        $rows[32][3 + 2] = 'Gibt es nicht';
        $r = PlanerImport::parse($rows, 2026);
        assertSame([['2026-01-07', '13:45', 'Schule'], ['2026-01-07', null, 'Notiz'], ['2026-02-28', null, 'Fasching'], ['2026-12-31', '19:00', 'Silvester']],
            array_map(static fn ($x) => [$x['datum'], $x['beginn'], $x['titel']], $r['eintraege']));
        assertSame(1, count($r['probleme']));
        assertSame(2026, PlanerImport::year($rows));
    }],
    ['Planer-Kategorie: am Titel erkannt, erster Treffer zählt, Rest ohne Kategorie', static function (): void {
        $cases = [
            'bibliothek' => ['10:00 - 12:00 Stadtbibliothek Pasing', 'Biblitohek Mitte'],
            'bnb-schule' => ['GS Musterstraße 3-4', 'EDV 10. Klasse (A)', 'EDV 11. Klasse', 'Projekttag GS Beispielweg (P)'],
            'bnb-pep' => ['Minecraft Programmierkurs (PEP) (3/7)', 'Ferienkurs PEP', 'Elternabend PEP'],
            'bnb-dghk' => ['Programmierkurs LEGO (DGhK)', 'Ganztagesworkshop (DGHK)'],
            'bnb-allach' => ['Ferienkurs Allach 3/12', 'Medienführerschein (A) 6-10J'],
            'bnb-pasing' => ['Programmierworkshop (P) 0/10', '10:00 Pasing - Termin'],
            'tw' => ['TW Weihnachtsfeier', 'IPMA Prüfung', 'Deadline Reportabgabe'],
            'schule' => ['Festkreissitzung', 'Feskreistreffen', 'Elterabend', 'Elternabende', 'Öffentliche Monatsfeier', 'Cafeteriadienst', 'Keine Schule !!!'],
            'bricksnbytes' => ['Schnuppern: Programmieren mit Minecraft', 'Pictoblox (Online)', 'Geburtstag - LEGO', 'WERBUNG FÜR KURSE', 'Train the trainer'],
            'max' => ['Max abholen', 'Maxi Training', 'Zara Max Arzt'],
            'zara' => ['Zara Friseur', 'Yoga', '11:00 - 12:15 Yoga', 'Tischtennis ?!', 'Zahnarzt'],
        ];
        foreach ($cases as $art => $titles) {
            foreach ($titles as $t) {
                assertSame($art, PlanerImport::art($t), $t);
            }
        }
        foreach (['Pepperoni essen', '(B) Friseur', 'Maximilian', 'Twitter', ''] as $t) {
            assertSame(null, PlanerImport::art($t), $t);
        }
        assertSame(array_keys($cases), array_keys(PlanerImport::ART_MUSTER), 'Reihenfolge');
        foreach (PlanerImport::ART_MUSTER as $art => $pattern) {
            assertSame(true, isset(Kalender::ARTEN[$art]), $art);
        }
    }],
    ['Migration 011: gleiche Muster in gleicher Reihenfolge wie der Import', static function (): void {
        $sql = (string) file_get_contents(dirname(__DIR__) . '/migrations/011_kategorien.sql');
        preg_match_all("/SET art = '([\\w-]+)'\\s+WHERE import_lauf LIKE 'planer-%' AND \\(art IS NULL OR art LIKE 'alt:%'\\)\\s+AND titel REGEXP '((?:[^'\\\\]|\\\\.)*)';/", $sql, $m);
        assertSame(PlanerImport::ART_MUSTER, array_combine($m[1], array_map('stripslashes', $m[2])));
    }],
    ['CSV: Komma oder Semikolon, mehrzeilige Zellen, BOM', static function (): void {
        $rows = PlanerImport::csvRows("\xEF\xBB\xBF,,2026\nJanuar,,\n1,Do.,\"14-16 Kurs\n18:00 Essen\"\n");
        assertSame(['1', 'Do.', "14-16 Kurs\n18:00 Essen"], $rows[2]);
        assertSame(['a', 'b'], PlanerImport::csvRows("a;b\n")[0]);
    }],
    ['Zeiträume: Monat, Quartal, Halbjahr, Jahr', static function () use ($d): void {
        $range = static fn (string $v, string $ab) => implode(' – ', array_map(static fn ($x) => $x->format('Y-m-d'), Kalender::zeitraum($v, $d($ab))));
        assertSame('2026-10-01 – 2026-10-31', $range('monat', '2026-10-21'));
        assertSame('2026-10-01 – 2026-12-31', $range('quartal', '2026-11-05'));
        assertSame('2026-07-01 – 2026-12-31', $range('halbjahr', '2026-10-21'));
        assertSame('2026-01-01 – 2026-06-30', $range('halbjahr', '2026-02-01'));
        assertSame('2026-01-01 – 2026-12-31', $range('jahr', '2026-10-21'));
        assertSame('2027-01-01', Kalender::blaettern('quartal', $d('2026-10-01'), 1)->format('Y-m-d'));
        assertSame('Okt. – Dez. 2026', str_replace(['Oktober', 'Dezember'], ['Okt.', 'Dez.'], Kalender::titel('quartal', $d('2026-10-01'), $d('2026-12-31'))));
    }],
    ['Monatsraster: Wochen Montag bis Sonntag', static function (): void {
        $weeks = Kalender::monatsRaster(2026, 10);   // 1.10.2026 ist ein Donnerstag
        assertSame('2026-09-28', $weeks[0][0]);
        assertSame('2026-10-01', $weeks[0][3]);
        assertSame('2026-11-01', end($weeks)[6]);
        assertSame(5, count($weeks));
    }],
    ['Tage: eigene (mehrtägig), Kurse, Aufträge, Geburtstage zusammen, nach Uhrzeit', static function () use ($d): void {
        $eigene = [['id' => 1, 'datum' => '2026-10-20', 'bis_datum' => '2026-10-22', 'beginn' => null, 'ende' => null, 'titel' => 'Urlaub', 'art' => null],
            ['id' => 2, 'datum' => '2026-10-21', 'bis_datum' => null, 'beginn' => '18:00:00', 'ende' => '19:30:00', 'titel' => 'Sport', 'art' => null]];
        $kurse = [['beginn' => '2026-10-21 15:00:00', 'ende' => '2026-10-21 16:00:00', 'titel' => 'LEGO Spike']];
        $auftraege = [['id' => 5, 'titel' => 'Projekttag', 'kunde' => 'Grundschule', 'status' => 'bestaetigt', 'regelmaessig' => 0, 'leistung_von' => '2026-10-21', 'leistung_bis' => null],
            ['id' => 6, 'titel' => 'Abgesagt', 'kunde' => 'X', 'status' => 'abgesagt', 'regelmaessig' => 0, 'leistung_von' => '2026-10-21', 'leistung_bis' => null],
            ['id' => 7, 'titel' => 'Hort-AG', 'kunde' => 'Hort', 'status' => 'bestaetigt', 'regelmaessig' => 1, 'leistung_von' => '2026-09-15', 'leistung_bis' => '2027-07-20']];
        $kinder = [['kunde_id' => 3, 'vorname' => 'Ben', 'nachname' => null, 'geburtsdatum' => '2018-10-21']];
        $tage = Kalender::tage($d('2026-10-01'), $d('2026-10-31'), $eigene, $kurse, $auftraege, $kinder);
        assertSame(['2026-10-20', '2026-10-21', '2026-10-22'], array_keys($tage));
        // all-day first (alphabetical), then by time
        assertSame(['geburtstag:Ben wird 8', 'auftrag:Projekttag – Grundschule', 'eigen:Urlaub', 'kurs:15:00 LEGO Spike', 'eigen:18:00 Sport'],
            array_map(static fn ($e) => $e['quelle'] . ':' . ($e['beginn'] ? $e['beginn'] . ' ' : '') . $e['titel'], $tage['2026-10-21']));
        $sept = Kalender::tage($d('2026-09-01'), $d('2026-09-30'), [], [], $auftraege, []);
        assertSame(['2026-09-15'], array_keys($sept), 'regelmäßig: nur Start (und Ende)');
        assertSame('Start: Hort-AG – Hort', $sept['2026-09-15'][0]['titel']);
    }],
    ['Vorbei: frühere Tage ganz, heute nach Ende (oder Beginn), ganztägig heute nicht', static function (): void {
        $e = static fn (?string $b, ?string $en): array => ['beginn' => $b, 'ende' => $en];
        assertSame(true, Kalender::vorbei('2026-10-27', $e(null, null), '2026-10-28', '08:00'));
        assertSame(false, Kalender::vorbei('2026-10-29', $e('07:00', '08:00'), '2026-10-28', '23:00'));
        assertSame(true, Kalender::vorbei('2026-10-28', $e('09:00', '10:30'), '2026-10-28', '10:30'));
        assertSame(false, Kalender::vorbei('2026-10-28', $e('09:00', '10:30'), '2026-10-28', '10:29'), 'läuft noch');
        assertSame(true, Kalender::vorbei('2026-10-28', $e('11:00', null), '2026-10-28', '12:00'), 'nur Beginn');
        assertSame(false, Kalender::vorbei('2026-10-28', $e(null, null), '2026-10-28', '23:59'), 'ganztägig heute');
    }],
    ['Ansicht: vergangene Tage grau, ihre Termine durchgestrichen; kommende normal', static function () use ($d): void {
        $render = static function (array $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            require BACKOFFICE_ROOT . '/templates/termine/index.php';
            return (string) ob_get_clean();
        };
        $today = new DateTimeImmutable('today');
        $mk = static fn (string $t) => ['quelle' => 'eigen', 'titel' => $t, 'beginn' => null, 'ende' => null, 'link' => null, 'id' => null];
        $tage = [$today->modify('-1 day')->format('Y-m-d') => [$mk('Gestern-Termin')], $today->modify('+1 day')->format('Y-m-d') => [$mk('Morgen-Termin')],
            $today->format('Y-m-d') => [$mk('Heute-ganztags')]];
        foreach (['quartal', 'jahr'] as $view) {
            // From the period of yesterday to the end of tomorrow's period (spans the change of period)
            [$from] = Kalender::zeitraum($view, $today->modify('-1 day'));
            [, $to] = Kalender::zeitraum($view, $today->modify('+1 day'));
            $html = $render(['ansicht' => $view, 'from' => $from, 'to' => $to, 'tage' => $tage]);
            assertSame(1, preg_match('~class="cal-entry cal-entry--ohne is-past" title="Gestern-Termin \(vorbei\)"~', $html), $view . ' gestern');
            assertSame(true, str_contains($html, 'class="cal-entry cal-entry--ohne" title="Heute-ganztags"'), $view . ' heute ganztags');
            assertSame(true, str_contains($html, 'is-past') && !str_contains($html, 'Morgen-Termin (vorbei)'), $view . ' morgen');
        }
        $month = $render(['ansicht' => 'monat', 'from' => $today->modify('first day of this month'), 'to' => $today->modify('last day of this month'), 'tage' => $tage]);
        $pastDays = (int) $today->format('j') - 1;
        preg_match_all('~<td class="[^"]*is-past[^"]*"~', $month, $m);
        assertSame(true, count($m[0]) >= $pastDays, 'alle früheren Tage des Monats grau');
        assertSame(false, (bool) preg_match('~<td class="[^"]*is-past[^"]*is-today~', $month), 'heute nicht grau');
    }],
    ['Kategorien und Farben: BricksnBytes alle dunkelblau, Kurse und Aufträge dazu, ohne Kategorie grau', static function (): void {
        $kat = static fn (string $quelle, ?string $art = null) => Kalender::kategorie(['quelle' => $quelle, 'art' => $art]);
        assertSame('', $kat('eigen'));
        assertSame('', $kat('eigen', 'unbekannt'));
        assertSame('bnb-pep', $kat('eigen', 'bnb-pep'));
        assertSame('zara', $kat('eigen', 'zara'));
        assertSame('bricksnbytes', $kat('kurs'));
        assertSame('bricksnbytes', $kat('auftrag', 'zara'));
        assertSame('geburtstag', $kat('geburtstag'));
        $farben = [];
        foreach (array_keys(Kalender::ARTEN) as $art) {
            $farben[$art] = Kalender::farbe($art);
        }
        assertSame(['' => 'ohne', 'bricksnbytes' => 'bricksnbytes', 'bnb-allach' => 'bricksnbytes', 'bnb-pasing' => 'bricksnbytes', 'bnb-pep' => 'bricksnbytes',
            'bnb-dghk' => 'bricksnbytes', 'bnb-schule' => 'bricksnbytes', 'tw' => 'tw', 'schule' => 'schule', 'zara' => 'zara', 'max' => 'max', 'bibliothek' => 'bibliothek'], $farben);
        $css = (string) file_get_contents(BACKOFFICE_ROOT . '/public/assets/app.css');
        foreach (array_unique([...array_values($farben), Kalender::farbe('geburtstag'), ...array_map([Kalender::class, 'farbe'], array_keys(Kalender::FILTER))]) as $farbe) {
            assertSame(true, str_contains($css, ".cal-entry--$farbe {"), $farbe);
        }
        assertSame(true, str_contains($css, '.cal-entry--bricksnbytes { border-left-color: #232b3d; background: #232b3d; color: #fff; }'), 'dunkelblau wie --ink der Website');
    }],
    ['Filter: BricksnBytes nimmt alle BnB-Kategorien, sonst genau die Kategorie; leere Tage fallen weg', static function (): void {
        $e = static fn (string $quelle, ?string $art, string $titel) => ['quelle' => $quelle, 'art' => $art, 'titel' => $titel];
        $tage = [
            '2026-11-02' => [$e('eigen', 'bnb-pep', 'PEP'), $e('kurs', null, 'Kurs'), $e('eigen', 'zara', 'Yoga')],
            '2026-11-03' => [$e('eigen', null, 'Ohne'), $e('geburtstag', null, 'Ben wird 8'), $e('auftrag', null, 'Auftrag')],
            '2026-11-04' => [$e('eigen', 'schule', 'Elternabend'), $e('eigen', 'bricksnbytes', 'Werbung')],
        ];
        $titles = static fn (array $t) => array_map(static fn ($list) => array_column($list, 'titel'), $t);
        assertSame($titles($tage), $titles(Kalender::filtern($tage, '')));
        assertSame(['2026-11-02' => ['PEP', 'Kurs'], '2026-11-03' => ['Auftrag'], '2026-11-04' => ['Werbung']], $titles(Kalender::filtern($tage, 'bricksnbytes')));
        assertSame(['2026-11-02' => ['PEP']], $titles(Kalender::filtern($tage, 'bnb-pep')));
        assertSame(['2026-11-02' => ['Yoga']], $titles(Kalender::filtern($tage, 'zara')));
        assertSame(['2026-11-03' => ['Ohne']], $titles(Kalender::filtern($tage, 'ohne')));
        assertSame(['2026-11-03' => ['Ben wird 8']], $titles(Kalender::filtern($tage, 'geburtstag')));
        assertSame([], Kalender::filtern($tage, 'tw'));
        assertSame(array_diff_key(Kalender::ARTEN, ['' => true]), array_intersect_key(Kalender::ARTEN, Kalender::FILTER), 'jede Kategorie hat einen Filter');
    }],
    ['Ansicht: Filter statt Legende, gewählter Filter markiert und in allen Links behalten', static function (): void {
        $render = static function (array $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            require BACKOFFICE_ROOT . '/templates/termine/index.php';
            return (string) ob_get_clean();
        };
        $from = new DateTimeImmutable('2026-11-01');
        $to = new DateTimeImmutable('2026-11-30');
        $tage = ['2026-11-02' => [['quelle' => 'eigen', 'art' => 'zara', 'titel' => 'Yoga', 'beginn' => null, 'ende' => null, 'link' => null, 'id' => null]]];
        $html = $render(['ansicht' => 'monat', 'from' => $from, 'to' => $to, 'tage' => $tage, 'kategorie' => 'zara']);
        assertSame(false, str_contains($html, 'cal-legend'));
        preg_match_all('~<nav class="cal-filter".*?</nav>~s', $html, $nav);
        preg_match_all('~<a(?: class="([^"]*)")? href="([^"]*)"( aria-current="true")?>([^<]*)</a>~', $nav[0][0] ?? '', $m, PREG_SET_ORDER);
        assertSame(['Alle', ...array_values(Kalender::FILTER)], array_map(static fn ($x) => html_entity_decode($x[4]), $m));
        assertSame(['Zara'], array_values(array_map(static fn ($x) => $x[4], array_filter($m, static fn ($x) => ($x[3] ?? '') !== ''))));
        assertSame('/termine?ansicht=monat&amp;ab=2026-11', $m[0][2], 'Alle ohne Filter');
        assertSame('/termine?ansicht=monat&amp;ab=2026-11&amp;kategorie=bnb-pep', $m[4][2]);
        assertSame('cal-entry--bricksnbytes', $m[4][1]);
        assertSame(true, str_contains($html, 'href="/termine?ansicht=quartal&amp;ab=2026-11&amp;kategorie=zara"'), 'Ansicht wechseln behält Filter');
        assertSame(true, str_contains($html, 'href="/termine?ansicht=monat&amp;ab=2026-12&amp;kategorie=zara"'), 'Blättern behält Filter');
        assertSame(true, str_contains($html, 'class="cal-entry cal-entry--zara'), 'Termin rosa');
        $empty = $render(['ansicht' => 'monat', 'from' => $from, 'to' => $to, 'tage' => [], 'kategorie' => 'tw']);
        assertSame(true, str_contains($empty, 'Keine Termine „TW“ in diesem Zeitraum.'));
        $all = $render(['ansicht' => 'jahr', 'from' => new DateTimeImmutable('2026-01-01'), 'to' => new DateTimeImmutable('2026-12-31'), 'tage' => [], 'kategorie' => '']);
        assertSame(true, str_contains($all, '<a href="/termine?ansicht=jahr&amp;ab=2026-01" aria-current="true">Alle</a>'));
        assertSame(true, str_contains($all, 'href="/termine?ansicht=monat&amp;ab=2026-03"') && !str_contains($all, 'Keine Termine'));
    }],
    ['Termin-Formular: Kategorie aus der Liste, Unbekanntes wird ohne Kategorie', static function (): void {
        $base = ['titel' => 'Elternabend', 'datum' => '2026-10-21'];
        assertSame(null, TerminValidator::validate($base)['data']['art']);
        foreach (array_keys(array_diff_key(Kalender::ARTEN, ['' => true])) as $art) {
            assertSame($art, TerminValidator::validate($base + ['art' => $art])['data']['art'], $art);
        }
        assertSame(null, TerminValidator::validate($base + ['art' => 'kurs'])['data']['art'], 'alter Typ');
        assertSame(null, TerminValidator::validate($base + ['art' => 'privat<script>'])['data']['art']);
    }],
    ['Termin-Formular: Pflichtfelder, Zeiten, mehrtägig', static function (): void {
        assertSame(['titel', 'datum'], array_keys(TerminValidator::validate([])['errors']));
        $ok = TerminValidator::validate(['titel' => 'Yoga', 'datum' => '2026-10-21', 'bis_datum' => '2026-10-21', 'beginn' => '11:00', 'ende' => '12:15']);
        assertSame([], $ok['errors']);
        assertSame([null, '11:00', '12:15'], [$ok['data']['bis_datum'], $ok['data']['beginn'], $ok['data']['ende']]);
        $bad = TerminValidator::validate(['titel' => 'X', 'datum' => '2026-10-21', 'bis_datum' => '2026-10-20', 'beginn' => '25:00', 'ende' => '10:00']);
        assertSame(['bis_datum', 'beginn'], array_keys($bad['errors']));
    }],
    ['Ansichten: Monat als Kalender, sonst Monate als Spalten mit Tagen 1–31', static function () use ($d): void {
        $render = static function (array $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            require BACKOFFICE_ROOT . '/templates/termine/index.php';
            return (string) ob_get_clean();
        };
        $tage = ['2026-10-21' => [['quelle' => 'eigen', 'titel' => 'Yoga & Co', 'beginn' => '11:00', 'ende' => null, 'link' => '/termine/bearbeiten?id=1', 'id' => 1]]];
        $month = $render(['ansicht' => 'monat', 'from' => $d('2026-10-01'), 'to' => $d('2026-10-31'), 'tage' => $tage]);
        assertSame(true, str_contains($month, 'class="cal-month"') && substr_count($month, '<tr>') === 6, 'Kopf + 5 Wochen');
        assertSame(true, str_contains($month, 'Yoga &amp; Co') && str_contains($month, '/termine/neu?datum=2026-10-21'));
        $year = $render(['ansicht' => 'jahr', 'from' => $d('2026-01-01'), 'to' => $d('2026-12-31'), 'tage' => $tage]);
        assertSame(12, substr_count($year, '<th scope="col"><a href="/termine?ansicht=monat'));
        assertSame(31, substr_count(explode('<tbody>', $year)[1], '<tr>'));
        assertSame(7, substr_count($year, 'class="is-none"'), '30.2., 31.2., 29.2., 31.4., 31.6., 31.9., 31.11.');
        $q = $render(['ansicht' => 'quartal', 'from' => $d('2026-10-01'), 'to' => $d('2026-12-31'), 'tage' => $tage]);
        assertSame(3, substr_count($q, '<th scope="col"><a href="/termine?ansicht=monat'));
    }],
];
