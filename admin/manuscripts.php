<?php

/**
 * Administrator manuscript overview across the whole workflow.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

$filter = (string) ($_GET['status'] ?? '');

$where = '1 = 1';
$params = [];
if (array_key_exists($filter, workflow_statuses())) {
    $where = 'm.status = ?';
    $params[] = $filter;
}

$statement = $pdo->prepare(
    "SELECT m.*, u.name AS submitter_name,
            (SELECT COUNT(*) FROM reviewer_assignments ra WHERE ra.manuscript_id = m.id) AS reviewer_count,
            (SELECT COUNT(*) FROM reviews r WHERE r.manuscript_id = m.id) AS review_count,
            (SELECT COUNT(*) FROM published_articles pa WHERE pa.manuscript_id = m.id) AS published
     FROM manuscripts m
     INNER JOIN users u ON u.id = m.submitted_by
     WHERE {$where}
     ORDER BY m.submitted_at DESC, m.id DESC
     LIMIT 100"
);
$statement->execute($params);
$manuscripts = $statement->fetchAll();

$metaTitle = 'All manuscripts';
$pageTitle = 'All manuscripts';
$pageDescription = 'Every manuscript in the NIJOSTAM system.';
$activePage = 'admin-manuscripts';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'manuscripts'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('All manuscripts', count($manuscripts) . ' manuscript(s) listed.', '<a class="portal-btn" href="' . e(url('publication/dashboard.php')) . '"><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon>Publication</a>'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <label for="status" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Status</label>
                    <select id="status" onchange="window.location.href='<?= e(url('admin/manuscripts.php')) ?>?status=' + encodeURIComponent(this.value)">
                        <option value="">All statuses</option>
                        <?php foreach (workflow_statuses() as $key => $config): ?>
                            <option value="<?= e($key) ?>"<?= $filter === $key ? ' selected' : '' ?>><?= e($config['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!$manuscripts): ?>
                <div class="empty-state">
                    <ion-icon name="documents-outline" aria-hidden="true"></ion-icon>
                    <h2>No manuscripts found</h2>
                    <p>Nothing matches this filter.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Submitted by</th><th>Status</th><th>Review</th><th>Published</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($manuscripts as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 58)) ?></span></td>
                                <td><span class="muted"><?= e($manuscript['submitter_name']) ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><span class="muted"><?= e((string) $manuscript['reviewer_count']) ?> · <?= e((string) $manuscript['review_count']) ?></span></td>
                                <td><?php if ((int) $manuscript['published'] > 0): ?><span class="badge badge--good">Yes</span><?php else: ?><span class="muted">No</span><?php endif; ?></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('editor/manuscript.php?id=' . (int) $manuscript['id'])) ?>">Open</a></td>
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
