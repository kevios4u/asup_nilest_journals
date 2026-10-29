<?php

/**
 * Legacy entry point. Redirects to the portal sign-in page.
 */

require __DIR__ . '/includes/bootstrap.php';

redirect('auth/login.php');
