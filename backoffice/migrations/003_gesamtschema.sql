-- 003_gesamtschema: data model for all modules (Kunden, Kinder, Aufträge, Kurse, Termine,
-- Buchungen, Rechnungen, Forecast, Kursleiter, Einstellungen) plus change log.
-- Existing customers become type "privat"; names move to a contact person, the newsletter
-- flag becomes a consent record. Amounts are stored in cents.

-- ---------------------------------------------------------------- Basis

CREATE TABLE einstellungen (
  schluessel VARCHAR(100) NOT NULL PRIMARY KEY,
  wert TEXT NULL,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO einstellungen (schluessel, wert) VALUES
  ('standard_max_plaetze', '8'),
  ('zahlungsziel_tage', '14'),
  ('steuersatz_standard', '0'),
  ('steuerhinweis', 'Umsatzsteuerfrei gemäß § 4 Nr. 21 UStG'),
  ('protokoll_aufbewahrung_monate', '24'),
  ('forecast_anfrage', '20'),
  ('forecast_angebot_verschickt', '50'),
  ('forecast_bestaetigt', '100'),
  ('forecast_durchgefuehrt', '100'),
  ('forecast_abgerechnet', '100');

CREATE TABLE kursleiter (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  vorname VARCHAR(100) NOT NULL,
  nachname VARCHAR(100) NULL,
  email VARCHAR(254) NULL,
  telefon VARCHAR(50) NULL,
  aktiv TINYINT(1) NOT NULL DEFAULT 1,
  notizen TEXT NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles: admin (everything), buero (no user/settings admin), kursleiter (own dates only, later)
ALTER TABLE benutzer
  ADD COLUMN rolle ENUM('admin', 'buero', 'kursleiter') NOT NULL DEFAULT 'admin' AFTER passwort_hash,
  ADD COLUMN aktiv TINYINT(1) NOT NULL DEFAULT 1 AFTER rolle,
  ADD COLUMN kursleiter_id INT UNSIGNED NULL AFTER aktiv,
  ADD CONSTRAINT fk_benutzer_kursleiter FOREIGN KEY (kursleiter_id) REFERENCES kursleiter (id) ON DELETE SET NULL;

-- Change log: who changed what when. For deletions only the fact is kept, not the content.
CREATE TABLE aenderungen (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  zeit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  benutzer_id INT UNSIGNED NULL,
  benutzername VARCHAR(100) NULL,
  tabelle VARCHAR(64) NOT NULL,
  datensatz_id INT UNSIGNED NOT NULL,
  aktion ENUM('angelegt', 'geaendert', 'geloescht', 'importiert') NOT NULL,
  aenderungen JSON NULL,
  KEY idx_aenderungen_datensatz (tabelle, datensatz_id),
  KEY idx_aenderungen_zeit (zeit),
  CONSTRAINT fk_aenderungen_benutzer FOREIGN KEY (benutzer_id) REFERENCES benutzer (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Kunden, Ansprechpartner, Kinder, Einwilligungen

ALTER TABLE kunden
  ADD COLUMN typ ENUM('privat', 'schule', 'hort', 'kita', 'verein', 'firma', 'sonstige') NOT NULL DEFAULT 'privat' AFTER id,
  ADD COLUMN name VARCHAR(200) NULL AFTER typ,
  ADD COLUMN quelle VARCHAR(100) NULL AFTER ort,
  ADD COLUMN notizen TEXT NULL AFTER quelle,
  ADD KEY idx_kunden_typ (typ),
  ADD KEY idx_kunden_name (name);

CREATE TABLE ansprechpartner (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kunde_id INT UNSIGNED NOT NULL,
  vorname VARCHAR(100) NULL,
  nachname VARCHAR(100) NULL,
  rolle VARCHAR(100) NULL,
  email VARCHAR(254) NULL,
  telefon VARCHAR(50) NULL,
  ist_hauptkontakt TINYINT(1) NOT NULL DEFAULT 0,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ansprechpartner_kunde (kunde_id),
  KEY idx_ansprechpartner_name (nachname, vorname),
  CONSTRAINT fk_ansprechpartner_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing customers: their name becomes the main contact (Elternteil); e-mail/phone stay on the customer
INSERT INTO ansprechpartner (kunde_id, vorname, nachname, rolle, ist_hauptkontakt, erstellt_am)
  SELECT id, vorname, nachname, 'Elternteil', 1, erstellt_am FROM kunden
  WHERE vorname IS NOT NULL OR nachname IS NOT NULL;

ALTER TABLE kinder
  ADD COLUMN nachname VARCHAR(100) NULL AFTER vorname,
  ADD COLUMN geburtsjahr SMALLINT UNSIGNED NULL AFTER geburtsdatum,
  ADD COLUMN abholberechtigte TEXT NULL AFTER geburtsjahr,
  ADD COLUMN notizen TEXT NULL AFTER abholberechtigte,
  ADD KEY idx_kinder_name (nachname, vorname);

UPDATE kinder SET geburtsjahr = YEAR(geburtsdatum) WHERE geburtsdatum IS NOT NULL;

-- Consent per customer (optionally per child, e.g. photos). Active = erteilt_am set and widerrufen_am empty.
CREATE TABLE einwilligungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kunde_id INT UNSIGNED NOT NULL,
  kind_id INT UNSIGNED NULL,
  art ENUM('datenschutz', 'foto_video', 'newsletter') NOT NULL,
  erteilt_am DATE NULL,
  widerrufen_am DATE NULL,
  quelle VARCHAR(100) NULL,
  notiz VARCHAR(255) NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_einwilligung (kunde_id, kind_id, art),
  KEY idx_einwilligungen_art (art),
  CONSTRAINT fk_einwilligungen_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE CASCADE,
  CONSTRAINT fk_einwilligungen_kind FOREIGN KEY (kind_id) REFERENCES kinder (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO einwilligungen (kunde_id, art, erteilt_am, quelle)
  SELECT id, 'newsletter', DATE(erstellt_am), 'Übernahme Newsletter-Kennzeichen' FROM kunden WHERE newsletter = 1;

ALTER TABLE kunden
  DROP KEY idx_kunden_nachname,
  DROP KEY idx_kunden_newsletter,
  DROP COLUMN vorname,
  DROP COLUMN nachname,
  DROP COLUMN newsletter;

-- ---------------------------------------------------------------- Kurse, Orte, Termine

CREATE TABLE kursarten (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  beschreibung TEXT NULL,
  aktiv TINYINT(1) NOT NULL DEFAULT 1,
  sortierung SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_kursarten_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kursarten (name, sortierung) VALUES
  ('LEGO SPIKE', 1), ('intelino', 2), ('Scratch', 3), ('Minecraft', 4), ('Game Design', 5), ('Python', 6);

CREATE TABLE orte (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  adresse VARCHAR(200) NULL,
  plz VARCHAR(10) NULL,
  ort VARCHAR(100) NULL,
  kunde_id INT UNSIGNED NULL,
  hinweise TEXT NULL,
  aktiv TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_orte_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Aufträge / Angebote

CREATE TABLE auftraege (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nummer VARCHAR(30) NULL,
  kunde_id INT UNSIGNED NOT NULL,
  ansprechpartner_id INT UNSIGNED NULL,
  titel VARCHAR(200) NOT NULL,
  format ENUM('offener_kurs', 'ferienkurs', 'workshop', 'ag', 'projekttag', 'kita_einheit', 'geburtstag', 'sonstiges') NOT NULL,
  kursart_id INT UNSIGNED NULL,
  status ENUM('anfrage', 'angebot_verschickt', 'bestaetigt', 'durchgefuehrt', 'abgerechnet', 'abgesagt') NOT NULL DEFAULT 'anfrage',
  abrechnung ENUM('institution', 'eltern', 'pauschal') NULL,
  anfrage_am DATE NULL,
  angebot_am DATE NULL,
  bestaetigt_am DATE NULL,
  durchgefuehrt_am DATE NULL,
  abgesagt_am DATE NULL,
  leistung_von DATE NULL,
  leistung_bis DATE NULL,
  summe_cent INT NOT NULL DEFAULT 0,
  wahrscheinlichkeit TINYINT UNSIGNED NULL,
  notizen TEXT NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_auftraege_nummer (nummer),
  KEY idx_auftraege_status (status),
  KEY idx_auftraege_leistung (leistung_von),
  CONSTRAINT fk_auftraege_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE RESTRICT,
  CONSTRAINT fk_auftraege_ansprechpartner FOREIGN KEY (ansprechpartner_id) REFERENCES ansprechpartner (id) ON DELETE SET NULL,
  CONSTRAINT fk_auftraege_kursart FOREIGN KEY (kursart_id) REFERENCES kursarten (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auftrag_positionen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  auftrag_id INT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  beschreibung VARCHAR(255) NOT NULL,
  menge DECIMAL(10,2) NOT NULL DEFAULT 1,
  einzelpreis_cent INT NOT NULL DEFAULT 0,
  steuersatz DECIMAL(5,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_auftrag_positionen_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra details when the order is a birthday party (1:1)
CREATE TABLE geburtstage (
  auftrag_id INT UNSIGNED NOT NULL PRIMARY KEY,
  geburtstagskind VARCHAR(100) NULL,
  alter_jahre TINYINT UNSIGNED NULL,
  gaeste_anzahl TINYINT UNSIGNED NULL,
  thema ENUM('intelino', 'spike', 'minecraft', 'sonstiges') NULL,
  ort VARCHAR(200) NULL,
  anzahlung_cent INT NULL,
  CONSTRAINT fk_geburtstage_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A course is a series of dates (e.g. an AG with 13 dates), optionally belonging to an order
CREATE TABLE kurse (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kursart_id INT UNSIGNED NULL,
  auftrag_id INT UNSIGNED NULL,
  titel VARCHAR(200) NOT NULL,
  format ENUM('offener_kurs', 'ferienkurs', 'workshop', 'ag', 'projekttag', 'kita_einheit', 'geburtstag', 'sonstiges') NOT NULL,
  alter_von TINYINT UNSIGNED NULL,
  alter_bis TINYINT UNSIGNED NULL,
  klassenstufen VARCHAR(50) NULL,
  max_plaetze SMALLINT UNSIGNED NOT NULL DEFAULT 8,
  preis_cent INT NULL,
  pauschal TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('geplant', 'buchbar', 'laeuft', 'abgeschlossen', 'abgesagt') NOT NULL DEFAULT 'geplant',
  shop_referenz VARCHAR(100) NULL,
  beschreibung TEXT NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_kurse_status (status),
  CONSTRAINT fk_kurse_kursart FOREIGN KEY (kursart_id) REFERENCES kursarten (id) ON DELETE SET NULL,
  CONSTRAINT fk_kurse_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE termine (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kurs_id INT UNSIGNED NOT NULL,
  beginn DATETIME NOT NULL,
  ende DATETIME NULL,
  ort_id INT UNSIGNED NULL,
  kursleiter_id INT UNSIGNED NULL,
  max_plaetze SMALLINT UNSIGNED NULL,
  material TEXT NULL,
  status ENUM('geplant', 'durchgefuehrt', 'abgesagt') NOT NULL DEFAULT 'geplant',
  notizen TEXT NULL,
  KEY idx_termine_beginn (beginn),
  CONSTRAINT fk_termine_kurs FOREIGN KEY (kurs_id) REFERENCES kurse (id) ON DELETE CASCADE,
  CONSTRAINT fk_termine_ort FOREIGN KEY (ort_id) REFERENCES orte (id) ON DELETE SET NULL,
  CONSTRAINT fk_termine_kursleiter FOREIGN KEY (kursleiter_id) REFERENCES kursleiter (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Buchungen, Anwesenheit

CREATE TABLE buchungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind_id INT UNSIGNED NOT NULL,
  kurs_id INT UNSIGNED NOT NULL,
  kunde_id INT UNSIGNED NOT NULL,
  status ENUM('angemeldet', 'warteliste', 'storniert') NOT NULL DEFAULT 'angemeldet',
  warteliste_position SMALLINT UNSIGNED NULL,
  preis_cent INT NULL,
  quelle ENUM('backoffice', 'shop') NOT NULL DEFAULT 'backoffice',
  shop_referenz VARCHAR(100) NULL,
  gebucht_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  storniert_am DATETIME NULL,
  notizen TEXT NULL,
  UNIQUE KEY uq_buchungen_kind_kurs (kind_id, kurs_id),
  KEY idx_buchungen_status (status),
  CONSTRAINT fk_buchungen_kind FOREIGN KEY (kind_id) REFERENCES kinder (id) ON DELETE CASCADE,
  CONSTRAINT fk_buchungen_kurs FOREIGN KEY (kurs_id) REFERENCES kurse (id) ON DELETE CASCADE,
  CONSTRAINT fk_buchungen_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE anwesenheiten (
  termin_id INT UNSIGNED NOT NULL,
  kind_id INT UNSIGNED NOT NULL,
  status ENUM('anwesend', 'entschuldigt', 'fehlt') NOT NULL,
  notiz VARCHAR(255) NULL,
  PRIMARY KEY (termin_id, kind_id),
  CONSTRAINT fk_anwesenheiten_termin FOREIGN KEY (termin_id) REFERENCES termine (id) ON DELETE CASCADE,
  CONSTRAINT fk_anwesenheiten_kind FOREIGN KEY (kind_id) REFERENCES kinder (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Rechnungen

-- Gapless numbers: the row is locked (SELECT … FOR UPDATE) while a number is taken
CREATE TABLE nummernkreise (
  name VARCHAR(30) NOT NULL,
  jahr SMALLINT UNSIGNED NOT NULL,
  praefix VARCHAR(20) NOT NULL,
  naechste_nummer INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (name, jahr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rechnungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nummer VARCHAR(30) NULL,
  typ ENUM('rechnung', 'anzahlung', 'schlussrechnung', 'storno') NOT NULL DEFAULT 'rechnung',
  status ENUM('entwurf', 'verschickt', 'bezahlt', 'ueberfaellig', 'storniert') NOT NULL DEFAULT 'entwurf',
  kunde_id INT UNSIGNED NOT NULL,
  auftrag_id INT UNSIGNED NULL,
  bezug_rechnung_id INT UNSIGNED NULL,
  -- Recipient as sent (kept unchanged even if the customer record changes later)
  empfaenger_name VARCHAR(200) NULL,
  empfaenger_zusatz VARCHAR(200) NULL,
  empfaenger_adresse VARCHAR(200) NULL,
  empfaenger_plz VARCHAR(10) NULL,
  empfaenger_ort VARCHAR(100) NULL,
  empfaenger_email VARCHAR(254) NULL,
  rechnungsdatum DATE NULL,
  leistung_von DATE NULL,
  leistung_bis DATE NULL,
  faellig_am DATE NULL,
  summe_netto_cent INT NOT NULL DEFAULT 0,
  summe_steuer_cent INT NOT NULL DEFAULT 0,
  summe_brutto_cent INT NOT NULL DEFAULT 0,
  steuerhinweis VARCHAR(255) NULL,
  verschickt_am DATETIME NULL,
  bezahlt_am DATE NULL,
  pdf_datei VARCHAR(255) NULL,
  notizen TEXT NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rechnungen_nummer (nummer),
  KEY idx_rechnungen_status (status),
  KEY idx_rechnungen_faellig (faellig_am),
  CONSTRAINT fk_rechnungen_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE RESTRICT,
  CONSTRAINT fk_rechnungen_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE SET NULL,
  CONSTRAINT fk_rechnungen_bezug FOREIGN KEY (bezug_rechnung_id) REFERENCES rechnungen (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rechnung_positionen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rechnung_id INT UNSIGNED NOT NULL,
  position SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  beschreibung VARCHAR(255) NOT NULL,
  menge DECIMAL(10,2) NOT NULL DEFAULT 1,
  einzelpreis_cent INT NOT NULL DEFAULT 0,
  steuersatz DECIMAL(5,2) NOT NULL DEFAULT 0,
  buchung_id INT UNSIGNED NULL,
  CONSTRAINT fk_rechnung_positionen_rechnung FOREIGN KEY (rechnung_id) REFERENCES rechnungen (id) ON DELETE CASCADE,
  CONSTRAINT fk_rechnung_positionen_buchung FOREIGN KEY (buchung_id) REFERENCES buchungen (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE zahlungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rechnung_id INT UNSIGNED NOT NULL,
  betrag_cent INT NOT NULL,
  datum DATE NOT NULL,
  art ENUM('ueberweisung', 'bar', 'paypal', 'shop', 'gutschein', 'sonstiges') NOT NULL DEFAULT 'ueberweisung',
  notiz VARCHAR(255) NULL,
  CONSTRAINT fk_zahlungen_rechnung FOREIGN KEY (rechnung_id) REFERENCES rechnungen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mahnungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  rechnung_id INT UNSIGNED NOT NULL,
  stufe TINYINT UNSIGNED NOT NULL,
  datum DATE NOT NULL,
  gebuehr_cent INT NOT NULL DEFAULT 0,
  neue_frist DATE NULL,
  CONSTRAINT fk_mahnungen_rechnung FOREIGN KEY (rechnung_id) REFERENCES rechnungen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gutscheine (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  wert_cent INT NOT NULL,
  restwert_cent INT NOT NULL,
  kunde_id INT UNSIGNED NULL,
  ausgestellt_am DATE NOT NULL,
  gueltig_bis DATE NULL,
  notiz VARCHAR(255) NULL,
  UNIQUE KEY uq_gutscheine_code (code),
  CONSTRAINT fk_gutscheine_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gutschein_einloesungen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  gutschein_id INT UNSIGNED NOT NULL,
  rechnung_id INT UNSIGNED NOT NULL,
  betrag_cent INT NOT NULL,
  datum DATE NOT NULL,
  CONSTRAINT fk_einloesungen_gutschein FOREIGN KEY (gutschein_id) REFERENCES gutscheine (id) ON DELETE RESTRICT,
  CONSTRAINT fk_einloesungen_rechnung FOREIGN KEY (rechnung_id) REFERENCES rechnungen (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Aufgaben (Dashboard-To-dos)

CREATE TABLE aufgaben (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  titel VARCHAR(255) NOT NULL,
  faellig_am DATE NULL,
  erledigt_am DATETIME NULL,
  kunde_id INT UNSIGNED NULL,
  auftrag_id INT UNSIGNED NULL,
  benutzer_id INT UNSIGNED NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aufgaben_offen (erledigt_am, faellig_am),
  CONSTRAINT fk_aufgaben_kunde FOREIGN KEY (kunde_id) REFERENCES kunden (id) ON DELETE CASCADE,
  CONSTRAINT fk_aufgaben_auftrag FOREIGN KEY (auftrag_id) REFERENCES auftraege (id) ON DELETE CASCADE,
  CONSTRAINT fk_aufgaben_benutzer FOREIGN KEY (benutzer_id) REFERENCES benutzer (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
