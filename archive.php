<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Journal archive';
$pageDescription = 'Browse published issues of the NILEST Journal of Science, Technology & Management.';
$metaTitle = 'Journal archive — every published issue';
$activePage = 'archive';
require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<div class="archive-intro"><p class="page-lead">Browse past and current issues of NIJOSTAM.</p><p class="archive-count"><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon><?= count($archiveIssues) ?> issues <span>·</span> 2023–2026</p></div>
	<div class="archive-list">
		<?php foreach ($archiveIssues as $index => $archive): ?>
			<article class="archive-item"><span class="archive-volume"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><div><span class="paper-category">Vol. <?= e($archive['volume']) ?> · Issue <?= e($archive['issue_number']) ?><?= (int) $archive['id'] === (int) $issue['id'] ? ' · Current' : '' ?></span><h2><a href="current-issue.php?archive=<?= (int) $archive['id'] ?>"><?= e($archive['period']) ?></a></h2><p>NILEST Journal of Science, Technology &amp; Management</p></div><a class="round-link archive-open" href="current-issue.php?archive=<?= (int) $archive['id'] ?>" aria-label="Open <?= e($archive['period']) ?>"><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a></article>
		<?php endforeach; ?>
	</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>