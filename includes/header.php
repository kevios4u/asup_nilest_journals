<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#123b2e">
	<meta name="description" content="<?= e($pageDescription ?? 'NILEST Journal of Science, Technology & Management') ?>">
	<meta name="author" content="Nigerian Institute of Leather and Science Technology">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="NIJOSTAM">
	<meta property="og:title" content="<?= e($metaTitle ?? $pageTitle ?? 'NIJOSTAM') ?>">
	<meta property="og:description" content="<?= e($pageDescription ?? 'NILEST Journal of Science, Technology & Management') ?>">
	<meta property="og:image" content="<?= e(url('assets/images/android-chrome-512x512.png')) ?>">
	<meta name="twitter:card" content="summary">
	<title><?= e($metaTitle ?? $pageTitle ?? 'NIJOSTAM') ?><?= ($activePage ?? '') === 'home' ? '' : ' | NIJOSTAM' ?></title>
	<link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('assets/images/apple-touch-icon.png')) ?>">
	<link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/images/favicon-32x32.png')) ?>">
	<link rel="icon" type="image/png" sizes="16x16" href="<?= e(url('assets/images/favicon-16x16.png')) ?>">
	<link rel="manifest" href="<?= e(url('assets/images/site.webmanifest')) ?>">
	<script type="module" src="<?= e(url('assets/vendor/ionicons/ionicons.esm.js')) ?>"></script>
	<script nomodule src="<?= e(url('assets/vendor/ionicons/ionicons.js')) ?>"></script>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
	<?php if (!empty($isPortal)): ?><link rel="stylesheet" href="<?= e(url('assets/css/portal.css')) ?>"><?php endif; ?>
	<script src="<?= e(url('assets/js/site.js')) ?>" defer></script>
</head>
<body id="top">
	<a class="skip-link" href="#main">Skip to content</a>
	<?php $accountUser = currentUser(); $socialLinks = socialLinks(); ?>
	<div class="top-row"><div class="container top-row-inner">
		<?php if ($socialLinks): ?>
			<div class="social-bar">
				<span class="social-bar-label">Follow the journal</span>
				<ul class="social-nav">
					<?php foreach ($socialLinks as $social): ?>
						<li><a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($social['label']) ?>" aria-label="<?= e($social['label']) ?>"><ion-icon name="<?= e($social['icon']) ?>" aria-hidden="true"></ion-icon></a></li>
					<?php endforeach; ?>
					<li><a href="mailto:info@nijostam.org" title="Email the editorial office" aria-label="Email the editorial office"><ion-icon name="mail-outline" aria-hidden="true"></ion-icon></a></li>
				</ul>
			</div>
		<?php endif; ?>
		<div class="account-nav">
			<?php if ($accountUser): ?>
				<a class="account-name" href="<?= e(url(roleHome($accountUser['role']))) ?>" title="<?= e($accountUser['name']) ?>"><ion-icon name="person-circle-outline" aria-hidden="true"></ion-icon><span><?= e($accountUser['name']) ?></span></a>
				<a class="account-link" href="<?= e(url('auth/logout.php')) ?>" aria-label="Sign out" title="Sign out"><ion-icon name="log-out-outline" aria-hidden="true"></ion-icon><span>Sign out</span></a>
			<?php else: ?>
				<a class="account-link" href="<?= e(url('auth/login.php')) ?>"<?= ($activePage ?? '') === 'login' ? ' aria-current="page"' : '' ?> aria-label="Sign in" title="Sign in"><ion-icon name="log-in-outline" aria-hidden="true"></ion-icon><span>Sign in</span></a>
				<a class="account-link account-link-primary" href="<?= e(url('auth/register.php')) ?>"<?= ($activePage ?? '') === 'register' ? ' aria-current="page"' : '' ?> aria-label="Create an account" title="Create an account"><ion-icon name="person-add-outline" aria-hidden="true"></ion-icon><span>Register</span></a>
			<?php endif; ?>
		</div>
	</div></div>
	<header class="site-header" id="siteNav"><div class="container header-inner">
		<a class="brand" href="<?= e(url('index.php')) ?>" aria-label="NIJOSTAM home"><img class="brand-mark" src="<?= e(url('assets/images/asup_journal_logo.png')) ?>" alt=""><span class="brand-copy"><strong>NIJOSTAM</strong><small>NILEST Journal of Science,<br>Technology &amp; Management</small></span></a>
		<nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
			<a href="<?= e(url('index.php')) ?>"<?= ($activePage ?? '') === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
			<a href="<?= e(url('about.php')) ?>"<?= ($activePage ?? '') === 'about' ? ' aria-current="page"' : '' ?>>About</a>
			<a href="<?= e(url('current-issue.php')) ?>"<?= ($activePage ?? '') === 'current-issue' ? ' aria-current="page"' : '' ?>>Current issue</a>
			<a href="<?= e(url('archive.php')) ?>"<?= ($activePage ?? '') === 'archive' ? ' aria-current="page"' : '' ?>>Archive</a>
			<a href="<?= e(url('authors.php')) ?>"<?= ($activePage ?? '') === 'authors' ? ' aria-current="page"' : '' ?>>For authors</a>
			<a href="<?= e(url('contact.php')) ?>"<?= ($activePage ?? '') === 'contact' ? ' aria-current="page"' : '' ?>>Contact</a>
			<a class="nav-submit" href="<?= e(url('submissions.php')) ?>">Submit a paper <span><ion-icon name="arrow-forward-outline" aria-hidden="true"></ion-icon></span></a>
		</nav>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">Menu <span><ion-icon name="menu-outline" aria-hidden="true"></ion-icon></span></button>
	</div></header>
	<main id="main">
		<?php if (!isset($showPageHero) || $showPageHero): ?>
			<section class="inner-hero"><div class="container"><p class="inner-breadcrumb"><a href="<?= e(url('index.php')) ?>"><ion-icon name="home-outline" aria-hidden="true"></ion-icon>Home</a><span aria-hidden="true">/</span><?= e($pageTitle ?? 'NIJOSTAM') ?></p><p class="eyebrow"><span class="eyebrow-rule"></span>NILEST Journal of Science, Technology &amp; Management</p><h1><?= e($pageTitle ?? 'NIJOSTAM') ?></h1><p class="inner-description"><?= e($pageDescription ?? '') ?></p></div></section>
		<?php endif; ?>
