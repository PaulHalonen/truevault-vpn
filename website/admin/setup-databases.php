<?php
/**
 * TrueVault VPN - Database Setup Script
 * 
 * PURPOSE: Creates all SQLite databases using SQLite3 class (NOT PDO)
 * RUN ONCE: Visit https://vpn.the-truth-publishing.com/admin/setup-databases.php
 * 
 * PRICING:
 * Personal:   $9.97/mo  | $99.97/yr   | 3 devices
 * Family:    $14.97/mo  | $140.97/yr  | 6 devices
 * Dedicated: $39.97/mo  | $399.97/yr  | unlimited devices
 * 
 * @created January 2026
 * @version 1.0.2 - Corrected pricing
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DB_PATH', dirname(__DIR__) . '/databases/');

$results = [];
$errors = [];

/**
 * Create database using SQLite3 class
 */
function createDB($filename, $schema, $seeds = []) {
    global $results, $errors;
    
    $dbFile = DB_PATH . $filename;
    
    try {
        $db = new SQLite3($dbFile);
        $db->enableExceptions(true);
        $db->exec('PRAGMA foreign_keys = ON');
        
        // Execute schema
        $db->exec($schema);
        
        // Execute seeds
        foreach ($seeds as $sql) {
            if (!empty(trim($sql))) {
                $db->exec($sql);
            }
        }
        
        $db->close();
        
        $results[$filename] = [
            'status' => 'success',
            'size' => filesize($dbFile)
        ];
        return true;
        
    } catch (Exception $e) {
        $errors[$filename] = $e->getMessage();
        $results[$filename] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        return false;
    }
}

// ============================================
// DATABASE 1: main.db
// ============================================
$mainSchema = "
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type TEXT DEFAULT 'string',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS theme (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    element_name TEXT NOT NULL UNIQUE,
    element_value TEXT NOT NULL,
    element_category TEXT,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    added_by TEXT,
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    dedicated_server_id INTEGER,
    max_devices INTEGER DEFAULT 999,
    is_active INTEGER DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_theme_name ON theme(element_name);
CREATE INDEX IF NOT EXISTS idx_vip_email ON vip_users(email);
";

$mainSeeds = [
    // Site settings
    "INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES 
    ('site_name', 'TrueVault VPN', 'string', 'Website name'),
    ('site_tagline', 'Your Complete Digital Fortress', 'string', 'Tagline'),
    ('admin_email', 'paulhalonen@gmail.com', 'string', 'Admin email'),
    ('support_email', 'paulhalonen@gmail.com', 'string', 'Support email'),
    ('trial_days', '30', 'number', 'Free trial days')",
    
    // Plan settings - CORRECTED PRICING
    "INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES 
    ('max_devices_personal', '3', 'number', 'Max devices for Personal plan'),
    ('max_devices_family', '6', 'number', 'Max devices for Family plan'),
    ('max_devices_dedicated', '999', 'number', 'Max devices for Dedicated plan'),
    ('price_personal_monthly', '9.97', 'number', 'Personal monthly USD'),
    ('price_personal_annual', '99.97', 'number', 'Personal annual USD'),
    ('price_family_monthly', '14.97', 'number', 'Family monthly USD'),
    ('price_family_annual', '140.97', 'number', 'Family annual USD'),
    ('price_dedicated_monthly', '39.97', 'number', 'Dedicated monthly USD'),
    ('price_dedicated_annual', '399.97', 'number', 'Dedicated annual USD')",
    
    // Theme colors
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('primary_color', '#00d9ff', 'colors', 'Primary brand color'),
    ('secondary_color', '#00ff88', 'colors', 'Secondary brand color'),
    ('accent_color', '#ff6b6b', 'colors', 'Accent color'),
    ('background_color', '#0f0f1a', 'colors', 'Dark background'),
    ('background_secondary', '#1a1a2e', 'colors', 'Secondary background'),
    ('text_color', '#ffffff', 'colors', 'Main text color'),
    ('text_muted', '#888888', 'colors', 'Muted text color'),
    ('success_color', '#00ff88', 'colors', 'Success messages'),
    ('error_color', '#ff6464', 'colors', 'Error messages')",
    
    // Theme typography
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('font_family', '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif', 'typography', 'Main font'),
    ('heading_font', 'Poppins, sans-serif', 'typography', 'Heading font'),
    ('base_font_size', '16px', 'typography', 'Base font size'),
    ('button_radius', '8px', 'buttons', 'Button border radius'),
    ('button_padding', '10px 20px', 'buttons', 'Button padding'),
    ('container_max_width', '1100px', 'spacing', 'Max container width'),
    ('card_radius', '14px', 'spacing', 'Card border radius')",
    
    // VIP users
    "INSERT OR IGNORE INTO vip_users (email, added_by, notes, dedicated_server_id, max_devices, is_active) VALUES 
    ('paulhalonen@gmail.com', 'system', 'Owner - Full access to all servers', NULL, 10, 1)",
    
    "INSERT OR IGNORE INTO vip_users (email, added_by, notes, dedicated_server_id, max_devices, is_active) VALUES 
    ('seige235@yahoo.com', 'paulhalonen@gmail.com', 'Dedicated St. Louis server access only', 2, 999, 1)"
];

// ============================================
// DATABASE 2: users.db
// ============================================
$usersSchema = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    account_type TEXT DEFAULT 'standard',
    plan TEXT DEFAULT 'personal',
    status TEXT DEFAULT 'pending',
    max_devices INTEGER DEFAULT 3,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    email_verified INTEGER DEFAULT 0,
    verification_token TEXT,
    reset_token TEXT,
    reset_token_expires DATETIME,
    trial_ends_at DATETIME,
    subscription_id TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    ip_address TEXT,
    user_agent TEXT,
    device_info TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_valid INTEGER DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
";

$usersSeeds = [];

// ============================================
// DATABASE 3: devices.db
// ============================================
$devicesSchema = "
CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL UNIQUE,
    device_name TEXT NOT NULL,
    device_type TEXT,
    operating_system TEXT,
    public_key TEXT NOT NULL UNIQUE,
    private_key_encrypted TEXT,
    preshared_key TEXT,
    assigned_ip TEXT NOT NULL UNIQUE,
    current_server_id INTEGER DEFAULT 1,
    status TEXT DEFAULT 'active',
    is_online INTEGER DEFAULT 0,
    last_seen DATETIME,
    last_handshake DATETIME,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS device_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT NOT NULL,
    server_id INTEGER NOT NULL,
    config_content TEXT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    downloaded_at DATETIME,
    UNIQUE(device_id, server_id)
);

