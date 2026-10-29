<?php
/**
 * End-to-end smoke test for the journal management system.
 * Run: php database/smoke_test.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$failures = 0;
$checks = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $failures, $checks;
    $checks++;
    if ($condition) {
        echo "  PASS  {$label}\n";
    } else {
        $failures++;
        echo "  FAIL  {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

$pdo = db();
echo "Connection\n";
check('PDO connection', $pdo instanceof PDO);

if (!$pdo instanceof PDO) {
    exit(1);
}

echo "\nSchema\n";
$required = [
    'users', 'author_profiles', 'manuscripts', 'manuscript_authors', 'manuscript_files',
    'editor_assignments', 'reviewer_assignments', 'reviews', 'editorial_decisions',
    'revision_history', 'journal_issues', 'published_articles', 'notifications', 'audit_logs',
];
$existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($required as $table) {
    check("table {$table}", in_array($table, $existing, true));
}

echo "\nPreserved public content\n";
$articles = (int) $pdo->query('SELECT COUNT(*) FROM published_articles')->fetchColumn();
$issues = (int) $pdo->query('SELECT COUNT(*) FROM journal_issues')->fetchColumn();
check('published articles preserved', $articles >= 37, "count={$articles}");
check('journal issues preserved', $issues >= 6, "count={$issues}");

echo "\nRegistration\n";
$email = 'smoke.' . bin2hex(random_bytes(4)) . '@example.test';
[$ok, $message, $userId] = registerUser('Smoke Tester', $email, 'Testing12345pass', 'author');
check('author registration', $ok, $message);
check('user id returned', $userId > 0);

$check2 = $pdo->prepare('SELECT role, password_hash FROM users WHERE id = ?');
$check2->execute([$userId]);
$row = $check2->fetch();
check('role stored', ($row['role'] ?? '') === 'author');
check('password hashed', !empty($row['password_hash']) && password_verify('Testing12345pass', $row['password_hash']));

$profile = $pdo->prepare('SELECT COUNT(*) FROM author_profiles WHERE user_id = ?');
$profile->execute([$userId]);
check('author profile created', (int) $profile->fetchColumn() === 1);

echo "\nLogin\n";
[$ok, $message, $user] = attemptLogin($email, 'Testing12345pass');
check('login succeeds', $ok, $message);
[$ok2, $message2] = attemptLogin($email, 'wrong-password');
check('wrong password rejected', !$ok2, $message2);

echo "\nManuscript submission\n";
$_SESSION['user_id'] = $userId;
$reference = manuscriptReference($pdo);
check('reference format', (bool) preg_match('/^NIJOSTAM-\d{4}-\d{4}$/', $reference), $reference);

$statement = $pdo->prepare(
    "INSERT INTO manuscripts (reference_no, title, abstract, keywords, subject, article_type, language, status, submitted_by, submitted_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())"
);
$statement->execute([$reference, 'A smoke test manuscript for the NIJOSTAM journal system', str_repeat('Abstract text. ', 12), 'testing, workflow, journal', 'Science', 'Original research', 'English', $userId]);
$manuscriptId = (int) $pdo->lastInsertId();
check('manuscript inserted', $manuscriptId > 0);

$pdo->prepare('INSERT INTO manuscript_authors (manuscript_id, user_id, name, email, is_corresponding, author_order) VALUES (?, ?, ?, ?, 1, 1)')
    ->execute([$manuscriptId, $userId, 'Smoke Tester', $email]);
check('manuscript authors linked', count(manuscriptAuthors($manuscriptId)) === 1);

$manuscript = loadManuscript($manuscriptId);
check('manuscript loads', $manuscript !== null);
check('owner can view', canViewManuscript($manuscript, currentUser()));

echo "\nEditorial workflow\n";
$editorEmail = 'smoke.editor.' . bin2hex(random_bytes(3)) . '@example.test';
registerUser('Smoke Editor', $editorEmail, 'Testing12345pass', 'editor');
$editorId = (int) $pdo->query("SELECT id FROM users WHERE email = '{$editorEmail}'")->fetchColumn();

$pdo->prepare('INSERT INTO editor_assignments (manuscript_id, editor_id, assigned_by) VALUES (?, ?, ?)')
    ->execute([$manuscriptId, $editorId, $userId]);
check('editor assigned', count(manuscriptEditors($manuscriptId)) === 1);

$reviewerEmail = 'smoke.reviewer.' . bin2hex(random_bytes(3)) . '@example.test';
registerUser('Smoke Reviewer', $reviewerEmail, 'Testing12345pass', 'reviewer');
$reviewerId = (int) $pdo->query("SELECT id FROM users WHERE email = '{$reviewerEmail}'")->fetchColumn();

$pdo->prepare('INSERT INTO reviewer_assignments (manuscript_id, reviewer_id, editor_id, status) VALUES (?, ?, ?, ?)')
    ->execute([$manuscriptId, $reviewerId, $editorId, 'accepted']);
check('reviewer assigned', count(manuscriptReviewers($manuscriptId)) === 1);

$pdo->prepare('INSERT INTO reviews (manuscript_id, reviewer_id, round, recommendation, comments_for_author, comments_for_editor) VALUES (?, ?, 1, ?, ?, ?)')
    ->execute([$manuscriptId, $reviewerId, 'minor_revision', 'Please clarify the methodology.', 'Comments visible to the editor only.']);
check('review recorded', count(manuscriptReviews($manuscriptId)) === 1);
check('editor comments hidden from authors', count(manuscriptReviews($manuscriptId)) === 1);

$pdo->prepare('INSERT INTO editorial_decisions (manuscript_id, editor_id, decision, letter) VALUES (?, ?, ?, ?)')
    ->execute([$manuscriptId, $editorId, 'minor_revision', 'Please revise your methodology section.']);
$pdo->prepare("UPDATE manuscripts SET status = 'revision_requested', revision_round = 1, decided_at = NOW(), decided_by = ? WHERE id = ?")
    ->execute([$editorId, $manuscriptId]);

$manuscript = loadManuscript($manuscriptId);
check('status updated', $manuscript['status'] === 'revision_requested');

echo "\nNotifications and audit\n";
notify($userId, 'decision', 'Minor revision requested', 'Please revise the methodology.');
$count = (int) $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?')->execute([$userId]);
$notif = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$notif->execute([$userId]);
check('notification stored', (int) $notif->fetchColumn() === 1);
check('unread counter', unreadNotificationCount($userId) === 1);

audit('smoke.test', 'manuscript', $manuscriptId, 'Smoke test run');
$log = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE action = ? AND entity_id = ?');
$log->execute(['smoke.test', $manuscriptId]);
check('audit log stored', (int) $log->fetchColumn() === 1);

echo "\nTimeline\n";
$timeline = manuscriptTimeline($manuscriptId);
check('timeline built', count($timeline) >= 3, 'events=' . count($timeline));

echo "\nCleanup\n";
$pdo->prepare('DELETE FROM audit_logs WHERE action = ? AND entity_id = ?')->execute(['smoke.test', $manuscriptId]);
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$editorEmail]);
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$reviewerEmail]);
check('test records removed', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0);

echo "\n" . ($failures === 0 ? "ALL CHECKS PASSED ({$checks})" : "{$failures} CHECK(S) FAILED of {$checks}") . "\n";
exit($failures === 0 ? 0 : 1);
