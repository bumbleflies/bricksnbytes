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
        'bibliothek' => ['Bibliothek', 'Bibliotheken'],
        'partner' => ['Partner', 'Partner'],
        'oeffentlich' => ['Öffentlicher Träger', 'Öffentliche Träger'],
        'sonstige' => ['Sonstige', 'Sonstige'],
    ];

    // Customer status in the order of the acquisition pipeline (the DB enum sorts the same way)
    public const STATUS = [
        'akquise' => 'Akquise',
        'kontakt' => 'Kontakt',
        'angefragt' => 'Angefragt',
        'interessent' => 'Interessent',
        'angebot_offen' => 'Angebot offen',
        'nachfassen' => 'Nachfassen',
        'warteliste' => 'Warteliste',
        'kunde' => 'Kunde',
        'partner' => 'Partner',
        'beendet' => 'Beendet',
        'kein_interesse' => 'Kein Interesse',
        'nicht_anschreiben' => 'Nicht mehr anschreiben',
    ];

    // Status that mean: please do not write to them (highlighted in lists)
    public const STATUS_STOP = ['kein_interesse', 'nicht_anschreiben'];

    public const EINWILLIGUNGEN = [
        'datenschutz' => 'Datenschutz',
        'foto_video' => 'Fotos/Video',
        'newsletter' => 'Newsletter',
    ];

    public static function valid(?string $typ): bool
    {
        return $typ !== null && isset(self::ALLE[$typ]);
    }

    public static function validStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS[$status]);
    }

    public static function statusLabel(?string $status): string
    {
        return $status === null ? '' : (self::STATUS[$status] ?? $status);
    }

    public static function label(string $typ): string
    {
        return self::ALLE[$typ][0] ?? $typ;
    }

    public static function plural(string $typ): string
    {
        return self::ALLE[$typ][1] ?? $typ;
    }

    /**
     * Customer fields of the form section "Familie" (Eltern) or "Einrichtung" (all other types) – the one
     * place that decides it. First/last name are with the parents / contact persons, e-mail and phone
     * too for families; stored values of fields not in the form are kept on save.
     */
    public static function stammdatenFelder(string $typ): array
    {
        return self::isPrivat($typ)
            ? ['adresse', 'plz', 'ort', 'status', 'quelle']
            : ['name', 'email', 'telefon', 'adresse', 'plz', 'ort', 'status', 'quelle'];
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
