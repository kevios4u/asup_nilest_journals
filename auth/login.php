<?php

/**
 * Sign in for all portal roles.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

if (isLoggedIn()) {
    redirect(roleHome(currentUser()['role']));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please sign in again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        [$success, $message, $user] = attemptLogin($email, (string) ($_POST['password'] ?? ''));

        if ($success) {
            loginUser((int) $user['id']);
            audit('auth.login', 'user', (int) $user['id'], 'User signed in');
            flash('success', 'Welcome back, ' . $user['name'] . '.');
            $intended = $_SESSION['_intended'] ?? '';
            unset($_SESSION['_intended']);
            redirect($intended !== '' ? $intended : roleHome($user['role']));
        }

        $error = $message;
    }
}

$metaTitle = 'Sign in';
$pageTitle = 'Sign in';
$pageDescription = 'Sign in to the NIJOSTAM journal management portal.';
$activePage = 'login';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<section class="auth-shell">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="auth-lede">Sign in to manage your manuscripts, reviews, and editorial tasks.</p>

        <?php if ($error !== ''): ?>
            <div class="alert-stack" style="margin-bottom:20px">
                <div class="alert alert--danger"><ion-icon name="close-circle-outline" aria-hidden="true"></ion-icon><span><?= e($error) ?></span></div>
            </div>
        <?php endif; ?>

        <form class="portal-form" method="post" action="<?= e(url('auth/login.php')) ?>" novalidate>
            <?= csrfField() ?>

            <div class="form-field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email" autofocus>
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <div class="portal-actions">
                <button class="portal-btn" type="submit"><ion-icon name="log-in-outline" aria-hidden="true"></ion-icon>Sign in</button>
                <a class="portal-btn portal-btn--ghost" href="<?= e(url('auth/register.php')) ?>">Create an account</a>
            </div>
        </form>
    </div>

    <aside class="auth-aside">
        <h2>Journal management, end to end</h2>
        <ul>
            <li><ion-icon name="create-outline" aria-hidden="true"></ion-icon><span>Authors submit manuscripts and upload revision files.</span></li>
            <li><ion-icon name="briefcase-outline" aria-hidden="true"></ion-icon><span>Editors screen submissions, assign reviewers, and record decisions.</span></li>
            <li><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon><span>Reviewers accept invitations and submit structured reports.</span></li>
            <li><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon><span>Administrators publish issues, manage users, and audit every action.</span></li>
        </ul>
        <p>Accounts are role based. If a reviewer or editor account needs activation, write to the editorial office and quote your email address.</p>
    </aside>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
