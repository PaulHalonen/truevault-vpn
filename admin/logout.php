<?php
require_once 'config.php';

if (isAdminLoggedIn()) {
    // Log activity
    logActivity('admin_logout');
    
    // Delete session from database
    $db = getAdminDB();
    $stmt = $db->prepare("DELETE FROM admin_sessions WHERE admin_id = ? AND session_token = ?");
    $stmt->execute([$_SESSION['admin_id'], $_SESSION['admin_token']]);
    
    // Destroy session
    session_destroy();
}

header('Location: /admin/login.php');
exit;
?>
