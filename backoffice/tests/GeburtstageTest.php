<?php
declare(strict_types=1);

use Backoffice\Modules\Kinder\Geburtstage;

$kind = static fn (string $vorname, ?string $geburtsdatum): array => ['vorname' => $vorname, 'geburtsdatum' => $geburtsdatum];
// Next birthdays as "Name dd.mm.yyyy age", today marked
$run = static fn (array $kinder, string $today, int $anzahl = Geburtstage::ANZAHL): array => array_map(
    static fn ($k) => $k['vorname'] . ' ' . $k['geburtstag']->format('d.m.Y') . ' ' . $k['alter'] . ($k['heute'] ? ' heute' : ''),
    Geburtstage::naechste($kinder, new DateTimeImmutable($today), $anzahl)
);

return [
    ['Standard: 5 Geburtstage', static function (): void {
        assertSame(5, Geburtstage::ANZAHL);
    }],
    ['Viele Kinder → genau die nächsten 5, nächster zuerst', static function () use ($run, $kind): void {
        $kinder = [];
        foreach (['01-15', '03-02', '10-21', '10-25', '11-01', '11-30', '12-24', '06-06', '10-19'] as $i => $md) {
            $kinder[] = $kind("K$i", "2018-$md");
        }
        assertSame(['K2 21.10.2026 8', 'K3 25.10.2026 8', 'K4 01.11.2026 8', 'K5 30.11.2026 8', 'K6 24.12.2026 8'], $run($kinder, '2026-10-20'));
    }],
    ['Jahreswechsel: heute 28.12., Geburtstage im Januar zählen fürs nächste Jahr', static function () use ($run, $kind): void {
        $kinder = [$kind('Jan', '2019-01-03'), $kind('Dez', '2017-12-30'), $kind('Nov', '2016-11-02'), $kind('Feb', '2020-02-10')];
        assertSame(['Dez 30.12.2026 9', 'Jan 03.01.2027 8', 'Feb 10.02.2027 7', 'Nov 02.11.2027 11'], $run($kinder, '2026-12-28'));
    }],
    ['Geburtstag heute steht oben und ist markiert', static function () use ($run, $kind): void {
        $kinder = [$kind('Morgen', '2019-10-21'), $kind('Heute', '2018-10-20'), $kind('Gestern', '2020-10-19')];
        assertSame(['Heute 20.10.2026 8 heute', 'Morgen 21.10.2026 7', 'Gestern 19.10.2027 7'], $run($kinder, '2026-10-20'));
    }],
    ['29.02. im Nicht-Schaltjahr am 28.02., im Schaltjahr am 29.02.', static function () use ($run, $kind): void {
        $kinder = [$kind('Lina', '2020-02-29')];
        assertSame(['Lina 28.02.2026 6 heute'], $run($kinder, '2026-02-28'));
        assertSame(['Lina 28.02.2027 7'], $run($kinder, '2026-03-01'));
        assertSame(['Lina 29.02.2028 8'], $run($kinder, '2028-02-01'));
    }],
    ['Gleicher Tag → nach Name, Liste bleibt bei 5', static function () use ($run, $kind): void {
        $kinder = [];
        foreach (['Zoe', 'anna', 'Mia', 'Ben', 'Lea', 'Kai', 'Ole'] as $name) {
            $kinder[] = $kind($name, '2019-10-22');
        }
        assertSame(['anna 22.10.2026 7', 'Ben 22.10.2026 7', 'Kai 22.10.2026 7', 'Lea 22.10.2026 7', 'Mia 22.10.2026 7'], $run($kinder, '2026-10-20'));
    }],
    ['Weniger als 5 Kinder mit Datum → alle; ohne Datum ignoriert', static function () use ($run, $kind): void {
        $kinder = [$kind('Ali', '2018-05-02'), $kind('Ohne', null), $kind('Kaputt', '2019-02-31'), $kind('Bea', '2019-11-11')];
        assertSame(['Bea 11.11.2026 7', 'Ali 02.05.2027 9'], $run($kinder, '2026-10-20'));
    }],
    ['Keine Kinder → leer', static function () use ($run, $kind): void {
        assertSame([], $run([], '2026-10-20'));
        assertSame([], $run([$kind('Ohne', null)], '2026-10-20'));
    }],
    ['Alter: wird X am nächsten Geburtstag; heute oder später Geborene fehlen', static function () use ($run, $kind): void {
        $kinder = [$kind('Alt', '2010-10-23'), $kind('Baby', '2026-03-01'), $kind('Neu', '2026-10-20'), $kind('Falsch', '2027-01-01')];
        assertSame(['Alt 23.10.2026 16', 'Baby 01.03.2027 1'], $run($kinder, '2026-10-20'));
    }],
    ['Anzahl lässt sich ändern', static function () use ($run, $kind): void {
        $kinder = [$kind('A', '2018-10-21'), $kind('B', '2018-10-22'), $kind('C', '2018-10-23')];
        assertSame(['A 21.10.2026 8', 'B 22.10.2026 8'], $run($kinder, '2026-10-20', 2));
    }],
];
