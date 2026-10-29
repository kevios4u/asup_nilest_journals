<?php

$articleId = filter_input(INPUT_GET, 'article', FILTER_VALIDATE_INT);
if ($articleId) {
    header('Location: article.php?id=' . $articleId, true, 302);
    exit;
}

$view = $_GET['view'] ?? '';
$routes = [
    'about' => 'about.php',
    'submissions' => 'submissions.php',
    'archive' => 'archive.php',
    'issue' => 'current-issue.php',
    'announcement' => 'announcement.php',
    'readers' => 'readers.php',
    'authors' => 'authors.php',
    'librarians' => 'librarians.php',
];

if ($view === 'category') {
    $category = rawurlencode((string) ($_GET['name'] ?? 'Science'));
    header('Location: category.php?name=' . $category, true, 302);
    exit;
}

if ($view === 'issue' && ($archiveId = filter_input(INPUT_GET, 'archive', FILTER_VALIDATE_INT))) {
    header('Location: current-issue.php?archive=' . $archiveId, true, 302);
    exit;
}

header('Location: ' . ($routes[$view] ?? 'index.php'), true, 302);
exit;
