# Backoffice (Imperiales Sicherheitsbüro)

Internal backoffice for BricksnBytes. This is a **separate application** from the Astro site in the repo root:
**PHP 8.3+ / MySQL-MariaDB, no framework, no Composer dependencies**, deployed to Strato webspace or as the
`bumblecode/ibs` Docker image. Full setup, feature and data-model docs: [README.md](README.md).
The root `npm` commands do not apply here.

## Commands

Run from `backoffice/`:

```bash
php tests/run.php        # unit tests; no DB or config.php needed (CI uses php:8.5-cli)
php bin/setup.php        # first boot: create tables and the first admin (refuses to re-run)
php bin/migrate.php      # apply new migrations; safe to re-run
docker build -t ibs .    # build context is this directory (CI builds ./backoffice)
```

Local development (MariaDB on port 3307, built-in PHP server on 8099) is described in
[README.md — Lokale Entwicklung](README.md#6-lokale-entwicklung). Only use invented test data locally.

## Layout

```
public/      # webroot — the subdomain points here (index.php, healthz.php, .htaccess)
src/         # Auth, Db, Router, Csrf, View, Settings, Modules/<feature>/
templates/   # PHP HTML views, grouped per module
migrations/  # numbered SQL (001_…, …, 011_…); new schema changes go in a NEW 00X_ file
bin/         # CLI: setup.php, migrate.php, seed-admin.php
docker/      # image entrypoint, Apache config, PHP upload ini, config generator
tests/       # dependency-free PHP test runner + image smoke script
```

## Conventions

- New module: a folder under `src/Modules/`, templates under `templates/<modul>/`, a
  `…Controller::register($router);` line in `public/index.php`, and remove the
  `Platzhalter` entry once implemented.
- Schema changes are **append-only migrations** under `migrations/`; never edit an already applied file.
- Tests are plain PHP: each `tests/*Test.php` returns a list of `[name, callable]` pairs, run by
  `php tests/run.php` (also run in the `backoffice.yml` CI workflow).
- German is the primary UI language. Monetary amounts are stored in cents (`…_cent`).

## Safety and gotchas

- The repository is public: **never** commit `config.php`, `.htpasswd`, CSV imports, backups, or DB
  credentials. Copy `config.example.php` to `config.php`; `storage/` is created at runtime and gitignored.
- `public/` is the only webroot — `config.php` must stay outside it. The real backoffice hostname is
  intentionally kept out of the docs.
- Secrets are passed as environment variables (`IBS_ADMIN_*`, `NEWSLETTER_ABMELDE_TOKEN`,
  `APP_OWNER_EMAIL`, …), never stored in the repo. The seed-admin entrypoint ignores `IBS_ADMIN_*`
  once a user exists.
- The website mailer calls the unauthenticated `POST /api/newsletter-abmelden` endpoint using the shared
  `X-Abmelde-Token` header; its token must match `NEWSLETTER_ABMELDE_TOKEN` here. See
  [../mailer/AGENTS.md](../mailer/AGENTS.md).
- Migrations change tables and are not automatically reversible — back up the database before applying
  them on the server. Never hotfix production directly.
