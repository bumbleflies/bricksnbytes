# Newsletter unsubscribe page + automatic withdrawal in the backoffice (2026-10-21)

Problem: there was no self-service way to leave the newsletter (only "write us an e-mail"). Newsletters go
out from the mail client with all recipients in BCC, so a personal unsubscribe link per recipient is not
possible.

Solution:
- Website: new page `/newsletter-abmelden` (noindex, `SubpageLayout`, `mailerForm` + honeypot). Always the
  same answer, so the page does not reveal whether an address is known. Linked in the footer, the newsletter
  sign-up text and the privacy policy (section 4, which also describes the transfer to the backoffice).
- Mailer: `POST /api/newsletter-abmelden` (`mailer/unsubscribe.mjs`): forwards the address to the backoffice
  (`BACKOFFICE_ABMELDE_URL`, header `X-Abmelde-Token: BACKOFFICE_ABMELDE_TOKEN`, 10 s timeout) and sends an
  info mail to `MAIL_TO` with the result (withdrawn / unknown address / backoffice not reachable → remove by
  hand). If the backoffice recorded it, the visitor gets a success even when the info mail fails. Same rate
  limit as the other forms. nginx and the Astro dev proxy forward the new path.
- Backoffice: `Modules/Newsletter/AbmeldungController` — public route without CSRF (new `csrf` flag on
  `Router::post`), token check with `hash_equals` (token ≥ 32 chars, unset = endpoint off, 403).
  `KundenRepository::withdrawNewsletterByEmail()` matches the newsletter address (own e-mail, else main
  contact, case-insensitive) and withdraws via `setNewsletter(…, false)` (now returns whether it changed);
  change log user "Website (Selbst-Abmeldung)". `/api/` requests start no session.
- "Newsletter verschicken" pre-fills the mail text with the unsubscribe link (`Verteiler::newsletterFooter()`,
  config `newsletter.abmelde_url` / `NEWSLETTER_ABMELDE_URL`).

Deployment: generate a token (`php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`), set it as
`NEWSLETTER_ABMELDE_TOKEN` in the backoffice and as `BACKOFFICE_ABMELDE_TOKEN` (plus `BACKOFFICE_ABMELDE_URL`)
in the website container. Without them the page still works and only sends the info mail.

Verification: `npm run test`, `npx astro check`, `npm run build`, `php backoffice/tests/run.php`; local
end-to-end with MariaDB (migrations applied): known / main-contact / already withdrawn / non-main contact /
unknown addresses, wrong or missing token (403), invalid address (422), change log entries; mailer dry run
against the backoffice and against an unreachable backoffice; page checked in Chromium at 1280 px and 390 px.
