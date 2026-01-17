<?php
/**
 * TrueVault VPN - Database Setup Script
 * Creates all required SQLite3 databases and tables
 * Run once: https://vpn.the-truth-publishing.com/admin/setup-databases.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent re-running
$lockFile = __DIR__ . '/../databases/.setup_complete';

$dbPath = __DIR__ . '/../databases/';

// Create databases directory
if (!is_dir($dbPath)) {
    mkdir($dbPath, 0755, true);
}

$results = [];
$errors = [];

// ============================================
// 1. MAIN DATABASE (users, sessions, vip_list)
// ============================================
try {
    $db = new SQLite3($dbPath . 'main.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        first_name TEXT,
        last_name TEXT,
        phone TEXT,
        plan TEXT DEFAULT 'free',
        status TEXT DEFAULT 'active',
        is_vip INTEGER DEFAULT 0,
        vip_approved_at DATETIME,
        max_devices INTEGER DEFAULT 3,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS vip_list (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        added_by TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add default VIP
    $db->exec("INSERT OR IGNORE INTO vip_list (email, added_by, notes) VALUES ('seige235@yahoo.com', 'system', 'Dedicated server user')");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)");
    
    $db->close();
    $results[] = "✅ main.db - users, sessions, vip_list";
} catch (Exception $e) {
    $errors[] = "❌ main.db: " . $e->getMessage();
}

// ============================================
// 2. DEVICES DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'devices.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'computer',
        public_key TEXT NOT NULL,
        private_key TEXT,
        assigned_ip TEXT,
        server_id INTEGER,
        is_active INTEGER DEFAULT 1,
        last_connected DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_user ON devices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_pubkey ON devices(public_key)");
    
    $db->close();
    $results[] = "✅ devices.db - devices";
} catch (Exception $e) {
    $errors[] = "❌ devices.db: " . $e->getMessage();
}

// ============================================
// 3. SERVERS DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'servers.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        location TEXT NOT NULL,
        country TEXT DEFAULT 'US',
        ip TEXT NOT NULL,
        port INTEGER DEFAULT 51820,
        public_key TEXT,
        endpoint TEXT,
        dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
        allowed_ips TEXT DEFAULT '0.0.0.0/0',
        type TEXT DEFAULT 'shared',
        is_active INTEGER DEFAULT 1,
        max_users INTEGER DEFAULT 100,
        current_users INTEGER DEFAULT 0,
        last_check DATETIME,
        last_status TEXT DEFAULT 'unknown',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default servers
    $db->exec("INSERT OR IGNORE INTO servers (id, name, location, country, ip, port, type, max_users) VALUES 
        (1, 'US East', 'New York, USA', 'US', '66.94.103.91', 51820, 'shared', 100),
        (2, 'US Central', 'St. Louis, USA', 'US', '144.126.133.253', 51820, 'dedicated', 1),
        (3, 'US South', 'Dallas, USA', 'US', '66.241.124.4', 51820, 'shared', 100),
        (4, 'Canada', 'Toronto, Canada', 'CA', '66.241.125.247', 51820, 'shared', 100)
    ");
    
    $db->close();
    $results[] = "✅ servers.db - servers (4 default servers added)";
} catch (Exception $e) {
    $errors[] = "❌ servers.db: " . $e->getMessage();
}

// ============================================
// 4. BILLING DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'billing.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subscription_id INTEGER,
        paypal_transaction_id TEXT,
        type TEXT DEFAULT 'payment',
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'USD',
        status TEXT DEFAULT 'completed',
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        invoice_number TEXT UNIQUE,
        amount REAL NOT NULL,
        tax REAL DEFAULT 0,
        total REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        due_date DATE,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subs_user ON subscriptions(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_trans_user ON transactions(user_id)");
    
    $db->close();
    $results[] = "✅ billing.db - subscriptions, transactions, invoices";
} catch (Exception $e) {
    $errors[] = "❌ billing.db: " . $e->getMessage();
}

// ============================================
// 5. ADMIN DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'admin.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        name TEXT,
        role TEXT DEFAULT 'admin',
        is_active INTEGER DEFAULT 1,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        variables TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Default admin (change password!)
    $defaultPass = password_hash('TrueVault2026!', PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO admin_users (email, password, name, role) VALUES ('admin@truevault.com', '$defaultPass', 'Administrator', 'superadmin')");
    
    // Default settings
    $db->exec("INSERT OR IGNORE INTO system_settings (setting_key, setting_value, description) VALUES 
        ('site_name', 'TrueVault VPN', 'Website name'),
        ('support_email', 'paulhalonen@gmail.com', 'Support email address'),
        ('paypal_mode', 'sandbox', 'PayPal mode: sandbox or live'),
        ('max_devices_free', '1', 'Max devices for free users'),
        ('max_devices_personal', '3', 'Max devices for personal plan'),
        ('max_devices_family', '10', 'Max devices for family plan')
    ");
    
    $db->close();
    $results[] = "✅ admin.db - admin_users, system_settings, email_templates";
} catch (Exception $e) {
    $errors[] = "❌ admin.db: " . $e->getMessage();
}

// ============================================
// 6. LOGS DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'logs.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS security_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        event_type TEXT NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        table_name TEXT,
        record_id INTEGER,
        old_values TEXT,
        new_values TEXT,
        ip_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS email_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        to_email TEXT NOT NULL,
        subject TEXT,
        template TEXT,
        status TEXT DEFAULT 'sent',
        error TEXT,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS email_queue (
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
    
    $db->exec("CREATE TABLE IF NOT EXISTS workflow_executions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        workflow_name TEXT NOT NULL,
        trigger_data TEXT,
        status TEXT DEFAULT 'running',
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        error TEXT
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_type TEXT NOT NULL,
        task_data TEXT,
        execute_at DATETIME NOT NULL,
        status TEXT DEFAULT 'pending',
        attempts INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        executed_at DATETIME
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS cron_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_name TEXT NOT NULL,
        status TEXT,
        duration_ms INTEGER,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_security_user ON security_events(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_status ON scheduled_tasks(status, execute_at)");
    
    $db->close();
    $results[] = "✅ logs.db - security_events, audit_log, email_log, email_queue, workflow_executions, scheduled_tasks, cron_log";
} catch (Exception $e) {
    $errors[] = "❌ logs.db: " . $e->getMessage();
}

// ============================================
// 7. PORT FORWARDS DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'port_forwards.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS port_forwards (
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
    
    $db->exec("CREATE TABLE IF NOT EXISTS discovered_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        ip TEXT NOT NULL,
        mac TEXT,
        hostname TEXT,
        vendor TEXT,
        device_type TEXT,
        open_ports TEXT,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->close();
    $results[] = "✅ port_forwards.db - port_forwards, discovered_devices";
} catch (Exception $e) {
    $errors[] = "❌ port_forwards.db: " . $e->getMessage();
}

// ============================================
// 8. SUPPORT DATABASE
// ============================================
try {
    $db = new SQLite3($dbPath . 'support.db');
    $db->enableExceptions(true);
    
    $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
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
    
    $db->exec("CREATE TABLE IF NOT EXISTS ticket_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL,
        user_id INTEGER,
        is_staff INTEGER DEFAULT 0,
        message TEXT NOT NULL,
        attachments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS knowledge_base (
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
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_user ON support_tickets(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_status ON support_tickets(status)");
    
    $db->close();
    $results[] = "✅ support.db - support_tickets, ticket_messages, knowledge_base";
} catch (Exception $e) {
    $errors[] = "❌ support.db: " . $e->getMessage();
}

// Create lock file
file_put_contents($lockFile, date('Y-m-d H:i:s'));

// Output HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - TrueVault VPN</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #00d9ff; }
        .success { color: #00ff88; }
        .error { color: #ff5050; }
        .box { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 20px; margin: 20px 0; }
        .next-steps { background: rgba(0,217,255,0.1); border: 1px solid rgba(0,217,255,0.3); border-radius: 10px; padding: 20px; margin-top: 30px; }
        .next-steps h3 { color: #00d9ff; margin-top: 0; }
        a { color: #00d9ff; }
        code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ TrueVault Database Setup</h1>
        
        <div class="box">
            <h2>Results</h2>
            <?php foreach ($results as $r): ?>
                <p class="success"><?= $r ?></p>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $e): ?>
                <p class="error"><?= $e ?></p>
            <?php endforeach; ?>
        </div>
        
        <div class="box">
            <h2>📊 Summary</h2>
            <p><strong>Databases created:</strong> <?= count($results) ?></p>
            <p><strong>Errors:</strong> <?= count($errors) ?></p>
            <p><strong>Location:</strong> <code><?= realpath($dbPath) ?: $dbPath ?></code></p>
        </div>
        
        <?php if (count($errors) === 0): ?>
        <div class="next-steps">
            <h3>✅ Setup Complete! Next Steps:</h3>
            <ol>
                <li><a href="install-email-templates.php">Install Email Templates</a></li>
                <li><a href="index.html">Go to Admin Dashboard</a></li>
                <li>Test user registration at <a href="../register.html">/register.html</a></li>
            </ol>
            <p><strong>Default Admin Login:</strong></p>
            <ul>
                <li>Email: <code>admin@truevault.com</code></li>
                <li>Password: <code>TrueVault2026!</code></li>
            </ul>
            <p style="color: #ffc107;">⚠️ Change this password immediately!</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
