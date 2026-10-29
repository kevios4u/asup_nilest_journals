<?php

/**
 * Contact enquiries from the public website.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('admin/messages.php');
    }

    $messageId = (int) ($_POST['message_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'read');
    if (!in_array($status, ['new', 'read', 'closed'], true)) {
        $status = 'read';
    }
    $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $messageId]);
    audit('message.status', 'contact_message', $messageId, 'Status set to ' . $status);
    flash('success', 'The enquiry has been updated.');
    redirect('admin/messages.php');
}

$filter = (string) ($_GET['status'] ?? '');
$where = '1 = 1';
$params = [];
if (in_array($filter, ['new', 'read', 'closed'], true)) {
    $where = 'status = ?';
    $params[] = $filter;
}

$statement = $pdo->prepare("SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT 100");
$statement->execute($params);
$messages = $statement->fetchAll();

$metaTitle = 'Enquiries';
$pageTitle = 'Enquiries';
$pageDescription = 'Messages sent through the public contact form.';
$activePage = 'admin-messages';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'messages'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Enquiries', count($messages) . ' message(s) from the contact form.'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div class="filter-bar">
                    <label for="status" class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700">Status</label>
                    <select id="status" onchange="window.location.href='<?= e(url('admin/messages.php')) ?>?status=' + encodeURIComponent(this.value)">
                        <option value="">All</option>
                        <?php foreach (['new' => 'New', 'read' => 'Read', 'closed' => 'Closed'] as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $filter === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!$messages): ?>
                <div class="empty-state">
                    <ion-icon name="chatbubble-ellipses-outline" aria-hidden="true"></ion-icon>
                    <h2>No enquiries</h2>
                    <p>Messages sent through the contact form appear here.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><span class="title-cell"><?= e($message['name']) ?></span><span class="muted"><?= e($message['email']) ?></span></td>
                                <td><span class="muted"><?= e(truncateText($message['subject'], 40)) ?></span></td>
                                <td><span class="muted"><?= e(truncateText($message['message'], 90)) ?></span></td>
                                <td><span class="muted"><?= e(formatDateTime($message['created_at'])) ?></span></td>
                                <td><span class="badge badge--<?= $message['status'] === 'new' ? 'warn' : ($message['status'] === 'closed' ? 'good' : 'info') ?>"><?= e(ucfirst((string) $message['status'])) ?></span></td>
                                <td>
                                    <form method="post" action="<?= e(url('admin/messages.php')) ?>">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="message_id" value="<?= e((string) $message['id']) ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--portal-border);border-radius:8px;font:inherit;font-size:13px">
                                            <?php foreach (['new' => 'New', 'read' => 'Read', 'closed' => 'Closed'] as $key => $label): ?>
                                                <option value="<?= e($key) ?>"<?= $message['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
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
