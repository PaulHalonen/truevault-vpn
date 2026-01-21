<?php
/**
 * TrueVault VPN - Database Setup Script
 * 
 * PURPOSE: Creates all SQLite databases with proper schemas
 * RUN ONCE: Only during initial setup
 * DATABASE: Uses SQLite3 class (NOT PDO)
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Prevent running in production
if (ENVIRONMENT === 'production' && !isset($_GET['confirm'])) {
    die('Add ?confirm=yes to run in production');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault VPN - Database Setup</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { background: #16213e; padding: 30px; border-radius: 10px; }
        h1 { color: #00d9ff; }
        .success { background: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ TrueVault VPN - Database Setup</h1>
<?php

$results = [];

// ============================================
// DATABASE 1: USERS.DB
// ============================================
try {
    echo '<h2>Creating users.db...</h2>';
    
    if (file_exists(DB_USERS)) {
        echo '<div class="info">⚠️ users.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_USERS);
        
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            tier TEXT DEFAULT 'standard',
            status TEXT DEFAULT 'active',
            email_verified INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            login_attempts INTEGER DEFAULT 0,
            locked_until DATETIME,
            vip_approved INTEGER DEFAULT 0,
            notes TEXT
        )");
        
        $db->exec("CREATE INDEX idx_users_email ON users(email)");
        $db->exec("CREATE INDEX idx_users_tier ON users(tier)");
        
        $db->exec("CREATE TABLE sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            session_token TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL
        )");
        
        $db->close();
        echo '<div class="success">✅ users.db created!</div>';
    }
    $results['users.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['users.db'] = 'error';
}

// ============================================
// DATABASE 2: DEVICES.DB
// ============================================
try {
    echo '<h2>Creating devices.db...</h2>';
    
    if (file_exists(DB_DEVICES)) {
        echo '<div class="info">⚠️ devices.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_DEVICES);
        
        $db->exec("CREATE TABLE devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_name TEXT NOT NULL,
            device_type TEXT,
            public_key TEXT NOT NULL UNIQUE,
            private_key_encrypted TEXT NOT NULL,
            ipv4_address TEXT NOT NULL UNIQUE,
            current_server_id INTEGER,
            status TEXT DEFAULT 'active',
            last_handshake DATETIME,
            data_sent_bytes INTEGER DEFAULT 0,
            data_received_bytes INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE INDEX idx_devices_user ON devices(user_id)");
        $db->exec("CREATE INDEX idx_devices_pubkey ON devices(public_key)");
        
        $db->close();
        echo '<div class="success">✅ devices.db created!</div>';
    }
    $results['devices.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['devices.db'] = 'error';
}

// ============================================
// DATABASE 3: SERVERS.DB
// ============================================
try {
    echo '<h2>Creating servers.db...</h2>';
    
    if (file_exists(DB_SERVERS)) {
        echo '<div class="info">⚠️ servers.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_SERVERS);
        
        $db->exec("CREATE TABLE servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL,
            country_code TEXT,
            endpoint TEXT NOT NULL,
            public_key TEXT NOT NULL,
            private_key_encrypted TEXT NOT NULL,
            listen_port INTEGER DEFAULT 51820,
            ip_pool_start TEXT NOT NULL,
            ip_pool_end TEXT NOT NULL,
            max_clients INTEGER DEFAULT 100,
            current_clients INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active',
            vip_only INTEGER DEFAULT 0,
            dedicated_user_email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert the 4 servers
        $db->exec("INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, ip_pool_start, ip_pool_end, vip_only) 
            VALUES ('New York Shared', 'New York, USA', 'US', '66.94.103.91:51820', 'NY_KEY', 'ENCRYPTED', '10.8.0.2', '10.8.0.254', 0)");
        
        $db->exec("INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, ip_pool_start, ip_pool_end, vip_only, dedicated_user_email) 
            VALUES ('St. Louis VIP', 'St. Louis, USA', 'US', '144.126.133.253:51820', 'STL_KEY', 'ENCRYPTED', '10.8.1.2', '10.8.1.254', 1, 'seige235@yahoo.com')");
        
        $db->exec("INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, ip_pool_start, ip_pool_end, vip_only) 
            VALUES ('Dallas Streaming', 'Dallas, USA', 'US', '66.241.124.4:51820', 'DAL_KEY', 'ENCRYPTED', '10.8.2.2', '10.8.2.254', 0)");
        
        $db->exec("INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, ip_pool_start, ip_pool_end, vip_only) 
            VALUES ('Toronto Canada', 'Toronto, Canada', 'CA', '66.241.125.247:51820', 'TOR_KEY', 'ENCRYPTED', '10.8.3.2', '10.8.3.254', 0)");
        
        $db->close();
        echo '<div class="success">✅ servers.db created with 4 servers!</div>';
    }
    $results['servers.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['servers.db'] = 'error';
}

// ============================================
// DATABASE 4: BILLING.DB
// ============================================
try {
    echo '<h2>Creating billing.db...</h2>';
    
    if (file_exists(DB_BILLING)) {
        echo '<div class="info">⚠️ billing.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_BILLING);
        
        $db->exec("CREATE TABLE subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            plan_id TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            paypal_subscription_id TEXT UNIQUE,
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            next_billing_date DATETIME,
            cancelled_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            transaction_type TEXT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency TEXT DEFAULT 'USD',
            paypal_transaction_id TEXT UNIQUE,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            invoice_number TEXT NOT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            status TEXT DEFAULT 'pending',
            due_date DATETIME,
            paid_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->close();
        echo '<div class="success">✅ billing.db created!</div>';
    }
    $results['billing.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['billing.db'] = 'error';
}

// ============================================
// DATABASE 5: PORT_FORWARDS.DB
// ============================================
try {
    echo '<h2>Creating port_forwards.db...</h2>';
    
    if (file_exists(DB_PORT_FORWARDS)) {
        echo '<div class="info">⚠️ port_forwards.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_PORT_FORWARDS);
        
        $db->exec("CREATE TABLE port_forwards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER NOT NULL,
            rule_name TEXT NOT NULL,
            protocol TEXT NOT NULL,
            external_port INTEGER NOT NULL,
            internal_ip TEXT NOT NULL,
            internal_port INTEGER NOT NULL,
            status TEXT DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE discovered_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            ip_address TEXT NOT NULL,
            mac_address TEXT,
            device_name TEXT,
            device_type TEXT,
            manufacturer TEXT,
            discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->close();
        echo '<div class="success">✅ port_forwards.db created!</div>';
    }
    $results['port_forwards.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['port_forwards.db'] = 'error';
}

// ============================================
// DATABASE 6: PARENTAL_CONTROLS.DB
// ============================================
try {
    echo '<h2>Creating parental_controls.db...</h2>';
    
    if (file_exists(DB_PARENTAL)) {
        echo '<div class="info">⚠️ parental_controls.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_PARENTAL);
        
        $db->exec("CREATE TABLE profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            profile_name TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE blocked_sites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id INTEGER NOT NULL,
            domain TEXT NOT NULL,
            category TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            profile_id INTEGER NOT NULL,
            day_of_week INTEGER NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            is_allowed INTEGER DEFAULT 1
        )");
        
        $db->close();
        echo '<div class="success">✅ parental_controls.db created!</div>';
    }
    $results['parental_controls.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['parental_controls.db'] = 'error';
}

// ============================================
// DATABASE 7: ADMIN.DB
// ============================================
try {
    echo '<h2>Creating admin.db...</h2>';
    
    if (file_exists(DB_ADMIN)) {
        echo '<div class="info">⚠️ admin.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_ADMIN);
        
        $db->exec("CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'string',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default settings
        $db->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', 'TrueVault VPN')");
        $db->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('support_email', 'paulhalonen@gmail.com')");
        $db->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_email', 'admin@the-truth-publishing.com')");
        
        $db->close();
        echo '<div class="success">✅ admin.db created!</div>';
    }
    $results['admin.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['admin.db'] = 'error';
}

// ============================================
// DATABASE 8: LOGS.DB
// ============================================
try {
    echo '<h2>Creating logs.db...</h2>';
    
    if (file_exists(DB_LOGS)) {
        echo '<div class="info">⚠️ logs.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_LOGS);
        
        $db->exec("CREATE TABLE activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE connection_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            disconnected_at DATETIME,
            bytes_sent INTEGER DEFAULT 0,
            bytes_received INTEGER DEFAULT 0
        )");
        
        $db->exec("CREATE TABLE error_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            error_type TEXT NOT NULL,
            message TEXT NOT NULL,
            file TEXT,
            line INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->close();
        echo '<div class="success">✅ logs.db created!</div>';
    }
    $results['logs.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['logs.db'] = 'error';
}

// ============================================
// DATABASE 9: THEMES.DB
// ============================================
try {
    echo '<h2>Creating themes.db...</h2>';
    
    if (file_exists(DB_THEMES)) {
        echo '<div class="info">⚠️ themes.db already exists - skipping</div>';
    } else {
        $db = new SQLite3(DB_THEMES);
        
        $db->exec("CREATE TABLE themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            theme_name TEXT NOT NULL UNIQUE,
            theme_slug TEXT NOT NULL UNIQUE,
            category TEXT DEFAULT 'standard',
            primary_color TEXT DEFAULT '#667eea',
            secondary_color TEXT DEFAULT '#764ba2',
            background_color TEXT DEFAULT '#1a1a2e',
            text_color TEXT DEFAULT '#ffffff',
            font_family TEXT DEFAULT 'Inter, sans-serif',
            is_active INTEGER DEFAULT 0,
            is_default INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE theme_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default theme
        $db->exec("INSERT INTO themes (theme_name, theme_slug, category, is_active, is_default) VALUES ('Professional Dark', 'professional-dark', 'standard', 1, 1)");
        $db->exec("INSERT INTO theme_settings (setting_key, setting_value) VALUES ('active_theme', 'professional-dark')");
        
        $db->close();
        echo '<div class="success">✅ themes.db created!</div>';
    }
    $results['themes.db'] = 'success';
} catch (Exception $e) {
    echo '<div class="error">❌ ' . $e->getMessage() . '</div>';
    $results['themes.db'] = 'error';
}

// Summary
echo '<h2>Summary</h2>';
echo '<div class="info"><ul>';
foreach ($results as $db => $status) {
    $icon = $status === 'success' ? '✅' : '❌';
    echo "<li>$icon $db</li>";
}
echo '</ul></div>';
echo '<p><strong>All 9 databases created!</strong> Setup complete.</p>';

?>
</div>
</body>
</html>
