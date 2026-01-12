<?php
/**
 * TrueVault VPN - MASTER DATABASE SETUP
 * Run this once to create all database tables
 * 
 * URL: https://vpn.the-truth-publishing.com/api/config/setup-all.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>TrueVault Database Setup</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#0f0;padding:20px}";
echo ".ok{color:#0f0}.err{color:#f00}.warn{color:#ff0}h1{color:#0ff}pre{background:#000;padding:15px;border-radius:5px}</style></head><body>";
echo "<h1>🔐 TrueVault VPN - Master Database Setup</h1><pre>\n";

$dataDir = __DIR__ . '/../../data';

// Create data directory
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "<span class='ok'>✓</span> Created data directory\n";
}

// ============================================
// USERS DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>USERS DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/users.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        first_name TEXT,
        last_name TEXT,
        plan_type TEXT DEFAULT 'free',
        status TEXT DEFAULT 'pending',
        email_verified INTEGER DEFAULT 0,
        verification_token TEXT,
        reset_token TEXT,
        reset_expires DATETIME,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created users table\n";
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)");
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Users DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// VPN DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>VPN DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/vpn.db');
    
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
    echo "<span class='ok'>✓</span> Created vpn_servers table\n";
    
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
    echo "<span class='ok'>✓</span> Created user_peers table\n";
    
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
    echo "<span class='ok'>✓</span> Created vpn_connections table\n";
    
    // Insert servers
    $servers = [
        ['New York', 'US-East', '66.94.103.91', 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', '10.0.0', 0, NULL, 'unlimited', 'gaming,streaming,torrents,cameras', 'RECOMMENDED - Unlimited bandwidth for all uses'],
        ['St. Louis VIP', 'US-Central', '144.126.133.253', 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', '10.0.1', 1, 'seige235@yahoo.com', 'unlimited', 'everything', 'EXCLUSIVE VIP SERVER'],
        ['Dallas', 'US-South', '66.241.124.4', 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', '10.10.1', 0, NULL, 'limited', 'streaming', 'STREAMING ONLY - No gaming/torrents'],
        ['Toronto', 'Canada', '66.241.125.247', 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', '10.10.0', 0, NULL, 'limited', 'streaming', 'CANADIAN STREAMING - No gaming/torrents']
    ];
    
    foreach ($servers as $s) {
        $existing = $db->querySingle("SELECT id FROM vpn_servers WHERE ip_address = '{$s[2]}'");
        if (!$existing) {
            $vipEmail = $s[6] ? "'{$s[6]}'" : 'NULL';
            $db->exec("INSERT INTO vpn_servers (name, location, ip_address, public_key, network_prefix, is_vip, vip_user_email, bandwidth_limit, allowed_uses, instructions) 
                       VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', '{$s[3]}', '{$s[4]}', {$s[5]}, {$vipEmail}, '{$s[7]}', '{$s[8]}', '{$s[9]}')");
            echo "<span class='ok'>✓</span> Inserted server: {$s[0]}\n";
        } else {
            echo "<span class='warn'>-</span> Server exists: {$s[0]}\n";
        }
    }
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> VPN DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// BILLING DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>BILLING DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/billing.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_type TEXT NOT NULL,
        status TEXT DEFAULT 'active',
        payment_id TEXT,
        max_devices INTEGER DEFAULT 3,
        max_cameras INTEGER DEFAULT 1,
        start_date DATETIME,
        end_date DATETIME,
        cancelled_at DATETIME,
        cancel_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created subscriptions table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_number TEXT UNIQUE NOT NULL,
        plan_id TEXT,
        amount REAL NOT NULL,
        payment_id TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created invoices table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS pending_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_id TEXT UNIQUE NOT NULL,
        plan_id TEXT NOT NULL,
        amount REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created pending_orders table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS webhook_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        webhook_id TEXT,
        event_type TEXT,
        payload TEXT,
        processed INTEGER DEFAULT 0,
        error TEXT,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created webhook_log table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_revocations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        revoke_at DATETIME NOT NULL,
        status TEXT DEFAULT 'pending',
        completed_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created scheduled_revocations table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Billing DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// DEVICES DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>DEVICES DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/devices.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS user_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id TEXT UNIQUE NOT NULL,
        device_name TEXT NOT NULL,
        device_type TEXT,
        is_primary INTEGER DEFAULT 0,
        status TEXT DEFAULT 'active',
        registered_at DATETIME,
        last_active DATETIME,
        removed_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created user_devices table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS device_swaps (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        old_device_id TEXT,
        new_device_id TEXT,
        swapped_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created device_swaps table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Devices DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// CAMERAS DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>CAMERAS DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/cameras.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS user_cameras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        camera_id TEXT UNIQUE NOT NULL,
        camera_name TEXT NOT NULL,
        local_ip TEXT,
        camera_type TEXT,
        vendor TEXT,
        server_id INTEGER,
        external_port INTEGER,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        removed_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created user_cameras table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Cameras DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// PORT FORWARDING DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>PORT FORWARDING DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/port_forwarding.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS port_forwards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        camera_id TEXT,
        server_id INTEGER NOT NULL,
        internal_ip TEXT NOT NULL,
        internal_port INTEGER NOT NULL,
        external_port INTEGER NOT NULL,
        protocol TEXT DEFAULT 'tcp',
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created port_forwards table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Port Forwarding DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// CERTIFICATES DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>CERTIFICATES DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/certificates.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS user_certificates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT,
        type TEXT DEFAULT 'wireguard',
        public_key TEXT,
        private_key TEXT,
        status TEXT DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        revoked_at DATETIME
    )");
    echo "<span class='ok'>✓</span> Created user_certificates table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Certificates DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// VIP DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>VIP DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/vip.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS vip_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        tier TEXT NOT NULL,
        max_devices INTEGER DEFAULT 8,
        max_cameras INTEGER DEFAULT 2,
        dedicated_server_id INTEGER,
        notes TEXT,
        added_by TEXT,
        activated_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created vip_users table\n";
    
    // Insert VIP users
    $vips = [
        ['paulhalonen@gmail.com', 'owner', 999, 999, NULL, 'Owner - Unlimited everything'],
        ['seige235@yahoo.com', 'vip_dedicated', 999, 12, 2, 'VIP Dedicated - St. Louis server'],
        ['joyceloveorphanage@gmail.com', 'vip_basic', 8, 2, NULL, 'VIP Basic'],
        ['darylsedore@icloud.com', 'vip_basic', 8, 2, NULL, 'VIP Basic'],
        ['starbeing23@hotmail.com', 'vip_basic', 8, 2, NULL, 'VIP Basic']
    ];
    
    foreach ($vips as $v) {
        $existing = $db->querySingle("SELECT id FROM vip_users WHERE email = '{$v[0]}'");
        if (!$existing) {
            $serverId = $v[4] ? $v[4] : 'NULL';
            $db->exec("INSERT INTO vip_users (email, tier, max_devices, max_cameras, dedicated_server_id, notes, added_by) 
                       VALUES ('{$v[0]}', '{$v[1]}', {$v[2]}, {$v[3]}, {$serverId}, '{$v[5]}', 'system')");
            echo "<span class='ok'>✓</span> Added VIP: {$v[0]}\n";
        } else {
            echo "<span class='warn'>-</span> VIP exists: {$v[0]}\n";
        }
    }
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> VIP DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// LOGS DATABASE
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='warn'>LOGS DATABASE</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n";

try {
    $db = new SQLite3($dataDir . '/logs.db');
    
    $db->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT,
        details TEXT,
        ip_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created activity_log table\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS cron_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task TEXT,
        results TEXT,
        duration REAL,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='ok'>✓</span> Created cron_log table\n";
    
    $db->close();
} catch (Exception $e) {
    echo "<span class='err'>✗</span> Logs DB Error: " . $e->getMessage() . "\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n<span class='warn'>═══════════════════════════════════════</span>\n";
echo "<span class='ok'>✓ ALL DATABASES SETUP COMPLETE!</span>\n";
echo "<span class='warn'>═══════════════════════════════════════</span>\n\n";

echo "Database files created in: {$dataDir}\n\n";

// List files
$files = glob($dataDir . '/*.db');
foreach ($files as $file) {
    $size = filesize($file);
    $name = basename($file);
    echo "  📁 {$name} ({$size} bytes)\n";
}

echo "\n</pre>";
echo "<p style='color:#0ff'>Setup complete! <a href='../auth/login.php' style='color:#0f0'>Go to Login</a></p>";
echo "</body></html>";
