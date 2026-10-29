<?php

/**
 * Registration: creates an author, editor or reviewer account.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

if (isLoggedIn()) {
    redirect(roleHome(currentUser()['role']));
}

$errors = [];
$values = [
    'name' => '',
    'email' => '',
    'role' => 'author',
    'affiliation' => '',
    'country' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } else {
        $values['name'] = trim((string) ($_POST['name'] ?? ''));
        $values['email'] = trim((string) ($_POST['email'] ?? ''));
        $values['role'] = (string) ($_POST['role'] ?? 'author');
        $values['affiliation'] = trim((string) ($_POST['affiliation'] ?? ''));
        $values['country'] = trim((string) ($_POST['country'] ?? ''));

        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($password !== $confirmation) {
            $errors[] = 'The two passwords do not match.';
        }

        $country = resolveCountry($values['country']);
        if ($country === null) {
            $errors[] = 'Please choose a country from the list.';
        } else {
            $values['country'] = $country;
        }

        if (!$errors) {
            [$success, $message, $userId] = registerUser(
                $values['name'],
                $values['email'],
                $password,
                $values['role']
            );

            if ($success) {
                $pdo->prepare(
                    'UPDATE author_profiles SET affiliation = ?, country = ? WHERE user_id = ?'
                )->execute([
                    $values['affiliation'] !== '' ? $values['affiliation'] : null,
                    $values['country'] !== '' ? $values['country'] : null,
                    $userId,
                ]);

                flash('success', $message);
                loginUser($userId);
                audit('auth.register', 'user', $userId, 'Self-registered ' . $values['role'] . ' account');
                redirect(roleHome($values['role']));
            }

            $errors[] = $message;
        }
    }
}

$metaTitle = 'Create an account';
$pageTitle = 'Create an account';
$pageDescription = 'Register for the NIJOSTAM journal management portal to submit and track manuscripts.';
$activePage = 'register';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<section class="auth-shell auth-shell--register">
    <div class="auth-banner">
        <div class="auth-banner-copy">
            <h2>One account for the whole editorial cycle</h2>
            <p>Manuscript files are stored outside the public web directory and are only released to the editorial team and assigned reviewers.</p>
        </div>
        <ul class="auth-banner-list">
            <li><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon><span>Submit manuscripts and receive an instant reference number.</span></li>
            <li><ion-icon name="notifications-outline" aria-hidden="true"></ion-icon><span>Get notified when a decision, revision request, or review report is ready.</span></li>
            <li><ion-icon name="eye-outline" aria-hidden="true"></ion-icon><span>Follow every stage: screening, peer review, revision, and publication.</span></li>
            <li><ion-icon name="shield-checkmark-outline" aria-hidden="true"></ion-icon><span>Your files stay private and are never published as downloads.</span></li>
        </ul>
    </div>

    <div class="auth-card">
        <h1>Create your account</h1>
        <p class="auth-lede">Register once to submit manuscripts, follow peer review, and track every decision in one place.</p>

        <?php if ($errors): ?>
            <div class="alert-stack" style="margin-bottom:20px">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert--danger"><ion-icon name="close-circle-outline" aria-hidden="true"></ion-icon><span><?= e($error) ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="portal-form" method="post" action="<?= e(url('auth/register.php')) ?>" novalidate>
            <?= csrfField() ?>

            <div class="form-field">
                <label for="name">Full name <span class="required-mark">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required autocomplete="name">
            </div>

            <div class="form-field">
                <label for="email">Email address <span class="required-mark">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required autocomplete="email">
                <span class="hint">Use the address that appears on your published papers.</span>
            </div>

            <div class="form-field">
                <label>I am registering as</label>
                <div class="role-picker">
                    <label class="role-option"><input type="radio" name="role" value="author"<?= $values['role'] === 'author' ? ' checked' : '' ?>><span><ion-icon name="create-outline" aria-hidden="true"></ion-icon>Author</span></label>
                    <label class="role-option"><input type="radio" name="role" value="reviewer"<?= $values['role'] === 'reviewer' ? ' checked' : '' ?>><span><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>Reviewer</span></label>
                    <label class="role-option"><input type="radio" name="role" value="editor"<?= $values['role'] === 'editor' ? ' checked' : '' ?>><span><ion-icon name="briefcase-outline" aria-hidden="true"></ion-icon>Editor</span></label>
                </div>
                <span class="hint">Reviewer and editor accounts are activated after verification by the editorial office.</span>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="affiliation">Affiliation / institution</label>
                    <input type="text" id="affiliation" name="affiliation" value="<?= e($values['affiliation']) ?>" autocomplete="organization">
                </div>
                <div class="form-field">
                    <label for="country">Country</label>
                    <select id="country" name="country" autocomplete="country-name">
                        <option value="">Select your country</option>
                        <?php foreach (countries() as $country): ?>
                            <option value="<?= e($country) ?>"<?= $values['country'] === $country ? ' selected' : '' ?>><?= e($country) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="password">Password <span class="required-mark">*</span></label>
                    <div class="input-affix">
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?= e((string) PASSWORD_MIN_LENGTH) ?>">
                        <button class="input-affix-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false"><ion-icon name="eye-outline" aria-hidden="true"></ion-icon></button>
                    </div>
                    <span class="hint">At least <?= e((string) PASSWORD_MIN_LENGTH) ?> characters, with a letter and a number.</span>
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirm password <span class="required-mark">*</span></label>
                    <div class="input-affix">
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        <button class="input-affix-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false"><ion-icon name="eye-outline" aria-hidden="true"></ion-icon></button>
                    </div>
                </div>
            </div>

            <div class="portal-actions">
                <button class="portal-btn" type="submit"><ion-icon name="person-add-outline" aria-hidden="true"></ion-icon>Create account</button>
                <a class="portal-btn portal-btn--ghost" href="<?= e(url('auth/login.php')) ?>">I already have an account</a>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
