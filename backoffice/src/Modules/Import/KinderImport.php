<?php
declare(strict_types=1);

namespace Backoffice\Modules\Import;

use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Modules\Kunden\KundenValidator;
use Backoffice\Modules\Kunden\Verteiler;

// Children from the pretix order overview (one row per child and booking): groups the rows by
// e-mail into families and children and sorts them into "new family", "new child for an existing
// customer", "already there" and "please check" — nothing is written here.
final class KinderImport
{
    private const COLUMNS = [
        'bestellung' => ['bestellung', 'order', 'bestellnummer'],
        'bestellt_am' => ['bestellt am', 'bestellt_am', 'bestelldatum'],
        'email' => ['e-mail', 'email', 'mail'],
        'eltern' => ['name eltern', 'name_eltern', 'eltern'],
        'kind' => ['name kind', 'name_kind', 'kind'],
        'alter' => ['alter kind', 'alter_kind', 'alter'],
        'hinweis' => ['hinweis'],
    ];

    private const MONTHS = ['januar' => 1, 'februar' => 2, 'märz' => 3, 'maerz' => 3, 'april' => 4, 'mai' => 5, 'juni' => 6,
        'juli' => 7, 'august' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'dezember' => 12];

    /**
     * @return array{encoding: string, delimiter: string, header: array, mapping: array, total: int,
     *               families: list<array>, newChildren: list<array>, existing: list<array>, check: list<array>,
     *               otherEmails: list<array{kind: string, emails: list<string>}>}
     */
    public static function analyse(string $bytes): array
    {
        ['encoding' => $encoding, 'delimiter' => $delimiter, 'header' => $header, 'stream' => $stream] = CsvImport::open($bytes);
        $mapping = CsvImport::mapColumns($header, self::COLUMNS);
        $result = ['encoding' => $encoding, 'delimiter' => $delimiter, 'header' => $header, 'mapping' => $mapping, 'total' => 0,
            'families' => [], 'newChildren' => [], 'existing' => [], 'check' => [], 'otherEmails' => []];
        if (!isset($mapping['email'], $mapping['kind'])) {
            fclose($stream);
            return $result;
        }

        $ownEmail = mb_strtolower((string) Verteiler::ownEmail());
        $byEmail = [];
        $line = 1;
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;
            if ($row === [null] || $row === []) {
                continue;
            }
            $result['total']++;
            $get = static fn (string $field) => isset($mapping[$field]) ? trim((string) ($row[$mapping[$field]] ?? '')) : '';
            $r = [
                'zeile' => $line,
                'bestellung' => $get('bestellung'),
                'datum' => self::date($get('bestellt_am')),
                'email' => mb_strtolower($get('email')),
                'eltern' => self::cleanName($get('eltern')),
                'kind' => self::cleanName($get('kind')),
                'alter' => self::age($get('alter')),
                'mehrere' => str_contains(mb_strtolower($get('hinweis')), 'mehrere kinder'),
            ];

            $reason = match (true) {
                $r['email'] === '' || !KundenValidator::validEmail($r['email']) => 'Keine gültige E-Mail-Adresse',
                $ownEmail !== '' && $r['email'] === $ownEmail => 'Testbuchung mit der eigenen Adresse',
                $r['kind'] === '' || strcasecmp($r['kind'], 'null') === 0 || preg_match('/[<>]/', $r['kind']) === 1 => 'Kein Name des Kindes (Platzhalter)',
                $r['eltern'] !== '' && mb_strtolower($r['kind']) === mb_strtolower($r['eltern']) => 'Kind heißt wie das Elternteil – bitte prüfen',
                mb_strlen($r['kind']) > 200 => 'Name des Kindes zu lang',
                default => null,
            };
            if ($reason !== null) {
                $result['check'][] = $r + ['grund' => $reason];
                continue;
            }
            $byEmail[$r['email']][] = $r;
        }
        fclose($stream);

