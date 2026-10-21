<?php
declare(strict_types=1);

// Customer list: status badges (colour per status, grey for unknown/empty, not clickable) and the
// columns (no Telefon / Ort; Kinder and Aktionen stay; the Ort filter stays). Rendered without database.

use Backoffice\Modules\Kunden\Kundentypen;
use Backoffice\Modules\Kunden\StatusBadge;

if (!defined('BACKOFFICE_ROOT')) {
    define('BACKOFFICE_ROOT', dirname(__DIR__));
}
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Backoffice\View::e($value);
    }
}

$list = static function (?string $typ, array $rows) {
    $_SESSION ??= [];
    $vars = [
        'rows' => $rows, 'total' => count($rows), 'typeOptions' => ['privat' => ['Eltern', 1], 'schule' => ['Schulen', 1]],
        'places' => ['München' => 1, 'Pullach' => 1], 'q' => '', 'typ' => $typ, 'sort' => 'name', 'dir' => 'asc', 'page' => 1, 'pages' => 1,
        'newsletter' => null, 'status' => null, 'ort' => 'Pullach', 'filteredAddresses' => [], 'newsletterAddresses' => [], 'ownEmail' => null,
    ];
    extract($vars, EXTR_SKIP);
    ob_start();
    require BACKOFFICE_ROOT . '/templates/kunden/index.php';
    return (string) ob_get_clean();
};
$row = static fn (string $typ, ?string $status): array => [
    'id' => 3, 'typ' => $typ, 'name' => $typ === 'privat' ? null : 'Grundschule Test', 'status' => $status, 'email' => 'a@example.de',
    'telefon' => '089 4711', 'adresse' => 'Weg 1', 'plz' => '82049', 'ort' => 'Pullach', 'hk_vorname' => 'Anna', 'hk_nachname' => 'Muster',
    'hk_rolle' => null, 'kinder_namen' => 'Ben', 'mail_adresse' => 'a@example.de', 'newsletter_seit' => null,
];
$headers = static function (string $html): array {
    preg_match('~<thead>(.*?)</thead>~s', $html, $m);
    preg_match_all('~<th[^>]*>(.*?)</th>~s', $m[1], $th);
    return array_values(array_filter(array_map(static fn ($h) => trim(preg_replace(['/[▲▼]/u', '/\s+/'], ['', ' '], strip_tags($h))), $th[1]), static fn ($h) => $h !== ''));
};

return [
    // Status badges
    ['Status-Badge: jeder Status hat seine Farbe', static function (): void {
        $expected = [
            'akquise' => 'grau', 'kontakt' => 'lila', 'angefragt' => 'lila', 'interessent' => 'gelb', 'angebot_offen' => 'orange',
            'nachfassen' => 'orange', 'warteliste' => 'gelb', 'kunde' => 'gruen', 'partner' => 'gruen', 'beendet' => 'grau',
            'kein_interesse' => 'rot', 'nicht_anschreiben' => 'rot',
        ];
        assertSame(array_keys(Kundentypen::STATUS), array_keys(StatusBadge::FARBEN), 'jeder Status zugeordnet');
        foreach ($expected as $status => $farbe) {
            assertSame($farbe, StatusBadge::farbe($status), $status);
            assertSame('<span class="status-badge status-badge--' . $farbe . '">' . e(Kundentypen::statusLabel($status)) . '</span>', StatusBadge::html($status), $status);
        }
    }],
    ['Status-Badge: unbekannt oder leer → grau', static function (): void {
        assertSame('grau', StatusBadge::farbe('gibt_es_nicht'));
        assertSame('grau', StatusBadge::farbe(null));
        assertSame('<span class="status-badge status-badge--grau">gibt_es_nicht</span>', StatusBadge::html('gibt_es_nicht'));
        assertSame('<span class="status-badge status-badge--grau">Kein Status</span>', StatusBadge::html(null));
        assertSame('<span class="status-badge status-badge--grau">&lt;b&gt;</span>', StatusBadge::html('<b>'));
    }],
    ['Status-Badge ist nicht klickbar (kein Button/Link, nicht fokussierbar, kein Hand-Cursor)', static function (): void {
        $html = StatusBadge::html('kunde');
        assertSame(1, preg_match('~^<span class="[^"]+">[^<]*</span>$~', $html));
        assertSame(false, (bool) preg_match('~tabindex|role=|href|onclick|<button|<a~', $html));
        $css = (string) file_get_contents(BACKOFFICE_ROOT . '/public/assets/app.css');
        assertSame(true, str_contains($css, '.status-badge { display: inline-block;') && str_contains($css, 'cursor: default;'));
        assertSame(false, (bool) preg_match('~\.status-badge[^{]*:(hover|focus)~', $css));
        foreach (array_unique(StatusBadge::FARBEN) as $farbe) {
            assertSame(true, str_contains($css, "--status-$farbe-bg:") && str_contains($css, "--status-$farbe-text:"), $farbe);
        }
    }],
    ['Kundenliste zeigt den Status als Badge', static function () use ($list, $row): void {
        $html = $list(null, [$row('schule', 'angebot_offen'), $row('privat', null)]);
        assertSame(true, str_contains($html, '<span class="status-badge status-badge--orange">Angebot offen</span>'));
        assertSame(true, str_contains($html, '<span class="status-badge status-badge--grau">Kein Status</span>'));
    }],
    // Columns
    ['Kundenliste (alle): ohne Telefon und Ort, Kinder und Aktionen da', static function () use ($list, $row, $headers): void {
        $html = $list(null, [$row('schule', 'kunde')]);
        assertSame(['Name', 'Typ', 'Status', 'E-Mail', 'Newsletter', 'Kinder / Ansprechpartner', 'Aktionen'], $headers($html));
        assertSame(false, str_contains($html, '089 4711'));
    }],
    ['Kundenliste Eltern: ohne Telefon und Ort, Kinder und Aktionen da', static function () use ($list, $row, $headers): void {
        $html = $list('privat', [$row('privat', 'kunde')]);
        assertSame(['Vorname', 'Nachname', 'E-Mail', 'Newsletter', 'Status', 'Adresse', 'PLZ', 'Kinder', 'Aktionen'], $headers($html));
        assertSame(false, str_contains($html, '089 4711'));
    }],
    ['Kundenliste Einrichtungstyp: ohne Ort, Aktionen da', static function () use ($list, $row, $headers): void {
        assertSame(['Name', 'Newsletter', 'Status', 'Typ', 'Ansprechpartner', 'Aktionen'], $headers($list('schule', [$row('schule', 'kunde')])));
    }],
    ['Ort-Filter bleibt (Auswahl erhalten, aktueller Ort gewählt)', static function () use ($list, $row): void {
        $html = $list(null, [$row('schule', 'kunde')]);
        assertSame(true, str_contains($html, '<select id="filter-ort" name="ort" data-autosubmit>'));
        assertSame(true, str_contains($html, '<option value="Pullach" selected>Pullach (1)</option>'));
        assertSame(true, str_contains($html, '<input id="q" name="q" type="search"'));
    }],
    ['Newsletter verschicken: Abmeldelink als Mailtext', static function () use ($list, $row): void {
        $html = $list(null, [$row('schule', 'kunde')]);
        assertSame(true, (bool) preg_match('~data-newsletter-body="[^"]*abmelden: https://bricksnbytes.de/newsletter-abmelden~', $html));
    }],
];
