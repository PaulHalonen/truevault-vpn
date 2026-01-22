<?php
/**
 * Add webhook_log table to logs database
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

try {
    $logsDb = Database::getInstance('logs');
    
    // Create webhook_log table if not exists
    $logsDb->exec("
        CREATE TABLE IF NOT EXISTS webhook_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            source TEXT NOT NULL,
            event_type TEXT NOT NULL,
            payload TEXT,
            headers TEXT,
            processed INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    $logsDb->exec("CREATE INDEX IF NOT EXISTS idx_webhook_source ON webhook_log(source)");
    $logsDb->exec("CREATE INDEX IF NOT EXISTS idx_webhook_created ON webhook_log(created_at)");
    
    echo json_encode(['success' => true, 'message' => 'webhook_log table created']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