        // A child flagged in one row (e.g. test booking "child = parent") is flagged in all rows of that e-mail
        $flagged = [];
        foreach ($result['check'] as $c) {
            $flagged[$c['email']][mb_strtolower($c['kind'])] = $c['grund'];
        }
        foreach ($byEmail as $email => $rows) {
            foreach ($rows as $i => $r) {
                $reason = $flagged[$email][mb_strtolower($r['kind'])] ?? null;
                if ($reason !== null) {
                    $result['check'][] = $r + ['grund' => $reason];
                    unset($byEmail[$email][$i]);
                }
            }
            if (!$byEmail[$email]) {
                unset($byEmail[$email]);
            }
        }
        usort($result['check'], static fn ($a, $b) => $a['zeile'] <=> $b['zeile']);

        // Same full child name under different e-mails: probably one family with two addresses
        $emailsByChild = [];
        foreach ($byEmail as $email => $rows) {
            foreach ($rows as $r) {
                if (str_contains($r['kind'], ' ')) {
                    $emailsByChild[mb_strtolower($r['kind'])][$email] = $r['kind'];
                }
            }
        }
        foreach ($emailsByChild as $names) {
            if (count($names) > 1) {
                $result['otherEmails'][] = ['kind' => reset($names), 'emails' => array_keys($names)];
            }
        }

