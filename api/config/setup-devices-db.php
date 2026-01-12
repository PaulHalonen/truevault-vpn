<?php
/**
 * TrueVault VPN - Setup Devices Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Setting up Devices Database</h2><pre>\n";

$dbDir = __DIR__ . '/../../data';
if (!is_dir($dbDir)) mkdir($dbDir, 0755, true);

try {
    $db = new SQLite3("$dbDir/devices.db");
    
    $db->exec("CREATE TABLE IF NOT EXISTS user_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_name TEXT NOT NULL,
        device_type TEXT DEFAULT 'other',
        server_id INTEGER NOT NULL,
        server_name TEXT NOT NULL,
        private_key TEXT NOT NULL,
        public_key TEXT NOT NULL,
        client_ip TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        last_connected TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_server ON user_devices(server_id)");
    
    echo "✅ Devices database created\n";
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
