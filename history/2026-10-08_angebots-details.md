# Angebotsseiten: Detail-Pop-up für alle Karten

## Problem
Die Karten auf /kurse, /schulprojekttage, /geburtstage und /vorschule-hort zeigten nur vier
Stichpunkte. Mehr Infos gab es nur im Shop. Außerdem fehlten die eigentlichen Kursfotos:
mittelschule.webp wurde doppelt verwendet, und why-photo.png diente als Platzhalter.

## Lösung
- **Pop-up pro Karte:** Ein Klick auf Bild, Titel oder Karte, oder Enter/Leertaste, öffnet es.
  - Auf dem Desktop erscheint ein zentriertes Modal, auf Handys ein Bottom Sheet.
  - Natives `<dialog>`: Schließen per ESC, X oder Klick auf den Hintergrund. Der Fokus bleibt im Dialog und springt danach zur Karte zurück.
  - Kein Framework, keine neue Bibliothek.
- **Hover (nur Geräte mit Maus):** Die Karte hebt sich, das Bild zoomt, „Mehr erfahren →“ erscheint. Auf Touch-Geräten ist der Hinweis immer sichtbar. `prefers-reduced-motion` wird beachtet.
- **Eine Datenquelle:** `src/data/kartendetails.ts`, ein Eintrag pro Karten-YAML.
  - Alter, Dauer, Material und „Das nehmen die Kinder mit“ kommen weiter aus den Stichpunkten der Karte.
  - Die Texte stammen von den Shop-Übersichtsseiten.
  - Fehlendes steht als `TODO: …` darin: in `npm run dev` gelb markiert, im Live-Build ausgeblendet.
- **„4–6 Termine à 60 Min.“** bei Programmierkursen und Online-Programmierkursen.
- **Bilder:** Die hochgeladenen Fotos waren JPEGs in `.svg`-Hüllen und lagen unter falschen Pfaden.
  - Sie wurden als WebP neu abgelegt, mit abgeschnittenen Collage-Rändern.
  - `public/images/kurse/kurs-*.webp` (6) und `public/images/angebot-*.webp` (4); die SVG-Hüllen wurden entfernt.
  - Lazy Loading war bereits gesetzt.

## Dateien
- neu: `src/data/kartendetails.ts`, `src/data/kartendetails.test.ts`, `src/components/DetailModal.astro`, `src/components/TileDetails.astro`
- geändert: `src/components/BlobTile.astro` (optional `detailsId`), `src/components/TileList.astro`, `src/components/Icon.astro` (map-pin, tag, x), `src/content/tiles/kurse/{programmierkurse,online}.yaml`
- Bilder: siehe oben

## Tests
- `npm run build`, `npx astro check` (0 Fehler), `npm run test` (34 Tests)
- Playwright bei 1280 px und 390 px mit 210 Prüfungen auf allen 4 Seiten:
  - jede Karte öffnet per Klick aufs Foto, Titel und aria-labelledby stimmen
  - Schließen per ESC, X und Hintergrund, der Fokus kehrt zurück
  - Tab bleibt im Dialog, Enter und Leertaste öffnen
  - Bottom Sheet auf dem Handy, Hover-Effekt auf dem Desktop
  - der Shop-Button der Karte bleibt separat klickbar
  - kein horizontales Scrollen, keine TODOs im Live-Build, keine JS-Fehler

## Pflege
Texte in `src/data/kartendetails.ts` ändern. Eine neue Karte (YAML) braucht dort einen Eintrag,
sonst schlagen Build und Test fehl. Ein schärferes Foto legt man mit gleichem Namen als `.webp`
nach `public/images/kurse/`.
