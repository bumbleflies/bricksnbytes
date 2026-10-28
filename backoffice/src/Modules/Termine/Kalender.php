<?php
declare(strict_types=1);

namespace Backoffice\Modules\Termine;

use Backoffice\Modules\Kinder\Geburtstage;
use DateTimeImmutable;

// Dates overview: periods of the views, the month grid and all entries per day (own entries, course
// dates, orders with a date, children's birthdays). Pure (no database), so it can be unit-tested.
final class Kalender
{
    // View => [label, months]
    public const ANSICHTEN = ['monat' => ['Monat', 1], 'quartal' => ['3 Monate', 3], 'halbjahr' => ['6 Monate', 6], 'jahr' => ['Jahr', 12]];
    public const MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    public const WOCHENTAGE = ['Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.', 'So.'];
    // Type of an own entry ('' = plain own entry); course dates and orders count as "kurs"
    public const ARTEN = ['' => 'Eigener Termin', 'kurs' => 'Kurs', 'schule' => 'Schule'];
    // Colour per kind (CSS .cal-entry--<farbe>), with the legend label
    public const FARBEN = ['eigen' => 'Eigener Termin', 'kurs' => 'Kurs / Auftrag', 'schule' => 'Schule', 'geburtstag' => '🎂 Geburtstag'];
    // Regular orders (e.g. weekly in the Hort) only show their first and last day
    private const MAX_TAGE_AUFTRAG = 31;

    /**
     * First and last day of the view around $ab: month; calendar quarter; half year (Jan/Jul); year.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public static function zeitraum(string $ansicht, DateTimeImmutable $ab): array
    {
        $months = self::ANSICHTEN[$ansicht][1] ?? 1;
        $year = (int) $ab->format('Y');
        $month = (int) $ab->format('n');
        $start = intdiv($month - 1, $months) * $months + 1;
        $from = (new DateTimeImmutable())->setDate($year, $start, 1)->setTime(0, 0);
        return [$from, $from->modify('+' . $months . ' months -1 day')];
    }

    /** Start of the previous / next period */
    public static function blaettern(string $ansicht, DateTimeImmutable $from, int $direction): DateTimeImmutable
    {
        $months = self::ANSICHTEN[$ansicht][1] ?? 1;
        return $from->modify(($direction < 0 ? '-' : '+') . $months . ' months');
    }

    /** Title of the period, e.g. "Oktober 2026", "Okt. – Dez. 2026", "2026" */
    public static function titel(string $ansicht, DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $m = static fn (DateTimeImmutable $d) => self::MONATE[(int) $d->format('n') - 1];
        return match ($ansicht) {
            'jahr' => $from->format('Y'),
            'quartal', 'halbjahr' => $m($from) . ' – ' . $m($to) . ' ' . $to->format('Y'),
            default => $m($from) . ' ' . $from->format('Y'),
        };
    }

