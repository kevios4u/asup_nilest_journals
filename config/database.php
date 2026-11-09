<?php

/**
 * Database connection.
 *
 * Configure with environment variables when available:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD
 */

$dbHost = getenv('DB_HOST') ?: 'sql101.infinityfree.com';
$dbName = getenv('DB_NAME') ?: 'if0_43390323_asup_nilest_journals';
$dbUser = getenv('DB_USER') ?: 'if0_43390323';
$dbPassword = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '3NdLK18kr0R';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    $pdo = null;
}

return [
    'pdo' => $pdo,
    'name' => $dbName,
    'host' => $dbHost,
];
