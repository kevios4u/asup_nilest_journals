<?php

/**
 * Sign out.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

logoutUser();

session_id(bin2hex(random_bytes(32)));
startSecureSession();
flash('info', 'You have been signed out.');

redirect('auth/login.php');
