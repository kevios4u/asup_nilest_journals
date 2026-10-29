<?php

/**
 * Reviewer dashboard.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('reviewer', 'admin');

$user = currentUser();
$pdo = db();
$reviewerId = (int) $user['id'];

$counts = ['invited' => 0, 'accepted' => 0, 'completed' => 0, 'declined' => 0, 'overdue' => 0];
$assignments = [];

if ($pdo instanceof PDO) {
    $statement = $pdo->prepare(
        'SELECT ra.*, m.reference_no, m.title, m.subject, m.abstract, m.submitted_at, m.status AS manuscript_status,
                e.name AS editor_name
         FROM reviewer_assignments ra
         INNER JOIN manuscripts m ON m.id = ra.manuscript_id
         LEFT JOIN users e ON e.id = ra.editor_id
         WHERE ra.reviewer_id = ?
         ORDER BY FIELD(ra.status, \'invited\', \'accepted\', \'completed\', \'declined\'), ra.due_at'
    );
    $statement->execute([$reviewerId]);
    $assignments = $statement->fetchAll();

    foreach ($assignments as $assignment) {
        $status = (string) $assignment['status'];
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
        if ($status === 'accepted' && $assignment['due_at'] && strtotime((string) $assignment['due_at']) < time()) {
            $counts['overdue']++;
        }
    }
}

$metaTitle = 'Reviewer dashboard';
$pageTitle = 'Reviewer dashboard';
$pageDescription = 'Review invitations, deadlines, and submitted reports.';
$activePage = 'reviewer-dashboard';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'dashboard'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Reviewer workspace', 'Confidential peer review for NIJOSTAM manuscripts.', '<a class="portal-btn" href="' . e(url('reviewer/reviews.php')) . '"><ion-icon name="clipboard-outline" aria-hidden="true"></ion-icon>All reviews</a>'); ?>

        <?php if ($counts['overdue'] > 0): ?>
            <div class="alert alert--warning"><ion-icon name="alert-circle-outline" aria-hidden="true"></ion-icon><span><strong><?= e((string) $counts['overdue']) ?></strong> review(s) are past the agreed due date. Please submit or decline them.</span></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon"><ion-icon name="mail-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['invited']) ?></strong><span>Awaiting response</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['accepted']) ?></strong><span>Accepted, in progress</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['completed']) ?></strong><span>Reports submitted</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="time-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['overdue']) ?></strong><span>Overdue</span></div></div>
        </div>

        <section class="portal-card">
            <div class="portal-card-head"><div><h2>Assigned manuscripts</h2><p>Accept or decline an invitation before the due date.</p></div></div>
            <?php if (!$assignments): ?>
                <div class="empty-state">
                    <ion-icon name="clipboard-outline" aria-hidden="true"></ion-icon>
                    <h2>No assignments yet</h2>
                    <p>Review invitations appear here as soon as an editor assigns them to you.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Status</th><th>Due</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><span class="ref"><?= e($assignment['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($assignment['title'], 62)) ?></span><span class="muted"><?= e($assignment['subject'] ?? '—') ?></span></td>
                                <td>
                                    <span class="badge badge--<?= $assignment['status'] === 'completed' ? 'good' : ($assignment['status'] === 'declined' ? 'bad' : ($assignment['status'] === 'accepted' ? 'info' : 'warn')) ?>"><?= e(ucfirst((string) $assignment['status'])) ?></span>
                                </td>
                                <td><span class="muted"><?= e(formatDate($assignment['due_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('reviewer/review.php?id=' . (int) $assignment['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="portal-card">
            <div class="portal-card-head"><h2>Reviewer code of conduct</h2></div>
            <div class="portal-card-body">
                <ul class="timeline">
                    <li><span class="timeline-dot"><ion-icon name="shield-checkmark-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Keep the manuscript confidential</strong><span>Do not share the paper or discuss it with colleagues who are not part of the review.</span></div></li>
                    <li><span class="timeline-dot"><ion-icon name="bulb-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Declare conflicts of interest</strong><span>Decline the invitation if you have a competing interest or recent collaboration with the authors.</span></div></li>
                    <li><span class="timeline-dot"><ion-icon name="timer-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Report on time</strong><span>The agreed review period is <?= e((string) setting('review_period_days', 21)) ?> days. Request an extension from the handling editor if needed.</span></div></li>
                    <li><span class="timeline-dot"><ion-icon name="chatbubble-ellipses-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Separate author comments from confidential notes</strong><span>Only the "comments for the author" section is shared with the author.</span></div></li>
                </ul>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
