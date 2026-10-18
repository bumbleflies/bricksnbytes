-- 007: customer type "Öffentlicher Träger"; Bildungslokale become that type, libraries type Bibliothek.
-- Matched on the organisation name or the e-mail address (case-insensitive collation). Families with
-- children are never changed. Every change is written to the change log like a change in the backoffice.

ALTER TABLE kunden
  MODIFY COLUMN typ ENUM('privat', 'schule', 'hort', 'kita', 'verein', 'firma', 'bibliothek', 'partner', 'oeffentlich', 'sonstige') NOT NULL DEFAULT 'privat';

-- Bildungslokal → Öffentlicher Träger
INSERT INTO aenderungen (benutzername, tabelle, datensatz_id, aktion, aenderungen)
  SELECT 'migration 007', 'kunden', k.id, 'geaendert', JSON_OBJECT('typ', JSON_ARRAY(k.typ, 'oeffentlich'))
  FROM kunden k
  WHERE (k.name LIKE '%bildungslokal%' OR k.email LIKE '%bildungslokal%')
    AND k.typ <> 'oeffentlich'
    AND NOT EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id);

UPDATE kunden k SET k.typ = 'oeffentlich'
  WHERE (k.name LIKE '%bildungslokal%' OR k.email LIKE '%bildungslokal%')
    AND k.typ <> 'oeffentlich'
    AND NOT EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id);

-- Bibliothek (also "Stadtbibliothek …", e-mail addresses with "bibliothek") → Bibliothek
INSERT INTO aenderungen (benutzername, tabelle, datensatz_id, aktion, aenderungen)
  SELECT 'migration 007', 'kunden', k.id, 'geaendert', JSON_OBJECT('typ', JSON_ARRAY(k.typ, 'bibliothek'))
  FROM kunden k
  WHERE (k.name LIKE '%bibliothek%' OR k.email LIKE '%bibliothek%')
    AND COALESCE(k.name, '') NOT LIKE '%bildungslokal%'
    AND k.typ <> 'bibliothek'
    AND NOT EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id);

UPDATE kunden k SET k.typ = 'bibliothek'
  WHERE (k.name LIKE '%bibliothek%' OR k.email LIKE '%bibliothek%')
    AND COALESCE(k.name, '') NOT LIKE '%bildungslokal%'
    AND k.typ <> 'bibliothek'
    AND NOT EXISTS (SELECT 1 FROM kinder ki WHERE ki.kunde_id = k.id);
