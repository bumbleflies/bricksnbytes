<?php
declare(strict_types=1);

use Backoffice\Modules\Kinder\Geburtstage;

$d = static fn (string $ymd): DateTimeImmutable => new DateTimeImmutable($ymd);
$kind = static fn (string $vorname, ?string $geburtsdatum): array => ['vorname' => $vorname, 'geburtsdatum' => $geburtsdatum];
// Birthdays in the period of $today, as "Name dd.mm. age" for compact comparisons
$run = static function (array $kinder, string $zeitraum, string $today) use ($d): array {
    [$from, $to] = Geburtstage::zeitraum($zeitraum, $d($today));
    return array_map(
        static fn ($k) => $k['vorname'] . ' ' . $k['geburtstag']->format('d.m.') . ' ' . $k['alter'] . ($k['heute'] ? ' heute' : ''),
        Geburtstage::imZeitraum($kinder, $from, $to, $d($today))
    );
};
$range = static function (string $zeitraum, string $today) use ($d): string {
    [$from, $to] = Geburtstage::zeitraum($zeitraum, $d($today));
    return $from->format('Y-m-d') . ' – ' . $to->format('Y-m-d');
};

return [
    ['Standard ist „Woche“', static function (): void {
        assertSame('woche', Geburtstage::STANDARD);
    }],
    ['Woche = Montag bis Sonntag (auch am Sonntag und am Montag)', static function () use ($range): void {
        assertSame('2026-10-19 – 2026-10-25', $range('woche', '2026-10-20'));
        assertSame('2026-10-19 – 2026-10-25', $range('woche', '2026-10-19'));
        assertSame('2026-10-19 – 2026-10-25', $range('woche', '2026-10-25'));
    }],
    ['Woche über den Jahreswechsel: 29.12. bis 04.01.', static function () use ($range): void {
        assertSame('2025-12-29 – 2026-01-04', $range('woche', '2025-12-31'));
        assertSame('2025-12-29 – 2026-01-04', $range('woche', '2026-01-02'));
    }],
    ['Monat = Kalendermonat', static function () use ($range): void {
        assertSame('2026-10-01 – 2026-10-31', $range('monat', '2026-10-20'));
        assertSame('2028-02-01 – 2028-02-29', $range('monat', '2028-02-10'));
    }],
    ['Woche über den Jahreswechsel findet Geburtstage aus beiden Jahren, richtiges Alter', static function () use ($run, $kind): void {
        $kinder = [$kind('Ben', '2019-12-30'), $kind('Mia', '2020-01-03'), $kind('Ole', '2018-01-05'), $kind('Lea', '2017-12-28')];
        // Today 31.12.2025: Ben (30.12.) is past, Mia (03.01.2026) turns 6; Ole and Lea are outside
        assertSame(['Mia 03.01. 6', 'Ben 30.12. 6'], $run($kinder, 'woche', '2025-12-31'));
    }],
    ['Woche über einen Monatswechsel', static function () use ($run, $kind): void {
        $kinder = [$kind('Tom', '2016-11-01'), $kind('Ida', '2015-10-28'), $kind('Kai', '2016-11-03')];
        // Week 26.10.–01.11.2026
        assertSame(['Ida 28.10. 11', 'Tom 01.11. 10'], $run($kinder, 'woche', '2026-10-27'));
    }],
    ['Monat: alle Geburtstage im Monat, nächste zuerst, vergangene am Ende', static function () use ($run, $kind): void {
        $kinder = [$kind('Ali', '2018-10-02'), $kind('Bea', '2019-10-31'), $kind('Cem', '2020-10-21'), $kind('Dan', '2020-11-01')];
        assertSame(['Cem 21.10. 6', 'Bea 31.10. 7', 'Ali 02.10. 8'], $run($kinder, 'monat', '2026-10-20'));
    }],
    ['Heute steht oben und ist markiert', static function () use ($run, $kind): void {
        $kinder = [$kind('Zoe', '2019-10-22'), $kind('Max', '2018-10-20'), $kind('Eva', '2020-10-19')];
        assertSame(['Max 20.10. 8 heute', 'Zoe 22.10. 7', 'Eva 19.10. 6'], $run($kinder, 'woche', '2026-10-20'));
    }],
    ['29.02. im Nicht-Schaltjahr am 28.02.', static function () use ($run, $kind): void {
        $kinder = [$kind('Lina', '2020-02-29')];
        assertSame(['Lina 28.02. 6 heute'], $run($kinder, 'woche', '2026-02-28'));
        assertSame([], $run($kinder, 'woche', '2026-03-02'));
        assertSame(['Lina 28.02. 6'], $run($kinder, 'monat', '2026-02-01'));
    }],
    ['29.02. im Schaltjahr am 29.02.', static function () use ($run, $kind): void {
        $kinder = [$kind('Lina', '2020-02-29')];
        assertSame(['Lina 29.02. 8 heute'], $run($kinder, 'woche', '2028-02-29'));
        assertSame([], $run($kinder, 'woche', '2028-02-27')); // week 21.–27.02.2028
    }],
    ['Keine Treffer', static function () use ($run, $kind): void {
        assertSame([], $run([], 'woche', '2026-10-20'));
        assertSame([], $run([$kind('Ali', '2018-05-02')], 'monat', '2026-10-20'));
    }],
    ['Ohne Geburtsdatum, ungültiges Datum oder noch nicht geboren → nicht dabei', static function () use ($run, $kind): void {
        $kinder = [$kind('Ohne', null), $kind('Kaputt', '2019-02-31'), $kind('Neu', '2026-10-21'), $kind('Bald', '2027-10-21')];
        assertSame([], $run($kinder, 'woche', '2026-10-20'));
    }],
    ['Alter: das Geburtsjahr zählt nur fürs Alter, nicht für den Vergleich', static function () use ($run, $kind): void {
        $kinder = [$kind('Alt', '2010-10-23'), $kind('Jung', '2025-10-23')];
        assertSame(['Alt 23.10. 16', 'Jung 23.10. 1'], $run($kinder, 'woche', '2026-10-20'));
    }],
];
