<?php
/**
 * TrueVault VPN - Database Setup Script (SQLite3 version)
 * Creates all 21 SQLite databases with their tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TrueVault VPN - Database Setup</h2><pre>\n";

// Database directory
$dbDir = __DIR__ . '/../../data';

// Create data directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✅ Created data directory\n";
}

// Check if SQLite3 is available
if (!class_exists('SQLite3')) {
    die("❌ SQLite3 is not available on this server!\n");
}

echo "✅ SQLite3 is available\n\n";

// Database schemas
$schemas = [
    'users' => [
        "CREATE TABLE IF NOT EXISTS users (
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
        )",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)"
    ],
    
    'admin_users' => [
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            first_name TEXT,
            last_name TEXT,
            role TEXT DEFAULT 'admin',
            status TEXT DEFAULT 'active',
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'subscriptions' => [
        "CREATE TABLE IF NOT EXISTS subscriptions (
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
        )"
    ],
    
    'payments' => [
        "CREATE TABLE IF NOT EXISTS payments (
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
        )",
        "CREATE TABLE IF NOT EXISTS payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            payment_method TEXT,
            card_last_four TEXT,
            card_brand TEXT,
            is_default INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'vpn' => [
        "CREATE TABLE IF NOT EXISTS vpn_servers (
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
            vip_user_email TEXT,
            status TEXT DEFAULT 'online',
            provider TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS vpn_connections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            status TEXT DEFAULT 'connected',
            assigned_ip TEXT,
            data_transfer INTEGER DEFAULT 0,
            connected_at TEXT DEFAULT CURRENT_TIMESTAMP,
            disconnected_at TEXT
        )"
    ],
    
    'certificates' => [
        "CREATE TABLE IF NOT EXISTS user_certificates (
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
        )",
        "CREATE TABLE IF NOT EXISTS ca_certificates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            public_key TEXT NOT NULL,
            private_key TEXT NOT NULL,
            certificate TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'devices' => [
        "CREATE TABLE IF NOT EXISTS user_devices (
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
        )"
    ],
    
    'identities' => [
        "CREATE TABLE IF NOT EXISTS regional_identities (
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
        )"
    ],
    
    'mesh' => [
        "CREATE TABLE IF NOT EXISTS mesh_networks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            owner_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            max_members INTEGER DEFAULT 6,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS mesh_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            user_id INTEGER,
            email TEXT,
            nickname TEXT,
            role TEXT DEFAULT 'member',
            permissions TEXT DEFAULT 'full',
            status TEXT DEFAULT 'pending',
            joined_at TEXT
        )",
        "CREATE TABLE IF NOT EXISTS mesh_invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            email TEXT NOT NULL,
            invite_code TEXT NOT NULL,
            permissions TEXT DEFAULT 'full',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            expires_at TEXT
        )",
        "CREATE TABLE IF NOT EXISTS shared_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            owner_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            local_ip TEXT,
            access_level TEXT DEFAULT 'view',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'cameras' => [
        "CREATE TABLE IF NOT EXISTS ip_cameras (
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
        )"
    ],
    
    'themes' => [
        "CREATE TABLE IF NOT EXISTS themes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            is_active INTEGER DEFAULT 0,
            variables TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'pages' => [
        "CREATE TABLE IF NOT EXISTS pages (
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
        )"
    ],
    
    'emails' => [
        "CREATE TABLE IF NOT EXISTS email_templates (
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
        )",
        "CREATE TABLE IF NOT EXISTS email_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            template_id INTEGER,
            to_email TEXT NOT NULL,
            subject TEXT,
            status TEXT DEFAULT 'sent',
            opened_at TEXT,
            clicked_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'media' => [
        "CREATE TABLE IF NOT EXISTS media_files (
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
        )"
    ],
    
    'logs' => [
        "CREATE TABLE IF NOT EXISTS system_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            level TEXT DEFAULT 'info',
            category TEXT DEFAULT 'system',
            message TEXT NOT NULL,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS activity_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS admin_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'settings' => [
        "CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            value TEXT,
            type TEXT DEFAULT 'string',
            category TEXT DEFAULT 'general',
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'automation' => [
        "CREATE TABLE IF NOT EXISTS workflows (
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
        )",
        "CREATE TABLE IF NOT EXISTS workflow_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER NOT NULL,
            status TEXT DEFAULT 'running',
            context TEXT,
            result TEXT,
            started_at TEXT DEFAULT CURRENT_TIMESTAMP,
            completed_at TEXT
        )",
        "CREATE TABLE IF NOT EXISTS scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            workflow_id INTEGER,
            step_index INTEGER,
            context TEXT,
            execute_at TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'notifications' => [
        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT DEFAULT 'info',
            title TEXT NOT NULL,
            message TEXT,
            is_read INTEGER DEFAULT 0,
            action_url TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'analytics' => [
        "CREATE TABLE IF NOT EXISTS page_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_path TEXT NOT NULL,
            user_id INTEGER,
            session_id TEXT,
            referrer TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_name TEXT NOT NULL,
            event_data TEXT,
            user_id INTEGER,
            session_id TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'bandwidth' => [
        "CREATE TABLE IF NOT EXISTS bandwidth_usage (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            server_id INTEGER NOT NULL,
            bytes_in INTEGER DEFAULT 0,
            bytes_out INTEGER DEFAULT 0,
            period_start TEXT NOT NULL,
            period_end TEXT NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS bandwidth_credits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount INTEGER NOT NULL,
            reason TEXT,
            expires_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ],
    
    'support' => [
        "CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            priority TEXT DEFAULT 'normal',
            status TEXT DEFAULT 'open',
            assigned_to INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS ticket_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER,
            admin_id INTEGER,
            message TEXT NOT NULL,
            attachments TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS knowledge_base (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT UNIQUE NOT NULL,
            content TEXT NOT NULL,
            category TEXT,
            tags TEXT,
            view_count INTEGER DEFAULT 0,
            is_published INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )"
    ]
];

// Create databases
$success = 0;
$errors = 0;

foreach ($schemas as $dbName => $tables) {
    $dbPath = "$dbDir/$dbName.db";
    
    try {
        $db = new SQLite3($dbPath);
        
        foreach ($tables as $sql) {
            $db->exec($sql);
        }
        
        echo "✅ Created: $dbName.db\n";
        $success++;
        $db->close();
        
    } catch (Exception $e) {
        echo "❌ Error creating $dbName.db: " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Insert default data
echo "\n--- Inserting default data ---\n";

// Default theme
try {
    $db = new SQLite3("$dbDir/themes.db");
    $themeVars = json_encode([
        'colors' => [
            'primary' => '#00d9ff',
            'secondary' => '#00ff88',
            'accent' => '#ff6b6b',
            'background' => '#0f0f1a',
            'backgroundSecondary' => '#1a1a2e',
            'text' => '#ffffff',
            'textMuted' => '#888888',
            'success' => '#00ff88',
            'warning' => '#ffbb00',
            'error' => '#ff5050',
            'border' => 'rgba(255,255,255,0.08)'
        ],
        'gradients' => [
            'primary' => 'linear-gradient(90deg, #00d9ff, #00ff88)',
            'background' => 'linear-gradient(135deg, #0f0f1a, #1a1a2e)'
        ],
        'typography' => [
            'fontFamily' => 'Inter, -apple-system, sans-serif',
            'fontSizeBase' => '16px'
        ],
        'buttons' => [
            'borderRadius' => '8px',
            'padding' => '10px 20px'
        ],
        'cards' => [
            'borderRadius' => '14px',
            'padding' => '20px'
        ]
    ]);
    $stmt = $db->prepare("INSERT OR REPLACE INTO themes (id, name, is_active, variables) VALUES (1, 'Default Dark', 1, :vars)");
    $stmt->bindValue(':vars', $themeVars, SQLITE3_TEXT);
    $stmt->execute();
    echo "✅ Default theme inserted\n";
    $db->close();
} catch (Exception $e) {
    echo "❌ Theme error: " . $e->getMessage() . "\n";
}

// Default admin user (password: password)
try {
    $db = new SQLite3("$dbDir/admin_users.db");
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT OR REPLACE INTO admin_users (id, email, password, first_name, last_name, role) VALUES (1, :email, :pass, 'Kah-Len', 'Halonen', 'super_admin')");
    $stmt->bindValue(':email', 'kahlen@truthvault.com', SQLITE3_TEXT);
    $stmt->bindValue(':pass', $passwordHash, SQLITE3_TEXT);
    $stmt->execute();
    echo "✅ Default admin user created\n";
    $db->close();
} catch (Exception $e) {
    echo "❌ Admin error: " . $e->getMessage() . "\n";
}

// Default VPN servers with public keys
try {
    $db = new SQLite3("$dbDir/vpn.db");
    // [id, name, ip_address, location, region, country_code, port, is_vip, vip_user_email, status, provider, public_key]
    $servers = [
        [1, 'US-East', '66.94.103.91', 'New York', 'US-East', 'US', 51820, 0, NULL, 'online', 'Contabo', 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s='],
        [2, 'US-Central VIP', '144.126.133.253', 'St. Louis', 'US-Central', 'US', 51820, 1, 'seige235@yahoo.com', 'online', 'Contabo', 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM='],
        [3, 'Dallas', '66.241.124.4', 'Dallas', 'US-South', 'US', 51820, 0, NULL, 'online', 'Fly.io', 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk='],
        [4, 'Canada', '66.241.125.247', 'Toronto', 'CA-East', 'CA', 51820, 0, NULL, 'online', 'Fly.io', 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=']
    ];
    
    foreach ($servers as $s) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO vpn_servers (id, name, ip_address, location, region, country_code, port, is_vip, vip_user_email, status, provider, public_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $s[0], SQLITE3_INTEGER);
        $stmt->bindValue(2, $s[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $s[2], SQLITE3_TEXT);
        $stmt->bindValue(4, $s[3], SQLITE3_TEXT);
        $stmt->bindValue(5, $s[4], SQLITE3_TEXT);
        $stmt->bindValue(6, $s[5], SQLITE3_TEXT);
        $stmt->bindValue(7, $s[6], SQLITE3_INTEGER);
        $stmt->bindValue(8, $s[7], SQLITE3_INTEGER);
        $stmt->bindValue(9, $s[8], SQLITE3_TEXT);
        $stmt->bindValue(10, $s[9], SQLITE3_TEXT);
        $stmt->bindValue(11, $s[10], SQLITE3_TEXT);
        $stmt->bindValue(12, $s[11], SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "✅ VPN servers inserted (4 servers, 1 VIP) with public keys\n";
    $db->close();
} catch (Exception $e) {
    echo "❌ Servers error: " . $e->getMessage() . "\n";
}

// Default settings
try {
    $db = new SQLite3("$dbDir/settings.db");
    $settings = [
        ['site_name', 'TrueVault VPN', 'general'],
        ['support_email', 'paulhalonen@gmail.com', 'general'],
        ['timezone', 'America/Chicago', 'general'],
        ['maintenance_mode', '0', 'general'],
        ['vip_server_id', '2', 'vip'],
        ['vip_server_ip', '144.126.133.253', 'vip'],
        ['vip_authorized_email', 'seige235@yahoo.com', 'vip'],
        ['paypal_mode', 'live', 'paypal'],
        ['paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'paypal'],
        ['jwt_secret', 'truevault_jwt_secret_change_this', 'security']
    ];
    
    foreach ($settings as $s) {
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value, category) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $s[0], SQLITE3_TEXT);
        $stmt->bindValue(2, $s[1], SQLITE3_TEXT);
        $stmt->bindValue(3, $s[2], SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "✅ Default settings inserted\n";
    $db->close();
} catch (Exception $e) {
    echo "❌ Settings error: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Total: $success databases created, $errors errors\n";
echo "========================================\n";

if ($errors === 0) {
    echo "\n✅ ALL DATABASES CREATED SUCCESSFULLY!\n\n";
    echo "Default admin login:\n";
    echo "  Email: kahlen@truthvault.com\n";
    echo "  Password: password\n";
    echo "\n⚠️  CHANGE THIS PASSWORD IMMEDIATELY!\n";
    echo "\nNow visit: https://vpn.the-truth-publishing.com/\n";
}

echo "</pre>";
