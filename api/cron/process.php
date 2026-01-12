<?php
/**
 * TrueVault VPN - Cron Job Processor
 * Run every 5 minutes via cron:
 * */5 * * * * php /path/to/api/cron/process.php
 * 
 * Or via URL with secret:
 * https://vpn.the-truth-publishing.com/api/cron/process.php?secret=TrueVault2026CronSecret
 */

// Allow CLI execution only, or with secret key
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    if ($secret !== 'TrueVault2026CronSecret') {
        http_response_code(403);
        die('Forbidden');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../billing/billing-manager.php';
require_once __DIR__ . '/../helpers/vip.php';

$startTime = microtime(true);
$results = [];

echo "========================================\n";
echo "TrueVault Cron Processor\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// 1. Process scheduled revocations
echo "1. Processing scheduled revocations...\n";
try {
    $revoked = BillingManager::processRevocations();
    $results['revocations'] = $revoked;
    echo "   Processed: {$revoked} revocations\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    $results['revocations_error'] = $e->getMessage();
}

// 2. Check expired subscriptions
echo "\n2. Checking expired subscriptions...\n";
try {
    $expired = Database::queryAll('billing',
        "SELECT s.user_id FROM subscriptions s
         WHERE s.status = 'active' AND s.end_date < datetime('now')"
    );
    
    $expiredCount = 0;
    foreach ($expired as $row) {
        $user = Database::queryOne('users', "SELECT email FROM users WHERE id = ?", [$row['user_id']]);
        
        // Skip VIPs
        if ($user && VIPManager::isVIP($user['email'])) {
            continue;
        }
        
        // Mark as expired
        Database::execute('billing',
            "UPDATE subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'",
            [$row['user_id']]
        );
        
        // Schedule immediate revocation
        BillingManager::scheduleAccessRevocation($row['user_id'], date('Y-m-d H:i:s'));
        
        $expiredCount++;
    }
    
    $results['expired_subscriptions'] = $expiredCount;
    echo "   Found: {$expiredCount} expired subscriptions\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 3. Update server peer counts
echo "\n3. Updating server statistics...\n";
try {
    $servers = Database::queryAll('vpn', "SELECT id FROM vpn_servers");
    foreach ($servers as $server) {
        $count = Database::queryOne('vpn', 
            "SELECT COUNT(*) as cnt FROM user_peers WHERE server_id = ? AND status = 'active'",
            [$server['id']]
        );
        Database::execute('vpn',
            "UPDATE vpn_servers SET current_peers = ? WHERE id = ?",
            [$count['cnt'] ?? 0, $server['id']]
        );
    }
    echo "   Updated server peer counts\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 4. Clean old logs
echo "\n4. Cleaning old logs...\n";
try {
    Database::execute('billing',
        "DELETE FROM webhook_log WHERE received_at < datetime('now', '-30 days')"
    );
    echo "   Cleaned webhook logs older than 30 days\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

$duration = round(microtime(true) - $startTime, 3);

echo "\n========================================\n";
echo "Completed in {$duration} seconds\n";
echo "========================================\n";

// Return JSON if called via web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'results' => $results, 'duration' => $duration]);
}
