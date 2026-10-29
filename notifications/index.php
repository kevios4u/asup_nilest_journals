<?php

/**
 * Notification inbox for every signed-in role.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireLogin();

$user = currentUser();
$pdo = db();
$userId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('notifications/index.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
        audit('notification.read_all', 'user', $userId, 'All notifications marked as read');
        flash('success', 'All notifications marked as read.');
        redirect('notifications/index.php');
    }

    if ($action === 'read') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
        redirect('notifications/index.php');
    }

    if ($action === 'delete') {
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
        flash('success', 'Notification removed.');
        redirect('notifications/index.php');
    }

    redirect('notifications/index.php');
}

$filter = (string) ($_GET['filter'] ?? '');
$where = 'user_id = ?';
$params = [$userId];
if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
}

$statement = $pdo->prepare("SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT 100");
$statement->execute($params);
$notifications = $statement->fetchAll();

$unread = unreadNotificationCount($userId);

$metaTitle = 'Notifications';
$pageTitle = 'Notifications';
$pageDescription = 'Messages from the NIJOSTAM editorial office.';
$activePage = 'notifications';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'notifications'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            'Notifications',
            $unread > 0 ? $unread . ' unread message(s).' : 'You are all caught up.',
            $unread > 0 ? '<form method="post" action="' . e(url('notifications/index.php')) . '">' . csrfField() . '<input type="hidden" name="action" value="read_all"><button class="portal-btn portal-btn--ghost" type="submit"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>Mark all as read</button></form>' : null
        );
        ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <a class="portal-btn portal-btn--sm portal-btn--<?= $filter === '' ? 'accent' : 'ghost' ?>" href="<?= e(url('notifications/index.php')) ?>">All</a>
                    <a class="portal-btn portal-btn--sm portal-btn--<?= $filter === 'unread' ? 'accent' : 'ghost' ?>" href="<?= e(url('notifications/index.php?filter=unread')) ?>">Unread (<?= e((string) $unread) ?>)</a>
                </div>
            </div>
            <div class="portal-card-body">
                <?php if (!$notifications): ?>
                    <div class="empty-state">
                        <ion-icon name="notifications-outline" aria-hidden="true"></ion-icon>
                        <h2>No notifications</h2>
                        <p>Editorial updates appear here as your manuscripts progress.</p>
                    </div>
                <?php else: ?>
                    <ul class="notif-list">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="notif-item<?= (int) $notification['is_read'] === 0 ? ' is-unread' : '' ?>">
                                <ion-icon name="<?= e(notificationIcon((string) $notification['type'])) ?>" aria-hidden="true"></ion-icon>
                                <div style="flex:1;min-width:0">
                                    <strong><?= e($notification['title']) ?></strong>
                                    <?php if ($notification['message']): ?><p><?= e($notification['message']) ?></p><?php endif; ?>
                                    <time><?= e(formatDateTime($notification['created_at'])) ?></time>
                                    <?php if (!empty($notification['link'])): ?>
                                        <div class="portal-actions" style="margin-top:9px">
                                            <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url((string) $notification['link'])) ?>">Open</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="portal-actions">
                                    <?php if ((int) $notification['is_read'] === 0): ?>
                                        <form method="post" action="<?= e(url('notifications/index.php')) ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="read">
                                            <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">
                                            <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit">Mark read</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('notifications/index.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="notification_id" value="<?= e((string) $notification['id']) ?>">
                                        <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit" aria-label="Delete notification"><ion-icon name="close-outline" aria-hidden="true"></ion-icon></button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
