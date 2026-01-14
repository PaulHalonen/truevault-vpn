<?php
/**
 * TrueVault VPN - Complete Database Setup
 * Creates all required database tables
 */

header('Content-Type: application/json');

$basePath = __DIR__ . '/../../databases';

// Create databases directory if it doesn't exist
if (!is_dir($basePath)) {
    mkdir($basePath, 0755, true);
}

$databases = [
    'users' => [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            plan TEXT DEFAULT 'basic',
            status TEXT DEFAULT 'active',
            is_vip INTEGER DEFAULT 0,
            auto_connect INTEGER DEFAULT 0,
            kill_switch INTEGER DEFAULT 1,
            email_notifications INTEGER DEFAULT 1,
            two_factor INTEGER DEFAULT 0,
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)"
    ],
    
    'billing' => [
        "CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            paypal_subscription_id TEXT,
            plan TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            amount REAL,
            currency TEXT DEFAULT 'USD',
            billing_cycle TEXT DEFAULT 'monthly',
            next_billing_date TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            paypal_transaction_id TEXT,
            type TEXT,
            amount REAL,
            currency TEXT DEFAULT 'USD',
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(user_id)"
    ],
    
    'devices' => [
        "CREATE TABLE IF NOT EXISTS devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT DEFAULT 'laptop',
            status TEXT DEFAULT 'active',
            last_connected TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_devices_user ON devices(user_id)"
    ],
    
    'cameras' => [
        "CREATE TABLE IF NOT EXISTS cameras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            ip_address TEXT,
            port INTEGER DEFAULT 80,
            brand TEXT,
            status TEXT DEFAULT 'offline',
            last_online TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_cameras_user ON cameras(user_id)"
    ],
    
    'certificates' => [
        "CREATE TABLE IF NOT EXISTS certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            name TEXT NOT NULL,
            fingerprint TEXT,
            status TEXT DEFAULT 'active',
            expires_at TEXT,
            revoked_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_certificates_user ON certificates(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_certificates_type ON certificates(type)"
    ],
    
    'vpn' => [
        "CREATE TABLE IF NOT EXISTS connections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            peer_ip TEXT,
            public_key TEXT,
            status TEXT DEFAULT 'disconnected',
            connected_at TEXT,
            disconnected_at TEXT,
            bytes_sent INTEGER DEFAULT 0,
            bytes_received INTEGER DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT,
            ip_address TEXT NOT NULL,
            port INTEGER DEFAULT 51820,
            public_key TEXT,
            network_range TEXT,
            status TEXT DEFAULT 'online',
            is_vip_only INTEGER DEFAULT 0,
            max_users INTEGER DEFAULT 100,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_connections_user ON connections(user_id)"
    ],
    
    'scanner' => [
        "CREATE TABLE IF NOT EXISTS scanner_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL,
            expires_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS scanned_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            ip_address TEXT,
            mac_address TEXT,
            hostname TEXT,
            vendor TEXT,
            type TEXT,
            discovered_at TEXT,
            last_seen TEXT
        )",
        "CREATE INDEX IF NOT EXISTS idx_scanner_tokens_user ON scanner_tokens(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_scanned_devices_user ON scanned_devices(user_id)"
    ],
    
    'logs' => [
        "CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT,
            message TEXT,
            user_id INTEGER,
            admin_id INTEGER,
            ip_address TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS error_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT,
            message TEXT,
            context TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX IF NOT EXISTS idx_activity_log_type ON activity_log(type)",
        "CREATE INDEX IF NOT EXISTS idx_activity_log_user ON activity_log(user_id)"
    ],
    
    'admin' => [
        "CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            name TEXT,
            role TEXT DEFAULT 'admin',
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ]
];

$results = [];

foreach ($databases as $dbName => $tables) {
    try {
        $dbPath = $basePath . '/' . $dbName . '.sqlite';
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        foreach ($tables as $sql) {
            $db->exec($sql);
        }
        
        $results[$dbName] = 'OK';
        
    } catch (Exception $e) {
        $results[$dbName] = 'ERROR: ' . $e->getMessage();
    }
}

// Insert default data

// Default servers
try {
    $vpnDb = new PDO('sqlite:' . $basePath . '/vpn.sqlite');
    $vpnDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $servers = [
        ['US-East (NY)', 'New York, NY', '66.94.103.91', '10.0.0.0/24', 0],
        ['US-Central VIP (STL)', 'St. Louis, MO', '144.126.133.253', '10.0.1.0/24', 1],
        ['US-South (TX)', 'Dallas, TX', '66.241.124.4', '10.10.1.0/24', 0],
        ['Canada (Toronto)', 'Toronto, ON', '66.241.125.247', '10.10.0.0/24', 0]
    ];
    
    foreach ($servers as $server) {
        $stmt = $vpnDb->prepare("INSERT OR IGNORE INTO servers (name, location, ip_address, network_range, is_vip_only) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($server);
    }
    
    $results['default_servers'] = 'OK';
} catch (Exception $e) {
    $results['default_servers'] = 'ERROR: ' . $e->getMessage();
}

// Default admin
try {
    $adminDb = new PDO('sqlite:' . $basePath . '/admin.sqlite');
    $adminDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $adminPassword = password_hash('TrueVault2026!', PASSWORD_DEFAULT);
    $stmt = $adminDb->prepare("INSERT OR IGNORE INTO admins (email, password, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['kahlen@truthvault.com', $adminPassword, 'Kah-Len', 'superadmin']);
    
    $results['default_admin'] = 'OK';
} catch (Exception $e) {
    $results['default_admin'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode([
    'success' => true,
    'message' => 'Database setup complete',
    'results' => $results
], JSON_PRETTY_PRINT);
