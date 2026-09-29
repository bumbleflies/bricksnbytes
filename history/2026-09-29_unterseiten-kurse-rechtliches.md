# Unterseiten-Vorlage, /kurse, Über uns & Rechtliches — September 29, 2026

## Summary
One shared subpage template, simpler navigation with mobile icon buttons, a data-driven course
overview, the "Über uns" page, legal pages with the original texts, and favicons/web manifest.

## Changes
- `src/layouts/SubpageLayout.astro`: small page hero (title + intro), ~70ch content column
  (`wide` for tile grids), closing booking/contact block. Used by every page except the home page.
- `Header.astro`: only "Kurse" → `/kurse`, mega-menu, burger and drawer removed. On mobile,
  "Anrufen" and "Kurs buchen" are 44×44 icon buttons (Lucide `phone`, `calendar-check`, inline SVG).
- Home: hero CTA → `/kurse`; tag above "Unsere Angebote" removed; tiles use
  `public/images/angebote/angebot-<slug>.webp` when present (`src/data/offerImage.ts`), else the
  old image; alt texts in `src/content/offers/*.yaml`; "Wer wir sind" text shortened;
  why-photo half width on mobile.
- `BlobTile.astro`: tile extracted from the offers section, shared with `/kurse`; row layout is the
  global `.tile-row` class.
- `/kurse`: tiles from `src/content/courses/*.yaml` (name, age, text, shop link, image, colour),
  button "Termine & Tickets" opens the shop in a new tab.
- `/programs/*` removed; nginx answers with 301 → `/kurse/`, Astro `redirects` cover dev/preview.
- `/uber-uns`, `/faq` (details/summary), `/impressum`, `/datenschutz`: legal and FAQ texts taken
  1:1 from bricksnbytes.de (`/privacy-policy/` holds Impressum and Datenschutz together, `/faq/`).
- Favicons from `public/images/`, `public/site.webmanifest`, `theme-color`.

## Open items
- Upload `public/images/angebote/angebot-*.webp` (fallback images are shown until then)
- Datenschutz / Impressum review (see PR description)

## Verification
```bash
npm run test && npx astro check && npm run build
```
