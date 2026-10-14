-- 005: customer status (acquisition pipeline) and the customer types Bibliothek and Partner

ALTER TABLE kunden
  MODIFY COLUMN typ ENUM('privat', 'schule', 'hort', 'kita', 'verein', 'firma', 'bibliothek', 'partner', 'sonstige') NOT NULL DEFAULT 'privat',
  ADD COLUMN status ENUM('akquise', 'kontakt', 'angefragt', 'interessent', 'angebot_offen', 'nachfassen', 'warteliste',
    'kunde', 'partner', 'beendet', 'kein_interesse', 'nicht_anschreiben') NULL AFTER name,
  ADD KEY idx_kunden_status (status);
