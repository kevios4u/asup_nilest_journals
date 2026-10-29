<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About the journal';
$pageDescription = 'Scope, peer review, open access and publication policies for NIJOSTAM.';
$metaTitle = 'About NIJOSTAM — scope, peer review & open access';
$activePage = 'about';
require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<div class="page-layout"><article class="page-content">
		<p class="page-lead">The NILEST Journal of Science, Technology and Management (NIJOSTAM) is a peer-reviewed academic journal advancing knowledge in science, technology and management.</p>
		<h2 class="section-title"><ion-icon name="compass-outline" aria-hidden="true"></ion-icon> Focus and scope</h2><p>NIJOSTAM publishes original research, reviews, case studies, analytical reports and scholarly reflections. Interdisciplinary work connecting science, technology and management to practical problems is welcome.</p>
		<ul class="scope-list"><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Biological and physical sciences</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Computer science and information technologies</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Mathematics, statistics and scientific methods</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Agricultural and environmental sciences</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Engineering and applied technologies</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Management, business and decision analytics</li><li><ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>Industrial, production, financial and project management</li></ul>
		<h2 class="section-title"><ion-icon name="shield-checkmark-outline" aria-hidden="true"></ion-icon> Peer review</h2><p>Submissions undergo double-blind peer review to assess academic quality, originality, clarity and contribution to knowledge.</p>
		<h2 class="section-title"><ion-icon name="lock-open-outline" aria-hidden="true"></ion-icon> Open access and copyright</h2><p>Published articles are freely accessible. Authors retain copyright, and articles are shared under the Creative Commons Attribution 4.0 license.</p>
		<h2 class="section-title"><ion-icon name="archive-outline" aria-hidden="true"></ion-icon> Publication and preservation</h2><p>The journal publishes quarterly, with issues scheduled for March, June, September and December. Digital preservation is supported through LOCKSS and CLOCKSS.</p>
		<a class="button button-green" href="submissions.php">Read author guidelines <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
	</article><aside class="page-aside"><p class="eyebrow eyebrow-green"><ion-icon name="business-outline" aria-hidden="true"></ion-icon> Journal details</p><dl class="journal-facts"><div><dt><ion-icon name="business-outline" aria-hidden="true"></ion-icon>Publisher</dt><dd>Nigerian Institute of Leather and Science Technology</dd></div><div><dt><ion-icon name="reader-outline" aria-hidden="true"></ion-icon>eISSN</dt><dd>3027-2580</dd></div><div><dt><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon>Frequency</dt><dd>Quarterly</dd></div><div><dt><ion-icon name="lock-open-outline" aria-hidden="true"></ion-icon>Access</dt><dd>Open access</dd></div></dl><a class="aside-action" href="archive.php">Browse the archive <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a></aside></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>