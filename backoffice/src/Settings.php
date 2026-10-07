<?php
declare(strict_types=1);

namespace Backoffice;

// Key/value settings stored in the einstellungen table
final class Settings
{
    private static ?array $cache = null;

    public static function get(string $key): ?string
    {
        if (self::$cache === null) {
            self::$cache = Db::query('SELECT schluessel, wert FROM einstellungen')->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        return self::$cache[$key] ?? null;
    }
}
