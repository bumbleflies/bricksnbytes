<?php
declare(strict_types=1);

use Backoffice\Modules\Kunden\KundenFilter;

// Conditions of the WHERE clause, in order (each top-level "AND" part)
$parts = static function (KundenFilter $f): array {
    [$where] = $f->where();
    if ($where === '') {
        return [];
    }
    $sql = substr($where, strlen('WHERE '));
    // split on the top-level " AND " only (the search block has its own nested ANDs)
    $out = [];
    $depth = 0;
    $current = '';
    $tokens = preg_split('/( AND |\(|\))/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($tokens as $t) {
        if ($t === '(') { $depth++; }
        if ($t === ')') { $depth--; }
        if ($t === ' AND ' && $depth === 0) { $out[] = trim($current); $current = ''; continue; }
        $current .= $t;
    }
    $out[] = trim($current);
    return $out;
};
$isSearch = static fn (string $part): bool => str_starts_with($part, '(k.name LIKE ?');

return [
    ['ohne Suche und Filter: keine Bedingung', static function () use ($parts): void {
        $f = KundenFilter::fromQuery([]);
        assertSame('', $f->where()[0]);
        assertSame([], $f->where()[1]);
        assertSame(true, $f->isEmpty());
    }],
    ['„Alle“ (leere Werte) setzt jeden Filter zurück', static function (): void {
        $f = KundenFilter::fromQuery(['q' => '', 'typ' => '', 'newsletter' => '', 'ort' => '', 'status' => '']);
        assertSame('', $f->where()[0]);
        assertSame([], $f->params());
    }],
    ['nur Suche: ein Suchblock, 12× derselbe LIKE-Wert, Platzhalter maskiert', static function () use ($parts, $isSearch): void {
        $f = KundenFilter::fromQuery(['q' => '  50%_müller  ']);
        [$sql, $params] = $f->where();
        $p = $parts($f);
        assertSame(1, count($p));
        assertSame(true, $isSearch($p[0]));
        assertSame(12, count($params));
        assertSame(['%50\%\_müller%'], array_values(array_unique($params)));
    }],
    ['nur Typ', static function () use ($parts): void {
        $f = KundenFilter::fromQuery(['typ' => 'schule']);
        assertSame(['k.typ = ?'], $parts($f));
        assertSame(['schule'], $f->where()[1]);
    }],
    ['unbekannter Typ wird ignoriert', static function (): void {
        assertSame('', KundenFilter::fromQuery(['typ' => 'raumschiff'])->where()[0]);
    }],
    ['nur Newsletter Ja / Nein', static function () use ($parts): void {
        $ja = $parts(KundenFilter::fromQuery(['newsletter' => 'ja']));
        $nein = $parts(KundenFilter::fromQuery(['newsletter' => 'nein']));
        assertSame(true, count($ja) === 1 && str_starts_with($ja[0], 'EXISTS (SELECT 1 FROM einwilligungen'));
        assertSame(true, count($nein) === 1 && str_starts_with($nein[0], 'NOT EXISTS (SELECT 1 FROM einwilligungen'));
        assertSame('', KundenFilter::fromQuery(['newsletter' => 'vielleicht'])->where()[0]);
    }],
    ['nur Ort (exakt, Leerzeichen bereinigt)', static function () use ($parts): void {
        $f = KundenFilter::fromQuery(['ort' => '  München  ']);
        assertSame(['k.ort = ?'], $parts($f));
        assertSame(['München'], $f->where()[1]);
    }],
    ['nur Status, auch „Ohne Status“', static function () use ($parts): void {
        assertSame(['k.status = ?'], $parts(KundenFilter::fromQuery(['status' => 'akquise'])));
        assertSame(['k.status IS NULL'], $parts(KundenFilter::fromQuery(['status' => 'ohne'])));
        assertSame('', KundenFilter::fromQuery(['status' => 'egal'])->where()[0]);
    }],
    ['Suche + mehrere Dropdowns: alles UND-verknüpft, Werte in passender Reihenfolge', static function () use ($parts, $isSearch): void {
        $f = KundenFilter::fromQuery(['q' => 'anna', 'typ' => 'privat', 'newsletter' => 'ja', 'ort' => 'Pullach', 'status' => 'kunde']);
        $p = $parts($f);
        assertSame(5, count($p));
        assertSame('k.typ = ?', $p[0]);
        assertSame(true, str_starts_with($p[1], 'EXISTS ('));
        assertSame('k.ort = ?', $p[2]);
        assertSame('k.status = ?', $p[3]);
        assertSame(true, $isSearch($p[4]));
        $params = $f->where()[1];
        assertSame(['privat', 'Pullach', 'kunde'], array_slice($params, 0, 3));
        assertSame(15, count($params));
    }],
    ['params(): nur gesetzte Werte, für Links und versteckte Felder', static function (): void {
        $f = KundenFilter::fromQuery(['q' => 'anna', 'typ' => 'schule', 'ort' => '', 'newsletter' => 'nein']);
        assertSame(['q' => 'anna', 'typ' => 'schule', 'newsletter' => 'nein'], $f->params());
        assertSame(false, $f->isEmpty());
    }],
    ['Arrays oder andere Typen in der URL werden ignoriert', static function (): void {
        $f = KundenFilter::fromQuery(['q' => ['x'], 'typ' => ['schule'], 'ort' => 5]);
        assertSame('', $f->where()[0]);
    }],
];
