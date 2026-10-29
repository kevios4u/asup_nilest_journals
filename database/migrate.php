<?php

/**
 * NIJOSTAM Journal Management System — database migration.
 *
 * Applies the canonical schema (database/schema.sql) to the configured database
 * and upgrades the legacy public-site tables so published content is preserved.
 *
 * Usage:  php database/migrate.php
 */

$config = require __DIR__ . '/../config/database.php';

if (!$config['pdo'] instanceof PDO) {
    fwrite(STDERR, "Database connection failed. Check config/database.php.\n");
    exit(1);
}

$pdo = $config['pdo'];


function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $statement->execute([$table]);
    return (int) $statement->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $statement->execute([$table, $column]);
    return (int) $statement->fetchColumn() > 0;
}

function addColumn(PDO $pdo, string $table, string $definition): void
{
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    echo "  + added to $table: $definition\n";
}

function dropColumn(PDO $pdo, string $table, string $column): void
{
    $pdo->exec("ALTER TABLE `$table` DROP COLUMN `$column`");
    echo "  - dropped $table.$column\n";
}

echo "Connected to {$config['name']}\n\n";

echo "Legacy upgrade\n";
if (tableExists($pdo, 'articles') && !tableExists($pdo, 'published_articles')) {
    $pdo->exec('RENAME TABLE articles TO published_articles');
    echo "  renamed articles -> published_articles\n";
}

foreach ([
    'manuscript_id' => 'manuscript_id BIGINT UNSIGNED NULL',
    'doi' => 'doi VARCHAR(120) NULL',
    'abstract' => 'abstract TEXT NULL',
    'keywords' => 'keywords VARCHAR(500) NULL',
    'published_at' => 'published_at DATE NULL',
    'created_at' => 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
] as $column => $definition) {
    if (tableExists($pdo, 'published_articles') && !columnExists($pdo, 'published_articles', $column)) {
        addColumn($pdo, 'published_articles', $definition);
    }
}

foreach ([
    'role' => "role ENUM('author','editor','reviewer','admin') NOT NULL DEFAULT 'author'",
    'status' => "status ENUM('active','suspended') NOT NULL DEFAULT 'active'",
    'last_login_at' => 'last_login_at DATETIME NULL',
    'last_login_ip' => 'last_login_ip VARCHAR(45) NULL',
    'reset_token' => 'reset_token VARCHAR(64) NULL',
    'reset_expires_at' => 'reset_expires_at DATETIME NULL',
] as $column => $definition) {
    if (!columnExists($pdo, 'users', $column)) {
        addColumn($pdo, 'users', $definition);
    }
}

if (tableExists($pdo, 'journal_issues') && !columnExists($pdo, 'journal_issues', 'created_at')) {
    addColumn($pdo, 'journal_issues', 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

if (tableExists($pdo, 'announcements') && !columnExists($pdo, 'announcements', 'is_active')) {
    addColumn($pdo, 'announcements', 'is_active TINYINT(1) NOT NULL DEFAULT 1');
}

foreach (['manuscripts', 'manuscript_authors', 'manuscript_files', 'editor_assignments', 'reviewer_assignments', 'reviews', 'editorial_decisions', 'revision_history', 'notifications', 'audit_logs', 'author_profiles'] as $table) {
    if (tableExists($pdo, $table) && !columnExists($pdo, $table, 'created_at')) {
        addColumn($pdo, $table, 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
}

echo "\nSchema\n";
$sql = file_get_contents(__DIR__ . '/schema.sql');
$sql = preg_replace('/^\s*(CREATE DATABASE|USE)\s+[^;]+;\s*$/mi', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));
$applied = 0;
foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        $pdo->exec($statement);
        $applied++;
    } catch (PDOException $exception) {
        fwrite(STDERR, "  ! " . $exception->getMessage() . "\n  statement: " . substr(preg_replace('/\s+/', ' ', $statement), 0, 120) . "\n");
    }
}
echo "  applied {$applied} statements\n";

echo "\nRemovals\n";
foreach (['author_profiles', 'manuscript_authors'] as $table) {
    if (tableExists($pdo, $table) && columnExists($pdo, $table, 'orcid')) {
        dropColumn($pdo, $table, 'orcid');
    }
}

echo "\nSummary\n";
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    echo "  {$table}: {$count} rows\n";
}

echo "\nMigration complete.\n";
