<?php
declare(strict_types=1);

namespace Backoffice\Modules\Auftraege;

use Backoffice\Audit;
use Backoffice\Db;
use PDOException;

// All SQL for orders, their files and status history. Values go through placeholders; every write is
// recorded in the change log (file contents are never written to the log, only name and size).
final class AuftragRepository
{
    public const FIELDS = ['titel', 'kunde_id', 'ansprechpartner_id', 'format', 'status', 'summe_cent', 'preis_einheit', 'klassen_anzahl',
        'teilnehmer_anzahl', 'regelmaessig', 'termine', 'einsatzort', 'leistung_von', 'leistung_bis', 'notizen'];

    // Status → date column that records when the order reached it
    private const STATUS_DATUM = ['anfrage' => 'anfrage_am', 'angebot_verschickt' => 'angebot_am', 'bestaetigt' => 'bestaetigt_am',
        'durchgefuehrt' => 'durchgefuehrt_am', 'abgesagt' => 'abgesagt_am'];

    private const LIST_SELECT = "SELECT a.id, a.nummer, a.titel, a.format, a.status, a.kunde_id, a.erstellt_am,
          k.typ AS kunde_typ, k.name AS kunde_name, k.email AS kunde_email, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname,
          d.id AS datei_id, d.dateiname AS datei_name, d.hochgeladen_am AS datei_am
        FROM auftraege a
        JOIN kunden k ON k.id = a.kunde_id
        LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1
        LEFT JOIN auftrag_dateien d ON d.id = (SELECT d2.id FROM auftrag_dateien d2 WHERE d2.auftrag_id = a.id ORDER BY d2.hochgeladen_am DESC, d2.id DESC LIMIT 1)";

    /** @return array{rows: array, total: int} newest first */
    public static function page(int $page, int $perPage): array
    {
        $total = (int) Db::query('SELECT COUNT(*) FROM auftraege')->fetchColumn();
        $rows = Db::query(self::LIST_SELECT . ' ORDER BY a.erstellt_am DESC, a.id DESC LIMIT ' . max(1, $perPage)
            . ' OFFSET ' . max(0, ($page - 1) * $perPage))->fetchAll();
        return ['rows' => $rows, 'total' => $total];
    }

    public static function forKunde(int $kundeId): array
    {
        return Db::query(self::LIST_SELECT . ' WHERE a.kunde_id = ? ORDER BY a.erstellt_am DESC, a.id DESC', [$kundeId])->fetchAll();
    }

