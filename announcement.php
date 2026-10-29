<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'From the editors';
$pageDescription = 'Announcements and special issue information from NIJOSTAM.';
$metaTitle = 'Announcements from the editors';
require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<article class="page-content narrow-content"><p class="eyebrow eyebrow-green"><ion-icon name="megaphone-outline" aria-hidden="true"></ion-icon> Special issue · Book project · <?= e($announcementDate) ?></p><h2><?= e($announcement['title']) ?></h2><p class="page-lead">A scholarly book project on building a more sustainable and internationally competitive Nigerian leather value chain.</p><p><?= e($announcement['summary']) ?></p><h2 class="section-title"><ion-icon name="sparkles-outline" aria-hidden="true"></ion-icon> Topics in focus</h2><ul class="icon-list"><li><ion-icon name="leaf-outline" aria-hidden="true"></ion-icon>Sustainable leather manufacturing and cleaner processing</li><li><ion-icon name="flash-outline" aria-hidden="true"></ion-icon>Innovative technologies and environmental management</li><li><ion-icon name="stats-chart-outline" aria-hidden="true"></ion-icon>Market development, investment and policy frameworks</li><li><ion-icon name="globe-outline" aria-hidden="true"></ion-icon>Strategies to strengthen Nigeria’s leather value chain</li></ul><p>The project is intended for academics, industry practitioners, policymakers, investors and development agencies.</p><a class="button button-green" href="authors.php">Author information <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a></article>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>