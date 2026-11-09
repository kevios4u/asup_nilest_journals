<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json');
echo json_encode([
    'APP_BASE_URL' => APP_BASE_URL,
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
    'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'not set',
], JSON_PRETTY_PRINT);