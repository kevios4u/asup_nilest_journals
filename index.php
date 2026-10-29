<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/paper-list.php';

$pageTitle = 'NIJOSTAM — NILEST Journal of Science, Technology & Management';
$pageDescription = 'Peer-reviewed open-access research published quarterly by the Nigerian Institute of Leather and Science Technology.';
$activePage = 'home';
$showPageHero = false;
require __DIR__ . '/includes/header.php';

$currentPaperCount = count($articles);
$publishedPapers = count(loadAllArticles());
$publishedIssues = count($archiveIssues);
$showWelcome = isset($_GET['welcome']) && !empty($_SESSION['user']);
?>

<?php if ($showWelcome): ?>
	<div class="container welcome-note" role="status">
		<ion-icon name="checkmark-circle-outline" aria-hidden="true"></ion-icon>
		<span>Welcome<?= !empty($_SESSION['user']['name']) ? ', ' . e(explode(' ', trim((string) $_SESSION['user']['name']))[0]) : '' ?>. Your NIJOSTAM reader account is active.</span>
	</div>
<?php endif; ?>

<section class="hero">
	<div class="container hero-grid">
		<div class="hero-copy">
			<p class="eyebrow"><span class="eyebrow-rule"></span>Nigerian Institute of Leather and Science Technology</p>
			<h1>Advancing <em>Science</em>, Technology and Management.</h1>
			<p class="hero-description">NIJOSTAM is a quarterly, open-access journal publishing peer-reviewed original research across materials science, engineering, computing, environmental science and management — with a focus on the problems that matter to African industry.</p>
			<div class="hero-actions">
				<a class="button button-green" href="current-issue.php">Read the current issue <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
				<a class="text-link" href="submissions.php">Submit a paper <span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></span></a>
			</div>
			<p class="hero-index"><span>eISSN 3027-2580</span><span class="index-line" aria-hidden="true"></span><span>Published quarterly</span><span class="index-line" aria-hidden="true"></span><span>Open access · CC BY 4.0</span></p>
		</div>
		<aside class="issue-feature">
			<p class="feature-label"><span class="live-dot" aria-hidden="true"></span>Current issue</p>
			<div class="cover-frame"><img src="<?= e($issue['cover_url']) ?>" alt="Cover of <?= e($issueLabel) ?> of NIJOSTAM"></div>
			<div class="feature-caption">
				<div>
					<p class="caption-kicker"><?= e($issueLabel) ?></p>
					<h2><?= e($issue['period']) ?></h2>
					<p><?= $currentPaperCount ?> papers · Published <?= e($issueDate) ?></p>
				</div>
				<a class="round-link" href="current-issue.php" aria-label="Open the current issue"><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
			</div>
			<span class="feature-edition" aria-hidden="true">Open access</span>
		</aside>
	</div>
	<span class="hero-stamp" aria-hidden="true"><span>NIJOSTAM</span></span>
</section>

<section class="facts-strip">
	<div class="container facts-grid">
		<div class="fact"><span class="icon-tile"><ion-icon name="layers-outline" aria-hidden="true"></ion-icon></span><span class="fact-body"><span class="fact-value"><?= $publishedIssues ?></span><span class="fact-label">Issues published</span></span></div>
		<div class="fact"><span class="icon-tile"><ion-icon name="documents-outline" aria-hidden="true"></ion-icon></span><span class="fact-body"><span class="fact-value"><?= $publishedPapers ?></span><span class="fact-label">Papers published</span></span></div>
		<div class="fact"><span class="icon-tile"><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon></span><span class="fact-body"><span class="fact-value">4&times;</span><span class="fact-label">Issues per year</span></span></div>
		<div class="fact"><span class="icon-tile"><ion-icon name="lock-open-outline" aria-hidden="true"></ion-icon></span><span class="fact-body"><span class="fact-value">Open access</span><span class="fact-label">CC BY 4.0 · LOCKSS &amp; CLOCKSS</span></span></div>
	</div>
</section>

<section class="announcement-section">
	<div class="container announcement-inner">
		<p class="announcement-label"><span class="announcement-icon"><ion-icon name="megaphone-outline" aria-hidden="true"></ion-icon></span><span>From the editors<small><?= e($announcementDate) ?></small></span></p>
		<div class="announcement-copy">
			<p class="eyebrow">Special issue announcement</p>
			<h2><?= e($announcement['title']) ?></h2>
			<p><?= e($announcement['summary']) ?></p>
		</div>
		<a class="announcement-arrow" href="announcement.php" aria-label="Read the full announcement"><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
	</div>
