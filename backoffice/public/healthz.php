<?php

declare(strict_types=1);

// Container healthcheck + CI smoke target. Deliberately does NOT include
// src/bootstrap.php: it must answer 200 even when the DB is unreachable
// (e.g. during `docker run` in CI without a database).

header('Content-Type: application/json');
echo '{"status":"ok","app":"ibs-backoffice"}';
