<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kinder;

use DateTimeImmutable;

// Children's next birthdays (dashboard tile). Pure (no database), so it can be unit-tested.
// Only day and month count; a 29.02. birthday falls on the 28.02. in non-leap years.
final class Geburtstage
{
    // How many birthdays the dashboard tile shows
    public const ANZAHL = 5;

    /**
     * The next $anzahl birthdays from $today on (today included, across the year change), each child
     * (rows with at least `geburtsdatum` Y-m-d) extended by `geburtstag` (next date), `alter` (age they
     * turn) and `heute`. Order: next first, same day by name. Children without a valid date are skipped.
     *
     * @return list<array>
     */
    public static function naechste(array $kinder, DateTimeImmutable $today, int $anzahl = self::ANZAHL): array
    {
        $today = $today->setTime(0, 0);
        $found = [];
        foreach ($kinder as $kind) {
            $born = self::date($kind['geburtsdatum'] ?? null);
            if ($born === null || $born >= $today) {
                continue; // no date, or born today / in the future: no birthday to come yet
            }
            $year = (int) $today->format('Y');
            $day = self::birthdayIn($born, $year);
            if ($day < $today) {
                $day = self::birthdayIn($born, ++$year);
            }
            $found[] = $kind + ['geburtstag' => $day, 'alter' => $year - (int) $born->format('Y'), 'heute' => $day == $today];
        }
        $name = static fn (array $k): string => mb_strtolower(trim(($k['vorname'] ?? '') . ' ' . ($k['nachname'] ?? '')));
        usort($found, static fn (array $a, array $b): int => [$a['geburtstag'], $name($a)] <=> [$b['geburtstag'], $name($b)]);
        return array_slice($found, 0, max(0, $anzahl));
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
