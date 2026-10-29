<?php

/**
 * Shared application helpers.
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('db')) {
    function db(): ?PDO
    {
        global $pdo;
        return $pdo instanceof PDO ? $pdo : null;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            $pdo = db();
            if ($pdo instanceof PDO) {
                try {
                    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
                    foreach ($rows as $row) {
                        $cache[$row['setting_key']] = $row['setting_value'];
                    }
                } catch (PDOException $exception) {
                    $cache = [];
                }
            }
        }

        return array_key_exists($key, $cache) && $cache[$key] !== null ? $cache[$key] : $default;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = APP_BASE_URL === '/' ? '' : APP_BASE_URL;
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . (preg_match('#^https?://#i', $path) ? $path : url($path)));
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('takeFlashes')) {
    function takeFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('rememberOld')) {
    function rememberOld(array $input): void
    {
        unset($input['csrf_token'], $input['password'], $input['password_confirmation']);
        $_SESSION['_old'] = $input;
    }
}

if (!function_exists('clearOld')) {
    function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('manuscriptReference')) {
    function manuscriptReference(PDO $pdo, ?int $manuscriptId = null): string
    {
        $prefix = (string) setting('manuscript_prefix', APP_NAME);
        $year = (int) date('Y');

        $statement = $pdo->prepare('SELECT COUNT(*) FROM manuscripts WHERE reference_no LIKE ?');
        $statement->execute([sprintf('%s-%d-%%', $prefix, $year)]);
        $sequence = (int) $statement->fetchColumn() + 1;

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $reference = sprintf('%s-%d-%04d', $prefix, $year, $sequence);
            $check = $pdo->prepare('SELECT id FROM manuscripts WHERE reference_no = ? LIMIT 1');
            $check->execute([$reference]);
            if ($check->fetchColumn() === false) {
                return $reference;
            }
            $sequence++;
        }

        return sprintf('%s-%d-%s', $prefix, $year, strtoupper(bin2hex(random_bytes(4))));
    }
}

if (!function_exists('notify')) {
    function notify(int $userId, string $type, string $title, string $message = '', ?string $link = null): bool
    {
        $pdo = db();
        if (!$pdo instanceof PDO || $userId <= 0) {
            return false;
        }

        $statement = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)'
        );
        return $statement->execute([$userId, $type, $title, $message, $link]);
    }
}

if (!function_exists('notifyMany')) {
    function notifyMany(array $userIds, string $type, string $title, string $message = '', ?string $link = null): void
    {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            notify($userId, $type, $title, $message, $link);
        }
    }
}

if (!function_exists('audit')) {
    function audit(string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): void
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return;
        }

        $user = function_exists('currentUser') ? currentUser() : null;
        $statement = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $user['id'] ?? null,
            $action,
            $entityType,
            $entityId,
            $description !== null ? mb_substr($description, 0, 500) : null,
            substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
        ]);
    }
}

if (!function_exists('unreadNotificationCount')) {
    function unreadNotificationCount(?int $userId = null): int
    {
        $userId = $userId ?? (function_exists('currentUser') ? (int) currentUser()['id'] : 0);
        $pdo = db();
        if (!$pdo instanceof PDO || $userId <= 0) {
            return 0;
        }

        $statement = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        return round($bytes / (1024 ** $power), $precision) . ' ' . $units[$power];
    }
}

if (!function_exists('formatDate')) {
    function formatDate(?string $datetime, string $format = 'd M Y'): string
    {
        if (!$datetime || $datetime === '0000-00-00 00:00:00') {
            return '—';
        }
        $timestamp = strtotime($datetime);
        return $timestamp ? date($format, $timestamp) : '—';
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime(?string $datetime): string
    {
        return formatDate($datetime, 'd M Y, H:i');
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $letters !== '' ? $letters : '?';
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string
    {
        $label = workflow_status_label($status);
        $tone = workflow_status_tone($status);
        return '<span class="badge badge--' . e($tone) . '">' . e($label) . '</span>';
    }
}

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '';
        return strtolower(trim($value, '-'));
    }
}

if (!function_exists('storagePath')) {
    function storagePath(string $relative = ''): string
    {
        $base = rtrim(STORAGE_PATH, '/\\');
        return $relative === '' ? $base : $base . '/' . ltrim($relative, '/\\');
    }
}

if (!function_exists('protectStorageDirectory')) {
    /**
     * Denies web access to the storage folder when it has to live inside the
     * public directory.
     */
    function protectStorageDirectory(): void
    {
        $root = storagePath();
        if (!is_dir($root)) {
            return;
        }
        $guard = $root . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($guard)) {
            @file_put_contents(
                $guard,
                "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
            );
        }
    }
}

