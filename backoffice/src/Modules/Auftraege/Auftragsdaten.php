<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auftraege;

// Fixed values of an order (labels, status colours, price units) and the checks for uploaded files.
// Pure (no database), so it can be unit-tested.
final class Auftragsdaten
{
    // Order types offered in the form (DB column auftraege.format; older values stay readable)
    public const TYPEN = [
        'projekttag' => 'Projekttag',
        'hort' => 'Hort',
        'workshop' => 'Workshop',
        'ferienkurs' => 'Ferienkurs',
        'kita_einheit' => 'Kita',
        'geburtstag' => 'Geburtstag',
        'sonstiges' => 'Sonstiges',
    ];
    private const TYPEN_ALT = ['offener_kurs' => 'Offener Kurs', 'ag' => 'AG'];

    // Status in the order of the process; colour = StatusBadge token (--status-<farbe>-*)
    public const STATUS = [
        'anfrage' => ['Anfrage', 'lila'],
        'angebot_verschickt' => ['Angebot verschickt', 'orange'],
        'bestaetigt' => ['Bestätigt', 'gruen'],
        'durchgefuehrt' => ['Durchgeführt', 'gelb'],
        'abgerechnet' => ['Abgerechnet', 'grau'],
        'abgesagt' => ['Abgesagt', 'rot'],
    ];

    public const PREIS_EINHEITEN = [
        'gesamt' => 'gesamt',
        'pro_termin' => 'pro Termin',
        'pro_kind' => 'pro Kind',
        'pro_monat' => 'pro Monat',
    ];

    // Uploads: at most 10 MB per file (the database takes 16 MB per query); extension → MIME type
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const ERLAUBT = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];
    // Shown in the browser; everything else is downloaded
    private const INLINE = ['application/pdf', 'image/jpeg', 'image/png'];

    public static function typLabel(string $typ): string
    {
        return self::TYPEN[$typ] ?? self::TYPEN_ALT[$typ] ?? $typ;
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS[$status][0] ?? $status;
    }

    public static function statusFarbe(?string $status): string
    {
        return self::STATUS[$status ?? ''][1] ?? 'grau';
    }

    /** "1.234,50", "1234.5", "450 €" → cents; '' → 0; null when it is no amount */
    public static function parsePreis(string $value): ?int
    {
        $value = trim(str_replace(['€', ' ', "\u{00A0}"], '', $value));
        if ($value === '') {
            return 0;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $value)) {        // 1.234,50
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (preg_match('/^\d+(,\d{1,2})?$/', $value)) {                // 1234,50
            $value = str_replace(',', '.', $value);
        } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {               // 1234.50
            return null;
        }
        $cents = (int) round((float) $value * 100);
        return $cents <= 2_000_000_000 ? $cents : null;
    }

    /** 123450 → "1.234,50 €" */
    public static function formatPreis(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }

    /** The agreed price as text, e.g. "450,00 € pro Termin"; '' when none */
    public static function preisText(int $cents, string $einheit): string
    {
        if ($cents <= 0) {
            return '';
        }
        return self::formatPreis($cents) . ($einheit !== 'gesamt' ? ' ' . (self::PREIS_EINHEITEN[$einheit] ?? '') : '');
    }

    /**
     * Checks one upload: allowed extension, size, and that the content matches the extension
     * ($detectedMime from finfo). Returns the MIME type to store, or an error text.
     *
     * @return array{0: ?string, 1: ?string} [mime, error]
     */
    public static function pruefeDatei(string $name, int $size, ?string $detectedMime): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::ERLAUBT[$ext])) {
            return [null, "„{$name}“: Dateityp nicht erlaubt (erlaubt: PDF, Word, Excel, PowerPoint, OpenDocument, Text, JPG, PNG)."];
        }
        if ($size <= 0) {
            return [null, "„{$name}“ ist leer."];
        }
        if ($size > self::MAX_BYTES) {
            return [null, "„{$name}“ ist größer als 10 MB."];
        }
        $expected = self::ERLAUBT[$ext];
        // Office files are ZIP containers; old Office files are "CDF"; text may come as several types
        $ok = match (true) {
            in_array($ext, ['docx', 'xlsx', 'pptx', 'odt', 'ods'], true) => in_array($detectedMime, ['application/zip', $expected, 'application/octet-stream'], true),
            in_array($ext, ['doc', 'xls', 'ppt'], true) => in_array($detectedMime, ['application/CDFV2', 'application/x-ole-storage', $expected, 'application/octet-stream'], true),
            $ext === 'txt' => $detectedMime !== null && str_starts_with($detectedMime, 'text/'),
            default => $detectedMime === $expected,
        };
        return $ok ? [$expected, null] : [null, "„{$name}“: Inhalt passt nicht zur Endung .{$ext}."];
    }

    /** File name safe for storing and for the download header (no path, no control characters) */
    public static function dateiname(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F"]+/u', '', $name);
        $name = trim($name, " .");
        return mb_substr($name !== '' ? $name : 'datei', 0, 200);
    }

    public static function inline(string $mime): bool
    {
        return in_array($mime, self::INLINE, true);
    }

    /** 1536 → "1,5 KB" */
    public static function groesse(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }
}
