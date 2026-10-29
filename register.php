<?php

/**
 * Legacy entry point. Redirects to the portal registration page.
 */

require __DIR__ . '/includes/bootstrap.php';

redirect('auth/register.php');
