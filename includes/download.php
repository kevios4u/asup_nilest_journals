<?php

/**
 * Protected manuscript file download.
 *
 * Files live outside the public web directory and are streamed only to users
 * who are permitted to see the manuscript.
 */

require_once __DIR__ . '/bootstrap.php';

requireLogin();

$fileId = (int) ($_GET['id'] ?? 0);
$user = currentUser();
$pdo = db();

if ($fileId <= 0 || !$pdo instanceof PDO) {
    http_response_code(404);
    exit('File not found.');
}

$statement = $pdo->prepare('SELECT * FROM manuscript_files WHERE id = ? LIMIT 1');
$statement->execute([$fileId]);
$file = $statement->fetch();

if (!$file || !canDownloadManuscriptFile($file, $user)) {
    http_response_code(403);
    exit('You do not have permission to download this file.');
}

audit('file.download', 'manuscript_file', (int) $file['id'], 'Downloaded ' . $file['original_name']);

downloadStoredFile((string) $file['stored_path'], (string) $file['original_name']);
