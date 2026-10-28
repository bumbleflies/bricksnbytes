<?php
declare(strict_types=1);

namespace Backoffice\Modules\Termine;

use Backoffice\Audit;
use Backoffice\Db;
use Backoffice\Modules\Kunden\Kundentypen;
use PDOException;

// SQL for the dates overview: own entries (kalender_eintraege) and the other sources for a period.
final class TermineRepository
{
    public const FIELDS = ['datum', 'bis_datum', 'beginn', 'ende', 'titel', 'art', 'notizen'];

    /** Own entries that touch the period (multi-day ones too) */
    public static function eigene(string $from, string $to): array
    {
        return Db::query(
            'SELECT id, datum, bis_datum, beginn, ende, titel, art FROM kalender_eintraege
             WHERE datum <= ? AND COALESCE(bis_datum, datum) >= ? ORDER BY datum, beginn',
            [$to, $from]
        )->fetchAll();
    }

    /** Course dates (Kurse) in the period, cancelled ones left out */
    public static function kursTermine(string $from, string $to): array
    {
        return Db::query(
            "SELECT t.beginn, t.ende, k.titel FROM termine t JOIN kurse k ON k.id = t.kurs_id
             WHERE t.status <> 'abgesagt' AND t.beginn >= ? AND t.beginn < ? + INTERVAL 1 DAY ORDER BY t.beginn",
            [$from, $to]
        )->fetchAll();
    }

    /** Orders with a date range touching the period, with the customer's display name */
    public static function auftraege(string $from, string $to): array
    {
        $rows = Db::query(
            "SELECT a.id, a.titel, a.status, a.regelmaessig, a.leistung_von, a.leistung_bis,
                    k.typ, k.name, k.email, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname
             FROM auftraege a JOIN kunden k ON k.id = a.kunde_id
             LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1
             WHERE a.leistung_von IS NOT NULL AND a.leistung_von <= ? AND COALESCE(a.leistung_bis, a.leistung_von) >= ?",
            [$to, $from]
        )->fetchAll();
        return array_map(static fn ($r) => $r + ['kunde' => Kundentypen::displayName($r)], $rows);
    }

    /** Children with a birthdate (the overview picks the birthdays in the period) */
    public static function kinder(): array
    {
        return Db::query('SELECT kunde_id, vorname, nachname, geburtsdatum FROM kinder WHERE geburtsdatum IS NOT NULL')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Db::query('SELECT * FROM kalender_eintraege WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public static function create(array $data, ?string $importLauf = null, bool $audit = true): int
    {
        $values = array_map(static fn ($f) => $data[$f] ?? null, self::FIELDS);
        Db::query('INSERT INTO kalender_eintraege (' . implode(', ', self::FIELDS) . ', import_lauf) VALUES (' . str_repeat('?, ', count(self::FIELDS)) . '?)', [...$values, $importLauf]);
        $id = (int) Db::pdo()->lastInsertId();
        if ($audit) {
            Audit::created('kalender_eintraege', $id, array_combine(self::FIELDS, $values));
        }
        return $id;
    }

    public static function update(int $id, array $before, array $data): void
    {
        $values = array_map(static fn ($f) => $data[$f] ?? null, self::FIELDS);
        Db::query('UPDATE kalender_eintraege SET ' . implode(', ', array_map(static fn ($f) => "$f = ?", self::FIELDS)) . ' WHERE id = ?', [...$values, $id]);
        Audit::changed('kalender_eintraege', $id, $before, array_combine(self::FIELDS, $values));
    }

    public static function delete(int $id): void
    {
        Db::query('DELETE FROM kalender_eintraege WHERE id = ?', [$id]);
        Audit::deleted('kalender_eintraege', $id);
    }

    /**
     * Takes over the planner entries of one year in one transaction. $replace removes the entries of
     * earlier take-overs of the same year first (only those, never entries added by hand).
     *
     * @return array{0: int, 1: int} [inserted, removed]
     */
    public static function importPlaner(int $year, array $entries, bool $replace): array
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $removed = 0;
            if ($replace) {
                $removed = Db::query("DELETE FROM kalender_eintraege WHERE import_lauf LIKE ?", ['planer-' . $year . '-%'])->rowCount();
            }
            $run = 'planer-' . $year . '-' . date('YmdHis');
            foreach ($entries as $e) {
                self::create(['datum' => $e['datum'], 'beginn' => $e['beginn'], 'ende' => $e['ende'], 'titel' => $e['titel'],
                    'art' => PlanerImport::art($e['titel'])], $run, false);
            }
            Audit::created('kalender_eintraege', 0, ['import_lauf' => $run, 'eintraege' => count($entries), 'ersetzt' => $removed], 'importiert');
            $pdo->commit();
            return [count($entries), $removed];
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Entries of earlier take-overs of this year */
    public static function importedCount(int $year): int
    {
        return (int) Db::query('SELECT COUNT(*) FROM kalender_eintraege WHERE import_lauf LIKE ?', ['planer-' . $year . '-%'])->fetchColumn();
    }
}
