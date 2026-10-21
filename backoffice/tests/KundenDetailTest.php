<?php
declare(strict_types=1);

// Customer detail page and form: order of the sections for families (Eltern) and for the other types,
// the slimmed "Familie" section, the children rows and the stored values the family form leaves out.
// Templates are rendered directly (no database, no layout).

use Backoffice\Modules\Kinder\Alter;
use Backoffice\Modules\Kunden\KundenValidator;
use Backoffice\Modules\Kunden\Kundentypen;

if (!defined('BACKOFFICE_ROOT')) {
    define('BACKOFFICE_ROOT', dirname(__DIR__));
}
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return Backoffice\View::e($value);
    }
}

$render = static function (string $template, array $vars): string {
    $_SESSION ??= [];
    extract($vars, EXTR_SKIP);
    ob_start();
    require BACKOFFICE_ROOT . '/templates/' . $template . '.php';
    return (string) ob_get_clean();
};
$matches = static function (string $pattern, string $html): array {
    preg_match_all($pattern, $html, $m);
    return array_map(static fn ($s) => trim(strip_tags($s)), $m[1]);
};
$legends = static fn (string $html): array => $matches('~<legend>(.*?)</legend>~s', $html);
$headings = static fn (string $html): array => $matches('~<h2>(.*?)</h2>~s', $html);
// Field names inside the fieldset with the given legend
$fieldsIn = static function (string $html, string $legend) use ($matches): array {
    preg_match('~<legend>' . preg_quote($legend, '~') . '</legend>(.*?)</fieldset>~s', $html, $m);
    return $matches('~ name="([a-z_]+)"~', $m[1] ?? '');
};

$kunde = static fn (string $typ, array $extra = []): array => $extra + [
    'id' => 7, 'typ' => $typ, 'name' => $typ === 'privat' ? null : 'Grundschule Test', 'vorname' => 'Anna', 'nachname' => 'Muster',
    'email' => 'familie@example.de', 'telefon' => '089 123', 'adresse' => 'Weg 1', 'plz' => '80331', 'ort' => 'München',
    'status' => null, 'quelle' => 'Website', 'notizen' => null, 'erstellt_am' => '2026-10-01 10:00:00', 'geaendert_am' => '2026-10-02 10:00:00',
    'hk_vorname' => 'Anna', 'hk_nachname' => 'Muster',
    'ansprechpartner' => [['id' => 1, 'vorname' => 'Anna', 'nachname' => 'Muster', 'rolle' => 'Mutter', 'email' => 'anna@example.de', 'telefon' => null, 'ist_hauptkontakt' => 1]],
    'kinder' => $typ === 'privat' ? [
        ['id' => 11, 'vorname' => 'Ben', 'nachname' => 'Muster', 'geburtsdatum' => '2018-05-01', 'geburtsjahr' => 2018, 'abholberechtigte' => 'Oma Inge', 'notizen' => 'Allergie: Nüsse'],
        ['id' => 12, 'vorname' => 'Mia', 'nachname' => 'Muster', 'geburtsdatum' => null, 'geburtsjahr' => 2020, 'abholberechtigte' => null, 'notizen' => null],
    ] : [],
    'einwilligungen' => [],
];
$form = static function (array $kunde, bool $newChild = false) use ($render): string {
    $contacts = $kunde['ansprechpartner'];
    $contacts[] = ['id' => null, 'ist_hauptkontakt' => 0];
    return $render('kunden/form', [
        'id' => $kunde['id'], 'kunde' => $kunde, 'contacts' => $contacts, 'kinder' => $kunde['kinder'], 'newChild' => $newChild,
        'consents' => [], 'errors' => [], 'zurueck' => null,
    ]);
};
$show = static fn (array $kunde): string => $render('kunden/show', ['kunde' => $kunde, 'protokoll' => [], 'auftraege' => []]);
$childRows = static fn (string $html): array => $matches('~name="kinder\[(\d+)\]\[vorname\]" value="([^"]*)"~', $html);

