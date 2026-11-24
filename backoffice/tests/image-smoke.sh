#!/usr/bin/env bash
# First-boot smoke test for the published bumblecode/ibs image.
#
# Run in CI (workflow backoffice.yml) or locally with Docker:
#   bash backoffice/tests/image-smoke.sh [image-ref]
#
# Catches two regressions the old no-DB healthcheck could not see:
#   1. A required PHP extension is missing from the image. mb_* is used by
#      Auth and every import module; bin/seed-admin.php calls mb_strlen().
#   2. The entrypoint's DB -> migrations -> first-admin chain fails. CI used
#      to start the image without IBS_ADMIN_*, so the seed step was skipped.
set -euo pipefail

IMAGE="${1:-bumblecode/ibs:latest}"
DB_IMAGE="${MARIADB_TEST_IMAGE:-mariadb:11.4}"
PREFIX="ibs-smoke-$$"
DB="$PREFIX-db"
APP="$PREFIX-app"
NET="$PREFIX-net"
DB_NAME=ibs
DB_USER=ibs
DB_PASSWORD=smoke-test-password
ADMIN_USER=smokeadmin
ADMIN_PASSWORD=SmokePass123!

cleanup() {
  docker rm -f "$APP" "$DB" >/dev/null 2>&1 || true
  docker network rm "$NET" >/dev/null 2>&1 || true
}
trap cleanup EXIT

fail() {
  echo "FAIL: $*" >&2
  exit 1
}

# 1) Required extensions. This is the fast, direct guard against an image
#    rebuild that silently drops an extension the app relies on.
echo "== checking PHP extensions in $IMAGE =="
docker run --rm --entrypoint php "$IMAGE" -r '
  foreach (["mbstring", "pdo_mysql", "sodium"] as $ext) {
    if (!extension_loaded($ext)) {
      fwrite(STDERR, "missing PHP extension: $ext\n");
      exit(1);
    }
  }
  echo "PHP extensions OK\n";
' || fail "required PHP extension missing from $IMAGE"

# 2) Real first boot: DB wait, migrations and the one-shot admin seed must all
#    succeed before Apache starts and the image healthcheck turns healthy.
echo "== booting $IMAGE against MariaDB and seeding the first admin =="
docker network create "$NET" >/dev/null

docker run -d --name "$DB" --network "$NET" \
  -e MARIADB_ROOT_PASSWORD=smoke-root-password \
  -e MARIADB_DATABASE="$DB_NAME" \
  -e MARIADB_USER="$DB_USER" \
  -e MARIADB_PASSWORD="$DB_PASSWORD" \
  "$DB_IMAGE" >/dev/null

for i in $(seq 1 60); do
  if docker exec "$DB" mariadb-admin ping -u "$DB_USER" -p"$DB_PASSWORD" --silent >/dev/null 2>&1; then
    break
  fi
  [ "$i" -eq 60 ] && fail "MariaDB did not become ready"
  sleep 1
done

docker run -d --name "$APP" --network "$NET" \
  -e DB_HOSTNAME="$DB" \
  -e DB_PORT=3306 \
  -e DB_NAME="$DB_NAME" \
  -e DB_USER="$DB_USER" \
  -e DB_PASSWORD="$DB_PASSWORD" \
  -e IBS_ADMIN_USER="$ADMIN_USER" \
  -e IBS_ADMIN_PASSWORD="$ADMIN_PASSWORD" \
  "$IMAGE" >/dev/null

for i in $(seq 1 60); do
  status=$(docker inspect --format='{{.State.Health.Status}}' "$APP" 2>/dev/null || echo "")
  [ "$status" = "healthy" ] && break
  state=$(docker inspect --format='{{.State.Status}}' "$APP" 2>/dev/null || echo "")
  [ "$state" = "exited" ] && fail "app container exited during boot; logs:\n$(docker logs "$APP" 2>&1)"
  [ "$i" -eq 60 ] && fail "app did not become healthy; logs:\n$(docker logs "$APP" 2>&1)"
  sleep 2
done

docker logs "$APP" 2>&1 | grep -F "seed-admin: admin \"$ADMIN_USER\" created." >/dev/null \
  || fail "first admin was not seeded; logs:\n$(docker logs "$APP" 2>&1)"

echo "PASS: $IMAGE boots with MariaDB, migrates and seeds the first admin"
