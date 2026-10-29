<?php

/**
 * Administrator dashboard.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

$counts = [
    'users' => 0,
    'authors' => 0,
    'editors' => 0,
    'reviewers' => 0,
    'manuscripts' => 0,
    'open' => 0,
    'published' => 0,
    'issues' => 0,
    'messages' => 0,
];

if ($pdo instanceof PDO) {
    $counts['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $counts['authors'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'author'")->fetchColumn();
    $counts['editors'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'editor'")->fetchColumn();
    $counts['reviewers'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'reviewer'")->fetchColumn();
    $counts['manuscripts'] = (int) $pdo->query('SELECT COUNT(*) FROM manuscripts')->fetchColumn();
    $counts['open'] = (int) $pdo->query("SELECT COUNT(*) FROM manuscripts WHERE status NOT IN ('published','rejected')")->fetchColumn();
    $counts['published'] = (int) $pdo->query('SELECT COUNT(*) FROM published_articles')->fetchColumn();
    $counts['issues'] = (int) $pdo->query('SELECT COUNT(*) FROM journal_issues')->fetchColumn();
    $counts['messages'] = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
}

$recentActivity = [];
$recentUsers = [];
if ($pdo instanceof PDO) {
    $recentActivity = $pdo->query(
        'SELECT a.*, u.name AS user_name FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.created_at DESC, a.id DESC LIMIT 8'
    )->fetchAll();

    $recentUsers = $pdo->query(
        'SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 6'
    )->fetchAll();
}

$metaTitle = 'Administrator dashboard';
$pageTitle = 'Administrator dashboard';
$pageDescription = 'System overview for the NIJOSTAM journal management platform.';
$activePage = 'admin-dashboard';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'dashboard'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('System dashboard', 'Users, submissions, publication, and platform activity.', '<a class="portal-btn" href="' . e(url('admin/users.php')) . '"><ion-icon name="people-outline" aria-hidden="true"></ion-icon>Manage users</a>'); ?>

        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon"><ion-icon name="people-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['users']) ?></strong><span>Registered users</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="documents-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['manuscripts']) ?></strong><span>Manuscripts in system</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="hourglass-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['open']) ?></strong><span>Open submissions</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['published']) ?></strong><span>Published articles</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="albums-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['issues']) ?></strong><span>Journal issues</span></div></div>
            <div class="stat-card"><span class="stat-icon"><ion-icon name="chatbubbles-outline" aria-hidden="true"></ion-icon></span><div><strong><?= e((string) $counts['messages']) ?></strong><span>New enquiries</span></div></div>
        </div>

        <div class="split-panel">
            <section class="portal-card">
                <div class="portal-card-head">
                    <div><h2>Recent activity</h2><p>Audit trail of the last actions.</p></div>
                    <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('admin/audit.php')) ?>">Full log</a>
                </div>
                <?php if (!$recentActivity): ?>
                    <div class="empty-state" style="padding:28px 8px"><ion-icon name="reader-outline" aria-hidden="true"></ion-icon><p>No activity recorded yet.</p></div>
                <?php else: ?>
                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead><tr><th>Action</th><th>User</th><th>Description</th><th>When</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentActivity as $entry): ?>
                                <tr>
                                    <td><span class="ref"><?= e($entry['action']) ?></span></td>
                                    <td><span class="muted"><?= e($entry['user_name'] ?? 'System') ?></span></td>
                                    <td><span class="muted"><?= e(truncateText($entry['description'], 64)) ?></span></td>
                                    <td><span class="muted"><?= e(formatDateTime($entry['created_at'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="portal-card">
                <div class="portal-card-head">
                    <div><h2>Newest accounts</h2><p>Recently registered users.</p></div>
                    <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('admin/users.php')) ?>">All users</a>
                </div>
                <?php if (!$recentUsers): ?>
                    <div class="empty-state" style="padding:28px 8px"><ion-icon name="people-outline" aria-hidden="true"></ion-icon><p>No accounts yet.</p></div>
                <?php else: ?>
                    <div class="portal-table-wrap">
                        <table class="portal-table">
                            <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentUsers as $account): ?>
                                <tr>
                                    <td><span class="title-cell"><?= e($account['name']) ?></span><span class="muted"><?= e($account['email']) ?></span></td>
                                    <td><span class="badge badge--info"><?= e(role_labels()[$account['role']] ?? $account['role']) ?></span></td>
                                    <td><span class="badge badge--<?= $account['status'] === 'active' ? 'good' : 'bad' ?>"><?= e(ucfirst((string) $account['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="portal-card">
            <div class="portal-card-head"><h2>Quick actions</h2></div>
            <div class="portal-card-body">
                <div class="portal-actions">
                    <a class="portal-btn portal-btn--ghost" href="<?= e(url('admin/manuscripts.php')) ?>"><ion-icon name="documents-outline" aria-hidden="true"></ion-icon>All manuscripts</a>
                    <a class="portal-btn portal-btn--ghost" href="<?= e(url('publication/dashboard.php')) ?>"><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon>Publication</a>
                    <a class="portal-btn portal-btn--ghost" href="<?= e(url('admin/announcements.php')) ?>"><ion-icon name="megaphone-outline" aria-hidden="true"></ion-icon>Announcements</a>
                    <a class="portal-btn portal-btn--ghost" href="<?= e(url('admin/messages.php')) ?>"><ion-icon name="chatbubble-ellipses-outline" aria-hidden="true"></ion-icon>Enquiries</a>
                    <a class="portal-btn portal-btn--ghost" href="<?= e(url('admin/settings.php')) ?>"><ion-icon name="settings-outline" aria-hidden="true"></ion-icon>Settings</a>
                </div>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
