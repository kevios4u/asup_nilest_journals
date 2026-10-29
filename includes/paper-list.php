<?php

function renderPaperRows(array $papers): void
{
	if (!$papers) {
		echo '<p class="page-note">No papers are available in this selection.</p>';
		return;
	}

	foreach ($papers as $index => $paper) {
		printf(
			'<article class="paper-item" data-search="%s"><span class="paper-number">%02d</span><div class="paper-content"><span class="paper-category"><ion-icon name="%s" aria-hidden="true"></ion-icon> %s</span><h3><a href="article.php?id=%d">%s</a></h3><p class="paper-authors">%s</p></div><div class="paper-tools"><span class="paper-pages">pp. %s</span><a class="pdf-link" href="%s" target="_blank" rel="noreferrer"><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon> PDF</a></div></article>',
			e(strtolower($paper['title'] . ' ' . $paper['authors'] . ' ' . $paper['category'])),
			$index + 1,
			e(categoryIcon($paper['category'])),
			e($paper['category']),
			(int) $paper['id'],
			e($paper['title']),
			e($paper['authors']),
			e($paper['pages']),
			e($paper['pdf_url'])
		);
	}
}