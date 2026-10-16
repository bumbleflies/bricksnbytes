<?php
declare(strict_types=1);

// Minimal test runner without dependencies: php tests/run.php
// Runs every tests/*Test.php; each file returns a list of [name, callable]. A test fails by throwing.

spl_autoload_register(static function (string $class): void {
    $prefix = 'Backoffice\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message !== '' ? "$message: " : '')
            . 'erwartet ' . var_export($expected, true) . ', erhalten ' . var_export($actual, true));
    }
}

$failed = 0;
$count = 0;
foreach (glob(__DIR__ . '/*Test.php') ?: [] as $file) {
    foreach (require $file as [$name, $test]) {
        $count++;
        try {
            $test();
            echo "ok   $name\n";
        } catch (Throwable $e) {
            $failed++;
            echo "FAIL $name\n     " . $e->getMessage() . "\n";
        }
    }
}
echo "\n$count Tests, $failed fehlgeschlagen\n";
exit($failed > 0 ? 1 : 0);
