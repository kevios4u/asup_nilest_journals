<?php

/**
 * All manuscripts submitted by the signed-in author.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('author', 'admin');

$user = currentUser();
$pdo = db();
$userId = (int) $user['id'];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$filter = (string) ($_GET['status'] ?? '');

$where = 'm.submitted_by = ?';
$params = [$userId];
if ($filter !== '' && array_key_exists($filter, workflow_statuses())) {
    $where .= ' AND m.status = ?';
    $params[] = $filter;
}

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM manuscripts m WHERE {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT m.*, (SELECT COUNT(*) FROM editorial_decisions d WHERE d.manuscript_id = m.id) AS decision_count
     FROM manuscripts m WHERE {$where} ORDER BY m.submitted_at DESC, m.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$statement->execute($params);
$manuscripts = $statement->fetchAll();

$metaTitle = 'My manuscripts';
$pageTitle = 'My manuscripts';
$pageDescription = 'Every manuscript you have submitted to NIJOSTAM.';
$activePage = 'author-manuscripts';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'manuscripts'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            'My manuscripts',
            $total . ' manuscript(s) on record.',
            '<a class="portal-btn" href="' . e(url('author/submit.php')) . '"><ion-icon name="cloud-upload-outline" aria-hidden="true"></ion-icon>Submit manuscript</a>'
        );
        ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <label for="status" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Filter</label>
                    <select id="status" onchange="window.location.href='<?= e(url('author/manuscripts.php')) ?>?status=' + encodeURIComponent(this.value)">
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
                    <h2>Nothing here yet</h2>
                    <p>No manuscripts match this filter.</p>
                    <a class="portal-btn" href="<?= e(url('author/submit.php')) ?>">Submit a manuscript</a>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Type</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($manuscripts as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 70)) ?></span></td>
                                <td><span class="muted"><?= e($manuscript['article_type'] ?? '—') ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><span class="muted"><?= e(formatDate($manuscript['submitted_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('author/manuscript.php?id=' . (int) $manuscript['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $pages, url('author/manuscripts.php') . '?status=' . urlencode($filter)); ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
