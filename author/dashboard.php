<?php

/**
 * Author dashboard.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('author', 'admin');

$user = currentUser();
$pdo = db();
$userId = (int) $user['id'];

$counts = [
    'total' => 0,
    'submitted' => 0,
    'under_review' => 0,
    'revision_requested' => 0,
    'accepted' => 0,
    'published' => 0,
];

$manuscripts = [];
if ($pdo instanceof PDO) {
    $statement = $pdo->prepare(
        "SELECT m.*, (SELECT COUNT(*) FROM editorial_decisions d WHERE d.manuscript_id = m.id) AS decision_count
         FROM manuscripts m WHERE m.submitted_by = ? ORDER BY m.submitted_at DESC, m.id DESC"
    );
    $statement->execute([$userId]);
    $manuscripts = $statement->fetchAll();

    $counts['total'] = count($manuscripts);
    foreach ($manuscripts as $manuscript) {
        $status = (string) $manuscript['status'];
        if ($status === 'revision_requested') {
            $counts['revision_requested']++;
        }
        if (in_array($status, ['under_screening', 'awaiting_reviewer', 'under_review', 'revised'], true)) {
            $counts['under_review']++;
        }
        if ($status === 'accepted' || $status === 'in_production') {
            $counts['accepted']++;
        }
        if ($status === 'published') {
            $counts['published']++;
        }
        if ($status === 'submitted') {
            $counts['submitted']++;
        }
    }
}

$notifications = [];
if ($pdo instanceof PDO) {
    $statement = $pdo->prepare(
        'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5'
    );
    $statement->execute([$userId]);
    $notifications = $statement->fetchAll();
}

$metaTitle = 'Author dashboard';
$pageTitle = 'Author dashboard';
$pageDescription = 'Track your submitted manuscripts, revisions, and editorial decisions.';
$activePage = 'author-dashboard';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'dashboard'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            'Welcome back, ' . $user['name'],
            'Your workspace for NIJOSTAM submissions, revisions, and decisions.',
            '<a class="portal-btn" href="' . e(url('author/submit.php')) . '"><ion-icon name="cloud-upload-outline" aria-hidden="true"></ion-icon>Submit manuscript</a>'
        );
        ?>

        <?php if ($counts['revision_requested'] > 0): ?>
            <div class="alert alert--warning"><ion-icon name="alert-circle-outline" aria-hidden="true"></ion-icon><span><strong><?= e((string) $counts['revision_requested']) ?></strong> manuscript(s) are awaiting your revision. Upload a revised file to continue the review.</span></div>
        <?php endif; ?>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon"><ion-icon name="documents-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['total']) ?></strong><span>Total manuscripts</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="hourglass-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['under_review']) ?></strong><span>In the editorial process</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="refresh-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['revision_requested']) ?></strong><span>Revisions requested</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="trophy-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['accepted']) ?></strong><span>Accepted / in production</span></div></div>
        </div>

        <section class="portal-card">
            <div class="portal-card-head">
                <div>
                    <h2>My manuscripts</h2>
                    <p>Most recent submissions first.</p>
                </div>
                <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('author/manuscripts.php')) ?>">View all</a>
            </div>
            <?php if (!$manuscripts): ?>
                <div class="empty-state">
                    <ion-icon name="documents-outline" aria-hidden="true"></ion-icon>
                    <h2>No manuscripts yet</h2>
                    <p>Submit your first paper to receive a reference number and start the review process.</p>
                    <a class="portal-btn" href="<?= e(url('author/submit.php')) ?>">Submit a manuscript</a>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($manuscripts, 0, 8) as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 78)) ?></span><span class="muted"><?= e($manuscript['subject'] ?? '—') ?> · <?= e($manuscript['article_type'] ?? '—') ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><span class="muted"><?= e(formatDate($manuscript['submitted_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('author/manuscript.php?id=' . (int) $manuscript['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <div class="split-panel">
            <section class="portal-card">
                <div class="portal-card-head"><h2>Notifications</h2><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('notifications/index.php')) ?>">Open inbox</a></div>
                <div class="portal-card-body">
                    <?php if (!$notifications): ?>
                        <div class="empty-state" style="padding:24px 8px">
                            <ion-icon name="notifications-outline" aria-hidden="true"></ion-icon>
                            <p>No notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <ul class="notif-list">
                            <?php foreach ($notifications as $notification): ?>
                                <li class="notif-item<?= $notification['is_read'] ? '' : ' is-unread' ?>">
                                    <ion-icon name="information-circle-outline" aria-hidden="true"></ion-icon>
                                    <div>
                                        <strong><?= e($notification['title']) ?></strong>
                                        <?php if ($notification['message']): ?><p><?= e(truncateText($notification['message'], 140)) ?></p><?php endif; ?>
                                        <time><?= e(formatDateTime($notification['created_at'])) ?></time>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-card-head"><h2>Submission checklist</h2></div>
                <div class="portal-card-body">
                    <ul class="timeline">
                        <li><span class="timeline-dot"><ion-icon name="create-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Prepare your manuscript</strong><span>Title, abstract, keywords, and co-author details.</span></div></li>
                        <li><span class="timeline-dot"><ion-icon name="document-attach-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Upload the manuscript file</strong><span>PDF or Word document, maximum <?= e((string) setting('max_file_size_mb', 10)) ?> MB.</span></div></li>
                        <li><span class="timeline-dot"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>Track the review</strong><span>You are notified at every stage of the editorial process.</span></div></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
