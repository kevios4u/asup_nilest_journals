<?php
require __DIR__ . '/includes/bootstrap.php';
$articleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$article = $articleId ? loadArticle($articleId) : null;
if (!$article) {
	http_response_code(404);
}
$pageTitle = $article['title'] ?? 'Article not found';
$pageDescription = $article ? 'Article details, citation and PDF for ' . $article['title'] : 'This journal article could not be found.';
$metaTitle = $article ? truncateTitle($article['title']) : 'Article not found';
$articleSummaries = [
	1 => 'This study evaluates dual compatibilisation of luffa sponge and recycled low-density polyethylene composites. Testing found improved mechanical performance and reduced water absorption compared with single- and uncompatibilised composites.',
	2 => 'This study assesses the antifungal activity of methanol extracts from three plant species against Malassezia furfur. The individual extracts showed stronger potential than combined extracts, with further in-vivo research recommended.',
	3 => 'The authors examine mechanical and abrasion performance in cow-horn fibre and recycled-polyethylene composites. Their findings indicate that fibre and compatibilisers can improve hardness and abrasion resistance.',
	4 => 'Using a manufacturing-defects dataset as a proxy for tannery processes, this study evaluates a neural-network classifier and interpretable feature analysis for quality-assurance applications.',
	5 => 'This research compares animal- and plant-fibre biofillers in recycled polyethylene. It reports how fibre choice, filler loading and compatibilisers affect water absorption and chemical resistance.',
	6 => 'This study characterises antibiotic resistance in Vibrio isolates from the Wupa wastewater system and river in Abuja, reporting multidrug resistance and recommending expanded environmental surveillance.',
	7 => 'A molecular-docking study investigates compounds from Parkia biglobosa and their interactions with collagen, examining the plant extract’s potential as a lower-impact raw-hide preservative.',
];
require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<?php if (!$article): ?>
		<section class="page-content"><p class="eyebrow eyebrow-green">Page unavailable</p><h2>We couldn’t find that article.</h2><p>It may have moved, or it may not be part of the local journal database.</p><a class="button button-green" href="current-issue.php">Return to current issue</a></section>
	<?php else: ?>
		<?php $articleIssueLabel = 'Vol. ' . $article['issue_volume'] . ' No. ' . $article['issue_number']; ?>
		<div class="article-layout"><article class="page-content"><p class="eyebrow eyebrow-green"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon> <?= e($article['category']) ?> · Original research</p><h2 class="article-title"><?= e($article['title']) ?></h2><p class="article-authors"><?= e($article['authors']) ?></p><div class="article-meta"><span><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon><?= e($articleIssueLabel) ?></span><span><ion-icon name="reader-outline" aria-hidden="true"></ion-icon>Pages <?= e($article['pages']) ?></span><span><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon>Published <?= e(date('F j, Y', strtotime($article['published_at']))) ?></span></div><h2>Abstract</h2><p class="page-lead"><?= e($articleSummaries[(int) $article['id']] ?? 'This peer-reviewed study contributes new research in ' . strtolower($article['category']) . '.') ?></p><p>This article appears in <?= e($article['issue_period']) ?> in the NILEST Journal of Science, Technology &amp; Management. The full text is available as an open-access PDF.</p><a class="button button-green" href="<?= e($article['pdf_url']) ?>" target="_blank" rel="noreferrer">Download article PDF <ion-icon name="download-outline" aria-hidden="true"></ion-icon></a><h2>How to cite</h2><p class="citation"><?= e($article['authors']) ?> (<?= e(date('Y', strtotime($article['published_at']))) ?>). <?= e($article['title']) ?>. <em>NILEST Journal of Science, Technology &amp; Management, <?= e($article['issue_volume']) ?></em>(<?= e($article['issue_number']) ?>), <?= e($article['pages']) ?>.</p><p class="license-note">This work is licensed under a Creative Commons Attribution 4.0 International License.</p></article><aside class="page-aside"><p class="eyebrow eyebrow-green"><ion-icon name="information-circle-outline" aria-hidden="true"></ion-icon> Article details</p><dl class="journal-facts"><div><dt><ion-icon name="layers-outline" aria-hidden="true"></ion-icon>Section</dt><dd>Original research papers</dd></div><div><dt><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon>Issue</dt><dd><?= e($articleIssueLabel) ?></dd></div><div><dt><ion-icon name="reader-outline" aria-hidden="true"></ion-icon>Pages</dt><dd><?= e($article['pages']) ?></dd></div><div><dt><ion-icon name="lock-open-outline" aria-hidden="true"></ion-icon>License</dt><dd>Creative Commons Attribution 4.0</dd></div></dl><a class="aside-action" href="current-issue.php?archive=<?= (int) $article['issue_id'] ?>">Return to issue <ion-icon name="arrow-back-outline" aria-hidden="true"></ion-icon></a></aside></div>
	<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>