<?php

/**
 * Backwards-compatible configuration shim.
 *
 * The application now loads its configuration from the config/ directory.
 * This file is kept so that any page still including the root config.php
 * continues to work.
 */

require_once __DIR__ . '/config/database.php';

$pdo = $config['pdo'];
$dbHost = $config['host'];
$dbName = $config['name'];
