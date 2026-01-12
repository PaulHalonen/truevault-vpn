<?php
/**
 * TrueVault VPN - Complete VIP & Plans Setup
 * 
 * PLANS:
 * - Basic: $9.99/mo - 3 devices + 1 camera (NY only for camera)
 * - Family: $14.99/mo - 5 devices + 2 cameras
 * - Dedicated: $29.99/mo - Unlimited on dedicated + 5 on NY, 12 cameras max
 * - Corporate: Custom - 12+ cameras, contact sales
 * 
 * VIP (SECRET - Friends & Family):
 * - VIP Basic: FREE - 8 devices + 2 cameras
 * - VIP Dedicated: $9.97/mo - Unlimited devices, dedicated server
 * 
 * SERVERS:
 * - NY (66.94.103.91): SHARED - Full access (torrents, Xbox, streaming) - HOME DEVICES
 * - Dallas (66.241.124.4): SHARED - Netflix OK, NO torrents/Xbox (limited bandwidth)
 * - Canada (66.241.125.247): SHARED - Netflix OK, NO torrents/Xbox (limited bandwidth)  
 * - St. Louis (144.126.133.253): DEDICATED - VIP/Dedicated customers only
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TrueVault VPN - Complete System Setup</h2><pre>\n";

$dbDir = __DIR__ . '/../../data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// ========== VIP DATABASE ==========
echo "=== Setting up VIP Database ===\n";
try {
    $vipDb = new SQLite3("$dbDir/vip.db");
    
    $vipDb->exec("CREATE TABLE IF NOT EXISTS vip_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        tier TEXT DEFAULT 'vip_basic',
        max_devices INTEGER DEFAULT 8,
        max_cameras INTEGER DEFAULT 2,
        dedicated_server_id INTEGER,
        dedicated_server_ip TEXT,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        activated_at TEXT
    )");
    
    $vipDb->exec("CREATE TABLE IF NOT EXISTS server_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER NOT NULL,
        rule_type TEXT NOT NULL,
        allowed INTEGER DEFAULT 1,
        description TEXT
    )");
    
    // Insert VIP users
    $vipUsers = [
        ['paulhalonen@gmail.com', 'vip_dedicated', 999, 999, null, null, 'Owner - Full access'],
        ['seige235@yahoo.com', 'vip_dedicated', 999, 999, 2, '144.126.133.253', 'VIP - Dedicated St. Louis'],
        ['joyceloveorphanage@gmail.com', 'vip_basic', 8, 2, null, null, 'VIP Basic - Family/Friend'],
        ['darylsedore@icloud.com', 'vip_basic', 8, 2, null, null, 'VIP Basic - Family/Friend'],
        ['starbeing23@hotmail.com', 'vip_basic', 8, 2, null, null, 'VIP Basic - Family/Friend']
    ];
    
    $stmt = $vipDb->prepare("INSERT OR REPLACE INTO vip_users 
        (email, tier, max_devices, max_cameras, dedicated_server_id, dedicated_server_ip, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($vipUsers as $v) {
        $stmt->bindValue(1, strtolower($v[0]), SQLITE3_TEXT);
        $stmt->bindValue(2, $v[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $v[2], SQLITE3_INTEGER);
        $stmt->bindValue(4, $v[3], SQLITE3_INTEGER);
        $stmt->bindValue(5, $v[4], SQLITE3_INTEGER);
        $stmt->bindValue(6, $v[5], SQLITE3_TEXT);
        $stmt->bindValue(7, $v[6], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
        $icon = $v[1] === 'vip_dedicated' ? '👑' : '⭐';
        echo "$icon VIP: {$v[0]}\n";
    }
    
    // Server rules
    $vipDb->exec("DELETE FROM server_rules");
    $rules = [
        [1, 'torrents', 1, 'Torrents allowed on NY'],
        [1, 'xbox', 1, 'Xbox/Gaming allowed on NY'],
        [1, 'streaming', 1, 'All streaming allowed'],
        [1, 'cameras', 1, 'Camera connections allowed'],
        [1, 'home_devices', 1, 'Recommended for home devices'],
        [2, 'torrents', 1, 'Torrents allowed (dedicated)'],
        [2, 'xbox', 1, 'Xbox/Gaming allowed (dedicated)'],
        [2, 'streaming', 1, 'All streaming allowed'],
        [2, 'cameras', 1, 'Camera connections allowed'],
        [2, 'dedicated_only', 1, 'Dedicated customers only'],
        [3, 'torrents', 0, 'NO torrents - limited bandwidth'],
        [3, 'xbox', 0, 'NO Xbox - limited bandwidth'],
        [3, 'streaming', 1, 'Netflix/streaming OK'],
        [3, 'cameras', 0, 'No cameras - use NY'],
        [4, 'torrents', 0, 'NO torrents - limited bandwidth'],
        [4, 'xbox', 0, 'NO Xbox - limited bandwidth'],
        [4, 'streaming', 1, 'Netflix/streaming OK'],
        [4, 'cameras', 0, 'No cameras - use NY'],
    ];
    
    $stmt = $vipDb->prepare("INSERT INTO server_rules (server_id, rule_type, allowed, description) VALUES (?, ?, ?, ?)");
    foreach ($rules as $r) {
        $stmt->bindValue(1, $r[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $r[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $r[2], SQLITE3_INTEGER);
        $stmt->bindValue(4, $r[3], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
    
    echo "✅ VIP database configured\n\n";
    $vipDb->close();
    
} catch (Exception $e) {
    echo "❌ VIP DB Error: " . $e->getMessage() . "\n";
}

// ========== PLANS DATABASE ==========
echo "=== Setting up Plans Database ===\n";
try {
    $plansDb = new SQLite3("$dbDir/plans.db");
    
    $plansDb->exec("DROP TABLE IF EXISTS plans");
    $plansDb->exec("CREATE TABLE plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        price_monthly REAL NOT NULL,
        price_yearly REAL,
        max_devices INTEGER DEFAULT 3,
        max_cameras INTEGER DEFAULT 1,
        features TEXT,
        server_access TEXT,
        is_dedicated INTEGER DEFAULT 0,
        is_vip INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1
    )");
    
    $plans = [
        [
            'basic', 'Basic', 'Perfect for individual use',
            9.99, 99.99, 3, 1,
            '["3 devices","1 IP camera (NY server)","All shared servers","256-bit encryption","24/7 support"]',
            '["ny","dallas","canada"]', 0, 0, 1
        ],
        [
            'family', 'Family', 'Share with your household',
            14.99, 149.99, 5, 2,
            '["5 devices","2 IP cameras","All shared servers","256-bit encryption","Priority support","Device swapping"]',
            '["ny","dallas","canada"]', 0, 0, 2
        ],
        [
            'dedicated', 'Dedicated', 'Your own private server',
            29.99, 299.99, 999, 12,
            '["Unlimited devices on dedicated","5 devices on NY shared","Up to 12 cameras","Static IP address","Port forwarding GUI","Terminal access","Full bandwidth"]',
            '["dedicated","ny"]', 1, 0, 3
        ],
        [
            'corporate', 'Corporate', 'Enterprise solutions',
            0, 0, 999, 999,
            '["Unlimited everything","Multiple dedicated servers","Custom configuration","SLA guarantee","Dedicated support","Contact for pricing"]',
            '["dedicated","ny","dallas","canada"]', 1, 0, 4
        ],
        [
            'vip_basic', 'VIP Basic', 'Friends & Family',
            0, 0, 8, 2,
            '["FREE forever","8 devices + 2 cameras","All shared servers","Full support"]',
            '["ny","dallas","canada"]', 0, 1, 10
        ],
        [
            'vip_dedicated', 'VIP Dedicated', 'VIP with dedicated server',
            9.97, 99.97, 999, 12,
            '["Unlimited devices","Dedicated server","12 cameras","Special VIP pricing"]',
            '["dedicated","ny","dallas","canada"]', 1, 1, 11
        ]
    ];
    
    $stmt = $plansDb->prepare("INSERT INTO plans 
        (code, name, description, price_monthly, price_yearly, max_devices, max_cameras, features, server_access, is_dedicated, is_vip, sort_order) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($plans as $p) {
        for ($i = 0; $i < 12; $i++) {
            $stmt->bindValue($i + 1, $p[$i]);
        }
        $stmt->execute();
        $stmt->reset();
        $price = $p[3] > 0 ? "\${$p[3]}/mo" : "FREE";
        echo "📦 {$p[1]}: $price - {$p[5]} devices, {$p[6]} cameras\n";
    }
    
    echo "✅ Plans database configured\n\n";
    $plansDb->close();
    
} catch (Exception $e) {
    echo "❌ Plans DB Error: " . $e->getMessage() . "\n";
}

// ========== SERVERS DATABASE ==========
echo "=== Setting up Servers Database ===\n";
try {
    $serversDb = new SQLite3("$dbDir/servers.db");
    
    $serversDb->exec("DROP TABLE IF EXISTS vpn_servers");
    $serversDb->exec("CREATE TABLE vpn_servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        hostname TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        port INTEGER DEFAULT 51820,
        public_key TEXT,
        location TEXT,
        country TEXT,
        country_code TEXT,
        provider TEXT,
        server_type TEXT DEFAULT 'shared',
        allowed_features TEXT,
        restricted_features TEXT,
        bandwidth_limit TEXT,
        status TEXT DEFAULT 'online',
        dedicated_to_email TEXT,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    
    $servers = [
        [
            'US East - New York', 'us-east.truevault.vpn', '66.94.103.91', 51820,
            'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
            'New York', 'United States', 'US', 'Contabo', 'shared',
            '["torrents","xbox","gaming","streaming","cameras","home_devices"]',
            '[]', 'unlimited', 'online', null,
            'PRIMARY SERVER - Use for home devices, Xbox, cameras, torrents'
        ],
        [
            'US Central - St. Louis (VIP)', 'us-central.truevault.vpn', '144.126.133.253', 51820,
            'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
            'St. Louis', 'United States', 'US', 'Contabo', 'dedicated',
            '["torrents","xbox","gaming","streaming","cameras","port_forwarding","terminal"]',
            '[]', 'unlimited', 'online', 'seige235@yahoo.com',
            'DEDICATED - seige235@yahoo.com only - Full unlimited access'
        ],
        [
            'US South - Dallas', 'us-south.truevault.vpn', '66.241.124.4', 51820,
            'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
            'Dallas', 'United States', 'US', 'Fly.io', 'shared',
            '["streaming","netflix"]',
            '["torrents","xbox","gaming","cameras"]', 'limited', 'online', null,
            'LIMITED BANDWIDTH - Netflix OK, NO torrents/Xbox/cameras'
        ],
        [
            'Canada - Toronto', 'ca-east.truevault.vpn', '66.241.125.247', 51820,
            'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
            'Toronto', 'Canada', 'CA', 'Fly.io', 'shared',
            '["streaming","netflix"]',
            '["torrents","xbox","gaming","cameras"]', 'limited', 'online', null,
            'LIMITED BANDWIDTH - Netflix OK (not flagged), NO torrents/Xbox/cameras'
        ]
    ];
    
    $stmt = $serversDb->prepare("INSERT INTO vpn_servers 
        (name, hostname, ip_address, port, public_key, location, country, country_code, provider, server_type, allowed_features, restricted_features, bandwidth_limit, status, dedicated_to_email, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($servers as $s) {
        for ($i = 0; $i < 16; $i++) {
            $stmt->bindValue($i + 1, $s[$i]);
        }
        $stmt->execute();
        $stmt->reset();
        $type = $s[9] === 'dedicated' ? '🔒' : '🌐';
        echo "$type {$s[0]} ({$s[2]})\n";
    }
    
    echo "✅ Servers database configured\n\n";
    $serversDb->close();
    
} catch (Exception $e) {
    echo "❌ Servers DB Error: " . $e->getMessage() . "\n";
}

echo "========================================\n";
echo "✅ COMPLETE SYSTEM SETUP FINISHED!\n";
echo "========================================\n\n";

echo "SERVERS:\n";
echo "  🌐 NY (66.94.103.91) - FULL ACCESS - Home devices, Xbox, cameras\n";
echo "  🔒 St. Louis (144.126.133.253) - DEDICATED VIP ONLY\n";
echo "  🌐 Dallas (66.241.124.4) - LIMITED - Netflix OK, no torrents\n";
echo "  🌐 Canada (66.241.125.247) - LIMITED - Netflix OK, no torrents\n\n";

echo "PLANS:\n";
echo "  📦 Basic: \$9.99/mo - 3 devices + 1 camera\n";
echo "  📦 Family: \$14.99/mo - 5 devices + 2 cameras\n";
echo "  📦 Dedicated: \$29.99/mo - Unlimited + 12 cameras\n";
echo "  📦 Corporate: Contact sales - 12+ cameras\n\n";

echo "VIP (SECRET):\n";
echo "  👑 paulhalonen@gmail.com - Owner\n";
echo "  👑 seige235@yahoo.com - Dedicated St. Louis\n";
echo "  ⭐ joyceloveorphanage@gmail.com\n";
echo "  ⭐ darylsedore@icloud.com\n";
echo "  ⭐ starbeing23@hotmail.com\n";

echo "</pre>";
