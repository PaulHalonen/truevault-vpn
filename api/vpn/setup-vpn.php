<?php
/**
 * TrueVault VPN - VPN Database Setup
 * Creates VPN-related tables including user_peers for access control
 */

require_once __DIR__ . '/../config/database.php';

echo "<pre>";
echo "=== TrueVault VPN Database Setup ===\n\n";

try {
    $db = Database::getConnection('vpn');
    
    // User peers table - tracks which users have access to which servers
    $db->exec("CREATE TABLE IF NOT EXISTS user_peers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        public_key TEXT NOT NULL,
        assigned_ip TEXT NOT NULL,
        status TEXT DEFAULT 'active',
        provisioned_at DATETIME,
        revoked_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, server_id)
    )");
    echo "✓ Created user_peers table\n";
    
    // VPN connections table
    $db->exec("CREATE TABLE IF NOT EXISTS vpn_connections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        assigned_ip TEXT,
        status TEXT DEFAULT 'connected',
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        disconnected_at DATETIME
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
        network TEXT,
        status TEXT DEFAULT 'online',
        is_vip INTEGER DEFAULT 0,
        vip_user_email TEXT,
        max_connections INTEGER DEFAULT 100,
        current_connections INTEGER DEFAULT 0,
        allows_cameras INTEGER DEFAULT 1,
        allows_gaming INTEGER DEFAULT 1,
        allows_torrents INTEGER DEFAULT 1,
        bandwidth_limit TEXT,
        usage_instructions TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Created vpn_servers table\n";
    
    // Insert servers
    $servers = [
        [1, 'US-East', 'New York', '66.94.103.91', 51820, 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', '10.0.0', 0, null, 'RECOMMENDED FOR HOME USE - Gaming, cameras, torrents all allowed', 1, 1, 1, 'unlimited'],
        [2, 'US-Central VIP', 'St. Louis', '144.126.133.253', 51820, 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', '10.0.1', 1, 'seige235@yahoo.com', 'YOUR PRIVATE SERVER - Exclusively yours', 1, 1, 1, 'unlimited'],
        [3, 'US-South', 'Dallas', '66.241.124.4', 51820, 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', '10.10.1', 0, null, 'STREAMING ONLY - Netflix not flagged, NO gaming/torrents/cameras', 0, 0, 0, 'limited'],
        [4, 'Canada', 'Toronto', '66.241.125.247', 51820, 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', '10.10.0', 0, null, 'CANADIAN STREAMING - Netflix not flagged, NO gaming/torrents/cameras', 0, 0, 0, 'limited']
    ];
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO vpn_servers 
        (id, name, location, ip_address, port, public_key, network, is_vip, vip_user_email, usage_instructions, allows_cameras, allows_gaming, allows_torrents, bandwidth_limit)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($servers as $server) {
        $stmt->execute($server);
    }
    echo "✓ Inserted 4 VPN servers\n";
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_user ON user_peers(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_server ON user_peers(server_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_peers_status ON user_peers(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_connections_user ON vpn_connections(user_id)");
    echo "✓ Created indexes\n";
    
    echo "\n=== VPN Database Setup Complete ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
