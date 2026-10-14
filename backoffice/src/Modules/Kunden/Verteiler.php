<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

// Mail recipient lists for "E-Mail an Auswahl" / "Newsletter verschicken"
final class Verteiler
{
    /**
     * Trims, drops empty and invalid addresses and removes duplicates (case-insensitive),
     * keeping the first spelling and the original order.
     *
     * @param array<int, ?string> $addresses
     * @return list<string>
     */
    public static function clean(array $addresses): array
    {
        $clean = [];
        foreach ($addresses as $address) {
            $address = trim((string) $address);
            $key = mb_strtolower($address);
            if ($address !== '' && !isset($clean[$key]) && KundenValidator::validEmail($address)) {
                $clean[$key] = $address;
            }
        }
        return array_values($clean);
    }

    /** Single address for one table row, or '' when it can't receive mail */
    public static function one(?string $address): string
    {
        return self::clean([$address])[0] ?? '';
    }
}
