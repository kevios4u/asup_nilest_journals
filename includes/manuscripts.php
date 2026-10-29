<?php

/**
 * Manuscript helpers shared by the author, editor, reviewer and admin portals.
 */

if (!function_exists('loadManuscript')) {
    function loadManuscript(int $manuscriptId): ?array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return null;
        }
        $statement = $pdo->prepare(
            'SELECT m.*, u.name AS submitter_name, u.email AS submitter_email
             FROM manuscripts m
             INNER JOIN users u ON u.id = m.submitted_by
             WHERE m.id = ? LIMIT 1'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetch() ?: null;
    }
}

if (!function_exists('manuscriptAuthors')) {
    function manuscriptAuthors(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT * FROM manuscript_authors WHERE manuscript_id = ? ORDER BY author_order, id'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptFiles')) {
    function manuscriptFiles(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT f.*, u.name AS uploader_name FROM manuscript_files f
             LEFT JOIN users u ON u.id = f.uploaded_by
             WHERE f.manuscript_id = ? ORDER BY f.file_type, f.version, f.id'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptDecisions')) {
    function manuscriptDecisions(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT d.*, u.name AS editor_name FROM editorial_decisions d
             INNER JOIN users u ON u.id = d.editor_id
             WHERE d.manuscript_id = ? ORDER BY d.created_at DESC, d.id DESC'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptRevisions')) {
    function manuscriptRevisions(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT r.*, u.name AS submitter_name FROM revision_history r
             LEFT JOIN users u ON u.id = r.submitted_by
             WHERE r.manuscript_id = ? ORDER BY r.version DESC'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptReviews')) {
    function manuscriptReviews(int $manuscriptId, bool $includeEditorComments = false): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $columns = $includeEditorComments
            ? 'r.*, u.name AS reviewer_name, r.comments_for_editor'
            : 'r.id, r.manuscript_id, r.reviewer_id, r.round, r.recommendation, r.comments_for_author, r.submitted_at';
        $statement = $pdo->prepare(
            "SELECT {$columns}, u.name AS reviewer_name FROM reviews r
             INNER JOIN users u ON u.id = r.reviewer_id
             WHERE r.manuscript_id = ? ORDER BY r.round, r.submitted_at"
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptReviewers')) {
    function manuscriptReviewers(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT ra.*, u.name AS reviewer_name, u.email AS reviewer_email, e.name AS editor_name
             FROM reviewer_assignments ra
             INNER JOIN users u ON u.id = ra.reviewer_id
             LEFT JOIN users e ON e.id = ra.editor_id
             WHERE ra.manuscript_id = ? ORDER BY ra.invited_at'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptEditors')) {
    function manuscriptEditors(int $manuscriptId): array
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return [];
        }
        $statement = $pdo->prepare(
            'SELECT ea.*, u.name AS editor_name, u.email AS editor_email FROM editor_assignments ea
             INNER JOIN users u ON u.id = ea.editor_id
             WHERE ea.manuscript_id = ? ORDER BY ea.assigned_at'
        );
        $statement->execute([$manuscriptId]);
        return $statement->fetchAll();
    }
}

if (!function_exists('manuscriptTimeline')) {
    function manuscriptTimeline(int $manuscriptId): array
    {
        $events = [];

        $pdo = db();
        if (!$pdo instanceof PDO) {
            return $events;
        }

        $statement = $pdo->prepare('SELECT reference_no, submitted_at, updated_at FROM manuscripts WHERE id = ?');
        $statement->execute([$manuscriptId]);
        $manuscript = $statement->fetch();
        if ($manuscript) {
            $events[] = [
                'at' => $manuscript['submitted_at'] ?? $manuscript['updated_at'],
                'icon' => 'cloud-upload-outline',
                'title' => 'Manuscript submitted',
                'detail' => 'Reference ' . $manuscript['reference_no'],
            ];
        }

        foreach (manuscriptDecisions($manuscriptId) as $decision) {
            $label = editorial_decisions()[$decision['decision']]['label'] ?? ucfirst($decision['decision']);
            $events[] = [
                'at' => $decision['created_at'],
                'icon' => 'document-text-outline',
                'title' => 'Editorial decision: ' . $label,
                'detail' => 'By ' . $decision['editor_name'],
            ];
        }

        foreach (manuscriptRevisions($manuscriptId) as $revision) {
            $events[] = [
                'at' => $revision['submitted_at'],
                'icon' => 'refresh-outline',
                'title' => 'Revision version ' . $revision['version'] . ' uploaded',
                'detail' => $revision['submitter_name'] ? 'By ' . $revision['submitter_name'] : '',
            ];
        }

        $statement = $pdo->prepare(
            'SELECT r.submitted_at, r.recommendation, u.name AS reviewer_name FROM reviews r
             INNER JOIN users u ON u.id = r.reviewer_id
             WHERE r.manuscript_id = ? ORDER BY r.submitted_at'
        );
        $statement->execute([$manuscriptId]);
        foreach ($statement->fetchAll() as $review) {
            $events[] = [
                'at' => $review['submitted_at'],
                'icon' => 'checkmark-done-outline',
                'title' => 'Peer review report submitted',
                'detail' => $review['reviewer_name'],
            ];
        }

        usort($events, static fn(array $a, array $b): int => strtotime((string) $a['at']) <=> strtotime((string) $b['at']));

        return array_reverse($events);
    }
}

if (!function_exists('canViewManuscript')) {
    function canViewManuscript(array $manuscript, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user['role'] === 'admin' || $user['role'] === 'editor' || $user['role'] === 'reviewer') {
            return true;
        }
        return (int) $manuscript['submitted_by'] === (int) $user['id'];
    }
}

if (!function_exists('canDownloadManuscriptFile')) {
    function canDownloadManuscriptFile(array $file, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user['role'], ['admin', 'editor', 'reviewer'], true)) {
            return true;
        }

        $pdo = db();
        if (!$pdo instanceof PDO) {
            return false;
        }
        $statement = $pdo->prepare('SELECT submitted_by FROM manuscripts WHERE id = ?');
        $statement->execute([(int) $file['manuscript_id']]);
        $row = $statement->fetch();

        return $row && (int) $row['submitted_by'] === (int) $user['id'];
    }
}

if (!function_exists('authorIsCorresponding')) {
    function authorIsCorresponding(int $manuscriptId, int $userId): bool
    {
        $pdo = db();
        if (!$pdo instanceof PDO) {
            return false;
        }
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM manuscript_authors WHERE manuscript_id = ? AND (user_id = ? OR email = (SELECT email FROM users WHERE id = ?))'
        );
        $statement->execute([$manuscriptId, $userId, $userId]);
        return (int) $statement->fetchColumn() > 0;
    }
}
