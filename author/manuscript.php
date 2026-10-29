<?php

/**
 * Author view of a single manuscript, including revision uploads.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('author', 'admin');

$user = currentUser();
$pdo = db();
$manuscriptId = (int) ($_GET['id'] ?? 0);
$manuscript = $manuscriptId > 0 ? loadManuscript($manuscriptId) : null;

if (!$manuscript || !canViewManuscript($manuscript, $user)) {
    http_response_code(404);
    $metaTitle = 'Manuscript not found';
    $pageTitle = 'Manuscript not found';
    $pageDescription = 'The requested manuscript could not be found.';
    $activePage = 'author-dashboard';
    $isPortal = true;
    require __DIR__ . '/../includes/header.php';
    ?>
    <div class="portal-shell">
        <?php renderPortalSidebar($user, 'dashboard'); ?>
        <div class="portal-main">
            <div class="portal-card"><div class="empty-state">
                <ion-icon name="document-outline" aria-hidden="true"></ion-icon>
                <h1>Manuscript not found</h1>
                <p>This manuscript does not exist, or it is not linked to your account.</p>
                <a class="portal-btn" href="<?= e(url('author/manuscripts.php')) ?>">Back to my manuscripts</a>
            </div></div>
        </div>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$canRevise = in_array($manuscript['status'], ['revision_requested'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revise') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('author/manuscript.php?id=' . $manuscriptId);
    }

    if (!$canRevise) {
        flash('danger', 'A revision cannot be uploaded at this stage.');
        redirect('author/manuscript.php?id=' . $manuscriptId);
    }

    try {
        $revisionFile = storeUploadedFile($_FILES['revision_file'] ?? null, 'manuscripts');
        if ($revisionFile === null) {
            flash('warning', 'Please attach the revised manuscript file.');
        } else {
            $version = (int) $manuscript['revision_round'] + 1;
            $pdo->prepare(
                'INSERT INTO manuscript_files (manuscript_id, file_type, original_name, stored_path, mime_type, size_bytes, version, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$manuscriptId, 'manuscript', $revisionFile[1], $revisionFile[0], $revisionFile[2], $revisionFile[3], $version, (int) $user['id']]);

            $pdo->prepare(
                'INSERT INTO revision_history (manuscript_id, version, notes, submitted_by) VALUES (?, ?, ?, ?)'
            )->execute([$manuscriptId, $version, trim((string) ($_POST['revision_notes'] ?? '')), (int) $user['id']]);

            $pdo->prepare(
                "UPDATE manuscripts SET status = 'revised', revision_round = ?, notes = ? WHERE id = ?"
            )->execute([$version, trim((string) ($_POST['revision_notes'] ?? '')), $manuscriptId]);

            $editors = $pdo->query("SELECT id FROM users WHERE role IN ('editor', 'admin') AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
            notifyMany($editors, 'revision', 'Revised manuscript received', $manuscript['reference_no'] . ' — version ' . $version . ' uploaded by ' . $user['name'], 'editor/manuscripts.php');

            audit('manuscript.revise', 'manuscript', $manuscriptId, 'Version ' . $version . ' uploaded');

            flash('success', 'Your revised manuscript has been uploaded and sent to the editorial team.');
        }
    } catch (RuntimeException $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect('author/manuscript.php?id=' . $manuscriptId);
}

$authors = manuscriptAuthors($manuscriptId);
$files = manuscriptFiles($manuscriptId);
$decisions = manuscriptDecisions($manuscriptId);
$reviews = manuscriptReviews($manuscriptId);
$timeline = manuscriptTimeline($manuscriptId);

$metaTitle = $manuscript['reference_no'];
$pageTitle = $manuscript['reference_no'];
$pageDescription = truncateText($manuscript['title'], 160);
$activePage = 'author-manuscripts';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'manuscripts'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php
        renderPageHead(
            (string) $manuscript['reference_no'],
            (string) $manuscript['title'],
            '<a class="portal-btn portal-btn--ghost" href="' . e(url('author/manuscripts.php')) . '"><ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>All manuscripts</a>'
        );
        ?>

        <div class="split-panel">
            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head">
                        <div><h2>Manuscript record</h2><p>Metadata as submitted.</p></div>
                        <div><?= statusBadge((string) $manuscript['status']) ?></div>
                    </div>
                    <div class="portal-card-body">
                        <div class="detail-grid">
                            <div class="detail-item"><span>Reference</span><strong><?= e($manuscript['reference_no']) ?></strong></div>
                            <div class="detail-item"><span>Article type</span><strong><?= e($manuscript['article_type'] ?? '—') ?></strong></div>
                            <div class="detail-item"><span>Subject</span><strong><?= e($manuscript['subject'] ?? '—') ?></strong></div>
                            <div class="detail-item"><span>Language</span><strong><?= e($manuscript['language'] ?? 'English') ?></strong></div>
                            <div class="detail-item"><span>Submitted</span><strong><?= e(formatDateTime($manuscript['submitted_at'])) ?></strong></div>
                            <div class="detail-item"><span>Revision round</span><strong><?= e((string) $manuscript['revision_round']) ?></strong></div>
                        </div>

                        <h3 style="margin:24px 0 8px;font-size:14px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Abstract</h3>
                        <p style="margin:0;font-size:14px;line-height:1.7"><?= e($manuscript['abstract']) ?></p>

                        <h3 style="margin:22px 0 8px;font-size:14px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Keywords</h3>
                        <p style="margin:0;font-size:14px"><?= e($manuscript['keywords']) ?></p>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Authors</h2><p>Listed in order of authorship.</p></div></div>
                    <div class="portal-card-body">
                        <ul class="file-list">
                            <?php foreach ($authors as $author): ?>
                                <li>
                                    <ion-icon name="person-outline" aria-hidden="true"></ion-icon>
                                    <div>
                                        <span class="file-name"><?= e($author['name']) ?><?= $author['is_corresponding'] ? ' <span class="badge badge--info">Corresponding</span>' : '' ?></span>
                                        <span class="file-meta"><?= e($author['email'] ?? 'No email') ?><?= $author['affiliation'] ? ' · ' . e($author['affiliation']) : '' ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
        </section>

                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Files</h2><p>Only you and the editorial team can access these files.</p></div></div>
                    <div class="portal-card-body">
                        <ul class="file-list">
                            <?php if (!$files): ?>
                                <li><ion-icon name="folder-open-outline" aria-hidden="true"></ion-icon><div><span class="file-name">No files attached</span></div></li>
                            <?php endif; ?>
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <ion-icon name="document-attach-outline" aria-hidden="true"></ion-icon>
                                    <div>
                                        <span class="file-name"><?= e($file['original_name']) ?> <span class="badge"><?= e(str_replace('_', ' ', $file['file_type'])) ?> v<?= e((string) $file['version']) ?></span></span>
                                        <span class="file-meta"><?= e(formatBytes((int) $file['size_bytes'])) ?> · uploaded <?= e(formatDate($file['uploaded_at'])) ?><?= $file['uploader_name'] ? ' by ' . e($file['uploader_name']) : '' ?></span>
                                    </div>
                                    <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('includes/download.php?id=' . (int) $file['id'])) ?>">Download</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <?php if ($canRevise): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><div><h2>Upload a revision</h2><p>The editor has requested changes to this manuscript.</p></div></div>
                        <div class="portal-card-body">
                            <form class="portal-form" method="post" enctype="multipart/form-data" action="<?= e(url('author/manuscript.php?id=' . $manuscriptId)) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="revise">
                                <div class="form-field">
                                    <label for="revision_file">Revised manuscript <span class="required-mark">*</span></label>
                                    <input type="file" id="revision_file" name="revision_file" accept=".pdf,.doc,.docx" required>
                                </div>
                                <div class="form-field">
                                    <label for="revision_notes">Response to reviewers <span class="hint">optional</span></label>
                                    <textarea id="revision_notes" name="revision_notes" placeholder="Summarise the changes made in response to the review reports."></textarea>
                                </div>
                                <div class="portal-actions">
                                    <button class="portal-btn" type="submit"><ion-icon name="refresh-outline" aria-hidden="true"></ion-icon>Upload revision</button>
                                </div>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head"><h2>Editorial history</h2></div>
                    <div class="portal-card-body">
                        <ul class="timeline">
                            <?php if (!$timeline): ?>
                                <li><span class="timeline-dot"><ion-icon name="time-outline" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong>No activity recorded yet</strong></div></li>
                            <?php endif; ?>
                            <?php foreach ($timeline as $event): ?>
                                <li>
                                    <span class="timeline-dot"><ion-icon name="<?= e($event['icon']) ?>" aria-hidden="true"></ion-icon></span>
                                    <div class="timeline-body">
                                        <strong><?= e($event['title']) ?></strong>
                                        <span><?= e(formatDateTime($event['at'])) ?><?= $event['detail'] ? ' · ' . e($event['detail']) : '' ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <?php if ($decisions): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><h2>Decisions</h2></div>
                        <div class="portal-card-body" style="display:flex;flex-direction:column;gap:14px">
                            <?php foreach ($decisions as $decision): ?>
                                <div>
                                    <div class="portal-actions" style="justify-content:space-between">
                                        <strong style="font-size:14px"><?= e(editorial_decisions()[$decision['decision']]['label'] ?? ucfirst($decision['decision'])) ?></strong>
                                        <span class="muted" style="font-size:12px"><?= e(formatDate($decision['created_at'])) ?></span>
                                    </div>
                                    <p style="margin:6px 0 0;font-size:13.5px;line-height:1.65;color:var(--portal-muted)"><?= e($decision['letter']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($reviews): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><h2>Reviewer reports</h2></div>
                        <div class="portal-card-body" style="display:flex;flex-direction:column;gap:14px">
                            <?php foreach ($reviews as $review): ?>
                                <div>
                                    <div class="portal-actions" style="justify-content:space-between">
                                        <strong style="font-size:14px">Round <?= e((string) $review['round']) ?> report</strong>
                                        <span class="muted" style="font-size:12px"><?= e(formatDate($review['submitted_at'])) ?></span>
                                    </div>
                                    <?php if (!empty($review['comments_for_author'])): ?>
                                        <p style="margin:6px 0 0;font-size:13.5px;line-height:1.65;color:var(--portal-muted)"><?= e($review['comments_for_author']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
