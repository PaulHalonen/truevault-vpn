<?php
/**
 * TrueVault VPN - Cron Job Handler
 * Run every 5 minutes via cron:
 * */5 * * * * php /path/to/api/cron/process.php
 * 
 * Or access via web: /api/cron/process.php?key=TrueVault2026CronKey
 */

// Security check for web access
$cronKey = 'TrueVault2026CronKey';
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== $cronKey) {
        http_response_code(403);
        die('Forbidden');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../billing/billing-manager.php';
require_once __DIR__ . '/../helpers/vip.php';

$startTime = microtime(true);
$results = [];

echo "TrueVault VPN - Cron Job\n";
echo "========================\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Process scheduled access revocations
echo "[1] Processing scheduled revocations...\n";
try {
    $revoked = BillingManager::processRevocations();
    $results['revocations'] = $revoked;
    echo "    Processed: {$revoked} revocations\n";
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
    $results['revocations_error'] = $e->getMessage();
}

// 2. Check for expiring subscriptions (send reminder emails)
echo "\n[2] Checking expiring subscriptions...\n";
try {
    $expiring = checkExpiringSubscriptions();
    $results['expiring_notified'] = $expiring;
    echo "    Notified: {$expiring} users of expiring subscriptions\n";
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// 3. Clean up old pending orders (older than 24 hours)
echo "\n[3] Cleaning up stale pending orders...\n";
try {
    $cleaned = cleanupPendingOrders();
    $results['orders_cleaned'] = $cleaned;
    echo "    Cleaned: {$cleaned} stale orders\n";
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// 4. Check server health
echo "\n[4] Checking server health...\n";
try {
    $health = checkServerHealth();
    $results['servers'] = $health;
    foreach ($health as $server => $status) {
        $icon = $status['healthy'] ? '✓' : '✗';
        echo "    {$icon} {$server}: {$status['message']}\n";
    }
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// 5. Process failed payment retries
echo "\n[5] Processing payment retry queue...\n";
try {
    $retries = processPaymentRetries();
    $results['retries'] = $retries;
    echo "    Processed: {$retries} retry attempts\n";
} catch (Exception $e) {
    echo "    ERROR: " . $e->getMessage() . "\n";
}

// Summary
$duration = round(microtime(true) - $startTime, 2);
echo "\n========================\n";
echo "Completed in {$duration}s\n";

// Log cron run
Database::execute('logs',
    "INSERT INTO activity_log (user_id, action, details, created_at)
     VALUES (0, 'cron_run', ?, datetime('now'))",
    [json_encode($results)]
);

/**
 * Check for subscriptions expiring in 3 days
 */
function checkExpiringSubscriptions() {
    $expiring = Database::queryAll('billing',
        "SELECT s.*, u.email, u.first_name 
         FROM subscriptions s
         JOIN users u ON u.id = s.user_id
         WHERE s.status = 'active' 
         AND s.end_date BETWEEN datetime('now') AND datetime('now', '+3 days')
         AND NOT EXISTS (
             SELECT 1 FROM payment_failures pf 
             WHERE pf.user_id = s.user_id AND pf.notified = 1 
             AND pf.created_at > datetime('now', '-3 days')
         )"
    );
    
    $notified = 0;
    foreach ($expiring as $sub) {
        // Check if VIP (they don't expire)
        if (VIPManager::isVIP($sub['email'])) {
            continue;
        }
        
        // TODO: Send email notification
        // For now, just log it
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, created_at)
             VALUES (?, 'subscription_expiring_notice', ?, datetime('now'))",
            [$sub['user_id'], json_encode(['end_date' => $sub['end_date']])]
        );
        
        $notified++;
    }
    
    return $notified;
}

/**
 * Clean up pending orders older than 24 hours
 */
function cleanupPendingOrders() {
    $result = Database::execute('billing',
        "UPDATE pending_orders SET status = 'expired' 
         WHERE status = 'pending' 
         AND created_at < datetime('now', '-24 hours')"
    );
    
    return $result ? $result->rowCount() : 0;
}

/**
 * Check health of all VPN servers
 */
function checkServerHealth() {
    $servers = [
        'NY (66.94.103.91)' => ['ip' => '66.94.103.91', 'port' => 8080],
        'STL (144.126.133.253)' => ['ip' => '144.126.133.253', 'port' => 8080],
        'TX (66.241.124.4)' => ['ip' => '66.241.124.4', 'port' => 8080],
        'CAN (66.241.125.247)' => ['ip' => '66.241.125.247', 'port' => 8080]
    ];
    
    $results = [];
    
    foreach ($servers as $name => $server) {
        $healthy = false;
        $message = 'Unknown';
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://{$server['ip']}:{$server['port']}/health");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $healthy = ($data['status'] ?? '') === 'healthy';
                $message = $healthy ? 'Online' : 'Unhealthy';
            } else {
                $message = "HTTP {$httpCode}";
            }
        } catch (Exception $e) {
            $message = 'Unreachable';
        }
        
        $results[$name] = [
            'healthy' => $healthy,
            'message' => $message
        ];
        
        // Log unhealthy servers
        if (!$healthy) {
            Database::execute('logs',
                "INSERT INTO activity_log (user_id, action, details, created_at)
                 VALUES (0, 'server_unhealthy', ?, datetime('now'))",
                [json_encode(['server' => $name, 'status' => $message])]
            );
        }
    }
    
    return $results;
}

/**
 * Process payment retry queue
 */
function processPaymentRetries() {
    // Get failed payments that should be retried (max 3 retries, once per day)
    $failures = Database::queryAll('billing',
        "SELECT * FROM payment_failures 
         WHERE retry_count < 3 
         AND created_at < datetime('now', '-1 day')
         ORDER BY created_at ASC 
         LIMIT 10"
    );
    
    $processed = 0;
    
    foreach ($failures as $failure) {
        // Check if user has since paid
        $activeSub = Database::queryOne('billing',
            "SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active'",
            [$failure['user_id']]
        );
        
        if ($activeSub) {
            // User has paid, remove from failures
            Database::execute('billing',
                "DELETE FROM payment_failures WHERE id = ?",
                [$failure['id']]
            );
            continue;
        }
        
        // TODO: Attempt to charge saved payment method via PayPal
        // For now, just increment retry count and send notification
        
        Database::execute('billing',
            "UPDATE payment_failures SET retry_count = retry_count + 1 WHERE id = ?",
            [$failure['id']]
        );
        
        $processed++;
    }
    
    return $processed;
}
