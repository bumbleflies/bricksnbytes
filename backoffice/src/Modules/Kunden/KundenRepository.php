<?php
declare(strict_types=1);

namespace Backoffice\Modules\Kunden;

use Backoffice\Db;
use PDOException;

// All SQL for customers and their children. Every value goes through a placeholder;
// sort columns come from a fixed whitelist.
final class KundenRepository
{
    public const FIELDS = ['vorname', 'nachname', 'email', 'telefon', 'adresse', 'plz', 'ort', 'newsletter'];

    // URL sort key => SQL expression
    public const SORTS = [
        'vorname' => 'k.vorname',
        'nachname' => 'k.nachname',
        'email' => 'k.email',
        'telefon' => 'k.telefon',
        'adresse' => 'k.adresse',
        'plz' => 'k.plz',
        'ort' => 'k.ort',
        'kind1' => 'kind1',
        'kind2' => 'kind2',
        'newsletter' => 'k.newsletter',
        'erstellt' => 'k.erstellt_am',
    ];

    /** @return array{rows: array, total: int} */
    public static function search(string $q, string $sort, string $dir, int $page, int $perPage, bool $onlyNewsletter = false): array
    {
        [$where, $params] = self::filter($q, $onlyNewsletter);
        $orderBy = (self::SORTS[$sort] ?? 'k.nachname') . ($dir === 'desc' ? ' DESC' : ' ASC');

        $total = (int) Db::query("SELECT COUNT(*) FROM kunden k $where", $params)->fetchColumn();

        // Empty values sort last, then by name as tie-breaker
        $sql = 'SELECT k.*,
                  (SELECT vorname FROM kinder WHERE kunde_id = k.id ORDER BY geburtsdatum IS NULL, geburtsdatum, id LIMIT 1) AS kind1,
                  (SELECT vorname FROM kinder WHERE kunde_id = k.id ORDER BY geburtsdatum IS NULL, geburtsdatum, id LIMIT 1 OFFSET 1) AS kind2,
                  (SELECT COUNT(*) FROM kinder WHERE kunde_id = k.id) AS kinder_anzahl
                FROM kunden k ' . $where . '
                ORDER BY ' . (self::SORTS[$sort] ?? 'k.nachname') . " IS NULL, $orderBy, k.nachname, k.vorname, k.id
                LIMIT " . max(1, $perPage) . ' OFFSET ' . max(0, ($page - 1) * $perPage);

        return ['rows' => Db::query($sql, $params)->fetchAll(), 'total' => $total];
    }

    /** All matching customers with all children, for the CSV export */
    public static function exportRows(string $q, bool $onlyNewsletter = false): array
    {
        [$where, $params] = self::filter($q, $onlyNewsletter);
        $kunden = Db::query("SELECT k.* FROM kunden k $where ORDER BY k.nachname IS NULL, k.nachname, k.vorname, k.id", $params)->fetchAll();
        $kinder = [];
        foreach (Db::query('SELECT kunde_id, vorname, geburtsdatum FROM kinder ORDER BY geburtsdatum IS NULL, geburtsdatum, id') as $kind) {
            $kinder[$kind['kunde_id']][] = $kind;
        }
        foreach ($kunden as &$kunde) {
            $kunde['kinder'] = $kinder[$kunde['id']] ?? [];
        }
        return $kunden;
    }

    public static function find(int $id): ?array
    {
        $kunde = Db::query('SELECT * FROM kunden WHERE id = ?', [$id])->fetch();
        if (!$kunde) {
            return null;
        }
        $kunde['kinder'] = Db::query(
            'SELECT id, vorname, geburtsdatum FROM kinder WHERE kunde_id = ? ORDER BY geburtsdatum IS NULL, geburtsdatum, id',
            [$id]
        )->fetchAll();
        return $kunde;
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) Db::query(
            'SELECT 1 FROM kunden WHERE email = ? AND id <> ?',
            [$email, $exceptId ?? 0]
        )->fetchColumn();
    }

    /** @param array $data validated fields @param array $kinder [['id'=>?int,'vorname'=>..,'geburtsdatum'=>?string]] */
    public static function save(?int $id, array $data, array $kinder): int
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
            } else {
                Db::query(
                    'UPDATE kunden SET ' . implode(', ', array_map(static fn ($f) => "$f = ?", self::FIELDS)) . ' WHERE id = ?',
                    [...$values, $id]
                );
            }
            self::syncKinder($id, $kinder);
            $pdo->commit();
            return $id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findIdByEmail(string $email): ?int
    {
        $id = Db::query('SELECT id FROM kunden WHERE email = ?', [$email])->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public static function delete(int $id): void
    {
        // Children are removed by the foreign key (ON DELETE CASCADE)
        Db::query('DELETE FROM kunden WHERE id = ?', [$id]);
    }

    // Update kept children, add new ones, remove the ones no longer in the form
    private static function syncKinder(int $kundeId, array $kinder): void
    {
        $existing = array_map('intval', Db::query('SELECT id FROM kinder WHERE kunde_id = ?', [$kundeId])->fetchAll(\PDO::FETCH_COLUMN));
        $keep = [];
        foreach ($kinder as $kind) {
            $kindId = $kind['id'] ?? null;
            if ($kindId !== null && in_array($kindId, $existing, true)) {
                Db::query(
                    'UPDATE kinder SET vorname = ?, geburtsdatum = ? WHERE id = ? AND kunde_id = ?',
                    [$kind['vorname'], $kind['geburtsdatum'], $kindId, $kundeId]
                );
                $keep[] = $kindId;
            } else {
                Db::query(
                    'INSERT INTO kinder (kunde_id, vorname, geburtsdatum) VALUES (?, ?, ?)',
                    [$kundeId, $kind['vorname'], $kind['geburtsdatum']]
                );
            }
        }
        foreach (array_diff($existing, $keep) as $removeId) {
            Db::query('DELETE FROM kinder WHERE id = ? AND kunde_id = ?', [$removeId, $kundeId]);
        }
    }

    /** @return array{0: string, 1: array} */
    private static function filter(string $q, bool $onlyNewsletter = false): array
    {
        $conditions = [];
        $params = [];
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $conditions[] = "(k.vorname LIKE ? OR k.nachname LIKE ? OR CONCAT_WS(' ', k.vorname, k.nachname) LIKE ? OR k.email LIKE ? OR k.ort LIKE ?)";
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($onlyNewsletter) {
            $conditions[] = 'k.newsletter = 1';
        }
        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
