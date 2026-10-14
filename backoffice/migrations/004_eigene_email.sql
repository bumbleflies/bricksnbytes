-- 004_eigene_email: own address for the "An" field of the customer mail buttons.
-- Config app.eigene_email (APP_OWNER_EMAIL) still wins when set; this is the default.

INSERT INTO einstellungen (schluessel, wert) VALUES ('eigene_email', 'info@bricksnbytes.de')
  ON DUPLICATE KEY UPDATE wert = wert;
