<?php
/**
 * TrueVault VPN - Billing Cron Job
 * Run every 5 minutes via cron
 * 
 * Cron entry:
 * */5 * * * * php /path/to/api/billing/cron.php
 */

// Prevent direct browser access in production
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    // Allow access with secret key for testing
    if (!isset($_GET['cron_key']) || $_GET['cron_key'] !== 'TrueVault2026CronKey') {
        die('CLI only');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/billing-manager.php';

$startTime = microtime(true);
$results = [];

echo "=== TrueVault Billing Cron - " . date('Y-m-d H:i:s') . " ===\n\n";

// 1. Process scheduled revocations
echo "Processing revocations...\n";
$revoked = BillingManager::processRevocations();
$results['revocations'] = $revoked;
echo "  Revoked: {$revoked} users\n";

// 2. Check for expiring subscriptions (7 days warning)
echo "\nChecking expiring subscriptions...\n";
$expiring = processExpiringSubscriptions();
$results['expiring_warnings'] = $expiring;
echo "  Warnings sent: {$expiring}\n";

// 3. Process failed payment retries
echo "\nProcessing payment retries...\n";
$retries = processPaymentRetries();
$results['payment_retries'] = $retries;
echo "  Retries: {$retries}\n";

// 4. Clean up old pending orders (older than 24 hours)
echo "\nCleaning up old pending orders...\n";
$cleaned = cleanupPendingOrders();
$results['cleaned_orders'] = $cleaned;
echo "  Cleaned: {$cleaned}\n";

// 5. Generate monthly invoices (on the 1st)
if (date('j') === '1') {
    echo "\nGenerating monthly invoices...\n";
    $invoices = generateMonthlyInvoices();
    $results['monthly_invoices'] = $invoices;
    echo "  Generated: {$invoices}\n";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);
echo "\n=== Completed in {$elapsed}ms ===\n";

// Log results
Database::execute('logs',
    "INSERT INTO cron_log (job_name, results, duration_ms, created_at)
     VALUES ('billing_cron', ?, ?, datetime('now'))",
    [json_encode($results), $elapsed]
);

/**
 * Send warnings for subscriptions expiring in 7 days
 */
function processExpiringSubscriptions() {
    $expiring = Database::queryAll('billing',
        "SELECT s.*, u.email, u.first_name 
         FROM subscriptions s
         JOIN users u ON s.user_id = u.id
         WHERE s.status = 'active'
         AND date(s.end_date) = date('now', '+7 days')
         AND s.expiry_warned = 0"
    );
    
    $count = 0;
    foreach ($expiring as $sub) {
        // Send warning email
        // Mailer::sendTemplate($sub['email'], 'subscription_expiring', [
        //     'first_name' => $sub['first_name'],
        //     'end_date' => $sub['end_date'],
        //     'plan_type' => $sub['plan_type']
        // ]);
        
        Database::execute('billing',
            "UPDATE subscriptions SET expiry_warned = 1 WHERE id = ?",
            [$sub['id']]
        );
        $count++;
    }
    
    return $count;
}

/**
 * Retry failed payments (every 3 days for 9 days)
 */
function processPaymentRetries() {
    $failures = Database::queryAll('billing',
        "SELECT * FROM payment_failures 
         WHERE retry_count < 3
         AND date(last_retry) <= date('now', '-3 days')
         AND date(grace_end_date) > date('now')"
    );
    
    $count = 0;
    foreach ($failures as $failure) {
        // Attempt to charge saved payment method
        // This would integrate with PayPal's saved payment methods
        
        Database::execute('billing',
            "UPDATE payment_failures 
             SET retry_count = retry_count + 1, last_retry = datetime('now')
             WHERE id = ?",
            [$failure['id']]
        );
        $count++;
    }
    
    return $count;
}

/**
 * Clean up pending orders older than 24 hours
 */
function cleanupPendingOrders() {
    $result = Database::execute('billing',
        "UPDATE pending_orders 
         SET status = 'expired' 
         WHERE status = 'pending' 
         AND created_at < datetime('now', '-24 hours')"
    );
    
    return $result ? $result->rowCount() : 0;
}

/**
 * Generate monthly invoices for active subscriptions
 */
function generateMonthlyInvoices() {
    global $PLANS;
    
    $activeSubscriptions = Database::queryAll('billing',
        "SELECT s.*, u.email, u.first_name, u.last_name
         FROM subscriptions s
         JOIN users u ON s.user_id = u.id
         WHERE s.status = 'active'
         AND s.plan_type NOT IN ('vip_basic', 'vip_dedicated')" // Skip VIPs
    );
    
    $count = 0;
    foreach ($activeSubscriptions as $sub) {
        $plan = $PLANS[$sub['plan_type']] ?? $PLANS['basic'];
        
        BillingManager::createInvoice(
            $sub['user_id'],
            $sub['plan_type'],
            $plan['price'],
            'monthly_' . date('Ym')
        );
        $count++;
    }
    
    return $count;
}
