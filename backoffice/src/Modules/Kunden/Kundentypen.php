<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

final class Kundentypen
{
    // Singular label, plural label (for filters)
    public const ALLE = [
        'privat' => ['Eltern (privat)', 'Eltern'],
        'schule' => ['Schule', 'Schulen'],
        'hort' => ['Hort', 'Horte'],
        'kita' => ['Kita', 'Kitas'],
        'verein' => ['Verein', 'Vereine'],
        'firma' => ['Firma', 'Firmen'],
        'sonstige' => ['Sonstige', 'Sonstige'],
    ];

    public const EINWILLIGUNGEN = [
        'datenschutz' => 'Datenschutz',
        'foto_video' => 'Fotos/Video',
        'newsletter' => 'Newsletter',
    ];

    public static function valid(?string $typ): bool
    {
        return $typ !== null && isset(self::ALLE[$typ]);
    }

    public static function label(string $typ): string
    {
        return self::ALLE[$typ][0] ?? $typ;
    }

    public static function plural(string $typ): string
    {
        return self::ALLE[$typ][1] ?? $typ;
    }

    public static function isPrivat(string $typ): bool
    {
        return $typ === 'privat';
    }

    /** Name shown in lists: organisation name, else main contact, else e-mail */
    public static function displayName(array $kunde): string
    {
        if (!self::isPrivat($kunde['typ']) && !empty($kunde['name'])) {
            return $kunde['name'];
        }
        $contact = trim(($kunde['hk_vorname'] ?? '') . ' ' . ($kunde['hk_nachname'] ?? ''));
        return $contact !== '' ? $contact : ($kunde['name'] ?: ($kunde['email'] ?? 'Ohne Namen'));
    }
}