return [
    // Form (Bearbeiten)
    ['Formular Eltern: Kundentyp → Eltern → Familie → Kinder → Rest', static function () use ($form, $kunde, $legends): void {
        assertSame(['Kundentyp', 'Eltern', 'Familie', 'Kinder', 'Einwilligungen', 'Notizen'], $legends($form($kunde('privat'))));
    }],
    ['Formular Eltern: „Familie“ nur Adresse, Status, Quelle', static function () use ($form, $kunde, $fieldsIn): void {
        assertSame(['adresse', 'plz', 'ort', 'status', 'quelle'], $fieldsIn($form($kunde('privat')), 'Familie'));
    }],
    ['Formular Eltern: genau die gespeicherten Kinder, kein leeres Zusatzformular', static function () use ($form, $kunde, $childRows): void {
        $html = $form($kunde('privat'));
        assertSame(['0', '1'], $childRows($html));
        assertSame(0, substr_count(explode('<template', $html)[0], 'data-new-child'));
        assertSame(1, substr_count($html, 'id="add-child"'));
    }],
    ['Formular Eltern: „+ Kind hinzufügen“ bringt ein leeres Formular mit denselben Feldern', static function () use ($form, $kunde, $matches): void {
        $html = $form($kunde('privat'));
        preg_match('~<template id="child-template">(.*?)</template>~s', $html, $t);
        $fields = static fn (string $s) => array_map(static fn ($n) => preg_replace('~^kinder\[[^\]]+\]~', '', $n), $matches('~ name="(kinder\[[^"]+)"~', $s));
        $stored = $fields(explode('<template', $html)[0]);
        // Template: index placeholder, same fields as a stored child (without the hidden id)
        assertSame(array_values(array_unique(array_diff($stored, ['[id]']))), $fields($t[1]));
        assertSame(true, str_contains($t[1], 'name="kinder[__i__][vorname]"'));
        // Without JavaScript (or from the detail page): the form is rendered with one new empty child
        $withNew = $form($kunde('privat'), true);
        assertSame(1, substr_count(explode('<template', $withNew)[0], 'data-new-child'));
        assertSame(true, str_contains($withNew, 'name="kinder[2][vorname]" value=""'));
    }],
    ['Formular andere Typen: Reihenfolge wie bisher, kein Kinder-Bereich', static function () use ($form, $kunde, $legends): void {
        $html = $form($kunde('schule'));
        assertSame(['Kundentyp', 'Einrichtung', 'Ansprechpartner', 'Einwilligungen', 'Notizen'], $legends($html));
        assertSame(false, str_contains($html, 'id="add-child"'));
    }],
    ['Formular jeder Einrichtungstyp: „Einrichtung“ ohne Vor-/Nachname, Ansprechpartner mit', static function () use ($form, $kunde, $fieldsIn): void {
        $types = array_keys(array_filter(Kundentypen::ALLE, static fn ($t, $k) => !Kundentypen::isPrivat($k), ARRAY_FILTER_USE_BOTH));
        assertSame(['schule', 'hort', 'kita', 'verein', 'firma', 'bibliothek', 'partner', 'oeffentlich', 'sonstige'], $types);
        foreach ($types as $typ) {
            $html = $form($kunde($typ));
            assertSame(['name', 'email', 'telefon', 'adresse', 'plz', 'ort', 'status', 'quelle'], $fieldsIn($html, 'Einrichtung'), $typ);
            preg_match('~<legend>Ansprechpartner</legend>(.*?)</fieldset>~s', $html, $m);
            assertSame(true, str_contains($m[1], 'name="ansprechpartner[0][vorname]" value="Anna"') && str_contains($m[1], 'name="ansprechpartner[0][nachname]" value="Muster"'), $typ);
        }
    }],
    ['Feldkonfiguration an einer Stelle: Eltern unverändert (Adresse, Status, Quelle)', static function (): void {
        assertSame(['adresse', 'plz', 'ort', 'status', 'quelle'], Kundentypen::stammdatenFelder('privat'));
        assertSame(['name', 'email', 'telefon', 'adresse', 'plz', 'ort', 'status', 'quelle'], Kundentypen::stammdatenFelder('kita'));
    }],
    // Detail page (Ansehen)
    ['Detailseite Eltern: Eltern → Familie → Kinder → Rest', static function () use ($show, $kunde, $headings): void {
        assertSame(['Eltern', 'Familie', 'Kinder', 'Einwilligungen', 'Notizen', 'Aufträge', 'Buchungen', 'Rechnungen', 'Änderungsprotokoll'], $headings($show($kunde('privat'))));
    }],
    ['Detailseite Eltern: „Familie“ nur Adresse, Status, Quelle; Kinder genau die gespeicherten', static function () use ($show, $kunde, $matches): void {
        $html = $show($kunde('privat'));
        preg_match('~<h2>Familie</h2>(.*?)</section>~s', $html, $f);
        assertSame(['Adresse', 'Status', 'Quelle'], $matches('~<dt>(.*?)</dt>~', $f[1]));
        preg_match('~<h2>Kinder</h2>(.*?)</section>~s', $html, $k);
        assertSame(['Ben Muster', 'Mia Muster'], $matches('~<strong>(.*?)</strong>~', $k[1]));
        assertSame(true, str_contains($k[1], 'kind=neu'));
    }],
    ['Detailseite andere Typen: Bereiche wie bisher, Stammdaten ohne Vor-/Nachname', static function () use ($show, $kunde, $headings, $matches): void {
        foreach (['schule', 'bibliothek', 'firma'] as $typ) {
            $html = $show($kunde($typ));
            assertSame(['Stammdaten', 'Ansprechpartner', 'Einwilligungen', 'Notizen', 'Aufträge', 'Buchungen', 'Rechnungen', 'Änderungsprotokoll'], $headings($html), $typ);
            preg_match('~<h2>Stammdaten</h2>(.*?)</section>~s', $html, $f);
            assertSame(['Name', 'Status', 'E-Mail', 'Telefon', 'Adresse', 'Quelle', 'Angelegt', 'Geändert'], $matches('~<dt>(.*?)</dt>~', $f[1]), $typ);
            preg_match('~<h2>Ansprechpartner</h2>(.*?)</section>~s', $html, $c);
            assertSame(['Anna Muster'], $matches('~<strong>(.*?)</strong>~', $c[1]), $typ);
        }
    }],
    ['Schnellansicht: Einrichtung ohne Vor-/Nachname, Eltern unverändert', static function () use ($render, $kunde, $matches): void {
        $quick = static fn (array $k): array => $matches('~<dt>(.*?)</dt>~', explode('<h3', $render('kunden/quick', ['kunde' => $k, 'zurueck' => null]))[0]);
        assertSame(false, in_array('Vorname', $quick($kunde('schule')), true));
        assertSame(true, in_array('Vorname', $quick($kunde('privat')), true) && in_array('Nachname', $quick($kunde('privat')), true));
    }],
    ['Speichern Einrichtung: Vor-/Nachname bleiben, gesendete Felder werden übernommen', static function () use ($kunde): void {
        $stored = $kunde('schule', ['vorname' => 'Gabi', 'nachname' => 'Lehrer']);
        $input = ['typ' => 'schule', 'name' => 'Grundschule Neu', 'telefon' => '089 999', 'ort' => 'Pullach', 'ansprechpartner' => [['vorname' => 'Anna', 'nachname' => 'Muster']]];
        $r = KundenValidator::keepUnsentFields(KundenValidator::validate($input, 7), $input, $stored);
        assertSame([], $r['errors']);
        assertSame(['Grundschule Neu', 'Gabi', 'Lehrer', '089 999', 'Pullach'], [$r['data']['name'], $r['data']['vorname'], $r['data']['nachname'], $r['data']['telefon'], $r['data']['ort']]);
    }],
    // Children: only name and birthdate (pick-up persons, notes and birth year hidden, but kept)
    ['Kinder im Formular: nur Vorname, Nachname, Geburtsdatum (auch beim neuen Kind)', static function () use ($form, $kunde, $matches): void {
        $html = $form($kunde('privat'), true);
        [$rows, $template] = explode('<template', $html, 2);
        $fields = static fn (string $part): array => array_values(array_unique(array_map(static fn ($n) => preg_replace('~^kinder\[[^\]]+\]~', '', $n), $matches('~ name="(kinder\[[^"]+)"~', $part))));
        assertSame(['[id]', '[vorname]', '[nachname]', '[geburtsdatum]'], $fields($rows));
        assertSame(['[vorname]', '[nachname]', '[geburtsdatum]'], $fields($template));
        preg_match('~<legend>Kinder</legend>(.*?)</fieldset>~s', $html, $k);
        foreach (['Abholberechtigte', 'Notizen', 'Jahr', 'Oma Inge', 'Allergie'] as $hidden) {
            assertSame(false, str_contains(str_replace('Geburtsdatum', '', $k[1]), $hidden), $hidden);
        }
        assertSame(true, str_contains($k[1], 'type="date" name="kinder[0][geburtsdatum]" value="2018-05-01"'));
    }],
    ['Kinder auf der Detailseite: Name und Alter aus dem Geburtsdatum, ohne Abholberechtigte, Notizen, Jahr', static function () use ($show, $kunde): void {
        preg_match('~<h2>Kinder</h2>(.*?)</section>~s', $show($kunde('privat')), $k);
        foreach (['Abholberechtigt', 'Oma Inge', 'Notiz', 'Allergie', 'Jg.', 'ca.'] as $hidden) {
            assertSame(false, str_contains($k[1], $hidden), $hidden);
        }
        assertSame(true, str_contains($k[1], '<strong>Ben Muster</strong>') && str_contains($k[1], '(01.05.2018)'));
        assertSame(true, str_contains($k[1], '<strong>Mia Muster</strong> ') || str_contains($k[1], 'Alter unbekannt'));
    }],
    ['Alter nur aus dem Geburtsdatum', static function (): void {
        $date = (new DateTimeImmutable('today'))->modify('-7 years -3 days');
        assertSame('7 Jahre (' . $date->format('d.m.Y') . ')', Alter::text($date->format('Y-m-d')));
        assertSame('Alter unbekannt', Alter::text(null));
    }],
    ['Neues Kind nur mit Name und Geburtsdatum speicherbar', static function () use ($kunde): void {
        $input = ['typ' => 'privat', 'ansprechpartner' => [['vorname' => 'Anna']], 'kinder' => [['vorname' => 'Lea', 'geburtsdatum' => '2021-03-04']]];
        $r = KundenValidator::validate($input, null);
        assertSame([], $r['errors']);
        assertSame(['id' => null, 'vorname' => 'Lea', 'nachname' => null, 'geburtsdatum' => '2021-03-04', 'geburtsjahr' => 2021], $r['kinder'][0]);
    }],
    ['Bestehendes Kind speichern: Abholberechtigte, Notizen, Jahr werden nicht mitgeschrieben (bleiben)', static function () use ($kunde): void {
        $input = ['typ' => 'privat', 'ansprechpartner' => [['vorname' => 'Anna']],
            'kinder' => [['id' => '11', 'vorname' => 'Ben', 'nachname' => 'Muster', 'geburtsdatum' => '2018-05-01'], ['id' => '12', 'vorname' => 'Mia', 'nachname' => 'Muster', 'geburtsdatum' => '']]];
        $r = KundenValidator::validate($input, 7);
        assertSame([], $r['errors']);
        // Only these columns are updated (KundenRepository::syncRows updates the fields a row carries)
        assertSame(['id', 'vorname', 'nachname', 'geburtsdatum', 'geburtsjahr'], array_keys($r['kinder'][0]));
        assertSame(['id', 'vorname', 'nachname', 'geburtsdatum'], array_keys($r['kinder'][1]));
    }],
    // Saving keeps what the family form does not show
    ['Speichern Eltern: Vorname, Nachname, E-Mail, Telefon bleiben erhalten', static function () use ($kunde): void {
        $stored = $kunde('privat');
        $input = ['typ' => 'privat', 'adresse' => 'Neuer Weg 2', 'ort' => 'München', 'ansprechpartner' => [['vorname' => 'Anna']]];
        $r = KundenValidator::keepUnsentFields(KundenValidator::validate($input, 7), $input, $stored);
        assertSame([], $r['errors']);
        assertSame(['Anna', 'Muster', 'familie@example.de', '089 123', 'Neuer Weg 2'],
            [$r['data']['vorname'], $r['data']['nachname'], $r['data']['email'], $r['data']['telefon'], $r['data']['adresse']]);
    }],
    ['Speichern Eltern nur mit E-Mail (ohne Elternteil): kein Fehler, E-Mail bleibt', static function () use ($kunde): void {
        $stored = $kunde('privat', ['vorname' => null, 'nachname' => null, 'telefon' => null]);
        $input = ['typ' => 'privat', 'quelle' => 'Import'];
        $r = KundenValidator::keepUnsentFields(KundenValidator::validate($input, 7), $input, $stored);
        assertSame([], $r['errors']);
        assertSame('familie@example.de', $r['data']['email']);
    }],
    ['Gesendete Felder (Formular anderer Typen) gelten weiter, auch geleert', static function (): void {
        $result = ['data' => ['vorname' => null, 'nachname' => 'Neu', 'email' => null, 'telefon' => null], 'errors' => []];
        $input = ['vorname' => '', 'nachname' => 'Neu', 'email' => '', 'telefon' => ''];
        $r = KundenValidator::keepUnsentFields($result, $input, ['vorname' => 'Alt', 'nachname' => 'Alt', 'email' => 'a@example.de', 'telefon' => '1']);
        assertSame(['vorname' => null, 'nachname' => 'Neu', 'email' => null, 'telefon' => null], $r['data']);
    }],
    ['Ohne Elternteil und ohne gespeicherte E-Mail bleibt der Hinweis', static function () use ($kunde): void {
        $stored = $kunde('privat', ['email' => null]);
        $input = ['typ' => 'privat'];
        $r = KundenValidator::keepUnsentFields(KundenValidator::validate($input, 7), $input, $stored);
        assertSame(true, isset($r['errors']['ansprechpartner']));
    }],
];
