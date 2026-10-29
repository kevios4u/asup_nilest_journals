<?php

/**
 * Reviewer directory for editors.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('editor', 'admin');

$user = currentUser();
$pdo = db();

$search = trim((string) ($_GET['q'] ?? ''));

$where = "u.role = 'reviewer'";
$params = [];
if ($search !== '') {
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR p.affiliation LIKE ? OR p.country LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$statement = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login_at,
            p.affiliation, p.country, p.institution,
            (SELECT COUNT(*) FROM reviewer_assignments ra WHERE ra.reviewer_id = u.id) AS assignment_count,
            (SELECT COUNT(*) FROM reviews r WHERE r.reviewer_id = u.id) AS review_count
     FROM users u
     LEFT JOIN author_profiles p ON p.user_id = u.id
     WHERE {$where}
     ORDER BY u.name"
);
$statement->execute($params);
$reviewers = $statement->fetchAll();

$metaTitle = 'Reviewers';
$pageTitle = 'Reviewers';
$pageDescription = 'Registered peer reviewers and their workload.';
$activePage = 'editor-reviewers';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'reviewers'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Reviewers', count($reviewers) . ' registered peer reviewer(s).'); ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <form class="filter-bar" method="get" action="<?= e(url('editor/reviewers.php')) ?>">
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, affiliation, or country">
                    <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit">Search</button>
                </form>
            </div>

            <?php if (!$reviewers): ?>
                <div class="empty-state">
                    <ion-icon name="people-outline" aria-hidden="true"></ion-icon>
                    <h2>No reviewers found</h2>
                    <p>Reviewer accounts appear here once they register and are activated.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reviewer</th><th>Affiliation</th><th>Status</th><th>Workload</th><th>Last sign-in</th></tr></thead>
                        <tbody>
                        <?php foreach ($reviewers as $reviewer): ?>
                            <tr>
                                <td><span class="title-cell"><?= e($reviewer['name']) ?></span><span class="muted"><?= e($reviewer['email']) ?><?= $reviewer['institution'] ? ' · ' . e($reviewer['institution']) : '' ?></span></td>
                                <td><span class="muted"><?= e($reviewer['institution'] ?? $reviewer['affiliation'] ?? '—') ?><?= $reviewer['country'] ? ' · ' . e($reviewer['country']) : '' ?></span></td>
                                <td><span class="badge badge--<?= $reviewer['status'] === 'active' ? 'good' : 'bad' ?>"><?= e(ucfirst((string) $reviewer['status'])) ?></span></td>
                                <td><span class="muted"><?= e((string) $reviewer['assignment_count']) ?> assigned · <?= e((string) $reviewer['review_count']) ?> reports</span></td>
                                <td><span class="muted"><?= e(formatDate($reviewer['last_login_at'])) ?></span></td>
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
