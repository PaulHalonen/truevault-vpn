<?php
/**
 * TrueVault VPN - Complete Database Setup
 * Creates all required tables across all databases
 * 
 * Run once during installation: /admin/setup-databases.php
 */

// Prevent accidental re-runs in production
$lockFile = __DIR__ . '/../databases/.setup_complete';
$forceRun = isset($_GET['force']) && $_GET['force'] === 'yes';

if (file_exists($lockFile) && !$forceRun) {
    die('Database setup already completed. Add ?force=yes to run again.');
}

require_once __DIR__ . '/../includes/Database.php';

$results = [];

// ============================================
// 1. MAIN DATABASE (main.db)
// ============================================

try {
    $db = new Database('main');
    
    // Users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            name TEXT,
            tier TEXT NOT NULL DEFAULT 'free',
            status TEXT NOT NULL DEFAULT 'active',
            email_verified INTEGER DEFAULT 0,
            verification_token TEXT,
            reset_token TEXT,
            reset_expires DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        )
    ");
    
    // Sessions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            user_agent TEXT,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // VIP list (SECRET - admin only)
    $db->exec("
        CREATE TABLE IF NOT EXISTS vip_list (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            added_by TEXT,
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Add default VIP
    $db->exec("INSERT OR IGNORE INTO vip_list (email, notes) VALUES ('seige235@yahoo.com', 'Permanent VIP - dedicated server')");
    
    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_tier ON users(tier)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id)");
    
    $results['main.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['main.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 2. DEVICES DATABASE (devices.db)
// ============================================

try {
    $db = new Database('devices');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            public_key TEXT NOT NULL,
            private_key_encrypted TEXT,
            assigned_ip TEXT NOT NULL,
            server_id INTEGER,
            server_ip TEXT,
            is_active INTEGER DEFAULT 1,
            last_handshake DATETIME,
            bytes_received INTEGER DEFAULT 0,
            bytes_sent INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_user ON devices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_server ON devices(server_id)");
    
    $results['devices.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['devices.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 3. SERVERS DATABASE (servers.db)
// ============================================

try {
    $db = new Database('servers');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            ip TEXT NOT NULL,
            location TEXT,
            country TEXT,
            type TEXT DEFAULT 'shared',
            public_key TEXT,
            endpoint TEXT,
            port INTEGER DEFAULT 51820,
            dns TEXT DEFAULT '1.1.1.1',
            is_active INTEGER DEFAULT 1,
            max_users INTEGER DEFAULT 500,
            current_users INTEGER DEFAULT 0,
            last_check DATETIME,
            last_status TEXT DEFAULT 'unknown',
            provider TEXT,
            monthly_cost REAL,
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default servers
    $db->exec("INSERT OR IGNORE INTO servers (name, ip, location, country, type, port) VALUES 
        ('New York', '66.94.103.91', 'USA East', 'US', 'shared', 51820),
        ('St. Louis', '144.126.133.253', 'USA Central', 'US', 'vip', 51820),
        ('Dallas', '66.241.124.4', 'USA Central', 'US', 'shared', 51820),
        ('Toronto', '66.241.125.247', 'Canada', 'CA', 'shared', 51820)
    ");
    
    $results['servers.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['servers.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 4. BILLING DATABASE (billing.db)
// ============================================

try {
    $db = new Database('billing');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            paypal_subscription_id TEXT UNIQUE,
            plan_id TEXT,
            plan_name TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            amount REAL,
            currency TEXT DEFAULT 'USD',
            billing_cycle TEXT DEFAULT 'monthly',
            current_period_start DATETIME,
            current_period_end DATETIME,
            cancelled_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER,
            paypal_transaction_id TEXT,
            type TEXT NOT NULL,
            amount REAL NOT NULL,
            currency TEXT DEFAULT 'USD',
            status TEXT NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            invoice_number TEXT NOT NULL UNIQUE,
            subscription_id INTEGER,
            amount REAL NOT NULL,
            tax REAL DEFAULT 0,
            total REAL NOT NULL,
            status TEXT DEFAULT 'pending',
            due_date DATE,
            paid_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_paypal ON subscriptions(paypal_subscription_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(user_id)");
    
    $results['billing.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['billing.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 5. ADMIN DATABASE (admin.db)
// ============================================

try {
    $db = new Database('admin');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            name TEXT,
            role TEXT DEFAULT 'admin',
            is_active INTEGER DEFAULT 1,
            last_login DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            category TEXT DEFAULT 'general',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            subject TEXT NOT NULL,
            body_html TEXT NOT NULL,
            body_text TEXT,
            category TEXT DEFAULT 'general',
            variables TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default settings
    $defaultSettings = [
        ['company_name', 'TrueVault VPN', 'general'],
        ['support_email', 'support@vpn.the-truth-publishing.com', 'general'],
        ['from_email', 'noreply@vpn.the-truth-publishing.com', 'email'],
        ['smtp_host', 'smtp.gmail.com', 'email'],
        ['smtp_port', '587', 'email'],
        ['gmail_user', 'paulhalonen@gmail.com', 'email'],
        ['paypal_mode', 'live', 'paypal'],
        ['plan_personal_price', '9.97', 'billing'],
        ['plan_family_price', '14.97', 'billing'],
        ['plan_dedicated_price', '39.97', 'billing']
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO system_settings (setting_key, setting_value, category) VALUES (?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    
    $results['admin.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['admin.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 6. LOGS DATABASE (logs.db)
// ============================================

try {
    $db = new Database('logs');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS security_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_type TEXT NOT NULL,
            user_id INTEGER,
            ip_address TEXT,
            user_agent TEXT,
            details TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            old_value TEXT,
            new_value TEXT,
            ip_address TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            endpoint TEXT NOT NULL,
            method TEXT,
            user_id INTEGER,
            ip_address TEXT,
            response_code INTEGER,
            response_time_ms INTEGER,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            method TEXT NOT NULL,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            body TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            error_message TEXT,
            sent_at DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient TEXT NOT NULL,
            subject TEXT NOT NULL,
            template_name TEXT NOT NULL,
            template_variables TEXT,
            email_type TEXT NOT NULL DEFAULT 'customer',
            status TEXT NOT NULL DEFAULT 'pending',
            scheduled_for DATETIME NOT NULL,
            sent_at DATETIME,
            attempts INTEGER DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS workflow_executions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_name TEXT NOT NULL,
            trigger_event TEXT NOT NULL,
            user_id INTEGER,
            user_email TEXT,
            status TEXT NOT NULL DEFAULT 'running',
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME,
            error_message TEXT,
            execution_data TEXT
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            execution_id INTEGER,
            task_name TEXT NOT NULL,
            task_type TEXT NOT NULL,
            task_data TEXT,
            execute_at DATETIME NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            executed_at DATETIME,
            result TEXT
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS automation_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT DEFAULT 'info',
            message TEXT NOT NULL,
            execution_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_security_events_type ON security_events(event_type)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status, scheduled_for)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_scheduled_tasks_status ON scheduled_tasks(status, execute_at)");
    
    $results['logs.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['logs.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 7. PORT FORWARDS DATABASE (port_forwards.db)
// ============================================

try {
    $db = new Database('port_forwards');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS port_forwards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            device_id INTEGER,
            name TEXT,
            external_port INTEGER NOT NULL,
            internal_ip TEXT NOT NULL,
            internal_port INTEGER NOT NULL,
            protocol TEXT DEFAULT 'tcp',
            is_active INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS discovered_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            ip TEXT NOT NULL,
            mac TEXT,
            hostname TEXT,
            vendor TEXT,
            device_type TEXT,
            open_ports TEXT,
            discovered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_port_forwards_user ON port_forwards(user_id)");
    
    $results['port_forwards.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['port_forwards.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// 8. SUPPORT DATABASE (support.db)
// ============================================

try {
    $db = new Database('support');
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            description TEXT NOT NULL,
            category TEXT,
            priority TEXT NOT NULL DEFAULT 'normal',
            status TEXT NOT NULL DEFAULT 'open',
            assigned_to TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER,
            is_staff INTEGER DEFAULT 0,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
        )
    ");
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS knowledge_base (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            category TEXT NOT NULL,
            keywords TEXT,
            view_count INTEGER DEFAULT 0,
            helpful_count INTEGER DEFAULT 0,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_user ON support_tickets(user_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tickets_status ON support_tickets(status, priority)");
    
    $results['support.db'] = 'SUCCESS';
} catch (Exception $e) {
    $results['support.db'] = 'FAILED: ' . $e->getMessage();
}

// ============================================
// CREATE LOCK FILE
// ============================================

$allSuccess = !in_array(false, array_map(function($r) { return strpos($r, 'SUCCESS') === 0; }, $results));

if ($allSuccess) {
    file_put_contents($lockFile, date('Y-m-d H:i:s') . "\n" . json_encode($results));
}

// ============================================
// OUTPUT RESULTS
// ============================================

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - TrueVault VPN</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #1a1a2e; color: #fff; }
        h1 { color: #00d9ff; }
        .result { padding: 10px 15px; margin: 10px 0; border-radius: 6px; }
        .success { background: rgba(0,255,136,0.2); border-left: 4px solid #00ff88; }
        .failed { background: rgba(255,107,107,0.2); border-left: 4px solid #ff6b6b; }
        .summary { margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 8px; }
        a { color: #00d9ff; }
    </style>
</head>
<body>
    <h1>🗄️ Database Setup Complete</h1>
    
    <h2>Results:</h2>
    <?php foreach ($results as $db => $status): ?>
        <div class="result <?= strpos($status, 'SUCCESS') === 0 ? 'success' : 'failed' ?>">
            <strong><?= $db ?>:</strong> <?= $status ?>
        </div>
    <?php endforeach; ?>
    
    <div class="summary">
        <?php if ($allSuccess): ?>
            <h3>✅ All databases created successfully!</h3>
            <p>You can now:</p>
            <ul>
                <li><a href="/admin/">Go to Admin Panel</a></li>
                <li><a href="/admin/install-email-templates.php">Install Email Templates</a></li>
            </ul>
        <?php else: ?>
            <h3>⚠️ Some databases failed to create</h3>
            <p>Please check the errors above and try again.</p>
        <?php endif; ?>
    </div>
    
    <p style="margin-top: 20px; color: #888; font-size: 12px;">
        This page can only be run once. To run again, delete the lock file or add ?force=yes to the URL.
    </p>
</body>
</html>
