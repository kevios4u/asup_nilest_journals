<?php

/**
 * Editor manuscript list with workflow filters.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('editor', 'admin');

$user = currentUser();
$pdo = db();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$filter = (string) ($_GET['status'] ?? '');

$where = '1 = 1';
$params = [];
if ($filter !== '' && array_key_exists($filter, workflow_statuses())) {
    $where = 'm.status = ?';
    $params[] = $filter;
}

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM manuscripts m WHERE {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT m.*, u.name AS submitter_name,
            (SELECT COUNT(*) FROM reviewer_assignments ra WHERE ra.manuscript_id = m.id) AS reviewer_count,
            (SELECT COUNT(*) FROM reviews r WHERE r.manuscript_id = m.id) AS review_count
     FROM manuscripts m
     INNER JOIN users u ON u.id = m.submitted_by
     WHERE {$where}
     ORDER BY m.submitted_at DESC, m.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$statement->execute($params);
$manuscripts = $statement->fetchAll();

$metaTitle = 'Manuscripts';
$pageTitle = 'Manuscripts';
$pageDescription = 'Every manuscript handled by the NIJOSTAM editorial office.';
$activePage = 'editor-manuscripts';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'manuscripts'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Manuscripts', $total . ' manuscript(s) in the system.'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <label for="status" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Status</label>
                    <select id="status" onchange="window.location.href='<?= e(url('editor/manuscripts.php')) ?>?status=' + encodeURIComponent(this.value)">
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
                        <thead><tr><th>Reference</th><th>Title</th><th>Author</th><th>Status</th><th>Reviewers</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($manuscripts as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 60)) ?></span><span class="muted"><?= e($manuscript['article_type'] ?? '—') ?></span></td>
                                <td><span class="muted"><?= e($manuscript['submitter_name']) ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><span class="muted"><?= e((string) $manuscript['reviewer_count']) ?> · <?= e((string) $manuscript['review_count']) ?> reports</span></td>
                                <td><span class="muted"><?= e(formatDate($manuscript['submitted_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('editor/manuscript.php?id=' . (int) $manuscript['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $pages, url('editor/manuscripts.php') . '?status=' . urlencode($filter)); ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
