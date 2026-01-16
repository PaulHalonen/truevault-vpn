<?php
/**
 * TrueVault VPN - Admin Entry Point
 * Redirects to the admin dashboard HTML
 */

// If accessing setup-databases.php, don't redirect
if (basename($_SERVER['PHP_SELF']) === 'setup-databases.php') {
    return;
}

// Redirect to admin dashboard
header('Location: /admin/index.html');
exit;
