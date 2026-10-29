<?php

/**
 * Editor workspace for a single manuscript: screening, reviewer assignment,
 * decisions and correspondence.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('editor', 'admin');

$user = currentUser();
$pdo = db();
$manuscriptId = (int) ($_GET['id'] ?? 0);
$manuscript = $manuscriptId > 0 ? loadManuscript($manuscriptId) : null;

if (!$manuscript) {
    http_response_code(404);
    $metaTitle = 'Manuscript not found';
    $pageTitle = 'Manuscript not found';
    $pageDescription = 'The requested manuscript could not be found.';
    $activePage = 'editor-manuscripts';
    $isPortal = true;
    require __DIR__ . '/../includes/header.php';
    ?>
    <div class="portal-shell">
        <?php renderPortalSidebar($user, 'manuscripts'); ?>
        <div class="portal-main"><div class="portal-card"><div class="empty-state">
            <ion-icon name="document-outline" aria-hidden="true"></ion-icon>
            <h1>Manuscript not found</h1>
            <p>This manuscript does not exist or has been removed.</p>
            <a class="portal-btn" href="<?= e(url('editor/manuscripts.php')) ?>">Back to manuscripts</a>
        </div></div></div>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$editorId = (int) $user['id'];
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Your session expired. Please try again.');
        redirect('editor/manuscript.php?id=' . $manuscriptId);
    }

    switch ($action) {
        case 'claim':
            $exists = $pdo->prepare('SELECT COUNT(*) FROM editor_assignments WHERE manuscript_id = ? AND editor_id = ?');
            $exists->execute([$manuscriptId, $editorId]);
            if ((int) $exists->fetchColumn() === 0) {
                $pdo->prepare('INSERT INTO editor_assignments (manuscript_id, editor_id, assigned_by) VALUES (?, ?, ?)')
                    ->execute([$manuscriptId, $editorId, $editorId]);
                audit('editor.assign', 'manuscript', $manuscriptId, 'Editor claimed ' . $manuscript['reference_no']);
            }
            if ($manuscript['status'] === 'submitted') {
                $pdo->prepare("UPDATE manuscripts SET status = 'under_screening', screened_by = ? WHERE id = ?")
                    ->execute([$editorId, $manuscriptId]);
            }
            flash('success', 'You are now the handling editor for ' . $manuscript['reference_no'] . '.');
            break;

        case 'screen':
            $pdo->prepare(
                "UPDATE manuscripts SET status = 'awaiting_reviewer', screened_at = NOW(), screened_by = ?, notes = ? WHERE id = ?"
            )->execute([$editorId, trim((string) ($_POST['screening_notes'] ?? '')), $manuscriptId]);
            $pdo->prepare("UPDATE editor_assignments SET screening_status = 'completed', screening_notes = ? WHERE manuscript_id = ? AND editor_id = ?")
                ->execute([trim((string) ($_POST['screening_notes'] ?? '')), $manuscriptId, $editorId]);
            audit('editor.screen', 'manuscript', $manuscriptId, 'Screening completed for ' . $manuscript['reference_no']);
            flash('success', 'Screening recorded. The manuscript is now awaiting reviewer assignment.');
            break;

        case 'assign_reviewer':
            $reviewerId = (int) ($_POST['reviewer_id'] ?? 0);
            if ($reviewerId <= 0) {
                flash('danger', 'Select a reviewer to assign.');
                break;
            }
            $duplicate = $pdo->prepare(
                'SELECT COUNT(*) FROM reviewer_assignments WHERE manuscript_id = ? AND reviewer_id = ?'
            );
            $duplicate->execute([$manuscriptId, $reviewerId]);
            if ((int) $duplicate->fetchColumn() > 0) {
                flash('warning', 'That reviewer is already assigned to this manuscript.');
                break;
            }
            $dueDays = (int) setting('review_period_days', 21);
            $pdo->prepare(
                "INSERT INTO reviewer_assignments (manuscript_id, reviewer_id, editor_id, status, due_at) VALUES (?, ?, ?, 'invited', DATE_ADD(NOW(), INTERVAL ? DAY))"
            )->execute([$manuscriptId, $reviewerId, $editorId, $dueDays]);

            $reviewer = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
            $reviewer->execute([$reviewerId]);
            $reviewerRow = $reviewer->fetch();
            if ($reviewerRow) {
                notify((int) $reviewerId, 'review_invitation', 'Review invitation', $manuscript['reference_no'] . ' — ' . truncateText($manuscript['title'], 120), 'reviewer/reviews.php');
            }

            if (in_array($manuscript['status'], ['submitted', 'under_screening', 'awaiting_reviewer'], true)) {
                $pdo->prepare("UPDATE manuscripts SET status = 'under_review' WHERE id = ?")->execute([$manuscriptId]);
            }
            audit('editor.assign_reviewer', 'manuscript', $manuscriptId, 'Reviewer assigned to ' . $manuscript['reference_no']);
            flash('success', 'Reviewer invitation sent.');
            break;

        case 'remind_reviewer':
            $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
            $statement = $pdo->prepare(
                'SELECT ra.reviewer_id, ra.status, m.reference_no, m.title FROM reviewer_assignments ra
                 INNER JOIN manuscripts m ON m.id = ra.manuscript_id WHERE ra.id = ?'
            );
            $statement->execute([$assignmentId]);
            $assignment = $statement->fetch();
            if ($assignment) {
                notify((int) $assignment['reviewer_id'], 'review_reminder', 'Reminder: review still outstanding', $assignment['reference_no'] . ' — ' . truncateText($assignment['title'], 120), 'reviewer/reviews.php');
                audit('editor.remind', 'manuscript', $manuscriptId, 'Reminder sent for ' . $assignment['reference_no']);
                flash('success', 'Reminder sent to the reviewer.');
            }
            break;

        case 'decision':
            $decision = (string) ($_POST['decision'] ?? '');
            if (!array_key_exists($decision, editorial_decisions())) {
                flash('danger', 'Choose a valid editorial decision.');
                break;
            }
            $letter = trim((string) ($_POST['letter'] ?? ''));
            if ($letter === '') {
                flash('danger', 'A decision letter is required.');
                break;
            }

            $nextStatus = editorial_decisions()[$decision]['next_status'];
            $pdo->beginTransaction();
            try {
                $pdo->prepare(
                    'INSERT INTO editorial_decisions (manuscript_id, editor_id, decision, letter) VALUES (?, ?, ?, ?)'
                )->execute([$manuscriptId, $editorId, $decision, $letter]);
                $pdo->prepare(
                    'UPDATE manuscripts SET status = ?, decision = ?, decided_at = NOW(), decided_by = ? WHERE id = ?'
                )->execute([$nextStatus, $decision, $editorId, $manuscriptId]);
                $pdo->commit();
            } catch (PDOException $exception) {
                $pdo->rollBack();
                flash('danger', 'The decision could not be saved. Please try again.');
                break;
            }

            $authorId = (int) $manuscript['submitted_by'];
            notify($authorId, 'decision', 'Editorial decision: ' . editorial_decisions()[$decision]['label'], truncateText($letter, 240), 'author/manuscript.php?id=' . $manuscriptId);
            audit('editor.decision', 'manuscript', $manuscriptId, editorial_decisions()[$decision]['label'] . ' for ' . $manuscript['reference_no']);
            flash('success', 'Decision recorded and the author has been notified.');
            break;

        case 'to_production':
            $pdo->prepare("UPDATE manuscripts SET status = 'in_production' WHERE id = ?")->execute([$manuscriptId]);
            notify((int) $manuscript['submitted_by'], 'production', 'Manuscript moved to production', $manuscript['reference_no'] . ' has entered copyediting and production.', 'author/manuscript.php?id=' . $manuscriptId);
            audit('editor.production', 'manuscript', $manuscriptId, 'Moved to production: ' . $manuscript['reference_no']);
            flash('success', 'The manuscript has been moved to copyediting and production.');
            break;

        case 'message_author':
            $message = trim((string) ($_POST['message'] ?? ''));
            if ($message === '') {
                flash('warning', 'Enter a message before sending.');
                break;
            }
            notify((int) $manuscript['submitted_by'], 'correspondence', 'Message from the editorial office', $message, 'author/manuscript.php?id=' . $manuscriptId);
            audit('editor.message', 'manuscript', $manuscriptId, 'Correspondence sent for ' . $manuscript['reference_no']);
            flash('success', 'Message sent to the corresponding author.');
            break;
    }

    redirect('editor/manuscript.php?id=' . $manuscriptId);
}

$authors = manuscriptAuthors($manuscriptId);
$files = manuscriptFiles($manuscriptId);
$editors = manuscriptEditors($manuscriptId);
$reviewers = manuscriptReviewers($manuscriptId);
$reviews = manuscriptReviews($manuscriptId, true);
$decisions = manuscriptDecisions($manuscriptId);
$timeline = manuscriptTimeline($manuscriptId);

$availableReviewers = [];
if ($pdo instanceof PDO) {
    $statement = $pdo->query("SELECT id, name, email FROM users WHERE role = 'reviewer' AND status = 'active' ORDER BY name");
    $availableReviewers = $statement->fetchAll();
}

$isAssignedEditor = false;
foreach ($editors as $editor) {
    if ((int) $editor['editor_id'] === $editorId) {
        $isAssignedEditor = true;
    }
}

$metaTitle = $manuscript['reference_no'];
$pageTitle = $manuscript['reference_no'];
$pageDescription = truncateText($manuscript['title'], 160);
$activePage = 'editor-manuscripts';
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
            '<a class="portal-btn portal-btn--ghost" href="' . e(url('editor/manuscripts.php')) . '"><ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon>All manuscripts</a>'
        );
        ?>

        <div class="portal-actions">
            <?= statusBadge((string) $manuscript['status']) ?>
            <span class="badge badge--info"><?= e($manuscript['article_type'] ?? '—') ?></span>
            <span class="badge"><?= e($manuscript['subject'] ?? '—') ?></span>
            <span class="badge"><?= e(formatDate($manuscript['submitted_at'])) ?></span>
        </div>

        <div class="split-panel">
            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Manuscript</h2><p>Submitted by <?= e($manuscript['submitter_name']) ?> (<?= e($manuscript['submitter_email']) ?>)</p></div></div>
                    <div class="portal-card-body">
                        <h3 style="margin:0 0 12px;font-size:17px;line-height:1.4"><?= e($manuscript['title']) ?></h3>
                        <p style="margin:0 0 18px;font-size:14px;line-height:1.75;color:var(--portal-muted)"><?= e($manuscript['abstract']) ?></p>
                        <p style="margin:0;font-size:13.5px"><strong>Keywords:</strong> <?= e($manuscript['keywords']) ?></p>

                        <h3 style="margin:22px 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Authors</h3>
                        <ul class="file-list">
                            <?php foreach ($authors as $author): ?>
                                <li><ion-icon name="person-outline" aria-hidden="true"></ion-icon><div><span class="file-name"><?= e($author['name']) ?><?= $author['is_corresponding'] ? ' <span class="badge badge--info">Corresponding</span>' : '' ?></span><span class="file-meta"><?= e($author['email'] ?? '—') ?><?= $author['affiliation'] ? ' · ' . e($author['affiliation']) : '' ?></span></div></li>
                            <?php endforeach; ?>
                        </ul>

                        <h3 style="margin:22px 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.07em;color:var(--portal-muted)">Files</h3>
                        <ul class="file-list">
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <ion-icon name="document-attach-outline" aria-hidden="true"></ion-icon>
                                    <div><span class="file-name"><?= e($file['original_name']) ?> <span class="badge"><?= e(str_replace('_', ' ', $file['file_type'])) ?> v<?= e((string) $file['version']) ?></span></span><span class="file-meta"><?= e(formatBytes((int) $file['size_bytes'])) ?> · <?= e(formatDate($file['uploaded_at'])) ?></span></div>
                                    <a class="portal-btn portal-btn--ghost portal-btn--sm" href="<?= e(url('includes/download.php?id=' . (int) $file['id'])) ?>">Download</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Reviewer assignments</h2><p>Reviewers receive a secure invitation and report through the portal.</p></div></div>
                    <div class="portal-card-body" style="display:flex;flex-direction:column;gap:16px">
                        <?php if ($reviewers): ?>
                            <div class="portal-table-wrap">
                                <table class="portal-table">
                                    <thead><tr><th>Reviewer</th><th>Status</th><th>Due</th><th>Recommendation</th><th></th></tr></thead>
                                    <tbody>
                                    <?php foreach ($reviewers as $reviewer): ?>
                                        <tr>
                                            <td><span class="title-cell"><?= e($reviewer['reviewer_name']) ?></span><span class="muted"><?= e($reviewer['reviewer_email']) ?></span></td>
                                            <td><span class="badge badge--<?= $reviewer['status'] === 'completed' ? 'good' : ($reviewer['status'] === 'declined' ? 'bad' : ($reviewer['status'] === 'accepted' ? 'info' : 'warn')) ?>"><?= e(ucfirst($reviewer['status'])) ?></span></td>
                                            <td><span class="muted"><?= e(formatDate($reviewer['due_at'])) ?></span></td>
                                            <td><span class="muted"><?= e($reviewer['recommendation'] ? ucfirst(str_replace('_', ' ', $reviewer['recommendation'])) : '—') ?></span></td>
                                            <td>
                                                <?php if ($reviewer['status'] === 'invited'): ?>
                                                    <form method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="remind_reviewer">
                                                        <input type="hidden" name="assignment_id" value="<?= e((string) $reviewer['id']) ?>">
                                                        <button class="portal-btn portal-btn--ghost portal-btn--sm" type="submit">Remind</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="muted" style="margin:0;font-size:14px">No reviewers assigned yet.</p>
                        <?php endif; ?>

                        <form class="portal-form" method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="assign_reviewer">
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="reviewer_id">Assign a reviewer</label>
                                    <select id="reviewer_id" name="reviewer_id" required>
                                        <option value="">Select a reviewer</option>
                                        <?php foreach ($availableReviewers as $reviewer): ?>
                                            <option value="<?= e((string) $reviewer['id']) ?>"><?= e($reviewer['name']) ?> — <?= e($reviewer['email']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>&nbsp;</label>
                                    <button class="portal-btn" type="submit"><ion-icon name="person-add-outline" aria-hidden="true"></ion-icon>Send invitation</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><div><h2>Peer review reports</h2><p>Reports shared with the editorial team.</p></div></div>
                    <div class="portal-card-body" style="display:flex;flex-direction:column;gap:16px">
                        <?php if (!$reviews): ?>
                            <p class="muted" style="margin:0;font-size:14px">No review reports submitted yet.</p>
                        <?php endif; ?>
                        <?php foreach ($reviews as $review): ?>
                            <div style="border:1px solid var(--portal-border);border-radius:12px;padding:16px">
                                <div class="portal-actions" style="justify-content:space-between">
                                    <strong style="font-size:14px"><?= e($review['reviewer_name']) ?> — round <?= e((string) $review['round']) ?></strong>
                                    <span class="badge badge--<?= e(editorial_decisions()[$review['recommendation']]['tone'] ?? 'info') ?>"><?= e(review_recommendations()[$review['recommendation']] ?? '—') ?></span>
                                </div>
                                <?php if (!empty($review['comments_for_author'])): ?>
                                    <p style="margin:10px 0 0;font-size:13.5px;line-height:1.7"><strong>For the author:</strong> <?= e($review['comments_for_author']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($review['comments_for_editor'])): ?>
                                    <p style="margin:8px 0 0;font-size:13.5px;line-height:1.7;color:var(--portal-muted)"><strong>Confidential note to the editor:</strong> <?= e($review['comments_for_editor']) ?></p>
                                <?php endif; ?>
                                <span class="muted" style="font-size:12px">Submitted <?= e(formatDateTime($review['submitted_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div style="display:flex;flex-direction:column;gap:22px">
                <section class="portal-card">
                    <div class="portal-card-head"><h2>Handling editor</h2></div>
                    <div class="portal-card-body">
                        <?php if ($editors): ?>
                            <ul class="file-list">
                                <?php foreach ($editors as $editor): ?>
                                    <li><ion-icon name="briefcase-outline" aria-hidden="true"></ion-icon><div><span class="file-name"><?= e($editor['editor_name']) ?></span><span class="file-meta">Assigned <?= e(formatDate($editor['assigned_at'])) ?></span></div></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="claim">
                                <button class="portal-btn portal-btn--block" type="submit"><ion-icon name="hand-left-outline" aria-hidden="true"></ion-icon>Claim this manuscript</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>Editorial screening</h2></div>
                    <div class="portal-card-body">
                        <form class="portal-form" method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="screen">
                            <div class="form-field">
                                <label for="screening_notes">Screening notes</label>
                                <textarea id="screening_notes" name="screening_notes" placeholder="Scope, ethics approval, formatting, plagiarism screening."><?= e((string) ($manuscript['notes'] ?? '')) ?></textarea>
                            </div>
                            <button class="portal-btn portal-btn--block" type="submit"><ion-icon name="checkmark-done-outline" aria-hidden="true"></ion-icon>Complete screening</button>
                        </form>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>Record decision</h2></div>
                    <div class="portal-card-body">
                        <form class="portal-form" method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="decision">
                            <div class="form-field">
                                <label for="decision">Decision</label>
                                <select id="decision" name="decision" required>
                                    <?php foreach (editorial_decisions() as $key => $config): ?>
                                        <option value="<?= e($key) ?>"><?= e($config['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="letter">Decision letter</label>
                                <textarea id="letter" name="letter" required placeholder="Explain the decision for the author."></textarea>
                            </div>
                            <button class="portal-btn portal-btn--block" type="submit"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon>Record decision</button>
                        </form>
                    </div>
                </section>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>Message the author</h2></div>
                    <div class="portal-card-body">
                        <form class="portal-form" method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="message_author">
                            <div class="form-field">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" required placeholder="Clarification request or general correspondence."></textarea>
                            </div>
                            <button class="portal-btn portal-btn--ghost portal-btn--block" type="submit"><ion-icon name="send-outline" aria-hidden="true"></ion-icon>Send message</button>
                        </form>
                    </div>
                </section>

                <?php if ($manuscript['status'] === 'accepted'): ?>
                    <section class="portal-card">
                        <div class="portal-card-head"><h2>Production</h2></div>
                        <div class="portal-card-body">
                            <form method="post" action="<?= e(url('editor/manuscript.php?id=' . $manuscriptId)) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="to_production">
                                <button class="portal-btn portal-btn--accent portal-btn--block" type="submit"><ion-icon name="construct-outline" aria-hidden="true"></ion-icon>Move to production</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="portal-card">
                    <div class="portal-card-head"><h2>History</h2></div>
                    <div class="portal-card-body">
                        <ul class="timeline">
                            <?php foreach ($timeline as $event): ?>
                                <li><span class="timeline-dot"><ion-icon name="<?= e($event['icon']) ?>" aria-hidden="true"></ion-icon></span><div class="timeline-body"><strong><?= e($event['title']) ?></strong><span><?= e(formatDateTime($event['at'])) ?></span></div></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
