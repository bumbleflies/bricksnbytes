<?php
declare(strict_types=1);

// Applies all migrations/NNN_*.sql that are not yet recorded in schema_version.
// Usage (SSH): php bin/migrate.php

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use Backoffice\Db;

$pdo = Db::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_version (
  version INT UNSIGNED NOT NULL PRIMARY KEY,
  angewendet_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$applied = array_map('intval', $pdo->query('SELECT version FROM schema_version')->fetchAll(PDO::FETCH_COLUMN));
$files = glob(__DIR__ . '/../migrations/*.sql') ?: [];
sort($files);

$count = 0;
foreach ($files as $file) {
    $version = (int) basename($file);
    if ($version === 0 || in_array($version, $applied, true)) {
        continue;
    }
    // Statements end with a semicolon at the end of a line; comment lines are dropped
    $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
    foreach (preg_split('/;\s*$/m', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
    Db::query('INSERT INTO schema_version (version) VALUES (?)', [$version]);
    echo 'Angewendet: ' . basename($file) . "\n";
    $count++;
}

echo $count === 0 ? "Datenbank ist aktuell.\n" : "$count Migration(en) angewendet.\n";