        foreach ($byEmail as $email => $rows) {
            $kunde = KundenRepository::findIdByAnyEmail($email);
            $known = $kunde === null ? [] : array_map('mb_strtolower', KundenRepository::childFirstNames($kunde));
            $children = self::children($rows);

            if ($kunde === null) {
                $result['families'][] = ['email' => $email, 'eltern' => self::parents($rows), 'kinder' => array_values($children)];
                continue;
            }
            foreach ($children as $key => $child) {
                $entry = $child + ['email' => $email, 'kunde_id' => $kunde];
                if (in_array($key, $known, true)) {
                    $result['existing'][] = $entry;
                } else {
                    $result['newChildren'][] = $entry;
                }
            }
        }
        return $result;
    }

    /**
     * One entry per child (same first name = same child), with the most complete name and the
     * age from the latest booking.
     *
     * @return array<string, array{vorname: string, nachname: ?string, geburtsjahr: ?int, notizen: string, buchungen: int}>
     */
    private static function children(array $rows): array
    {
        // Several different children in one order: the age may belong to a sibling
        $perOrder = [];
        foreach ($rows as $r) {
            $perOrder[$r['bestellung']][self::firstName($r['kind'])] = true;
        }

        $children = [];
        foreach ($rows as $r) {
            $key = self::firstName($r['kind']);
            $c = $children[$key] ?? ['name' => '', 'datum' => null, 'alter' => null, 'pruefen' => false, 'buchungen' => 0];
            $c['buchungen']++;
            if (mb_strlen($r['kind']) > mb_strlen($c['name'])) {
                $c['name'] = $r['kind'];
            }
            if ($r['alter'] !== null && ($c['datum'] === null || ($r['datum'] ?? '') >= $c['datum'])) {
                $c['datum'] = $r['datum'];
                $c['alter'] = $r['alter'];
                $c['pruefen'] = $r['mehrere'] || count($perOrder[$r['bestellung']] ?? []) > 1;
            }
            $children[$key] = $c;
        }

        $out = [];
        foreach ($children as $key => $c) {
            $parts = preg_split('/\s+/', $c['name'], 2);
            $year = $c['alter'] !== null && $c['datum'] !== null ? (int) substr($c['datum'], 0, 4) - (int) floor($c['alter']) : null;
            $note = $year !== null
                ? sprintf('Import pretix: Geburtsjahr geschätzt (%s J. bei Buchung am %s).', self::ageText($c['alter']), self::germanDate($c['datum']))
                : 'Import pretix: Alter unbekannt.';
            if ($c['pruefen'] && $year !== null) {
                $note .= ' Alter bitte prüfen (mehrere Kinder in einer Buchung).';
            }
            $out[$key] = [
                'vorname' => mb_substr($parts[0], 0, 100),
                'nachname' => isset($parts[1]) ? mb_substr($parts[1], 0, 100) : null,
                'geburtsjahr' => $year,
                'notizen' => $note,
                'buchungen' => $c['buchungen'],
            ];
        }
        return $out;
    }

    /**
     * Parent contacts for a new family from the most frequent "Name Eltern" (ties: the longest):
     * "Anna und Max Muster" → Anna Muster + Max Muster; at most two contacts.
     *
     * @return list<array{vorname: ?string, nachname: ?string}>
     */
    public static function parents(array $rows): array
    {
        $counts = [];
        foreach ($rows as $r) {
            if ($r['eltern'] !== '') {
                $counts[$r['eltern']] = ($counts[$r['eltern']] ?? 0) + 1;
            }
        }
        if (!$counts) {
            return [];
        }
        uksort($counts, static fn ($a, $b) => [$counts[$b], mb_strlen($b)] <=> [$counts[$a], mb_strlen($a)]);
        return self::splitParents((string) array_key_first($counts));
    }

    /** @return list<array{vorname: ?string, nachname: ?string}> */
    public static function splitParents(string $names): array
    {
        $people = preg_split('/\s*(?:,|&|\/|\bund\b|\bu\.?(?=\s))\s*/iu', trim($names, " \t.;")) ?: [];
        $people = array_values(array_filter(array_map(static fn ($p) => trim($p, " \t."), $people), static fn ($p) => $p !== ''));
        $people = array_slice($people, 0, 2);

        $split = array_map(static fn ($p) => preg_split('/\s+/', $p), $people);
        // A surname given only once at the end belongs to the first-name-only people before it
        $last = $split ? end($split) : [];
        $surname = count($last) > 1 ? implode(' ', array_slice($last, 1)) : null;

        $contacts = [];
        foreach ($split as $i => $words) {
            $isLast = $i === count($split) - 1;
            if (count($words) === 1) {
                $contacts[] = ['vorname' => mb_substr($words[0], 0, 100), 'nachname' => !$isLast && $surname !== null ? mb_substr($surname, 0, 100) : null];
            } else {
                $contacts[] = ['vorname' => mb_substr($words[0], 0, 100), 'nachname' => mb_substr(implode(' ', array_slice($words, 1)), 0, 100)];
            }
        }
        return $contacts;
    }

    private static function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? '', " \t.");
    }

    private static function firstName(string $name): string
    {
        return mb_strtolower(preg_split('/\s+/', trim($name), 2)[0] ?? '');
    }

    // "8", "4,5", "4.5 Jahre" → float; anything else → null
    private static function age(string $value): ?float
    {
        if (!preg_match('/^(\d{1,2})(?:[.,](\d))?/', $value, $m)) {
            return null;
        }
        $age = (float) ($m[1] . (isset($m[2]) ? '.' . $m[2] : ''));
        return $age >= 1 && $age <= 18 ? $age : null;
    }

    private static function ageText(float $age): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($age, 1, '.', ''), '0'), '.'));
    }

    // "7. Juli 2025 19:26" (pretix, German) or "07.07.2025" / "2025-07-07" → "2025-07-07"
    private static function date(string $value): ?string
    {
        if (preg_match('/^(\d{1,2})\.\s*([a-zäöü]+)\s+(\d{4})/iu', $value, $m) && isset(self::MONTHS[mb_strtolower($m[2])])) {
            return sprintf('%04d-%02d-%02d', $m[3], self::MONTHS[mb_strtolower($m[2])], $m[1]);
        }
        foreach (['!d.m.Y H:i', '!d.m.Y', '!Y-m-d H:i:s', '!Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    private static function germanDate(string $isoDate): string
    {
        [$y, $m, $d] = explode('-', $isoDate);
        return "$d.$m.$y";
    }
}
