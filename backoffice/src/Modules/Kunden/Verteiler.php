<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Config;
use Backoffice\Settings;

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

    /** Own address for the "An" field: config app.eigene_email (APP_OWNER_EMAIL), else setting eigene_email */
    public static function ownEmail(): ?string
    {
        $address = self::one((string) (Config::get('app.eigene_email') ?: Settings::get('eigene_email')));
        return $address !== '' ? $address : null;
    }

    /** Closing lines of every mail from "Newsletter verschicken": the link to the unsubscribe page */
    public static function newsletterFooter(): string
    {
        $url = (string) (Config::get('newsletter.abmelde_url') ?: 'https://bricksnbytes.de/newsletter-abmelden');
        return "\n\n\n--\nDu möchtest keinen Newsletter mehr bekommen? Hier kannst du dich abmelden: $url\n";
    }

    /** Single address for one table row, or '' when it can't receive mail */
    public static function one(?string $address): string
    {
        return self::clean([$address])[0] ?? '';
    }
}
