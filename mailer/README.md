# Contact-form mailer

Small Node service behind nginx: `POST /api/contact` (JSON) → e-mail to `MAIL_TO` via SMTP.
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

Without `SMTP_HOST` the form answers with a friendly 503 pointing to the e-mail address.

Example:

```bash
docker run -p 80:80 \
  -e SMTP_HOST=smtp.strato.de -e SMTP_USER=info@bricksnbytes.de -e SMTP_PASS=... \
  bumblecode/bnb:latest
```

## Spam and abuse protection

- Honeypot field `website` (filled → silently accepted, not sent)
- Server-side validation (`validate.mjs`, unit-tested in `validate.test.mjs`)
- Rate limit: 5 messages per IP and 100 in total per hour; body limit 32 KB

## Local development

```bash
cd mailer && npm install && npm run dev   # dry run: prints mails instead of sending
npm run dev                               # in the project root; /api/contact is proxied
```
