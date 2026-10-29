<?php

/**
 * All review assignments for the signed-in reviewer.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('reviewer', 'admin');

$user = currentUser();
$pdo = db();
$reviewerId = (int) $user['id'];

$filter = (string) ($_GET['status'] ?? '');

$where = 'ra.reviewer_id = ?';
$params = [$reviewerId];
if (in_array($filter, ['invited', 'accepted', 'completed', 'declined'], true)) {
    $where .= ' AND ra.status = ?';
    $params[] = $filter;
}

$statement = $pdo->prepare(
    "SELECT ra.*, m.reference_no, m.title, m.subject, e.name AS editor_name
     FROM reviewer_assignments ra
     INNER JOIN manuscripts m ON m.id = ra.manuscript_id
     LEFT JOIN users e ON e.id = ra.editor_id
     WHERE {$where}
     ORDER BY FIELD(ra.status, 'invited', 'accepted', 'completed', 'declined'), ra.due_at"
);
$statement->execute($params);
$assignments = $statement->fetchAll();

$metaTitle = 'My reviews';
$pageTitle = 'My reviews';
$pageDescription = 'Peer review assignments handled by you.';
$activePage = 'reviewer-reviews';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'reviews'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('My reviews', count($assignments) . ' assignment(s) on record.'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <label for="status" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Status</label>
                    <select id="status" onchange="window.location.href='<?= e(url('reviewer/reviews.php')) ?>?status=' + encodeURIComponent(this.value)">
                        <option value="">All</option>
                        <?php foreach (['invited' => 'Awaiting response', 'accepted' => 'Accepted', 'completed' => 'Completed', 'declined' => 'Declined'] as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $filter === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!$assignments): ?>
                <div class="empty-state">
                    <ion-icon name="clipboard-outline" aria-hidden="true"></ion-icon>
                    <h2>Nothing to show</h2>
                    <p>No review assignments match this filter.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Editor</th><th>Status</th><th>Recommendation</th><th>Due</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><span class="ref"><?= e($assignment['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($assignment['title'], 58)) ?></span></td>
                                <td><span class="muted"><?= e($assignment['editor_name'] ?? '—') ?></span></td>
                                <td><span class="badge badge--<?= $assignment['status'] === 'completed' ? 'good' : ($assignment['status'] === 'declined' ? 'bad' : ($assignment['status'] === 'accepted' ? 'info' : 'warn')) ?>"><?= e(ucfirst((string) $assignment['status'])) ?></span></td>
                                <td><span class="muted"><?= e($assignment['recommendation'] ? ucfirst(str_replace('_', ' ', (string) $assignment['recommendation'])) : '—') ?></span></td>
                                <td><span class="muted"><?= e(formatDate($assignment['due_at'])) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('reviewer/review.php?id=' . (int) $assignment['id'])) ?>">Open</a></td>
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
