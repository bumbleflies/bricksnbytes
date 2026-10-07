<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Audit;
use Backoffice\Db;
use PDOException;

// All SQL for customers and their contacts, children and consents. Every value goes through a
// placeholder; sort columns come from a fixed whitelist. Every write is recorded in the change log.
final class KundenRepository
{
    public const FIELDS = ['typ', 'name', 'email', 'telefon', 'adresse', 'plz', 'ort', 'quelle', 'notizen'];
    public const CONTACT_FIELDS = ['vorname', 'nachname', 'rolle', 'email', 'telefon', 'ist_hauptkontakt'];
    public const CHILD_FIELDS = ['vorname', 'nachname', 'geburtsdatum', 'geburtsjahr', 'abholberechtigte', 'notizen'];

    // URL sort key => SQL expression (used for every list view)
    public const SORTS = [
        'name' => "COALESCE(NULLIF(k.name, ''), CONCAT_WS(' ', hk.nachname, hk.vorname), k.email)",
        'vorname' => 'hk.vorname',
        'nachname' => 'hk.nachname',
        'typ' => 'k.typ',
        'email' => 'k.email',
        'telefon' => 'k.telefon',
        'adresse' => 'k.adresse',
        'plz' => 'k.plz',
        'ort' => 'k.ort',
        'kinder' => 'kinder_namen',
        'ansprechpartner' => "CONCAT_WS(' ', hk.nachname, hk.vorname)",
        'erstellt' => 'k.erstellt_am',
    ];

    private const BASE_SELECT = "SELECT k.*, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname, hk.rolle AS hk_rolle,
          (SELECT GROUP_CONCAT(ki.vorname ORDER BY ki.geburtsdatum IS NULL, ki.geburtsdatum, ki.geburtsjahr, ki.id SEPARATOR ', ')
             FROM kinder ki WHERE ki.kunde_id = k.id) AS kinder_namen,
          (SELECT COUNT(*) FROM ansprechpartner a WHERE a.kunde_id = k.id) AS ansprechpartner_anzahl
        FROM kunden k
        LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1";