if (!function_exists('storeUploadedFile')) {
    /**
     * @return array{0:string,1:string,2:string,3:int}|null [stored relative path, original name, mime, size]
     */
    function storeUploadedFile(?array $file, string $subdirectory = 'manuscripts'): ?array
    {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The file could not be uploaded (error code ' . (int) $file['error'] . ').');
        }

        if ($file['size'] <= 0) {
            throw new RuntimeException('The uploaded file is empty.');
        }

        $maxMb = (float) setting('max_file_size_mb', 10);
        if ($file['size'] > $maxMb * 1024 * 1024) {
            throw new RuntimeException('The uploaded file exceeds the maximum size of ' . $maxMb . ' MB.');
        }

        $allowed = array_filter(array_map('trim', explode(',', (string) setting(
            'allowed_file_types',
            'application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword'
        ))));
        $mime = (string) ($file['type'] ?? 'application/octet-stream');
        if ($allowed && !in_array($mime, $allowed, true)) {
            throw new RuntimeException('This file type is not accepted. Allowed types: PDF or Word documents.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            throw new RuntimeException('Only PDF, DOC and DOCX files are accepted.');
        }

        $relative = $subdirectory . '/' . date('Y') . '/' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target = storagePath($relative);

        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('The storage directory is not writable.');
        }

        protectStorageDirectory();

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('The file could not be stored on the server.');
        }

        return [$relative, (string) $file['name'], $mime, (int) $file['size']];
    }
}

if (!function_exists('deleteStoredFile')) {
    function deleteStoredFile(?string $relative): void
    {
        if (!$relative) {
            return;
        }
        $target = storagePath($relative);
        $root = realpath(storagePath());
        $real = realpath($target);
        if ($root && $real && str_starts_with($real, $root) && is_file($real)) {
            @unlink($real);
        }
    }
}

if (!function_exists('downloadStoredFile')) {
    function downloadStoredFile(string $relative, string $originalName): void
    {
        $target = storagePath($relative);
        $root = realpath(storagePath());
        $real = realpath($target);
        if (!$root || !$real || !str_starts_with($real, $root) || !is_file($real)) {
            http_response_code(404);
            exit('File not found.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $extension === 'pdf' ? 'application/pdf' : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($real));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $originalName) . '"');
        readfile($real);
        exit;
    }
}

if (!function_exists('sanitiseFilename')) {
    function sanitiseFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? 'file';
        return substr($name, 0, 120);
    }
}

if (!function_exists('pageTitle')) {
    function pageTitle(string $title): string
    {
        return $title . ' | ' . APP_FULL_NAME;
    }
}

if (!function_exists('truncateText')) {
    function truncateText(?string $text, int $length = 160, string $suffix = '…'): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $length)) . $suffix;
    }
}

if (!function_exists('notificationIcon')) {
    function notificationIcon(string $type): string
    {
        return match ($type) {
            'submission' => 'cloud-upload-outline',
            'decision' => 'document-text-outline',
            'revision' => 'refresh-outline',
            'review_invitation' => 'mail-outline',
            'review_reminder' => 'time-outline',
            'review_submitted' => 'checkmark-done-outline',
            'review_declined' => 'close-circle-outline',
            'production' => 'construct-outline',
            'publication' => 'newspaper-outline',
            'correspondence' => 'chatbubble-ellipses-outline',
            default => 'information-circle-outline',
        };
    }
}

if (!function_exists('socialLinks')) {
    /**
     * Social channels that have a configured address.
     *
     * @return array<int, array{key:string,label:string,icon:string,url:string}>
     */
    function socialLinks(): array
    {
        $links = [];
        foreach (social_channels() as $key => $channel) {
            $url = trim((string) setting($channel['setting'], ''));
            if ($url === '') {
                continue;
            }
            $links[] = [
                'key' => $key,
                'label' => $channel['label'],
                'icon' => $channel['icon'],
                'url' => $url,
            ];
        }
        return $links;
    }
}

if (!function_exists('maskEmail')) {
    function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($name === '') {
            return $email;
        }
        $visible = mb_substr($name, 0, 1);
        return $visible . str_repeat('*', max(mb_strlen($name) - 1, 1)) . '@' . $domain;
    }
}

