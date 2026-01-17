<?php
/**
 * TrueVault VPN - Scheduled Task Processor (Cron Job)
 * 
 * Run this every 5 minutes via cron:
 * */5 * * * * php /path/to/api/cron/process-tasks.php
 * 
 * Or call via URL with secret key:
 * https://vpn.the-truth-publishing.com/api/cron/process-tasks.php?key=YOUR_CRON_SECRET
 */

// Prevent web access without key
$cronSecret = 'truevault_cron_2025_secret'; // Change this!

if (php_sapi_name() !== 'cli') {
    // Web request - verify secret key
    if (!isset($_GET['key']) || $_GET['key'] !== $cronSecret) {
        http_response_code(403);
        die('Access denied');
    }
}

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/AutomationEngine.php';
require_once __DIR__ . '/../../includes/Email.php';

// Set execution time limit
set_time_limit(300); // 5 minutes max

$startTime = microtime(true);
$results = [
    'started_at' => date('Y-m-d H:i:s'),
    'tasks_processed' => 0,
    'emails_processed' => 0,
    'errors' => []
];

try {
    // 1. Process scheduled automation tasks
    $engine = new AutomationEngine();
    $results['tasks_processed'] = $engine->processScheduledTasks(50);
    
    // 2. Process email queue
    $email = new Email();
    $results['emails_processed'] = $email->processQueue(20);
    
    // 3. Clean up old data (weekly)
    if (date('N') == 1 && date('H') == 3) { // Monday at 3am
        cleanupOldData();
    }
    
} catch (Exception $e) {
    $results['errors'][] = $e->getMessage();
    error_log("Cron error: " . $e->getMessage());
}

$results['duration_ms'] = round((microtime(true) - $startTime) * 1000);
$results['completed_at'] = date('Y-m-d H:i:s');

// Log results
logCronRun($results);

// Output results
if (php_sapi_name() === 'cli') {
    echo "Cron completed:\n";
    echo "- Tasks processed: {$results['tasks_processed']}\n";
    echo "- Emails processed: {$results['emails_processed']}\n";
    echo "- Duration: {$results['duration_ms']}ms\n";
    if (!empty($results['errors'])) {
        echo "- Errors: " . implode(', ', $results['errors']) . "\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);
}

/**
 * Clean up old log data
 */
function cleanupOldData() {
    try {
        $logsDb = new Database('logs');
        
        // Delete automation logs older than 30 days
        $logsDb->exec("DELETE FROM automation_log WHERE created_at < datetime('now', '-30 days')");
        
        // Delete completed workflow executions older than 90 days
        $logsDb->exec("DELETE FROM workflow_executions WHERE status IN ('completed', 'failed') AND completed_at < datetime('now', '-90 days')");
        
        // Delete old scheduled tasks
        $logsDb->exec("DELETE FROM scheduled_tasks WHERE status IN ('completed', 'cancelled', 'failed') AND executed_at < datetime('now', '-30 days')");
        
        // Delete email logs older than 60 days
        $logsDb->exec("DELETE FROM email_log WHERE created_at < datetime('now', '-60 days')");
        
        error_log("Cron cleanup completed");
        
    } catch (Exception $e) {
        error_log("Cron cleanup error: " . $e->getMessage());
    }
}

/**
 * Log cron run to database
 */
function logCronRun($results) {
    try {
        $logsDb = new Database('logs');
        
        // Ensure table exists
        $logsDb->exec("
            CREATE TABLE IF NOT EXISTS cron_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tasks_processed INTEGER DEFAULT 0,
                emails_processed INTEGER DEFAULT 0,
                duration_ms INTEGER,
                errors TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $logsDb->prepare("
            INSERT INTO cron_log (tasks_processed, emails_processed, duration_ms, errors)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $results['tasks_processed'],
            $results['emails_processed'],
            $results['duration_ms'],
            !empty($results['errors']) ? json_encode($results['errors']) : null
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to log cron run: " . $e->getMessage());
    }
}
