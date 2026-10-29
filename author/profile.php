<?php

/**
 * Author profile.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('author', 'admin');

$user = currentUser();
$pdo = db();
$userId = (int) $user['id'];

$profile = [
    'affiliation' => '',
    'department' => '',
    'institution' => '',
    'country' => '',
    'phone' => '',
    'bio' => '',
    'is_corresponding' => 1,
];

$statement = $pdo->prepare('SELECT * FROM author_profiles WHERE user_id = ? LIMIT 1');
$statement->execute([$userId]);
$existing = $statement->fetch();
if ($existing) {
    $profile = array_merge($profile, $existing);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } else {
        $profile['affiliation'] = trim((string) ($_POST['affiliation'] ?? ''));
        $profile['department'] = trim((string) ($_POST['department'] ?? ''));
        $profile['institution'] = trim((string) ($_POST['institution'] ?? ''));
        $profile['country'] = trim((string) ($_POST['country'] ?? ''));
        $profile['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $profile['bio'] = trim((string) ($_POST['bio'] ?? ''));
        $profile['is_corresponding'] = isset($_POST['is_corresponding']) ? 1 : 0;

        $name = trim((string) ($_POST['name'] ?? ''));
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter your full name.';
        }

        if (!$errors) {
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $userId]);
            $pdo->prepare(
                'INSERT INTO author_profiles (user_id, affiliation, department, institution, country, phone, bio, is_corresponding)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE affiliation = VALUES(affiliation), department = VALUES(department), institution = VALUES(institution),
                 country = VALUES(country), phone = VALUES(phone), bio = VALUES(bio), is_corresponding = VALUES(is_corresponding)'
            )->execute([
                $userId,
                $profile['affiliation'] !== '' ? $profile['affiliation'] : null,
                $profile['department'] !== '' ? $profile['department'] : null,
                $profile['institution'] !== '' ? $profile['institution'] : null,
                $profile['country'] !== '' ? $profile['country'] : null,
                $profile['phone'] !== '' ? $profile['phone'] : null,
                $profile['bio'] !== '' ? $profile['bio'] : null,
                $profile['is_corresponding'],
            ]);

            audit('profile.update', 'user', $userId, 'Author profile updated');
            flash('success', 'Your profile has been updated.');
            redirect('author/profile.php');
        }
    }
}

$metaTitle = 'My profile';
$pageTitle = 'My profile';
$pageDescription = 'Keep your contact and affiliation details up to date.';
$activePage = 'author-profile';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'profile'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('My profile', 'These details are used on the article page and in editorial correspondence.'); ?>

        <?php if ($errors): ?>
            <div class="alert-stack">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert--danger"><ion-icon name="close-circle-outline" aria-hidden="true"></ion-icon><span><?= e($error) ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="portal-form" method="post" action="<?= e(url('author/profile.php')) ?>" novalidate>
            <?= csrfField() ?>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Account</h2><p>Your name and sign-in address.</p></div></div>
                <div class="portal-card-body portal-form">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">Full name</label>
                            <input type="text" id="name" name="name" value="<?= e((string) ($_POST['name'] ?? $user['name'])) ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="email">Email address</label>
                            <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                            <span class="hint">Contact the editorial office to change your sign-in address.</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Affiliation</h2><p>Where you are based and where you work.</p></div></div>
                <div class="portal-card-body portal-form">
                    <div class="form-field">
                        <label for="institution">Institution</label>
                        <input type="text" id="institution" name="institution" value="<?= e((string) $profile['institution']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" value="<?= e((string) $profile['department']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="affiliation">Affiliation</label>
                            <input type="text" id="affiliation" name="affiliation" value="<?= e((string) $profile['affiliation']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?= e((string) $profile['country']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?= e((string) $profile['phone']) ?>">
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="bio">Short biography <span class="hint">optional</span></label>
                        <textarea id="bio" name="bio"><?= e((string) $profile['bio']) ?></textarea>
                    </div>
                    <div class="form-field">
                        <label style="flex-direction:row;align-items:center;gap:9px;display:flex">
                            <input type="checkbox" name="is_corresponding" value="1" style="width:auto"<?= $profile['is_corresponding'] ? ' checked' : '' ?>>
                            <span>I am the corresponding author for this account</span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="portal-actions">
                <button class="portal-btn" type="submit"><ion-icon name="save-outline" aria-hidden="true"></ion-icon>Save profile</button>
                <a class="portal-btn portal-btn--ghost" href="<?= e(url('author/dashboard.php')) ?>">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
