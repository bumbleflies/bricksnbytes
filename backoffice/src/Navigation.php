<?php
declare(strict_types=1);

namespace Backoffice;

// Sidebar entries for all modules; placeholder modules show "Kommt bald" until they are built
final class Navigation
{
    public const ITEMS = [
        ['/', 'Dashboard'],
        ['/kunden', 'Kunden'],
        ['/kinder', 'Kinder'],
        ['/auftraege', 'Aufträge'],
        ['/kurse', 'Kurse'],
        ['/termine', 'Termine'],
        ['/buchungen', 'Buchungen'],
        ['/rechnungen', 'Rechnungen'],
        ['/forecast', 'Forecast'],
        ['/kursleiter', 'Kursleiter'],
        ['/einstellungen', 'Einstellungen'],
    ];

    // Modules without the customer search and "+ Neu" (customer) in the top bar
    public const OHNE_KUNDENSUCHE = ['/termine'];

    public static function isActive(string $href, string $current): bool
    {
        return $href === '/' ? $current === '/' : ($current === $href || str_starts_with($current, $href . '/'));
    }

    /** Top bar with customer search and "+ Neu" on this page? */
    public static function kundensuche(string $current): bool
    {
        foreach (self::OHNE_KUNDENSUCHE as $href) {
            if (self::isActive($href, $current)) {
                return false;
            }
        }
        return true;
    }
}