if (!function_exists('countries')) {
    /**
     * Country names offered in the country menus, alphabetical by ISO 3166-1 short name.
     *
     * @return array<int, string>
     */
    function countries(): array
    {
        static $countries = [
            'Afghanistan',
            'Albania',
            'Algeria',
            'Andorra',
            'Angola',
            'Antigua and Barbuda',
            'Argentina',
            'Armenia',
            'Australia',
            'Austria',
            'Azerbaijan',
            'Bahamas',
            'Bahrain',
            'Bangladesh',
            'Barbados',
            'Belarus',
            'Belgium',
            'Belize',
            'Benin',
            'Bhutan',
            'Bolivia',
            'Bosnia and Herzegovina',
            'Botswana',
            'Brazil',
            'Brunei',
            'Bulgaria',
            'Burkina Faso',
            'Burundi',
            'Cabo Verde',
            'Cambodia',
            'Cameroon',
            'Canada',
            'Central African Republic',
            'Chad',
            'Chile',
            'China',
            'Colombia',
            'Comoros',
            'Congo',
            'Costa Rica',
            'Croatia',
            'Cuba',
            'Cyprus',
            'Czechia',
            "Côte d'Ivoire",
            'Democratic Republic of the Congo',
            'Denmark',
            'Djibouti',
            'Dominica',
            'Dominican Republic',
            'Ecuador',
            'Egypt',
            'El Salvador',
            'Equatorial Guinea',
            'Eritrea',
            'Estonia',
            'Eswatini',
            'Ethiopia',
            'Fiji',
            'Finland',
            'France',
            'Gabon',
            'Gambia',
            'Georgia',
            'Germany',
            'Ghana',
            'Greece',
            'Grenada',
            'Guatemala',
            'Guinea',
            'Guinea-Bissau',
            'Guyana',
            'Haiti',
            'Honduras',
            'Hungary',
            'Iceland',
            'India',
            'Indonesia',
            'Iran',
            'Iraq',
            'Ireland',
            'Israel',
            'Italy',
            'Jamaica',
            'Japan',
            'Jordan',
            'Kazakhstan',
            'Kenya',
            'Kiribati',
            'Kuwait',
            'Kyrgyzstan',
            'Laos',
            'Latvia',
            'Lebanon',
            'Lesotho',
            'Liberia',
            'Libya',
            'Liechtenstein',
            'Lithuania',
            'Luxembourg',
            'Madagascar',
            'Malawi',
            'Malaysia',
            'Maldives',
            'Mali',
            'Malta',
            'Marshall Islands',
            'Mauritania',
            'Mauritius',
            'Mexico',
            'Micronesia',
            'Moldova',
            'Monaco',
            'Mongolia',
            'Montenegro',
            'Morocco',
            'Mozambique',
            'Myanmar',
            'Namibia',
            'Nauru',
            'Nepal',
            'Netherlands',
            'New Zealand',
            'Nicaragua',
            'Niger',
            'Nigeria',
            'North Korea',
            'North Macedonia',
            'Norway',
            'Oman',
            'Pakistan',
            'Palau',
            'Palestine',
            'Panama',
            'Papua New Guinea',
            'Paraguay',
            'Peru',
            'Philippines',
            'Poland',
            'Portugal',
            'Qatar',
            'Romania',
            'Russia',
            'Rwanda',
            'Saint Kitts and Nevis',
            'Saint Lucia',
            'Saint Vincent and the Grenadines',
            'Samoa',
            'San Marino',
            'Sao Tome and Principe',
            'Saudi Arabia',
            'Senegal',
            'Serbia',
            'Seychelles',
            'Sierra Leone',
            'Singapore',
            'Slovakia',
            'Slovenia',
            'Solomon Islands',
            'Somalia',
            'South Africa',
            'South Korea',
            'South Sudan',
            'Spain',
            'Sri Lanka',
            'Sudan',
            'Suriname',
            'Sweden',
            'Switzerland',
            'Syria',
            'Taiwan',
            'Tajikistan',
            'Tanzania',
            'Thailand',
            'Timor-Leste',
            'Togo',
            'Tonga',
            'Trinidad and Tobago',
            'Tunisia',
            'Turkey',
            'Turkmenistan',
            'Tuvalu',
            'Uganda',
            'Ukraine',
            'United Arab Emirates',
            'United Kingdom',
            'United States of America',
            'Uruguay',
            'Uzbekistan',
            'Vanuatu',
            'Vatican City',
            'Venezuela',
            'Vietnam',
            'Yemen',
            'Zambia',
            'Zimbabwe',
        ];

        return $countries;
    }
}

if (!function_exists('resolveCountry')) {
    /**
     * Normalise a submitted country name against the country menu.
     *
     * Returns an empty string when the value is blank, the canonical menu entry
     * when it matches one, and null when the value is not on the menu.
     */
    function resolveCountry(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        foreach (countries() as $country) {
            if (strcasecmp($country, $value) === 0) {
                return $country;
            }
        }

        return null;
    }
}
