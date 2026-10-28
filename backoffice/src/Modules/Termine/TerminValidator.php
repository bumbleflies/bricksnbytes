<?php
declare(strict_types=1);

namespace Backoffice\Modules\Termine;

// Own calendar entry: form input → clean values (empty → null) and German error messages. Pure.
final class TerminValidator
{
    /** @return array{data: array, errors: array<string, string>} */
    public static function validate(array $input): array
    {
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        $errors = [];
        $data = [];

        $data['titel'] = $str('titel') !== '' ? mb_substr($str('titel'), 0, 255) : null;
        if ($data['titel'] === null) {
            $errors['titel'] = 'Bitte angeben, was ansteht.';
        }
        foreach (['datum' => true, 'bis_datum' => false] as $key => $required) {
            $value = $str($key);
            $valid = (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
            if (($value !== '' && !$valid) || ($required && $value === '')) {
                $errors[$key] = $required && $value === '' ? 'Bitte ein Datum wählen.' : 'Bitte ein gültiges Datum wählen.';
            }
            $data[$key] = $valid ? $value : null;
        }
        if ($data['datum'] !== null && $data['bis_datum'] !== null) {
            if ($data['bis_datum'] < $data['datum']) {
                $errors['bis_datum'] = 'Das Ende liegt vor dem Beginn.';
            } elseif ($data['bis_datum'] === $data['datum']) {
                $data['bis_datum'] = null;
            }
        }
        foreach (['beginn', 'ende'] as $key) {
            $value = $str($key);
            $valid = (bool) preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value);
            if ($value !== '' && !$valid) {
                $errors[$key] = 'Bitte eine Uhrzeit wie 14:30 eingeben.';
            }
            $data[$key] = $valid ? $value : null;
        }
        if ($data['beginn'] === null && $data['ende'] !== null) {
            $errors['beginn'] = 'Bitte auch den Beginn angeben.';
        } elseif ($data['beginn'] !== null && $data['ende'] !== null && $data['bis_datum'] === null && $data['ende'] < $data['beginn']) {
            $errors['ende'] = 'Das Ende liegt vor dem Beginn.';
        }
        $data['art'] = $str('art') !== '' && isset(Kalender::ARTEN[$str('art')]) ? $str('art') : null;
        $notes = $str('notizen');
        $data['notizen'] = $notes !== '' ? mb_substr($notes, 0, 5000) : null;
        return ['data' => $data, 'errors' => $errors];
    }
}
