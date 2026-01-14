<?php
/**
 * TrueVault VPN - Device Workflow Migration
 * Updates user_peers table to support multiple devices per user
 * 
 * Run this once to migrate from old schema to new device-based schema
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>TrueVault VPN - Device Workflow Migration</h1>";
echo "<pre>";

try {
    $db = Database::getConnection('vpn');
    
    echo "Starting migration...\n\n";
    
    // Step 1: Check if already migrated
    $cols = $db->query("PRAGMA table_info(user_peers)")->fetchAll(PDO::FETCH_ASSOC);
    $hasDeviceName = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'device_name') {
            $hasDeviceName = true;
            break;
        }
    }
    
    if ($hasDeviceName) {
        echo "❗ Migration already applied\n";
        echo "   user_peers table already has device support\n\n";
    } else {
        echo "Step 1: Backing up current user_peers table...\n";
        
        // Create backup
        $db->exec("CREATE TABLE IF NOT EXISTS user_peers_backup AS SELECT * FROM user_peers");
        echo "✓ Backup created as user_peers_backup\n\n";
        
        echo "Step 2: Creating new user_peers table with device support...\n";
        
        // Drop old table
        $db->exec("DROP TABLE user_peers");
        
        // Create new table with device_name and without unique constraint
        $db->exec("CREATE TABLE user_peers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            device_name TEXT NOT NULL,
            public_key TEXT NOT NULL,
            assigned_ip TEXT NOT NULL UNIQUE,
            status TEXT DEFAULT 'active',
            provisioned_at DATETIME,
            revoked_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, device_name)
        )");
        echo "✓ New user_peers table created\n";
        echo "  - Added: device_name field\n";
        echo "  - Removed: UNIQUE constraint on (user_id, server_id)\n";
        echo "  - Added: UNIQUE constraint on (user_id, device_name)\n";
        echo "  - Added: UNIQUE constraint on assigned_ip\n\n";
        
        echo "Step 3: Migrating existing data...\n";
        
        // Migrate old data (give generic device names)
        $oldData = $db->query("SELECT * FROM user_peers_backup")->fetchAll(PDO::FETCH_ASSOC);
        $migrated = 0;
        
        $stmt = $db->prepare("INSERT INTO user_peers 
            (user_id, server_id, device_name, public_key, assigned_ip, status, provisioned_at, revoked_at, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($oldData as $row) {
            // Generate device name based on server
            $serverNames = [
                1 => 'Primary Device',
                2 => 'VIP Device',
                3 => 'Streaming Device',
                4 => 'Canadian Device'
            ];
            $deviceName = $serverNames[$row['server_id']] ?? "Device {$row['id']}";
            
            $stmt->execute([
                $row['user_id'],
                $row['server_id'],
                $deviceName,
                $row['public_key'],
                $row['assigned_ip'],
                $row['status'],
                $row['provisioned_at'],
                $row['revoked_at'],
                $row['created_at'],
                $row['updated_at']
            ]);
            $migrated++;
        }
        
        echo "✓ Migrated {$migrated} existing peers\n\n";
        
        echo "Step 4: Recreating indexes...\n";
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_user ON user_peers(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_server ON user_peers(server_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_status ON user_peers(status)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_device ON user_peers(device_name)");
        echo "✓ Indexes created\n\n";
    }
    
    // Step 5: Add display_name and country_flag to vpn_servers if not exists
    echo "Step 5: Updating vpn_servers table...\n";
    
    $serverCols = $db->query("PRAGMA table_info(vpn_servers)")->fetchAll(PDO::FETCH_ASSOC);
    $hasDisplayName = false;
    $hasCountryFlag = false;
    
    foreach ($serverCols as $col) {
        if ($col['name'] === 'display_name') $hasDisplayName = true;
        if ($col['name'] === 'country_flag') $hasCountryFlag = true;
    }
    
    if (!$hasDisplayName) {
        $db->exec("ALTER TABLE vpn_servers ADD COLUMN display_name TEXT");
        echo "✓ Added display_name column\n";
        
        // Set display names for existing servers
        $db->exec("UPDATE vpn_servers SET display_name = 'New York' WHERE id = 1");
        $db->exec("UPDATE vpn_servers SET display_name = 'St. Louis (VIP)' WHERE id = 2");
        $db->exec("UPDATE vpn_servers SET display_name = 'Dallas' WHERE id = 3");
        $db->exec("UPDATE vpn_servers SET display_name = 'Toronto' WHERE id = 4");
        echo "✓ Set display names for existing servers\n";
    }
    
    if (!$hasCountryFlag) {
        $db->exec("ALTER TABLE vpn_servers ADD COLUMN country_flag TEXT");
        echo "✓ Added country_flag column\n";
        
        // Set flags for existing servers
        $db->exec("UPDATE vpn_servers SET country_flag = '🇺🇸' WHERE id IN (1, 2, 3)");
        $db->exec("UPDATE vpn_servers SET country_flag = '🇨🇦' WHERE id = 4");
        echo "✓ Set flags for existing servers\n";
    }
    
    // Add latency and cpu_load columns for UI display
    $hasLatency = false;
    $hasCpuLoad = false;
    foreach ($serverCols as $col) {
        if ($col['name'] === 'latency') $hasLatency = true;
        if ($col['name'] === 'cpu_load') $hasCpuLoad = true;
    }
    
    if (!$hasLatency) {
        $db->exec("ALTER TABLE vpn_servers ADD COLUMN latency INTEGER DEFAULT 50");
        echo "✓ Added latency column\n";
        
        // Set default latencies
        $db->exec("UPDATE vpn_servers SET latency = 25 WHERE id = 1"); // NY - closest
        $db->exec("UPDATE vpn_servers SET latency = 35 WHERE id = 2"); // STL
        $db->exec("UPDATE vpn_servers SET latency = 45 WHERE id = 3"); // Dallas
        $db->exec("UPDATE vpn_servers SET latency = 55 WHERE id = 4"); // Canada
    }
    
    if (!$hasCpuLoad) {
        $db->exec("ALTER TABLE vpn_servers ADD COLUMN cpu_load INTEGER DEFAULT 0");
        echo "✓ Added cpu_load column\n";
        
        // Set default loads
        $db->exec("UPDATE vpn_servers SET cpu_load = 15 WHERE id = 1"); // NY - low
        $db->exec("UPDATE vpn_servers SET cpu_load = 5 WHERE id = 2");  // VIP - very low
        $db->exec("UPDATE vpn_servers SET cpu_load = 35 WHERE id = 3"); // Dallas - medium
        $db->exec("UPDATE vpn_servers SET cpu_load = 40 WHERE id = 4"); // Canada - medium
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ MIGRATION COMPLETE\n";
    echo "========================================\n\n";
    
    echo "What changed:\n";
    echo "- user_peers now supports multiple devices per user\n";
    echo "- Each device has a unique name (e.g., 'My iPhone', 'Work Laptop')\n";
    echo "- Devices can connect to different servers\n";
    echo "- vpn_servers has display_name, country_flag, latency, cpu_load\n\n";
    
    echo "Next steps:\n";
    echo "1. Test device adding with new API endpoints\n";
    echo "2. Test server switching\n";
    echo "3. If everything works, you can drop user_peers_backup table\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
