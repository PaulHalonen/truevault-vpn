<?php
/**
 * TrueVault VPN - Cron Job Handler
 * Run every 5 minutes via cron
 * 
 * Cron entry:
 * */5 * * * * php /path/to/api/cron/run.php
 * 
 * Or via URL:
 * https://vpn.the-truth-publishing.com/api/cron/run.php?key=TrueVault2026CronKey
 */

// Security check
$cronKey = 'TrueVault2026CronKey';
if (php_sapi_name() !== 'cli') {
    // Running via web - check key
    if (($_GET['key'] ?? '') !== $cronKey) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$results = [];
$startTime = microtime(true);

// ==================== TASK 1: Process Revocations ====================
try {
    $revoked = BillingManager::processRevocations();
    $results['revocations'] = [
        'success' => true,
        'processed' => $revoked
    ];
} catch (Exception $e) {
    $results['revocations'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// ==================== TASK 2: Check Expiring Subscriptions ====================
try {
    // Find subscriptions expiring in 3 days
    $expiring = Database::queryAll('billing',
        "SELECT s.*, u.email, u.first_name 
         FROM subscriptions s 
         JOIN (SELECT id, email, first_name FROM users) u ON s.user_id = u.id
         WHERE s.status = 'active' 
         AND s.end_date BETWEEN datetime('now') AND datetime('now', '+3 days')
         AND s.user_id NOT IN (SELECT user_id FROM payment_failures WHERE notified = 0)"
    );
    
    foreach ($expiring as $sub) {
        // Log expiring subscription (TODO: send email)
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, created_at)
             VALUES (?, 'subscription_expiring', ?, datetime('now'))",
            [$sub['user_id'], json_encode(['end_date' => $sub['end_date']])]
        );
    }
    
    $results['expiring_subscriptions'] = [
        'success' => true,
        'count' => count($expiring)
    ];
} catch (Exception $e) {
    $results['expiring_subscriptions'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// ==================== TASK 3: Auto-Suspend Expired ====================
try {
    // Find expired subscriptions
    $expired = Database::queryAll('billing',
        "SELECT user_id FROM subscriptions 
         WHERE status = 'active' 
         AND end_date < datetime('now')"
    );
    
    foreach ($expired as $sub) {
        // Check if VIP (they never expire)
        $user = Database::queryOne('users', "SELECT email FROM users WHERE id = ?", [$sub['user_id']]);
        
        if ($user) {
            require_once __DIR__ . '/../helpers/vip.php';
            if (VIPManager::isVIP($user['email'])) {
                // Extend VIP subscription indefinitely
                Database::execute('billing',
                    "UPDATE subscriptions SET end_date = datetime('now', '+100 years') WHERE user_id = ? AND status = 'active'",
                    [$sub['user_id']]
                );
                continue;
            }
        }
        
        // Mark as expired
        Database::execute('billing',
            "UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'",
            [$sub['user_id']]
        );
        
        // Schedule revocation (grace period already passed)
        BillingManager::scheduleAccessRevocation($sub['user_id'], date('Y-m-d H:i:s'));
    }
    
    $results['expired_subscriptions'] = [
        'success' => true,
        'count' => count($expired)
    ];
} catch (Exception $e) {
    $results['expired_subscriptions'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// ==================== TASK 4: Cleanup Old Logs ====================
try {
    // Delete webhook logs older than 30 days
    Database::execute('logs',
        "DELETE FROM webhook_log WHERE received_at < datetime('now', '-30 days')"
    );
    
    // Delete activity logs older than 90 days
    Database::execute('logs',
        "DELETE FROM activity_log WHERE created_at < datetime('now', '-90 days')"
    );
    
    $results['cleanup'] = ['success' => true];
} catch (Exception $e) {
    $results['cleanup'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// ==================== TASK 5: Health Check Servers ====================
try {
    $servers = [
        ['id' => 1, 'ip' => '66.94.103.91', 'name' => 'NY'],
        ['id' => 2, 'ip' => '144.126.133.253', 'name' => 'STL'],
        ['id' => 3, 'ip' => '66.241.124.4', 'name' => 'TX'],
        ['id' => 4, 'ip' => '66.241.125.247', 'name' => 'CAN']
    ];
    
    $serverStatus = [];
    foreach ($servers as $server) {
        // Quick ping check
        $fp = @fsockopen($server['ip'], 51820, $errno, $errstr, 2);
        $online = $fp !== false;
        if ($fp) fclose($fp);
        
        $serverStatus[$server['name']] = $online ? 'online' : 'offline';
        
        // Update database status
        Database::execute('vpn',
            "UPDATE vpn_servers SET status = ?, last_check = datetime('now') WHERE id = ?",
            [$online ? 'online' : 'offline', $server['id']]
        );
    }
    
    $results['server_health'] = [
        'success' => true,
        'servers' => $serverStatus
    ];
} catch (Exception $e) {
    $results['server_health'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// ==================== Log Cron Run ====================
$duration = round((microtime(true) - $startTime) * 1000);

Database::execute('logs',
    "INSERT INTO activity_log (user_id, action, details, created_at)
     VALUES (NULL, 'cron_run', ?, datetime('now'))",
    [json_encode(['duration_ms' => $duration, 'results' => $results])]
);

// Output results
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'duration_ms' => $duration,
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results
], JSON_PRETTY_PRINT);
