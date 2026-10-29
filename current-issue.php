<?php
require __DIR__ . '/includes/bootstrap.php';
$archiveId = filter_input(INPUT_GET, 'archive', FILTER_VALIDATE_INT);
$selectedIssue = $archiveId ? loadIssue($archiveId) : $issue;
if (!$selectedIssue) {
	http_response_code(404);
	$selectedIssue = $issue;
}
$selectedArticles = $selectedIssue ? loadIssueArticles((int) $selectedIssue['id']) : $articles;
$selectedIssueLabel = 'Vol. ' . $selectedIssue['volume'] . ' No. ' . $selectedIssue['issue_number'];
$pageTitle = 'Current issue';
$pageDescription = $selectedIssue['period'];
$metaTitle = 'Current issue — ' . $selectedIssueLabel . ' (' . $selectedIssue['period'] . ')';
$activePage = 'current-issue';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/paper-list.php';
?>
<div class="container page-body">
	<div class="issue-detail-head"><img src="<?= e($selectedIssue['cover_url']) ?>" alt="Cover of <?= e($selectedIssueLabel) ?>"><div><p class="eyebrow eyebrow-green"><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon> Published <?= e(date('F j, Y', strtotime($selectedIssue['published_at']))) ?></p><h2><?= e($selectedIssueLabel) ?></h2><p><?= e($selectedIssue['period']) ?></p><p class="issue-detail-note">Original research in science, technology and management.</p></div></div>
	<h2 class="list-heading"><span class="list-heading-label"><ion-icon name="reader-outline" aria-hidden="true"></ion-icon> In this issue</span><span><?= count($selectedArticles) ?> papers</span></h2>
	<div class="paper-list"><?php renderPaperRows($selectedArticles); ?></div>
	<a class="archive-link" href="archive.php">Back to all issues <ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon></a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>