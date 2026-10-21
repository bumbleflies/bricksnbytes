-- 008: orders (Aufträge) module – type "Hort", agreed price with unit, number of classes and
-- participants, regular dates, place; files per order (stored in the database, so they are part of
-- every database backup) and a status history with a comment per change.

ALTER TABLE auftraege
  MODIFY COLUMN format ENUM('offener_kurs', 'ferienkurs', 'workshop', 'ag', 'projekttag', 'kita_einheit', 'geburtstag', 'hort', 'sonstiges') NOT NULL,
  ADD COLUMN preis_einheit ENUM('gesamt', 'pro_termin', 'pro_kind', 'pro_monat') NOT NULL DEFAULT 'gesamt' AFTER summe_cent,
  ADD COLUMN klassen_anzahl SMALLINT UNSIGNED NULL AFTER preis_einheit,
  ADD COLUMN teilnehmer_anzahl SMALLINT UNSIGNED NULL AFTER klassen_anzahl,
  ADD COLUMN regelmaessig TINYINT(1) NOT NULL DEFAULT 0 AFTER teilnehmer_anzahl,
  ADD COLUMN termine VARCHAR(200) NULL AFTER regelmaessig,
  ADD COLUMN einsatzort VARCHAR(200) NULL AFTER termine;

CREATE TABLE auftrag_dateien (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  auftrag_id INT UNSIGNED NOT NULL,
  dateiname VARCHAR(255) NOT NULL,
  mime_typ VARCHAR(100) NOT NULL,
  groesse INT UNSIGNED NOT NULL,
  inhalt LONGBLOB NOT NULL,
  hochgeladen_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  hochgeladen_von VARCHAR(100) NULL,
  KEY idx_auftrag_dateien_auftrag (auftrag_id, hochgeladen_am),
  CONSTRAINT fk_auftrag_dateien_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auftrag_status (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  auftrag_id INT UNSIGNED NOT NULL,
  status ENUM('anfrage', 'angebot_verschickt', 'bestaetigt', 'durchgefuehrt', 'abgerechnet', 'abgesagt') NOT NULL,
  kommentar TEXT NULL,
  zeit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  benutzername VARCHAR(100) NULL,
  KEY idx_auftrag_status_auftrag (auftrag_id, zeit),
  CONSTRAINT fk_auftrag_status_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
