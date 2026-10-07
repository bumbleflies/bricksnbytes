<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

// Turns raw form input into clean values (empty -> null) and collects German error messages
final class KundenValidator
{
    private const MAX = ['vorname' => 100, 'nachname' => 100, 'email' => 254, 'telefon' => 50, 'adresse' => 200, 'plz' => 10, 'ort' => 100];

    /** @return array{data: array, kinder: array, errors: array} */
    public static function validate(array $input, ?int $kundeId): array
    {
        $data = [];
        $errors = [];

        foreach (array_diff(KundenRepository::FIELDS, ['newsletter']) as $field) {
            $value = is_string($input[$field] ?? null) ? trim(preg_replace('/\s+/u', ' ', $input[$field])) : '';
            if (mb_strlen($value) > self::MAX[$field]) {
                $errors[$field] = 'Höchstens ' . self::MAX[$field] . ' Zeichen.';
            }
            $data[$field] = $value === '' ? null : $value;
        }

        // Checkbox: present = subscribed
        $data['newsletter'] = !empty($input['newsletter']) ? 1 : 0;

        if ($data['email'] !== null) {
            $data['email'] = mb_strtolower($data['email']);
            if (!self::validEmail($data['email'])) {
                $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
            } elseif (KundenRepository::emailTaken($data['email'], $kundeId)) {
                $errors['email'] = 'Diese E-Mail-Adresse gehört schon zu einem anderen Kunden.';
            }
        }
        if ($data['telefon'] !== null && !preg_match('/^[0-9+()\/\- ]{3,50}$/', $data['telefon'])) {
            $errors['telefon'] = 'Nur Ziffern, Leerzeichen und + ( ) / - erlaubt.';
        }
        if ($data['plz'] !== null && !preg_match('/^[0-9A-Za-z\- ]{3,10}$/', $data['plz'])) {
            $errors['plz'] = 'Bitte eine gültige Postleitzahl eingeben.';
        }
        if ($data['vorname'] === null && $data['nachname'] === null && $data['email'] === null) {
            $errors['nachname'] = 'Bitte mindestens Name oder E-Mail-Adresse angeben.';
        }

        $kinder = [];
        foreach (is_array($input['kinder'] ?? null) ? $input['kinder'] : [] as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $vorname = is_string($row['vorname'] ?? null) ? trim($row['vorname']) : '';
            $geburt = is_string($row['geburtsdatum'] ?? null) ? trim($row['geburtsdatum']) : '';
            $id = isset($row['id']) && ctype_digit((string) $row['id']) ? (int) $row['id'] : null;

            if ($vorname === '' && $geburt === '') {
                continue; // empty slot (or a child removed by clearing its fields)
            }
            if ($vorname === '') {
                $errors["kinder.$i"] = 'Bitte den Vornamen des Kindes angeben.';
            } elseif (mb_strlen($vorname) > 100) {
                $errors["kinder.$i"] = 'Vorname höchstens 100 Zeichen.';
            }
            if ($geburt !== '' && !self::validBirthdate($geburt)) {
                $errors["kinder.$i"] = 'Bitte ein gültiges Geburtsdatum (nicht in der Zukunft) eingeben.';
            }
            $kinder[$i] = ['id' => $id, 'vorname' => $vorname, 'geburtsdatum' => $geburt === '' ? null : $geburt];
        }

        return ['data' => $data, 'kinder' => $kinder, 'errors' => $errors];
    }

    public static function validEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 254;
    }

    private static function validBirthdate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false
            && $date->format('Y-m-d') === $value
            && $date <= new \DateTimeImmutable('today')
            && (int) $date->format('Y') >= 1900;
    }
}
