<?php
/**
 * Database Cleanup & Configuration Script
 * 
 * PURPOSE: Remove test data, update VIP list, fix admin credentials
 * RUN ONCE then DELETE this file!
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Cleanup</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #0c5460; border: 1px solid #17a2b8; color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #856404; border: 1px solid #ffc107; color: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧹 Database Cleanup & Configuration</h1>

<?php

$results = [];

// ============================================
// STEP 1: CLEAN UP TEST USERS
// ============================================

echo '<h2>Step 1: Remove Test Users</h2>';

try {
    $usersDb = new SQLite3(DB_USERS);
    $usersDb->enableExceptions(true);
    
    // Delete all existing users (fresh start)
    $usersDb->exec("DELETE FROM users");
    $usersDb->exec("DELETE FROM sessions");
    $usersDb->exec("DELETE FROM password_reset_tokens");
    $usersDb->exec("DELETE FROM email_verification_tokens");
    
    // Reset auto-increment
    $usersDb->exec("DELETE FROM sqlite_sequence WHERE name='users'");
    $usersDb->exec("DELETE FROM sqlite_sequence WHERE name='sessions'");
    
    $usersDb->close();
    echo '<div class="success">✅ All test users removed, tables cleaned</div>';
    $results['users_cleanup'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['users_cleanup'] = 'error';
}

// ============================================
// STEP 2: UPDATE VIP LIST (Database-driven)
// ============================================

echo '<h2>Step 2: Update VIP List</h2>';

try {
    $adminDb = new SQLite3(DB_ADMIN);
    $adminDb->enableExceptions(true);
    
    // Clear existing VIP list
    $adminDb->exec("DELETE FROM vip_list");
    
    // Add VIPs (database-driven, not hardcoded in code)
    $vips = [
        ['paulhalonen@gmail.com', 'Owner - Regular VIP access', null, 'full', 'system'],
        ['seige235@yahoo.com', 'Dedicated St. Louis server (144.126.133.253)', 2, 'full', 'system']
    ];
    
    $stmt = $adminDb->prepare("
        INSERT INTO vip_list (email, notes, dedicated_server_id, access_level, status, added_by, added_at)
        VALUES (:email, :notes, :server_id, :access, 'active', :added_by, datetime('now'))
    ");
    
    foreach ($vips as $vip) {
        $stmt->bindValue(':email', $vip[0], SQLITE3_TEXT);
        $stmt->bindValue(':notes', $vip[1], SQLITE3_TEXT);
        $stmt->bindValue(':server_id', $vip[2], $vip[2] ? SQLITE3_INTEGER : SQLITE3_NULL);
        $stmt->bindValue(':access', $vip[3], SQLITE3_TEXT);
        $stmt->bindValue(':added_by', $vip[4], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
        echo '<div class="info">Added VIP: ' . htmlspecialchars($vip[0]) . '</div>';
    }
    
    $adminDb->close();
    echo '<div class="success">✅ VIP list updated (2 VIPs)</div>';
    $results['vip_list'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['vip_list'] = 'error';
}

// ============================================
// STEP 3: UPDATE ADMIN CREDENTIALS
// ============================================

echo '<h2>Step 3: Update Admin Credentials</h2>';

try {
    $adminDb = new SQLite3(DB_ADMIN);
    $adminDb->enableExceptions(true);
    
    // Clear existing admin users
    $adminDb->exec("DELETE FROM admin_users");
    
    // Create new admin with secure password
    $adminEmail = 'paulhalonen@gmail.com';
    $adminPassword = 'Asasasas4!';
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $adminDb->prepare("
        INSERT INTO admin_users (email, password_hash, full_name, role, status, created_at, updated_at)
        VALUES (:email, :password, :name, 'super_admin', 'active', datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':email', $adminEmail, SQLITE3_TEXT);
    $stmt->bindValue(':password', $passwordHash, SQLITE3_TEXT);
    $stmt->bindValue(':name', 'Kah-Len (Owner)', SQLITE3_TEXT);
    $stmt->execute();
    
    $adminDb->close();
    echo '<div class="success">✅ Admin credentials updated</div>';
    echo '<div class="info">Admin email: ' . htmlspecialchars($adminEmail) . '</div>';
    $results['admin_credentials'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['admin_credentials'] = 'error';
}

// ============================================
// STEP 4: CLEAN LOGS
// ============================================

echo '<h2>Step 4: Clean Test Logs</h2>';

try {
    $logsDb = new SQLite3(DB_LOGS);
    $logsDb->enableExceptions(true);
    
    $logsDb->exec("DELETE FROM security_events");
    $logsDb->exec("DELETE FROM audit_log");
    $logsDb->exec("DELETE FROM api_requests");
    $logsDb->exec("DELETE FROM error_log");
    
    $logsDb->close();
    echo '<div class="success">✅ Logs cleaned</div>';
    $results['logs_cleanup'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['logs_cleanup'] = 'error';
}

// ============================================
// SUMMARY
// ============================================

echo '<h2>Summary</h2>';
$allSuccess = !in_array('error', $results);

if ($allSuccess) {
    echo '<div class="success">';
    echo '<h3>🎉 All tasks completed successfully!</h3>';
    echo '<ul>';
    echo '<li>Test users removed</li>';
    echo '<li>VIP list: paulhalonen@gmail.com (regular VIP), seige235@yahoo.com (dedicated server)</li>';
    echo '<li>Admin: paulhalonen@gmail.com</li>';
    echo '<li>Logs cleaned</li>';
    echo '</ul>';
    echo '<p><strong>⚠️ DELETE THIS FILE NOW!</strong></p>';
    echo '</div>';
} else {
    echo '<div class="error">Some tasks failed. Review errors above.</div>';
}

?>

</div>
</body>
</html>
