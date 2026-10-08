<?php
declare(strict_types=1);

namespace Backoffice;

// Change log (table aenderungen): who changed which record when, with old → new values per field.
// Deletions record only the fact, not the content, so erased data does not live on in the log.
final class Audit
{
    public static function created(string $table, int $id, array $values, string $action = 'angelegt'): void
    {
        $changes = [];
        foreach ($values as $field => $value) {
            if ($value !== null && $value !== '') {
                $changes[$field] = [null, $value];
            }
        }
        self::write($table, $id, $action, $changes);
    }

    public static function changed(string $table, int $id, array $before, array $after): void
    {
        $changes = [];
        foreach ($after as $field => $value) {
            $old = $before[$field] ?? null;
            if ((string) $old !== (string) $value) {
                $changes[$field] = [$old, $value];
            }
        }
        if ($changes) {
            self::write($table, $id, 'geaendert', $changes);
        }
    }

    public static function deleted(string $table, int $id): void
    {
        self::write($table, $id, 'geloescht', null);
    }

    /** Latest entries for one record (and optionally its sub-records) */
    public static function forRecord(string $table, int $id, int $limit = 15): array
    {
        return Db::query(
            'SELECT zeit, benutzername, tabelle, aktion, aenderungen FROM aenderungen
             WHERE tabelle = ? AND datensatz_id = ? ORDER BY zeit DESC, id DESC LIMIT ' . max(1, $limit),
            [$table, $id]
        )->fetchAll();
    }

    // Removes entries older than the configured retention (Einstellungen → protokoll_aufbewahrung_monate)
    public static function purge(): void
    {
        $months = (int) (Settings::get('protokoll_aufbewahrung_monate') ?? 24);
        Db::query('DELETE FROM aenderungen WHERE zeit < NOW() - INTERVAL ? MONTH', [max(1, $months)]);
    }

    private static function write(string $table, int $id, string $action, ?array $changes): void
    {
        Db::query(
            'INSERT INTO aenderungen (benutzer_id, benutzername, tabelle, datensatz_id, aktion, aenderungen) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_name'] ?? (PHP_SAPI === 'cli' ? 'cli' : null),
                $table,
                $id,
                $action,
                $changes === null ? null : json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]
        );
    }
}