    /** @return array{rows: array, total: int} */
    public static function search(string $q, ?string $typ, string $sort, string $dir, int $page, int $perPage): array
    {
        [$where, $params] = self::filter($q, $typ);
        $expr = self::SORTS[$sort] ?? self::SORTS['name'];
        $direction = $dir === 'desc' ? 'DESC' : 'ASC';

        $total = (int) Db::query(
            "SELECT COUNT(*) FROM kunden k LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1 $where",
            $params
        )->fetchColumn();

        $rows = Db::query(
            self::BASE_SELECT . " $where ORDER BY ($expr) IS NULL, $expr $direction, k.id LIMIT " . max(1, $perPage)
            . ' OFFSET ' . max(0, ($page - 1) * $perPage),
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** Count per customer type, e.g. ['privat' => 12, 'schule' => 3] */
    public static function countsByType(): array
    {
        return Db::query('SELECT typ, COUNT(*) FROM kunden GROUP BY typ')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public static function recent(int $limit): array
    {
        return Db::query(self::BASE_SELECT . ' ORDER BY k.erstellt_am DESC, k.id DESC LIMIT ' . max(1, $limit))->fetchAll();
    }

    /** All matching customers with contacts, children and consents, for the CSV export */
    public static function exportRows(string $q, ?string $typ): array
    {
        [$where, $params] = self::filter($q, $typ);
        $rows = Db::query(self::BASE_SELECT . " $where ORDER BY k.typ, " . self::SORTS['name'] . ', k.id', $params)->fetchAll();
        $kinder = self::groupBy(Db::query('SELECT * FROM kinder ORDER BY geburtsdatum IS NULL, geburtsdatum, geburtsjahr, id')->fetchAll());
        $einwilligungen = self::groupBy(Db::query('SELECT * FROM einwilligungen WHERE kind_id IS NULL')->fetchAll());
        foreach ($rows as &$row) {
            $row['kinder'] = $kinder[$row['id']] ?? [];
            $row['einwilligungen'] = array_column($einwilligungen[$row['id']] ?? [], null, 'art');
        }
        return $rows;
    }

    public static function find(int $id): ?array
    {
        $kunde = Db::query(self::BASE_SELECT . ' WHERE k.id = ?', [$id])->fetch();
        if (!$kunde) {
            return null;
        }
        $kunde['ansprechpartner'] = Db::query(
            'SELECT * FROM ansprechpartner WHERE kunde_id = ? ORDER BY ist_hauptkontakt DESC, id',
            [$id]
        )->fetchAll();
        $kunde['kinder'] = Db::query(
            'SELECT * FROM kinder WHERE kunde_id = ? ORDER BY geburtsdatum IS NULL, geburtsdatum, geburtsjahr, id',
            [$id]
        )->fetchAll();
        $kunde['einwilligungen'] = array_column(
            Db::query('SELECT * FROM einwilligungen WHERE kunde_id = ? AND kind_id IS NULL', [$id])->fetchAll(),
            null,
            'art'
        );
        return $kunde;
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) Db::query('SELECT 1 FROM kunden WHERE email = ? AND id <> ?', [$email, $exceptId ?? 0])->fetchColumn();
    }

    public static function findIdByEmail(string $email): ?int
    {
        $id = Db::query('SELECT id FROM kunden WHERE email = ?', [$email])->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * Saves the customer with its contacts, children (only for type privat) and consents in one transaction.
     * @param array $kinder null = leave children untouched
     */
    public static function save(?int $id, array $data, array $contacts, ?array $kinder, array $consents, string $action = 'angelegt'): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $values = array_map(static fn ($f) => $data[$f] ?? null, self::FIELDS);
            if ($id === null) {
                Db::query(
                    'INSERT INTO kunden (' . implode(', ', self::FIELDS) . ') VALUES (' . implode(', ', array_fill(0, count(self::FIELDS), '?')) . ')',
                    $values
                );
                $id = (int) $pdo->lastInsertId();
                Audit::created('kunden', $id, array_combine(self::FIELDS, $values), $action);
            } else {
                $before = Db::query('SELECT ' . implode(', ', self::FIELDS) . ' FROM kunden WHERE id = ?', [$id])->fetch() ?: [];
                Db::query(
                    'UPDATE kunden SET ' . implode(', ', array_map(static fn ($f) => "$f = ?", self::FIELDS)) . ' WHERE id = ?',
                    [...$values, $id]
                );
                Audit::changed('kunden', $id, $before, array_combine(self::FIELDS, $values));
            }

            self::syncRows('ansprechpartner', self::CONTACT_FIELDS, $id, $contacts);
            if ($kinder !== null) {
                self::syncRows('kinder', self::CHILD_FIELDS, $id, $kinder);
            }
            self::syncConsents($id, $consents);

            $pdo->commit();
            return $id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        // Contacts, children and consents are removed by the foreign keys (ON DELETE CASCADE)
        Db::query('DELETE FROM kunden WHERE id = ?', [$id]);
        Audit::deleted('kunden', $id);
    }

    // Update kept rows, add new ones, remove the ones no longer in the form
    private static function syncRows(string $table, array $fields, int $kundeId, array $rows): void
    {
        $existing = [];
        foreach (Db::query("SELECT * FROM $table WHERE kunde_id = ?", [$kundeId])->fetchAll() as $row) {
            $existing[(int) $row['id']] = $row;
        }
        $keep = [];
        foreach ($rows as $row) {
            $values = array_map(static fn ($f) => $row[$f] ?? null, $fields);
            $rowId = $row['id'] ?? null;
            if ($rowId !== null && isset($existing[$rowId])) {
                Db::query(
                    "UPDATE $table SET " . implode(', ', array_map(static fn ($f) => "$f = ?", $fields)) . ' WHERE id = ? AND kunde_id = ?',
                    [...$values, $rowId, $kundeId]
                );
                Audit::changed($table, $rowId, $existing[$rowId], array_combine($fields, $values));
                $keep[] = $rowId;
            } else {
                Db::query(
                    "INSERT INTO $table (kunde_id, " . implode(', ', $fields) . ') VALUES (?' . str_repeat(', ?', count($fields)) . ')',
                    [$kundeId, ...$values]
                );
                Audit::created($table, (int) Db::pdo()->lastInsertId(), array_combine($fields, $values));
            }
        }
        foreach (array_diff(array_keys($existing), $keep) as $removeId) {
            Db::query("DELETE FROM $table WHERE id = ? AND kunde_id = ?", [$removeId, $kundeId]);
            Audit::deleted($table, $removeId);
        }
    }

    /** @param array $consents art => ['erteilt_am' => ?string, 'widerrufen_am' => ?string, 'quelle' => ?string] */
    private static function syncConsents(int $kundeId, array $consents): void
    {
        foreach (Kundentypen::EINWILLIGUNGEN as $art => $label) {
            $new = $consents[$art] ?? ['erteilt_am' => null, 'widerrufen_am' => null];
            $old = Db::query('SELECT * FROM einwilligungen WHERE kunde_id = ? AND kind_id IS NULL AND art = ?', [$kundeId, $art])->fetch();
            $empty = $new['erteilt_am'] === null && $new['widerrufen_am'] === null;

            if ($old && $empty) {
                Db::query('DELETE FROM einwilligungen WHERE id = ?', [$old['id']]);
                Audit::deleted('einwilligungen', (int) $old['id']);
            } elseif ($old) {
                Db::query('UPDATE einwilligungen SET erteilt_am = ?, widerrufen_am = ? WHERE id = ?', [$new['erteilt_am'], $new['widerrufen_am'], $old['id']]);
                Audit::changed('einwilligungen', (int) $old['id'], $old, ['erteilt_am' => $new['erteilt_am'], 'widerrufen_am' => $new['widerrufen_am']]);
            } elseif (!$empty) {
                Db::query(
                    'INSERT INTO einwilligungen (kunde_id, art, erteilt_am, widerrufen_am, quelle) VALUES (?, ?, ?, ?, ?)',
                    [$kundeId, $art, $new['erteilt_am'], $new['widerrufen_am'], $new['quelle'] ?? 'Backoffice']
                );
                Audit::created('einwilligungen', (int) Db::pdo()->lastInsertId(), ['art' => $art] + $new);
            }
        }
    }

    /** @return array{0: string, 1: array} */
    private static function filter(string $q, ?string $typ): array
    {
        $conditions = [];
        $params = [];
        if ($typ !== null) {
            $conditions[] = 'k.typ = ?';
            $params[] = $typ;
        }
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $conditions[] = "(k.name LIKE ? OR k.email LIKE ? OR k.ort LIKE ?
                OR EXISTS (SELECT 1 FROM ansprechpartner a WHERE a.kunde_id = k.id
                           AND (a.vorname LIKE ? OR a.nachname LIKE ? OR CONCAT_WS(' ', a.vorname, a.nachname) LIKE ? OR a.email LIKE ?))
                OR EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id
                           AND (ki.vorname LIKE ? OR CONCAT_WS(' ', ki.vorname, ki.nachname) LIKE ?)))";
            array_push($params, ...array_fill(0, 9, $like));
        }
        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    private static function groupBy(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['kunde_id']][] = $row;
        }
        return $grouped;
    }
}