    /** One order with customer, main contact and the chosen contact person */
    public static function find(int $id): ?array
    {
        $row = Db::query(
            "SELECT a.*, k.typ AS kunde_typ, k.name AS kunde_name, k.email AS kunde_email, k.telefon AS kunde_telefon,
                    k.adresse AS kunde_adresse, k.plz AS kunde_plz, k.ort AS kunde_ort,
                    hk.vorname AS hk_vorname, hk.nachname AS hk_nachname,
                    ap.vorname AS ap_vorname, ap.nachname AS ap_nachname, ap.rolle AS ap_rolle, ap.email AS ap_email, ap.telefon AS ap_telefon
             FROM auftraege a
             JOIN kunden k ON k.id = a.kunde_id
             LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1
             LEFT JOIN ansprechpartner ap ON ap.id = a.ansprechpartner_id
             WHERE a.id = ?",
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** File list without contents, newest first */
    public static function files(int $auftragId): array
    {
        return Db::query(
            'SELECT id, dateiname, mime_typ, groesse, hochgeladen_am, hochgeladen_von FROM auftrag_dateien
             WHERE auftrag_id = ? ORDER BY hochgeladen_am DESC, id DESC',
            [$auftragId]
        )->fetchAll();
    }

    public static function file(int $id): ?array
    {
        $row = Db::query('SELECT * FROM auftrag_dateien WHERE id = ?', [$id])->fetch();
        return $row ?: null;
    }

    /** Status history, newest first */
    public static function history(int $auftragId): array
    {
        return Db::query('SELECT status, kommentar, zeit, benutzername FROM auftrag_status WHERE auftrag_id = ? ORDER BY zeit DESC, id DESC', [$auftragId])->fetchAll();
    }

    /** Customers for the select: id => [typ, name, email, main contact] */
    public static function customers(): array
    {
        return Db::query(
            "SELECT k.id, k.typ, k.name, k.email, hk.vorname AS hk_vorname, hk.nachname AS hk_nachname FROM kunden k
             LEFT JOIN ansprechpartner hk ON hk.kunde_id = k.id AND hk.ist_hauptkontakt = 1"
        )->fetchAll();
    }

    public static function customerExists(int $kundeId): bool
    {
        return (bool) Db::query('SELECT 1 FROM kunden WHERE id = ?', [$kundeId])->fetchColumn();
    }

    public static function contacts(int $kundeId): array
    {
        return Db::query('SELECT id, vorname, nachname, rolle, ist_hauptkontakt FROM ansprechpartner WHERE kunde_id = ? ORDER BY ist_hauptkontakt DESC, id', [$kundeId])->fetchAll();
    }

    /** New order with its first status entry; returns the id */
    public static function create(array $data, ?string $kommentar, ?string $user): int
    {
        return self::transaction(static function () use ($data, $kommentar, $user): int {
            $values = array_map(static fn ($f) => $data[$f] ?? null, self::FIELDS);
            $nummer = self::nextNumber();
            Db::query(
                'INSERT INTO auftraege (nummer, ' . implode(', ', self::FIELDS) . ') VALUES (?' . str_repeat(', ?', count(self::FIELDS)) . ')',
                [$nummer, ...$values]
            );
            $id = (int) Db::pdo()->lastInsertId();
            Audit::created('auftraege', $id, ['nummer' => $nummer] + array_combine(self::FIELDS, $values));
            self::recordStatus($id, $data['status'], $kommentar, $user);
            return $id;
        });
    }

    /** Saves the fields; a new status or a comment adds a history entry */
    public static function update(int $id, array $before, array $data, ?string $kommentar, ?string $user): void
    {
        self::transaction(static function () use ($id, $before, $data, $kommentar, $user): int {
            $values = array_map(static fn ($f) => $data[$f] ?? null, self::FIELDS);
            Db::query('UPDATE auftraege SET ' . implode(', ', array_map(static fn ($f) => "$f = ?", self::FIELDS)) . ' WHERE id = ?', [...$values, $id]);
            Audit::changed('auftraege', $id, $before, array_combine(self::FIELDS, $values));
            if ($data['status'] !== $before['status'] || $kommentar !== null) {
                self::recordStatus($id, $data['status'], $kommentar, $user, $data['status'] !== $before['status']);
            }
            return $id;
        });
    }

    public static function addFile(int $auftragId, string $name, string $mime, string $content, ?string $user): int
    {
        return self::transaction(static function () use ($auftragId, $name, $mime, $content, $user): int {
            Db::query(
                'INSERT INTO auftrag_dateien (auftrag_id, dateiname, mime_typ, groesse, inhalt, hochgeladen_von) VALUES (?, ?, ?, ?, ?, ?)',
                [$auftragId, $name, $mime, strlen($content), $content, $user]
            );
            $id = (int) Db::pdo()->lastInsertId();
            Audit::created('auftrag_dateien', $id, ['auftrag_id' => $auftragId, 'dateiname' => $name, 'groesse' => strlen($content)]);
            return $id;
        });
    }

    /** Deletes one file of this order; false if it does not belong to it */
    public static function deleteFile(int $fileId, int $auftragId): bool
    {
        $deleted = Db::query('DELETE FROM auftrag_dateien WHERE id = ? AND auftrag_id = ?', [$fileId, $auftragId])->rowCount() > 0;
        if ($deleted) {
            Audit::deleted('auftrag_dateien', $fileId);
        }
        return $deleted;
    }

    /** Order with its files and status history (foreign keys cascade) */
    public static function delete(int $id): void
    {
        Db::query('DELETE FROM auftraege WHERE id = ?', [$id]);
        Audit::deleted('auftraege', $id);
    }

    private static function recordStatus(int $id, string $status, ?string $kommentar, ?string $user, bool $changed = true): void
    {
        Db::query('INSERT INTO auftrag_status (auftrag_id, status, kommentar, benutzername) VALUES (?, ?, ?, ?)', [$id, $status, $kommentar, $user]);
        // First time in this status: remember the date (anfrage_am, angebot_am, …)
        if ($changed && isset(self::STATUS_DATUM[$status])) {
            $column = self::STATUS_DATUM[$status];
            Db::query("UPDATE auftraege SET $column = COALESCE($column, CURDATE()) WHERE id = ?", [$id]);
        }
    }

    // Order number per year: 2026-001, 2026-002, …
    private static function nextNumber(): string
    {
        $year = date('Y');
        $last = Db::query("SELECT MAX(CAST(SUBSTRING(nummer, 6) AS UNSIGNED)) FROM auftraege WHERE nummer LIKE ?", [$year . '-%'])->fetchColumn();
        return sprintf('%s-%03d', $year, (int) $last + 1);
    }

    /** @param callable(): int $work */
    private static function transaction(callable $work): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $result = $work();
            $pdo->commit();
            return $result;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
