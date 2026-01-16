<?php
/**
 * TrueVault VPN - Database Setup Script
 * 
 * PURPOSE: Creates all SQLite databases with proper schemas
 * RUN ONCE: Visit https://vpn.the-truth-publishing.com/admin/setup-databases.php
 * 
 * DATABASES CREATED:
 * - main.db      (settings, theme, vip_users)
 * - users.db     (users, sessions)
 * - devices.db   (devices, device_configs)
 * - servers.db   (servers, server_health)
 * - billing.db   (subscriptions, invoices, payments, payment_methods)
 * - logs.db      (activity_logs, error_logs)
 * - support.db   (tickets, messages)
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Security: Only run if accessed directly with setup key
$setupKey = $_GET['key'] ?? '';
$validKey = 'TrueVault2026Setup'; // Change this after setup!

// Uncomment below line to require key (recommended after first setup)
// if ($setupKey !== $validKey) { die('Invalid setup key'); }

// Define paths
define('DB_PATH', dirname(__DIR__) . '/databases/');

// Track results
$results = [];
$errors = [];

/**
 * Create database and execute SQL
 */
function createDatabase($filename, $sql, $seedData = []) {
    global $results, $errors;
    
    $dbFile = DB_PATH . $filename;
    
    try {
        // Create/open database
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        
        // Execute schema
        $pdo->exec($sql);
        
        // Seed data
        foreach ($seedData as $insert) {
            $pdo->exec($insert);
        }
        
        $results[$filename] = [
            'status' => 'success',
            'message' => 'Created successfully',
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
-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type TEXT DEFAULT 'string',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Theme table
CREATE TABLE IF NOT EXISTS theme (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    element_name TEXT NOT NULL UNIQUE,
    element_value TEXT NOT NULL,
    element_category TEXT,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- VIP Users table (SECRET - never expose!)
CREATE TABLE IF NOT EXISTS vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    added_by TEXT,
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    dedicated_server_id INTEGER,
    is_active BOOLEAN DEFAULT 1
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_theme_name ON theme(element_name);
CREATE INDEX IF NOT EXISTS idx_vip_email ON vip_users(email);
";

$mainSeed = [
    // Settings
    "INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES 
    ('site_name', 'TrueVault VPN', 'string', 'Website name'),
    ('site_tagline', 'Your Complete Digital Fortress', 'string', 'Tagline'),
    ('admin_email', 'paulhalonen@gmail.com', 'string', 'Admin email'),
    ('from_email', 'noreply@vpn.the-truth-publishing.com', 'string', 'From email'),
    ('support_email', 'paulhalonen@gmail.com', 'string', 'Support email'),
    ('paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'string', 'PayPal Client ID'),
    ('paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'string', 'PayPal Secret'),
    ('paypal_mode', 'live', 'string', 'PayPal mode'),
    ('paypal_webhook_id', '46924926WL757580D', 'string', 'PayPal Webhook ID'),
    ('max_devices_personal', '3', 'number', 'Max devices for Personal plan'),
    ('max_devices_family', '10', 'number', 'Max devices for Family plan'),
    ('max_devices_business', '50', 'number', 'Max devices for Business plan'),
    ('price_personal', '9.99', 'number', 'Personal plan price'),
    ('price_family', '14.99', 'number', 'Family plan price'),
    ('price_business', '29.99', 'number', 'Business plan price'),
    ('trial_days', '30', 'number', 'Free trial days'),
    ('jwt_secret', 'TrueVault2026JWTSecretKey!@#\$', 'string', 'JWT Secret Key'),
    ('peer_api_secret', 'TrueVault2026SecretKey', 'string', 'Server API Secret')",
    
    // Theme - Colors
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('primary_color', '#00d9ff', 'colors', 'Primary brand color (cyan)'),
    ('secondary_color', '#00ff88', 'colors', 'Secondary brand color (green)'),
    ('accent_color', '#ff6b6b', 'colors', 'Accent color (red for alerts)'),
    ('background_color', '#0f0f1a', 'colors', 'Dark background'),
    ('background_secondary', '#1a1a2e', 'colors', 'Secondary background'),
    ('card_background', 'rgba(255,255,255,0.04)', 'colors', 'Card background'),
    ('text_color', '#ffffff', 'colors', 'Main text color'),
    ('text_muted', '#888888', 'colors', 'Muted text color'),
    ('link_color', '#00d9ff', 'colors', 'Link color'),
    ('success_color', '#00ff88', 'colors', 'Success messages'),
    ('error_color', '#ff6464', 'colors', 'Error messages'),
    ('warning_color', '#ffaa00', 'colors', 'Warning messages')",
    
    // Theme - Typography
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('font_family', '-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif', 'typography', 'Main font'),
    ('heading_font', 'Poppins, sans-serif', 'typography', 'Heading font'),
    ('mono_font', '\"SF Mono\", Monaco, Consolas, monospace', 'typography', 'Monospace font'),
    ('base_font_size', '16px', 'typography', 'Base font size'),
    ('heading_weight', '700', 'typography', 'Heading font weight')",
    
    // Theme - Buttons
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('button_radius', '8px', 'buttons', 'Button border radius'),
    ('button_padding', '10px 20px', 'buttons', 'Button padding'),
    ('button_gradient', 'linear-gradient(90deg, #00d9ff, #00ff88)', 'buttons', 'Primary button gradient')",
    
    // Theme - Spacing
    "INSERT OR IGNORE INTO theme (element_name, element_value, element_category, description) VALUES 
    ('container_max_width', '1100px', 'spacing', 'Max container width'),
    ('section_padding', '60px 20px', 'spacing', 'Section padding'),
    ('card_padding', '18px', 'spacing', 'Card padding'),
    ('card_radius', '14px', 'spacing', 'Card border radius')",
    
    // VIP Users (SECRET!)
    "INSERT OR IGNORE INTO vip_users (email, added_by, notes, dedicated_server_id, is_active) VALUES 
    ('paulhalonen@gmail.com', 'system', 'Owner - Kah-Len - Full access to all servers', NULL, 1),
    ('seige235@yahoo.com', 'paulhalonen@gmail.com', 'Dedicated St. Louis server access only', 2, 1)"
];

// ============================================
// DATABASE 2: users.db
// ============================================
$usersSchema = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    account_type TEXT DEFAULT 'standard',
    plan TEXT DEFAULT 'personal',
    status TEXT DEFAULT 'active',
    max_devices INTEGER DEFAULT 3,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    email_verified BOOLEAN DEFAULT 0,
    verification_token TEXT,
    reset_token TEXT,
    reset_token_expires DATETIME,
    trial_ends_at DATETIME,
    subscription_id TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sessions table
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
    is_valid BOOLEAN DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_account_type ON users(account_type);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
";

$usersSeed = [
    // Create owner account with hashed password
    "INSERT OR IGNORE INTO users (email, password_hash, first_name, last_name, account_type, plan, status, max_devices, email_verified) VALUES 
    ('paulhalonen@gmail.com', '\$2y\$12\$placeholder_hash_change_me', 'Kah-Len', 'Halonen', 'vip', 'business', 'active', 999, 1)"
];

// ============================================
// DATABASE 3: devices.db
// ============================================
$devicesSchema = "
-- Devices table
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
    is_online BOOLEAN DEFAULT 0,
    last_seen DATETIME,
    last_handshake DATETIME,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Device Configs table (pre-generated WireGuard configs)
CREATE TABLE IF NOT EXISTS device_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT NOT NULL,
    server_id INTEGER NOT NULL,
    config_content TEXT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    downloaded_at DATETIME,
    UNIQUE(device_id, server_id)
);

-- IP Pool table (track assigned IPs)
CREATE TABLE IF NOT EXISTS ip_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL UNIQUE,
    device_id TEXT,
    assigned_at DATETIME,
    is_available BOOLEAN DEFAULT 1
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_devices_user_id ON devices(user_id);
CREATE INDEX IF NOT EXISTS idx_devices_device_id ON devices(device_id);
CREATE INDEX IF NOT EXISTS idx_devices_public_key ON devices(public_key);
CREATE INDEX IF NOT EXISTS idx_devices_assigned_ip ON devices(assigned_ip);
CREATE INDEX IF NOT EXISTS idx_device_configs_device ON device_configs(device_id);
CREATE INDEX IF NOT EXISTS idx_ip_pool_available ON ip_pool(is_available);
";

$devicesSeed = [
    // Pre-populate IP pool (10.8.0.2 to 10.8.0.254)
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.2', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.3', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.4', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.5', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.6', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.7', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.8', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.9', 1)",
    "INSERT OR IGNORE INTO ip_pool (ip_address, is_available) VALUES ('10.8.0.10', 1)"
    // More IPs will be added dynamically as needed
];

// ============================================
// DATABASE 4: servers.db
// ============================================
$serversSchema = "
-- Servers table
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
    private_key_encrypted TEXT,
    endpoint TEXT NOT NULL,
    dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
    status TEXT DEFAULT 'online',
    is_vip_only BOOLEAN DEFAULT 0,
    dedicated_user_email TEXT,
    max_connections INTEGER DEFAULT 100,
    current_connections INTEGER DEFAULT 0,
    bandwidth_limit_gb INTEGER,
    provider TEXT,
    monthly_cost DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Server Health table
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
    is_healthy BOOLEAN DEFAULT 1,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_servers_status ON servers(status);
CREATE INDEX IF NOT EXISTS idx_servers_vip ON servers(is_vip_only);
CREATE INDEX IF NOT EXISTS idx_health_server ON server_health(server_id);
CREATE INDEX IF NOT EXISTS idx_health_timestamp ON server_health(timestamp);
";

$serversSeed = [
    // Server 1: New York (Shared)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, provider, monthly_cost) VALUES 
    (1, 'new_york', 'New York Shared', 'New York, USA', 'United States', 'US', '66.94.103.91', 51820, 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', '66.94.103.91:51820', 'online', 0, 'Contabo', 6.75)",
    
    // Server 2: St. Louis VIP (Dedicated to seige235@yahoo.com)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, dedicated_user_email, provider, monthly_cost) VALUES 
    (2, 'st_louis', 'St. Louis VIP', 'St. Louis, USA', 'United States', 'US', '144.126.133.253', 51820, 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', '144.126.133.253:51820', 'online', 1, 'seige235@yahoo.com', 'Contabo', 6.15)",
    
    // Server 3: Dallas (Shared - Fly.io)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, provider, monthly_cost) VALUES 
    (3, 'dallas', 'Dallas Streaming', 'Dallas, USA', 'United States', 'US', '66.241.124.4', 51820, 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', '66.241.124.4:51820', 'online', 0, 'Fly.io', 5.00)",
    
    // Server 4: Toronto (Shared - Fly.io)
    "INSERT OR IGNORE INTO servers (id, name, display_name, location, country, country_code, ip_address, port, public_key, endpoint, status, is_vip_only, provider, monthly_cost) VALUES 
    (4, 'toronto', 'Toronto Canada', 'Toronto, Canada', 'Canada', 'CA', '66.241.125.247', 51820, 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', '66.241.125.247:51820', 'online', 0, 'Fly.io', 5.00)"
];

// ============================================
// DATABASE 5: billing.db
// ============================================
$billingSchema = "
-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    paypal_subscription_id TEXT UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    billing_cycle TEXT DEFAULT 'monthly',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    current_period_start DATETIME,
    current_period_end DATETIME,
    cancelled_at DATETIME,
    cancel_reason TEXT,
    trial_ends_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    invoice_number TEXT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'pending',
    due_date DATE,
    paid_at DATETIME,
    paypal_invoice_id TEXT,
    pdf_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_id INTEGER,
    subscription_id INTEGER,
    paypal_transaction_id TEXT UNIQUE,
    paypal_capture_id TEXT,
    amount DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'completed',
    payment_method TEXT DEFAULT 'paypal',
    payer_email TEXT,
    payer_name TEXT,
    raw_response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Payment Methods table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    paypal_email TEXT,
    is_default BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_paypal ON subscriptions(paypal_subscription_id);
CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_number ON invoices(invoice_number);
CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_paypal ON payments(paypal_transaction_id);
";

$billingSeed = [];

// ============================================
// DATABASE 6: logs.db
// ============================================
$logsSchema = "
-- Activity Logs table
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

-- Error Logs table
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

-- API Logs table
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

-- Indexes
CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_error_level ON error_logs(level);
CREATE INDEX IF NOT EXISTS idx_error_created ON error_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_api_endpoint ON api_logs(endpoint);
CREATE INDEX IF NOT EXISTS idx_api_created ON api_logs(created_at);
";

$logsSeed = [];

// ============================================
// DATABASE 7: support.db
// ============================================
$supportSchema = "
-- Support Tickets table
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

-- Ticket Messages table
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,
    is_staff BOOLEAN DEFAULT 0,
    message TEXT NOT NULL,
    attachments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

-- Knowledge Base table
CREATE TABLE IF NOT EXISTS knowledge_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    content TEXT NOT NULL,
    category TEXT,
    tags TEXT,
    views INTEGER DEFAULT 0,
    is_published BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_tickets_user ON tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_number ON tickets(ticket_number);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);
CREATE INDEX IF NOT EXISTS idx_messages_ticket ON ticket_messages(ticket_id);
CREATE INDEX IF NOT EXISTS idx_kb_slug ON knowledge_base(slug);
CREATE INDEX IF NOT EXISTS idx_kb_category ON knowledge_base(category);
";

$supportSeed = [];

// ============================================
// RUN DATABASE CREATION
// ============================================

// Ensure database directory exists
if (!file_exists(DB_PATH)) {
    mkdir(DB_PATH, 0755, true);
}

// Create each database
createDatabase('main.db', $mainSchema, $mainSeed);
createDatabase('users.db', $usersSchema, $usersSeed);
createDatabase('devices.db', $devicesSchema, $devicesSeed);
createDatabase('servers.db', $serversSchema, $serversSeed);
createDatabase('billing.db', $billingSchema, $billingSeed);
createDatabase('logs.db', $logsSchema, $logsSeed);
createDatabase('support.db', $supportSchema, $supportSeed);

// ============================================
// OUTPUT HTML
// ============================================
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
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
        }
        .card {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .card h2 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .db-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .db-item:last-child { margin-bottom: 0; }
        .db-name {
            font-family: monospace;
            color: #00d9ff;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status.success {
            background: rgba(0,255,136,0.15);
            color: #00ff88;
        }
        .status.error {
            background: rgba(255,100,100,0.15);
            color: #ff6464;
        }
        .size {
            color: #888;
            font-size: 0.85rem;
        }
        .summary {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        .stat {
            text-align: center;
        }
        .stat-num {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #888;
        }
        .warning {
            background: rgba(255,170,0,0.1);
            border: 1px solid rgba(255,170,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #ffaa00;
        }
        .warning strong { color: #ffcc00; }
        .next-steps {
            background: rgba(0,217,255,0.1);
            border: 1px solid rgba(0,217,255,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .next-steps h3 {
            color: #00d9ff;
            margin-bottom: 10px;
        }
        .next-steps ul {
            list-style: none;
            padding-left: 0;
        }
        .next-steps li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        .next-steps li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: #00ff88;
        }
        a { color: #00d9ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 TrueVault VPN</h1>
        <p class="subtitle">Database Setup Complete</p>
        
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
        
        <div class="summary">
            <div class="stat">
                <div class="stat-num"><?php echo count(array_filter($results, fn($r) => $r['status'] === 'success')); ?></div>
                <div class="stat-label">Databases Created</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?php echo count($errors); ?></div>
                <div class="stat-label">Errors</div>
            </div>
            <div class="stat">
                <div class="stat-num">4</div>
                <div class="stat-label">Servers Seeded</div>
            </div>
            <div class="stat">
                <div class="stat-num">2</div>
                <div class="stat-label">VIP Users</div>
            </div>
        </div>
        
        <?php if (count($errors) > 0): ?>
        <div class="warning">
            <strong>⚠️ Errors Occurred:</strong><br>
            <?php foreach ($errors as $db => $error): ?>
            <p><?php echo $db; ?>: <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="next-steps">
            <h3>📋 Next Steps</h3>
            <ul>
                <li>Protect this file: Rename or delete <code>setup-databases.php</code></li>
                <li>Update owner password in users.db</li>
                <li>Test database connections via API</li>
                <li>Proceed to Phase 2: Authentication System</li>
            </ul>
        </div>
        
        <div class="warning">
            <strong>🔒 Security Notice:</strong><br>
            Delete or rename this file after setup! It contains sensitive database creation logic.
        </div>
    </div>
</body>
</html>
