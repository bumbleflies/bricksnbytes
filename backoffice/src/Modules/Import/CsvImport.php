<?php
declare(strict_types=1);

namespace Backoffice\Modules\Import;

use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Modules\Kunden\KundenValidator;

// Reads a customer CSV (e.g. the old Noptin newsletter export) and sorts every row into
// "new", "duplicate" or "invalid" — nothing is written here.
final class CsvImport
{
    // Accepted header names per target field (compared lower-case, trimmed)
    private const COLUMNS = [
        'email' => ['email', 'e-mail', 'mail', 'e_mail', 'emailadresse', 'e-mail-adresse'],
        'vorname' => ['vorname', 'first_name', 'firstname', 'first name'],
        'nachname' => ['nachname', 'last_name', 'lastname', 'last name'],
        // Noptin: subscribed / unsubscribed
        'status' => ['status'],
        // Generic: 1/0, ja/nein, yes/no
        'newsletter' => ['newsletter'],
        // Noptin: signup and last change (= unsubscribe date for unsubscribed rows)
        'angemeldet_am' => ['date_created', 'angemeldet_am', 'anmeldedatum'],
        'geaendert_am' => ['date_modified', 'geaendert_am'],
    ];

    /**
     * @return array{encoding: string, delimiter: string, header: array, mapping: array,
     *               new: array, duplicates: array, invalid: array, total: int}
     */
    public static function analyse(string $bytes): array
    {
        $encoding = 'UTF-8';
        if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
            $bytes = substr($bytes, 3);
            $encoding = 'UTF-8 (mit BOM)';
        } elseif (!mb_check_encoding($bytes, 'UTF-8')) {
            $bytes = mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
            $encoding = 'Windows-1252 (umgewandelt nach UTF-8)';
        }

        $firstLine = strtok($bytes, "\r\n") ?: '';
        $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $bytes);
        rewind($stream);
        $header = fgetcsv($stream, null, $delimiter, '"', '') ?: [];
        $header = array_map(static fn ($h) => trim((string) $h), $header);

        $mapping = [];
        foreach (self::COLUMNS as $field => $names) {
            foreach ($header as $index => $name) {
                if (in_array(mb_strtolower($name), $names, true)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }

        $result = ['encoding' => $encoding, 'delimiter' => $delimiter, 'header' => $header, 'mapping' => $mapping,
            'new' => [], 'duplicates' => [], 'invalid' => [], 'total' => 0];

        if (!isset($mapping['email'])) {
            fclose($stream);
            return $result;
        }

        $seen = [];
        $line = 1;
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($row === [null] || $row === []) {
                continue; // blank line
            }
            $result['total']++;
            $get = static fn (string $field) => isset($mapping[$field]) ? trim((string) ($row[$mapping[$field]] ?? '')) : '';

            $email = mb_strtolower($get('email'));
            $record = [
                'zeile' => $line,
                'email' => $email,
                'vorname' => $get('vorname'),
                'nachname' => $get('nachname'),
                'newsletter' => self::newsletterFlag($get('status'), $get('newsletter')),
                'angemeldet_am' => self::date($get('angemeldet_am')),
                'abgemeldet_am' => mb_strtolower($get('status')) === 'unsubscribed' ? self::date($get('geaendert_am')) : null,
            ];

            if ($email === '') {
                $result['invalid'][] = $record + ['grund' => 'Keine E-Mail-Adresse'];
            } elseif (!KundenValidator::validEmail($email)) {
                $result['invalid'][] = $record + ['grund' => 'Ungültige E-Mail-Adresse'];
            } elseif (mb_strlen($record['vorname']) > 100 || mb_strlen($record['nachname']) > 100) {
                $result['invalid'][] = $record + ['grund' => 'Name länger als 100 Zeichen'];
            } elseif (isset($seen[$email])) {
                $result['duplicates'][] = $record + ['grund' => 'Doppelt in der Datei (Zeile ' . $seen[$email] . ')'];
            } elseif (KundenRepository::findIdByEmail($email) !== null) {
                $result['duplicates'][] = $record + ['grund' => 'Gibt es schon als Kunde'];
            } else {
                $result['new'][] = $record;
            }
            $seen[$email] ??= $line;
        }
        fclose($stream);

        return $result;
    }

    // "06.03.2026 14:05", "2026-03-06" or "2026-03-06 14:05:00" -> "2026-03-06"
    private static function date(string $value): ?string
    {
        foreach (['!d.m.Y H:i', '!d.m.Y', '!Y-m-d H:i:s', '!Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date <= new \DateTimeImmutable('tomorrow')) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    private static function newsletterFlag(string $status, string $newsletter): int
    {
        if ($status !== '') {
            return mb_strtolower($status) === 'subscribed' ? 1 : 0;
        }
        return in_array(mb_strtolower($newsletter), ['1', 'ja', 'yes', 'true', 'x'], true) ? 1 : 0;
    }
}
