<?php

/**
 * Editor dashboard.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('editor', 'admin');

$user = currentUser();
$pdo = db();
$editorId = (int) $user['id'];

$counts = [
    'awaiting_screening' => 0,
    'awaiting_reviewer' => 0,
    'under_review' => 0,
    'awaiting_decision' => 0,
    'in_production' => 0,
];

$queue = [];
$recentDecisions = [];

if ($pdo instanceof PDO) {
    $statusGroups = [
        'awaiting_screening' => ['submitted', 'under_screening'],
        'awaiting_reviewer' => ['awaiting_reviewer'],
        'under_review' => ['under_review', 'revised'],
        'awaiting_decision' => ['revision_requested'],
        'in_production' => ['accepted', 'in_production'],
    ];

    foreach ($statusGroups as $key => $statuses) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $statement = $pdo->prepare("SELECT COUNT(*) FROM manuscripts WHERE status IN ({$placeholders})");
        $statement->execute($statuses);
        $counts[$key] = (int) $statement->fetchColumn();
    }

    $statement = $pdo->prepare(
        'SELECT m.*, (SELECT COUNT(*) FROM reviewer_assignments ra WHERE ra.manuscript_id = m.id) AS reviewer_count,
                (SELECT COUNT(*) FROM reviews r WHERE r.manuscript_id = m.id) AS review_count
         FROM manuscripts m
         WHERE m.status NOT IN (\'published\', \'rejected\')
         ORDER BY FIELD(m.status, \'submitted\', \'under_screening\', \'awaiting_reviewer\', \'under_review\', \'revised\', \'revision_requested\', \'accepted\', \'in_production\'), m.submitted_at ASC
         LIMIT 10'
    );
    $statement->execute();
    $queue = $statement->fetchAll();

    $statement = $pdo->prepare(
        'SELECT d.*, m.reference_no, m.title FROM editorial_decisions d
         INNER JOIN manuscripts m ON m.id = d.manuscript_id
         ORDER BY d.created_at DESC LIMIT 6'
    );
    $statement->execute();
    $recentDecisions = $statement->fetchAll();
}

$metaTitle = 'Editor dashboard';
$pageTitle = 'Editor dashboard';
$pageDescription = 'Screen submissions, assign reviewers, and record editorial decisions.';
$activePage = 'editor-dashboard';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'dashboard'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Editorial dashboard', 'Everything waiting for the editorial team, in one queue.', '<a class="portal-btn" href="' . e(url('editor/manuscripts.php')) . '"><ion-icon name="documents-outline" aria-hidden="true"></ion-icon>All manuscripts</a>'); ?>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon"><ion-icon name="search-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['awaiting_screening']) ?></strong><span>Awaiting screening</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="people-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['awaiting_reviewer']) ?></strong><span>Awaiting reviewers</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="eye-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['under_review']) ?></strong><span>Under peer review</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['awaiting_decision']) ?></strong><span>Revision rounds open</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="construct-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['in_production']) ?></strong><span>In production</span></div></div>
        </div>

        <section class="portal-card">
            <div class="portal-card-head">
                <div><h2>Editorial queue</h2><p>Ordered by workflow stage, then by submission date.</p></div>
            </div>
            <?php if (!$queue): ?>
                <div class="empty-state">
                    <ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>
                    <h2>The queue is clear</h2>
                    <p>No manuscripts are waiting for editorial attention.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Stage</th><th>Reviewers</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($queue as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 68)) ?></span><span class="muted"><?= e($manuscript['subject'] ?? '—') ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><span class="muted"><?= e((string) $manuscript['reviewer_count']) ?> assigned · <?= e((string) $manuscript['review_count']) ?> reports</span></td>
                                <td><span class="muted"><?= e(formatDate($manuscript['submitted_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('editor/manuscript.php?id=' . (int) $manuscript['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="portal-card">
            <div class="portal-card-head"><div><h2>Recent decisions</h2><p>Decisions recorded across the journal.</p></div></div>
            <?php if (!$recentDecisions): ?>
                <div class="empty-state" style="padding:28px 8px"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon><p>No decisions recorded yet.</p></div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Manuscript</th><th>Decision</th><th>Editor</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentDecisions as $decision): ?>
                            <tr>
                                <td><span class="ref"><?= e($decision['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($decision['title'], 60)) ?></span></td>
                                <td><span class="badge badge--<?= e(editorial_decisions()[$decision['decision']]['tone'] ?? 'info') ?>"><?= e(editorial_decisions()[$decision['decision']]['label'] ?? ucfirst($decision['decision'])) ?></span></td>
                                <td><span class="muted"><?= e($decision['editor_name']) ?></span></td>
                                <td><span class="muted"><?= e(formatDate($decision['created_at'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
