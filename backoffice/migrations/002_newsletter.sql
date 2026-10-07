-- 002_newsletter: newsletter flag per customer (1 = angemeldet, 0 = nicht angemeldet / abgemeldet)

ALTER TABLE kunden
  ADD COLUMN newsletter TINYINT(1) NOT NULL DEFAULT 0 AFTER ort,
  ADD KEY idx_kunden_newsletter (newsletter);
