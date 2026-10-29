<?php

/**
 * Shared bootstrap for every page: configuration, session, helpers and
 * the public-site content used by the website pages.
 */

$config = require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/manuscripts.php';

$pdo = $config['pdo'];

startSecureSession();

$issue = [
	'id' => 1,
	'volume' => '4',
	'issue_number' => '1',
	'period' => 'January - June, 2026',
	'published_at' => '2026-06-30',
	'cover_url' => 'https://nijostam.org/journals/public/journals/1/cover_issue_4_en.jpg',
];
$announcement = [
	'title' => 'The Leather and Leather Products Industry in Nigeria: Approaches to Sustainable Manufacturing and Marketing',
	'published_at' => '2026-10-02',
	'summary' => 'A book project exploring sustainable manufacturing systems, innovative processing technologies, environmental management, market development, policy frameworks, and strategic investment pathways for Nigeria’s leather industry.',
	'url' => 'https://nijostam.org/journals/index.php/njstm/announcement/view/1',
];
$articles = [];
$archiveIssues = [];
$databaseReady = false;

if ($pdo instanceof PDO) {
	try {
		$databaseIssue = $pdo->query('SELECT id, volume, issue_number, period, published_at, cover_url FROM journal_issues ORDER BY published_at DESC, id DESC LIMIT 1')->fetch();
		if ($databaseIssue) {
			$issue = $databaseIssue;
			$articles = loadIssueArticles((int) $issue['id']);
			$archiveIssues = $pdo->query('SELECT id, volume, issue_number, period, published_at, cover_url FROM journal_issues ORDER BY published_at DESC, id DESC')->fetchAll();
			$databaseAnnouncement = $pdo->query('SELECT title, published_at, summary, url FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetch();
			if ($databaseAnnouncement) {
				$announcement = $databaseAnnouncement;
			}
			$databaseReady = true;
		}
	} catch (PDOException $exception) {
		$databaseReady = false;
	}
}

$issueLabel = 'Vol. ' . $issue['volume'] . ' No. ' . $issue['issue_number'];
$issueDate = date('F j, Y', strtotime($issue['published_at']));
$announcementDate = date('F j, Y', strtotime($announcement['published_at']));

function loadIssue(?int $issueId = null): ?array
{
	global $pdo;
	if (!$pdo instanceof PDO) {
		return null;
	}
	if ($issueId !== null) {
		$statement = $pdo->prepare('SELECT id, volume, issue_number, period, published_at, cover_url FROM journal_issues WHERE id = ?');
		$statement->execute([$issueId]);
		return $statement->fetch() ?: null;
	}
	return $pdo->query('SELECT id, volume, issue_number, period, published_at, cover_url FROM journal_issues ORDER BY published_at DESC, id DESC LIMIT 1')->fetch() ?: null;
}

function loadIssueArticles(int $issueId): array
{
	global $pdo;
	if (!$pdo instanceof PDO) {
		return [];
	}
	$statement = $pdo->prepare('SELECT id, title, authors, pages, category, article_url, pdf_url FROM published_articles WHERE issue_id = ? ORDER BY sort_order, id');
	$statement->execute([$issueId]);
	return $statement->fetchAll();
}

function loadAllArticles(): array
{
	global $pdo;
	return $pdo instanceof PDO
		? $pdo->query('SELECT id, title, authors, pages, category, article_url, pdf_url FROM published_articles ORDER BY issue_id DESC, sort_order, id')->fetchAll()
		: [];
}

function loadArticle(int $articleId): ?array
{
	global $pdo;
	if (!$pdo instanceof PDO) {
		return null;
	}
	$statement = $pdo->prepare('SELECT a.id, a.issue_id, a.title, a.authors, a.pages, a.category, a.article_url, a.pdf_url, a.doi, a.abstract, i.volume AS issue_volume, i.issue_number, i.period AS issue_period, i.published_at FROM published_articles a INNER JOIN journal_issues i ON i.id = a.issue_id WHERE a.id = ?');
	$statement->execute([$articleId]);
	return $statement->fetch() ?: null;
}

function truncateTitle(string $value, int $length = 70): string
{
	return mb_strlen($value) > $length ? rtrim(mb_substr($value, 0, $length - 1), " ,.;:-") . '…' : $value;
}

function categoryIcon(string $category): string
{
	$category = strtolower($category);
	$map = [
		'management' => 'stats-chart-outline',
		'engineering' => 'construct-outline',
		'mechanical' => 'construct-outline',
		'material' => 'cube-outline',
		'environment' => 'leaf-outline',
		'leather' => 'shirt-outline',
		'computer' => 'hardware-chip-outline',
		'software' => 'hardware-chip-outline',
		'technology' => 'hardware-chip-outline',
		'biological' => 'flask-outline',
		'pharmaceutical' => 'flask-outline',
		'medical' => 'flask-outline',
		'chemistry' => 'flask-outline',
		'science' => 'flask-outline',
		'textile' => 'shirt-outline',
		'energy' => 'flash-outline',
		'water' => 'water-outline',
		'food' => 'nutrition-outline',
	];
	foreach ($map as $needle => $icon) {
		if (str_contains($category, $needle)) {
			return $icon;
		}
	}
	return 'document-text-outline';
}
