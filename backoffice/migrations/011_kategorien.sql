-- 011: new calendar categories (Kalender::ARTEN) instead of "kurs" / "schule". Planner entries get their
-- category again by title – same patterns and order as PlanerImport::ART_MUSTER (first match wins); an
-- entry no pattern matches keeps its old meaning: "kurs" becomes "bricksnbytes", "schule" stays.
-- Entries added by hand: "kurs" becomes "bricksnbytes", everything else stays.

UPDATE kalender_eintraege SET art = CONCAT('alt:', art) WHERE import_lauf LIKE 'planer-%' AND art IN ('kurs', 'schule');

UPDATE kalender_eintraege SET art = 'bibliothek'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)bibli';

UPDATE kalender_eintraege SET art = 'bnb-schule'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bGS\\b|\\bEDV\\b';

UPDATE kalender_eintraege SET art = 'bnb-pep'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bPEP\\b';

UPDATE kalender_eintraege SET art = 'bnb-dghk'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bDGhK\\b';

UPDATE kalender_eintraege SET art = 'bnb-allach'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)allach|\\(A\\)';

UPDATE kalender_eintraege SET art = 'bnb-pasing'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)pasing|\\(P\\)';

UPDATE kalender_eintraege SET art = 'tw'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bTW\\b|ipma|report';

UPDATE kalender_eintraege SET art = 'schule'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)fest?kreis|elter\\w*abend|monatsfeier|cafeteria|\\bschul';

UPDATE kalender_eintraege SET art = 'bricksnbytes'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)programmier|minecraft|medienführerschein|kurs|pictoblox|workshop|lego|train the trainer|schnuppern|game design';

UPDATE kalender_eintraege SET art = 'max'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bmaxi?\\b';

UPDATE kalender_eintraege SET art = 'zara'
WHERE import_lauf LIKE 'planer-%' AND (art IS NULL OR art LIKE 'alt:%')
  AND titel REGEXP '(?i)\\bzara\\b|yoga|tischtennis|arzt';

UPDATE kalender_eintraege SET art = 'bricksnbytes' WHERE art IN ('alt:kurs', 'kurs');

UPDATE kalender_eintraege SET art = 'schule' WHERE art = 'alt:schule';
