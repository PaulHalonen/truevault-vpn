<?php
/**
 * TrueVault VPN - COMPLETE Database Setup
 * Following MASTER_CHECKLIST_PART2.md EXACTLY
 * 
 * Creates ALL 9 databases with FULL schemas, indexes, and default data
 * NO PLACEHOLDERS - ALL REAL DATA
 */

// Security check
define('TRUEVAULT_INIT', true);

// Get config
require_once __DIR__ . '/../configs/config.php';

// Only allow in development or with special parameter
if (ENVIRONMENT === 'production' && !isset($_GET['force'])) {
    die('Production mode - add ?force=1 to run (DANGEROUS - will recreate all databases)');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault VPN - Complete Database Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; margin-bottom: 5px; }
        h2 { color: #00ff88; margin-top: 30px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .success { background: #155724; padding: 10px 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #721c24; padding: 10px 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #0c5460; padding: 10px 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        .warning { background: #856404; padding: 10px 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
        code { background: #000; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
        ul { margin: 10px 0; padding-left: 25px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ TrueVault VPN - Complete Database Setup</h1>
    <p>Following MASTER_CHECKLIST_PART2.md exactly - Full schemas with all indexes and default data</p>
    
<?php

$results = [];

// ============================================
// DATABASE 1: USERS.DB
// ============================================
echo '<h2>👤 Database 1: users.db</h2>';

try {
    if (file_exists(DB_USERS)) {
        unlink(DB_USERS);
        echo '<div class="warning">Deleted existing users.db</div>';
    }
    
    $db = new SQLite3(DB_USERS);
    
    // Users table
    $db->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            tier TEXT NOT NULL DEFAULT 'standard' CHECK(tier IN ('standard', 'pro', 'vip')),
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'suspended', 'pending')),
            email_verified INTEGER DEFAULT 0,
            email_verification_token TEXT,
            password_reset_token TEXT,
            password_reset_expires DATETIME,
            vip_approved INTEGER DEFAULT 0,
            vip_server_id INTEGER,
            last_login DATETIME,
            login_count INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_users_email ON users(email)");
    $db->exec("CREATE INDEX idx_users_tier ON users(tier)");
    $db->exec("CREATE INDEX idx_users_status ON users(status)");
    $db->exec("CREATE INDEX idx_users_vip_approved ON users(vip_approved)");
    
    // Sessions table
    $db->exec("
        CREATE TABLE sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            user_agent TEXT,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_sessions_token ON sessions(session_token)");
    $db->exec("CREATE INDEX idx_sessions_user_id ON sessions(user_id)");
    $db->exec("CREATE INDEX idx_sessions_expires ON sessions(expires_at)");
    
    $db->close();
    
    echo '<div class="success">✅ users.db created with tables: users, sessions</div>';
    echo '<div class="info">Indexes: idx_users_email, idx_users_tier, idx_users_status, idx_users_vip_approved, idx_sessions_token, idx_sessions_user_id, idx_sessions_expires</div>';
    $results['users.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['users.db'] = 'error';
}

// ============================================
// DATABASE 2: DEVICES.DB
// ============================================
echo '<h2>📱 Database 2: devices.db</h2>';

try {
    if (file_exists(DB_DEVICES)) {
        unlink(DB_DEVICES);
        echo '<div class="warning">Deleted existing devices.db</div>';
    }
    
    $db = new SQLite3(DB_DEVICES);
    
    // Devices table
    $db->exec("
        CREATE TABLE devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_name TEXT NOT NULL,
            device_type TEXT DEFAULT 'unknown',
            public_key TEXT NOT NULL UNIQUE,
            private_key_encrypted TEXT,
            ipv4_address TEXT NOT NULL UNIQUE,
            current_server_id INTEGER,
            last_handshake DATETIME,
            bytes_sent INTEGER DEFAULT 0,
            bytes_received INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_devices_user_id ON devices(user_id)");
    $db->exec("CREATE UNIQUE INDEX idx_devices_public_key ON devices(public_key)");
    $db->exec("CREATE UNIQUE INDEX idx_devices_ipv4 ON devices(ipv4_address)");
    $db->exec("CREATE INDEX idx_devices_server ON devices(current_server_id)");
    
    $db->close();
    
    echo '<div class="success">✅ devices.db created with table: devices</div>';
    echo '<div class="info">Indexes: idx_devices_user_id, idx_devices_public_key, idx_devices_ipv4, idx_devices_server</div>';
    $results['devices.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['devices.db'] = 'error';
}

// ============================================
// DATABASE 3: SERVERS.DB
// ============================================
echo '<h2>🖥️ Database 3: servers.db</h2>';

try {
    if (file_exists(DB_SERVERS)) {
        unlink(DB_SERVERS);
        echo '<div class="warning">Deleted existing servers.db</div>';
    }
    
    $db = new SQLite3(DB_SERVERS);
    
    // Servers table
    $db->exec("
        CREATE TABLE servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL,
            country_code TEXT NOT NULL,
            endpoint TEXT NOT NULL UNIQUE,
            public_key TEXT NOT NULL,
            ip_pool_start TEXT NOT NULL,
            ip_pool_end TEXT NOT NULL,
            dns_servers TEXT DEFAULT '1.1.1.1, 1.0.0.1',
            max_clients INTEGER DEFAULT 250,
            current_clients INTEGER DEFAULT 0,
            bandwidth_limit_mbps INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            vip_only INTEGER DEFAULT 0,
            vip_user_email TEXT,
            server_type TEXT DEFAULT 'shared' CHECK(server_type IN ('shared', 'dedicated', 'streaming', 'gaming')),
            provider TEXT,
            monthly_cost DECIMAL(10,2),
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_servers_endpoint ON servers(endpoint)");
    $db->exec("CREATE INDEX idx_servers_active ON servers(is_active)");
    $db->exec("CREATE INDEX idx_servers_vip ON servers(vip_only)");
    $db->exec("CREATE INDEX idx_servers_type ON servers(server_type)");
    
    // Insert ALL 4 servers with REAL keys
    $servers = [
        [
            'New York Shared', 'New York, US-East', 'US',
            '66.94.103.91:51820', 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+lKGai9n3s=',
            '10.8.0.2', '10.8.0.254', '1.1.1.1, 1.0.0.1',
            250, 0, 100, 1, 0, NULL, 'shared', 'Contabo', 6.75, 'General use shared server'
        ],
        [
            'St. Louis VIP', 'St. Louis, US-Central', 'US',
            '144.126.133.253:51820', 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
            '10.8.1.2', '10.8.1.254', '1.1.1.1, 1.0.0.1',
            1, 0, 0, 1, 1, 'seige235@yahoo.com', 'dedicated', 'Contabo', 6.15, 'Dedicated VIP server for seige235@yahoo.com'
        ],
        [
            'Dallas Streaming', 'Dallas, Texas', 'US',
            '66.241.124.4:51820', 'dFEz/d9TKfddk0Z6aMN03uO+j0GgQwXSR/+Ay+IXXmk=',
            '10.8.2.2', '10.8.2.254', '1.1.1.1, 1.0.0.1',
            250, 0, 50, 1, 0, NULL, 'streaming', 'Fly.io', 5.00, 'Optimized for streaming services'
        ],
        [
            'Toronto Canada', 'Toronto, Ontario', 'CA',
            '66.241.125.247:51820', 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
            '10.8.3.2', '10.8.3.254', '1.1.1.1, 1.0.0.1',
            250, 0, 50, 1, 0, NULL, 'shared', 'Fly.io', 5.00, 'Canadian server for regional access'
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO servers (name, location, country_code, endpoint, public_key, ip_pool_start, ip_pool_end, 
                           dns_servers, max_clients, current_clients, bandwidth_limit_mbps, is_active, 
                           vip_only, vip_user_email, server_type, provider, monthly_cost, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($servers as $server) {
        $stmt->reset();
        for ($i = 0; $i < count($server); $i++) {
            $stmt->bindValue($i + 1, $server[$i]);
        }
        $stmt->execute();
    }
    
    $db->close();
    
    echo '<div class="success">✅ servers.db created with 4 servers (ALL REAL KEYS)</div>';
    echo '<div class="info">Servers: New York Shared, St. Louis VIP (seige235@yahoo.com), Dallas Streaming, Toronto Canada</div>';
    $results['servers.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['servers.db'] = 'error';
}

// ============================================
// DATABASE 4: BILLING.DB
// ============================================
echo '<h2>💳 Database 4: billing.db</h2>';

try {
    if (file_exists(DB_BILLING)) {
        unlink(DB_BILLING);
        echo '<div class="warning">Deleted existing billing.db</div>';
    }
    
    $db = new SQLite3(DB_BILLING);
    
    // Subscriptions table
    $db->exec("
        CREATE TABLE subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            plan_id TEXT NOT NULL CHECK(plan_id IN ('standard', 'pro', 'vip')),
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'cancelled', 'expired', 'grace_period')),
            paypal_subscription_id TEXT UNIQUE,
            paypal_payer_id TEXT,
            start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            next_billing_date DATETIME,
            cancelled_at DATETIME,
            expires_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id)");
    $db->exec("CREATE INDEX idx_subscriptions_status ON subscriptions(status)");
    $db->exec("CREATE UNIQUE INDEX idx_subscriptions_paypal_id ON subscriptions(paypal_subscription_id)");
    
    // Transactions table
    $db->exec("
        CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            transaction_type TEXT NOT NULL CHECK(transaction_type IN ('payment', 'refund', 'chargeback')),
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT NOT NULL DEFAULT 'USD',
            paypal_transaction_id TEXT UNIQUE,
            paypal_order_id TEXT,
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'failed', 'refunded')),
            description TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME
        )
    ");
    
    $db->exec("CREATE INDEX idx_transactions_user_id ON transactions(user_id)");
    $db->exec("CREATE INDEX idx_transactions_status ON transactions(status)");
    $db->exec("CREATE UNIQUE INDEX idx_transactions_paypal_id ON transactions(paypal_transaction_id)");
    
    // Invoices table
    $db->exec("
        CREATE TABLE invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            invoice_number TEXT NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT NOT NULL DEFAULT 'USD',
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'failed', 'cancelled')),
            due_date DATETIME,
            paid_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_invoices_user_id ON invoices(user_id)");
    $db->exec("CREATE INDEX idx_invoices_status ON invoices(status)");
    $db->exec("CREATE UNIQUE INDEX idx_invoices_number ON invoices(invoice_number)");
    
    // Payment methods table
    $db->exec("
        CREATE TABLE payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            paypal_payer_id TEXT,
            paypal_email TEXT,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_payment_methods_user_id ON payment_methods(user_id)");
    
    $db->close();
    
    echo '<div class="success">✅ billing.db created with tables: subscriptions, transactions, invoices, payment_methods</div>';
    $results['billing.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['billing.db'] = 'error';
}

// ============================================
// DATABASE 5: PORT_FORWARDS.DB
// ============================================
echo '<h2>🔌 Database 5: port_forwards.db</h2>';

try {
    if (file_exists(DB_PORT_FORWARDS)) {
        unlink(DB_PORT_FORWARDS);
        echo '<div class="warning">Deleted existing port_forwards.db</div>';
    }
    
    $db = new SQLite3(DB_PORT_FORWARDS);
    
    // Port forwards table
    $db->exec("
        CREATE TABLE port_forwards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            rule_name TEXT NOT NULL,
            protocol TEXT NOT NULL CHECK(protocol IN ('tcp', 'udp', 'both')),
            external_port INTEGER NOT NULL,
            internal_ip TEXT NOT NULL,
            internal_port INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, external_port)
        )
    ");
    
    $db->exec("CREATE INDEX idx_port_forwards_user_id ON port_forwards(user_id)");
    $db->exec("CREATE INDEX idx_port_forwards_device_id ON port_forwards(device_id)");
    $db->exec("CREATE INDEX idx_port_forwards_status ON port_forwards(status)");
    
    // Discovered devices table (from network scanner)
    $db->exec("
        CREATE TABLE discovered_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            mac_address TEXT,
            hostname TEXT,
            vendor TEXT,
            device_type TEXT,
            device_name TEXT,
            icon TEXT,
            open_ports TEXT,
            discovered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, device_id)
        )
    ");
    
    $db->exec("CREATE INDEX idx_discovered_devices_user_id ON discovered_devices(user_id)");
    $db->exec("CREATE INDEX idx_discovered_devices_type ON discovered_devices(device_type)");
    
    $db->close();
    
    echo '<div class="success">✅ port_forwards.db created with tables: port_forwards, discovered_devices</div>';
    $results['port_forwards.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['port_forwards.db'] = 'error';
}

// ============================================
// DATABASE 6: PARENTAL_CONTROLS.DB
// ============================================
echo '<h2>👨‍👩‍👧‍👦 Database 6: parental_controls.db</h2>';

try {
    if (file_exists(DB_PARENTAL)) {
        unlink(DB_PARENTAL);
        echo '<div class="warning">Deleted existing parental_controls.db</div>';
    }
    
    $db = new SQLite3(DB_PARENTAL);
    
    // Parental rules table
    $db->exec("
        CREATE TABLE parental_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            rule_name TEXT NOT NULL,
            enabled INTEGER DEFAULT 1,
            block_categories TEXT,
            block_keywords TEXT,
            whitelist_domains TEXT,
            blacklist_domains TEXT,
            schedule_enabled INTEGER DEFAULT 0,
            schedule_days TEXT,
            schedule_start_time TEXT,
            schedule_end_time TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_parental_rules_user_id ON parental_rules(user_id)");
    $db->exec("CREATE INDEX idx_parental_rules_device_id ON parental_rules(device_id)");
    $db->exec("CREATE INDEX idx_parental_rules_enabled ON parental_rules(enabled)");
    
    // Blocked requests log
    $db->exec("
        CREATE TABLE blocked_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            rule_id INTEGER NOT NULL,
            blocked_domain TEXT NOT NULL,
            reason TEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_blocked_requests_user_id ON blocked_requests(user_id)");
    $db->exec("CREATE INDEX idx_blocked_requests_timestamp ON blocked_requests(timestamp)");
    
    // Website categories table
    $db->exec("
        CREATE TABLE website_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_name TEXT NOT NULL UNIQUE,
            description TEXT,
            default_blocked INTEGER DEFAULT 0
        )
    ");
    
    // Insert default categories
    $categories = [
        ['Adult Content', 'Pornography and adult entertainment', 1],
        ['Gambling', 'Online gambling and betting sites', 1],
        ['Violence', 'Violent or graphic content', 1],
        ['Drugs', 'Drug-related content', 1],
        ['Social Media', 'Social networking sites', 0],
        ['Gaming', 'Online gaming platforms', 0],
        ['Streaming', 'Video streaming services', 0],
        ['Shopping', 'E-commerce and shopping sites', 0],
        ['News', 'News and media sites', 0],
        ['Search Engines', 'Search engine websites', 0]
    ];
    
    $stmt = $db->prepare("INSERT INTO website_categories (category_name, description, default_blocked) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->reset();
        $stmt->bindValue(1, $cat[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $cat[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $cat[2], SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    $db->close();
    
    echo '<div class="success">✅ parental_controls.db created with tables: parental_rules, blocked_requests, website_categories</div>';
    echo '<div class="info">Pre-loaded 10 website categories (4 blocked by default: Adult, Gambling, Violence, Drugs)</div>';
    $results['parental_controls.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['parental_controls.db'] = 'error';
}

// ============================================
// DATABASE 7: ADMIN.DB
// ============================================
echo '<h2>🔐 Database 7: admin.db</h2>';

try {
    if (file_exists(DB_ADMIN)) {
        unlink(DB_ADMIN);
        echo '<div class="warning">Deleted existing admin.db</div>';
    }
    
    $db = new SQLite3(DB_ADMIN);
    
    // Admin users table
    $db->exec("
        CREATE TABLE admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'admin' CHECK(role IN ('super_admin', 'admin', 'support')),
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
            last_login DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_admin_users_email ON admin_users(email)");
    
    // Insert admin user with REAL credentials
    $admin_hash = password_hash('Asasasas4!', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare("INSERT INTO admin_users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, 'paulhalonen@gmail.com', SQLITE3_TEXT);
    $stmt->bindValue(2, $admin_hash, SQLITE3_TEXT);
    $stmt->bindValue(3, 'Paul Halonen (Owner)', SQLITE3_TEXT);
    $stmt->bindValue(4, 'super_admin', SQLITE3_TEXT);
    $stmt->bindValue(5, 'active', SQLITE3_TEXT);
    $stmt->execute();
    
    // System settings table
    $db->exec("
        CREATE TABLE system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type TEXT NOT NULL CHECK(setting_type IN ('string', 'integer', 'boolean', 'json', 'decimal')),
            description TEXT,
            editable INTEGER DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by TEXT
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_system_settings_key ON system_settings(setting_key)");
    
    // Insert ALL default settings with REAL values
    $settings = [
        // Site Settings
        ['site_name', 'TrueVault VPN', 'string', 'Website name', 1],
        ['site_tagline', 'Your Complete Digital Fortress', 'string', 'Website tagline', 1],
        ['site_url', 'https://vpn.the-truth-publishing.com', 'string', 'Website URL', 1],
        
        // Device Limits
        ['max_devices_standard', '3', 'integer', 'Max devices for Standard tier', 1],
        ['max_devices_pro', '5', 'integer', 'Max devices for Pro tier', 1],
        ['max_devices_vip', '999', 'integer', 'Max devices for VIP tier', 1],
        
        // Pricing (REAL prices)
        ['price_standard', '9.97', 'decimal', 'Standard tier price per month', 1],
        ['price_pro', '14.97', 'decimal', 'Pro tier price per month', 1],
        ['price_dedicated', '39.97', 'decimal', 'Dedicated server price per month', 1],
        
        // PayPal Settings (REAL credentials)
        ['paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'string', 'PayPal Client ID', 1],
        ['paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'string', 'PayPal Secret Key', 1],
        ['paypal_mode', 'live', 'string', 'PayPal mode (sandbox/live)', 1],
        ['paypal_webhook_id', '46924926WL757580D', 'string', 'PayPal Webhook ID', 1],
        
        // Email Settings
        ['email_from', 'admin@the-truth-publishing.com', 'string', 'From email address for customers', 1],
        ['email_from_name', 'TrueVault VPN', 'string', 'From name for emails', 1],
        ['email_notifications', 'paulhalonen@gmail.com', 'string', 'Admin notification email', 1],
        
        // System Settings
        ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', 1],
        ['registration_enabled', 'true', 'boolean', 'Allow new registrations', 1],
        ['trial_days', '0', 'integer', 'Free trial days (0 = no trial)', 1],
        
        // WireGuard Settings
        ['wireguard_port', '51820', 'integer', 'WireGuard port', 0],
        ['wireguard_dns', '1.1.1.1, 1.0.0.1', 'string', 'DNS servers for VPN clients', 1],
        ['wireguard_keepalive', '25', 'integer', 'Persistent keepalive seconds', 1]
    ];
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, editable) VALUES (?, ?, ?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->reset();
        $stmt->bindValue(1, $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $setting[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $setting[2], SQLITE3_TEXT);
        $stmt->bindValue(4, $setting[3], SQLITE3_TEXT);
        $stmt->bindValue(5, $setting[4], SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // VIP list table
    $db->exec("
        CREATE TABLE vip_list (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            notes TEXT,
            dedicated_server_id INTEGER,
            added_by TEXT,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_vip_list_email ON vip_list(email)");
    
    // Add the VIPs
    $stmt = $db->prepare("INSERT INTO vip_list (email, notes, dedicated_server_id, added_by) VALUES (?, ?, ?, ?)");
    
    $stmt->bindValue(1, 'paulhalonen@gmail.com', SQLITE3_TEXT);
    $stmt->bindValue(2, 'Owner - Full access', SQLITE3_TEXT);
    $stmt->bindValue(3, null, SQLITE3_NULL);
    $stmt->bindValue(4, 'system', SQLITE3_TEXT);
    $stmt->execute();
    
    $stmt->reset();
    $stmt->bindValue(1, 'seige235@yahoo.com', SQLITE3_TEXT);
    $stmt->bindValue(2, 'Dedicated St. Louis server - completely free', SQLITE3_TEXT);
    $stmt->bindValue(3, 2, SQLITE3_INTEGER);  // Server ID 2 is St. Louis VIP
    $stmt->bindValue(4, 'system', SQLITE3_TEXT);
    $stmt->execute();
    
    $db->close();
    
    echo '<div class="success">✅ admin.db created with tables: admin_users, system_settings, vip_list</div>';
    echo '<div class="info">Admin: paulhalonen@gmail.com (super_admin)</div>';
    echo '<div class="info">22 system settings configured with REAL PayPal credentials</div>';
    echo '<div class="info">VIP List: paulhalonen@gmail.com (owner), seige235@yahoo.com (dedicated server)</div>';
    $results['admin.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['admin.db'] = 'error';
}

// ============================================
// DATABASE 8: LOGS.DB
// ============================================
echo '<h2>📊 Database 8: logs.db</h2>';

try {
    if (file_exists(DB_LOGS)) {
        unlink(DB_LOGS);
        echo '<div class="warning">Deleted existing logs.db</div>';
    }
    
    $db = new SQLite3(DB_LOGS);
    
    // Security events log
    $db->exec("
        CREATE TABLE security_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_type TEXT NOT NULL,
            severity TEXT NOT NULL CHECK(severity IN ('low', 'medium', 'high', 'critical')),
            user_id INTEGER,
            ip_address TEXT,
            user_agent TEXT,
            event_data TEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_security_events_type ON security_events(event_type)");
    $db->exec("CREATE INDEX idx_security_events_severity ON security_events(severity)");
    $db->exec("CREATE INDEX idx_security_events_timestamp ON security_events(timestamp)");
    $db->exec("CREATE INDEX idx_security_events_user ON security_events(user_id)");
    
    // Audit log
    $db->exec("
        CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_id INTEGER,
            performed_by INTEGER,
            old_values TEXT,
            new_values TEXT,
            ip_address TEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_audit_log_entity ON audit_log(entity_type, entity_id)");
    $db->exec("CREATE INDEX idx_audit_log_performed_by ON audit_log(performed_by)");
    $db->exec("CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp)");
    
    // API requests log (for rate limiting and monitoring)
    $db->exec("
        CREATE TABLE api_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            endpoint TEXT NOT NULL,
            method TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            request_data TEXT,
            response_code INTEGER,
            response_time_ms INTEGER,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_api_requests_user_id ON api_requests(user_id)");
    $db->exec("CREATE INDEX idx_api_requests_endpoint ON api_requests(endpoint)");
    $db->exec("CREATE INDEX idx_api_requests_timestamp ON api_requests(timestamp)");
    
    // Error log
    $db->exec("
        CREATE TABLE error_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            error_level TEXT NOT NULL,
            error_message TEXT NOT NULL,
            error_file TEXT,
            error_line INTEGER,
            stack_trace TEXT,
            user_id INTEGER,
            ip_address TEXT,
            url TEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_error_log_level ON error_log(error_level)");
    $db->exec("CREATE INDEX idx_error_log_timestamp ON error_log(timestamp)");
    
    // Connection log (VPN connections)
    $db->exec("
        CREATE TABLE connection_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            connected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            disconnected_at DATETIME,
            duration_seconds INTEGER,
            bytes_sent INTEGER DEFAULT 0,
            bytes_received INTEGER DEFAULT 0,
            client_ip TEXT
        )
    ");
    
    $db->exec("CREATE INDEX idx_connection_log_user ON connection_log(user_id)");
    $db->exec("CREATE INDEX idx_connection_log_device ON connection_log(device_id)");
    $db->exec("CREATE INDEX idx_connection_log_server ON connection_log(server_id)");
    $db->exec("CREATE INDEX idx_connection_log_connected ON connection_log(connected_at)");
    
    $db->close();
    
    echo '<div class="success">✅ logs.db created with tables: security_events, audit_log, api_requests, error_log, connection_log</div>';
    $results['logs.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['logs.db'] = 'error';
}

// ============================================
// DATABASE 9: THEMES.DB
// ============================================
echo '<h2>🎨 Database 9: themes.db</h2>';

try {
    if (file_exists(DB_THEMES)) {
        unlink(DB_THEMES);
        echo '<div class="warning">Deleted existing themes.db</div>';
    }
    
    $db = new SQLite3(DB_THEMES);
    
    // Themes table
    $db->exec("
        CREATE TABLE themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_name TEXT NOT NULL UNIQUE,
            theme_slug TEXT NOT NULL UNIQUE,
            category TEXT DEFAULT 'standard',
            primary_color TEXT NOT NULL DEFAULT '#667eea',
            secondary_color TEXT NOT NULL DEFAULT '#764ba2',
            accent_color TEXT DEFAULT '#00d9ff',
            background_color TEXT NOT NULL DEFAULT '#1a1a2e',
            surface_color TEXT DEFAULT '#16213e',
            text_color TEXT NOT NULL DEFAULT '#ffffff',
            text_muted_color TEXT DEFAULT '#a0aec0',
            success_color TEXT DEFAULT '#00ff88',
            warning_color TEXT DEFAULT '#ffc107',
            error_color TEXT DEFAULT '#ff5252',
            font_family TEXT DEFAULT 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
            font_size_base TEXT DEFAULT '16px',
            border_radius TEXT DEFAULT '8px',
            is_active INTEGER DEFAULT 0,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE UNIQUE INDEX idx_themes_slug ON themes(theme_slug)");
    $db->exec("CREATE INDEX idx_themes_active ON themes(is_active)");
    
    // Insert default theme
    $db->exec("
        INSERT INTO themes (
            theme_name, theme_slug, category,
            primary_color, secondary_color, accent_color,
            background_color, surface_color, text_color, text_muted_color,
            success_color, warning_color, error_color,
            font_family, font_size_base, border_radius,
            is_active, is_default
        ) VALUES (
            'Professional Dark', 'professional-dark', 'standard',
            '#667eea', '#764ba2', '#00d9ff',
            '#1a1a2e', '#16213e', '#ffffff', '#a0aec0',
            '#00ff88', '#ffc107', '#ff5252',
            'Inter, -apple-system, BlinkMacSystemFont, sans-serif', '16px', '8px',
            1, 1
        )
    ");
    
    // Insert additional themes
    $db->exec("
        INSERT INTO themes (theme_name, theme_slug, category, primary_color, secondary_color, accent_color, background_color, surface_color, text_color, is_active, is_default)
        VALUES ('Midnight Blue', 'midnight-blue', 'standard', '#3498db', '#2980b9', '#1abc9c', '#0a0a1a', '#111127', '#ecf0f1', 0, 0)
    ");
    
    $db->exec("
        INSERT INTO themes (theme_name, theme_slug, category, primary_color, secondary_color, accent_color, background_color, surface_color, text_color, is_active, is_default)
        VALUES ('Forest Green', 'forest-green', 'standard', '#27ae60', '#2ecc71', '#f39c12', '#0d1f0d', '#1a2e1a', '#ecf0f1', 0, 0)
    ");
    
    // Theme settings table
    $db->exec("
        CREATE TABLE theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('active_theme', 'professional-dark')");
    $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('custom_css', '')");
    $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('logo_url', '')");
    $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('favicon_url', '')");
    
    $db->close();
    
    echo '<div class="success">✅ themes.db created with tables: themes, theme_settings</div>';
    echo '<div class="info">3 themes pre-loaded: Professional Dark (active), Midnight Blue, Forest Green</div>';
    $results['themes.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['themes.db'] = 'error';
}

// ============================================
// FINAL SUMMARY
// ============================================
echo '<h2>🎉 Setup Complete!</h2>';

$success_count = count(array_filter($results, function($s) { return $s === 'success'; }));
$total_count = count($results);

echo '<div class="' . ($success_count === $total_count ? 'success' : 'error') . '">';
echo "<strong>Results: $success_count / $total_count databases created successfully</strong>";
echo '<ul>';
foreach ($results as $db => $status) {
    $icon = $status === 'success' ? '✅' : '❌';
    echo "<li>$icon $db</li>";
}
echo '</ul>';
echo '</div>';

if ($success_count === $total_count) {
    echo '<div class="info">';
    echo '<h3>Summary of Data Created:</h3>';
    echo '<ul>';
    echo '<li><strong>Admin User:</strong> paulhalonen@gmail.com / Asasasas4!</li>';
    echo '<li><strong>4 VPN Servers:</strong> New York, St. Louis (VIP), Dallas, Toronto - ALL with real WireGuard keys</li>';
    echo '<li><strong>VIP Users:</strong> paulhalonen@gmail.com, seige235@yahoo.com (dedicated server)</li>';
    echo '<li><strong>22 System Settings:</strong> Including real PayPal credentials</li>';
    echo '<li><strong>10 Website Categories:</strong> For parental controls</li>';
    echo '<li><strong>3 Themes:</strong> Professional Dark (active), Midnight Blue, Forest Green</li>';
    echo '</ul>';
    echo '</div>';
}

?>

</div>
</body>
</html>
