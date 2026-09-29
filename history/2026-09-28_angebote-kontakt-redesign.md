# Angebote, Kontaktformular & Footer — September 28, 2026

## Summary
Reworked the home page around four offer categories, added category subpages, a founder section,
a contact form with its own mail service, a footer and placeholder legal pages.

## Solution

### Navigation (`Header.astro`)
- Only "Kurse" remains; mega-menu and mobile drawer list the 4 offer categories
- "Anrufen" → `tel:+491792342370`; every "Kurs buchen" → `https://shop.bricksnbytes.de` in a new tab
- Fixed pre-existing bugs: mobile drawer and mega-menu were not clickable (inherited
  `pointer-events: none` from `.nav-outer`), the drawer covered the burger so it could not be closed,
  the Kurse accordion never opened (class vs. `hidden` attribute), burger "X" was misaligned,
  mega-menu overlapped the pill
- Contact data lives in `src/data/site.ts`

### Home page
- Hero: image `/images/hero-roboter.svg` (**file still to be added** to `public/images/`), CTA → `#angebote`
- `OffersSection.astro` replaces `AgeGroupSection.astro` (`id="angebote"`); data in `src/content/offers/*.yaml`
- `WhoWeAreSection.astro` before "Was uns besonders macht", photo `public/images/zara.webp` (800×1045, 64 KB)
- `ContactForm.astro` below the CTA (`id="kontakt"`)

### Offer subpages (`src/pages/angebote/[slug].astro`)
- `/angebote/{programmierkurse,schulprojekttage,geburtstage,vorschule-hort}`
- Course tiles from `programs:` (existing program slugs) and `placeholders:` ("Beschreibung folgt")
- Existing `/programs/*` URLs are unchanged, so no redirects are needed

### Contact form + mailer (`mailer/`)
- Hosting is static nginx in Docker (no PHP), so a small Node service sends mail via SMTP
- nginx proxies `POST /api/contact` to `127.0.0.1:3001`; `docker/40-start-mailer.sh` starts it
  via the nginx image's entrypoint hooks
- Honeypot, server-side validation, rate limit (5/IP/h, 100/h total), 32 KB body limit
- Configuration via environment variables — see `mailer/README.md`

### Footer & legal
- `Footer.astro`: Impressum, FAQ, Datenschutz, `tel:`/`mailto:` links
- `/impressum` (§ 5 DDG placeholder) and `/datenschutz` (outline placeholder)
- Default meta description in German

## Deployment notes
- **SMTP env vars must be set** on the container (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, …);
  without them the form shows a friendly error pointing to info@bricksnbytes.de
- nginx: `absolute_redirect off` so `/impressum` → `/impressum/` no longer redirects to `http://`

## Open items
- Add `public/images/hero-roboter.svg`
- `/uber-uns` and `/faq` are linked but do not exist yet
- Fill in Impressum/Datenschutz; the Datenschutz should cover the contact form, hosting and Google Fonts

## Testing
- `npm run test`: 10 tests (incl. 8 for mailer validation)
- `npx astro check`: 0 errors; `npm run build`: 14 pages
- Docker image built and run: pages, `/health` and `/api/contact` (dry run + missing SMTP) verified
- Playwright at 390px and 1280px: drawer, accordion, mega-menu, form errors and success message

## Verification
```bash
npm run test && npx astro check && npm run build
cd mailer && npm install && npm run dev   # dry-run mailer; in another shell: npm run dev
```
