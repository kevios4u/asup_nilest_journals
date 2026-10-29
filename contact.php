<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Contact the journal';
$pageDescription = 'Send a message to the NIJOSTAM editorial office.';
$metaTitle = 'Contact the NIJOSTAM editorial office';
$activePage = 'contact';
$errors = [];
$sent = false;
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string) ($_POST['name'] ?? ''));
	$email = strtolower(trim((string) ($_POST['email'] ?? '')));
	$subject = trim((string) ($_POST['subject'] ?? ''));
	$message = trim((string) ($_POST['message'] ?? ''));
	$honeypot = trim((string) ($_POST['website'] ?? ''));

	if ($honeypot !== '') {
		$sent = true;
	} elseif (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
		$errors[] = 'Your form session expired. Please try again.';
	} elseif ($name === '' || strlen($name) > 120) {
		$errors[] = 'Enter your name (up to 120 characters).';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
		$errors[] = 'Enter a valid email address.';
	} elseif ($subject === '' || strlen($subject) > 180) {
		$errors[] = 'Enter a subject (up to 180 characters).';
	} elseif ($message === '' || strlen($message) > 10000) {
		$errors[] = 'Enter a message of up to 10,000 characters.';
	} elseif (!$pdo instanceof PDO) {
		$errors[] = 'The contact form is temporarily unavailable. Please email info@nijostam.org.';
	} else {
		$statement = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
		$statement->execute([$name, $email, $subject, $message]);
		$sent = true;
		$name = $email = $subject = $message = '';
		unset($_SESSION['csrf_token']);
	}
}

require __DIR__ . '/includes/header.php';
?>
<div class="container page-body">
	<section class="form-page contact-page">
		<p class="page-lead">Send a message to the NIJOSTAM editorial office. Your message will be stored for follow-up.</p>
		<?php if ($sent): ?><div class="form-message is-success" role="status">Thank you. Your message has been received.</div><?php endif; ?>
		<?php if ($errors): ?><div class="form-message is-error" role="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
		<form class="journal-form" method="post" action="contact.php">
			<input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
			<div class="honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
			<label for="name">Full name</label>
			<div class="field"><ion-icon name="person-outline" aria-hidden="true"></ion-icon><input id="name" name="name" type="text" value="<?= e($name) ?>" autocomplete="name" maxlength="120" required></div>
			<label for="email">Email address</label>
			<div class="field"><ion-icon name="mail-outline" aria-hidden="true"></ion-icon><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" required></div>
			<label for="subject">Subject</label>
			<div class="field"><ion-icon name="chatbubble-ellipses-outline" aria-hidden="true"></ion-icon><input id="subject" name="subject" type="text" value="<?= e($subject) ?>" maxlength="180" required></div>
			<label for="message">Message</label>
			<div class="field field-textarea"><ion-icon name="text-outline" aria-hidden="true"></ion-icon><textarea id="message" name="message" rows="7" maxlength="10000" required><?= e($message) ?></textarea></div>
			<button class="button button-green" type="submit">Send message <ion-icon name="send-outline" aria-hidden="true"></ion-icon></button>
		</form>
	</section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>