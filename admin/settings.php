<?php

/**
 * Journal settings, including the social media channels and workflow options.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin');

$user = currentUser();
$pdo = db();

$groups = ['journal', 'fees', 'workflow', 'uploads', 'social'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('admin/settings.php');
    }

    $saved = 0;
    foreach ($groups as $group) {
        $fields = $_POST['settings'][$group] ?? [];
        if (!is_array($fields)) {
            continue;
        }
        foreach ($fields as $key => $value) {
            $value = is_string($value) ? trim($value) : $value;
            if ($value === '' ) {
                $value = null;
            }
            $statement = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)'
            );
            $statement->execute([(string) $key, $value, $group]);
            $saved++;
        }
    }

    audit('settings.update', 'settings', null, $saved . ' setting(s) updated');
    flash('success', $saved . ' setting(s) saved.');
    redirect('admin/settings.php');
}

$settings = [];
foreach ($pdo->query('SELECT * FROM settings ORDER BY setting_group, setting_key')->fetchAll() as $row) {
    $settings[$row['setting_group']][$row['setting_key']] = $row;
}

$groupTitles = [
    'journal' => 'Journal details',
    'fees' => 'Article processing charges',
    'workflow' => 'Editorial workflow',
    'uploads' => 'Manuscript uploads',
    'social' => 'Social media channels',
];

$metaTitle = 'Settings';
$pageTitle = 'Settings';
$pageDescription = 'Configure journal details, workflow, fees, uploads, and social channels.';
$activePage = 'admin-settings';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'settings'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Settings', 'Values saved here are used across the website and the editorial workflow.'); ?>

        <form class="portal-form" method="post" action="<?= e(url('admin/settings.php')) ?>">
            <?= csrfField() ?>

            <?php foreach ($groups as $group): ?>
                <?php if (empty($settings[$group])) { continue; } ?>
                <section class="portal-card">
                    <div class="portal-card-head">
                        <div><h2><?= e($groupTitles[$group] ?? ucfirst($group)) ?></h2><p><?= e((string) count($settings[$group])) ?> setting(s).</p></div>
                    </div>
                    <div class="portal-card-body portal-form">
                        <?php foreach ($settings[$group] as $key => $row): ?>
                            <div class="form-field">
                                <label for="setting_<?= e($key) ?>"><?= e($row['label'] ?? $key) ?> <span class="hint"><?= e($key) ?></span></label>
                                <?php if ($group === 'workflow' && $key === 'submission_open'): ?>
                                    <select id="setting_<?= e($key) ?>" name="settings[<?= e($group) ?>][<?= e($key) ?>]">
                                        <option value="1"<?= $row['setting_value'] === '1' ? ' selected' : '' ?>>Accepting submissions</option>
                                        <option value="0"<?= $row['setting_value'] !== '1' ? ' selected' : '' ?>>Not accepting submissions</option>
                                    </select>
                                <?php else: ?>
                                    <input type="text" id="setting_<?= e($key) ?>" name="settings[<?= e($group) ?>][<?= e($key) ?>]" value="<?= e((string) ($row['setting_value'] ?? '')) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="portal-actions">
                <button class="portal-btn" type="submit"><ion-icon name="save-outline" aria-hidden="true"></ion-icon>Save settings</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
