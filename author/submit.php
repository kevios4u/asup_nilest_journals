<?php

/**
 * New manuscript submission.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';

requireRole('author', 'admin');

$user = currentUser();
$pdo = db();

if (setting('submission_open', '1') !== '1' && $user['role'] !== 'admin') {
    flash('warning', 'The journal is not accepting new submissions at the moment.');
    redirect('author/dashboard.php');
}

$errors = [];
$values = [
    'title' => trim((string) ($_POST['title'] ?? '')),
    'abstract' => trim((string) ($_POST['abstract'] ?? '')),
    'keywords' => trim((string) ($_POST['keywords'] ?? '')),
    'subject' => trim((string) ($_POST['subject'] ?? '')),
    'article_type' => trim((string) ($_POST['article_type'] ?? '')),
    'language' => trim((string) ($_POST['language'] ?? 'English')),
    'cover_letter' => trim((string) ($_POST['cover_letter'] ?? '')),
];

$coAuthors = [];
if (isset($_POST['co_author_name']) && is_array($_POST['co_author_name'])) {
    foreach ($_POST['co_author_name'] as $index => $name) {
        $coAuthors[] = [
            'name' => trim((string) $name),
            'email' => trim((string) ($_POST['co_author_email'][$index] ?? '')),
            'affiliation' => trim((string) ($_POST['co_author_affiliation'][$index] ?? '')),
        ];
    }
}
$coAuthors = array_values(array_filter($coAuthors, static fn(array $row): bool => $row['name'] !== ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }

    if (mb_strlen($values['title']) < 10) {
        $errors[] = 'The manuscript title must be at least 10 characters long.';
    }
    if (mb_strlen($values['abstract']) < 100) {
        $errors[] = 'The abstract must be at least 100 characters long.';
    }
    if ($values['keywords'] === '') {
        $errors[] = 'Please provide at least three keywords, separated by commas.';
    }
    if ($values['subject'] === '') {
        $errors[] = 'Please select the subject area.';
    }
    if ($values['article_type'] === '') {
        $errors[] = 'Please select the article type.';
    }
    if (!isset($_POST['agreement'])) {
        $errors[] = 'Please confirm the originality and authorship declaration.';
    }

    $manuscriptFile = null;
    $coverLetterFile = null;
    $manuscriptUploadError = false;

    try {
        $manuscriptFile = storeUploadedFile($_FILES['manuscript_file'] ?? null, 'manuscripts');
    } catch (RuntimeException $exception) {
        $manuscriptUploadError = true;
        $errors[] = $exception->getMessage();
    }

    try {
        $coverLetterFile = storeUploadedFile($_FILES['cover_letter_file'] ?? null, 'cover-letters');
    } catch (RuntimeException $exception) {
        $errors[] = $exception->getMessage();
    }

    if ($manuscriptFile === null && !$manuscriptUploadError) {
        $errors[] = 'Please attach the manuscript file.';
    }

    foreach ($coAuthors as $coAuthor) {
        if ($coAuthor['email'] !== '' && !filter_var($coAuthor['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Co-author email "' . $coAuthor['email'] . '" is not valid.';
        }
    }

    if (!$errors && $pdo instanceof PDO) {
        $reference = manuscriptReference($pdo);

        $statement = $pdo->prepare(
            "INSERT INTO manuscripts (reference_no, title, abstract, keywords, subject, article_type, language, status, submitted_by, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())"
        );
        $statement->execute([
            $reference,
            $values['title'],
            $values['abstract'],
            $values['keywords'],
            $values['subject'],
            $values['article_type'],
            $values['language'] !== '' ? $values['language'] : 'English',
            (int) $user['id'],
        ]);
        $manuscriptId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO manuscript_authors (manuscript_id, user_id, name, email, affiliation, is_corresponding, author_order)
             VALUES (?, ?, ?, ?, ?, 1, 1)'
        )->execute([
            $manuscriptId,
            (int) $user['id'],
            $user['name'],
            $user['email'],
            null,
        ]);

        $order = 2;
        foreach ($coAuthors as $coAuthor) {
            $pdo->prepare(
                'INSERT INTO manuscript_authors (manuscript_id, user_id, name, email, affiliation, is_corresponding, author_order)
                 VALUES (?, NULL, ?, ?, ?, 0, ?)'
            )->execute([$manuscriptId, $coAuthor['name'], $coAuthor['email'] !== '' ? $coAuthor['email'] : null, $coAuthor['affiliation'] !== '' ? $coAuthor['affiliation'] : null, $order]);
            $order++;
        }

        if ($manuscriptFile !== null) {
            $pdo->prepare(
                'INSERT INTO manuscript_files (manuscript_id, file_type, original_name, stored_path, mime_type, size_bytes, version, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
            )->execute([$manuscriptId, 'manuscript', $manuscriptFile[1], $manuscriptFile[0], $manuscriptFile[2], $manuscriptFile[3], (int) $user['id']]);
        }

        if ($coverLetterFile !== null) {
            $pdo->prepare(
                'INSERT INTO manuscript_files (manuscript_id, file_type, original_name, stored_path, mime_type, size_bytes, version, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
            )->execute([$manuscriptId, 'cover_letter', $coverLetterFile[1], $coverLetterFile[0], $coverLetterFile[2], $coverLetterFile[3], (int) $user['id']]);
        }

        $pdo->prepare('INSERT INTO revision_history (manuscript_id, version, notes, submitted_by) VALUES (?, 1, ?, ?)')
            ->execute([$manuscriptId, 'Initial submission.', (int) $user['id']]);

        $editorStatement = $pdo->query("SELECT id FROM users WHERE role IN ('editor', 'admin') AND status = 'active'");
        notifyMany($editorStatement->fetchAll(PDO::FETCH_COLUMN), 'submission', 'New manuscript submitted', $reference . ' — ' . truncateText($values['title'], 120), 'editor/manuscripts.php');

        audit('manuscript.submit', 'manuscript', $manuscriptId, $reference . ' submitted');

        flash('success', 'Your manuscript has been submitted with reference number ' . $reference . '.');
        redirect('author/manuscript.php?id=' . $manuscriptId);
    }

    if ($manuscriptFile !== null) {
        deleteStoredFile($manuscriptFile[0]);
    }
    if ($coverLetterFile !== null) {
        deleteStoredFile($coverLetterFile[0]);
    }
}

$metaTitle = 'Submit a manuscript';
$pageTitle = 'Submit a manuscript';
$pageDescription = 'Upload a new manuscript to the NIJOSTAM editorial system.';
$activePage = 'author-submit';
$isPortal = true;

require __DIR__ . '/../includes/header.php';
?>

<div class="portal-shell">
    <?php renderPortalSidebar($user, 'submit'); ?>

    <div class="portal-main">
        <?php renderFlash(); ?>

        <?php renderPageHead('Submit a manuscript', 'Complete the metadata, add your co-authors, and upload the manuscript file.'); ?>

        <?php if ($errors): ?>
            <div class="alert-stack">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert--danger"><ion-icon name="close-circle-outline" aria-hidden="true"></ion-icon><span><?= e($error) ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="portal-form" method="post" enctype="multipart/form-data" action="<?= e(url('author/submit.php')) ?>" novalidate>
            <?= csrfField() ?>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Manuscript details</h2><p>These details appear on the article page once published.</p></div></div>
                <div class="portal-card-body portal-form">
                    <div class="form-field">
                        <label for="title">Title <span class="required-mark">*</span></label>
                        <input type="text" id="title" name="title" value="<?= e($values['title']) ?>" required maxlength="600">
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="article_type">Article type <span class="required-mark">*</span></label>
                            <select id="article_type" name="article_type" required>
                                <option value="">Select an article type</option>
                                <?php foreach (article_types() as $type): ?>
                                    <option value="<?= e($type) ?>"<?= $values['article_type'] === $type ? ' selected' : '' ?>><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="subject">Subject area <span class="required-mark">*</span></label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a subject area</option>
                                <?php foreach (subjects() as $subject): ?>
                                    <option value="<?= e($subject) ?>"<?= $values['subject'] === $subject ? ' selected' : '' ?>><?= e($subject) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="language">Language</label>
                            <select id="language" name="language">
                                <?php foreach (['English', 'French', 'Other'] as $language): ?>
                                    <option value="<?= e($language) ?>"<?= $values['language'] === $language ? ' selected' : '' ?>><?= e($language) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="abstract">Abstract <span class="required-mark">*</span></label>
                        <textarea id="abstract" name="abstract" required placeholder="A single paragraph describing the background, method, results, and conclusion of the study."><?= e($values['abstract']) ?></textarea>
                        <span class="hint">Minimum 100 characters. The abstract is not edited after acceptance without approval.</span>
                    </div>

                    <div class="form-field">
                        <label for="keywords">Keywords <span class="required-mark">*</span></label>
                        <input type="text" id="keywords" name="keywords" value="<?= e($values['keywords']) ?>" placeholder="leather processing, sustainability, water management" required>
                        <span class="hint">Separate keywords with commas.</span>
                    </div>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Authors</h2><p>You are listed as the corresponding author.</p></div></div>
                <div class="portal-card-body portal-form">
                    <div class="author-rows">
                        <div class="author-row">
                            <div class="form-field"><label>Name</label><input type="text" value="<?= e($user['name']) ?>" disabled></div>
                            <div class="form-field"><label>Email</label><input type="text" value="<?= e($user['email']) ?>" disabled></div>
                            <div class="form-field"><label>Role</label><input type="text" value="Corresponding author" disabled></div>
                            <span></span>
                        </div>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <?php $coAuthor = $coAuthors[$i] ?? ['name' => '', 'email' => '', 'affiliation' => '']; ?>
                            <div class="author-row">
                                <div class="form-field"><label>Co-author name</label><input type="text" name="co_author_name[]" value="<?= e($coAuthor['name']) ?>"></div>
                                <div class="form-field"><label>Email</label><input type="email" name="co_author_email[]" value="<?= e($coAuthor['email']) ?>"></div>
                                <div class="form-field"><label>Affiliation</label><input type="text" name="co_author_affiliation[]" value="<?= e($coAuthor['affiliation']) ?>"></div>
                                <span></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <p class="hint">Leave a row blank to skip it. You can add or amend co-authors until the manuscript is accepted.</p>
                </div>
            </section>

            <section class="portal-card">
                <div class="portal-card-head"><div><h2>Files</h2><p>Maximum <?= e((string) setting('max_file_size_mb', 10)) ?> MB per file. PDF, DOC, or DOCX.</p></div></div>
                <div class="portal-card-body portal-form">
                    <div class="form-field">
                        <label for="manuscript_file">Manuscript file <span class="required-mark">*</span></label>
                        <input type="file" id="manuscript_file" name="manuscript_file" accept=".pdf,.doc,.docx" required>
                    </div>
                    <div class="form-field">
                        <label for="cover_letter_file">Cover letter <span class="hint">optional</span></label>
                        <input type="file" id="cover_letter_file" name="cover_letter_file" accept=".pdf,.doc,.docx">
                    </div>
                    <div class="form-field">
                        <label for="cover_letter">Cover letter message <span class="hint">optional</span></label>
                        <textarea id="cover_letter" name="cover_letter" placeholder="Briefly describe the contribution and any related manuscripts."><?= e($values['cover_letter']) ?></textarea>
                    </div>
                    <div class="form-field">
                        <label style="flex-direction:row;align-items:flex-start;gap:9px;display:flex">
                            <input type="checkbox" name="agreement" value="1" style="width:auto;margin-top:3px"<?= isset($_POST['agreement']) ? ' checked' : '' ?>>
                            <span>I confirm that this manuscript is original, is not under review elsewhere, and that all listed authors approve its submission to NIJOSTAM.</span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="portal-actions">
                <button class="portal-btn" type="submit"><ion-icon name="send-outline" aria-hidden="true"></ion-icon>Submit manuscript</button>
                <a class="portal-btn portal-btn--ghost" href="<?= e(url('author/dashboard.php')) ?>">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
