# Website mailer

Small Node service behind nginx that turns the website forms (JSON) into an e-mail to `MAIL_TO` via SMTP:

- `POST /api/contact` – contact form
- `POST /api/newsletter` – newsletter sign-up (first/last name, e-mail, optional phone, consent checkbox).
  There is no double opt-in: the mail to `MAIL_TO` is the record of the consent; the sign-up is entered
  in the backoffice by hand.
- `POST /api/newsletter-abmelden` – unsubscribe (form on `/newsletter-abmelden`, only the e-mail address).
  The mailer withdraws the newsletter consent in the backoffice (`POST /api/newsletter-abmelden` there, with
  the shared token in the `X-Abmelde-Token` header) and sends an info mail to `MAIL_TO` saying whether that
  worked. The visitor always gets the same answer, whether the address is known or not.

It runs inside the same Docker image as nginx (started by `docker/40-start-mailer.sh`).

## Configuration (container environment)

| Variable       | Required | Default                | Notes                                          |
|----------------|----------|------------------------|------------------------------------------------|
| `SMTP_HOST`    | yes      | –                      | e.g. `smtp.strato.de`                          |
| `SMTP_PORT`    | no       | `465`                  | 465 = implicit TLS, 587 = STARTTLS             |
| `SMTP_SECURE`  | no       | `true` if port is 465  |                                                |
| `SMTP_USER`    | yes*     | –                      | mailbox login, e.g. `info@bricksnbytes.de`     |
| `SMTP_PASS`    | yes*     | –                      | mailbox password — pass as a secret, never commit |
| `MAIL_FROM`    | no       | `SMTP_USER`            | must be an address the SMTP server may send as |
| `MAIL_TO`      | no       | `info@bricksnbytes.de` |                                                |
| `BACKOFFICE_ABMELDE_URL`   | no | – | full URL of the backoffice endpoint, e.g. `https://<backoffice>/api/newsletter-abmelden` — keep it out of the repo |
| `BACKOFFICE_ABMELDE_TOKEN` | no | – | shared secret, same value as `NEWSLETTER_ABMELDE_TOKEN` in the backoffice — pass as a secret, never commit |

Without `BACKOFFICE_ABMELDE_URL`/`BACKOFFICE_ABMELDE_TOKEN` an unsubscribe only sends the info mail, which asks
to remove the address in the backoffice by hand.

Without `SMTP_HOST` the forms answer with a friendly 503 pointing to the e-mail address.

Example:

```bash
docker run -p 80:80 \
  -e SMTP_HOST=smtp.strato.de -e SMTP_USER=info@bricksnbytes.de -e SMTP_PASS=... \
  bumblecode/bnb:latest
```

## Spam and abuse protection

- Honeypot field `website` (filled → silently accepted, not sent)
- Server-side validation (`validate.mjs`, unit-tested in `validate.test.mjs`)
- Rate limit: 5 submissions per IP and 100 in total per hour (all forms together); body limit 32 KB

## Local development

```bash
cd mailer && npm install && npm run dev   # dry run: prints mails instead of sending
npm run dev                               # in the project root; the /api/ paths are proxied
```