CREATE TABLE IF NOT EXISTS ip_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    device_id TEXT,
    assigned_at DATETIME,
    is_available INTEGER DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_devices_user ON devices(user_id);
CREATE INDEX IF NOT EXISTS idx_devices_pubkey ON devices(public_key);
CREATE INDEX IF NOT EXISTS idx_ip_available ON ip_pool(is_available);
";

// Generate IP pool (10.8.0.2 to 10.8.0.254)
$devicesSeeds = [];
for ($i = 2; $i <= 254; $i++) {
    $devicesSeeds[] = "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.{$i}', 1)";
}

// ============================================
// DATABASE 4: servers.db
// ============================================
$serversSchema = "
CREATE TABLE IF NOT EXISTS servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    display_name TEXT NOT NULL,
    location TEXT NOT NULL,
    country TEXT NOT NULL,
    country_code TEXT,
    ip_address TEXT NOT NULL,
    port INTEGER DEFAULT 51820,
    public_key TEXT NOT NULL,
    endpoint TEXT NOT NULL,
    dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
    allowed_ips TEXT DEFAULT '0.0.0.0/0',
    status TEXT DEFAULT 'active',
    is_vip_only INTEGER DEFAULT 0,
    dedicated_user_email TEXT,
    max_clients INTEGER DEFAULT 100,
    current_clients INTEGER DEFAULT 0,
    bandwidth_limit INTEGER DEFAULT 0,
    provider TEXT,
    monthly_cost REAL,
    priority INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS server_health (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    cpu_usage REAL,
    memory_usage REAL,
    disk_usage REAL,
    bandwidth_in INTEGER,
    bandwidth_out INTEGER,
    active_connections INTEGER,
    latency_ms INTEGER,
    is_healthy INTEGER DEFAULT 1,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_servers_status ON servers(status);
CREATE INDEX IF NOT EXISTS idx_servers_vip ON servers(is_vip_only);
CREATE INDEX IF NOT EXISTS idx_health_server ON server_health(server_id);
";

$serversSeeds = [
    // Server 1: New York (Contabo - Shared)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, max_clients, bandwidth_limit, provider, monthly_cost, priority) VALUES 
    (1, 'us_east', 'US East', 'New York, USA', 'United States', 'US', '66.94.103.91', 51820, 'SERVER_PUBKEY_NY', '66.94.103.91:51820', 'active', 0, 100, 1000, 'Contabo', 6.75, 1)",
    
    // Server 2: St. Louis VIP (Contabo - Dedicated to seige235@yahoo.com)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, dedicated_user_email, max_clients, bandwidth_limit, provider, monthly_cost, priority) VALUES 
    (2, 'us_central_vip', 'US Central VIP', 'St. Louis, USA', 'United States', 'US', '144.126.133.253', 51820, 'SERVER_PUBKEY_STL', '144.126.133.253:51820', 'active', 1, 'seige235@yahoo.com', 10, 0, 'Contabo', 6.15, 10)",
    
    // Server 3: Dallas (Fly.io - Shared)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, max_clients, bandwidth_limit, provider, monthly_cost, priority) VALUES 
    (3, 'us_south', 'US South', 'Dallas, USA', 'United States', 'US', '66.241.124.4', 51820, 'SERVER_PUBKEY_DAL', '66.241.124.4:51820', 'active', 0, 100, 1000, 'Fly.io', 5.00, 2)",
    
    // Server 4: Toronto (Fly.io - Shared)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, max_clients, bandwidth_limit, provider, monthly_cost, priority) VALUES 
    (4, 'canada', 'Canada', 'Toronto, Canada', 'Canada', 'CA', '66.241.125.247', 51820, 'SERVER_PUBKEY_TOR', '66.241.125.247:51820', 'active', 0, 100, 1000, 'Fly.io', 5.00, 3)"
];

// ============================================
// DATABASE 5: billing.db - UPDATED WITH ORDERS TABLE
// ============================================
$billingSchema = "
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    paypal_order_id TEXT UNIQUE,
    plan TEXT NOT NULL,
    billing_interval TEXT NOT NULL,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'pending',
    transaction_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    paypal_subscription_id TEXT UNIQUE,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    billing_interval TEXT DEFAULT 'monthly',
    current_period_start DATETIME,
    current_period_end DATETIME,
    cancelled_at DATETIME,
    cancel_reason TEXT,
    payment_failures INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    invoice_number TEXT NOT NULL UNIQUE,
    amount REAL NOT NULL,
    tax REAL DEFAULT 0,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'pending',
    due_date DATE,
    paid_at DATETIME,
    paypal_transaction_id TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_id INTEGER,
    subscription_id INTEGER,
    paypal_transaction_id TEXT UNIQUE,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'completed',
    payment_method TEXT DEFAULT 'paypal',
    payer_email TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_paypal ON orders(paypal_order_id);
CREATE INDEX IF NOT EXISTS idx_subs_user ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subs_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
";

$billingSeeds = [];

// ============================================
// DATABASE 6: logs.db
// ============================================
$logsSchema = "
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id INTEGER,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS error_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT DEFAULT 'error',
    message TEXT NOT NULL,
    file TEXT,
    line INTEGER,
    trace TEXT,
    context TEXT,
    user_id INTEGER,
    ip_address TEXT,
    url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS api_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    endpoint TEXT NOT NULL,
    method TEXT NOT NULL,
    request_body TEXT,
    response_code INTEGER,
    response_body TEXT,
    user_id INTEGER,
    ip_address TEXT,
    duration_ms INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS webhook_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source TEXT NOT NULL,
    event_type TEXT,
    payload TEXT,
    processed INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);

CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_error_created ON error_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_api_endpoint ON api_logs(endpoint);
CREATE INDEX IF NOT EXISTS idx_webhook_source ON webhook_logs(source);
";

$logsSeeds = [];

// ============================================
// DATABASE 7: support.db
// ============================================
$supportSchema = "
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ticket_number TEXT NOT NULL UNIQUE,
    subject TEXT NOT NULL,
    category TEXT DEFAULT 'general',
    priority TEXT DEFAULT 'normal',
    status TEXT DEFAULT 'open',
    assigned_to TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME
);

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,
    is_staff INTEGER DEFAULT 0,
    message TEXT NOT NULL,
    attachments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS knowledge_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    content TEXT NOT NULL,
    category TEXT,
    tags TEXT,
    views INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tickets_user ON tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);
CREATE INDEX IF NOT EXISTS idx_messages_ticket ON ticket_messages(ticket_id);
CREATE INDEX IF NOT EXISTS idx_kb_slug ON knowledge_base(slug);
";

$supportSeeds = [];

// ============================================
// RUN DATABASE CREATION
// ============================================

if (!file_exists(DB_PATH)) {
    mkdir(DB_PATH, 0755, true);
}

createDB('main.db', $mainSchema, $mainSeeds);
createDB('users.db', $usersSchema, $usersSeeds);
createDB('devices.db', $devicesSchema, $devicesSeeds);
createDB('servers.db', $serversSchema, $serversSeeds);
createDB('billing.db', $billingSchema, $billingSeeds);
createDB('logs.db', $logsSchema, $logsSeeds);
createDB('support.db', $supportSchema, $supportSeeds);

