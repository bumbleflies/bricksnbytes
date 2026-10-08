<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

// Turns raw form input into clean values (empty -> null) and collects German error messages.
// Errors for repeated rows use keys like "ansprechpartner.2" or "kinder.0" (posted index).
final class KundenValidator
{
    private const MAX = [
        'name' => 200, 'email' => 254, 'telefon' => 50, 'adresse' => 200, 'plz' => 10, 'ort' => 100,
        'quelle' => 100, 'notizen' => 5000, 'vorname' => 100, 'nachname' => 100, 'rolle' => 100,
        'abholberechtigte' => 1000,
    ];

    /** @return array{data: array, contacts: array, kinder: array, consents: array, errors: array} */
    public static function validate(array $input, ?int $kundeId): array
    {
        $errors = [];
        $data = [];

        $data['typ'] = is_string($input['typ'] ?? null) && Kundentypen::valid($input['typ']) ? $input['typ'] : null;
        if ($data['typ'] === null) {
            $errors['typ'] = 'Bitte einen Kundentyp wählen.';
            $data['typ'] = 'privat';
        }
        $privat = Kundentypen::isPrivat($data['typ']);

        foreach (['name', 'email', 'telefon', 'adresse', 'plz', 'ort', 'quelle'] as $field) {
            $data[$field] = self::text($input[$field] ?? null, $field, $errors);
        }
        $data['notizen'] = self::text($input['notizen'] ?? null, 'notizen', $errors, multiline: true);
        if ($privat) {
            $data['name'] = null; // families are named after their contacts
        } elseif ($data['name'] === null) {
            $errors['name'] = 'Bitte den Namen der Einrichtung angeben.';
        }

        if ($data['email'] !== null) {
            $data['email'] = mb_strtolower($data['email']);
            if (!self::validEmail($data['email'])) {
                $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
            } elseif (KundenRepository::emailTaken($data['email'], $kundeId)) {
                $errors['email'] = 'Diese E-Mail-Adresse gehört schon zu einem anderen Kunden.';
            }
        }
        if ($data['telefon'] !== null && !self::validPhone($data['telefon'])) {
            $errors['telefon'] = 'Nur Ziffern, Leerzeichen und + ( ) / - erlaubt.';
        }
        if ($data['plz'] !== null && !preg_match('/^[0-9A-Za-z\- ]{3,10}$/', $data['plz'])) {
            $errors['plz'] = 'Bitte eine gültige Postleitzahl eingeben.';
        }

        // Contacts (parents for families)
        $contacts = [];
        $main = isset($input['hauptkontakt']) && is_string($input['hauptkontakt']) ? $input['hauptkontakt'] : null;
        foreach (self::rows($input['ansprechpartner'] ?? null) as $i => $row) {
            $rowErrors = [];
            $contact = ['id' => self::id($row)];
            foreach (['vorname', 'nachname', 'rolle', 'email', 'telefon'] as $field) {
                $contact[$field] = self::text($row[$field] ?? null, $field, $rowErrors);
            }
            if (count(array_filter([$contact['vorname'], $contact['nachname'], $contact['email'], $contact['telefon'], $contact['rolle']])) === 0) {
                continue; // empty slot or cleared row (= remove)
            }
            if ($contact['email'] !== null) {
                $contact['email'] = mb_strtolower($contact['email']);
                if (!self::validEmail($contact['email'])) {
                    $rowErrors[] = 'Ungültige E-Mail-Adresse.';
                }
            }
            if ($contact['telefon'] !== null && !self::validPhone($contact['telefon'])) {
                $rowErrors[] = 'Telefon: nur Ziffern, Leerzeichen und + ( ) / -.';
            }
            if ($contact['vorname'] === null && $contact['nachname'] === null) {
                $rowErrors[] = 'Bitte Vor- oder Nachnamen angeben.';
            }
            if ($rowErrors) {
                $errors["ansprechpartner.$i"] = implode(' ', array_unique($rowErrors));
            }
            $contact['ist_hauptkontakt'] = $main === (string) $i ? 1 : 0;
            $contacts[$i] = $contact;
        }
        // Exactly one main contact: the chosen one, else the first
        if ($contacts && !in_array(1, array_column($contacts, 'ist_hauptkontakt'), true)) {
            $contacts[array_key_first($contacts)]['ist_hauptkontakt'] = 1;
        }
        if ($privat && !$contacts && $data['email'] === null) {
            $errors['ansprechpartner'] = 'Bitte mindestens einen Elternteil oder eine E-Mail-Adresse angeben.';
        }

        // Children (families only)
        $kinder = [];
        foreach (self::rows($input['kinder'] ?? null) as $i => $row) {
            $rowErrors = [];
            $kind = ['id' => self::id($row)];
            foreach (['vorname', 'nachname'] as $field) {
                $kind[$field] = self::text($row[$field] ?? null, $field, $rowErrors);
            }
            foreach (['abholberechtigte', 'notizen'] as $field) {
                $kind[$field] = self::text($row[$field] ?? null, $field, $rowErrors, multiline: true);
            }
            $kind['geburtsdatum'] = self::text($row['geburtsdatum'] ?? null, 'geburtsdatum', $rowErrors);
            $year = self::text($row['geburtsjahr'] ?? null, 'geburtsjahr', $rowErrors);

            if (count(array_filter([$kind['vorname'], $kind['nachname'], $kind['geburtsdatum'], $year, $kind['abholberechtigte'], $kind['notizen']])) === 0) {
                continue;
            }
            if ($kind['vorname'] === null) {
                $rowErrors[] = 'Bitte den Vornamen angeben.';
            }
            if ($kind['geburtsdatum'] !== null) {
                if (!self::validDate($kind['geburtsdatum'])) {
                    $rowErrors[] = 'Ungültiges Geburtsdatum (nicht in der Zukunft).';
                } else {
                    $year = substr($kind['geburtsdatum'], 0, 4); // the date wins over a separate year
                }
            }
            if ($year !== null && (!ctype_digit($year) || (int) $year < 1990 || (int) $year > (int) date('Y'))) {
                $rowErrors[] = 'Geburtsjahr bitte vierstellig (z. B. 2017).';
            }
            $kind['geburtsjahr'] = $year === null || !ctype_digit($year) ? null : (int) $year;
            if ($rowErrors) {
                $errors["kinder.$i"] = implode(' ', array_unique($rowErrors));
            }
            $kinder[$i] = $kind;
        }
        if (!$privat && $kinder) {
            $errors['kinder'] = 'Kinder können nur bei Privatkunden (Eltern) hinterlegt werden. Bitte zuerst entfernen.';
        }

        // Consents: date given, date withdrawn
        $consents = [];
        foreach (Kundentypen::EINWILLIGUNGEN as $art => $label) {
            $row = is_array($input['einwilligungen'][$art] ?? null) ? $input['einwilligungen'][$art] : [];
            $given = self::text($row['erteilt_am'] ?? null, 'erteilt_am', $errors);
            $withdrawn = self::text($row['widerrufen_am'] ?? null, 'widerrufen_am', $errors);
            if (($given !== null && !self::validDate($given)) || ($withdrawn !== null && !self::validDate($withdrawn))) {
                $errors["einwilligungen.$art"] = "$label: ungültiges Datum (nicht in der Zukunft).";
            } elseif ($given !== null && $withdrawn !== null && $withdrawn < $given) {
                $errors["einwilligungen.$art"] = "$label: Der Widerruf liegt vor der Einwilligung.";
            }
            $consents[$art] = ['erteilt_am' => $given, 'widerrufen_am' => $withdrawn];
        }

        return ['data' => $data, 'contacts' => $contacts, 'kinder' => $kinder, 'consents' => $consents, 'errors' => $errors];
    }

    public static function validEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 254;
    }

    private static function validPhone(string $phone): bool
    {
        return (bool) preg_match('/^[0-9+()\/\- ]{3,50}$/', $phone);
    }

    private static function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false
            && $date->format('Y-m-d') === $value
            && $date <= new \DateTimeImmutable('today')
            && (int) $date->format('Y') >= 1900;
    }

    private static function text(mixed $value, string $field, array &$errors, bool $multiline = false): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = $multiline ? trim(str_replace("\r\n", "\n", $value)) : trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return null;
        }
        $max = self::MAX[$field] ?? 255;
        if (mb_strlen($value) > $max) {
            $errors[$field] = "Höchstens $max Zeichen.";
        }
        return $value;
    }

    private static function rows(mixed $rows): array
    {
        return is_array($rows) ? array_filter($rows, 'is_array') : [];
    }

    private static function id(array $row): ?int
    {
        return isset($row['id']) && ctype_digit((string) $row['id']) ? (int) $row['id'] : null;
    }
}
