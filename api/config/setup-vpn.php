<?php
/**
 * TrueVault VPN - VPN Database Setup
 * Creates peer tracking and connection tables
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>TrueVault VPN Database Setup</h1><pre>\n";

try {
    $db = Database::getConnection('vpn');
    
    // User peers table - tracks which servers each user has access to
    $db->exec("CREATE TABLE IF NOT EXISTS user_peers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        public_key TEXT NOT NULL,
        assigned_ip TEXT,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        revoked_at DATETIME,
        UNIQUE(user_id, server_id)
    )");
    echo "✓ Created user_peers table\n";
    
    // VPN connections log
    $db->exec("CREATE TABLE IF NOT EXISTS vpn_connections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        assigned_ip TEXT,
        connected_at DATETIME,
        disconnected_at DATETIME,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0
    )");
    echo "✓ Created vpn_connections table\n";
    
    // VPN servers table
    $db->exec("CREATE TABLE IF NOT EXISTS vpn_servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        location TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        port INTEGER DEFAULT 51820,
        public_key TEXT,
        api_port INTEGER DEFAULT 8080,
        network_prefix TEXT DEFAULT '10.8.0',
        status TEXT DEFAULT 'online',
        is_vip INTEGER DEFAULT 0,
        vip_user_email TEXT,
        max_peers INTEGER DEFAULT 250,
        current_peers INTEGER DEFAULT 0,
        bandwidth_limit TEXT,
        allowed_uses TEXT,
        instructions TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created vpn_servers table\n";
    
    // Insert/update servers
    $servers = [
        [
            'name' => 'New York',
            'location' => 'US-East',
            'ip_address' => '66.94.103.91',
            'port' => 51820,
            'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
            'api_port' => 8080,
            'network_prefix' => '10.0.0',
            'is_vip' => 0,
            'bandwidth_limit' => 'unlimited',
            'allowed_uses' => 'gaming,streaming,torrents,cameras',
            'instructions' => 'RECOMMENDED FOR HOME USE - Use for all home devices, gaming, cameras, streaming. Unlimited bandwidth.'
        ],
        [
            'name' => 'St. Louis VIP',
            'location' => 'US-Central',
            'ip_address' => '144.126.133.253',
            'port' => 51820,
            'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
            'api_port' => 8080,
            'network_prefix' => '10.0.1',
            'is_vip' => 1,
            'vip_user_email' => 'seige235@yahoo.com',
            'bandwidth_limit' => 'unlimited',
            'allowed_uses' => 'everything',
            'instructions' => 'YOUR PRIVATE SERVER - Exclusively yours, unlimited everything. Only you can connect.'
        ],
        [
            'name' => 'Dallas',
            'location' => 'US-South',
            'ip_address' => '66.241.124.4',
            'port' => 51820,
            'public_key' => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
            'api_port' => 8080,
            'network_prefix' => '10.10.1',
            'is_vip' => 0,
            'bandwidth_limit' => 'limited',
            'allowed_uses' => 'streaming',
            'instructions' => 'STREAMING ONLY - Netflix/Hulu not flagged as VPN. NO gaming, torrents, or cameras. Limited bandwidth.'
        ],
        [
            'name' => 'Toronto',
            'location' => 'Canada',
            'ip_address' => '66.241.125.247',
            'port' => 51820,
            'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
            'api_port' => 8080,
            'network_prefix' => '10.10.0',
            'is_vip' => 0,
            'bandwidth_limit' => 'limited',
            'allowed_uses' => 'streaming',
            'instructions' => 'CANADIAN STREAMING - Canadian Netflix/content. NO gaming, torrents, or cameras. Limited bandwidth.'
        ]
    ];
    
    foreach ($servers as $server) {
        $existing = $db->querySingle("SELECT id FROM vpn_servers WHERE ip_address = '{$server['ip_address']}'");
        
        if ($existing) {
            $db->exec("UPDATE vpn_servers SET
                name = '{$server['name']}',
                location = '{$server['location']}',
                port = {$server['port']},
                public_key = '{$server['public_key']}',
                api_port = {$server['api_port']},
                network_prefix = '{$server['network_prefix']}',
                is_vip = {$server['is_vip']},
                vip_user_email = " . ($server['vip_user_email'] ?? 'NULL' ? "'{$server['vip_user_email']}'" : 'NULL') . ",
                bandwidth_limit = '{$server['bandwidth_limit']}',
                allowed_uses = '{$server['allowed_uses']}',
                instructions = '{$server['instructions']}'
                WHERE ip_address = '{$server['ip_address']}'");
            echo "✓ Updated server: {$server['name']}\n";
        } else {
            $vipEmail = isset($server['vip_user_email']) ? "'{$server['vip_user_email']}'" : 'NULL';
            $db->exec("INSERT INTO vpn_servers 
                (name, location, ip_address, port, public_key, api_port, network_prefix, is_vip, vip_user_email, bandwidth_limit, allowed_uses, instructions)
                VALUES 
                ('{$server['name']}', '{$server['location']}', '{$server['ip_address']}', {$server['port']}, 
                 '{$server['public_key']}', {$server['api_port']}, '{$server['network_prefix']}', 
                 {$server['is_vip']}, {$vipEmail}, '{$server['bandwidth_limit']}', '{$server['allowed_uses']}', '{$server['instructions']}')");
            echo "✓ Inserted server: {$server['name']}\n";
        }
    }
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_peers_user ON user_peers(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_peers_server ON user_peers(server_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_peers_status ON user_peers(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_user ON vpn_connections(user_id)");
    echo "✓ Created indexes\n";
    
    echo "\n========================================\n";
    echo "VPN DATABASE SETUP COMPLETE!\n";
    echo "========================================\n";
    
    // Show servers
    echo "\nConfigured Servers:\n";
    $result = $db->query("SELECT id, name, ip_address, is_vip, vip_user_email FROM vpn_servers");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $vip = $row['is_vip'] ? " [VIP: {$row['vip_user_email']}]" : "";
        echo "  {$row['id']}. {$row['name']} ({$row['ip_address']}){$vip}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
