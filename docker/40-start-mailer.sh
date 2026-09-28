#!/bin/sh
# Run by the nginx image's /docker-entrypoint.sh before nginx starts.
# Keeps the contact-form mailer running in the background, restarting it if it exits.
# SMTP settings come from the container environment (see mailer/README.md).
set -e

(
  while true; do
    node /opt/mailer/server.mjs
    echo "[mailer] exited, restarting in 5s" >&2
    sleep 5
  done
) &
