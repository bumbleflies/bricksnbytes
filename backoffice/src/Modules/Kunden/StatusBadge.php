<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\View;

// Customer status as a coloured, non-interactive pill (list, detail page, quick view). The mapping
// status → colour lives only here; the colours are the --status-<farbe>-* tokens in app.css.
final class StatusBadge
{
    public const FARBEN = [
        'akquise' => 'grau',          // not contacted yet
        'kontakt' => 'lila',          // written to
        'angefragt' => 'lila',        // asked / waiting for an answer
        'interessent' => 'gelb',
        'angebot_offen' => 'orange',
        'nachfassen' => 'orange',     // open offer to follow up
        'warteliste' => 'gelb',       // interested, waiting for a place
        'kunde' => 'gruen',
        'partner' => 'gruen',
        'beendet' => 'grau',
        'kein_interesse' => 'rot',
        'nicht_anschreiben' => 'rot',
    ];

    public const NEUTRAL = 'grau';
    public const OHNE_STATUS = 'Kein Status';

    /** Colour of a status; unknown or empty status are neutral grey */
    public static function farbe(?string $status): string
    {
        return self::FARBEN[$status ?? ''] ?? self::NEUTRAL;
    }

    /** <span> pill – no button, no link, not focusable */
    public static function html(?string $status): string
    {
        $label = $status === null || $status === '' ? self::OHNE_STATUS : Kundentypen::statusLabel($status);
        return '<span class="status-badge status-badge--' . self::farbe($status) . '">' . View::e($label) . '</span>';
    }
}
