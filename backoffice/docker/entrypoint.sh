#!/bin/sh
# Docker entrypoint for the IBS backoffice image.
# Order: generate config.php from env → wait for DB → pre-migration
# safety dump → run migrations (bin/migrate.php) → start apache (CMD).
#
# The DB wait is BOUNDED: if no database answers after the grace period we
# still start apache (app is unusable but container stays healthy via
# /healthz and docker healthchecks/logs show the problem). This keeps CI
# smoke tests (`docker run` without a DB) deterministic.

set -e

APP_ROOT="${APP_ROOT:-/var/www/html}"
DB_HOSTNAME="${DB_HOSTNAME:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-ibs}"
DB_USER="${DB_USER:?DB_USER not set}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD not set}"
PHP_CLI="php -d display_errors=stderr"

log() { echo "[entrypoint] $*"; }

# 1) config.php from env (force regeneration via IBS_REGENERATE_CONFIG=1)
if [ ! -f "$APP_ROOT/config.php" ] || [ "${IBS_REGENERATE_CONFIG:-0}" = "1" ]; then
    log "generating config.php from environment"
    APP_ROOT="$APP_ROOT" php "$APP_ROOT/docker/make-config.php"
else
    log "config.php present, keeping it"
fi

mkdir -p /backups

# 2) wait for the DB (bounded: 45 tries * 2s = 90s max)
tries=0
until mariadb -h "$DB_HOSTNAME" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --connect-timeout=3 -e 'SELECT 1' >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 45 ]; then
        log "WARNING: DB ${DB_HOSTNAME}:${DB_PORT} not reachable after 90s, starting anyway"
        break
    fi
    sleep 2
done

if [ "$tries" -lt 45 ]; then
    # 3) pre-migration safety dump (version control for schema changes)
    log "DB reachable, taking pre-migration dump"
    mariadb-dump -h "$DB_HOSTNAME" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction --routines "$DB_NAME" \
        | gzip > "/backups/pre-migrate-$(date +%Y%m%dT%H%M%SZ).sql.gz"
    # keep the last 30 pre-migration dumps
    find /backups -name 'pre-migrate-*.sql.gz' -type f | sort -r | tail -n +31 | xargs -r rm -f
    log "dump written"
else
    log "skipping pre-migration dump (DB unreachable)"
fi

# 4) migrations (idempotent, schema_version-gated)
if [ "$tries" -lt 45 ] && [ "${IBS_SKIP_MIGRATIONS:-0}" != "1" ]; then
    log "running migrations"
    (cd "$APP_ROOT" && $PHP_CLI bin/migrate.php) || { log "migrations FAILED"; exit 1; }
fi

# 5) hand off to apache (compose CMD)
exec "$@"
