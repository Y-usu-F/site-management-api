<?php

declare(strict_types=1);

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

/**
 * @return array<string, string>
 */
function loadTestingEnvFile(string $path): array
{
    if (! is_file($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (! is_array($lines)) {
        return [];
    }

    $values = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
            continue;
        }

        [$rawKey, $rawValue] = explode('=', $trimmed, 2);
        $key = trim($rawKey);
        $value = trim($rawValue);
        $value = trim($value, " \t\n\r\0\x0B'\"");
        if ($key !== '') {
            $values[$key] = $value;
        }
    }

    return $values;
}

function requireTestingEnvironment(array $envFileValues): void
{
    $ciEnvironment = strtolower((string) envValue('CI_ENVIRONMENT', ''));
    $appEnvironment = strtolower((string) envValue('APP_ENV', ''));
    if ($ciEnvironment === '') {
        $ciEnvironment = strtolower((string) ($envFileValues['CI_ENVIRONMENT'] ?? ''));
    }
    if ($appEnvironment === '') {
        $appEnvironment = strtolower((string) ($envFileValues['APP_ENV'] ?? ''));
    }

    if ($ciEnvironment === 'testing' || $appEnvironment === 'testing') {
        return;
    }

    fwrite(STDERR, "Refusing to reset database outside testing environment.\n");
    fwrite(STDERR, "Set CI_ENVIRONMENT=testing or APP_ENV=testing.\n");
    exit(1);
}

function createMysqli(string $host, string $user, string $pass, int $port): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return new mysqli($host, $user, $pass, '', $port);
}

$envFileValues = loadTestingEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env.testing');
requireTestingEnvironment($envFileValues);

$host = (string) envValue('database.tests.hostname', $envFileValues['database.tests.hostname'] ?? envValue('database.default.hostname', '127.0.0.1'));
$user = (string) envValue('database.tests.username', $envFileValues['database.tests.username'] ?? envValue('database.default.username', 'root'));
$pass = (string) envValue('database.tests.password', $envFileValues['database.tests.password'] ?? envValue('database.default.password', ''));
$dbName = (string) envValue('database.tests.database', $envFileValues['database.tests.database'] ?? 'bys_test');
$port = (int) envValue('database.tests.port', $envFileValues['database.tests.port'] ?? envValue('database.default.port', '3306'));
$charset = (string) envValue('database.tests.charset', $envFileValues['database.tests.charset'] ?? 'utf8mb4');
$collation = (string) envValue('database.tests.DBCollat', $envFileValues['database.tests.DBCollat'] ?? 'utf8mb4_unicode_ci');

if ($dbName === '' || strtolower($dbName) === 'bys') {
    fwrite(STDERR, "Unsafe test database name: {$dbName}\n");
    exit(1);
}

if (! str_ends_with(strtolower($dbName), '_test')) {
    fwrite(STDERR, "Unsafe test database name (must end with _test): {$dbName}\n");
    exit(1);
}

try {
    $mysqli = createMysqli($host, $user, $pass, $port);
    $safeName = '`' . str_replace('`', '``', $dbName) . '`';
    $safeCharset = preg_replace('/[^a-zA-Z0-9_]/', '', $charset) ?: 'utf8mb4';
    $safeCollation = preg_replace('/[^a-zA-Z0-9_]/', '', $collation) ?: 'utf8mb4_unicode_ci';

    $mysqli->query("DROP DATABASE IF EXISTS {$safeName}");
    $mysqli->query("CREATE DATABASE {$safeName} CHARACTER SET {$safeCharset} COLLATE {$safeCollation}");
    $mysqli->close();
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to recreate test database: {$e->getMessage()}\n");
    exit(1);
}

// Apply migrations once so reset command can validate core schema deterministically.
// Spark must connect to database.tests (bys_test). Normal CLI uses database.default; see Services::migrations().
putenv('SPARK_USE_TESTS_DB=1');

$migrateExit = 0;
passthru(PHP_BINARY . ' spark migrate --all -g tests', $migrateExit);
if ($migrateExit !== 0) {
    fwrite(STDERR, "Migration step failed during test reset.\n");
    exit(1);
}

// Permissions matrix is not created by migrations; feature tests need catalog + role_permissions rows.
$seedExit = 0;
passthru(PHP_BINARY . ' spark db:seed RbacSeeder', $seedExit);
if ($seedExit !== 0) {
    fwrite(STDERR, "RbacSeeder failed during test reset.\n");
    exit(1);
}

try {
    $mysqli = new mysqli($host, $user, $pass, $dbName, $port);
    $requiredTables = [
        'companies',
        'users',
        'roles',
        'permissions',
        'user_roles',
        'role_permissions',
        'audit_logs',
        'user_refresh_tokens',
    ];

    $missing = [];
    foreach ($requiredTables as $table) {
        $stmt = $mysqli->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1'
        );
        if (! ($stmt instanceof mysqli_stmt)) {
            continue;
        }

        $stmt->bind_param('ss', $dbName, $table);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result !== false && $result->fetch_row() !== null;
        $stmt->close();

        if (! $exists) {
            $missing[] = $table;
        }
    }
    $mysqli->close();

    if ($missing !== []) {
        fwrite(STDERR, "Missing required core tables after reset: " . implode(', ', $missing) . "\n");
        exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to validate core tables after reset: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, "Test database reset completed: {$dbName}\n");
fwrite(STDOUT, "Core migrations validated for tests.\n");
exit(0);
