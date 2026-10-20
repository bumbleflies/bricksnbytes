<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kinder;

use DateTimeImmutable;

// Children's birthdays in a period (dashboard tile). Pure (no database), so it can be unit-tested.
// Only day and month count; a 29.02. birthday falls on the 28.02. in non-leap years.
final class Geburtstage
{
    // Periods of the "Woche | Monat" switch; STANDARD is shown first (and without JavaScript)
    public const ZEITRAEUME = ['woche' => 'Woche', 'monat' => 'Monat'];
    public const STANDARD = 'woche';

    /**
     * First and last day of the period around $today: "woche" = calendar week Monday to Sunday,
     * "monat" = calendar month.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    public static function zeitraum(string $zeitraum, DateTimeImmutable $today): array
    {
        $today = $today->setTime(0, 0);
        if ($zeitraum === 'monat') {
            return [$today->modify('first day of this month'), $today->modify('last day of this month')];
        }
        $monday = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
        return [$monday, $monday->modify('+6 days')];
    }

    /**
     * Children (rows with at least `geburtsdatum` Y-m-d) with a birthday from $from to $to (inclusive),
     * each extended by `geburtstag` (date in the period), `alter` (age they turn) and `heute`.
     * Order: today first, then the next ones; birthdays already passed in the period come last.
     *
     * @return list<array>
     */
    public static function imZeitraum(array $kinder, DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $today): array
    {
        $from = $from->setTime(0, 0);
        $to = $to->setTime(0, 0);
        $today = $today->setTime(0, 0);
        $found = [];
        foreach ($kinder as $kind) {
            $born = self::date($kind['geburtsdatum'] ?? null);
            if ($born === null) {
                continue;
            }
            // The period may span a year change (week 29.12.–04.01.)
            for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); $year++) {
                $day = self::birthdayIn($born, $year);
                $age = $year - (int) $born->format('Y');
                if ($day >= $from && $day <= $to && $age > 0) {
                    $found[] = $kind + ['geburtstag' => $day, 'alter' => $age, 'heute' => $day == $today];
                }
            }
        }
        usort($found, static function (array $a, array $b) use ($today): int {
            $passedA = $a['geburtstag'] < $today;
            $passedB = $b['geburtstag'] < $today;
            return [$passedA, $a['geburtstag'], $a['vorname'] ?? ''] <=> [$passedB, $b['geburtstag'], $b['vorname'] ?? ''];
        });
        return $found;
    }

    /** Birthday in the given year; 29.02. becomes 28.02. when the year has no 29.02. */
    public static function birthdayIn(DateTimeImmutable $born, int $year): DateTimeImmutable
    {
        $month = (int) $born->format('n');
        $day = (int) $born->format('j');
        if ($month === 2 && $day === 29 && !checkdate(2, 29, $year)) {
            $day = 28;
        }
        return (new DateTimeImmutable())->setDate($year, $month, $day)->setTime(0, 0);
    }

    private static function date(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        return (new DateTimeImmutable())->setDate((int) $m[1], (int) $m[2], (int) $m[3])->setTime(0, 0);
    }
}
