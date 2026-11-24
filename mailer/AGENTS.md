# Website mailer

Small standalone Node service behind nginx that turns the Astro site's JSON form posts into e-mails.
It has its own `package.json` (only dependency: **nodemailer**) and is **not** part of the root npm
project. Full endpoint and environment docs: [README.md](README.md).

Endpoints: `POST /api/contact`, `POST /api/newsletter`, `POST /api/newsletter-abmelden`. The last one
also withdraws the consent in the backoffice via its shared-token API before sending the info mail.

## Commands

Run from `mailer/`:

```bash
npm install
npm run dev     # MAILER_DRY_RUN=1 node server.mjs — prints mails instead of sending
npm start       # node server.mjs
```

Tests are Vitest `*.test.mjs` files. There is no `test` script in this package; from the **repo root**
run `npx vitest run mailer` (the root `npm run test` picks them up too).

## Conventions

- Keep validation I/O-free in `validate.mjs` and unit-test it in `validate.test.mjs`; keep the
  backoffice call injectable via `fetchImpl` (`unsubscribe.mjs`, tested in `unsubscribe.test.mjs`).
- Honeypot field, server-side validation, rate limit (5 submissions per IP / 100 total per hour) and
  the 32 KB body limit are the abuse-protection boundary — don't relax them casually.
- Visitors get the same answer whether an address is known or not; preserve that (no address enumeration).
- Without `SMTP_HOST`, forms answer with a friendly 503. Without the backoffice URL/token, an
  unsubscribe only sends the info mail asking for manual removal.
- Starts inside the website Docker image via `docker/40-start-mailer.sh`; nginx proxies `/api/` to it
  in local dev.

## Safety

- All configuration is container environment only: `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, optional
  `MAIL_FROM` / `MAIL_TO` and `BACKOFFICE_ABMELDE_URL` / `BACKOFFICE_ABMELDE_TOKEN`. Never commit or
  log these values; `BACKOFFICE_ABMELDE_TOKEN` must match the backoffice's `NEWSLETTER_ABMELDE_TOKEN`.
  See [../backoffice/AGENTS.md](../backoffice/AGENTS.md).
- Do not add runtime secrets to the Astro site — it stays fully static; e-mail sending lives only here.
