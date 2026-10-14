<?php
declare(strict_types=1);

namespace Backoffice\Modules\Import;

use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\KundenValidator;

// Contact list (one row per person/address: Name, E-Mail, Telefon, Organisation, Typ, Status, …).
// Rows of the same organisation become one customer with several contacts; families stay one
// customer per row. Only e-mails that are not in the backoffice yet are taken — nothing is written here.
final class KontakteImport
{
    private const COLUMNS = [
        'name' => ['name'],
        'email' => ['e-mail', 'email', 'mail'],
        'telefon' => ['telefon', 'tel'],
        'organisation' => ['organisation', 'einrichtung'],
        'typ' => ['typ'],
        'status' => ['status'],
        'letzter_kontakt' => ['letzter kontakt', 'letzter_kontakt'],
        'anzahl_mails' => ['anzahl mails', 'anzahl_mails'],
        'notizen' => ['notizen', 'notiz'],
    ];

    // Type in the file (lower-case) => customer type; null = not a customer, skipped
    private const TYPES = [
        'eltern' => 'privat',
        'schule' => 'schule',
        'kita' => 'kita',
        'kita/familienzentrum' => 'kita',
        'hort' => 'hort',
        'hort/förderverein' => 'hort',
        'verein' => 'verein',
        'firma' => 'firma',
        'unternehmen/sponsor' => 'firma',
        'hotel' => 'firma',
        'bibliothek' => 'bibliothek',
        'partner' => 'partner',
        'veranstalter/partner' => 'partner',
        'raumgeber/partner' => 'partner',
        'sonstige' => 'sonstige',
        'kursleiter' => null,
    ];

    // Status in the file (lower-case) => customer status; anything else goes into the notes
    private const STATUS = [
        'akquise' => 'akquise',
        'kontakt' => 'kontakt',
        'angefragt' => 'angefragt',
        'interessent' => 'interessent',
        'angebot offen' => 'angebot_offen',
        'nachfassen' => 'nachfassen',
        'warteliste' => 'warteliste',
        'kunde' => 'kunde',
        'partner' => 'partner',
        'beendet' => 'beendet',
        'kein interesse' => 'kein_interesse',
        'abgelehnt' => 'kein_interesse',
        'kein auftrag' => 'kein_interesse',
        'nicht mehr anschreiben' => 'nicht_anschreiben',
    ];

    public const QUELLE = 'Import Kontakte';

