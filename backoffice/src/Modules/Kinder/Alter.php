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

    public static function text(?string $birthdate, int|string|null $year): string
    {
        $years = self::years($birthdate, $year);
        if ($years === null) {
            return 'Alter unbekannt';
        }
        $label = $years === 1 ? '1 Jahr' : "$years Jahre";
        return $birthdate ? $label . ' (' . (new \DateTimeImmutable($birthdate))->format('d.m.Y') . ')' : "ca. $label (Jg. $year)";
    }
}
