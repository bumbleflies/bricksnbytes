# 2026-11-24 — IBS image: mbstring regression and first-boot smoke test

## Problem

`bnb-office.web` on servy entered a restart loop (190+ restarts) after watchtower
pulled `bumblecode/ibs:latest`. Both public hostnames (`ibs.bricksnbytes.de`,
`ibs.bumbleflies.de`) served Traefik's branded error page. Every boot reached
the admin-seed step and died:

```
PHP Fatal error: Uncaught Error: Call to undefined function mb_strlen()
in /var/www/html/bin/seed-admin.php:31
```

## Root cause

Renovate PR #177 (commit `54b1c886`, 2026-11-19) bumped the image base from
`php:8.5-apache` to `php:8.6-apache`. The Dockerfile never installed `mbstring`:
PHP 8.5's official image ships it, 8.6's does not. The app uses `mb_*` in Auth
and every import module, and `bin/seed-admin.php` calls `mb_strlen()` *before*
its "users already exist → no-op" check, so the entrypoint exited 1 on every
boot even though the DB already had an admin user.

The old CI healthcheck ran the image with only `DB_USER`/`DB_PASSWORD` — no
`IBS_ADMIN_*` — so the seed step was skipped; Apache started and `/healthz.php`
answered 200. Unit tests ran on `php:8.5-cli`, where mbstring exists. Watchtower
logged two rollback attempts, but `latest` still pointed at the bad digest, so
the rollback did not restore the working image.

## Solution

- `backoffice/Dockerfile`: install `libonig-dev` and add `mbstring` to
  `docker-php-ext-install` alongside `pdo_mysql`.
- `backoffice/tests/image-smoke.sh` (new): asserts the required PHP extensions,
  then boots the image against a real MariaDB with `IBS_ADMIN_*` set and checks
  that migrations and the first-admin seed complete and the image healthcheck
  turns healthy. Tears down its containers/network via an EXIT trap.
- `.github/workflows/backoffice.yml`: run the smoke test in the `healthcheck`
  job in addition to the existing no-DB healthcheck.

## Verification

On servyy-test.lxd (Docker is unavailable on the controller):

- RED: `image-smoke.sh bumblecode/ibs@sha256:26d691…` → `missing PHP extension:
  mbstring`, exit 1.
- GREEN: built the fixed context (`docker build -t ibs-smoke:fix -`) and ran
  `image-smoke.sh ibs-smoke:fix` → extensions OK, boot/migrate/seed OK, exit 0.
- Existing `php tests/run.php` suite on `php:8.5-cli`: 96 tests, 0 failures.

Production was not touched by this change. Publishing `latest` (merge to
master) lets watchtower replace the crash-looping container with the fixed
image.