    /**
     * @return array{encoding: string, delimiter: string, header: array, mapping: array, total: int,
     *               new: list<array>, addContacts: list<array>, existing: list<array>, check: list<array>, hints: list<string>}
     */
    public static function analyse(string $bytes): array
    {
        ['encoding' => $encoding, 'delimiter' => $delimiter, 'header' => $header, 'stream' => $stream] = CsvImport::open($bytes);
        $mapping = CsvImport::mapColumns($header, self::COLUMNS);
        $result = ['encoding' => $encoding, 'delimiter' => $delimiter, 'header' => $header, 'mapping' => $mapping, 'total' => 0,
            'new' => [], 'addContacts' => [], 'existing' => [], 'check' => [], 'hints' => []];
        if (!isset($mapping['email'], $mapping['typ'])) {
            fclose($stream);
            return $result;
        }

        $families = [];
        $organisations = [];
        $seen = [];
        $line = 1;
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($row === [null] || $row === []) {
                continue;
            }
            $result['total']++;
            $get = static fn (string $field) => isset($mapping[$field]) ? trim((string) preg_replace('/\s+/u', ' ', (string) ($row[$mapping[$field]] ?? ''))) : '';
            $typRaw = $get('typ');
            $statusRaw = $get('status');
            $r = [
                'zeile' => $line,
                'email' => mb_strtolower($get('email')),
                'name' => $get('name'),
                'telefon' => $get('telefon'),
                'organisation' => $get('organisation'),
                'typ_datei' => $typRaw,
                'typ' => self::TYPES[mb_strtolower($typRaw)] ?? null,
                'status_datei' => $statusRaw,
                'status' => self::STATUS[mb_strtolower($statusRaw)] ?? null,
                'letzter_kontakt' => self::date($get('letzter_kontakt')),
                'anzahl_mails' => ctype_digit($get('anzahl_mails')) ? (int) $get('anzahl_mails') : null,
                'notizen' => $get('notizen'),
            ];

            $reason = match (true) {
                $r['email'] === '' => 'Keine E-Mail-Adresse',
                !KundenValidator::validEmail($r['email']) => 'Ungültige E-Mail-Adresse',
                isset($seen[$r['email']]) => 'Doppelt in der Datei (Zeile ' . $seen[$r['email']] . ')',
                !array_key_exists(mb_strtolower($typRaw), self::TYPES) => 'Unbekannter Typ „' . $typRaw . '“',
                $r['typ'] === null => $typRaw . ' – wird nicht als Kunde importiert',
                $r['typ'] !== 'privat' && $r['organisation'] === '' => 'Keine Organisation angegeben – bitte von Hand anlegen',
                $r['telefon'] !== '' && !preg_match('/^[0-9+()\/\- ]{3,50}$/', $r['telefon']) => 'Ungültige Telefonnummer',
                mb_strlen($r['name']) > 200 || mb_strlen($r['organisation']) > 200 => 'Name oder Organisation zu lang',
                default => null,
            };
            $seen[$r['email']] ??= $line;
            if ($reason !== null) {
                $result['check'][] = $r + ['grund' => $reason];
                continue;
            }

            $kundeId = KundenRepository::findIdByAnyEmail($r['email']);
            if ($kundeId !== null) {
                $result['existing'][] = $r + ['kunde_id' => $kundeId];
                continue;
            }
            if ($r['status'] === null && $r['status_datei'] !== '') {
                $result['hints'][] = 'Zeile ' . $line . ' (' . $r['email'] . '): Status „' . $r['status_datei'] . '“ gibt es im Backoffice nicht – steht in den Notizen, bitte von Hand nacharbeiten.';
            }
            if ($r['typ'] === 'privat') {
                $families[] = $r;
            } else {
                $organisations[mb_strtolower($r['organisation'])][] = $r;
            }
        }
        fclose($stream);

        foreach ($families as $r) {
            $person = $r['name'] !== '' ? self::person($r['name']) : null;
            $notes = self::notes([$r]);
            if ($r['organisation'] !== '') {
                $notes .= "\nOrganisation laut Datei: " . $r['organisation'];
            }
            $result['new'][] = [
                'zeilen' => [$r['zeile']],
                'anzeige' => $r['name'] !== '' ? $r['name'] : $r['email'],
                'data' => ['typ' => 'privat', 'name' => null, 'status' => $r['status'], 'email' => $r['email'],
                    'telefon' => $r['telefon'] ?: null, 'quelle' => self::QUELLE, 'notizen' => $notes],
                'contacts' => $person && ($person['vorname'] ?? $person['nachname']) !== null ? [$person + ['id' => null, 'rolle' => 'Elternteil', 'email' => null, 'telefon' => null, 'ist_hauptkontakt' => 1]] : [],
            ];
        }

