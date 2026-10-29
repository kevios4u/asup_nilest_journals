<?php

/**
 * Audit log browser.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 40;
$actionFilter = trim((string) ($_GET['action'] ?? ''));

$where = '1 = 1';
$params = [];
if ($actionFilter !== '') {
    $where = 'a.action LIKE ?';
    $params[] = '%' . $actionFilter . '%';
}

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a WHERE {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT a.*, u.name AS user_name, u.email AS user_email
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     WHERE {$where}
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$statement->execute($params);
$entries = $statement->fetchAll();

$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

$metaTitle = 'Audit log';
$pageTitle = 'Audit log';
$pageDescription = 'Every recorded action in the journal management system.';
$activePage = 'admin-audit';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'audit'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Audit log', $total . ' recorded action(s).'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <form class="filter-bar" method="get" action="<?= e(url('admin/audit.php')) ?>">
                    <label for="action" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Action</label>
                    <select name="action" onchange="this.form.submit()">
                        <option value="">All actions</option>
                        <?php foreach ($actions as $entry): ?>
                            <option value="<?= e($entry) ?>"<?= $actionFilter === $entry ? ' selected' : '' ?>><?= e($entry) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (!$entries): ?>
                <div class="empty-state">
                    <ion-icon name="reader-outline" aria-hidden="true"></ion-icon>
                    <h2>Nothing logged</h2>
                    <p>No audit entries match this filter.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>When</th><th>Action</th><th>User</th><th>Entity</th><th>Description</th><th>IP</th></tr></thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><span class="muted"><?= e(formatDateTime($entry['created_at'])) ?></span></td>
                                <td><span class="ref"><?= e($entry['action']) ?></span></td>
                                <td><span class="muted"><?= e($entry['user_name'] ?? 'System') ?></span></td>
                                <td><span class="muted"><?= e($entry['entity_type'] ?? '—') ?><?= $entry['entity_id'] ? ' #' . e((string) $entry['entity_id']) : '' ?></span></td>
                                <td><span class="muted"><?= e(truncateText($entry['description'], 90)) ?></span></td>
                                <td><span class="muted"><?= e($entry['ip_address'] ?? '—') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $pages, url('admin/audit.php') . '?action=' . urlencode($actionFilter)); ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
