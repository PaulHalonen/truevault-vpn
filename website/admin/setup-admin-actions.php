<?php
/**
 * Setup Admin Actions Table
 * For Task 9.9 - Troubleshooting logging
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Setting up Admin Actions Table</h1><pre>";

try {
    $db = Database::getInstance('logs');
    
    // Create admin_actions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_actions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            server_id INTEGER,
            output TEXT,
            admin_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ admin_actions table created\n";
    
    // Create server_health_log if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS server_health_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            status TEXT,
            latency_ms INTEGER,
            details TEXT,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ server_health_log table verified\n";
    
    echo "\n✅ Setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
