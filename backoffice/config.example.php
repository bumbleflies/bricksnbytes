<?php
// Copy to config.php (same folder, outside public/) and fill in. config.php is never committed.
return [
    'db' => [
        // Strato: host and database name from "Datenbanken und Webspace" → "Datenbanken verwalten"
        'dsn' => 'mysql:host=DATENBANK-HOST;dbname=DATENBANK-NAME;charset=utf8mb4',
        'user' => 'DATENBANK-BENUTZER',
        'password' => 'DATENBANK-PASSWORT',
    ],
    'app' => [
        'name' => 'Imperiales Sicherheitsbüro',
        // Show PHP errors in the browser – only for local development
        'debug' => false,
        // Cookies only over HTTPS; set to false only for local http://127.0.0.1 testing
        'secure_cookies' => true,
        // Log out after this many minutes without activity …
        'session_idle_minutes' => 30,
        // … and after this many hours in any case
        'session_max_hours' => 8,
    ],
    'login' => [
        // Lock a user name after this many failed logins within the window …
        'max_failures_per_user' => 5,
        // … and an IP address after this many (covers guessing many user names)
        'max_failures_per_ip' => 20,
        'window_minutes' => 15,
    ],
];
