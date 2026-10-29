<?php

/**
 * Legacy entry point. Redirects to the portal sign-out page.
 */

require __DIR__ . '/includes/bootstrap.php';

redirect('auth/logout.php');
