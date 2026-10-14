<?php
declare(strict_types=1);

namespace Backoffice\Modules\Import;

use Backoffice\Db;
use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Modules\Kunden\Kundentypen;

// Import runs and undoing them. A run = customers created by an import (change log action
// "importiert") with the same source, created without a longer pause in between. Undo deletes
// only customers that were not touched after the import.
final class ImportUndo
{
    // Pause after which the next imported customer starts a new run
    private const GAP_SECONDS = 300;
    // Contacts, children and consents saved together with the imported customer are at most this much younger
    private const SAME_SAVE_SECONDS = 5;

    public const LABELS = [
        'Import alte Datenbank' => 'Newsletter-Liste',
        'Import pretix' => 'Kinder (pretix)',
        KontakteImport::QUELLE => 'Kontakte',
    ];

    /** @return list<array{id: int, quelle: ?string, von: string, bis: string, ids: list<int>}> newest first */
    public static function runs(): array
    {
        $rows = Db::query(
            "SELECT k.id, k.quelle, k.erstellt_am FROM kunden k
             WHERE EXISTS (SELECT 1 FROM aenderungen a WHERE a.tabelle = 'kunden' AND a.datensatz_id = k.id AND a.aktion = 'importiert')
             ORDER BY k.erstellt_am, k.id"
        )->fetchAll();

        $runs = [];
        $current = null;
        foreach ($rows as $r) {
            $time = strtotime($r['erstellt_am']);
            if ($current === null || $current['quelle'] !== $r['quelle'] || $time - strtotime($current['bis']) > self::GAP_SECONDS) {
                if ($current !== null) {
                    $runs[] = $current;
                }
                // The first customer's id identifies the run
                $current = ['id' => (int) $r['id'], 'quelle' => $r['quelle'], 'von' => $r['erstellt_am'], 'bis' => $r['erstellt_am'], 'ids' => []];
            }
            $current['ids'][] = (int) $r['id'];
            $current['bis'] = $r['erstellt_am'];
        }
        if ($current !== null) {
            $runs[] = $current;
        }
        return array_reverse($runs);
    }

    public static function find(int $id): ?array
    {
        foreach (self::runs() as $run) {
            if ($run['id'] === $id) {
                return $run;
            }
        }
        return null;
    }

    /**
     * Splits the customers of a run into "unchanged since the import" (may be deleted) and "changed"
     * (edited, children/contacts/consents added or changed, newsletter switched, or used in orders,
     * bookings, invoices …).
     *
     * @param list<int> $ids
     * @return array{unchanged: list<array>, changed: list<array>}
     */
    public static function check(array $ids): array
    {
        $result = ['unchanged' => [], 'changed' => []];
        if (!$ids) {
            return $result;
        }
        // Changed = an edit in the change log (customer or one of its rows), a row added later, or in use elsewhere
        $sub = static fn (string $table, string $alias) => "EXISTS (SELECT 1 FROM $table $alias WHERE $alias.kunde_id = k.id
            AND ($alias.erstellt_am > k.erstellt_am + INTERVAL " . self::SAME_SAVE_SECONDS . " SECOND
                 OR EXISTS (SELECT 1 FROM aenderungen x WHERE x.tabelle = '$table' AND x.datensatz_id = $alias.id AND x.aktion = 'geaendert')))";
        $rows = Db::query(
            "SELECT k.id, k.typ, k.name, k.email, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname,
               (EXISTS (SELECT 1 FROM aenderungen x WHERE x.tabelle = 'kunden' AND x.datensatz_id = k.id AND x.aktion = 'geaendert')
                OR " . $sub('ansprechpartner', 'a') . '
                OR ' . $sub('kinder', 'ki') . '
                OR ' . $sub('einwilligungen', 'e') . '
                OR EXISTS (SELECT 1 FROM auftraege x WHERE x.kunde_id = k.id)
                OR EXISTS (SELECT 1 FROM rechnungen x WHERE x.kunde_id = k.id)
                OR EXISTS (SELECT 1 FROM buchungen x WHERE x.kunde_id = k.id)
                OR EXISTS (SELECT 1 FROM aufgaben x WHERE x.kunde_id = k.id)
                OR EXISTS (SELECT 1 FROM orte x WHERE x.kunde_id = k.id)
                OR EXISTS (SELECT 1 FROM gutscheine x WHERE x.kunde_id = k.id)) AS geaendert
             FROM kunden k
             LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1
             WHERE k.id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')
             ORDER BY k.id',
            $ids
        )->fetchAll();
        foreach ($rows as $r) {
            $r['anzeige'] = Kundentypen::displayName($r);
            $result[(int) $r['geaendert'] === 1 ? 'changed' : 'unchanged'][] = $r;
        }
        return $result;
    }

    /** Deletes the unchanged customers of the run (contacts, children, consents go with them); returns the count */
    public static function undo(array $run): int
    {
        $ids = array_column(self::check($run['ids'])['unchanged'], 'id');
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($ids as $id) {
                KundenRepository::delete((int) $id);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return count($ids);
    }

    public static function label(?string $quelle): string
    {
        return self::LABELS[$quelle] ?? ($quelle ?: 'Import');
    }
}
