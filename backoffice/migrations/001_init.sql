-- 001_init: users, login throttling, customers, children
-- Applied by bin/migrate.php; statements are separated by a semicolon at line end.

CREATE TABLE IF NOT EXISTS schema_version (
  version INT UNSIGNED NOT NULL PRIMARY KEY,
  angewendet_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE benutzer (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  benutzername VARCHAR(100) NOT NULL,
  passwort_hash VARCHAR(255) NOT NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  letzter_login DATETIME NULL,
  UNIQUE KEY uq_benutzer_benutzername (benutzername)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed logins only; rows older than 24 h are deleted automatically
CREATE TABLE login_versuche (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  benutzername VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  zeit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_versuche_name_zeit (benutzername, zeit),
  KEY idx_login_versuche_ip_zeit (ip, zeit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- All fields except the id are optional so imported e-mail-only records can be completed later.
-- E-mail is unique (case-insensitive collation); several customers without e-mail are allowed.
CREATE TABLE kunden (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  vorname VARCHAR(100) NULL,
  nachname VARCHAR(100) NULL,
  email VARCHAR(254) NULL,
  telefon VARCHAR(50) NULL,
  adresse VARCHAR(200) NULL,
  plz VARCHAR(10) NULL,
  ort VARCHAR(100) NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kunden_email (email),
  KEY idx_kunden_nachname (nachname),
  KEY idx_kunden_ort (ort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Children belong to one customer (1:n) and are removed together with it
CREATE TABLE kinder (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kunde_id INT UNSIGNED NOT NULL,
  vorname VARCHAR(100) NOT NULL,
  geburtsdatum DATE NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_kinder_kunde (kunde_id),
  CONSTRAINT fk_kinder_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
