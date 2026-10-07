<?php
declare(strict_types=1);

namespace Backoffice;

use PDO;

// Single PDO connection. Real prepared statements only (no emulation), exceptions on errors.
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO(
                (string) Config::get('db.dsn'),
                (string) Config::get('db.user'),
                (string) Config::get('db.password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            // Store and show timestamps in German local time (offset form: named zones may be missing on shared hosts)
            self::$pdo->exec(sprintf(
                "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '%s'",
                (new \DateTimeImmutable())->format('P')
            ));
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