</section>

<section class="issue-section">
	<div class="container section-pad">
		<div class="issue-heading-row">
			<div class="section-heading"><p class="eyebrow eyebrow-green">Latest research</p><h2><?= e($issueLabel) ?></h2><p class="home-issue-period"><?= e($issue['period']) ?></p></div>
			<div class="issue-meta"><span><?= $currentPaperCount ?> original research papers</span><span>Published <?= e($issueDate) ?></span><span>eISSN 3027-2580</span></div>
		</div>
		<div class="papers-layout">
			<section class="papers-column" aria-labelledby="papers-heading">
				<div class="papers-toolbar">
					<h2 id="papers-heading">In this issue</h2>
					<div class="papers-toolbar-tools">
						<p class="result-count"><span id="result-count"><?= $currentPaperCount ?></span> papers</p>
						<label class="search-box"><ion-icon class="search-symbol" name="search-outline" aria-hidden="true"></ion-icon><span class="sr-only">Search papers or authors</span><input type="search" id="paper-search" placeholder="Search papers or authors" autocomplete="off"><kbd>/</kbd></label>
					</div>
				</div>
				<div class="paper-list" id="paper-list" aria-live="polite"><?php renderPaperRows($articles); ?><p class="empty-search" id="empty-search" hidden>No papers match that search. Try a different title, topic or author.</p></div>
				<a class="archive-link" href="archive.php">Browse all issues <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
			</section>
			<aside class="journal-aside">
				<div class="aside-card">
					<p class="eyebrow"><ion-icon name="cloud-upload-outline" aria-hidden="true"></ion-icon> For authors</p>
					<h2>Submit your research</h2>
					<p>Original papers on leather science, materials, engineering, environmental science and management are assessed by double-blind reviewers and published within three months of acceptance.</p>
					<a class="button button-green" href="submissions.php">Author guidelines <ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
				</div>
				<div class="aside-categories">
					<h2>Browse by subject</h2>
					<div>
						<a href="category.php?name=Science"><ion-icon name="flask-outline" aria-hidden="true"></ion-icon> Science</a>
						<a href="category.php?name=Technology"><ion-icon name="hardware-chip-outline" aria-hidden="true"></ion-icon> Technology</a>
						<a href="category.php?name=Engineering"><ion-icon name="construct-outline" aria-hidden="true"></ion-icon> Engineering</a>
						<a href="category.php?name=Management"><ion-icon name="stats-chart-outline" aria-hidden="true"></ion-icon> Management</a>
					</div>
				</div>
				<div class="aside-links">
					<h2>Journal information</h2>
					<a href="about.php"><span class="aside-link-label"><ion-icon name="information-circle-outline" aria-hidden="true"></ion-icon> About NIJOSTAM</span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
					<a href="submissions.php"><span class="aside-link-label"><ion-icon name="clipboard-outline" aria-hidden="true"></ion-icon> Submission guidelines</span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
					<a href="readers.php"><span class="aside-link-label"><ion-icon name="book-outline" aria-hidden="true"></ion-icon> Information for readers</span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
					<a href="librarians.php"><span class="aside-link-label"><ion-icon name="library-outline" aria-hidden="true"></ion-icon> Information for librarians</span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></a>
				</div>
			</aside>
		</div>
	</div>
</section>

<section class="about-section">
	<div class="container section-pad">
		<div class="about-grid">
			<div class="section-heading">
				<p class="eyebrow eyebrow-green"><ion-icon name="book-outline" aria-hidden="true"></ion-icon> About the journal</p>
				<h2>Research with <em>practice</em> in mind.</h2>
			</div>
			<div class="about-copy">
				<p class="lead">NIJOSTAM is the peer-reviewed journal of the Nigerian Institute of Leather and Science Technology, advancing knowledge across the sciences, engineering and management.</p>
				<p>Every submission is assessed by double-blind reviewers, published quarterly and made freely available under the Creative Commons Attribution 4.0 licence, with long-term archiving through LOCKSS and CLOCKSS.</p>
				<a class="green-link" href="about.php">More about NIJOSTAM <span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></span></a>
			</div>
		</div>
	</div>
</section>

<section class="closing-band">
	<div class="container closing-inner">
		<span class="closing-mark"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon></span>
		<p>Ready to share your research with an international audience? <em>Submit to NIJOSTAM.</em></p>
		<a href="submissions.php">Author guidelines <span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></span></a>
	</div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
