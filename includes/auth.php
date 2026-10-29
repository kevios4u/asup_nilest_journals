<?php

/**
 * Authentication and role authorization.
 */

if (!function_exists('startSecureSession')) {
    function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            session_name(SESSION_NAME);
            @session_start();
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

if (!function_exists('currentUser')) {
    function currentUser(): ?array
    {
        static $cached = false;
        static $user = null;
        static $cachedId = null;

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $cached = true;
            $user = null;
            $cachedId = null;
            return null;
        }

        if ($cached && $cachedId === (int) $userId) {
            return $user;
        }
        $cached = true;
        $cachedId = (int) $userId;

        $pdo = db();
        if (!$pdo instanceof PDO) {
            return null;
        }

        $statement = $pdo->prepare(
            'SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([(int) $userId]);
        $row = $statement->fetch();

        if (!$row || $row['status'] !== 'active') {
            unset($_SESSION['user_id']);
            $user = null;
            $cachedId = null;
            return null;
        }

        $user = $row;
        return $user;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return currentUser() !== null;
    }
}

if (!function_exists('userRole')) {
    function userRole(): ?string
    {
        $user = currentUser();
        return $user['role'] ?? null;
    }
}

if (!function_exists('hasRole')) {
    function hasRole(string ...$roles): bool
    {
        $role = userRole();
        return $role !== null && in_array($role, $roles, true);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (!isLoggedIn()) {
            flash('warning', 'Please sign in to continue.');
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? '';
            redirect('auth/login.php');
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole(string ...$roles): void
    {
        requireLogin();
        if (!hasRole(...$roles)) {
            http_response_code(403);
            renderAccessDenied();
            exit;
        }
    }
}

if (!function_exists('renderAccessDenied')) {
    function renderAccessDenied(): void
    {
        $metaTitle = 'Access denied';
        require APP_ROOT . '/includes/header.php';
        ?>
        <section class="page-section">
            <div class="container">
                <div class="empty-state">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <h1>Access denied</h1>
                    <p>Your account does not have permission to view this page.</p>
                    <a class="btn btn--primary" href="<?= e(url('auth/login.php')) ?>">Back to sign in</a>
                </div>
            </div>
        </section>
        <?php
        require APP_ROOT . '/includes/footer.php';
    }
}

if (!function_exists('loginUser')) {
    function loginUser(int $userId, bool $regenerate = true): void
    {
        if ($regenerate) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until'], $_SESSION['csrf_token']);
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser(): void
    {
        if (isLoggedIn()) {
            audit('auth.logout', 'user', (int) currentUser()['id'], 'User signed out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('registerUser')) {
    /**
     * Create an account.
     *
     * Only author, editor and reviewer roles may be self-registered; any other
     * role is downgraded to author. Administrator accounts are created by an
     * existing administrator.
     *
     * @return array{0:bool,1:string,2:int} [success, message, user id]
     */
    function registerUser(string $name, string $email, string $password, string $role = 'author'): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [false, 'The database is unavailable. Please try again later.', 0];
        }

        $name = trim($name);
        $email = strtolower(trim($email));

        if (mb_strlen($name) < 2) {
            return [false, 'Please enter your full name.', 0];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Please enter a valid email address.', 0];
        }
        if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
            return [false, 'The password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.', 0];
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return [false, 'The password must contain at least one letter and one number.', 0];
        }
        if (!in_array($role, ['author', 'editor', 'reviewer'], true)) {
            $role = 'author';
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetchColumn() !== false) {
            return [false, 'An account with this email address already exists.', 0];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $statement = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')"
        );
        if (!$statement->execute([$name, $email, $hash, $role])) {
            return [false, 'The account could not be created. Please try again.', 0];
        }

        $userId = (int) $pdo->lastInsertId();
        $pdo->prepare(
            'INSERT INTO author_profiles (user_id, is_corresponding) VALUES (?, 1) ON DUPLICATE KEY UPDATE user_id = user_id'
        )->execute([$userId]);

        audit('user.register', 'user', $userId, $role . ' account created for ' . $email);

        return [true, 'Your account has been created successfully.', $userId];
    }
}

if (!function_exists('attemptLogin')) {
    /**
     * @return array{0:bool,1:string,2:array|null} [success, message, user row]
     */
    function attemptLogin(string $email, string $password): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [false, 'The database is unavailable. Please try again later.', null];
        }

        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $minutes = (int) ceil(($lockedUntil - time()) / 60);
            return [false, 'Too many failed attempts. Try again in ' . $minutes . ' minute(s).', null];
        }

        $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
            $_SESSION['login_attempts'] = 0;
            return [false, 'Too many failed attempts. The account is temporarily locked.', null];
        }

        $statement = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'] = $attempts + 1;
            return [false, 'The email address or password is incorrect.', null];
        }

        if ($user['status'] !== 'active') {
            return [false, 'This account has been suspended. Please contact the editorial office.', null];
        }

        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

        $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?')
            ->execute([substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), $user['id']]);

        return [true, 'Signed in successfully.', $user];
    }
}

if (!function_exists('roleHome')) {
    function roleHome(string $role): string
    {
        return match ($role) {
            'author' => 'author/dashboard.php',
            'editor' => 'editor/dashboard.php',
            'reviewer' => 'reviewer/dashboard.php',
            'admin' => 'admin/dashboard.php',
            default => 'index.php',
        };
    }
}