// ============================================
// OUTPUT HTML
// ============================================
$successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
$errorCount = count($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueVault VPN - Database Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        .subtitle { text-align: center; color: #888; margin-bottom: 30px; }
        .card {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .card h2 { font-size: 1.1rem; margin-bottom: 15px; }
        .db-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .db-name { font-family: monospace; color: #00d9ff; }
        .status { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status.success { background: rgba(0,255,136,0.15); color: #00ff88; }
        .status.error { background: rgba(255,100,100,0.15); color: #ff6464; }
        .size { color: #888; font-size: 0.85rem; }
        .summary { display: flex; justify-content: center; gap: 30px; margin-top: 20px; }
        .stat { text-align: center; }
        .stat-num {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { font-size: 0.8rem; color: #888; }
        .warning {
            background: rgba(255,170,0,0.1);
            border: 1px solid rgba(255,170,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #ffaa00;
        }
        .success-box {
            background: rgba(0,255,136,0.1);
            border: 1px solid rgba(0,255,136,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #00ff88;
        }
        .error-box {
            background: rgba(255,100,100,0.1);
            border: 1px solid rgba(255,100,100,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #ff6464;
        }
        .pricing { margin-top: 15px; }
        .pricing table { width: 100%; border-collapse: collapse; }
        .pricing th, .pricing td { padding: 8px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .pricing th { color: #888; font-size: 0.85rem; }
        .pricing td { color: #00ff88; font-family: monospace; }
        a { color: #00d9ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 TrueVault VPN</h1>
        <p class="subtitle">Database Setup v1.0.2</p>
        
        <div class="card">
            <h2>📊 Database Status</h2>
            <?php foreach ($results as $db => $info): ?>
            <div class="db-item">
                <span class="db-name"><?php echo $db; ?></span>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <?php if (isset($info['size'])): ?>
                    <span class="size"><?php echo number_format($info['size'] / 1024, 1); ?> KB</span>
                    <?php endif; ?>
                    <span class="status <?php echo $info['status']; ?>">
                        <?php echo $info['status'] === 'success' ? '✓ Created' : '✗ Error'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card pricing">
            <h2>💰 Pricing Configuration</h2>
            <table>
                <tr><th>Plan</th><th>Monthly</th><th>Annual</th><th>Devices</th></tr>
                <tr><td>Personal</td><td>$9.97</td><td>$99.97</td><td>3</td></tr>
                <tr><td>Family</td><td>$14.97</td><td>$140.97</td><td>6</td></tr>
                <tr><td>Dedicated</td><td>$39.97</td><td>$399.97</td><td>Unlimited</td></tr>
            </table>
        </div>
        
        <div class="summary">
            <div class="stat">
                <div class="stat-num"><?php echo $successCount; ?></div>
                <div class="stat-label">Databases Created</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?php echo $errorCount; ?></div>
                <div class="stat-label">Errors</div>
            </div>
            <div class="stat">
                <div class="stat-num">4</div>
                <div class="stat-label">Servers</div>
            </div>
            <div class="stat">
                <div class="stat-num">2</div>
                <div class="stat-label">VIP Users</div>
            </div>
        </div>
        
        <?php if ($errorCount > 0): ?>
        <div class="error-box">
            <strong>❌ Errors Occurred:</strong><br>
            <?php foreach ($errors as $db => $error): ?>
            <p><?php echo $db; ?>: <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="success-box">
            <strong>✅ All databases created successfully!</strong><br>
            <p style="margin-top:10px;">Seeded data:</p>
            <ul style="margin-left:20px; margin-top:5px;">
                <li>4 VPN Servers (NY, St. Louis VIP, Dallas, Toronto)</li>
                <li>2 VIP Users (paulhalonen@gmail.com, seige235@yahoo.com)</li>
                <li>253 IP addresses in pool (10.8.0.2 - 10.8.0.254)</li>
                <li>Theme colors and typography settings</li>
                <li>Pricing: Personal $9.97/mo, Family $14.97/mo, Dedicated $39.97/mo</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="warning">
            <strong>🔒 Security Notice:</strong><br>
            Delete or rename this file after setup! Rename to: <code>setup-databases.php.bak</code>
        </div>
    </div>
</body>
</html>
