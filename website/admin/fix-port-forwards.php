<?php
/**
 * Fix port_forwards.db tables
 */
define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

header('Content-Type: application/json');

try {
    $pfDb = Database::getInstance('port_forwards');
    
    // Create port_forwarding_rules table if not exists
    $pfDb->exec("
        CREATE TABLE IF NOT EXISTS port_forwarding_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            rule_name TEXT NOT NULL,
            external_port INTEGER NOT NULL,
            internal_port INTEGER NOT NULL,
            protocol TEXT DEFAULT 'tcp',
            target_ip TEXT NOT NULL,
            enabled INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    // Create discovered_devices table if not exists
    $pfDb->exec("
        CREATE TABLE IF NOT EXISTS discovered_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            ip_address TEXT NOT NULL,
            mac_address TEXT,
            hostname TEXT,
            device_type TEXT,
            device_name TEXT,
            vendor TEXT,
            open_ports TEXT,
            last_seen TEXT DEFAULT (datetime('now')),
            created_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    echo json_encode(['success' => true, 'message' => 'Port forwarding tables created']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
