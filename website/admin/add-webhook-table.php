<?php
/**
 * Add webhook_log table to logs.db
 * Run once to add missing table
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

$logsDb = Database::getInstance('logs');

// Create webhook_log table
$logsDb->exec("
    CREATE TABLE IF NOT EXISTS webhook_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source TEXT NOT NULL,
        event_type TEXT NOT NULL,
        payload TEXT,
        processed INTEGER DEFAULT 0,
        processed_at TEXT,
        error TEXT,
        received_at TEXT DEFAULT (datetime('now'))
    )
");

// Create index
$logsDb->exec("CREATE INDEX IF NOT EXISTS idx_webhook_source ON webhook_log(source)");
$logsDb->exec("CREATE INDEX IF NOT EXISTS idx_webhook_event ON webhook_log(event_type)");

echo json_encode(['success' => true, 'message' => 'webhook_log table created']);
