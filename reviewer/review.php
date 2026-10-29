<?php

/**
 * Reviewer workspace for a single assignment: accept, decline, and submit
 * the structured review report.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('reviewer', 'admin');

$user = currentUser();
$pdo = db();
$assignmentId = (int) ($_GET['id'] ?? 0);
$reviewerId = (int) $user['id'];

$statement = $pdo->prepare(
    'SELECT ra.*, m.id AS manuscript_id, m.reference_no, m.title, m.abstract, m.keywords, m.subject, m.article_type, m.submitted_at, m.status AS manuscript_status,
            e.name AS editor_name, e.email AS editor_email
     FROM reviewer_assignments ra
     INNER JOIN manuscripts m ON m.id = ra.manuscript_id
     LEFT JOIN users e ON e.id = ra.editor_id
     WHERE ra.id = ? LIMIT 1'
);
$statement->execute([$assignmentId]);
$assignment = $statement->fetch();

if (!$assignment || ((int) $assignment['reviewer_id'] !== $reviewerId && $user['role'] !== 'admin')) {
    http_response_code(404);
    $metaTitle = 'Review not found';
    $pageTitle = 'Review not found';
    $pageDescription = 'The requested review assignment could not be found.';
    $activePage = 'reviewer-reviews';
    $isPortal = true;
    require __DIR__ . '/../includes/header.php';
    ?>
    <div class="portal-shell">
        <?php renderPortalSidebar($user, 'reviews'); ?>
        <div class="portal-main"><div class="portal-card"><div class="empty-state">
            <ion-icon name="document-outline" aria-hidden="true"></ion-icon>
            <h1>Review not found</h1>
            <p>This assignment does not exist or it is not linked to your account.</p>
            <a class="portal-btn" href="<?= e(url('reviewer/reviews.php')) ?>">Back to my reviews</a>
        </div></div></div>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$manuscriptId = (int) $assignment['manuscript_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('reviewer/review.php?id=' . $assignmentId);
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'accept') {
        $pdo->prepare("UPDATE reviewer_assignments SET status = 'accepted', responded_at = NOW() WHERE id = ?")
            ->execute([$assignmentId]);
        audit('review.accept', 'manuscript', $manuscriptId, 'Review accepted for ' . $assignment['reference_no']);
        flash('success', 'Thank you. The review is now assigned to you.');
        redirect('reviewer/review.php?id=' . $assignmentId);
    }

    if ($action === 'decline') {
        $reason = trim((string) ($_POST['decline_reason'] ?? ''));
        $pdo->prepare("UPDATE reviewer_assignments SET status = 'declined', responded_at = NOW(), comments_for_editor = ? WHERE id = ?")
            ->execute([$reason !== '' ? $reason : null, $assignmentId]);

        if ((int) $assignment['editor_id'] > 0) {
            notify((int) $assignment['editor_id'], 'review_declined', 'Review invitation declined', $assignment['reference_no'] . ' — ' . $user['name'] . ' declined the invitation.', 'editor/manuscripts.php');
        }
        audit('review.decline', 'manuscript', $manuscriptId, 'Review declined for ' . $assignment['reference_no']);
        flash('success', 'The invitation has been declined and the editor notified.');
        redirect('reviewer/reviews.php');
    }

    if ($action === 'submit') {
        $recommendation = (string) ($_POST['recommendation'] ?? '');
        $commentsForAuthor = trim((string) ($_POST['comments_for_author'] ?? ''));
        $commentsForEditor = trim((string) ($_POST['comments_for_editor'] ?? ''));

        $errors = [];
        if (!array_key_exists($recommendation, review_recommendations())) {
            $errors[] = 'Choose a recommendation.';
        }
        if (mb_strlen($commentsForAuthor) < 30) {
            $errors[] = 'The comments for the author must be at least 30 characters long.';
        }

        if ($errors) {
            foreach ($errors as $error) {
                flash('danger', $error);
            }
            redirect('reviewer/review.php?id=' . $assignmentId);
        }

        $round = (int) $pdo->query('SELECT COALESCE(MAX(round), 0) + 1 FROM reviews WHERE manuscript_id = ' . $manuscriptId)->fetchColumn();

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO reviews (manuscript_id, reviewer_assignment_id, reviewer_id, round, recommendation, comments_for_author, comments_for_editor)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $manuscriptId,
                $assignmentId,
                $reviewerId,
                $round,
                $recommendation,
                $commentsForAuthor,
                $commentsForEditor !== '' ? $commentsForEditor : null,
            ]);

            $pdo->prepare(
                "UPDATE reviewer_assignments SET status = 'completed', responded_at = NOW(), recommendation = ?, comments_for_author = ?, comments_for_editor = ? WHERE id = ?"
            )->execute([$recommendation, $commentsForAuthor, $commentsForEditor !== '' ? $commentsForEditor : null, $assignmentId]);

            $pdo->commit();
        } catch (PDOException $exception) {
            $pdo->rollBack();
            flash('danger', 'The report could not be saved. Please try again.');
            redirect('reviewer/review.php?id=' . $assignmentId);
        }

        if ((int) $assignment['editor_id'] > 0) {
            notify((int) $assignment['editor_id'], 'review_submitted', 'Peer review report submitted', $assignment['reference_no'] . ' — ' . $user['name'] . ' submitted a report.', 'editor/manuscripts.php');
        }

        audit('review.submit', 'manuscript', $manuscriptId, 'Report submitted for ' . $assignment['reference_no']);
        flash('success', 'Your report has been submitted to the handling editor.');
        redirect('reviewer/review.php?id=' . $assignmentId);
    }

    redirect('reviewer/review.php?id=' . $assignmentId);
}

$files = manuscriptFiles($manuscriptId);
$existingReview = null;
$reviewStatement = $pdo->prepare('SELECT * FROM reviews WHERE reviewer_assignment_id = ? ORDER BY id DESC LIMIT 1');
$reviewStatement->execute([$assignmentId]);
$existingReview = $reviewStatement->fetch();

$metaTitle = 'Review ' . $assignment['reference_no'];
$pageTitle = 'Review ' . $assignment['reference_no'];
$pageDescription = truncateText($assignment['title'], 160);
$activePage = 'reviewer-reviews';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'reviews'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            (string) $assignment['reference_no'],
            (string) $assignment['title'],
            '<a class="portal-btn portal-btn--ghost" href="' . e(url('reviewer/reviews.php')) . '"><ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>My reviews</a>'
        );
        ?>

        <div class="portal-actions">
            <span class="badge badge--<?= $assignment['status'] === 'completed' ? 'good' : ($assignment['status'] === 'declined' ? 'bad' : ($assignment['status'] === 'accepted' ? 'info' : 'warn')) ?>"><?= e(ucfirst((string) $assignment['status'])) ?></span>
            <span class="badge badge--info">Due <?= e(formatDate($assignment['due_at'])) ?></span>
            <span class="badge"><?= e($assignment['subject'] ?? '—') ?></span>
        </div>

        <div class="split-panel">
            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Manuscript</h2><p>Submitted <?= e(formatDate($assignment['submitted_at'])) ?>.</p></div></div>
                    <div class="portal-card-body">
                        <h3 style="margin:0 0 12px;font-size:17px;line-height:1.4"><?= e($assignment['title']) ?></h3>
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.75;color:var(--portal-muted)"><?= e($assignment['abstract']) ?></p>
                        <p style="margin:0;font-size:13.5px"><strong>Keywords:</strong> <?= e($assignment['keywords']) ?></p>

                        <h3 style="margin:22px 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Files</h3>
                        <ul class="file-list">
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <ion-icon name="document-attach-outline" aria-hidden="true"></ion-icon>
                                    <div><span class="file-name"><?= e($file['original_name']) ?> <span class="badge">v<?= e((string) $file['version']) ?></span></span><span class="file-meta"><?= e(formatBytes((int) $file['size_bytes'])) ?></span></div>
                                    <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('includes/download.php?id=' . (int) $file['id'])) ?>">Download</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <?php if ($existingReview): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><div><h2>Your submitted report</h2><p>Submitted <?= e(formatDateTime($existingReview['submitted_at'])) ?>.</p></div></div>
                        <div class="portal-card-body">
                            <p style="margin:0 0 14px"><span class="badge badge--<?= e(editorial_decisions()[$existingReview['recommendation']]['tone'] ?? 'info') ?>"><?= e(review_recommendations()[$existingReview['recommendation']] ?? '—') ?></span></p>
                            <h3 style="margin:0 0 6px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Comments for the author</h3>
                            <p style="margin:0;font-size:14px;line-height:1.7"><?= e($existingReview['comments_for_author']) ?></p>
                            <?php if (!empty($existingReview['comments_for_editor'])): ?>
                                <h3 style="margin:18px 0 6px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Confidential note to the editor</h3>
                                <p style="margin:0;font-size:14px;line-height:1.7;color:var(--portal-muted)"><?= e($existingReview['comments_for_editor']) ?></p>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php elseif ($assignment['status'] === 'accepted'): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><div><h2>Submit your report</h2><p>Only the comments for the author are shared with the author.</p></div></div>
                        <div class="portal-card-body">
                            <form class="portal-form" method="post" action="<?= e(url('reviewer/review.php?id=' . $assignmentId)) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="submit">
                                <div class="form-field">
                                    <label for="recommendation">Recommendation</label>
                                    <select id="recommendation" name="recommendation" required>
                                        <?php foreach (review_recommendations() as $key => $label): ?>
                                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="comments_for_author">Comments for the author <span class="required-mark">*</span></label>
                                    <textarea id="comments_for_author" name="comments_for_author" required placeholder="Summarise the strengths, weaknesses, and required changes."></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="comments_for_editor">Confidential note to the editor <span class="hint">optional</span></label>
                                    <textarea id="comments_for_editor" name="comments_for_editor" placeholder="Only the handling editor will read this."></textarea>
                                </div>
                                <div class="portal-actions">
                                    <button class="portal-btn" type="submit"><ion-icon name="send-outline" aria-hidden="true"></ion-icon>Submit report</button>
                                </div>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <div style="display:flex;flex-direction:column;gap:22px">
                <?php if ($assignment['status'] === 'invited'): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><h2>Respond to the invitation</h2></div>
                        <div class="portal-card-body">
                            <form class="portal-form" method="post" action="<?= e(url('reviewer/review.php?id=' . $assignmentId)) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="accept">
                                <button class="portal-btn portal-btn--block" type="submit"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>Accept the review</button>
                            </form>
                            <form class="portal-form" method="post" action="<?= e(url('reviewer/review.php?id=' . $assignmentId)) ?>" style="margin-top:18px">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="decline">
                                <div class="form-field">
                                    <label for="decline_reason">Decline the review</label>
                                    <textarea id="decline_reason" name="decline_reason" placeholder="Briefly explain why you cannot review this manuscript."></textarea>
                                </div>
                                <button class="portal-btn portal-btn--ghost portal-btn--block" type="submit"><ion-icon name="close-circle-outline" aria-hidden="true"></ion-icon>Decline</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>Assignment details</h2></div>
                    <div class="portal-card-body">
                        <div class="detail-grid">
                            <div class="detail-item"><span>Manuscript</span><strong><?= e($assignment['reference_no']) ?></strong></div>
                            <div class="detail-item"><span>Article type</span><strong><?= e($assignment['article_type'] ?? '—') ?></strong></div>
                            <div class="detail-item"><span>Handling editor</span><strong><?= e($assignment['editor_name'] ?? 'Unassigned') ?></strong></div>
                            <div class="detail-item"><span>Invited</span><strong><?= e(formatDate($assignment['invited_at'])) ?></strong></div>
                            <div class="detail-item"><span>Due date</span><strong><?= e(formatDate($assignment['due_at'])) ?></strong></div>
                            <div class="detail-item"><span>Round</span><strong><?= e((string) ((int) ($existingReview['round'] ?? 1))) ?></strong></div>
                        </div>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>Review policy</h2></div>
                    <div class="portal-card-body">
                        <p style="margin:0;font-size:13.5px;line-height:1.7;color:var(--portal-muted)">NIJOSTAM uses a single-blind review model: reviewer identities are withheld from authors, while authors are identified to reviewers. Reports should be constructive, evidence based, and actionable. The editorial office may contact you for clarification of a recommendation.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
