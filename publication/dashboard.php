<?php

/**
 * Publication workspace: issues, accepted manuscripts, and publishing.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin', 'editor');

$user = currentUser();
$pdo = db();

$issues = $pdo->query(
    'SELECT i.*, (SELECT COUNT(*) FROM published_articles a WHERE a.issue_id = i.id) AS article_count
     FROM journal_issues i ORDER BY i.published_at DESC, i.id DESC'
)->fetchAll();

$ready = $pdo->query(
    "SELECT m.*, u.name AS submitter_name FROM manuscripts m
     INNER JOIN users u ON u.id = m.submitted_by
     WHERE m.status IN ('accepted', 'in_production') AND m.published_article_id IS NULL
     ORDER BY m.decided_at"
)->fetchAll();

$published = $pdo->query(
    'SELECT m.id, m.reference_no, m.title, a.id AS article_id, a.issue_id, i.volume, i.issue_number
     FROM manuscripts m
     INNER JOIN published_articles a ON a.manuscript_id = m.id
     INNER JOIN journal_issues i ON i.id = a.issue_id
     ORDER BY a.id DESC LIMIT 10'
)->fetchAll();

$metaTitle = 'Publication';
$pageTitle = 'Publication';
$pageDescription = 'Assemble issues and publish accepted manuscripts.';
$activePage = 'admin-publication';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'publication'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            'Publication',
            'Create issues, place accepted manuscripts, and publish them to the journal site.',
            '<a class="portal-btn" href="' . e(url('publication/issue.php')) . '"><ion-icon name="add-circle-outline" aria-hidden="true"></ion-icon>New issue</a>'
        );
        ?>

        <section class="portal-card">
            <div class="portal-card-head">
                <div><h2>Ready to publish</h2><p>Accepted manuscripts not yet placed in an issue.</p></div>
            </div>
            <?php if (!$ready): ?>
                <div class="empty-state">
                    <ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>
                    <h2>Nothing waiting</h2>
                    <p>Manuscripts appear here once the handling editor accepts them.</p>
                </div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Author</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($ready as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 58)) ?></span></td>
                                <td><span class="muted"><?= e($manuscript['submitter_name']) ?></span></td>
                                <td><?= statusBadge((string) $manuscript['status']) ?></td>
                                <td><a class="portal-btn portal-btn--sm" href="<?= e(url('publication/article.php?manuscript=' . (int) $manuscript['id'])) ?>">Publish</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="portal-card">
            <div class="portal-card-head">
                <div><h2>Journal issues</h2><p><?= e((string) count($issues)) ?> issue(s) on record.</p></div>
                <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('publication/issue.php')) ?>">Manage issues</a>
            </div>
            <?php if (!$issues): ?>
                <div class="empty-state" style="padding:28px 8px"><ion-icon name="albums-outline" aria-hidden="true"></ion-icon><p>No issues created yet.</p></div>
            <?php else: ?>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Volume</th><th>Issue</th><th>Period</th><th>Published</th><th>Articles</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($issues as $issue): ?>
                            <tr>
                                <td><span class="ref">Vol. <?= e($issue['volume']) ?></span></td>
                                <td><span class="title-cell">No. <?= e($issue['issue_number']) ?></span></td>
                                <td><span class="muted"><?= e($issue['period']) ?></span></td>
                                <td><span class="muted"><?= e(formatDate($issue['published_at'])) ?></span></td>
                                <td><span class="muted"><?= e((string) $issue['article_count']) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('publication/issue.php?id=' . (int) $issue['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($published): ?>
            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Recently published</h2><p>Manuscripts now live on the journal site.</p></div></div>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Reference</th><th>Title</th><th>Issue</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($published as $manuscript): ?>
                            <tr>
                                <td><span class="ref"><?= e($manuscript['reference_no']) ?></span></td>
                                <td><span class="title-cell"><?= e(truncateText($manuscript['title'], 58)) ?></span></td>
                                <td><span class="muted">Vol. <?= e($manuscript['volume']) ?> No. <?= e($manuscript['issue_number']) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('article.php?id=' . (int) $manuscript['article_id'])) ?>">View article</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
