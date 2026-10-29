<?php

/**
 * Announcement management.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('admin/announcements.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));

        if (mb_strlen($title) < 5) {
            flash('danger', 'The announcement title must be at least 5 characters long.');
            redirect('admin/announcements.php');
        }

        $pdo->prepare(
            'INSERT INTO announcements (title, published_at, summary, url, is_active) VALUES (?, ?, ?, ?, 1)'
        )->execute([
            $title,
            $publishedAt !== '' ? $publishedAt : date('Y-m-d'),
            $summary !== '' ? $summary : null,
            $url !== '' ? $url : null,
        ]);

        audit('announcement.create', 'announcement', (int) $pdo->lastInsertId(), 'Announcement published: ' . $title);
        flash('success', 'The announcement has been published.');
        redirect('admin/announcements.php');
    }

    if ($action === 'toggle') {
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);
        $pdo->prepare('UPDATE announcements SET is_active = 1 - is_active WHERE id = ?')->execute([$announcementId]);
        audit('announcement.toggle', 'announcement', $announcementId, 'Announcement visibility changed');
        flash('success', 'The announcement visibility has been updated.');
        redirect('admin/announcements.php');
    }

    if ($action === 'delete') {
        $announcementId = (int) ($_POST['announcement_id'] ?? 0);
        $pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$announcementId]);
        audit('announcement.delete', 'announcement', $announcementId, 'Announcement deleted');
        flash('success', 'The announcement has been deleted.');
        redirect('admin/announcements.php');
    }

    redirect('admin/announcements.php');
}

$announcements = $pdo->query('SELECT * FROM announcements ORDER BY published_at DESC, id DESC')->fetchAll();

$metaTitle = 'Announcements';
$pageTitle = 'Announcements';
$pageDescription = 'Publish and manage journal announcements.';
$activePage = 'admin-announcements';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'announcements'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Announcements', 'Notices shown on the homepage banner and announcements page.'); ?>

        <section class="portal-card">
            <div class="portal-card-head"><div><h2>Publish an announcement</h2><p>Announcements appear in the site notice band and on the announcements page.</p></div></div>
            <div class="portal-card-body">
                <form class="portal-form" method="post" action="<?= e(url('admin/announcements.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="form-field">
                        <label for="title">Title <span class="required-mark">*</span></label>
                        <input type="text" id="title" name="title" required maxlength="500">
                    </div>
                    <div class="form-field">
                        <label for="summary">Summary</label>
                        <textarea id="summary" name="summary"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="url">Link</label>
                            <input type="text" id="url" name="url" placeholder="https://">
                        </div>
                        <div class="form-field">
                            <label for="published_at">Publication date</label>
                            <input type="date" id="published_at" name="published_at" value="<?= e(date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="portal-actions">
                        <button class="portal-btn" type="submit"><ion-icon name="megaphone-outline" aria-hidden="true"></ion-icon>Publish announcement</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-card-head"><div><h2>Published announcements</h2><p><?= e((string) count($announcements)) ?> on record.</p></div></div>
            <?php if (!$announcements): ?>
                <div class="empty-state">
                    <ion-icon name="megaphone-outline" aria-hidden="true"></ion-icon>
                    <h2>No announcements</h2>
                    <p>Publish a notice to inform readers and authors.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Title</th><th>Published</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td>
                                    <span class="title-cell"><?= e(truncateText($announcement['title'], 70)) ?></span>
                                    <?php if ($announcement['summary']): ?><span class="muted"><?= e(truncateText($announcement['summary'], 80)) ?></span><?php endif; ?>
                                </td>
                                <td><span class="muted"><?= e(formatDate($announcement['published_at'])) ?></span></td>
                                <td><span class="badge badge--<?= (int) $announcement['is_active'] === 1 ? 'good' : 'bad' ?>"><?= (int) $announcement['is_active'] === 1 ? 'Visible' : 'Hidden' ?></span></td>
                                <td>
                                    <div class="portal-actions">
                                        <form method="post" action="<?= e(url('admin/announcements.php')) ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="announcement_id" value="<?= e((string) $announcement['id']) ?>">
                                            <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit"><?= (int) $announcement['is_active'] === 1 ? 'Hide' : 'Show' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(url('admin/announcements.php')) ?>" onsubmit="return confirm('Delete this announcement?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="announcement_id" value="<?= e((string) $announcement['id']) ?>">
                                            <button class="portal-btn portal-btn--danger portal-btn--sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
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
