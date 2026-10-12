<?php
declare(strict_types=1);

// Docker first-boot seed: creates the first admin user from environment variables.
// No-op when env vars are empty or when any user already exists, so it can run on
// every container start without becoming a way to add accounts later.
// Usage (entrypoint): IBS_ADMIN_USER=... IBS_ADMIN_PASSWORD=... php bin/seed-admin.php

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use Backoffice\Auth;
use Backoffice\Db;

$username = (string) (getenv('IBS_ADMIN_USER') ?: '');
$password = (string) (getenv('IBS_ADMIN_PASSWORD') ?: '');

if ($username === '' || $password === '') {
    echo "seed-admin: IBS_ADMIN_USER/IBS_ADMIN_PASSWORD not set, skipping.\n";
    exit(0);
}

if (!preg_match('/^[\p{L}\p{N}._@-]{3,100}$/u', $username)) {
    fwrite(STDERR, "seed-admin: Benutzername: 3–100 Zeichen, nur Buchstaben, Ziffern und . _ @ -\n");
    exit(1);
}

$length = mb_strlen($password);
if ($length < Auth::PASSWORD_MIN_LENGTH || $length > Auth::PASSWORD_MAX_LENGTH) {
    fwrite(STDERR, 'seed-admin: Passwort muss ' . Auth::PASSWORD_MIN_LENGTH . '–' . Auth::PASSWORD_MAX_LENGTH . " Zeichen lang sein.\n");
    exit(1);
}

if ((int) Db::query('SELECT COUNT(*) FROM benutzer')->fetchColumn() > 0) {
    echo "seed-admin: users exist, ignoring IBS_ADMIN_*.\n";
    exit(0);
}

try {
    Db::query(
        "INSERT INTO benutzer (benutzername, passwort_hash, rolle, aktiv) VALUES (?, ?, 'admin', 1)",
        [$username, Auth::hash($password)]
    );
} catch (PDOException $e) {
    // Race: two containers booting against the same DB — whoever commits second
    // hits the unique key; that means a user exists now, so seeding is done.
    if ((string) ($e->errorInfo[1] ?? '') === '1062') {
        echo "seed-admin: users exist, ignoring IBS_ADMIN_*.\n";
        exit(0);
    }
    throw $e;
}

echo "seed-admin: admin \"$username\" created.\n";
