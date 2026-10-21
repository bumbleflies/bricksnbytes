<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auftraege;

// Order form input → clean values (empty → null) and German error messages. Pure: whether the
// customer exists is checked by the controller; the allowed contact ids are passed in.
final class AuftragValidator
{
    private const MAX = ['titel' => 200, 'termine' => 200, 'einsatzort' => 200, 'notizen' => 5000, 'status_kommentar' => 2000];

    /**
     * @param list<int> $contactIds contact persons of the chosen customer
     * @return array{data: array, kommentar: ?string, errors: array<string, string>}
     */
    public static function validate(array $input, array $contactIds): array
    {
        $errors = [];
        $str = static fn (string $key): string => is_string($input[$key] ?? null) ? trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $input[$key])) : '';
        $text = static function (string $key) use ($str, &$errors): ?string {
            $value = $str($key);
            if (mb_strlen($value) > (self::MAX[$key] ?? 200)) {
                $errors[$key] = 'Bitte höchstens ' . (self::MAX[$key] ?? 200) . ' Zeichen.';
            }
            return $value === '' ? null : $value;
        };
        $data = [];

        $data['titel'] = $text('titel');
        if ($data['titel'] === null) {
            $errors['titel'] = 'Bitte einen Titel angeben, z. B. „Projekttag Robotik 3a“.';
        }
        $data['kunde_id'] = ctype_digit($str('kunde_id')) ? (int) $str('kunde_id') : null;
        if ($data['kunde_id'] === null) {
            $errors['kunde_id'] = 'Bitte einen Kunden wählen.';
        }
        $contact = $str('ansprechpartner_id');
        $data['ansprechpartner_id'] = ctype_digit($contact) && in_array((int) $contact, $contactIds, true) ? (int) $contact : null;

        $data['format'] = isset(Auftragsdaten::TYPEN[$str('format')]) ? $str('format') : null;
        if ($data['format'] === null) {
            $errors['format'] = 'Bitte einen Typ wählen.';
        }
        $data['status'] = isset(Auftragsdaten::STATUS[$str('status')]) ? $str('status') : null;
        if ($data['status'] === null) {
            $errors['status'] = 'Bitte einen Status wählen.';
            $data['status'] = 'anfrage';
        }

        $cents = Auftragsdaten::parsePreis($str('preis'));
        if ($cents === null) {
            $errors['preis'] = 'Bitte einen Betrag wie 450 oder 1.234,50 eingeben.';
        }
        $data['summe_cent'] = $cents ?? 0;
        $data['preis_einheit'] = isset(Auftragsdaten::PREIS_EINHEITEN[$str('preis_einheit')]) ? $str('preis_einheit') : 'gesamt';

        foreach (['klassen_anzahl' => 'Klassen', 'teilnehmer_anzahl' => 'Kinder'] as $key => $label) {
            $value = $str($key);
            if ($value !== '' && (!ctype_digit($value) || (int) $value > 9999)) {
                $errors[$key] = "Bitte die Anzahl {$label} als Zahl eingeben.";
            }
            $data[$key] = $value !== '' && ctype_digit($value) ? (int) $value : null;
        }
        $data['regelmaessig'] = $str('regelmaessig') === '1' ? 1 : 0;
        $data['termine'] = $text('termine');
        $data['einsatzort'] = $text('einsatzort');
        $data['notizen'] = $text('notizen');

        foreach (['leistung_von', 'leistung_bis'] as $key) {
            $value = $str($key);
            $valid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
            if ($value !== '' && !$valid) {
                $errors[$key] = 'Bitte ein gültiges Datum wählen.';
            }
            $data[$key] = $valid ? $value : null;
        }
        if ($data['leistung_von'] !== null && $data['leistung_bis'] !== null && $data['leistung_bis'] < $data['leistung_von']) {
            $errors['leistung_bis'] = 'Das Ende liegt vor dem Beginn.';
        }

        $kommentar = $text('status_kommentar');
        return ['data' => $data, 'kommentar' => $kommentar, 'errors' => $errors];
    }
}
