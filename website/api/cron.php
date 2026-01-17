<?php
/**
 * TrueVault VPN - Cron Job Processor
 * Run every 5 minutes: */5 * * * * php /path/to/cron.php
 * 
 * Or call via URL: /api/cron.php?key=YOUR_CRON_KEY
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AutomationEngine.php';
require_once __DIR__ . '/../includes/Email.php';

// Security: Verify cron key or CLI execution
$isCommandLine = php_sapi_name() === 'cli';
$cronKey = $_GET['key'] ?? '';
$validKey = 'truevault_cron_' . date('Ymd'); // Simple daily rotating key

if (!$isCommandLine && $cronKey !== $validKey) {
    http_response_code(403);
    die('Access denied');
}

// Set execution time limit
set_time_limit(300); // 5 minutes max

$results = [
    'started' => date('Y-m-d H:i:s'),
    'tasks' => []
];

// ============================================
// 1. PROCESS SCHEDULED AUTOMATION TASKS
// ============================================

try {
    $engine = new AutomationEngine();
    $processed = $engine->processScheduledTasks(50);
    $results['tasks']['automation'] = [
        'processed' => $processed,
        'status' => 'success'
    ];
} catch (Exception $e) {
    $results['tasks']['automation'] = [
        'error' => $e->getMessage(),
        'status' => 'failed'
    ];
}

// ============================================
// 2. PROCESS EMAIL QUEUE
// ============================================

try {
    $email = new Email();
    $sent = $email->processQueue(20);
    $results['tasks']['email_queue'] = [
        'sent' => $sent,
        'status' => 'success'
    ];
} catch (Exception $e) {
    $results['tasks']['email_queue'] = [
        'error' => $e->getMessage(),
        'status' => 'failed'
    ];
}

// ============================================
// 3. CHECK SERVER HEALTH
// ============================================

try {
    $serversDb = new Database('servers');
    
    // Ensure table exists
    $serversDb->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            ip TEXT NOT NULL,
            location TEXT,
            type TEXT DEFAULT 'shared',
            public_key TEXT,
            endpoint TEXT,
            port INTEGER DEFAULT 51820,
            is_active INTEGER DEFAULT 1,
            last_check DATETIME,
            last_status TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $servers = $serversDb->query("SELECT * FROM servers WHERE is_active = 1")->fetchAll();
    $serverResults = [];
    
    foreach ($servers as $server) {
        $ip = $server['ip'];
        $port = $server['port'] ?? 51820;
        
        // Simple port check
        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        $isUp = $socket !== false;
        if ($socket) fclose($socket);
        
        // Update status
        $stmt = $serversDb->prepare("
            UPDATE servers SET last_check = datetime('now'), last_status = ? WHERE id = ?
        ");
        $stmt->execute([$isUp ? 'online' : 'offline', $server['id']]);
        
        $serverResults[$server['name']] = $isUp ? 'online' : 'offline';
        
        // If server went down, trigger alert (would need to track previous state)
        if (!$isUp) {
            // Log the issue
            $logsDb = new Database('logs');
            $logsDb->exec("
                CREATE TABLE IF NOT EXISTS server_health_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    server_id INTEGER,
                    server_name TEXT,
                    status TEXT,
                    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $stmt = $logsDb->prepare("
                INSERT INTO server_health_log (server_id, server_name, status) VALUES (?, ?, ?)
            ");
            $stmt->execute([$server['id'], $server['name'], 'offline']);
        }
    }
    
    $results['tasks']['server_health'] = [
        'servers' => $serverResults,
        'status' => 'success'
    ];
} catch (Exception $e) {
    $results['tasks']['server_health'] = [
        'error' => $e->getMessage(),
        'status' => 'failed'
    ];
}

// ============================================
// 4. CLEAN OLD LOGS (weekly)
// ============================================

if (date('N') == 1 && date('G') < 1) { // Monday, midnight hour
    try {
        $logsDb = new Database('logs');
        
        // Delete logs older than 90 days
        $logsDb->exec("DELETE FROM api_requests WHERE created_at < datetime('now', '-90 days')");
        $logsDb->exec("DELETE FROM automation_log WHERE created_at < datetime('now', '-90 days')");
        $logsDb->exec("DELETE FROM email_log WHERE created_at < datetime('now', '-90 days')");
        
        $results['tasks']['log_cleanup'] = ['status' => 'success'];
    } catch (Exception $e) {
        $results['tasks']['log_cleanup'] = ['error' => $e->getMessage(), 'status' => 'failed'];
    }
}

// ============================================
// 5. CHECK FOR EXPIRING SUBSCRIPTIONS
// ============================================

try {
    $billingDb = new Database('billing');
    $mainDb = new Database('main');
    
    // Get subscriptions expiring in 3 days (for reminder)
    $stmt = $billingDb->query("
        SELECT s.*, u.email, u.name 
        FROM subscriptions s
        JOIN main.users u ON s.user_id = u.id
        WHERE s.status = 'active' 
        AND date(s.current_period_end) = date('now', '+3 days')
    ");
    // Note: Cross-DB query may not work on all SQLite setups
    
    $results['tasks']['subscription_check'] = ['status' => 'success'];
} catch (Exception $e) {
    $results['tasks']['subscription_check'] = [
        'error' => $e->getMessage(),
        'status' => 'skipped'
    ];
}

$results['completed'] = date('Y-m-d H:i:s');

// Output results
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);

// Also log to file
$logFile = __DIR__ . '/../logs/cron.log';
$logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($results) . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);
