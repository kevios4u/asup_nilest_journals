<?php
/**
 * Temporary helper: creates demo accounts for browser verification.
 * Run: php database/seed_demo_users.php
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

$accounts = [
    ['demo.author@nijostam.test', 'Demo Author', 'author', 'Testing12345pass'],
    ['demo.editor@nijostam.test', 'Demo Editor', 'editor', 'Testing12345pass'],
    ['demo.reviewer@nijostam.test', 'Demo Reviewer', 'reviewer', 'Testing12345pass'],
    ['demo.admin@nijostam.test', 'Demo Administrator', 'admin', 'Testing12345pass'],
];

foreach ($accounts as [$email, $name, $role, $password]) {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    $existing = $check->fetchColumn();

    if ($existing !== false) {
        $userId = (int) $existing;
    } else {
        registerUser($name, $email, $password, $role);
        $check->execute([$email]);
        $userId = (int) $check->fetchColumn();
    }

    // Self-registration never grants administrator rights, so set the role here.
    $pdo->prepare('UPDATE users SET name = ?, role = ?, status = "active", password_hash = ? WHERE id = ?')
        ->execute([$name, $role, password_hash($password, PASSWORD_DEFAULT), $userId]);

    $pdo->prepare(
        'INSERT INTO author_profiles (user_id, affiliation, country, is_corresponding) VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE affiliation = VALUES(affiliation), country = VALUES(country)'
    )->execute([$userId, 'Nigerian Institute of Leather and Science Technology', 'Nigeria']);

    echo "{$role}: {$email} (id={$userId})\n";
}

// A demo manuscript for the author account.
$authorId = (int) $pdo->query("SELECT id FROM users WHERE email = 'demo.author@nijostam.test'")->fetchColumn();
$count = (int) $pdo->query('SELECT COUNT(*) FROM manuscripts WHERE submitted_by = ' . $authorId)->fetchColumn();

if ($count === 0) {
    $reference = manuscriptReference($pdo);
    $pdo->prepare(
        "INSERT INTO manuscripts (reference_no, title, abstract, keywords, subject, article_type, language, status, submitted_by, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'under_review', ?, NOW())"
    )->execute([
        $reference,
        'Sustainable processing of bovine hides using a low-water dehairing technique',
        'This demonstration manuscript illustrates a submission moving through editorial screening into peer review. It records the abstract, keywords, and authorship metadata so the portal can be reviewed with realistic content.',
        'leather processing, sustainability, water conservation',
        'Leather science',
        'Original research',
        'English',
        $authorId,
    ]);
    $manuscriptId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO manuscript_authors (manuscript_id, user_id, name, email, is_corresponding, author_order) VALUES (?, ?, ?, ?, 1, 1)')
        ->execute([$manuscriptId, $authorId, 'Demo Author', 'demo.author@nijostam.test']);
    $pdo->prepare('INSERT INTO revision_history (manuscript_id, version, notes, submitted_by) VALUES (?, 1, ?, ?)')
        ->execute([$manuscriptId, 'Initial submission.', $authorId]);

    $editorId = (int) $pdo->query("SELECT id FROM users WHERE email = 'demo.editor@nijostam.test'")->fetchColumn();
    $pdo->prepare('INSERT INTO editor_assignments (manuscript_id, editor_id, assigned_by) VALUES (?, ?, ?)')
        ->execute([$manuscriptId, $editorId, $editorId]);

    echo "manuscript: {$reference} (id={$manuscriptId})\n";
}

echo "Done.\n";
