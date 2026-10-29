<?php

/**
 * Publish an accepted manuscript into an issue.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('admin', 'editor');

$user = currentUser();
$pdo = db();

$manuscriptId = (int) ($_GET['manuscript'] ?? ($_POST['manuscript_id'] ?? 0));
$manuscript = $manuscriptId > 0 ? loadManuscript($manuscriptId) : null;

$issues = $pdo->query(
    'SELECT i.*, (SELECT COUNT(*) FROM published_articles a WHERE a.issue_id = i.id) AS article_count
     FROM journal_issues i ORDER BY i.published_at DESC, i.id DESC'
)->fetchAll();

if (!$manuscript) {
    flash('warning', 'Select a manuscript from the publication dashboard.');
    redirect('publication/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('publication/article.php?manuscript=' . $manuscriptId);
    }

    $issueId = (int) ($_POST['issue_id'] ?? 0);
    $pages = trim((string) ($_POST['pages'] ?? ''));
    $doi = trim((string) ($_POST['doi'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    $issueStatement = $pdo->prepare('SELECT * FROM journal_issues WHERE id = ?');
    $issueStatement->execute([$issueId]);
    $issue = $issueStatement->fetch();
    if (!$issue) {
        flash('danger', 'Choose a valid issue.');
        redirect('publication/article.php?manuscript=' . $manuscriptId);
    }

    $authors = manuscriptAuthors($manuscriptId);
    $authorNames = [];
    foreach ($authors as $author) {
        $authorNames[] = $author['name'];
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'INSERT INTO published_articles (issue_id, manuscript_id, title, authors, pages, category, doi, abstract, keywords, article_url, pdf_url, sort_order, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $issueId,
            $manuscriptId,
            $manuscript['title'],
            implode(', ', $authorNames),
            $pages !== '' ? $pages : null,
            $manuscript['subject'],
            $doi !== '' ? $doi : null,
            $manuscript['abstract'],
            $manuscript['keywords'],
            null,
            null,
            $sortOrder,
            $issue['published_at'],
        ]);

        $articleId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            "UPDATE manuscripts SET status = 'published', published_article_id = ?, decided_at = COALESCE(decided_at, NOW()) WHERE id = ?"
        )->execute([$articleId, $manuscriptId]);

        $pdo->commit();
    } catch (PDOException $exception) {
        $pdo->rollBack();
        flash('danger', 'The article could not be published. Please try again.');
        redirect('publication/article.php?manuscript=' . $manuscriptId);
    }

    notify((int) $manuscript['submitted_by'], 'publication', 'Your manuscript has been published', $manuscript['reference_no'] . ' now appears in Volume ' . $issue['volume'] . ', Issue ' . $issue['issue_number'] . '.', 'article.php?id=' . $articleId);
    audit('article.publish', 'published_article', $articleId, $manuscript['reference_no'] . ' published in ' . $issue['volume'] . '(' . $issue['issue_number'] . ')');

    flash('success', 'The manuscript has been published in Volume ' . $issue['volume'] . ', Issue ' . $issue['issue_number'] . '.');
    redirect('publication/issue.php?id=' . $issueId);
}

$authors = manuscriptAuthors($manuscriptId);
$publishedArticle = null;
if ($manuscript['published_article_id']) {
    $statement = $pdo->prepare('SELECT * FROM published_articles WHERE id = ?');
    $statement->execute([(int) $manuscript['published_article_id']]);
    $publishedArticle = $statement->fetch() ?: null;
}

$metaTitle = 'Publish ' . $manuscript['reference_no'];
$pageTitle = 'Publish ' . $manuscript['reference_no'];
$pageDescription = truncateText($manuscript['title'], 160);
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
            'Publish ' . $manuscript['reference_no'],
            (string) $manuscript['title'],
            '<a class="portal-btn portal-btn--ghost" href="' . e(url('publication/dashboard.php')) . '"><ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>Publication</a>'
        );
        ?>

        <?php if ($publishedArticle): ?>
            <div class="alert alert--success"><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon><span>This manuscript is already published. <a href="<?= e(url('article.php?id=' . (int) $publishedArticle['id'])) ?>">View the article page</a>.</span></div>
        <?php endif; ?>

        <div class="split-panel">
            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Article metadata</h2><p>Confirm the citation details for the published article.</p></div></div>
                <div class="portal-card-body">
                    <div class="detail-grid">
                        <div class="detail-item"><span>Reference</span><strong><?= e($manuscript['reference_no']) ?></strong></div>
                        <div class="detail-item"><span>Article type</span><strong><?= e($manuscript['article_type'] ?? '—') ?></strong></div>
                        <div class="detail-item"><span>Subject</span><strong><?= e($manuscript['subject'] ?? '—') ?></strong></div>
                        <div class="detail-item"><span>Decision</span><strong><?= e(ucfirst(str_replace('_', ' ', (string) ($manuscript['decision'] ?? '—')))) ?></strong></div>
                    </div>

                    <h3 style="margin:22px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Title</h3>
                    <p style="margin:0;font-size:15px;line-height:1.5"><?= e($manuscript['title']) ?></p>

                    <h3 style="margin:20px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Authors</h3>
                    <p style="margin:0;font-size:14px"><?php foreach ($authors as $index => $author): ?><?= e($author['name']) ?><?= $index < count($authors) - 1 ? ', ' : '' ?><?php endforeach; ?></p>

                    <h3 style="margin:20px 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Abstract</h3>
                    <p style="margin:0;font-size:14px;line-height:1.7;color:var(--portal-muted)"><?= e($manuscript['abstract']) ?></p>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Place in an issue</h2><p>Select the issue that will carry this article.</p></div></div>
                <div class="portal-card-body">
                    <?php if ($publishedArticle): ?>
                        <p class="muted" style="margin:0;font-size:14px">This manuscript is already published and cannot be placed again.</p>
                    <?php else: ?>
                        <form class="portal-form" method="post" action="<?= e(url('publication/article.php?manuscript=' . $manuscriptId)) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="manuscript_id" value="<?= e((string) $manuscriptId) ?>">
                            <div class="form-field">
                                <label for="issue_id">Issue <span class="required-mark">*</span></label>
                                <select id="issue_id" name="issue_id" required>
                                    <option value="">Select an issue</option>
                                    <?php foreach ($issues as $issue): ?>
                                        <option value="<?= e((string) $issue['id']) ?>">Volume <?= e($issue['volume']) ?>, Issue <?= e($issue['issue_number']) ?> — <?= e($issue['period']) ?> (<?= e((string) $issue['article_count']) ?> articles)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="pages">Pages</label>
                                    <input type="text" id="pages" name="pages" placeholder="1 - 12">
                                </div>
                                <div class="form-field">
                                    <label for="doi">DOI</label>
                                    <input type="text" id="doi" name="doi" placeholder="10.1234/nijostam.2026.0001">
                                </div>
                                <div class="form-field">
                                    <label for="sort_order">Order in issue</label>
                                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                                </div>
                            </div>
                            <div class="portal-actions">
                                <button class="portal-btn" type="submit"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>Publish article</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
