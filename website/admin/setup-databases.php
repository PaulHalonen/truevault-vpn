<?php
/**
 * TrueVault VPN - Database Setup Script
 * 
 * PURPOSE: Creates all 8 SQLite databases with proper schemas
 * RUN ONCE: This should only be run during initial setup
 * NOTE: Uses SQLite3 class (not PDO)
 * 
 * @created January 2026
 * @version 1.0.1
 * @updated January 18, 2026 - Converted from PDO to SQLite3
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';

// Prevent running in production without confirmation
if (ENVIRONMENT === 'production') {
    die('Cannot run database setup in production without manual confirmation.');
}

// Output header
?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault VPN - Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .database {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ TrueVault VPN - Database Setup</h1>
        <p>This script will create all 8 SQLite databases with proper schemas using SQLite3.</p>

<?php

// Array to track results
$results = [];

// ============================================
// DATABASE 1: USERS.DB
// ============================================

try {
    echo '<div class="database"><h2>📦 Creating users.db...</h2>';
    
    // Check if database already exists
    if (file_exists(DB_USERS)) {
        throw new Exception('Database already exists! Delete it first if you want to recreate.');
    }
    
    // Create database
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
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
            notes TEXT
        )
    ");
    
    // Create indexes
    $db->exec("CREATE INDEX idx_users_email ON users(email)");
    $db->exec("CREATE INDEX idx_users_tier ON users(tier)");
    $db->exec("CREATE INDEX idx_users_status ON users(status)");
    
    // Create password reset tokens table
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
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
        CREATE TABLE IF NOT EXISTS email_verification_tokens (
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
        CREATE TABLE IF NOT EXISTS sessions (
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
    $results['users.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 2: DEVICES.DB
// ============================================

try {
    echo '<div class="database"><h2>📱 Creating devices.db...</h2>';
    
    if (file_exists(DB_DEVICES)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_DEVICES);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create devices table
    $db->exec("
        CREATE TABLE IF NOT EXISTS devices (
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
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_devices_user_id ON devices(user_id)");
    $db->exec("CREATE INDEX idx_devices_public_key ON devices(public_key)");
    $db->exec("CREATE INDEX idx_devices_server_id ON devices(current_server_id)");
    
    // Create device configs table (stores generated config files)
    $db->exec("
        CREATE TABLE IF NOT EXISTS device_configs (
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
    $results['devices.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 3: SERVERS.DB
// ============================================

try {
    echo '<div class="database"><h2>🖥️ Creating servers.db...</h2>';
    
    if (file_exists(DB_SERVERS)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_SERVERS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create servers table
    $db->exec("
        CREATE TABLE IF NOT EXISTS servers (
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
    
    // Insert the 4 servers from your configuration
    $servers = [
        [
            'name' => 'New York Shared',
            'location' => 'New York, USA',
            'country_code' => 'US',
            'endpoint' => '66.94.103.91:51820',
            'public_key' => 'NY_SERVER_PUBLIC_KEY_HERE',
            'private_key_encrypted' => 'ENCRYPTED_PRIVATE_KEY',
            'ip_pool_start' => '10.8.0.2',
            'ip_pool_end' => '10.8.0.254',
            'vip_only' => 0,
            'dedicated_user_email' => null
        ],
        [
            'name' => 'St. Louis VIP',
            'location' => 'St. Louis, USA',
            'country_code' => 'US',
            'endpoint' => '144.126.133.253:51820',
            'public_key' => 'STL_SERVER_PUBLIC_KEY_HERE',
            'private_key_encrypted' => 'ENCRYPTED_PRIVATE_KEY',
            'ip_pool_start' => '10.8.1.2',
            'ip_pool_end' => '10.8.1.254',
            'vip_only' => 1,
            'dedicated_user_email' => 'seige235@yahoo.com'
        ],
        [
            'name' => 'Dallas Streaming',
            'location' => 'Dallas, USA',
            'country_code' => 'US',
            'endpoint' => '66.241.124.4:51820',
            'public_key' => 'DALLAS_SERVER_PUBLIC_KEY_HERE',
            'private_key_encrypted' => 'ENCRYPTED_PRIVATE_KEY',
            'ip_pool_start' => '10.8.2.2',
            'ip_pool_end' => '10.8.2.254',
            'vip_only' => 0,
            'dedicated_user_email' => null
        ],
        [
            'name' => 'Toronto Canada',
            'location' => 'Toronto, Canada',
            'country_code' => 'CA',
            'endpoint' => '66.241.125.247:51820',
            'public_key' => 'TORONTO_SERVER_PUBLIC_KEY_HERE',
            'private_key_encrypted' => 'ENCRYPTED_PRIVATE_KEY',
            'ip_pool_start' => '10.8.3.2',
            'ip_pool_end' => '10.8.3.254',
            'vip_only' => 0,
            'dedicated_user_email' => null
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, 
                            ip_pool_start, ip_pool_end, vip_only, dedicated_user_email)
        VALUES (:name, :location, :country_code, :endpoint, :public_key, :private_key_encrypted, 
                :ip_pool_start, :ip_pool_end, :vip_only, :dedicated_user_email)
    ");
    
    foreach ($servers as $server) {
        $stmt->bindValue(':name', $server['name'], SQLITE3_TEXT);
        $stmt->bindValue(':location', $server['location'], SQLITE3_TEXT);
        $stmt->bindValue(':country_code', $server['country_code'], SQLITE3_TEXT);
        $stmt->bindValue(':endpoint', $server['endpoint'], SQLITE3_TEXT);
        $stmt->bindValue(':public_key', $server['public_key'], SQLITE3_TEXT);
        $stmt->bindValue(':private_key_encrypted', $server['private_key_encrypted'], SQLITE3_TEXT);
        $stmt->bindValue(':ip_pool_start', $server['ip_pool_start'], SQLITE3_TEXT);
        $stmt->bindValue(':ip_pool_end', $server['ip_pool_end'], SQLITE3_TEXT);
        $stmt->bindValue(':vip_only', $server['vip_only'], SQLITE3_INTEGER);
        $stmt->bindValue(':dedicated_user_email', $server['dedicated_user_email'], SQLITE3_TEXT);
        $stmt->execute();
        $stmt->reset();
    }
    
    $db->close();
    
    echo '<div class="success">✅ servers.db created with 4 servers!</div>';
    $results['servers.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['servers.db'] = 'error';
}

echo '</div>';

// ============================================
// SUMMARY
// ============================================
// DATABASE 4: BILLING.DB
// ============================================

try {
    echo '<div class="database"><h2>💳 Creating billing.db...</h2>';
    
    if (file_exists(DB_BILLING)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_BILLING);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create subscriptions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
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
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id)");
    $db->exec("CREATE INDEX idx_subscriptions_status ON subscriptions(status)");
    $db->exec("CREATE INDEX idx_subscriptions_paypal_id ON subscriptions(paypal_subscription_id)");
    
    // Create transactions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS transactions (
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
            completed_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
        )
    ");
    
    $db->exec("CREATE INDEX idx_transactions_user_id ON transactions(user_id)");
    $db->exec("CREATE INDEX idx_transactions_status ON transactions(status)");
    $db->exec("CREATE INDEX idx_transactions_paypal_id ON transactions(paypal_transaction_id)");
    
    // Create invoices table
    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            invoice_number TEXT NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT NOT NULL DEFAULT 'USD',
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'failed', 'cancelled')),
            due_date DATETIME,
            paid_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
        )
    ");
    
    $db->exec("CREATE INDEX idx_invoices_user_id ON invoices(user_id)");
    $db->exec("CREATE INDEX idx_invoices_status ON invoices(status)");
    
    // Create payment methods table
    $db->exec("
        CREATE TABLE IF NOT EXISTS payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            paypal_payer_id TEXT,
            paypal_email TEXT,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $db->close();
    
    echo '<div class="success">✅ billing.db created successfully!</div>';
    $results['billing.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['billing.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 5: PORT_FORWARDS.DB
// ============================================

try {
    echo '<div class="database"><h2>🔌 Creating port_forwards.db...</h2>';
    
    if (file_exists(DB_PORT_FORWARDS)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_PORT_FORWARDS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create port forwards table
    $db->exec("
        CREATE TABLE IF NOT EXISTS port_forwards (
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            UNIQUE(user_id, external_port)
        )
    ");
    
    $db->exec("CREATE INDEX idx_port_forwards_user_id ON port_forwards(user_id)");
    $db->exec("CREATE INDEX idx_port_forwards_device_id ON port_forwards(device_id)");
    
    // Create discovered devices table (from network scanner)
    $db->exec("
        CREATE TABLE IF NOT EXISTS discovered_devices (
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, device_id)
        )
    ");
    
    $db->exec("CREATE INDEX idx_discovered_devices_user_id ON discovered_devices(user_id)");
    
    $db->close();
    
    echo '<div class="success">✅ port_forwards.db created successfully!</div>';
    $results['port_forwards.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['port_forwards.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 6: PARENTAL_CONTROLS.DB
// ============================================

try {
    echo '<div class="database"><h2>👨‍👩‍👧‍👦 Creating parental_controls.db...</h2>';
    
    if (file_exists(DB_PARENTAL_CONTROLS)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_PARENTAL_CONTROLS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create parental control rules table
    $db->exec("
        CREATE TABLE IF NOT EXISTS parental_rules (
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
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_parental_rules_user_id ON parental_rules(user_id)");
    $db->exec("CREATE INDEX idx_parental_rules_device_id ON parental_rules(device_id)");
    
    // Create blocked requests log
    $db->exec("
        CREATE TABLE IF NOT EXISTS blocked_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            rule_id INTEGER NOT NULL,
            blocked_domain TEXT NOT NULL,
            reason TEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
            FOREIGN KEY (rule_id) REFERENCES parental_rules(id) ON DELETE CASCADE
        )
    ");
    
    $db->exec("CREATE INDEX idx_blocked_requests_user_id ON blocked_requests(user_id)");
    $db->exec("CREATE INDEX idx_blocked_requests_timestamp ON blocked_requests(timestamp)");
    
    // Create website categories table
    $db->exec("
        CREATE TABLE IF NOT EXISTS website_categories (
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
        ['Shopping', 'E-commerce and shopping sites', 0]
    ];
    
    $stmt = $db->prepare("INSERT INTO website_categories (category_name, description, default_blocked) VALUES (:cat, :desc, :blocked)");
    foreach ($categories as $cat) {
        $stmt->bindValue(':cat', $cat[0], SQLITE3_TEXT);
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
    $results['parental_controls.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 7: ADMIN.DB
// ============================================

try {
    echo '<div class="database"><h2>🔐 Creating admin.db...</h2>';
    
    if (file_exists(DB_ADMIN)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_ADMIN);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create admin users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
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
    
    // Insert default admin (owner) - database-driven credentials
    $password_hash = password_hash('Asasasas4!', PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO admin_users (email, password_hash, full_name, role, status)
        VALUES (:email, :pass, :name, :role, :status)
    ");
    $stmt->bindValue(':email', 'paulhalonen@gmail.com', SQLITE3_TEXT);
    $stmt->bindValue(':pass', $password_hash, SQLITE3_TEXT);
    $stmt->bindValue(':name', 'Paul Halonen (Owner)', SQLITE3_TEXT);
    $stmt->bindValue(':role', 'super_admin', SQLITE3_TEXT);
    $stmt->bindValue(':status', 'active', SQLITE3_TEXT);
    $stmt->execute();
    
    // Create system settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
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
        ['paypal_client_id', 'YOUR_PAYPAL_CLIENT_ID', 'string', 'PayPal Client ID', 1],
        ['paypal_secret', 'YOUR_PAYPAL_SECRET', 'string', 'PayPal Secret Key', 1],
        ['paypal_mode', 'live', 'string', 'PayPal mode (sandbox/live)', 1],
        ['email_from', 'noreply@vpn.the-truth-publishing.com', 'string', 'From email address', 1],
        ['email_from_name', 'TrueVault VPN', 'string', 'From name for emails', 1],
        ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', 1],
        ['registration_enabled', 'true', 'boolean', 'Allow new registrations', 1],
        ['vip_secret_list', '[]', 'json', 'List of VIP emails (JSON array)', 1]
    ];
    
    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, editable) VALUES (:key, :val, :type, :desc, :edit)");
    foreach ($settings as $setting) {
        $stmt->bindValue(':key', $setting[0], SQLITE3_TEXT);
        $stmt->bindValue(':val', $setting[1], SQLITE3_TEXT);
        $stmt->bindValue(':type', $setting[2], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $setting[3], SQLITE3_TEXT);
        $stmt->bindValue(':edit', $setting[4], SQLITE3_INTEGER);
        $stmt->execute();
        $stmt->reset();
    }
    
    // Create VIP list table
    $db->exec("
        CREATE TABLE IF NOT EXISTS vip_list (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            notes TEXT,
            added_by TEXT,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Add the two known VIPs - database-driven list
    $stmt = $db->prepare("INSERT INTO vip_list (email, notes, added_by) VALUES (:email, :notes, :by)");
    
    $stmt->bindValue(':email', 'paulhalonen@gmail.com', SQLITE3_TEXT);
    $stmt->bindValue(':notes', 'Owner', SQLITE3_TEXT);
    $stmt->bindValue(':by', 'system', SQLITE3_TEXT);
    $stmt->execute();
    $stmt->reset();
    
    $stmt->bindValue(':email', 'seige235@yahoo.com', SQLITE3_TEXT);
    $stmt->bindValue(':notes', 'Dedicated St. Louis server', SQLITE3_TEXT);
    $stmt->bindValue(':by', 'system', SQLITE3_TEXT);
    $stmt->execute();
    
    $db->close();
    
    echo '<div class="success">✅ admin.db created with default settings and VIP list!</div>';
    $results['admin.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['admin.db'] = 'error';
}

echo '</div>';

// ============================================
// DATABASE 8: LOGS.DB
// ============================================

try {
    echo '<div class="database"><h2>📊 Creating logs.db...</h2>';
    
    if (file_exists(DB_LOGS)) {
        throw new Exception('Database already exists!');
    }
    
    $db = new SQLite3(DB_LOGS);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create security events log
    $db->exec("
        CREATE TABLE IF NOT EXISTS security_events (
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
    
    // Create audit log
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
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
    
    // Create API requests log (for rate limiting and monitoring)
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            endpoint TEXT NOT NULL,
            method TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            response_code INTEGER,
            response_time_ms INTEGER,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX idx_api_requests_user_id ON api_requests(user_id)");
    $db->exec("CREATE INDEX idx_api_requests_timestamp ON api_requests(timestamp)");
    
    // Create error log
    $db->exec("
        CREATE TABLE IF NOT EXISTS error_log (
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
    
    $db->close();
    
    echo '<div class="success">✅ logs.db created successfully!</div>';
    $results['logs.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $results['logs.db'] = 'error';
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
    $icon = $status === 'success' ? '✅' : '❌';
    echo "<li>$icon $db - $status</li>";
}
echo '</ul>';

$success_count = count(array_filter($results, function($status) { return $status === 'success'; }));
$total_count = count($results);

if ($success_count === $total_count) {
    echo '<div class="success">';
    echo '<h3>🎊 All 8 databases created successfully!</h3>';
    echo '<p><strong>Admin Credentials:</strong></p>';
    echo '<ul>';
    echo '<li>Email: paulhalonen@gmail.com</li>';
    echo '<li>Password: [Set securely]</li>';
    echo '<li>Role: Super Admin</li>';
    echo '</ul>';
    echo '<p><strong>Next Steps:</strong></p>';
    echo '<ol>';
    echo '<li>Update server public keys in servers.db</li>';
    echo '<li>Update PayPal credentials in admin.db (system_settings table)</li>';
    echo '<li>Change JWT secret in config.php</li>';
    echo '<li>Start building authentication system (Part 3)</li>';
    echo '</ol>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<p>Some databases failed to create. Please review errors above and try again.</p>';
    echo '</div>';
}

echo '</div>';

?>

</div>
</body>
</html>
