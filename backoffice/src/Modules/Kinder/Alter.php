<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kinder;

final class Alter
{
    /** Age in years: exact from the birthdate, approximate ("ca.") from the year only */
    public static function years(?string $birthdate, int|string|null $year): ?int
    {
        if ($birthdate) {
            return (new \DateTimeImmutable($birthdate))->diff(new \DateTimeImmutable('today'))->y;
        }
        return $year ? (int) date('Y') - (int) $year : null;
    }

    /** Shown age: from the birthdate only (a separately stored birth year is no longer shown) */
    public static function text(?string $birthdate): string
    {
        if (!$birthdate) {
            return 'Alter unbekannt';
        }
        $years = (int) self::years($birthdate, null);
        return ($years === 1 ? '1 Jahr' : "$years Jahre") . ' (' . (new \DateTimeImmutable($birthdate))->format('d.m.Y') . ')';
    }
}
