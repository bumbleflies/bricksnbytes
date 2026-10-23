<?php
declare(strict_types=1);

namespace Backoffice\Modules\Termine;

// Reads the planner sheet ("Planer 2026", one tab per year, downloaded as CSV): row 2 holds the month
// names, rows 3–33 the days 1–31; per month three columns (day, weekday, entries). A cell may hold
// several entries, one per line, usually with a time in front ("14-16 …", "8:00 - 9:00 …").
// Pure (no database), so it can be unit-tested.
final class PlanerImport
{
    private const T = '\d{1,2}(?:[:.]\d{2})?';

    /**
     * @param list<list<string>> $rows CSV rows
     * @return array{eintraege: list<array{datum: string, beginn: ?string, ende: ?string, titel: string}>, probleme: list<string>}
     */
    public static function parse(array $rows, int $year): array
    {
        $entries = [];
        $problems = [];
        foreach (array_slice($rows, 2, 31) as $r => $row) {
            for ($month = 1; $month <= 12; $month++) {
                $col = ($month - 1) * 3;
                $text = trim((string) ($row[$col + 2] ?? ''));
                if ($text === '') {
                    continue;
                }
                $day = (int) trim((string) ($row[$col] ?? ''));
                if ($day < 1 || !checkdate($month, $day, $year)) {
                    $problems[] = sprintf('Zeile %d, Monat %d: kein gültiger Tag für „%s“.', $r + 3, $month, mb_substr($text, 0, 60));
                    continue;
                }
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                    $entry = self::line($line);
                    if ($entry !== null) {
                        $entries[] = ['datum' => $date] + $entry;
                    }
                }
            }
        }
        // By date; entries of one day keep the order of the cell (sorting is stable)
        usort($entries, static fn ($a, $b) => $a['datum'] <=> $b['datum']);
        return ['eintraege' => $entries, 'probleme' => $problems];
    }

    /**
     * One line → time range and title. A single number without a colon is not a time ("11 Geburtstag").
     *
     * @return ?array{beginn: ?string, ende: ?string, titel: string}
     */
    public static function line(string $line): ?array
    {
        $line = trim((string) preg_replace('/\s+/u', ' ', $line));
        if ($line === '') {
            return null;
        }
        $t = self::T;
        $pattern = "/^(?<s>$t)(?:\\s*(?:-|–|bis)\\s*(?<e>\\d{1,2}(?:[:.\\-]\\d{2})?)|\\s+(?<e2>\\d{1,2}[:.]\\d{2}))?(?:\\s*Uhr)?"
            . "(?<more>(?:\\s*&\\s*$t\\s*-\\s*$t)*)\\s+(?<titel>.+)$/u";
        if (preg_match($pattern, $line, $m)) {
            $end = ($m['e'] ?? '') !== '' ? $m['e'] : (($m['e2'] ?? '') !== '' ? $m['e2'] : null);
            if (($m['more'] ?? '') !== '' && preg_match_all("/-\\s*($t)/u", $m['more'], $mm)) {
                $end = end($mm[1]);                       // "9-11 & 11-13 …" → 9–13
            }
            $begin = self::time($m['s']);
            $endTime = $end !== null ? self::time($end) : null;
            $isTime = $begin !== null && ($end === null ? (bool) preg_match('/[:.]/', $m['s']) : $endTime !== null);
            if ($isTime) {
                return ['beginn' => $begin, 'ende' => $endTime, 'titel' => mb_substr(trim($m['titel']), 0, 255)];
            }
        }
        return ['beginn' => null, 'ende' => null, 'titel' => mb_substr($line, 0, 255)];
    }

    // "14" → 14:00, "8:10" / "16.30" → 08:10 / 16:30, "18-45" (typo for 18:45) → 18:45; null if invalid
    private static function time(string $value): ?string
    {
        if (!preg_match('/^(\d{1,2})(?:[:.\-](\d{2}))?$/', $value, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $min = isset($m[2]) ? (int) $m[2] : 0;
        if ($h > 24 || $min > 59 || ($h === 24 && $min > 0)) {
            return null;
        }
        return sprintf('%02d:%02d', $h === 24 ? 23 : $h, $h === 24 ? 59 : $min);
    }

    /** CSV text (comma or semicolon, UTF-8 with or without BOM) → rows */
    public static function csvRows(string $csv): array
    {
        $csv = (string) preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $first = strtok($csv, "\n") ?: '';
        $sep = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, $sep, '"', '')) !== false) {
            $rows[] = array_map(static fn ($v) => (string) $v, $row);
        }
        fclose($handle);
        return $rows;
    }

    /** Year from the header row ("2026"), else null */
    public static function year(array $rows): ?int
    {
        foreach ($rows[0] ?? [] as $cell) {
            if (preg_match('/^(20\d{2})$/', trim((string) $cell), $m)) {
                return (int) $m[1];
            }
        }
        return null;
    }
}
