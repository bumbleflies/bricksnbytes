# IBS backoffice — Docker image + hosting (2026-10-08)

BricksnBytes office app (`backoffice/`) got its container story, companion to
servyy-container's `bnb-office` service (issue bumbleflies/bricksnbytes#105).

- `backoffice/Dockerfile`: PHP 8.3-Apache image, `pdo_mysql` + opcache,
  MariaDB client (for pre-migration dumps), FallbackResource front
  controller, `/healthz` healthcheck.
- `backoffice/docker/entrypoint.sh`: generates `config.php` from env
  (`docker/make-config.php`), bounded DB wait (90s → start anyway so CI can
  smoke-test), pre-migration `mariadb-dump` into `/backups` (volume in
  servyy), `bin/migrate.php`, exec apache.
- `backoffice/docker/make-config.php`: env → `config.php` (DB_* / APP_* /
  LOGIN_* vars; same defaults as `config.example.php`).
- `backoffice/public/healthz.php`: DB-independent 200 endpoint for the
  container HEALTHCHECK (main app pages may 500 while DB is down).
- `.github/workflows/backoffice.yml`: build → healthcheck smoke → push
  `bumblecode/ibs:latest` + `master-<sha>` on master pushes touching
  `backoffice/**` (org `DOCKER_TOKEN` secret, same as the website workflow).

Hosting side: servyy-container `bnb-office` compose (web + MariaDB sidecar,
digest-pinned to the image already on servy, Traefik via
`ibs.bricksnbytes.de`, HTTP-01 cert), daily DB dump timer → restic
off-host. Deployed to servyy-test first, prod after approval.
