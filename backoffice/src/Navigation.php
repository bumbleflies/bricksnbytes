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

    public static function isActive(string $href, string $current): bool
    {
        return $href === '/' ? $current === '/' : ($current === $href || str_starts_with($current, $href . '/'));
    }
}
