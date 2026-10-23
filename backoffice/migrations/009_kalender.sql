-- 009: own calendar entries for the dates overview (private and other appointments). Course dates,
-- orders with a date and children's birthdays are shown alongside, from their own tables.
-- "art" is for the colour coding (later); "import_lauf" marks entries taken over from the planner sheet.

CREATE TABLE kalender_eintraege (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  datum DATE NOT NULL,
  bis_datum DATE NULL,
  beginn TIME NULL,
  ende TIME NULL,
  titel VARCHAR(255) NOT NULL,
  art VARCHAR(30) NULL,
  notizen TEXT NULL,
  import_lauf VARCHAR(40) NULL,
  erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  geaendert_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_kalender_datum (datum),
  KEY idx_kalender_bis (bis_datum),
  KEY idx_kalender_import (import_lauf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
