<?php

/**
 * Application configuration.
 */

define('APP_NAME', 'NIJOSTAM');
define('APP_FULL_NAME', 'Nigerian Journal of Science, Technology, and Applied Materials');
define('APP_TAGLINE', 'NILEST Journal of Science, Technology & Management');
define('APP_PUBLISHER', 'Nigerian Institute of Leather and Science Technology');
define('APP_VERSION', '1.0.0');

define('APP_ROOT', dirname(__DIR__));

/**
 * Base URL of the application root, derived from the document root so that
 * every page (including the portal folders) resolves shared assets correctly.
 */
$appBaseUrl = '/';
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT'])
    ? realpath($_SERVER['DOCUMENT_ROOT'])
    : false;
$appRealPath = realpath(APP_ROOT);
if ($documentRoot && $appRealPath && str_starts_with($appRealPath, $documentRoot)) {
    $appBaseUrl = str_replace('\\', '/', substr($appRealPath, strlen($documentRoot)));
    $appBaseUrl = '/' . trim($appBaseUrl, '/');
    $appBaseUrl = $appBaseUrl === '/' ? '' : $appBaseUrl;
}
define('APP_BASE_URL', $appBaseUrl);

/**
 * Manuscript files are stored outside the public web directory.
 *
 * The location can be overridden with the NIJOSTAM_STORAGE environment
 * variable. Otherwise a sibling of the document root is used, with a
 * private in-application directory as the last resort.
 */
$storageRoot = dirname(APP_ROOT);
if ($documentRoot) {
    if (realpath($storageRoot) === $documentRoot) {
        $storageRoot = dirname($storageRoot);
    }
} else {
    $storageRoot = dirname($storageRoot);
}

define('STORAGE_PATH', getenv('NIJOSTAM_STORAGE') ?: $storageRoot . DIRECTORY_SEPARATOR . 'nijostam_storage');
define('STORAGE_IS_PUBLIC', (bool) ($documentRoot && str_starts_with(
    (string) realpath(dirname(STORAGE_PATH)),
    (string) $documentRoot
)));

define('SESSION_NAME', 'nijostam_session');
define('SESSION_LIFETIME', 7200);
define('PASSWORD_MIN_LENGTH', 12);
define('LOGIN_MAX_ATTEMPTS', 6);
define('LOGIN_LOCKOUT_SECONDS', 900);

define('MAIL_ENABLED', filter_var(getenv('NIJOSTAM_MAIL') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('MAIL_FROM', getenv('NIJOSTAM_MAIL_FROM') ?: 'editorial@nijostam.org');
define('MAIL_FROM_NAME', 'NIJOSTAM Editorial Office');

/**
 * Editorial workflow. Statuses are configurable so journal policy can change
 * without rewriting application logic.
 */
$GLOBALS['workflow_statuses'] = [
    'submitted' => ['label' => 'Submitted', 'tone' => 'info', 'author_action' => false],
    'under_screening' => ['label' => 'Editorial screening', 'tone' => 'info', 'author_action' => false],
    'awaiting_reviewer' => ['label' => 'Awaiting reviewer responses', 'tone' => 'info', 'author_action' => false],
    'under_review' => ['label' => 'Under peer review', 'tone' => 'info', 'author_action' => false],
    'revision_requested' => ['label' => 'Revision requested', 'tone' => 'warn', 'author_action' => true],
    'revised' => ['label' => 'Revised manuscript received', 'tone' => 'info', 'author_action' => false],
    'accepted' => ['label' => 'Accepted', 'tone' => 'good', 'author_action' => false],
    'rejected' => ['label' => 'Rejected', 'tone' => 'bad', 'author_action' => false],
    'in_production' => ['label' => 'Copyediting and production', 'tone' => 'info', 'author_action' => false],
    'published' => ['label' => 'Published', 'tone' => 'good', 'author_action' => false],
];

$GLOBALS['editorial_decisions'] = [
    'reject' => ['label' => 'Reject', 'tone' => 'bad', 'next_status' => 'rejected'],
    'minor_revision' => ['label' => 'Minor revision', 'tone' => 'warn', 'next_status' => 'revision_requested'],
    'major_revision' => ['label' => 'Major revision', 'tone' => 'warn', 'next_status' => 'revision_requested'],
    'accept' => ['label' => 'Accept', 'tone' => 'good', 'next_status' => 'accepted'],
];

$GLOBALS['review_recommendations'] = [
    'accept' => 'Accept',
    'minor_revision' => 'Minor revision',
    'major_revision' => 'Major revision',
    'reject' => 'Reject',
];

$GLOBALS['role_labels'] = [
    'author' => 'Author',
    'editor' => 'Editor',
    'reviewer' => 'Reviewer',
    'admin' => 'Administrator',
];

$GLOBALS['article_types'] = [
    'Original research' => 'Original research',
    'Short research article' => 'Short research article',
    'Review' => 'Review',
    'Short communication' => 'Short communication',
    'Minireview' => 'Minireview',
    'Letter to the editor' => 'Letter to the editor',
];

$GLOBALS['subjects'] = [
    'Science' => 'Science',
    'Technology' => 'Technology',
    'Engineering' => 'Engineering',
    'Management' => 'Management',
    'Materials science' => 'Materials science',
    'Environmental science' => 'Environmental science',
    'Leather science' => 'Leather science',
];

/**
 * Social channels rendered in the top bar. Each URL is stored in the settings
 * table so the editorial office can update them without touching code.
 */
$GLOBALS['social_channels'] = [
    'facebook' => ['label' => 'Facebook', 'icon' => 'logo-facebook', 'setting' => 'social_facebook'],
    'x' => ['label' => 'X (Twitter)', 'icon' => 'logo-twitter', 'setting' => 'social_x'],
    'instagram' => ['label' => 'Instagram', 'icon' => 'logo-instagram', 'setting' => 'social_instagram'],
    'linkedin' => ['label' => 'LinkedIn', 'icon' => 'logo-linkedin', 'setting' => 'social_linkedin'],
    'youtube' => ['label' => 'YouTube', 'icon' => 'logo-youtube', 'setting' => 'social_youtube'],
    'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'logo-whatsapp', 'setting' => 'social_whatsapp'],
];

function social_channels(): array
{
    return $GLOBALS['social_channels'];
}

function workflow_statuses(): array
{
    return $GLOBALS['workflow_statuses'];
}

function workflow_status_label(string $status): string
{
    return $GLOBALS['workflow_statuses'][$status]['label'] ?? ucfirst(str_replace('_', ' ', $status));
}

function workflow_status_tone(string $status): string
{
    return $GLOBALS['workflow_statuses'][$status]['tone'] ?? 'info';
}

function editorial_decisions(): array
{
    return $GLOBALS['editorial_decisions'];
}

function review_recommendations(): array
{
    return $GLOBALS['review_recommendations'];
}

function role_labels(): array
{
    return $GLOBALS['role_labels'];
}

function article_types(): array
{
    return $GLOBALS['article_types'];
}

function subjects(): array
{
    return $GLOBALS['subjects'];
}
