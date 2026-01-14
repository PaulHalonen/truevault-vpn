<?php
/**
 * TrueVault VPN - Master Database Setup
 * Creates ALL database tables and seeds initial data
 * 
 * Run once: /api/config/setup-databases.php
 * Or with key: /api/config/setup-databases.php?key=TrueVault2026Setup
 */

require_once __DIR__ . '/database.php';

// Optional security - can be run directly for first setup
$setupKey = 'TrueVault2026Setup';
if (isset($_GET['key']) && $_GET['key'] !== $setupKey) {
    http_response_code(403);
    die('Invalid setup key');
}

// Helper to generate UUID
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #00ff88; padding: 20px; margin: 0; min-height: 100vh;'>";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║       TrueVault VPN - Master Database Setup              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "<span style='color: #888'>Started: " . date('Y-m-d H:i:s') . "</span>\n\n";

// ============ USERS DATABASE ============
echo "<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  USERS DATABASE                     │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('users');

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active',
    is_vip INTEGER DEFAULT 0,
    email_verified INTEGER DEFAULT 0,
    two_factor_enabled INTEGER DEFAULT 0,
    two_factor_secret TEXT,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ users table created\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_users_uuid ON users(uuid)");

// Create VIP users table (database-driven VIP list)
$db->exec("CREATE TABLE IF NOT EXISTS vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL DEFAULT 'vip_basic',
    plan TEXT NOT NULL DEFAULT 'family',
    dedicated_server_id INTEGER,
    description TEXT,
    added_by TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ vip_users table created\n";
$db->exec("CREATE INDEX IF NOT EXISTS idx_vip_email ON vip_users(email)");

// ============ SEED VIP USERS ============
echo "\n<span style='color: #ffd700'>--- Seeding VIP Users ---</span>\n";

$initialVIPs = [
    [
        'email' => 'paulhalonen@gmail.com',
        'type' => 'owner',
        'plan' => 'dedicated',
        'dedicated_server_id' => null,
        'description' => 'System Owner'
    ],
    [
        'email' => 'seige235@yahoo.com',
        'type' => 'vip_dedicated',
        'plan' => 'dedicated',
        'dedicated_server_id' => 2,
        'description' => 'VIP Dedicated - St. Louis Server'
    ]
];

foreach ($initialVIPs as $vip) {
    // Check if VIP exists
    $existing = Database::queryOne('users', 
        "SELECT id FROM vip_users WHERE LOWER(email) = ?", 
        [strtolower($vip['email'])]
    );
    
    if (!$existing) {
        Database::execute('users',
            "INSERT INTO vip_users (email, type, plan, dedicated_server_id, description, added_by, created_at)
             VALUES (?, ?, ?, ?, ?, 'system_setup', datetime('now'))",
            [$vip['email'], $vip['type'], $vip['plan'], $vip['dedicated_server_id'], $vip['description']]
        );
        echo "✓ Added VIP: {$vip['email']} ({$vip['type']})\n";
    } else {
        echo "✓ VIP exists: {$vip['email']}\n";
    }
}

// ============ BILLING DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  BILLING DATABASE                   │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('billing');

$db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_type TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    payment_id TEXT,
    max_devices INTEGER DEFAULT 3,
    start_date DATETIME,
    end_date DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ subscriptions table\n";

$db->exec("CREATE TABLE IF NOT EXISTS pending_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id TEXT UNIQUE NOT NULL,
    plan_id TEXT NOT NULL,
    amount REAL NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ pending_orders table\n";

$db->exec("CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_number TEXT UNIQUE NOT NULL,
    plan_id TEXT,
    amount REAL NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ invoices table\n";

$db->exec("CREATE TABLE IF NOT EXISTS payment_failures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    failure_date DATETIME NOT NULL,
    grace_end_date DATETIME NOT NULL,
    retry_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ payment_failures table\n";

$db->exec("CREATE TABLE IF NOT EXISTS scheduled_revocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    revoke_at DATETIME NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ scheduled_revocations table\n";

$db->exec("CREATE TABLE IF NOT EXISTS webhook_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_type TEXT,
    event_id TEXT,
    payload TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ webhook_log table\n";

// ============ VPN DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  VPN DATABASE                       │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('vpn');

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
    UNIQUE(user_id, server_id)
)");
echo "✓ user_peers table\n";

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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ vpn_servers table\n";

// Insert/Update servers
$servers = [
    [1, 'US-East', 'New York', '66.94.103.91', 51820, 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', '10.0.0', 0, null],
    [2, 'US-Central VIP', 'St. Louis', '144.126.133.253', 51820, 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', '10.0.1', 1, 'seige235@yahoo.com'],
    [3, 'US-South', 'Dallas', '66.241.124.4', 51820, 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', '10.10.1', 0, null],
    [4, 'Canada', 'Toronto', '66.241.125.247', 51820, 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', '10.10.0', 0, null]
];

$stmt = $db->prepare("INSERT OR REPLACE INTO vpn_servers (id, name, location, ip_address, port, public_key, network, is_vip, vip_user_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($servers as $s) { 
    $stmt->execute($s); 
}
echo "✓ Inserted/Updated 4 VPN servers\n";

// ============ DEVICES DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  DEVICES DATABASE                   │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('devices');

$db->exec("CREATE TABLE IF NOT EXISTS user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT DEFAULT 'other',
    platform TEXT,
    identifier TEXT,
    public_key TEXT,
    last_seen DATETIME,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ user_devices table\n";

// ============ CAMERAS DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  CAMERAS DATABASE                   │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('cameras');

$db->exec("CREATE TABLE IF NOT EXISTS user_cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    local_ip TEXT,
    port INTEGER DEFAULT 554,
    brand TEXT,
    model TEXT,
    rtsp_path TEXT,
    username TEXT,
    password TEXT,
    forwarding_enabled INTEGER DEFAULT 0,
    forwarding_port INTEGER,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ user_cameras table\n";

// ============ CERTIFICATES DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  CERTIFICATES DATABASE              │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('certificates');

$db->exec("CREATE TABLE IF NOT EXISTS user_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT,
    type TEXT NOT NULL,
    public_key TEXT,
    private_key TEXT,
    fingerprint TEXT,
    status TEXT DEFAULT 'active',
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ user_certificates table\n";

// ============ LOGS DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  LOGS DATABASE                      │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('logs');

$db->exec("CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ activity_log table\n";

$db->exec("CREATE TABLE IF NOT EXISTS cron_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_name TEXT NOT NULL,
    results TEXT,
    duration_ms REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ cron_log table\n";

// ============ ADMIN DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  ADMIN DATABASE                     │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";
$db = Database::getConnection('admin');

$db->exec("CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name TEXT,
    role TEXT DEFAULT 'admin',
    status TEXT DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ admin_users table\n";

// Insert default admin
$adminEmail = 'paulhalonen@gmail.com';
$adminPassword = 'Asasasas4!';
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

$existingAdmin = Database::queryOne('admin', "SELECT id FROM admin_users WHERE email = ?", [$adminEmail]);
if (!$existingAdmin) {
    $db->exec("INSERT INTO admin_users (email, password_hash, name, role, status) 
               VALUES ('$adminEmail', '$adminHash', 'Paul Halonen', 'super_admin', 'active')");
    echo "✓ Default admin created: $adminEmail\n";
} else {
    // Update password hash
    Database::execute('admin', "UPDATE admin_users SET password_hash = ? WHERE email = ?", [$adminHash, $adminEmail]);
    echo "✓ Admin password updated: $adminEmail\n";
}

// ============ SCANNER DATABASE ============
echo "\n<span style='color: #00d9ff'>┌─────────────────────────────────────┐</span>\n";
echo "<span style='color: #00d9ff'>│  SCANNER DATABASE                   │</span>\n";
echo "<span style='color: #00d9ff'>└─────────────────────────────────────┘</span>\n";

// Scanner tokens table in users database
$db = Database::getConnection('users');
$db->exec("CREATE TABLE IF NOT EXISTS scanner_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ scanner_tokens table\n";

$db->exec("CREATE TABLE IF NOT EXISTS scanned_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    mac_address TEXT NOT NULL,
    ip_address TEXT,
    hostname TEXT,
    vendor TEXT,
    device_type TEXT,
    open_ports TEXT,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, mac_address)
)");
echo "✓ scanned_devices table\n";

// ============ SUMMARY ============
echo "\n<span style='color: #ffd700'>╔══════════════════════════════════════════════════════════╗</span>\n";
echo "<span style='color: #ffd700'>║                    SETUP COMPLETE!                       ║</span>\n";
echo "<span style='color: #ffd700'>╚══════════════════════════════════════════════════════════╝</span>\n\n";

echo "<span style='color: #00ff88'>✓ All database tables created</span>\n";
echo "<span style='color: #00ff88'>✓ VIP users seeded</span>\n";
echo "<span style='color: #00ff88'>✓ VPN servers configured</span>\n";
echo "<span style='color: #00ff88'>✓ Admin account ready</span>\n\n";

echo "<span style='color: #888'>Admin Login:</span>\n";
echo "  Email: $adminEmail\n";
echo "  Password: $adminPassword\n\n";

echo "<span style='color: #888'>VIP Users:</span>\n";
echo "  - paulhalonen@gmail.com (Owner)\n";
echo "  - seige235@yahoo.com (Dedicated VIP)\n\n";

echo "<span style='color: #888'>Finished: " . date('Y-m-d H:i:s') . "</span>\n";
echo "</pre>";
