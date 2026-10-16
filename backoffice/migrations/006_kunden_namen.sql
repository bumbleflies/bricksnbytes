-- 006: first and last name directly on the customer (Stammdaten). Existing customers stay empty;
-- names can be filled from the e-mail address on the edit form or via Kunden → Namen aus E-Mail.

ALTER TABLE kunden
  ADD COLUMN vorname VARCHAR(100) NULL AFTER name,
  ADD COLUMN nachname VARCHAR(100) NULL AFTER vorname;
