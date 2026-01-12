<?php
/**
 * TrueVault VPN - VIP System Setup
 * Creates VIP tables and seeds initial VIP users
 * 
 * VIP TIERS:
 * - vip_basic: Free forever, 10 devices (8 + 2 cameras), shared servers only
 * - vip_dedicated: Free dedicated server (owner-paid) OR $9.97/month upgrade
 * 
 * SERVER RULES:
 * - NY (66.94.103.91): Xbox, torrents, high streaming OK - USE FOR HOME DEVICES
 * - Dallas (66.241.124.4): Netflix OK, NO torrents/Xbox (limited bandwidth)
 * - Canada (66.241.125.247): Netflix OK, NO torrents/Xbox (limited bandwidth)
 * - St. Louis (144.126.133.253): DEDICATED - seige235@yahoo.com ONLY (FREE - owner paid)
 * 
 * IMPORTANT: No QR codes - users must copy/paste WireGuard config text
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TrueVault VPN - VIP System Setup</h2><pre>\n";

$dbDir = __DIR__ . '/../../data';

try {
    $db = new SQLite3("$dbDir/vip.db");
    
    // VIP users table
    $db->exec("DROP TABLE IF EXISTS vip_users");
    $db->exec("CREATE TABLE vip_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        tier TEXT DEFAULT 'vip_basic',
        max_devices INTEGER DEFAULT 8,
        max_cameras INTEGER DEFAULT 2,
        dedicated_server_id INTEGER,
        dedicated_server_ip TEXT,
        is_free_dedicated INTEGER DEFAULT 0,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        activated_at TEXT
    )");
    
    // Server info table
    $db->exec("DROP TABLE IF EXISTS server_info");
    $db->exec("CREATE TABLE server_info (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER UNIQUE NOT NULL,
        name TEXT NOT NULL,
        location TEXT,
        ip_address TEXT,
        flag TEXT,
        is_dedicated INTEGER DEFAULT 0,
        dedicated_to_email TEXT,
        bandwidth_type TEXT DEFAULT 'limited',
        torrents_allowed INTEGER DEFAULT 0,
        gaming_allowed INTEGER DEFAULT 0,
        streaming_allowed INTEGER DEFAULT 1,
        cameras_allowed INTEGER DEFAULT 0,
        recommended_for TEXT,
        not_allowed TEXT,
        user_instructions TEXT
    )");
    
    echo "✅ VIP database tables created\n\n";
    
    // Insert VIP users
    $vipUsers = [
        [
            'email' => 'paulhalonen@gmail.com',
            'tier' => 'vip_dedicated',
            'max_devices' => 999,
            'max_cameras' => 999,
            'dedicated_server_id' => null,
            'dedicated_server_ip' => null,
            'is_free_dedicated' => 1,
            'notes' => 'Owner - Full access to everything'
        ],
        [
            'email' => 'seige235@yahoo.com',
            'tier' => 'vip_dedicated',
            'max_devices' => 999,
            'max_cameras' => 12,
            'dedicated_server_id' => 2,
            'dedicated_server_ip' => '144.126.133.253',
            'is_free_dedicated' => 1,
            'notes' => 'VIP - FREE Dedicated St. Louis server (paid by owner)'
        ],
        [
            'email' => 'joyceloveorphanage@gmail.com',
            'tier' => 'vip_basic',
            'max_devices' => 8,
            'max_cameras' => 2,
            'dedicated_server_id' => null,
            'dedicated_server_ip' => null,
            'is_free_dedicated' => 0,
            'notes' => 'VIP Basic - Family/Friend - Can upgrade to dedicated for $9.97/mo'
        ],
        [
            'email' => 'darylsedore@icloud.com',
            'tier' => 'vip_basic',
            'max_devices' => 8,
            'max_cameras' => 2,
            'dedicated_server_id' => null,
            'dedicated_server_ip' => null,
            'is_free_dedicated' => 0,
            'notes' => 'VIP Basic - Family/Friend - Can upgrade to dedicated for $9.97/mo'
        ],
        [
            'email' => 'starbeing23@hotmail.com',
            'tier' => 'vip_basic',
            'max_devices' => 8,
            'max_cameras' => 2,
            'dedicated_server_id' => null,
            'dedicated_server_ip' => null,
            'is_free_dedicated' => 0,
            'notes' => 'VIP Basic - Family/Friend - Can upgrade to dedicated for $9.97/mo'
        ]
    ];
    
    echo "--- Adding VIP Users ---\n";
    foreach ($vipUsers as $vip) {
        $stmt = $db->prepare("INSERT INTO vip_users 
            (email, tier, max_devices, max_cameras, dedicated_server_id, dedicated_server_ip, is_free_dedicated, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, strtolower($vip['email']), SQLITE3_TEXT);
        $stmt->bindValue(2, $vip['tier'], SQLITE3_TEXT);
        $stmt->bindValue(3, $vip['max_devices'], SQLITE3_INTEGER);
        $stmt->bindValue(4, $vip['max_cameras'], SQLITE3_INTEGER);
        $stmt->bindValue(5, $vip['dedicated_server_id'], SQLITE3_INTEGER);
        $stmt->bindValue(6, $vip['dedicated_server_ip'], SQLITE3_TEXT);
        $stmt->bindValue(7, $vip['is_free_dedicated'], SQLITE3_INTEGER);
        $stmt->bindValue(8, $vip['notes'], SQLITE3_TEXT);
        $stmt->execute();
        
        $icon = $vip['tier'] === 'vip_dedicated' ? '👑' : '⭐';
        $free = $vip['is_free_dedicated'] ? ' (FREE)' : '';
        echo "$icon {$vip['email']} - {$vip['tier']}$free\n";
    }
    
    // Insert server info with usage instructions
    echo "\n--- Configuring Servers ---\n";
    
    $servers = [
        [
            'server_id' => 1,
            'name' => 'New York',
            'location' => 'New York, USA',
            'ip_address' => '66.94.103.91',
            'flag' => '🇺🇸',
            'is_dedicated' => 0,
            'bandwidth_type' => 'unlimited',
            'torrents_allowed' => 1,
            'gaming_allowed' => 1,
            'streaming_allowed' => 1,
            'cameras_allowed' => 1,
            'recommended_for' => 'Xbox, PlayStation, Torrents, IP Cameras, Home Devices',
            'not_allowed' => 'None - Full access',
            'user_instructions' => 'RECOMMENDED FOR HOME USE: Use this server for all your home devices, gaming consoles, and IP cameras. Full bandwidth, no restrictions.'
        ],
        [
            'server_id' => 2,
            'name' => 'St. Louis (VIP Dedicated)',
            'location' => 'St. Louis, USA',
            'ip_address' => '144.126.133.253',
            'flag' => '🇺🇸',
            'is_dedicated' => 1,
            'dedicated_to_email' => 'seige235@yahoo.com',
            'bandwidth_type' => 'unlimited',
            'torrents_allowed' => 1,
            'gaming_allowed' => 1,
            'streaming_allowed' => 1,
            'cameras_allowed' => 1,
            'recommended_for' => 'Everything - Private dedicated server',
            'not_allowed' => 'Other users - This server is exclusively for seige235@yahoo.com',
            'user_instructions' => 'YOUR PRIVATE SERVER: This dedicated server is exclusively yours. Unlimited bandwidth, full access to everything. Only you can connect to this server.'
        ],
        [
            'server_id' => 3,
            'name' => 'Dallas',
            'location' => 'Dallas, USA',
            'ip_address' => '66.241.124.4',
            'flag' => '🇺🇸',
            'is_dedicated' => 0,
            'bandwidth_type' => 'limited',
            'torrents_allowed' => 0,
            'gaming_allowed' => 0,
            'streaming_allowed' => 1,
            'cameras_allowed' => 0,
            'recommended_for' => 'Netflix, Streaming Services, Web Browsing',
            'not_allowed' => 'Torrents, Xbox/PlayStation Gaming, IP Cameras',
            'user_instructions' => 'STREAMING ONLY: This server has limited bandwidth. Use ONLY for Netflix and streaming. NOT for gaming, torrents, or cameras. Netflix is NOT flagged on this server.'
        ],
        [
            'server_id' => 4,
            'name' => 'Toronto',
            'location' => 'Toronto, Canada',
            'ip_address' => '66.241.125.247',
            'flag' => '🇨🇦',
            'is_dedicated' => 0,
            'bandwidth_type' => 'limited',
            'torrents_allowed' => 0,
            'gaming_allowed' => 0,
            'streaming_allowed' => 1,
            'cameras_allowed' => 0,
            'recommended_for' => 'Canadian Netflix, Canadian Streaming, Web Browsing',
            'not_allowed' => 'Torrents, Xbox/PlayStation Gaming, IP Cameras',
            'user_instructions' => 'CANADIAN STREAMING: This server has limited bandwidth. Use for Canadian Netflix and streaming content. NOT for gaming, torrents, or cameras. Netflix is NOT flagged on this server.'
        ]
    ];
    
    foreach ($servers as $s) {
        $stmt = $db->prepare("INSERT INTO server_info 
            (server_id, name, location, ip_address, flag, is_dedicated, dedicated_to_email, bandwidth_type, 
             torrents_allowed, gaming_allowed, streaming_allowed, cameras_allowed, recommended_for, not_allowed, user_instructions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $s['server_id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $s['name'], SQLITE3_TEXT);
        $stmt->bindValue(3, $s['location'], SQLITE3_TEXT);
        $stmt->bindValue(4, $s['ip_address'], SQLITE3_TEXT);
        $stmt->bindValue(5, $s['flag'], SQLITE3_TEXT);
        $stmt->bindValue(6, $s['is_dedicated'], SQLITE3_INTEGER);
        $stmt->bindValue(7, $s['dedicated_to_email'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(8, $s['bandwidth_type'], SQLITE3_TEXT);
        $stmt->bindValue(9, $s['torrents_allowed'], SQLITE3_INTEGER);
        $stmt->bindValue(10, $s['gaming_allowed'], SQLITE3_INTEGER);
        $stmt->bindValue(11, $s['streaming_allowed'], SQLITE3_INTEGER);
        $stmt->bindValue(12, $s['cameras_allowed'], SQLITE3_INTEGER);
        $stmt->bindValue(13, $s['recommended_for'], SQLITE3_TEXT);
        $stmt->bindValue(14, $s['not_allowed'], SQLITE3_TEXT);
        $stmt->bindValue(15, $s['user_instructions'], SQLITE3_TEXT);
        $stmt->execute();
        
        $type = $s['is_dedicated'] ? '🔒 DEDICATED' : '🌐 SHARED';
        $bw = $s['bandwidth_type'] === 'unlimited' ? '✓ Unlimited' : '⚠ Limited';
        echo "{$s['flag']} {$s['name']} - $type - $bw\n";
    }
    
    $db->close();
    
    echo "\n========================================\n";
    echo "✅ VIP SYSTEM CONFIGURED!\n";
    echo "========================================\n\n";
    
    echo "VIP USERS (5 total):\n";
    echo "  👑 paulhalonen@gmail.com - Owner (FREE - Full Access)\n";
    echo "  👑 seige235@yahoo.com - FREE Dedicated St. Louis\n";
    echo "  ⭐ joyceloveorphanage@gmail.com - VIP Basic (8 devices + 2 cameras)\n";
    echo "  ⭐ darylsedore@icloud.com - VIP Basic (8 devices + 2 cameras)\n";
    echo "  ⭐ starbeing23@hotmail.com - VIP Basic (8 devices + 2 cameras)\n\n";
    
    echo "SERVER RULES:\n";
    echo "  🇺🇸 NY: ✓Gaming ✓Torrents ✓Cameras ✓Streaming (USE FOR HOME)\n";
    echo "  🇺🇸 St. Louis: DEDICATED to seige235@yahoo.com ONLY (FREE)\n";
    echo "  🇺🇸 Dallas: ✓Streaming ✗Gaming ✗Torrents ✗Cameras (LIMITED BW)\n";
    echo "  🇨🇦 Canada: ✓Streaming ✗Gaming ✗Torrents ✗Cameras (LIMITED BW)\n\n";
    
    echo "UPGRADE PATH:\n";
    echo "  VIP Basic users can upgrade to dedicated for \$9.97/month\n";
    echo "  Dedicated = unlimited devices + 12 cameras + own server\n\n";
    
    echo "NOTE: No QR codes - config must be copy/pasted as text\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
