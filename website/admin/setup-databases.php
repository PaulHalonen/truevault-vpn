<?php
/**
 * TrueVault VPN - Complete Database Setup Script
 * 
 * PURPOSE: Creates all 9 SQLite3 databases with proper schemas
 * IMPORTANT: Uses SQLite3 class (NOT PDO!)
 * RUN ONCE: Delete existing databases first if recreating
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';

// Prevent running in production without force parameter
if (ENVIRONMENT === 'production' && !isset($_GET['force'])) {
    die('Cannot run database setup in production. Add ?force=1 to override (DANGEROUS!)');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault VPN - Database Setup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; border-bottom: 3px solid #00d9ff; padding-bottom: 10px; }
        h2 { color: #00ff88; margin-top: 20px; }
        .success { background: #155724; border: 1px solid #28a745; color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #721c24; border: 1px solid #dc3545; color: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #0c5460; border: 1px solid #17a2b8; color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #856404; border: 1px solid #ffc107; color: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .database { margin: 20px 0; padding: 15px; background: #0f0f1a; border-left: 4px solid #00d9ff; border-radius: 5px; }
        ul { margin: 10px 0; padding-left: 25px; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ TrueVault VPN - Database Setup</h1>
    <p>Creating all 9 SQLite3 databases with proper schemas...</p>
    <p class="warning">⚠️ This uses SQLite3 class (NOT PDO!)</p>

<?php

$results = [];

// ============================================
// DATABASE 1: USERS.DB
// ============================================

try {
    echo '<div class="database"><h2>👤 Creating users.db...</h2>';
    
    if (file_exists(DB_USERS)) {
        unlink(DB_USERS);
        echo '<div class="info">Deleted existing users.db</div>';
    }
    
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create users table
    $db->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            tier TEXT NOT NULL DEFAULT 'standard' CHECK(tier IN ('standard', 'pro', 'vip', 'admin')),
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'suspended', 'cancelled', 'grace_period')),
            email_verified INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            login_attempts INTEGER DEFAULT 0,
            locked_until DATETIME,
            vip_approved INTEGER DEFAULT 0,
            vip_approved_at DATETIME,
            vip_approved_by TEXT,
            vip_server_id INTEGER,
            notes TEXT
        )
    ");
    
    $db->exec("CREATE INDEX idx_users_email ON users(email)");
    $db->exec("CREATE INDEX idx_users_tier ON users(tier)");
    $db->exec("CREATE INDEX idx_users_status ON users(status)");
    
    // Create password reset tokens table
    $db->exec("
        CREATE TABLE password_reset_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create email verification tokens table
    $db->exec("
        CREATE TABLE email_verification_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create sessions table
    $db->exec("
        CREATE TABLE sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_sessions_token ON sessions(session_token)");
    $db->exec("CREATE INDEX idx_sessions_user_id ON sessions(user_id)");
    
    $db->close();
    echo '<div class="success">✅ users.db created successfully!</div>';
    $results['users.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['users.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';


// ============================================
// DATABASE 2: DEVICES.DB
// ============================================

try {
    echo '<div class="database"><h2>📱 Creating devices.db...</h2>';
    
    if (file_exists(DB_DEVICES)) {
        unlink(DB_DEVICES);
        echo '<div class="info">Deleted existing devices.db</div>';
    }
    
    $db = new SQLite3(DB_DEVICES);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create devices table
    $db->exec("
        CREATE TABLE devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_name TEXT NOT NULL,
            device_type TEXT CHECK(device_type IN ('mobile', 'desktop', 'tablet', 'router', 'other')),
            public_key TEXT NOT NULL UNIQUE,
            private_key_encrypted TEXT NOT NULL,
            preshared_key TEXT,
            ipv4_address TEXT NOT NULL UNIQUE,
            ipv6_address TEXT UNIQUE,
            current_server_id INTEGER,
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'suspended')),
            last_handshake DATETIME,
            data_sent_bytes INTEGER DEFAULT 0,
            data_received_bytes INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_devices_user_id ON devices(user_id)");
    $db->exec("CREATE INDEX idx_devices_public_key ON devices(public_key)");
    $db->exec("CREATE INDEX idx_devices_server_id ON devices(current_server_id)");
    
    // Create device configs table
    $db->exec("
        CREATE TABLE device_configs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            config_content TEXT NOT NULL,
            qr_code_data TEXT,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            downloaded INTEGER DEFAULT 0,
            downloaded_at DATETIME,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )
    ");
    
    $db->close();
    echo '<div class="success">✅ devices.db created successfully!</div>';
    $results['devices.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['devices.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';

// ============================================
// DATABASE 3: SERVERS.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>🖥️ Creating servers.db...</h2>';
    
    if (file_exists(DB_SERVERS)) {
        unlink(DB_SERVERS);
        echo '<div class="info">Deleted existing servers.db</div>';
    }
    
    $db = new SQLite3(DB_SERVERS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create servers table
    $db->exec("
        CREATE TABLE servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL,
            country_code TEXT,
            endpoint TEXT NOT NULL,
            public_key TEXT NOT NULL UNIQUE,
            private_key_encrypted TEXT NOT NULL,
            listen_port INTEGER NOT NULL DEFAULT 51820,
            ip_pool_start TEXT NOT NULL,
            ip_pool_end TEXT NOT NULL,
            ip_pool_current TEXT,
            max_clients INTEGER DEFAULT 100,
            current_clients INTEGER DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'maintenance', 'offline')),
            vip_only INTEGER DEFAULT 0,
            dedicated_user_email TEXT,
            load_percentage INTEGER DEFAULT 0,
            last_health_check DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            notes TEXT
        )
    ");
    
    $db->exec("CREATE INDEX idx_servers_status ON servers(status)");
    $db->exec("CREATE INDEX idx_servers_vip_only ON servers(vip_only)");
    
    // Insert the 4 servers (SQLite3 style with bindValue)
    $servers = [
        ['New York Shared', 'New York, USA', 'US', '66.94.103.91:51820', 'NY_SERVER_PUBLIC_KEY_PLACEHOLDER', 'ENCRYPTED_PRIVATE_KEY', '10.8.0.2', '10.8.0.254', 0, null],
        ['St. Louis VIP', 'St. Louis, USA', 'US', '144.126.133.253:51820', 'STL_SERVER_PUBLIC_KEY_PLACEHOLDER', 'ENCRYPTED_PRIVATE_KEY', '10.8.1.2', '10.8.1.254', 1, 'seige235@yahoo.com'],
        ['Dallas Streaming', 'Dallas, USA', 'US', '66.241.124.4:51820', 'DALLAS_SERVER_PUBLIC_KEY_PLACEHOLDER', 'ENCRYPTED_PRIVATE_KEY', '10.8.2.2', '10.8.2.254', 0, null],
        ['Toronto Canada', 'Toronto, Canada', 'CA', '66.241.125.247:51820', 'TORONTO_SERVER_PUBLIC_KEY_PLACEHOLDER', 'ENCRYPTED_PRIVATE_KEY', '10.8.3.2', '10.8.3.254', 0, null]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, ip_pool_start, ip_pool_end, vip_only, dedicated_user_email)
        VALUES (:name, :location, :country, :endpoint, :pubkey, :privkey, :pool_start, :pool_end, :vip, :dedicated)
    ");
    
    foreach ($servers as $s) {
        $stmt->bindValue(':name', $s[0], SQLITE3_TEXT);
        $stmt->bindValue(':location', $s[1], SQLITE3_TEXT);
        $stmt->bindValue(':country', $s[2], SQLITE3_TEXT);
        $stmt->bindValue(':endpoint', $s[3], SQLITE3_TEXT);
        $stmt->bindValue(':pubkey', $s[4], SQLITE3_TEXT);
        $stmt->bindValue(':privkey', $s[5], SQLITE3_TEXT);
        $stmt->bindValue(':pool_start', $s[6], SQLITE3_TEXT);
        $stmt->bindValue(':pool_end', $s[7], SQLITE3_TEXT);
        $stmt->bindValue(':vip', $s[8], SQLITE3_INTEGER);
        $stmt->bindValue(':dedicated', $s[9], $s[9] ? SQLITE3_TEXT : SQLITE3_NULL);
        $stmt->execute();
        $stmt->reset();
    }
    
    $db->close();
    echo '<div class="success">✅ servers.db created with 4 servers!</div>';
    $results['servers.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['servers.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';


// ============================================
// DATABASE 4: BILLING.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>💳 Creating billing.db...</h2>';
    
    if (file_exists(DB_BILLING)) {
        unlink(DB_BILLING);
        echo '<div class="info">Deleted existing billing.db</div>';
    }
    
    $db = new SQLite3(DB_BILLING);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create subscriptions table
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
    $db->exec("CREATE INDEX idx_subscriptions_paypal_id ON subscriptions(paypal_subscription_id)");
    
    // Create transactions table
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
    $db->exec("CREATE INDEX idx_transactions_paypal_id ON transactions(paypal_transaction_id)");
    
    // Create invoices table
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
    
    // Create payment methods table
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
    
    $db->close();
    echo '<div class="success">✅ billing.db created successfully!</div>';
    $results['billing.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['billing.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';

// ============================================
// DATABASE 5: PORT_FORWARDS.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>🔌 Creating port_forwards.db...</h2>';
    
    if (file_exists(DB_PORT_FORWARDS)) {
        unlink(DB_PORT_FORWARDS);
        echo '<div class="info">Deleted existing port_forwards.db</div>';
    }
    
    $db = new SQLite3(DB_PORT_FORWARDS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create port forwards table
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
    
    // Create discovered devices table (from network scanner)
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
    
    $db->close();
    echo '<div class="success">✅ port_forwards.db created successfully!</div>';
    $results['port_forwards.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['port_forwards.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';


// ============================================
// DATABASE 6: PARENTAL_CONTROLS.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>👨‍👩‍👧‍👦 Creating parental_controls.db...</h2>';
    
    if (file_exists(DB_PARENTAL_CONTROLS)) {
        unlink(DB_PARENTAL_CONTROLS);
        echo '<div class="info">Deleted existing parental_controls.db</div>';
    }
    
    $db = new SQLite3(DB_PARENTAL_CONTROLS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create parental control rules table
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
    
    // Create blocked requests log
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
    
    // Create website categories table
    $db->exec("
        CREATE TABLE website_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_name TEXT NOT NULL UNIQUE,
            description TEXT,
            default_blocked INTEGER DEFAULT 0
        )
    ");
    
    // Insert default categories (SQLite3 style)
    $categories = [
        ['Adult Content', 'Pornography and adult entertainment', 1],
        ['Gambling', 'Online gambling and betting sites', 1],
        ['Violence', 'Violent or graphic content', 1],
        ['Drugs', 'Drug-related content', 1],
        ['Social Media', 'Social networking sites', 0],
        ['Gaming', 'Online gaming platforms', 0],
        ['Streaming', 'Video streaming services', 0],
        ['Shopping', 'E-commerce and shopping sites', 0]
    ];
    
    $stmt = $db->prepare("INSERT INTO website_categories (category_name, description, default_blocked) VALUES (:name, :desc, :blocked)");
    foreach ($categories as $cat) {
        $stmt->bindValue(':name', $cat[0], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $cat[1], SQLITE3_TEXT);
        $stmt->bindValue(':blocked', $cat[2], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }
    
    $db->close();
    echo '<div class="success">✅ parental_controls.db created with default categories!</div>';
    $results['parental_controls.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['parental_controls.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';

// ============================================
// DATABASE 7: ADMIN.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>🔐 Creating admin.db...</h2>';
    
    if (file_exists(DB_ADMIN)) {
        unlink(DB_ADMIN);
        echo '<div class="info">Deleted existing admin.db</div>';
    }
    
    $db = new SQLite3(DB_ADMIN);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create admin users table
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
    
    // NOTE: Admin user should be created via reset-database.php or admin panel
    // Not hardcoded here - this just creates the table structure
    // Admin credentials are managed through the database, not code
    echo '<div class="info">ℹ️ Admin users table created (empty - use reset-database.php to add admin)</div>';
    
    // Create system settings table
    $db->exec("
        CREATE TABLE system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type TEXT NOT NULL CHECK(setting_type IN ('string', 'integer', 'boolean', 'json')),
            description TEXT,
            editable INTEGER DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by TEXT
        )
    ");
    
    // Insert default settings
    $settings = [
        ['site_name', 'TrueVault VPN', 'string', 'Website name', 1],
        ['site_tagline', 'Your Complete Digital Fortress', 'string', 'Website tagline', 1],
        ['max_devices_standard', '3', 'integer', 'Max devices for Standard tier', 1],
        ['max_devices_pro', '5', 'integer', 'Max devices for Pro tier', 1],
        ['max_devices_vip', '999', 'integer', 'Max devices for VIP tier', 1],
        ['price_standard', '9.99', 'string', 'Standard tier price per month', 1],
        ['price_pro', '14.99', 'string', 'Pro tier price per month', 1],
        ['paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'string', 'PayPal Client ID', 1],
        ['paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'string', 'PayPal Secret Key', 1],
        ['paypal_mode', 'live', 'string', 'PayPal mode (sandbox/live)', 1],
        ['email_from', 'noreply@vpn.the-truth-publishing.com', 'string', 'From email address', 1],
        ['email_from_name', 'TrueVault VPN', 'string', 'From name for emails', 1],
        ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', 1],
        ['registration_enabled', 'true', 'boolean', 'Allow new registrations', 1]
    ];
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, editable) VALUES (:key, :value, :type, :desc, :editable)");
    foreach ($settings as $s) {
        $stmt->bindValue(':key', $s[0], SQLITE3_TEXT);
        $stmt->bindValue(':value', $s[1], SQLITE3_TEXT);
        $stmt->bindValue(':type', $s[2], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $s[3], SQLITE3_TEXT);
        $stmt->bindValue(':editable', $s[4], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }
    
    // Create VIP list table
    $db->exec("
        CREATE TABLE vip_list (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            notes TEXT,
            dedicated_server_id INTEGER,
            access_level TEXT DEFAULT 'full',
            status TEXT DEFAULT 'active',
            added_by TEXT,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // NOTE: VIP list entries should be added via reset-database.php or admin panel
    // Not hardcoded here - this just creates the table structure
    // VIP list is managed through the database, not code
    echo '<div class="info">ℹ️ VIP list table created (empty - use reset-database.php to add VIPs)</div>';
    
    $db->close();
    echo '<div class="success">✅ admin.db created with default settings and VIP list!</div>';
    $results['admin.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['admin.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';


// ============================================
// DATABASE 8: LOGS.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>📊 Creating logs.db...</h2>';
    
    if (file_exists(DB_LOGS)) {
        unlink(DB_LOGS);
        echo '<div class="info">Deleted existing logs.db</div>';
    }
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create security events log
    $db->exec("
        CREATE TABLE security_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_type TEXT NOT NULL,
            severity TEXT NOT NULL CHECK(severity IN ('low', 'medium', 'high', 'critical')),
            user_id INTEGER,
            email TEXT,
            ip_address TEXT,
            user_agent TEXT,
            details TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_security_events_type ON security_events(event_type)");
    $db->exec("CREATE INDEX idx_security_events_severity ON security_events(severity)");
    $db->exec("CREATE INDEX idx_security_events_created_at ON security_events(created_at)");
    
    // Create audit log
    $db->exec("
        CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_id INTEGER,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_audit_log_entity ON audit_log(entity_type, entity_id)");
    $db->exec("CREATE INDEX idx_audit_log_user_id ON audit_log(user_id)");
    $db->exec("CREATE INDEX idx_audit_log_created_at ON audit_log(created_at)");
    
    // Create API requests log
    $db->exec("
        CREATE TABLE api_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            endpoint TEXT NOT NULL,
            method TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            response_code INTEGER,
            response_time_ms INTEGER,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_api_requests_user_id ON api_requests(user_id)");
    $db->exec("CREATE INDEX idx_api_requests_created_at ON api_requests(created_at)");
    
    // Create error log
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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_error_log_level ON error_log(error_level)");
    $db->exec("CREATE INDEX idx_error_log_created_at ON error_log(created_at)");
    
    $db->close();
    echo '<div class="success">✅ logs.db created successfully!</div>';
    $results['logs.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['logs.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';

// ============================================
// DATABASE 9: THEMES.DB (SQLite3!)
// ============================================

try {
    echo '<div class="database"><h2>🎨 Creating themes.db...</h2>';
    
    if (file_exists(DB_THEMES)) {
        unlink(DB_THEMES);
        echo '<div class="info">Deleted existing themes.db</div>';
    }
    
    $db = new SQLite3(DB_THEMES);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create themes table
    $db->exec("
        CREATE TABLE themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            colors TEXT NOT NULL,
            fonts TEXT NOT NULL,
            spacing TEXT NOT NULL,
            borders TEXT,
            is_active INTEGER DEFAULT 0,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create theme settings table
    $db->exec("
        CREATE TABLE theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default theme settings
    $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('seasonal_auto_switch', '0')");
    
    // Insert default dark theme
    $defaultColors = json_encode([
        'primary' => '#00d9ff',
        'secondary' => '#00ff88',
        'background' => '#1a1a2e',
        'surface' => '#16213e',
        'text' => '#ffffff',
        'text_muted' => '#888888',
        'success' => '#28a745',
        'error' => '#dc3545',
        'warning' => '#ffc107'
    ]);
    $defaultFonts = json_encode([
        'primary' => '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif',
        'heading' => '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif',
        'monospace' => 'Monaco, Consolas, monospace'
    ]);
    $defaultSpacing = json_encode([
        'xs' => '4px',
        'sm' => '8px',
        'md' => '16px',
        'lg' => '24px',
        'xl' => '32px'
    ]);
    
    $stmt = $db->prepare("INSERT INTO themes (theme_name, display_name, colors, fonts, spacing, is_active, is_default) VALUES (:name, :display, :colors, :fonts, :spacing, 1, 1)");
    $stmt->bindValue(':name', 'dark_default', SQLITE3_TEXT);
    $stmt->bindValue(':display', 'Dark (Default)', SQLITE3_TEXT);
    $stmt->bindValue(':colors', $defaultColors, SQLITE3_TEXT);
    $stmt->bindValue(':fonts', $defaultFonts, SQLITE3_TEXT);
    $stmt->bindValue(':spacing', $defaultSpacing, SQLITE3_TEXT);
    $stmt->execute();
    
    $db->close();
    echo '<div class="success">✅ themes.db created with default dark theme!</div>';
    $results['themes.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['themes.db'] = 'error: ' . $e->getMessage();
}
echo '</div>';

// ============================================
// FINAL SUMMARY
// ============================================

echo '<div class="info">';
echo '<h2>🎉 Database Setup Complete!</h2>';
echo '<h3>Summary:</h3>';
echo '<ul>';
foreach ($results as $db => $status) {
    $icon = strpos($status, 'success') !== false ? '✅' : '❌';
    echo "<li>$icon $db - $status</li>";
}
echo '</ul>';

$success_count = count(array_filter($results, function($s) { return strpos($s, 'success') !== false; }));
$total_count = count($results);

if ($success_count === $total_count) {
    echo '<div class="success">';
    echo '<h3>🎊 All 9 databases created successfully!</h3>';
    echo '<p><strong>Default VIPs Added:</strong></p>';
    echo '<ul><li>paulhalonen@gmail.com (Owner)</li><li>seige235@yahoo.com (Dedicated St. Louis server)</li></ul>';
    echo '<p><strong>Next Steps:</strong></p>';
    echo '<ol>';
    echo '<li>Update server public keys in servers.db</li>';
    echo '<li>Change default admin password (admin123)</li>';
    echo '<li>Verify PayPal credentials in admin.db</li>';
    echo '<li>Start building authentication system</li>';
    echo '</ol>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<p>Some databases failed. Review errors above.</p>';
    echo '</div>';
}

echo '</div>';

?>

</div>
</body>
</html>
