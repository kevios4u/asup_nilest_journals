<?php
/**
 * Removes verification accounts and manuscripts created by the test runs.
 * Run: php database/cleanup_test_data.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

$emails = [
    'demo.author@nijostam.test',
    'demo.editor@nijostam.test',
    'demo.reviewer@nijostam.test',
    'demo.admin@nijostam.test',
];

$manuscriptIds = [];
foreach ($emails as $email) {
    $statement = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $statement->execute([$email]);
    $userId = $statement->fetchColumn();
    if ($userId !== false) {
        $manuscriptStatement = $pdo->prepare('SELECT id FROM manuscripts WHERE submitted_by = ?');
        $manuscriptStatement->execute([(int) $userId]);
        foreach ($manuscriptStatement->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $manuscriptIds[] = (int) $id;
        }
    }
}

$wildcard = $pdo->query("SELECT id FROM users WHERE email LIKE 'webtest.%@example.test' OR email LIKE 'smoke.%@example.test'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($wildcard as $id) {
    $manuscriptStatement = $pdo->prepare('SELECT id FROM manuscripts WHERE submitted_by = ?');
    $manuscriptStatement->execute([(int) $id]);
    foreach ($manuscriptStatement->fetchAll(PDO::FETCH_COLUMN) as $manuscriptId) {
        $manuscriptIds[] = (int) $manuscriptId;
    }
}

foreach (array_unique($manuscriptIds) as $manuscriptId) {
    $files = $pdo->prepare('SELECT stored_path FROM manuscript_files WHERE manuscript_id = ?');
    $files->execute([$manuscriptId]);
    foreach ($files->fetchAll(PDO::FETCH_COLUMN) as $storedPath) {
        deleteStoredFile((string) $storedPath);
    }
    $pdo->prepare('UPDATE published_articles SET manuscript_id = NULL WHERE manuscript_id = ?')->execute([$manuscriptId]);
    $pdo->prepare('DELETE FROM manuscripts WHERE id = ?')->execute([$manuscriptId]);
}

foreach (array_merge($emails, []) as $email) {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
}
foreach ($wildcard as $id) {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([(int) $id]);
}

$pdo->exec("DELETE FROM audit_logs WHERE action LIKE 'smoke.%' OR action LIKE 'workflow.%' OR description LIKE '%verification%'");
$pdo->exec("DELETE FROM notifications WHERE user_id NOT IN (SELECT id FROM users)");

echo "Removed " . count(array_unique($manuscriptIds)) . " test manuscript(s) and " . (count($emails) + count($wildcard)) . " test account(s).\n";
echo 'users: ' . $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . PHP_EOL;
echo 'manuscripts: ' . $pdo->query('SELECT COUNT(*) FROM manuscripts')->fetchColumn() . PHP_EOL;
echo 'published_articles: ' . $pdo->query('SELECT COUNT(*) FROM published_articles')->fetchColumn() . PHP_EOL;
echo 'journal_issues: ' . $pdo->query('SELECT COUNT(*) FROM journal_issues')->fetchColumn() . PHP_EOL;
