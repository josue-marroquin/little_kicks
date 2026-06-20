<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Database.php';

use Kicks\App\Database;
// Load .env file into getenv() for CLI use (simple parser)
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        [$k, $v] = $parts;
        $k = trim($k);
        $v = trim($v);
        // Only set env var if not already set in environment
        if (getenv($k) === false) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

$sqlFile = __DIR__ . '/../migrations/create_kicks_tables.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Migration file not found: $sqlFile\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read migration file: $sqlFile\n");
    exit(1);
}

// Create a Database instance which will connect using env vars
try {
    $db = new Database();
    $pdo = $db->pdo();
    // Execute the migration SQL. This file may contain multiple statements.
    $pdo->exec($sql);
    echo "Migration applied successfully.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
