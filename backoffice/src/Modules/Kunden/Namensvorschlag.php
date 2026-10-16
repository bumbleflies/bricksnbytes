<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

// First and last name suggested from the local part of an e-mail address:
// "anna.mueller@…" → Anna / Mueller, "anna-maria.schmidt@…" → Anna-Maria / Schmidt.
// Only ever a suggestion — the caller shows it and the user decides by saving.
final class Namensvorschlag
{
    // Address words that are no names (info@, familie123@, sekretariat@ …)
    private const GENERIC = [
        'admin', 'anmeldung', 'bestellung', 'buchhaltung', 'buero', 'büro', 'contact', 'email', 'familie', 'family',
        'hallo', 'hello', 'hort', 'info', 'kindergarten', 'kita', 'kontakt', 'leitung', 'mail', 'news', 'newsletter',
        'noreply', 'office', 'post', 'postfach', 'privat', 'rechnung', 'schule', 'schulleitung', 'sekretariat',
        'service', 'support', 'team', 'test', 'verwaltung', 'webmaster',
    ];

    // Words in front of a surname: "familie.mueller@" → last name Mueller
    private const FAMILY = ['familie', 'family'];

    /** @return array{vorname: ?string, nachname: ?string}|null null = nothing sensible to suggest */
    public static function fromEmail(?string $email): ?array
    {
        $local = mb_strtolower(trim(explode('@', (string) $email, 2)[0]));
        $local = explode('+', $local, 2)[0];                  // anna+kurse@ → anna
        $local = (string) preg_replace('/\d+/u', '', $local); // drop digits

        $parts = [];
        // true when the one remaining word is most likely a surname ("familie.mueller@", "a.mueller@")
        $surname = false;
        foreach (preg_split('/[._]+/u', $local) ?: [] as $part) {
            $part = trim((string) preg_replace('/[^\p{L}-]+/u', '', $part), '-');
            $part = (string) preg_replace('/-{2,}/', '-', $part);
            if (in_array($part, self::GENERIC, true)) {
                $surname = $surname || (in_array($part, self::FAMILY, true) && !$parts);
                continue;
            }
            // Single letters are initials ("a.mueller"), not a name
            if (mb_strlen(str_replace('-', '', $part)) < 2) {
                $surname = $surname || ($part !== '' && !$parts);
                continue;
            }
            $parts[] = self::capitalize($part);
        }

        if (!$parts) {
            return null;
        }
        if (count($parts) === 1) {
            return $surname
                ? ['vorname' => null, 'nachname' => self::cut($parts[0])]
                : ['vorname' => self::cut($parts[0]), 'nachname' => null];
        }
        $last = array_pop($parts);
        return ['vorname' => self::cut(implode(' ', $parts)), 'nachname' => self::cut($last)];
    }

    // "anna-maria" → "Anna-Maria"
    private static function capitalize(string $word): string
    {
        return implode('-', array_map(
            static fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1),
            explode('-', $word)
        ));
    }

    private static function cut(string $value): string
    {
        return mb_substr($value, 0, 100);
    }
}
