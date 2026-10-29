<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/paper-list.php';
$category = trim((string) ($_GET['name'] ?? 'Science'));
$allowedCategories = ['Science', 'Technology', 'Management', 'Engineering'];
if (!in_array($category, $allowedCategories, true)) {
	$category = 'Science';
}
$pageTitle = $category;
$pageDescription = 'Research tagged under ' . $category . ' across NIJOSTAM issues.';
$metaTitle = $category . ' articles — research filed under ' . $category;
$allArticles = loadAllArticles();
$categoryArticles = array_values(array_filter($allArticles, static function (array $article) use ($category): bool {
	if ($category === 'Science') {
		return $article['category'] !== 'Management';
	}
	if ($category === 'Engineering') {
		return in_array($article['category'], ['Engineering', 'Materials science', 'Leather science'], true);
	}
	return $article['category'] === $category;
}));
require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<p class="page-lead">Browse <?= count($categoryArticles) ?> papers filed under <?= e($category) ?> across the journal archive.</p>
	<div class="paper-list"><?php renderPaperRows($categoryArticles); ?></div>
	<a class="archive-link" href="archive.php">Browse all issues <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>