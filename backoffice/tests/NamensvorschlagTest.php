<?php
declare(strict_types=1);

use Backoffice\Modules\Kunden\Namensvorschlag;

$expect = static function (string $email, ?string $vorname, ?string $nachname): array {
    $label = $vorname === null && $nachname === null ? 'kein Vorschlag' : trim(($vorname ?? '–') . ' / ' . ($nachname ?? '–'));
    return ["$email → $label", static function () use ($email, $vorname, $nachname): void {
        $result = Namensvorschlag::fromEmail($email);
        assertSame($vorname === null && $nachname === null ? null : ['vorname' => $vorname, 'nachname' => $nachname], $result, $email);
    }];
};

return [
    // Examples from the task
    $expect('anna.mueller@example.de', 'Anna', 'Mueller'),
    $expect('anna_mueller@example.de', 'Anna', 'Mueller'),
    $expect('anna-maria.schmidt@example.de', 'Anna-Maria', 'Schmidt'),
    $expect('info@example.de', null, null),
    $expect('familie123@example.de', null, null),
    // One word only → first name only
    $expect('anna@example.de', 'Anna', null),
    // Digits removed, first letter capital, rest lower case
    $expect('anna.mueller84@example.de', 'Anna', 'Mueller'),
    $expect('ANNA.MUELLER@example.de', 'Anna', 'Mueller'),
    $expect('max2.mustermann1990@example.de', 'Max', 'Mustermann'),
    // Several first names, double-barrelled last name
    $expect('anna.maria.schmidt@example.de', 'Anna Maria', 'Schmidt'),
    $expect('lena.meier-huber@example.de', 'Lena', 'Meier-Huber'),
    // Umlauts stay
    $expect('jörg.müller@example.de', 'Jörg', 'Müller'),
    // Generic words and initials are dropped; in front of a single word they mark it as the last name
    $expect('familie.mueller@example.de', null, 'Mueller'),
    $expect('kontakt.anna@example.de', 'Anna', null),
    $expect('sekretariat@grundschule.example', null, null),
    $expect('a.mueller@example.de', null, 'Mueller'),
    $expect('mueller.a@example.de', 'Mueller', null),
    $expect('12345@example.de', null, null),
    // Sub-addressing and odd characters
    $expect('anna.mueller+kurse@example.de', 'Anna', 'Mueller'),
    $expect('anna!.mueller@example.de', 'Anna', 'Mueller'),
    $expect('..anna__mueller..@example.de', 'Anna', 'Mueller'),
    $expect('-anna-.mueller@example.de', 'Anna', 'Mueller'),
    ['leere oder fehlende Adresse → kein Vorschlag', static function (): void {
        assertSame(null, Namensvorschlag::fromEmail(''));
        assertSame(null, Namensvorschlag::fromEmail(null));
    }],
];
