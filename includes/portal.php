<?php

/**
 * Portal chrome: sidebar navigation, flash messages and shared layout helpers.
 */

if (!function_exists('portalNavItems')) {
    function portalNavItems(string $role): array
    {
        $notifications = unreadNotificationCount();
        $badge = $notifications > 0 ? $notifications : null;

        $items = match ($role) {
            'author' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid-outline', 'href' => 'author/dashboard.php'],
                ['key' => 'submit', 'label' => 'Submit manuscript', 'icon' => 'cloud-upload-outline', 'href' => 'author/submit.php'],
                ['key' => 'manuscripts', 'label' => 'My manuscripts', 'icon' => 'documents-outline', 'href' => 'author/manuscripts.php'],
                ['key' => 'profile', 'label' => 'My profile', 'icon' => 'person-outline', 'href' => 'author/profile.php'],
            ],
            'editor' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid-outline', 'href' => 'editor/dashboard.php'],
                ['key' => 'manuscripts', 'label' => 'Manuscripts', 'icon' => 'documents-outline', 'href' => 'editor/manuscripts.php'],
                ['key' => 'reviewers', 'label' => 'Reviewers', 'icon' => 'people-outline', 'href' => 'editor/reviewers.php'],
                ['key' => 'publication', 'label' => 'Publication', 'icon' => 'newspaper-outline', 'href' => 'publication/dashboard.php'],
            ],
            'reviewer' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid-outline', 'href' => 'reviewer/dashboard.php'],
                ['key' => 'reviews', 'label' => 'My reviews', 'icon' => 'clipboard-outline', 'href' => 'reviewer/reviews.php'],
            ],
            'admin' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid-outline', 'href' => 'admin/dashboard.php'],
                ['key' => 'users', 'label' => 'Users', 'icon' => 'people-outline', 'href' => 'admin/users.php'],
                ['key' => 'manuscripts', 'label' => 'Manuscripts', 'icon' => 'documents-outline', 'href' => 'admin/manuscripts.php'],
                ['key' => 'publication', 'label' => 'Publication', 'icon' => 'newspaper-outline', 'href' => 'publication/dashboard.php'],
                ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'megaphone-outline', 'href' => 'admin/announcements.php'],
                ['key' => 'messages', 'label' => 'Messages', 'icon' => 'chatbubble-ellipses-outline', 'href' => 'admin/messages.php'],
                ['key' => 'settings', 'label' => 'Settings', 'icon' => 'settings-outline', 'href' => 'admin/settings.php'],
                ['key' => 'audit', 'label' => 'Audit log', 'icon' => 'reader-outline', 'href' => 'admin/audit.php'],
            ],
            default => [],
        };

        $items[] = ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'notifications-outline', 'href' => 'notifications/index.php', 'count' => $badge];

        return $items;
    }
}

if (!function_exists('renderPortalSidebar')) {
    function renderPortalSidebar(array $user, string $active = ''): void
    {
        $items = portalNavItems($user['role']);
        ?>
        <aside class="portal-sidebar">
            <div class="portal-sidebar-head">
                <span class="portal-avatar"><?= e(initials($user['name'])) ?></span>
                <div>
                    <strong><?= e($user['name']) ?></strong>
                    <span><?= e(role_labels()[$user['role']] ?? ucfirst($user['role'])) ?></span>
                </div>
            </div>
            <nav aria-label="Workspace navigation">
                <ul class="portal-nav">
                    <?php foreach ($items as $item): ?>
                        <li>
                            <a href="<?= e(url($item['href'])) ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>>
                                <ion-icon name="<?= e($item['icon']) ?>" aria-hidden="true"></ion-icon>
                                <span><?= e($item['label']) ?></span>
                                <?php if (!empty($item['count'])): ?><span class="nav-count"><?= e((string) $item['count']) ?></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>
        <?php
    }
}

if (!function_exists('renderFlash')) {
    function renderFlash(): void
    {
        $flashes = takeFlashes();
        if (!$flashes) {
            return;
        }
        $icons = [
            'success' => 'checkmark-circle-outline',
            'warning' => 'alert-circle-outline',
            'danger' => 'close-circle-outline',
            'error' => 'close-circle-outline',
            'info' => 'information-circle-outline',
        ];
        echo '<div class="alert-stack">';
        foreach ($flashes as $flash) {
            $type = $flashes && isset($icons[$flash['type']]) ? $flash['type'] : 'info';
            echo '<div class="alert alert--' . e($type) . '"><ion-icon name="' . e($icons[$type]) . '" aria-hidden="true"></ion-icon><span>' . e($flash['message']) . '</span></div>';
        }
        echo '</div>';
    }
}

if (!function_exists('renderPageHead')) {
    function renderPageHead(string $title, string $description = '', ?string $actions = null): void
    {
        ?>
        <div class="portal-head">
            <div>
                <h1><?= e($title) ?></h1>
                <?php if ($description !== ''): ?><p><?= e($description) ?></p><?php endif; ?>
            </div>
            <?php if ($actions !== null): ?><div class="portal-head-actions"><?= $actions ?></div><?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('renderPagination')) {
    function renderPagination(int $page, int $pages, string $baseUrl): void
    {
        if ($pages <= 1) {
            return;
        }
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        echo '<nav class="pagination" aria-label="Pagination">';
        if ($page > 1) {
            echo '<a href="' . e($baseUrl . $separator . 'page=' . ($page - 1)) . '">Previous</a>';
        } else {
            echo '<span class="is-disabled">Previous</span>';
        }

        $start = max(1, $page - 2);
        $end = min($pages, $start + 4);
        $start = max(1, $end - 4);
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $page) {
                echo '<span class="is-current" aria-current="page">' . e((string) $i) . '</span>';
            } else {
                echo '<a href="' . e($baseUrl . $separator . 'page=' . $i) . '">' . e((string) $i) . '</a>';
            }
        }

        if ($page < $pages) {
            echo '<a href="' . e($baseUrl . $separator . 'page=' . ($page + 1)) . '">Next</a>';
        } else {
            echo '<span class="is-disabled">Next</span>';
        }
        echo '</nav>';
    }
}
