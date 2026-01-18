<?php
/**
 * TrueVault VPN - FIXED Database Setup Script
 * 
 * THIS IS THE CORRECTED VERSION that matches what the API code expects.
 * Run: https://vpn.the-truth-publishing.com/admin/setup-databases-FIXED.php
 * 
 * @version 2.0.1 - FIXED with correct admin password
 * @created January 17, 2026
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/../databases/';

// Create databases directory
if (!is_dir($dbPath)) {
    mkdir($dbPath, 0755, true);
}

$results = [];
$errors = [];

// ============================================
// HELPER: Delete old database file if exists
// ============================================
function resetDb($path) {
    if (file_exists($path)) {
        unlink($path);
    }
}

// ============================================
// 1. MAIN DATABASE (users, sessions, vip_users)
// ============================================
try {
    resetDb($dbPath . 'main.db');
    $db = new SQLite3($dbPath . 'main.db');
    $db->enableExceptions(true);
    
    // Users table - matches Auth.php expectations
    $db->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        first_name TEXT DEFAULT '',
        last_name TEXT DEFAULT '',
        phone TEXT DEFAULT '',
        account_type TEXT DEFAULT 'standard',
        plan TEXT DEFAULT 'free',
        status TEXT DEFAULT 'pending',
        max_devices INTEGER DEFAULT 3,
        email_verified INTEGER DEFAULT 0,
        verification_token TEXT,
        reset_token TEXT,
        reset_token_expires DATETIME,
        trial_ends_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");
    
    // Sessions table
    $db->exec("CREATE TABLE sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_valid INTEGER DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // VIP users table - THIS IS WHAT THE CODE EXPECTS
    $db->exec("CREATE TABLE vip_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        notes TEXT DEFAULT '',
        max_devices INTEGER DEFAULT 999,
        dedicated_server_id INTEGER,
        added_by TEXT DEFAULT 'admin',
        is_active INTEGER DEFAULT 1,
        added_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Settings table for admin
    $db->exec("CREATE TABLE settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert VIP user (seige235@yahoo.com with dedicated server 2)
    $db->exec("INSERT INTO vip_users (email, notes, max_devices, dedicated_server_id, added_by) VALUES 
        ('seige235@yahoo.com', 'Dedicated St. Louis server owner', 999, 2, 'system')");
    
    // Insert admin email setting
    $db->exec("INSERT INTO settings (setting_key, setting_value, description) VALUES 
        ('admin_email', 'paulhalonen@gmail.com', 'Admin email address'),
        ('site_name', 'TrueVault VPN', 'Site name'),
        ('support_email', 'paulhalonen@gmail.com', 'Support email')");
    
    // Add paulhalonen to VIP list for admin access
    $db->exec("INSERT INTO vip_users (email, notes, max_devices, added_by) VALUES 
        ('paulhalonen@gmail.com', 'Site owner/admin', 999, 'system')");
    
    $db->exec("CREATE INDEX idx_users_email ON users(email)");
    $db->exec("CREATE INDEX idx_sessions_token ON sessions(token)");
    $db->exec("CREATE INDEX idx_vip_email ON vip_users(email)");
    
    $db->close();
    $results[] = "main.db - users, sessions, vip_users, settings";
} catch (Exception $e) {
    $errors[] = "main.db: " . $e->getMessage();
}

// ============================================
// 2. DEVICES DATABASE
// ============================================
try {
    resetDb($dbPath . 'devices.db');
    $db = new SQLite3($dbPath . 'devices.db');
    $db->enableExceptions(true);
    
    // Devices table - matches API expectations
    $db->exec("CREATE TABLE devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        device_type TEXT DEFAULT 'computer',
        public_key TEXT NOT NULL,
        private_key_encrypted TEXT,
        preshared_key TEXT,
        assigned_ip TEXT,
        server_id INTEGER,
        status TEXT DEFAULT 'active',
        is_online INTEGER DEFAULT 0,
        last_handshake DATETIME,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX idx_devices_user ON devices(user_id)");
    $db->exec("CREATE INDEX idx_devices_pubkey ON devices(public_key)");
    $db->exec("CREATE INDEX idx_devices_server ON devices(server_id)");
    
    $db->close();
    $results[] = "devices.db - devices";
} catch (Exception $e) {
    $errors[] = "devices.db: " . $e->getMessage();
}

// ============================================
// 3. SERVERS DATABASE - CRITICAL FIX
// ============================================
try {
    resetDb($dbPath . 'servers.db');
    $db = new SQLite3($dbPath . 'servers.db');
    $db->enableExceptions(true);
    
    // Servers table - matches what list.php and stats.php expect
    $db->exec("CREATE TABLE servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        display_name TEXT NOT NULL,
        location TEXT NOT NULL,
        country TEXT DEFAULT 'US',
        country_code TEXT DEFAULT 'US',
        ip_address TEXT NOT NULL,
        port INTEGER DEFAULT 51820,
        public_key TEXT,
        endpoint TEXT,
        dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
        allowed_ips TEXT DEFAULT '0.0.0.0/0',
        status TEXT DEFAULT 'active',
        is_vip_only INTEGER DEFAULT 0,
        vip_only INTEGER DEFAULT 0,
        dedicated_user_email TEXT,
        max_clients INTEGER DEFAULT 100,
        current_clients INTEGER DEFAULT 0,
        priority INTEGER DEFAULT 10,
        provider TEXT,
        notes TEXT,
        last_check DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert the 4 servers with CORRECT schema
    $db->exec("INSERT INTO servers (id, name, display_name, location, country, country_code, ip_address, port, endpoint, status, is_vip_only, vip_only, max_clients, priority, provider, dedicated_user_email) VALUES 
        (1, 'us-east', 'US East', 'New York, USA', 'US', 'US', '66.94.103.91', 51820, '66.94.103.91:51820', 'active', 0, 0, 100, 1, 'Contabo', NULL),
        (2, 'us-central', 'US Central (VIP)', 'St. Louis, USA', 'US', 'US', '144.126.133.253', 51820, '144.126.133.253:51820', 'active', 1, 1, 1, 2, 'Contabo', 'seige235@yahoo.com'),
        (3, 'us-south', 'US South', 'Dallas, USA', 'US', 'US', '66.241.124.4', 51820, '66.241.124.4:51820', 'active', 0, 0, 100, 3, 'Fly.io', NULL),
        (4, 'canada', 'Canada', 'Toronto, Canada', 'CA', 'CA', '66.241.125.247', 51820, '66.241.125.247:51820', 'active', 0, 0, 100, 4, 'Fly.io', NULL)
    ");
    
    $db->exec("CREATE INDEX idx_servers_status ON servers(status)");
    
    $db->close();
    $results[] = "servers.db - servers (4 servers with CORRECT schema)";
} catch (Exception $e) {
    $errors[] = "servers.db: " . $e->getMessage();
}

// ============================================
// 4. BILLING DATABASE
// ============================================
try {
    resetDb($dbPath . 'billing.db');
    $db = new SQLite3($dbPath . 'billing.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        paypal_subscription_id TEXT,
        plan TEXT NOT NULL,
        status TEXT DEFAULT 'pending',
        amount REAL,
        currency TEXT DEFAULT 'USD',
        billing_cycle TEXT DEFAULT 'monthly',
        started_at DATETIME,
        expires_at DATETIME,
        cancelled_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Payments table - this is what stats.php expects
    $db->exec("CREATE TABLE payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subscription_id INTEGER,
        paypal_payment_id TEXT,
        paypal_payer_id TEXT,
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'USD',
        status TEXT DEFAULT 'completed',
        payment_method TEXT DEFAULT 'paypal',
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subscription_id INTEGER,
        invoice_number TEXT UNIQUE,
        amount REAL NOT NULL,
        tax REAL DEFAULT 0,
        total REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        due_date DATE,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX idx_subs_user ON subscriptions(user_id)");
    $db->exec("CREATE INDEX idx_payments_user ON payments(user_id)");
    $db->exec("CREATE INDEX idx_payments_status ON payments(status)");
    
    $db->close();
    $results[] = "billing.db - subscriptions, payments, invoices";
} catch (Exception $e) {
    $errors[] = "billing.db: " . $e->getMessage();
}

// ============================================
// 5. ADMIN DATABASE - WITH CORRECT PASSWORD
// ============================================
try {
    resetDb($dbPath . 'admin.db');
    $db = new SQLite3($dbPath . 'admin.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT,
        role TEXT DEFAULT 'admin',
        is_active INTEGER DEFAULT 1,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE system_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type TEXT DEFAULT 'text',
        category TEXT DEFAULT 'general',
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE email_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        variables TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE theme_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        value TEXT,
        category TEXT DEFAULT 'colors',
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // CORRECT PASSWORD: Athena8 for paulhalonen@gmail.com
    $adminPass = password_hash('Athena8', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO admin_users (email, password_hash, name, role) VALUES (?, ?, ?, ?)");
    $stmt->bindValue(1, 'paulhalonen@gmail.com', SQLITE3_TEXT);
    $stmt->bindValue(2, $adminPass, SQLITE3_TEXT);
    $stmt->bindValue(3, 'Paul Halonen', SQLITE3_TEXT);
    $stmt->bindValue(4, 'superadmin', SQLITE3_TEXT);
    $stmt->execute();
    
    // System settings
    $db->exec("INSERT INTO system_settings (setting_key, setting_value, category, description) VALUES 
        ('site_name', 'TrueVault VPN', 'general', 'Website name'),
        ('support_email', 'paulhalonen@gmail.com', 'general', 'Support email'),
        ('paypal_mode', 'live', 'billing', 'PayPal mode'),
        ('price_personal', '9.97', 'billing', 'Personal plan price'),
        ('price_family', '14.97', 'billing', 'Family plan price'),
        ('price_dedicated', '39.97', 'billing', 'Dedicated plan price'),
        ('max_devices_personal', '3', 'limits', 'Max devices personal'),
        ('max_devices_family', '10', 'limits', 'Max devices family'),
        ('max_devices_dedicated', '25', 'limits', 'Max devices dedicated')");
    
    // Theme settings
    $db->exec("INSERT INTO theme_settings (name, value, category, description) VALUES 
        ('primary_color', '#00d9ff', 'colors', 'Primary accent color'),
        ('secondary_color', '#00ff88', 'colors', 'Secondary accent color'),
        ('background_color', '#0f0f1a', 'colors', 'Background color'),
        ('background_secondary', '#1a1a2e', 'colors', 'Secondary background'),
        ('text_color', '#ffffff', 'colors', 'Main text color'),
        ('text_muted', '#888888', 'colors', 'Muted text color'),
        ('success_color', '#00ff88', 'colors', 'Success color'),
        ('error_color', '#ff6464', 'colors', 'Error color')");
    
    $db->close();
    $results[] = "admin.db - admin_users (with password Athena8), system_settings, email_templates, theme_settings";
} catch (Exception $e) {
    $errors[] = "admin.db: " . $e->getMessage();
}

// ============================================
// 6. LOGS DATABASE
// ============================================
try {
    resetDb($dbPath . 'logs.db');
    $db = new SQLite3($dbPath . 'logs.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        entity_type TEXT,
        entity_id INTEGER,
        details TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE security_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        event_type TEXT NOT NULL,
        severity TEXT DEFAULT 'low',
        ip_address TEXT,
        user_agent TEXT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        to_email TEXT NOT NULL,
        subject TEXT,
        template TEXT,
        status TEXT DEFAULT 'sent',
        error TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE email_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        to_email TEXT NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        priority INTEGER DEFAULT 5,
        attempts INTEGER DEFAULT 0,
        status TEXT DEFAULT 'pending',
        scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME,
        error TEXT
    )");
    
    $db->exec("CREATE TABLE scheduled_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_type TEXT NOT NULL,
        task_data TEXT,
        execute_at DATETIME NOT NULL,
        status TEXT DEFAULT 'pending',
        attempts INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        executed_at DATETIME
    )");
    
    $db->exec("CREATE INDEX idx_activity_user ON activity_logs(user_id)");
    $db->exec("CREATE INDEX idx_activity_action ON activity_logs(action)");
    
    $db->close();
    $results[] = "logs.db - activity_logs, security_events, email_log, email_queue, scheduled_tasks";
} catch (Exception $e) {
    $errors[] = "logs.db: " . $e->getMessage();
}

// ============================================
// 7. PORT FORWARDS DATABASE
// ============================================
try {
    resetDb($dbPath . 'port_forwards.db');
    $db = new SQLite3($dbPath . 'port_forwards.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE port_forwards (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id INTEGER,
        name TEXT NOT NULL,
        internal_ip TEXT NOT NULL,
        internal_port INTEGER NOT NULL,
        external_port INTEGER NOT NULL,
        protocol TEXT DEFAULT 'tcp',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE discovered_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ip_address TEXT NOT NULL,
        mac_address TEXT,
        hostname TEXT,
        vendor TEXT,
        device_type TEXT,
        icon TEXT,
        open_ports TEXT,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->close();
    $results[] = "port_forwards.db - port_forwards, discovered_devices";
} catch (Exception $e) {
    $errors[] = "port_forwards.db: " . $e->getMessage();
}

// ============================================
// 8. SUPPORT DATABASE
// ============================================
try {
    resetDb($dbPath . 'support.db');
    $db = new SQLite3($dbPath . 'support.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE support_tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ticket_number TEXT UNIQUE,
        subject TEXT NOT NULL,
        category TEXT DEFAULT 'general',
        priority TEXT DEFAULT 'normal',
        status TEXT DEFAULT 'open',
        assigned_to INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        closed_at DATETIME
    )");
    
    $db->exec("CREATE TABLE ticket_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL,
        user_id INTEGER,
        is_staff INTEGER DEFAULT 0,
        message TEXT NOT NULL,
        attachments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE knowledge_base (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT UNIQUE,
        content TEXT NOT NULL,
        category TEXT,
        tags TEXT,
        views INTEGER DEFAULT 0,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->close();
    $results[] = "support.db - support_tickets, ticket_messages, knowledge_base";
} catch (Exception $e) {
    $errors[] = "support.db: " . $e->getMessage();
}

// ============================================
// SECURE THE DATABASES
// ============================================
$htaccess = "Order deny,allow\nDeny from all";
file_put_contents($dbPath . '.htaccess', $htaccess);

// Output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup FIXED - TrueVault VPN</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; padding: 40px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #00d9ff; }
        .success { color: #00ff88; padding: 8px 0; }
        .error { color: #ff5050; padding: 8px 0; }
        .box { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 20px; margin: 20px 0; }
        .warning { background: rgba(255,170,0,0.15); border: 1px solid #ffaa00; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .warning h3 { color: #ffaa00; margin-top: 0; }
        .next { background: rgba(0,217,255,0.1); border: 1px solid rgba(0,217,255,0.3); border-radius: 10px; padding: 20px; margin-top: 30px; }
        .next h3 { color: #00d9ff; margin-top: 0; }
        a { color: #00d9ff; }
        code { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <h1>TrueVault Database Setup - FIXED VERSION</h1>
        
        <div class="warning">
            <h3>This script REPLACES all databases!</h3>
            <p>All existing data has been deleted and recreated with the CORRECT schema that matches the API code.</p>
        </div>
        
        <div class="box">
            <h2>Results</h2>
            <?php foreach ($results as $r): ?>
                <p class="success">OK: <?= $r ?></p>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $e): ?>
                <p class="error">ERROR: <?= $e ?></p>
            <?php endforeach; ?>
        </div>
        
        <div class="box">
            <h2>Summary</h2>
            <p><strong>Databases created:</strong> <?= count($results) ?></p>
            <p><strong>Errors:</strong> <?= count($errors) ?></p>
            <p><strong>Location:</strong> <code><?= realpath($dbPath) ?: $dbPath ?></code></p>
        </div>
        
        <div class="box">
            <h2>Server Configuration</h2>
            <table>
                <tr><th>ID</th><th>Name</th><th>Location</th><th>IP</th><th>Type</th></tr>
                <tr><td>1</td><td>US East</td><td>New York</td><td><code>66.94.103.91</code></td><td>Shared</td></tr>
                <tr><td>2</td><td>US Central</td><td>St. Louis</td><td><code>144.126.133.253</code></td><td>VIP (seige235@yahoo.com)</td></tr>
                <tr><td>3</td><td>US South</td><td>Dallas</td><td><code>66.241.124.4</code></td><td>Shared</td></tr>
                <tr><td>4</td><td>Canada</td><td>Toronto</td><td><code>66.241.125.247</code></td><td>Shared</td></tr>
            </table>
        </div>
        
        <?php if (count($errors) === 0): ?>
        <div class="next">
            <h3>Setup Complete!</h3>
            <p><strong>Admin Login Credentials:</strong></p>
            <ul>
                <li>URL: <a href="index.html">/admin/index.html</a></li>
                <li>Email: <code>paulhalonen@gmail.com</code></li>
                <li>Password: <code>Athena8</code></li>
            </ul>
            <p><strong>User Dashboard:</strong></p>
            <ul>
                <li>Register: <a href="../register.html">/register.html</a></li>
                <li>Login: <a href="../login.html">/login.html</a></li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
