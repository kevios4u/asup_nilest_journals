<?php

/**
 * Create and manage journal issues.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin', 'editor');

$user = currentUser();
$pdo = db();

$issueId = (int) ($_GET['id'] ?? 0);
$issue = null;
if ($issueId > 0) {
    $statement = $pdo->prepare('SELECT * FROM journal_issues WHERE id = ?');
    $statement->execute([$issueId]);
    $issue = $statement->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('publication/issue.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $volume = trim((string) ($_POST['volume'] ?? ''));
        $issueNumber = trim((string) ($_POST['issue_number'] ?? ''));
        $period = trim((string) ($_POST['period'] ?? ''));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        $coverUrl = trim((string) ($_POST['cover_url'] ?? ''));

        if ($volume === '' || $issueNumber === '' || $period === '') {
            flash('danger', 'Volume, issue number, and period are all required.');
            redirect('publication/issue.php');
        }

        $duplicate = $pdo->prepare('SELECT id FROM journal_issues WHERE volume = ? AND issue_number = ?');
        $duplicate->execute([$volume, $issueNumber]);
        if ($duplicate->fetchColumn() !== false) {
            flash('danger', 'That volume and issue number already exist.');
            redirect('publication/issue.php');
        }

        $pdo->prepare(
            'INSERT INTO journal_issues (volume, issue_number, period, published_at, cover_url) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $volume,
            $issueNumber,
            $period,
            $publishedAt !== '' ? $publishedAt : date('Y-m-d'),
            $coverUrl !== '' ? $coverUrl : null,
        ]);

        $newId = (int) $pdo->lastInsertId();
        audit('issue.create', 'journal_issue', $newId, 'Issue ' . $volume . '(' . $issueNumber . ') created');
        flash('success', 'Issue created.');
        redirect('publication/issue.php?id=' . $newId);
    }

    if ($action === 'update' && $issue) {
        $volume = trim((string) ($_POST['volume'] ?? ''));
        $issueNumber = trim((string) ($_POST['issue_number'] ?? ''));
        $period = trim((string) ($_POST['period'] ?? ''));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        $coverUrl = trim((string) ($_POST['cover_url'] ?? ''));

        $pdo->prepare(
            'UPDATE journal_issues SET volume = ?, issue_number = ?, period = ?, published_at = ?, cover_url = ? WHERE id = ?'
        )->execute([
            $volume,
            $issueNumber,
            $period,
            $publishedAt !== '' ? $publishedAt : $issue['published_at'],
            $coverUrl !== '' ? $coverUrl : null,
            $issueId,
        ]);

        audit('issue.update', 'journal_issue', $issueId, 'Issue details updated');
        flash('success', 'Issue details updated.');
        redirect('publication/issue.php?id=' . $issueId);
    }

    if ($action === 'remove_article' && $issue) {
        $articleId = (int) ($_POST['article_id'] ?? 0);
        $pdo->prepare('UPDATE published_articles SET manuscript_id = NULL WHERE id = ?')->execute([$articleId]);
        $pdo->prepare('UPDATE manuscripts SET published_article_id = NULL, status = ? WHERE published_article_id = ?')
            ->execute(['accepted', $articleId]);
        audit('issue.remove_article', 'journal_issue', $issueId, 'Article removed from issue');
        flash('success', 'The article has been removed from this issue.');
        redirect('publication/issue.php?id=' . $issueId);
    }

    redirect('publication/issue.php');
}

$issueArticles = [];
if ($issue) {
    $statement = $pdo->prepare(
        'SELECT a.*, m.reference_no FROM published_articles a
         LEFT JOIN manuscripts m ON m.id = a.manuscript_id
         WHERE a.issue_id = ? ORDER BY a.sort_order, a.id'
    );
    $statement->execute([$issueId]);
    $issueArticles = $statement->fetchAll();
}

$issues = $pdo->query(
    'SELECT i.*, (SELECT COUNT(*) FROM published_articles a WHERE a.issue_id = i.id) AS article_count
     FROM journal_issues i ORDER BY i.published_at DESC, i.id DESC'
)->fetchAll();

$metaTitle = $issue ? 'Issue Vol. ' . $issue['volume'] . ' No. ' . $issue['issue_number'] : 'New issue';
$pageTitle = $metaTitle;
$pageDescription = 'Create and populate journal issues.';
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
            $issue ? 'Volume ' . $issue['volume'] . ', Issue ' . $issue['issue_number'] : 'Create a new issue',
            $issue ? $issue['period'] : 'Set up an issue, then place accepted manuscripts in it.',
            '<a class="portal-btn portal-btn--ghost" href="' . e(url('publication/dashboard.php')) . '"><ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>Publication</a>'
        );
        ?>

        <div class="split-panel">
            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head"><div><h2><?= $issue ? 'Issue details' : 'New issue' ?></h2><p>Volume, number, and coverage period.</p></div></div>
                    <div class="portal-card-body">
                        <form class="portal-form" method="post" action="<?= e(url('publication/issue.php') . ($issue ? '?id=' . $issueId : '')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="<?= $issue ? 'update' : 'create' ?>">
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="volume">Volume <span class="required-mark">*</span></label>
                                    <input type="text" id="volume" name="volume" value="<?= e((string) ($issue['volume'] ?? '')) ?>" required>
                                </div>
                                <div class="form-field">
                                    <label for="issue_number">Issue number <span class="required-mark">*</span></label>
                                    <input type="text" id="issue_number" name="issue_number" value="<?= e((string) ($issue['issue_number'] ?? '')) ?>" required>
                                </div>
                            </div>
                            <div class="form-field">
                                <label for="period">Period <span class="required-mark">*</span></label>
                                <input type="text" id="period" name="period" value="<?= e((string) ($issue['period'] ?? '')) ?>" placeholder="January - June, 2027" required>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="published_at">Publication date</label>
                                    <input type="date" id="published_at" name="published_at" value="<?= e((string) ($issue['published_at'] ?? date('Y-m-d'))) ?>">
                                </div>
                                <div class="form-field">
                                    <label for="cover_url">Cover image URL</label>
                                    <input type="text" id="cover_url" name="cover_url" value="<?= e((string) ($issue['cover_url'] ?? '')) ?>">
                                </div>
                            </div>
                            <div class="portal-actions">
                                <button class="portal-btn" type="submit"><ion-icon name="save-outline" aria-hidden="true"></ion-icon><?= $issue ? 'Save issue' : 'Create issue' ?></button>
                            </div>
                        </form>
                    </div>
                </section>

                <?php if ($issue): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><div><h2>Articles in this issue</h2><p><?= e((string) count($issueArticles)) ?> article(s) placed.</p></div></div>
                        <?php if (!$issueArticles): ?>
                            <div class="empty-state" style="padding:28px 8px">
                                <ion-icon name="documents-outline" aria-hidden="true"></ion-icon>
                                <p>No articles in this issue yet. Publish an accepted manuscript to add it.</p>
                                <a class="portal-btn portal-btn--sm" href="<?= e(url('publication/dashboard.php')) ?>">Go to ready list</a>
                            </div>
                        <?php else: ?>
                            <div class="portal-table-wrap">
                                <table class="portal-table">
                                    <thead><tr><th>Reference</th><th>Title</th><th>Pages</th><th></th></tr></thead>
                                    <tbody>
                                    <?php foreach ($issueArticles as $article): ?>
                                        <tr>
                                            <td><span class="ref"><?= e($article['reference_no'] ?? '—') ?></span></td>
                                            <td><span class="title-cell"><?= e(truncateText($article['title'], 58)) ?></span></td>
                                            <td><span class="muted"><?= e($article['pages'] ?? '—') ?></span></td>
                                            <td>
                                                <form method="post" action="<?= e(url('publication/issue.php?id=' . $issueId)) ?>" onsubmit="return confirm('Remove this article from the issue?')">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="remove_article">
                                                    <input type="hidden" name="article_id" value="<?= e((string) $article['id']) ?>">
                                                    <button class="portal-btn portal-btn--danger portal-btn--sm" type="submit">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>All issues</h2><p>Existing issues of the journal.</p></div></div>
                <div class="portal-table-wrap">
                    <table class="portal-table">
                        <thead><tr><th>Issue</th><th>Period</th><th>Articles</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($issues as $row): ?>
                            <tr>
                                <td><span class="ref">Vol. <?= e($row['volume']) ?> No. <?= e($row['issue_number']) ?></span></td>
                                <td><span class="muted"><?= e($row['period']) ?></span></td>
                                <td><span class="muted"><?= e((string) $row['article_count']) ?></span></td>
                                <td><a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('publication/issue.php?id=' . (int) $row['id'])) ?>">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
