<?php
/**
 * Setup Automation Tables
 * Task 9.10 - Verify/create automation database tables
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Setting up Automation Tables</h1><pre>";

try {
    $db = Database::getInstance('logs');
    
    // Create workflows table
    $db->exec("
        CREATE TABLE IF NOT EXISTS workflows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            trigger_event TEXT NOT NULL,
            steps TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ workflows table created\n";
    
    // Create automation_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS automation_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER,
            status TEXT,
            data TEXT,
            error TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ automation_logs table created\n";
    
    // Create scheduled_tasks table
    $db->exec("
        CREATE TABLE IF NOT EXISTS scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_type TEXT NOT NULL,
            data TEXT,
            scheduled_for DATETIME,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ scheduled_tasks table created\n";
    
    // Insert default workflows
    $workflows = [
        ['New Subscription', 'subscription.activated', '["send_welcome_email","create_device_config","log_activation"]'],
        ['Dedicated Server', 'subscription.dedicated', '["provision_server","send_config_email","log_provisioning"]'],
        ['Payment Failed', 'payment.failed', '["send_retry_email","schedule_followup","log_failure"]'],
        ['Subscription Cancelled', 'subscription.cancelled', '["send_farewell_email","revoke_access","log_cancellation"]']
    ];
    
    foreach ($workflows as $w) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO workflows (name, trigger_event, steps) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $w[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $w[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $w[2], SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "✅ Default workflows inserted\n";
    
    echo "\n✅ Automation setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
