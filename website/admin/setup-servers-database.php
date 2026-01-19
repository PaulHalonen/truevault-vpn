<?php
/**
 * TrueVault VPN - Server Management Database Setup
 * 
 * Creates tables for managing VPN servers:
 * - servers (main server inventory)
 * - server_costs (monthly billing tracking)
 * - server_logs (health check history)
 * - server_bandwidth (usage tracking)
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';

echo "<h1>Server Management Database Setup</h1>\n";
echo "<p>Creating server management tables...</p>\n";

try {
    $db = Database::getInstance();
    $serversConn = $db->getConnection('servers');
    
    // 1. Create servers table
    echo "<h2>1. Creating servers table...</h2>\n";
    $serversConn->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            location TEXT NOT NULL,
            country_code TEXT,
            ip_address TEXT NOT NULL,
            port INTEGER DEFAULT 51820,
            public_key TEXT NOT NULL,
            private_key TEXT NOT NULL,
            endpoint TEXT NOT NULL,
            provider TEXT,
            provider_id TEXT,
            is_active BOOLEAN DEFAULT 1,
            is_visible BOOLEAN DEFAULT 1,
            max_users INTEGER DEFAULT 500,
            current_users INTEGER DEFAULT 0,
            access_level TEXT DEFAULT 'public',
            vip_email TEXT,
            allowed_users TEXT,
            streaming_optimized BOOLEAN DEFAULT 0,
            port_forwarding BOOLEAN DEFAULT 1,
            monthly_cost DECIMAL(10,2),
            currency TEXT DEFAULT 'USD',
            bandwidth_limit TEXT,
            bandwidth_used BIGINT DEFAULT 0,
            uptime_percentage DECIMAL(5,2) DEFAULT 99.9,
            last_health_check DATETIME,
            health_status TEXT DEFAULT 'unknown',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p style='color: green;'>✓ servers table created</p>\n";
    
    // 2. Create server_costs table
    echo "<h2>2. Creating server_costs table...</h2>\n";
    $serversConn->exec("
        CREATE TABLE IF NOT EXISTS server_costs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT DEFAULT 'USD',
            billing_month TEXT NOT NULL,
            billing_date DATE,
            bandwidth_gb DECIMAL(10,2),
            user_count INTEGER,
            uptime_hours DECIMAL(10,2),
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ server_costs table created</p>\n";
    
    // 3. Create server_logs table
    echo "<h2>3. Creating server_logs table...</h2>\n";
    $serversConn->exec("
        CREATE TABLE IF NOT EXISTS server_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            check_type TEXT NOT NULL,
            status TEXT NOT NULL,
            response_time INTEGER,
            details TEXT,
            error_message TEXT,
            checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color: green;'>✓ server_logs table created</p>\n";
    
    // 4. Create server_bandwidth table
    echo "<h2>4. Creating server_bandwidth table...</h2>\n";
    $serversConn->exec("
        CREATE TABLE IF NOT EXISTS server_bandwidth (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER NOT NULL,
            date DATE NOT NULL,
            bytes_sent BIGINT DEFAULT 0,
            bytes_received BIGINT DEFAULT 0,
            total_bytes BIGINT DEFAULT 0,
            active_connections INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
            UNIQUE(server_id, date)
        )
    ");
    echo "<p style='color: green;'>✓ server_bandwidth table created</p>\n";
    
    // 5. Check if servers already exist
    $stmt = $serversConn->query("SELECT COUNT(*) as count FROM servers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<h2>5. Servers already populated</h2>\n";
        echo "<p style='color: orange;'>⚠ Found {$result['count']} existing servers. Skipping population.</p>\n";
    } else {
        echo "<h2>5. Populating initial server data...</h2>\n";
        
        // Generate WireGuard keys for each server
        function generateWireGuardKeys() {
            $privateKey = base64_encode(random_bytes(32));
            $publicKey = base64_encode(random_bytes(32));
            return ['private' => $privateKey, 'public' => $publicKey];
        }
        
        // Server 1: Contabo New York (Public Shared)
        $keys1 = generateWireGuardKeys();
        $serversConn->exec("
            INSERT INTO servers (
                name, location, country_code, ip_address, port,
                public_key, private_key, endpoint,
                provider, provider_id, is_visible, access_level,
                max_users, monthly_cost, currency, bandwidth_limit
            ) VALUES (
                'Contabo New York',
                'New York, USA',
                'US',
                '66.94.103.91',
                51820,
                '{$keys1['public']}',
                '{$keys1['private']}',
                '66.94.103.91:51820',
                'Contabo',
                'vmi2990026',
                1,
                'public',
                500,
                6.75,
                'USD',
                'Unlimited'
            )
        ");
        echo "<p style='color: green;'>✓ Server 1: Contabo New York (Public)</p>\n";
        
        // Server 2: Contabo St. Louis (VIP ONLY)
        $keys2 = generateWireGuardKeys();
        $serversConn->exec("
            INSERT INTO servers (
                name, location, country_code, ip_address, port,
                public_key, private_key, endpoint,
                provider, provider_id, is_visible, access_level,
                vip_email, max_users, monthly_cost, currency, bandwidth_limit
            ) VALUES (
                'Contabo St. Louis VIP',
                'St. Louis, USA',
                'US',
                '144.126.133.253',
                51820,
                '{$keys2['public']}',
                '{$keys2['private']}',
                '144.126.133.253:51820',
                'Contabo',
                'vmi2990005',
                0,
                'vip',
                'seige235@yahoo.com',
                1,
                6.15,
                'USD',
                'Unlimited'
            )
        ");
        echo "<p style='color: green;'>✓ Server 2: Contabo St. Louis (VIP ONLY - seige235@yahoo.com)</p>\n";
        
        // Server 3: Fly.io Dallas (Public Shared)
        $keys3 = generateWireGuardKeys();
        $serversConn->exec("
            INSERT INTO servers (
                name, location, country_code, ip_address, port,
                public_key, private_key, endpoint,
                provider, provider_id, is_visible, access_level,
                max_users, streaming_optimized, bandwidth_limit
            ) VALUES (
                'Fly.io Dallas',
                'Dallas, USA',
                'US',
                '66.241.124.4',
                51820,
                '{$keys3['public']}',
                '{$keys3['private']}',
                '66.241.124.4:51820',
                'Fly.io',
                'truevault-dallas',
                1,
                'public',
                500,
                1,
                'Limited'
            )
        ");
        echo "<p style='color: green;'>✓ Server 3: Fly.io Dallas (Streaming Optimized)</p>\n";
        
        // Server 4: Fly.io Toronto (Public Shared)
        $keys4 = generateWireGuardKeys();
        $serversConn->exec("
            INSERT INTO servers (
                name, location, country_code, ip_address, port,
                public_key, private_key, endpoint,
                provider, provider_id, is_visible, access_level,
                max_users, bandwidth_limit
            ) VALUES (
                'Fly.io Toronto',
                'Toronto, Canada',
                'CA',
                '66.241.125.247',
                51820,
                '{$keys4['public']}',
                '{$keys4['private']}',
                '66.241.125.247:51820',
                'Fly.io',
                'truevault-toronto',
                1,
                'public',
                500,
                'Limited'
            )
        ");
        echo "<p style='color: green;'>✓ Server 4: Fly.io Toronto (Canadian Server)</p>\n";
        
        echo "<p><strong>4 servers added successfully!</strong></p>\n";
    }
    
    // 6. Verify setup
    echo "<h2>6. Verification</h2>\n";
    $stmt = $serversConn->query("SELECT id, name, location, ip_address, access_level, vip_email FROM servers ORDER BY id");
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>\n";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>ID</th><th>Name</th><th>Location</th><th>IP Address</th><th>Access Level</th><th>VIP Email</th>";
    echo "</tr>\n";
    
    foreach ($servers as $server) {
        $bgColor = $server['access_level'] === 'vip' ? '#fff3cd' : '#ffffff';
        echo "<tr style='background: {$bgColor};'>";
        echo "<td>{$server['id']}</td>";
        echo "<td><strong>{$server['name']}</strong></td>";
        echo "<td>{$server['location']}</td>";
        echo "<td><code>{$server['ip_address']}</code></td>";
        echo "<td><strong>" . strtoupper($server['access_level']) . "</strong></td>";
        echo "<td>" . ($server['vip_email'] ?: '-') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2 style='color: green;'>✅ Server Management Database Setup Complete!</h2>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Configure WireGuard on each server</li>\n";
    echo "<li>Set up health monitoring</li>\n";
    echo "<li>Test server connectivity</li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
