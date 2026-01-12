<?php
/**
 * TrueVault VPN - Database Setup Script
 * Creates all 21 SQLite databases with their tables
 * 
 * Run this once after deploying to create the database files
 * URL: https://vpn.the-truth-publishing.com/api/config/setup-databases.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database directory
$dbDir = __DIR__ . '/../../data';

// Create data directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✅ Created data directory<br>\n";
}

// Database schemas
$schemas = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            plan_type TEXT DEFAULT 'personal',
            status TEXT DEFAULT 'active',
            email_verified INTEGER DEFAULT 0,
            email_verify_token TEXT,
            password_reset_token TEXT,
            password_reset_expires TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
        CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
    ",
    
    'admin_users' => "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            role TEXT DEFAULT 'admin',
            status TEXT DEFAULT 'active',
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'subscriptions' => "
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            plan_type TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            amount REAL,
            currency TEXT DEFAULT 'USD',
            billing_cycle TEXT DEFAULT 'monthly',
            next_billing_date TEXT,
            cancelled_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id);
        CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
    ",
    
    'payments' => "
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            currency TEXT DEFAULT 'USD',
            status TEXT DEFAULT 'pending',
            payment_method TEXT,
            transaction_id TEXT,
            invoice_number TEXT,
            description TEXT,
            refunded_amount REAL,
            refunded_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            payment_method TEXT,
            card_last_four TEXT,
            card_brand TEXT,
            is_default INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
        CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
    ",
    
    'vpn' => "
        CREATE TABLE IF NOT EXISTS vpn_servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            location TEXT,
            region TEXT,
            country_code TEXT,
            port INTEGER DEFAULT 51820,
            public_key TEXT,
            max_connections INTEGER DEFAULT 50,
            current_load REAL DEFAULT 0,
            is_vip INTEGER DEFAULT 0,
            status TEXT DEFAULT 'online',
            provider TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS vpn_connections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            status TEXT DEFAULT 'connected',
            assigned_ip TEXT,
            data_transfer INTEGER DEFAULT 0,
            connected_at TEXT DEFAULT CURRENT_TIMESTAMP,
            disconnected_at TEXT
        );
        CREATE INDEX IF NOT EXISTS idx_connections_user ON vpn_connections(user_id);
        CREATE INDEX IF NOT EXISTS idx_connections_server ON vpn_connections(server_id);
        CREATE INDEX IF NOT EXISTS idx_connections_status ON vpn_connections(status);
    ",
    
    'certificates' => "
        CREATE TABLE IF NOT EXISTS user_certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT,
            type TEXT DEFAULT 'device',
            public_key TEXT,
            private_key TEXT,
            certificate TEXT,
            fingerprint TEXT,
            status TEXT DEFAULT 'active',
            expires_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS ca_certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            public_key TEXT NOT NULL,
            private_key TEXT NOT NULL,
            certificate TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_certs_user ON user_certificates(user_id);
    ",
    
    'devices' => "
        CREATE TABLE IF NOT EXISTS user_devices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT DEFAULT 'desktop',
            os TEXT,
            public_key TEXT,
            ip_address TEXT,
            last_active TEXT,
            is_current INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id);
    ",
    
    'identities' => "
        CREATE TABLE IF NOT EXISTS regional_identities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            region TEXT NOT NULL,
            persistent_ip TEXT,
            timezone TEXT,
            fingerprint_hash TEXT,
            is_active INTEGER DEFAULT 0,
            last_used TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_identities_user ON regional_identities(user_id);
    ",
    
    'mesh' => "
        CREATE TABLE IF NOT EXISTS mesh_networks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            owner_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            max_members INTEGER DEFAULT 6,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS mesh_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            user_id INTEGER,
            email TEXT,
            nickname TEXT,
            role TEXT DEFAULT 'member',
            permissions TEXT DEFAULT 'full',
            status TEXT DEFAULT 'pending',
            joined_at TEXT
        );
        CREATE TABLE IF NOT EXISTS mesh_invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            email TEXT NOT NULL,
            invite_code TEXT NOT NULL,
            permissions TEXT DEFAULT 'full',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            expires_at TEXT
        );
        CREATE TABLE IF NOT EXISTS shared_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            owner_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            local_ip TEXT,
            access_level TEXT DEFAULT 'view',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_mesh_owner ON mesh_networks(owner_id);
    ",
    
    'cameras' => "
        CREATE TABLE IF NOT EXISTS ip_cameras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            local_ip TEXT,
            mac_address TEXT,
            brand TEXT,
            model TEXT,
            stream_url TEXT,
            port_forward_enabled INTEGER DEFAULT 0,
            port_forward_external INTEGER,
            status TEXT DEFAULT 'offline',
            last_seen TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_cameras_user ON ip_cameras(user_id);
    ",
    
    'themes' => "
        CREATE TABLE IF NOT EXISTS themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            is_active INTEGER DEFAULT 0,
            variables TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'pages' => "
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            content TEXT,
            html TEXT,
            css TEXT,
            js TEXT,
            status TEXT DEFAULT 'draft',
            template TEXT DEFAULT 'default',
            meta_title TEXT,
            meta_description TEXT,
            view_count INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_pages_slug ON pages(slug);
    ",
    
    'emails' => "
        CREATE TABLE IF NOT EXISTS email_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            subject TEXT NOT NULL,
            body TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            variables TEXT,
            sent_count INTEGER DEFAULT 0,
            open_rate REAL DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS email_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            template_id INTEGER,
            to_email TEXT NOT NULL,
            subject TEXT,
            status TEXT DEFAULT 'sent',
            opened_at TEXT,
            clicked_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'media' => "
        CREATE TABLE IF NOT EXISTS media_files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            original_name TEXT,
            mime_type TEXT,
            size INTEGER,
            path TEXT NOT NULL,
            width INTEGER,
            height INTEGER,
            uploaded_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'logs' => "
        CREATE TABLE IF NOT EXISTS system_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT DEFAULT 'info',
            category TEXT DEFAULT 'system',
            message TEXT NOT NULL,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS admin_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_system_log_level ON system_log(level);
        CREATE INDEX IF NOT EXISTS idx_activity_user ON activity_log(user_id);
    ",
    
    'settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            value TEXT,
            type TEXT DEFAULT 'string',
            category TEXT DEFAULT 'general',
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key);
        CREATE INDEX IF NOT EXISTS idx_settings_category ON settings(category);
    ",
    
    'automation' => "
        CREATE TABLE IF NOT EXISTS workflows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            trigger_type TEXT NOT NULL,
            trigger_config TEXT,
            steps TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            run_count INTEGER DEFAULT 0,
            success_rate REAL DEFAULT 100,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS workflow_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER NOT NULL,
            status TEXT DEFAULT 'running',
            context TEXT,
            result TEXT,
            started_at TEXT DEFAULT CURRENT_TIMESTAMP,
            completed_at TEXT
        );
        CREATE TABLE IF NOT EXISTS scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER,
            step_index INTEGER,
            context TEXT,
            execute_at TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'notifications' => "
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT DEFAULT 'info',
            title TEXT NOT NULL,
            message TEXT,
            is_read INTEGER DEFAULT 0,
            action_url TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
    ",
    
    'analytics' => "
        CREATE TABLE IF NOT EXISTS page_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_path TEXT NOT NULL,
            user_id INTEGER,
            session_id TEXT,
            referrer TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_name TEXT NOT NULL,
            event_data TEXT,
            user_id INTEGER,
            session_id TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'bandwidth' => "
        CREATE TABLE IF NOT EXISTS bandwidth_usage (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            bytes_in INTEGER DEFAULT 0,
            bytes_out INTEGER DEFAULT 0,
            period_start TEXT NOT NULL,
            period_end TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS bandwidth_credits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount INTEGER NOT NULL,
            reason TEXT,
            expires_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ",
    
    'support' => "
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            priority TEXT DEFAULT 'normal',
            status TEXT DEFAULT 'open',
            assigned_to INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER,
            admin_id INTEGER,
            message TEXT NOT NULL,
            attachments TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS knowledge_base (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            content TEXT NOT NULL,
            category TEXT,
            tags TEXT,
            view_count INTEGER DEFAULT 0,
            is_published INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    "
];

// Default data
$defaultData = [
    'themes' => "
        INSERT OR IGNORE INTO themes (id, name, is_active, variables) VALUES (1, 'Default Dark', 1, '{
            \"colors\": {
                \"primary\": \"#00d9ff\",
                \"secondary\": \"#00ff88\",
                \"accent\": \"#ff6b6b\",
                \"background\": \"#0f0f1a\",
                \"backgroundSecondary\": \"#1a1a2e\",
                \"text\": \"#ffffff\",
                \"textMuted\": \"#888888\",
                \"success\": \"#00ff88\",
                \"warning\": \"#ffbb00\",
                \"error\": \"#ff5050\",
                \"border\": \"rgba(255,255,255,0.08)\"
            },
            \"gradients\": {
                \"primary\": \"linear-gradient(90deg, #00d9ff, #00ff88)\",
                \"background\": \"linear-gradient(135deg, #0f0f1a, #1a1a2e)\"
            },
            \"typography\": {
                \"fontFamily\": \"Inter, -apple-system, sans-serif\",
                \"fontSizeBase\": \"16px\"
            },
            \"buttons\": {
                \"borderRadius\": \"8px\",
                \"padding\": \"10px 20px\"
            },
            \"cards\": {
                \"borderRadius\": \"14px\",
                \"padding\": \"20px\"
            },
            \"layout\": {
                \"sidebarWidth\": \"260px\",
                \"maxWidth\": \"1200px\"
            }
        }');
    ",
    
    'admin_users' => "
        INSERT OR IGNORE INTO admin_users (id, email, password, first_name, last_name, role) 
        VALUES (1, 'kahlen@truthvault.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kah-Len', 'Halonen', 'super_admin');
    ",
    
    'vpn' => "
        INSERT OR IGNORE INTO vpn_servers (id, name, ip_address, location, region, country_code, port, is_vip, status, provider) VALUES
        (1, 'US-East', '66.94.103.91', 'New York', 'US-East', 'US', 51820, 0, 'online', 'Contabo'),
        (2, 'US-Central VIP', '144.126.133.253', 'St. Louis', 'US-Central', 'US', 51820, 1, 'online', 'Contabo'),
        (3, 'Dallas', '66.241.124.4', 'Dallas', 'US-South', 'US', 51820, 0, 'online', 'Fly.io'),
        (4, 'Canada', '66.241.125.247', 'Toronto', 'CA-East', 'CA', 51820, 0, 'online', 'Fly.io');
    ",
    
    'settings' => "
        INSERT OR IGNORE INTO settings (key, value, category) VALUES
        ('site_name', 'TrueVault VPN', 'general'),
        ('support_email', 'paulhalonen@gmail.com', 'general'),
        ('timezone', 'America/Chicago', 'general'),
        ('maintenance_mode', '0', 'general'),
        ('vip_server_id', '2', 'vip'),
        ('vip_server_ip', '144.126.133.253', 'vip'),
        ('vip_authorized_email', 'seige235@yahoo.com', 'vip'),
        ('paypal_mode', 'live', 'paypal'),
        ('paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'paypal'),
        ('jwt_secret', 'truevault_jwt_secret_change_in_production', 'security'),
        ('jwt_expiry', '604800', 'security');
    "
];

// Create databases and tables
echo "<h2>TrueVault VPN - Database Setup</h2>\n";
echo "<pre>\n";

$success = 0;
$errors = 0;

foreach ($schemas as $dbName => $schema) {
    $dbPath = "$dbDir/$dbName.db";
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Execute schema
        $pdo->exec($schema);
        
        // Insert default data if exists
        if (isset($defaultData[$dbName])) {
            $pdo->exec($defaultData[$dbName]);
        }
        
        echo "✅ Created: $dbName.db\n";
        $success++;
        
    } catch (PDOException $e) {
        echo "❌ Error creating $dbName.db: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n----------------------------------------\n";
echo "Total: $success succeeded, $errors failed\n";
echo "----------------------------------------\n";

if ($errors === 0) {
    echo "\n✅ All databases created successfully!\n";
    echo "\nDefault admin login:\n";
    echo "Email: kahlen@truthvault.com\n";
    echo "Password: password\n";
    echo "\n⚠️  CHANGE THIS PASSWORD IMMEDIATELY!\n";
} else {
    echo "\n⚠️  Some databases failed to create. Check errors above.\n";
}

echo "</pre>\n";
