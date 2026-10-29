<?php

/**
 * User administration: list, filter, change role, suspend or reactivate.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();
$adminId = (int) $user['id'];

$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('admin/users.php');
    }

    $targetId = (int) ($_POST['user_id'] ?? 0);
    if ($targetId <= 0 || $targetId === $adminId) {
        flash('warning', 'That account cannot be modified.');
        redirect('admin/users.php');
    }

    $target = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ?');
    $target->execute([$targetId]);
    $targetRow = $target->fetch();
    if (!$targetRow) {
        flash('danger', 'The account was not found.');
        redirect('admin/users.php');
    }

    if ($action === 'set_role') {
        $newRole = (string) ($_POST['role'] ?? '');
        if (!array_key_exists($newRole, role_labels())) {
            flash('danger', 'Unknown role.');
            redirect('admin/users.php');
        }
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
        audit('user.role_change', 'user', $targetId, 'Role changed to ' . $newRole);
        flash('success', $targetRow['name'] . ' is now a ' . role_labels()[$newRole] . '.');
        redirect('admin/users.php?role=' . urlencode($newRole));
    }

    if ($action === 'toggle_status') {
        $statement = $pdo->prepare('SELECT status FROM users WHERE id = ?');
        $statement->execute([$targetId]);
        $current = (string) $statement->fetchColumn();
        $newStatus = $current === 'active' ? 'suspended' : 'active';

        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $targetId]);
        if ($newStatus === 'suspended') {
            $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$targetId]);
        }
        audit('user.status_change', 'user', $targetId, 'Status changed to ' . $newStatus);
        flash('success', $targetRow['name'] . ' has been ' . ($newStatus === 'active' ? 'reactivated' : 'suspended') . '.');
        redirect('admin/users.php?role=' . urlencode((string) ($_POST['return_role'] ?? '')));
    }

    redirect('admin/users.php');
}

$roleFilter = (string) ($_GET['role'] ?? '');
$statusFilter = (string) ($_GET['status'] ?? '');
$search = trim((string) ($_GET['q'] ?? ''));

$where = '1 = 1';
$params = [];
if (array_key_exists($roleFilter, role_labels())) {
    $where .= ' AND u.role = ?';
    $params[] = $roleFilter;
}
if (in_array($statusFilter, ['active', 'suspended'], true)) {
    $where .= ' AND u.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$statement = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login_at,
            (SELECT COUNT(*) FROM manuscripts m WHERE m.submitted_by = u.id) AS manuscript_count
     FROM users u
     WHERE {$where}
     ORDER BY u.created_at DESC, u.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$statement->execute($params);
$users = $statement->fetchAll();

$metaTitle = 'Users';
$pageTitle = 'Users';
$pageDescription = 'Manage journal accounts, roles, and access.';
$activePage = 'admin-users';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'users'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Users', $total . ' account(s) matching the current filter.'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <form class="filter-bar" method="get" action="<?= e(url('admin/users.php')) ?>">
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or email">
                    <select name="role" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        <?php foreach (role_labels() as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $roleFilter === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="active"<?= $statusFilter === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="suspended"<?= $statusFilter === 'suspended' ? ' selected' : '' ?>>Suspended</option>
                    </select>
                    <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit">Apply</button>
                </form>
            </div>

            <?php if (!$users): ?>
                <div class="empty-state">
                    <ion-icon name="people-outline" aria-hidden="true"></ion-icon>
                    <h2>No accounts found</h2>
                    <p>No user matches this filter.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Manuscripts</th><th>Last sign-in</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $account): ?>
                            <tr>
                                <td><span class="title-cell"><?= e($account['name']) ?><?= (int) $account['id'] === $adminId ? ' <span class="badge">You</span>' : '' ?></span><span class="muted"><?= e($account['email']) ?></span></td>
                                <td>
                                    <form method="post" action="<?= e(url('admin/users.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="set_role">
                                        <input type="hidden" name="user_id" value="<?= e((string) $account['id']) ?>">
                                        <select name="role" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--portal-border);border-radius:8px;font:inherit;font-size:13px">
                                            <?php foreach (role_labels() as $key => $label): ?>
                                                <option value="<?= e($key) ?>"<?= $account['role'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><span class="badge badge--<?= $account['status'] === 'active' ? 'good' : 'bad' ?>"><?= e(ucfirst((string) $account['status'])) ?></span></td>
                                <td><span class="muted"><?= e((string) $account['manuscript_count']) ?></span></td>
                                <td><span class="muted"><?= e(formatDate($account['last_login_at'])) ?></span></td>
                                <td>
                                    <?php if ((int) $account['id'] !== $adminId): ?>
                                        <form method="post" action="<?= e(url('admin/users.php')) ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= e((string) $account['id']) ?>">
                                            <input type="hidden" name="return_role" value="<?= e($roleFilter) ?>">
                                            <button class="portal-btn portal-btn--<?= $account['status'] === 'active' ? 'danger' : 'ghost' ?> portal-btn--sm" type="submit"><?= $account['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $pages, url('admin/users.php') . '?role=' . urlencode($roleFilter) . '&status=' . urlencode($statusFilter) . '&q=' . urlencode($search)); ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