        foreach ($organisations as $rows) {
            self::organisation($rows, $result);
        }
        usort($result['check'], static fn ($a, $b) => $a['zeile'] <=> $b['zeile']);
        return $result;
    }

    /** All rows of one organisation: a new customer, or new contacts for the existing one with that name */
    private static function organisation(array $rows, array &$result): void
    {
        // Latest contact first: it decides name spelling, status, main contact and general address
        usort($rows, static fn ($a, $b) => [$b['letzter_kontakt'] ?? '', $a['zeile']] <=> [$a['letzter_kontakt'] ?? '', $b['zeile']]);
        $latest = $rows[0];
        $name = $latest['organisation'];
        $zeilen = array_column($rows, 'zeile');
        sort($zeilen);

        // Most frequent type, ties: the type of the latest contact
        $types = array_count_values(array_column($rows, 'typ'));
        $typ = $latest['typ'];
        foreach ($types as $t => $n) {
            if ($n > $types[$typ]) {
                $typ = $t;
            }
        }
        if (count($types) > 1) {
            $files = array_unique(array_column($rows, 'typ_datei'));
            $result['hints'][] = '„' . $name . '“: unterschiedliche Typen in der Datei (' . implode(', ', $files) . ') – übernommen als ' . Kundentypen::label($typ) . '.';
        }

        $general = array_values(array_filter($rows, static fn ($r) => $r['name'] === ''));
        $named = array_values(array_filter($rows, static fn ($r) => $r['name'] !== ''));
        $contacts = [];
        foreach ($named as $r) {
            $contacts[] = self::person($r['name']) + ['id' => null, 'rolle' => null, 'email' => $r['email'],
                'telefon' => $r['telefon'] ?: null, 'ist_hauptkontakt' => $contacts ? 0 : 1];
        }
        $notes = self::notes($rows);

        $existing = KundenRepository::findOrgIdByName($name);
        if ($existing !== null) {
            foreach ($general as $r) {
                $result['check'][] = $r + ['grund' => '„' . $name . '“ gibt es schon – allgemeine Adresse bitte von Hand ergänzen'];
            }
            if ($named) {
                $result['addContacts'][] = ['kunde_id' => $existing, 'anzeige' => $name, 'zeilen' => array_column($named, 'zeile'),
                    'status' => $latest['status'], 'contacts' => $contacts, 'notizen' => self::notes($named)];
            }
            return;
        }

        // The latest general address becomes the customer's e-mail; further ones only fit into the notes
        $main = $general[0] ?? null;
        foreach (array_slice($general, 1) as $r) {
            $notes .= "\nWeitere allgemeine Adresse: " . $r['email'] . ($r['telefon'] !== '' ? ', Tel. ' . $r['telefon'] : '');
            $result['hints'][] = '„' . $name . '“: weitere allgemeine Adresse ' . $r['email'] . ' (Zeile ' . $r['zeile'] . ') steht nur in den Notizen.';
        }
        $result['new'][] = [
            'zeilen' => $zeilen,
            'anzeige' => $name,
            'data' => ['typ' => $typ, 'name' => mb_substr($name, 0, 200), 'status' => $latest['status'], 'email' => $main['email'] ?? null,
                'telefon' => ($main['telefon'] ?? '') ?: null, 'quelle' => self::QUELLE, 'notizen' => $notes],
            'contacts' => $contacts,
        ];
    }

    /**
     * "Anna Muster" → Anna / Muster, "Frau Muster" → – / Muster, "Dr. Anna Muster" → Dr. Anna / Muster,
     * a single word without salutation → first name.
     *
     * @return array{vorname: ?string, nachname: ?string}
     */
    public static function person(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $salutation = false;
        if ($words && preg_match('/^(frau|herr)$/iu', rtrim($words[0], '.'))) {
            array_shift($words);
            $salutation = true;
        }
        $titles = [];
        while (count($words) > 1 && preg_match('/^(dr|prof)\.?$/iu', $words[0])) {
            $titles[] = array_shift($words);
        }
        if (!$words) {
            return ['vorname' => null, 'nachname' => null];
        }
        if (count($words) === 1) {
            $single = trim(implode(' ', [...$titles, $words[0]]));
            return $salutation ? ['vorname' => null, 'nachname' => mb_substr($single, 0, 100)] : ['vorname' => mb_substr($single, 0, 100), 'nachname' => null];
        }
        return [
            'vorname' => mb_substr(implode(' ', [...$titles, $words[0]]), 0, 100),
            'nachname' => mb_substr(implode(' ', array_slice($words, 1)), 0, 100),
        ];
    }

    // Notes from the file, one line per row (with who it was for organisations with several rows)
    private static function notes(array $rows): string
    {
        $lines = ['Import Kontakte (' . date('d.m.Y') . '):'];
        foreach ($rows as $r) {
            $text = $r['notizen'] !== '' ? $r['notizen'] : 'keine Notiz';
            if ($r['anzahl_mails'] !== null) {
                $text .= ' (Mails: ' . $r['anzahl_mails'] . ')';
            }
            if ($r['status'] === null && $r['status_datei'] !== '') {
                $text .= ' Status in der Datei: ' . $r['status_datei'] . '.';
            }
            $who = $r['name'] !== '' ? $r['name'] . ' <' . $r['email'] . '>' : $r['email'];
            $lines[] = count($rows) > 1 ? '- ' . $who . ': ' . $text : $text;
        }
        return mb_substr(implode("\n", $lines), 0, 5000);
    }

    // "09.10.2026" or "2026-10-09" → "2026-10-09"
    private static function date(string $value): ?string
    {
        foreach (['!d.m.Y', '!Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date->format(substr($format, 1)) === $value) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }
}
