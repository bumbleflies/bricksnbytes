<?php
declare(strict_types=1);

// One-time setup: applies migrations and creates the first admin user.
// Refuses to run once any user exists, so it cannot be used to add accounts later.
// Usage (SSH): php bin/setup.php

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use Backoffice\Auth;
use Backoffice\Db;

passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/migrate.php'), $code);
if ($code !== 0) {
    exit("Migration fehlgeschlagen.\n");
}

if ((int) Db::query('SELECT COUNT(*) FROM benutzer')->fetchColumn() > 0) {
    exit("Es gibt bereits einen Benutzer – das Setup ist gesperrt.\n");
}

function ask(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty -echo');
        $value = (string) fgets(STDIN);
        shell_exec('stty echo');
        echo "\n";
    } else {
        $value = (string) fgets(STDIN);
    }
    return trim($value);
}

$username = ask('Benutzername für den ersten Admin: ');
if (!preg_match('/^[\p{L}\p{N}._@-]{3,100}$/u', $username)) {
    exit("Benutzername: 3–100 Zeichen, nur Buchstaben, Ziffern und . _ @ -\n");
}

$password = ask('Passwort (mind. 12 Zeichen): ', true);
if (mb_strlen($password) < 12) {
    exit("Das Passwort muss mindestens 12 Zeichen lang sein.\n");
}
if (ask('Passwort wiederholen: ', true) !== $password) {
    exit("Die Passwörter stimmen nicht überein.\n");
}

Db::query('INSERT INTO benutzer (benutzername, passwort_hash) VALUES (?, ?)', [$username, Auth::hash($password)]);
echo "Admin \"$username\" angelegt. Du kannst dich jetzt im Imperialen Sicherheitsbüro anmelden.\n";
echo "Empfehlung: bin/setup.php jetzt löschen (rm bin/setup.php).\n";
