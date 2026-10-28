-- 010: type (colour) for the entries already taken over from the planner, by title – same patterns as
-- PlanerImport::ART_MUSTER (first match wins). Only entries without a type; ones set by hand stay.

UPDATE kalender_eintraege SET art = 'kurs'
WHERE art IS NULL AND import_lauf LIKE 'planer-%'
  AND titel REGEXP '(?i)programmierkurs|ferienkurs|\\bPEP\\b|\\bDGhK\\b|\\(P\\)|\\(A\\)';

UPDATE kalender_eintraege SET art = 'schule'
WHERE art IS NULL AND import_lauf LIKE 'planer-%'
  AND titel REGEXP '(?i)festkreis|elternabend|monatsfeier';
