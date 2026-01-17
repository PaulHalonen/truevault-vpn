<?php
/**
 * TrueVault VPN - Cron Job Processor
 * Process scheduled tasks and email queue
 * 
 * Add to crontab:
 * */5 * * * * php /path/to/cron/process-tasks.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    // Allow web access with secret key for testing
    $validKey = 'tv_cron_2025_secret'; // Change this!
    if (($_GET['cron_key'] ?? '') !== $validKey) {
        http_response_code(403);
        die('CLI access only');
    }
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AutomationEngine.php';
require_once __DIR__ . '/../includes/Email.php';

$startTime = microtime(true);
$results = [];

echo "=== TrueVault Cron Processor ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Process scheduled automation tasks
    echo "Processing scheduled tasks...\n";
    $engine = new AutomationEngine();
    $tasksProcessed = $engine->processScheduledTasks(50);
    $results['scheduled_tasks'] = $tasksProcessed;
    echo "  Processed: $tasksProcessed tasks\n";
    
    // 2. Process email queue
    echo "\nProcessing email queue...\n";
    $email = new Email();
    $emailsProcessed = $email->processQueue(20);
    $results['emails_sent'] = $emailsProcessed;
    echo "  Sent: $emailsProcessed emails\n";
    
    // 3. Check for overdue payment reminders
    echo "\nChecking payment reminders...\n";
    $remindersProcessed = processPaymentReminders();
    $results['payment_reminders'] = $remindersProcessed;
    echo "  Processed: $remindersProcessed reminders\n";
    
    // 4. Clean up old logs (keep 30 days)
    echo "\nCleaning up old logs...\n";
    $logsDeleted = cleanupOldLogs();
    $results['logs_cleaned'] = $logsDeleted;
    echo "  Deleted: $logsDeleted old log entries\n";
    
} catch (Exception $e) {
    echo "\n!!! ERROR: " . $e->getMessage() . "\n";
    error_log("Cron error: " . $e->getMessage());
}

$duration = round(microtime(true) - $startTime, 3);
echo "\n=== Completed in {$duration}s ===\n";

// Log cron execution
try {
    $logsDb = new Database('logs');
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS cron_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            duration_ms INTEGER,
            results TEXT
        )
    ");
    
    $stmt = $logsDb->prepare("INSERT INTO cron_log (duration_ms, results) VALUES (?, ?)");
    $stmt->execute([(int)($duration * 1000), json_encode($results)]);
} catch (Exception $e) {
    // Ignore logging errors
}

/**
 * Check for users in grace period who need reminders
 */
function processPaymentReminders() {
    try {
        $mainDb = new Database('main');
        
        // Find users in grace_period status
        $stmt = $mainDb->query("
            SELECT id, email, name, status, updated_at
            FROM users 
            WHERE status = 'grace_period'
        ");
        $users = $stmt->fetchAll();
        
        $processed = 0;
        
        foreach ($users as $user) {
            $daysSinceUpdate = (time() - strtotime($user['updated_at'])) / 86400;
            
            // Day 8+ suspension
            if ($daysSinceUpdate >= 8) {
                $stmt = $mainDb->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$user['id']]);
                $processed++;
            }
        }
        
        return $processed;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Clean up old log entries
 */
function cleanupOldLogs() {
    try {
        $logsDb = new Database('logs');
        $deleted = 0;
        
        // Clean automation_log older than 30 days
        $logsDb->exec("DELETE FROM automation_log WHERE created_at < datetime('now', '-30 days')");
        $deleted += $logsDb->query("SELECT changes()")->fetchColumn();
        
        // Clean email_log older than 30 days
        $logsDb->exec("DELETE FROM email_log WHERE created_at < datetime('now', '-30 days')");
        $deleted += $logsDb->query("SELECT changes()")->fetchColumn();
        
        // Clean completed scheduled_tasks older than 7 days
        $logsDb->exec("DELETE FROM scheduled_tasks WHERE status IN ('completed', 'cancelled') AND executed_at < datetime('now', '-7 days')");
        $deleted += $logsDb->query("SELECT changes()")->fetchColumn();
        
        return $deleted;
    } catch (Exception $e) {
        return 0;
    }
}