    /**
     * Weeks of a month (Monday to Sunday) as dates 'Y-m-d'; days of the neighbouring months included.
     *
     * @return list<list<string>>
     */
    public static function monatsRaster(int $year, int $month): array
    {
        $first = (new DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0);
        $day = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
        $last = $first->modify('last day of this month');
        $weeks = [];
        while ($day <= $last) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $day->format('Y-m-d');
                $day = $day->modify('+1 day');
            }
            $weeks[] = $week;
        }
        return $weeks;
    }

    /**
     * All entries per day from $from to $to, each day sorted by time (all-day first, then by title).
     * Entry: [quelle, titel, beginn, ende, link, id]; quelle: eigen | kurs | auftrag | geburtstag.
     *
     * @return array<string, list<array>>
     */
    public static function tage(DateTimeImmutable $from, DateTimeImmutable $to, array $eigene, array $kurse, array $auftraege, array $kinder): array
    {
        $from = $from->setTime(0, 0);
        $to = $to->setTime(0, 0);
        $days = [];
        $add = static function (string $date, array $entry) use (&$days, $from, $to): void {
            if ($date >= $from->format('Y-m-d') && $date <= $to->format('Y-m-d')) {
                $days[$date][] = $entry + ['beginn' => null, 'ende' => null, 'link' => null, 'id' => null];
            }
        };

        foreach ($eigene as $e) {
            $start = new DateTimeImmutable($e['datum']);
            $end = $e['bis_datum'] ? new DateTimeImmutable($e['bis_datum']) : $start;
            for ($d = max($start, $from); $d <= min($end, $to); $d = $d->modify('+1 day')) {
                $add($d->format('Y-m-d'), ['quelle' => 'eigen', 'titel' => $e['titel'], 'beginn' => self::hm($e['beginn']), 'ende' => self::hm($e['ende']),
                    'link' => '/termine/bearbeiten?id=' . $e['id'], 'id' => (int) $e['id'], 'art' => $e['art'] ?? null]);
            }
        }
        foreach ($kurse as $k) {
            $add(substr($k['beginn'], 0, 10), ['quelle' => 'kurs', 'titel' => $k['titel'], 'beginn' => substr($k['beginn'], 11, 5),
                'ende' => $k['ende'] ? substr($k['ende'], 11, 5) : null]);
        }
        foreach ($auftraege as $a) {
            if ($a['status'] === 'abgesagt' || !$a['leistung_von']) {
                continue;
            }
            $title = $a['titel'] . ($a['kunde'] !== '' ? ' – ' . $a['kunde'] : '');
            $link = '/auftraege/ansehen?id=' . $a['id'];
            $start = new DateTimeImmutable($a['leistung_von']);
            $end = $a['leistung_bis'] ? new DateTimeImmutable($a['leistung_bis']) : $start;
            if ($a['regelmaessig'] || $start->diff($end)->days >= self::MAX_TAGE_AUFTRAG) {
                $add($start->format('Y-m-d'), ['quelle' => 'auftrag', 'titel' => 'Start: ' . $title, 'link' => $link]);
                if ($end > $start) {
                    $add($end->format('Y-m-d'), ['quelle' => 'auftrag', 'titel' => 'Ende: ' . $title, 'link' => $link]);
                }
                continue;
            }
            for ($d = max($start, $from); $d <= min($end, $to); $d = $d->modify('+1 day')) {
                $add($d->format('Y-m-d'), ['quelle' => 'auftrag', 'titel' => $title, 'link' => $link]);
            }
        }
        foreach ($kinder as $k) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($k['geburtsdatum'] ?? ''))) {
                continue;
            }
            $born = new DateTimeImmutable($k['geburtsdatum']);
            for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); $year++) {
                $day = Geburtstage::birthdayIn($born, $year);
                $age = $year - (int) $born->format('Y');
                if ($age > 0) {
                    $add($day->format('Y-m-d'), ['quelle' => 'geburtstag', 'titel' => trim($k['vorname'] . ' ' . ($k['nachname'] ?? '')) . ' wird ' . $age,
                        'link' => '/kunden/ansehen?id=' . $k['kunde_id']]);
                }
            }
        }

        foreach ($days as &$list) {
            usort($list, static fn ($a, $b) => [$a['beginn'] !== null, $a['beginn'] ?? '', mb_strtolower($a['titel'])] <=> [$b['beginn'] !== null, $b['beginn'] ?? '', mb_strtolower($b['titel'])]);
        }
        ksort($days);
        return $days;
    }

    /**
     * Already over: every entry of an earlier day; today the ones whose end (or start, without end)
     * has passed. All-day entries of today are not over yet. $now as "HH:MM".
     */
    public static function vorbei(string $date, array $entry, string $today, string $now): bool
    {
        if ($date !== $today) {
            return $date < $today;
        }
        $until = $entry['ende'] ?? $entry['beginn'] ?? null;
        return $until !== null && $until <= $now;
    }

    /** Colour of an entry: own entries by their type, course dates and orders blue, birthdays lilac */
    public static function farbe(array $entry): string
    {
        return match ($entry['quelle']) {
            'kurs', 'auftrag' => 'kurs',
            'geburtstag' => 'geburtstag',
            default => isset(self::ARTEN[$entry['art'] ?? '']) && ($entry['art'] ?? '') !== '' ? $entry['art'] : 'eigen',
        };
    }

    /** "14:00–16:00", "11:00", '' */
    public static function zeit(?string $beginn, ?string $ende): string
    {
        return $beginn === null ? '' : $beginn . ($ende !== null ? '–' . $ende : '');
    }

    private static function hm(?string $time): ?string
    {
        return $time === null || $time === '' ? null : substr($time, 0, 5);
    }
}
