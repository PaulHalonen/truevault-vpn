<?php
/**
 * Setup Server Health Log Table
 * 
 * Run once: https://vpn.the-truth-publishing.com/admin/setup-server-health-log.php
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/plain');

echo "Setting up server_health_log table...\n";

try {
    $logsDb = Database::getInstance('logs');
    
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS server_health_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            latency_ms INTEGER,
            details TEXT,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create index for faster queries
    $logsDb->exec("CREATE INDEX IF NOT EXISTS idx_server_health_server ON server_health_log(server_id)");
    $logsDb->exec("CREATE INDEX IF NOT EXISTS idx_server_health_date ON server_health_log(checked_at)");
    
    echo "✓ server_health_log table created\n";
    echo "✓ Indexes created\n";
    echo "\nDone!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
