<?php
/**
 * TrueVault VPN - Devices Database Setup
 * Creates all device-related tables
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>TrueVault Devices Database Setup</h1>";
echo "<pre>";

try {
    $devicesDb = Database::getConnection('devices');
    
    // User devices table
    $devicesDb->exec("
        CREATE TABLE IF NOT EXISTS user_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id TEXT UNIQUE,
            name TEXT,
            type TEXT DEFAULT 'unknown',
            mac_address TEXT,
            ip_address TEXT,
            swapped_from TEXT,
            status TEXT DEFAULT 'active',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            removed_at TEXT,
            last_seen TEXT
        )
    ");
    echo "✓ user_devices table created\n";
    
    // User cameras table
    $devicesDb->exec("
        CREATE TABLE IF NOT EXISTS user_cameras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            camera_id TEXT UNIQUE,
            name TEXT,
            type TEXT DEFAULT 'generic',
            ip_address TEXT NOT NULL,
            port INTEGER DEFAULT 554,
            username TEXT DEFAULT 'admin',
            password TEXT,
            server_id INTEGER DEFAULT 1,
            external_port INTEGER,
            status TEXT DEFAULT 'active',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            removed_at TEXT,
            last_online TEXT
        )
    ");
    echo "✓ user_cameras table created\n";
    
    // Device activity log
    $devicesDb->exec("
        CREATE TABLE IF NOT EXISTS device_activity (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id TEXT,
            user_id INTEGER,
            action TEXT,
            ip_address TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ device_activity table created\n";
    
    // Create indexes
    $devicesDb->exec("CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id)");
    $devicesDb->exec("CREATE INDEX IF NOT EXISTS idx_devices_status ON user_devices(status)");
    $devicesDb->exec("CREATE INDEX IF NOT EXISTS idx_cameras_user ON user_cameras(user_id)");
    $devicesDb->exec("CREATE INDEX IF NOT EXISTS idx_cameras_server ON user_cameras(server_id)");
    echo "✓ indexes created\n";
    
    echo "\n========================================\n";
    echo "✅ DEVICES DATABASE SETUP COMPLETE\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
