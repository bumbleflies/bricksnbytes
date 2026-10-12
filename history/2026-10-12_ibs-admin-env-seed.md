# IBS backoffice — first admin via env seed (2026-10-12)

Problem: the Docker entrypoint (`backoffice/docker/entrypoint.sh`) ran
migrations but never created a user, so a fresh servyy deploy (`bnb-office`)
had no way to log in without manual `docker exec -it … php bin/setup.php`.
The interactive `bin/setup.php` stays the path for Strato/local.

Solution (Docker/servyy only, seed-once + ignore):
- `backoffice/bin/seed-admin.php` (new): reads `IBS_ADMIN_USER` /
  `IBS_ADMIN_PASSWORD` from env; no-op when empty or when `benutzer` is
  non-empty. Same username regex as `setup.php`, password 12–256 chars per
  `Auth::PASSWORD_MIN_LENGTH/MAX_LENGTH`, argon2id via `Auth::hash()`,
  explicit `rolle='admin', aktiv=1`. Duplicate-key (1062) treated as
  already-seeded so parallel boots are safe. Never logs the password.
- `backoffice/docker/entrypoint.sh`: new step 4b after migrations (same
  DB-reachable guard) calls `bin/seed-admin.php` when both vars are set;
  non-zero exit fails the boot so bad input shows in `docker logs`.
- `backoffice/README.md`: Docker first-boot section (change password via UI,
  then remove vars as hygiene).
- servyy-container side (branch `claude/bnb-office-admin-seed`): `app.env.j2`
  passes both vars from vault-encrypted `secrets.yml`; no compose change
  needed (web already uses `env_file`).

Verification: `php -l`, `shellcheck`, local MariaDB boot with/without env +
second-boot idempotency, CI `backoffice.yml` green (no env → no-op).
