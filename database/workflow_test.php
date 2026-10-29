<?php
/**
 * End-to-end role workflow test: author submits, editor screens and assigns a
 * reviewer, reviewer reports, editor decides, admin publishes.
 * Run: php database/workflow_test.php
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
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$stamp = bin2hex(random_bytes(3));
$authorEmail = "author.{$stamp}@example.test";
$editorEmail = "editor.{$stamp}@example.test";
$reviewerEmail = "reviewer.{$stamp}@example.test";
$adminEmail = "admin.{$stamp}@example.test";

echo "Setup\n";
registerUser('Workflow Author', $authorEmail, 'Testing12345pass', 'author');
registerUser('Workflow Editor', $editorEmail, 'Testing12345pass', 'editor');
registerUser('Workflow Reviewer', $reviewerEmail, 'Testing12345pass', 'reviewer');
registerUser('Workflow Admin', $adminEmail, 'Testing12345pass', 'admin');

$id = static function (string $email): int {
    global $pdo;
    $statement = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $statement->execute([$email]);
    return (int) $statement->fetchColumn();
};

$authorId = $id($authorEmail);
$editorId = $id($editorEmail);
$reviewerId = $id($reviewerEmail);
$adminId = $id($adminEmail);
check('four role accounts created', $authorId > 0 && $editorId > 0 && $reviewerId > 0 && $adminId > 0);

echo "\nSubmission\n";
$_SESSION['user_id'] = $authorId;
$reference = manuscriptReference($pdo);
check('reference format', (bool) preg_match('/^NIJOSTAM-\d{4}-\d{4}$/', $reference), $reference);

$pdo->prepare(
    "INSERT INTO manuscripts (reference_no, title, abstract, keywords, subject, article_type, language, status, submitted_by, submitted_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())"
)->execute([$reference, 'A workflow test manuscript for NIJOSTAM', str_repeat('Abstract content. ', 15), 'workflow, testing', 'Science', 'Original research', 'English', $authorId]);
$manuscriptId = (int) $pdo->lastInsertId();
check('manuscript created', $manuscriptId > 0);

$pdo->prepare('INSERT INTO manuscript_authors (manuscript_id, user_id, name, email, is_corresponding, author_order) VALUES (?, ?, ?, ?, 1, 1)')
    ->execute([$manuscriptId, $authorId, 'Workflow Author', $authorEmail]);
check('corresponding author linked', count(manuscriptAuthors($manuscriptId)) === 1);

$pdo->prepare('INSERT INTO revision_history (manuscript_id, version, notes, submitted_by) VALUES (?, 1, ?, ?)')
    ->execute([$manuscriptId, 'Initial submission.', $authorId]);
check('initial version recorded', count(manuscriptRevisions($manuscriptId)) === 1);

echo "\nScreening and assignment\n";
$pdo->prepare('INSERT INTO editor_assignments (manuscript_id, editor_id, assigned_by) VALUES (?, ?, ?)')
    ->execute([$manuscriptId, $editorId, $adminId]);
$pdo->prepare("UPDATE manuscripts SET status = 'awaiting_reviewer', screened_at = NOW(), screened_by = ? WHERE id = ?")
    ->execute([$editorId, $manuscriptId]);
check('screening recorded', (string) loadManuscript($manuscriptId)['status'] === 'awaiting_reviewer');

$pdo->prepare("INSERT INTO reviewer_assignments (manuscript_id, reviewer_id, editor_id, status, due_at) VALUES (?, ?, ?, 'invited', DATE_ADD(NOW(), INTERVAL 21 DAY))")
    ->execute([$manuscriptId, $reviewerId, $editorId]);
notify($reviewerId, 'review_invitation', 'Review invitation', $reference);
check('reviewer invited', count(manuscriptReviewers($manuscriptId)) === 1);
check('reviewer notified', unreadNotificationCount($reviewerId) === 1);

echo "\nPeer review\n";
$pdo->prepare("UPDATE reviewer_assignments SET status = 'accepted', responded_at = NOW() WHERE manuscript_id = ?")
    ->execute([$manuscriptId]);
$pdo->prepare(
    'INSERT INTO reviews (manuscript_id, reviewer_assignment_id, reviewer_id, round, recommendation, comments_for_author, comments_for_editor)
     VALUES (?, ?, ?, 1, ?, ?, ?)'
)->execute([
    $manuscriptId,
    (int) $pdo->query('SELECT id FROM reviewer_assignments WHERE manuscript_id = ' . $manuscriptId)->fetchColumn(),
    $reviewerId,
    'minor_revision',
    'Please expand the methodology section and add statistical tests.',
    'The paper is promising but needs a stronger discussion.',
]);
check('report recorded', count(manuscriptReviews($manuscriptId)) === 1);
check('editor-only note stored', manuscriptReviews($manuscriptId, true)[0]['comments_for_editor'] !== null);

echo "\nDecision\n";
$pdo->prepare('INSERT INTO editorial_decisions (manuscript_id, editor_id, decision, letter) VALUES (?, ?, ?, ?)')
    ->execute([$manuscriptId, $editorId, 'minor_revision', 'Please revise the methodology and resubmit.']);
$pdo->prepare("UPDATE manuscripts SET status = 'revision_requested', decision = 'minor_revision', decided_at = NOW(), decided_by = ? WHERE id = ?")
    ->execute([$editorId, $manuscriptId]);
notify($authorId, 'decision', 'Editorial decision: Minor revision', 'Please revise the methodology.');
check('decision recorded', (string) loadManuscript($manuscriptId)['status'] === 'revision_requested');
check('author notified', unreadNotificationCount($authorId) === 1);

echo "\nAuthor revision\n";
$pdo->prepare('INSERT INTO manuscript_files (manuscript_id, file_type, original_name, stored_path, mime_type, size_bytes, version, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, 2, ?)')
    ->execute([$manuscriptId, 'manuscript', 'revision-v2.pdf', 'manuscripts/2026/test.pdf', 'application/pdf', 1024, $authorId]);
$pdo->prepare('INSERT INTO revision_history (manuscript_id, version, notes, submitted_by) VALUES (?, 2, ?, ?)')
    ->execute([$manuscriptId, 'Expanded methodology.', $authorId]);
$pdo->prepare("UPDATE manuscripts SET status = 'revised', revision_round = 2 WHERE id = ?")->execute([$manuscriptId]);
check('revision tracked', count(manuscriptRevisions($manuscriptId)) === 2);
check('files tracked', count(manuscriptFiles($manuscriptId)) === 1);

echo "\nAcceptance and publication\n";
$pdo->prepare("UPDATE manuscripts SET status = 'accepted', decision = 'accept' WHERE id = ?")->execute([$manuscriptId]);
$pdo->prepare("UPDATE manuscripts SET status = 'in_production' WHERE id = ?")->execute([$manuscriptId]);
check('moved to production', (string) loadManuscript($manuscriptId)['status'] === 'in_production');

$issueStatement = $pdo->prepare('SELECT id FROM journal_issues ORDER BY published_at DESC LIMIT 1');
$issueStatement->execute();
$issueId = (int) $issueStatement->fetchColumn();
check('issue available', $issueId > 0);

$pdo->prepare(
    'INSERT INTO published_articles (issue_id, manuscript_id, title, authors, pages, category, sort_order, published_at)
     VALUES (?, ?, ?, ?, ?, ?, 0, CURDATE())'
)->execute([$issueId, $manuscriptId, 'A workflow test manuscript for NIJOSTAM', 'Workflow Author', '1 - 10', 'Science']);
$articleId = (int) $pdo->lastInsertId();
$pdo->prepare("UPDATE manuscripts SET status = 'published', published_article_id = ? WHERE id = ?")
    ->execute([$articleId, $manuscriptId]);

$published = $pdo->query('SELECT id, title FROM published_articles WHERE id = ' . $articleId)->fetch();
check('article published', (bool) $published);
check('manuscript marked published', (string) loadManuscript($manuscriptId)['status'] === 'published');

echo "\nPublic visibility\n";
$statement = $pdo->prepare('SELECT COUNT(*) FROM published_articles WHERE issue_id = ? AND id = ?');
$statement->execute([$issueId, $articleId]);
check('article listed in issue', (int) $statement->fetchColumn() === 1);

echo "\nAuthor access control\n";
$_SESSION['user_id'] = $authorId;
check('author sees own manuscript', canViewManuscript(loadManuscript($manuscriptId), currentUser()));

$otherEmail = "other.{$stamp}@example.test";
registerUser('Other Author', $otherEmail, 'Testing12345pass', 'author');
$otherId = $id($otherEmail);
$_SESSION['user_id'] = $otherId;
check('unrelated author blocked', !canViewManuscript(loadManuscript($manuscriptId), currentUser()));

echo "\nAudit trail\n";
audit('workflow.test', 'manuscript', $manuscriptId, 'Full workflow test run');
$log = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE action = ? AND entity_id = ?');
$log->execute(['workflow.test', $manuscriptId]);
check('audit entry written', (int) $log->fetchColumn() === 1);

echo "\nCleanup\n";
$pdo->prepare('UPDATE published_articles SET manuscript_id = NULL WHERE id = ?')->execute([$articleId]);
$pdo->prepare('DELETE FROM published_articles WHERE id = ?')->execute([$articleId]);
$pdo->prepare('DELETE FROM audit_logs WHERE action = ? AND entity_id = ?')->execute(['workflow.test', $manuscriptId]);
foreach ([$authorEmail, $editorEmail, $reviewerEmail, $adminEmail, $otherEmail] as $email) {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
}
check('test accounts removed', (int) $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE '%{$stamp}%'")->fetchColumn() === 0);
check('test manuscript removed', (int) $pdo->query('SELECT COUNT(*) FROM manuscripts WHERE id = ' . $manuscriptId)->fetchColumn() === 0);

echo "\n" . ($failures === 0 ? "ALL CHECKS PASSED ({$checks})" : "{$failures} CHECK(S) FAILED of {$checks}") . "\n";
exit($failures === 0 ? 0 : 1);
