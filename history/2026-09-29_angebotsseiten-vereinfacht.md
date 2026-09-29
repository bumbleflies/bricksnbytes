# Vereinfachter Besucherweg & Angebotsseiten — September 29, 2026

## Summary
Visitor path is now: home → "Unsere Angebote" → offer page → book (shop) or request (e-mail).

## Changes
- Header: only logo + "Anrufen" + "Kurs buchen" (icons on mobile via `Icon.astro`, Lucide).
- Offer tiles link to `/kurse/`, `/schulprojekttage/`, `/geburtstage/`, `/vorschule-hort/`;
  `/angebote/*` redirects there (nginx 301 + Astro `redirects`).
- `TileGrid.astro` + `.tile-grid--n{1,2,3,4,6}`: column count follows the tile count so rows are
  always full (4 → 1/2/4, 6 → 1/2/3, 3 → 1/3).
- Tiles for all four offer pages live in `src/content/tiles/<page>/*.yaml` (bullets with icon,
  optional "Inklusive", `shopUrl` or `requestSubject`, `preferredImage` + fallback `image`).
  `TileList.astro` renders a page's tiles and the note below them.
- `SubpageLayout` `ctaMode="request"`: "Anfrage senden" (mailto with subject) + link to the
  home contact form.
- Images are picked at build time via `src/data/pickImage.ts`: `angebot-<slug>.(webp|svg|…)`,
  `kurse/kurs-*.webp`, `besonders.webp` — old images remain the fallback.
- Removed: `/angebote/[slug]`, `ProgramCard`, the `programs` and `courses` collections.

## Verification
```bash
npm run test && npx astro check && npm run build
```
Playwright at 390/768/1280 px: header icons ≥44 px on every page, balanced grids, links,
new-tab shop links, mailto request links, redirects (via nginx container).
