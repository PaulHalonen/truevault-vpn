<?php
/**
 * TrueVault VPN - Complete Database Setup
 * Run this ONCE to initialize all databases
 * 
 * URL: https://vpn.the-truth-publishing.com/api/config/setup-all.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>TrueVault Database Setup</title>
<style>
body { font-family: -apple-system, sans-serif; background: #0f0f1a; color: #fff; padding: 2rem; }
.success { color: #00ff88; }
.error { color: #ff5050; }
.info { color: #00d9ff; }
h1 { color: #00d9ff; }
pre { background: #1a1a2e; padding: 1rem; border-radius: 8px; overflow-x: auto; }
.section { margin: 2rem 0; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 8px; }
</style></head><body>";

echo "<h1>🔐 TrueVault VPN - Database Setup</h1>";
echo "<p>Setting up all databases...</p>";

// Determine base path
if (file_exists('/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com')) {
    $basePath = '/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases';
} else {
    $basePath = dirname(__DIR__, 2) . '/databases';
}

echo "<p class='info'>Database path: {$basePath}</p>";

// Create directories
$dirs = ['core', 'vpn', 'billing', 'cms', 'logs'];
foreach ($dirs as $dir) {
    $path = "{$basePath}/{$dir}";
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "<p class='success'>✓ Created directory: {$dir}/</p>";
    } else {
        echo "<p>Directory exists: {$dir}/</p>";
    }
}

$errors = [];
$success = [];

// ============================================
// 1. USERS DATABASE (core/users.db)
// ============================================
echo "<div class='section'><h2>1. Users Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/core/users.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        first_name TEXT,
        last_name TEXT,
        status TEXT DEFAULT 'active',
        is_vip INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");
    echo "<p class='success'>✓ Created users table</p>";
    
    // User devices table (CONSOLIDATED - one table for all device data)
    $db->exec("CREATE TABLE IF NOT EXISTS user_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_name TEXT NOT NULL,
        device_type TEXT DEFAULT 'unknown',
        public_key TEXT UNIQUE NOT NULL,
        server_id INTEGER,
        assigned_ip TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_connected DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(user_id, device_name)
    )");
    echo "<p class='success'>✓ Created user_devices table</p>";
    
    // Password resets
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        expires_at DATETIME NOT NULL,
        used INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "<p class='success'>✓ Created password_resets table</p>";
    
    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_devices_key ON user_devices(public_key)");
    echo "<p class='success'>✓ Created indexes</p>";
    
    $success[] = "users.db";
} catch (Exception $e) {
    $errors[] = "users.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 2. SERVERS DATABASE (vpn/servers.db)
// ============================================
echo "<div class='section'><h2>2. Servers Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/vpn/servers.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // VPN Servers table
    $db->exec("CREATE TABLE IF NOT EXISTS vpn_servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        display_name TEXT NOT NULL,
        country TEXT NOT NULL,
        country_code TEXT NOT NULL,
        country_flag TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        wireguard_port INTEGER DEFAULT 51820,
        api_port INTEGER DEFAULT 8080,
        public_key TEXT,
        server_type TEXT DEFAULT 'shared',
        vip_user_email TEXT,
        max_connections INTEGER DEFAULT 50,
        current_connections INTEGER DEFAULT 0,
        cpu_load INTEGER DEFAULT 0,
        latency_ms INTEGER DEFAULT 0,
        bandwidth_type TEXT DEFAULT 'unlimited',
        status TEXT DEFAULT 'active',
        rules_title TEXT,
        rules_description TEXT,
        rules_allowed TEXT,
        rules_not_allowed TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created vpn_servers table</p>";
    
    // Check if servers exist
    $count = $db->query("SELECT COUNT(*) FROM vpn_servers")->fetchColumn();
    
    if ($count == 0) {
        // Insert the 4 servers
        $db->exec("INSERT INTO vpn_servers (id, name, display_name, country, country_code, country_flag, ip_address, wireguard_port, api_port, public_key, server_type, vip_user_email, max_connections, bandwidth_type, status, rules_title, rules_description, rules_allowed, rules_not_allowed) VALUES
        (1, 'truevault-ny', 'New York, USA', 'USA', 'US', '🇺🇸', '66.94.103.91', 51820, 8080, 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', 'shared', NULL, 50, 'unlimited', 'active', 'RECOMMENDED FOR HOME USE', 'Use for all home devices including gaming consoles, IP cameras, and streaming.', '[\"Gaming\", \"Torrents/P2P\", \"IP Cameras\", \"Streaming\", \"General browsing\"]', '[]'),
        
        (2, 'truevault-stl', 'St. Louis, USA (VIP)', 'USA', 'US', '🇺🇸', '144.126.133.253', 51820, 8080, 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', 'vip_dedicated', 'seige235@yahoo.com', 1, 'unlimited', 'active', 'PRIVATE DEDICATED SERVER', 'Exclusively for VIP user. Unlimited bandwidth, no restrictions.', '[\"Everything - No restrictions\", \"Unlimited bandwidth\", \"Static IP address\"]', '[]'),
        
        (3, 'truevault-tx', 'Dallas, USA', 'USA', 'US', '🇺🇸', '66.241.124.4', 51820, 8443, 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', 'shared', NULL, 50, 'limited', 'active', 'STREAMING OPTIMIZED', 'Optimized for Netflix and streaming services.', '[\"Netflix\", \"Hulu\", \"Disney+\", \"Amazon Prime\"]', '[\"Gaming (high latency)\", \"Torrents/P2P\", \"IP Cameras\", \"Heavy downloads\"]'),
        
        (4, 'truevault-ca', 'Toronto, Canada', 'Canada', 'CA', '🇨🇦', '66.241.125.247', 51820, 8080, 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', 'shared', NULL, 50, 'limited', 'active', 'CANADIAN STREAMING', 'Access Canadian Netflix and streaming content.', '[\"Canadian Netflix\", \"CBC Gem\", \"Crave\", \"Canadian content\"]', '[\"Gaming (latency)\", \"Torrents/P2P\", \"IP Cameras\", \"Heavy downloads\"]')");
        echo "<p class='success'>✓ Inserted 4 servers</p>";
    } else {
        echo "<p>Servers already exist ({$count} servers)</p>";
    }
    
    // Server health table
    $db->exec("CREATE TABLE IF NOT EXISTS server_health (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER NOT NULL,
        cpu_load INTEGER,
        memory_usage INTEGER,
        connection_count INTEGER,
        bytes_in INTEGER,
        bytes_out INTEGER,
        checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created server_health table</p>";
    
    $success[] = "servers.db";
} catch (Exception $e) {
    $errors[] = "servers.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 3. PEERS DATABASE (vpn/peers.db)
// ============================================
echo "<div class='section'><h2>3. Peers Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/vpn/peers.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS vpn_peers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        public_key TEXT NOT NULL,
        assigned_ip TEXT NOT NULL,
        allowed_ips TEXT DEFAULT '0.0.0.0/0',
        is_active INTEGER DEFAULT 1,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        last_handshake DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(public_key),
        UNIQUE(server_id, assigned_ip)
    )");
    echo "<p class='success'>✓ Created vpn_peers table</p>";
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_peers_user ON vpn_peers(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_peers_server ON vpn_peers(server_id)");
    echo "<p class='success'>✓ Created indexes</p>";
    
    $success[] = "peers.db";
} catch (Exception $e) {
    $errors[] = "peers.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 4. BILLING DATABASE (billing/billing.db)
// ============================================
echo "<div class='section'><h2>4. Billing Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/billing/billing.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Plans table
    $db->exec("CREATE TABLE IF NOT EXISTS plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        display_name TEXT NOT NULL,
        price REAL NOT NULL,
        billing_cycle TEXT DEFAULT 'monthly',
        max_devices INTEGER DEFAULT 3,
        features TEXT,
        paypal_plan_id TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created plans table</p>";
    
    // Insert default plans
    $count = $db->query("SELECT COUNT(*) FROM plans")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO plans (id, name, display_name, price, max_devices, features) VALUES
        (1, 'trial', 'Free Trial', 0, 1, '[\"1 Device\", \"All servers\", \"14-day trial\"]'),
        (2, 'personal', 'Personal', 9.99, 3, '[\"3 Devices\", \"All servers\", \"24/7 Support\"]'),
        (3, 'family', 'Family', 14.99, 999, '[\"Unlimited Devices\", \"All servers\", \"Priority Support\"]'),
        (4, 'business', 'Business', 29.99, 999, '[\"Unlimited Devices\", \"Dedicated Server\", \"API Access\", \"SLA\"]')");
        echo "<p class='success'>✓ Inserted 4 plans</p>";
    }
    
    // Subscriptions table
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        plan_id INTEGER,
        plan_type TEXT NOT NULL,
        status TEXT DEFAULT 'active',
        paypal_subscription_id TEXT,
        price REAL DEFAULT 0,
        trial_ends_at DATETIME,
        current_period_start DATETIME,
        current_period_end DATETIME,
        cancelled_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created subscriptions table</p>";
    
    // Payments table
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        subscription_id INTEGER,
        paypal_payment_id TEXT,
        paypal_order_id TEXT,
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'USD',
        status TEXT DEFAULT 'pending',
        payment_method TEXT DEFAULT 'paypal',
        receipt_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created payments table</p>";
    
    $success[] = "billing.db";
} catch (Exception $e) {
    $errors[] = "billing.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 5. THEMES DATABASE (cms/themes.db)
// ============================================
echo "<div class='section'><h2>5. Themes Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/cms/themes.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Themes table
    $db->exec("CREATE TABLE IF NOT EXISTS themes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        is_active INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created themes table</p>";
    
    // Theme variables table
    $db->exec("CREATE TABLE IF NOT EXISTS theme_variables (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        theme_id INTEGER NOT NULL,
        category TEXT NOT NULL,
        variable_name TEXT NOT NULL,
        variable_value TEXT NOT NULL,
        UNIQUE(theme_id, variable_name)
    )");
    echo "<p class='success'>✓ Created theme_variables table</p>";
    
    // Insert default theme
    $count = $db->query("SELECT COUNT(*) FROM themes")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO themes (id, name, description, is_active) VALUES 
        (1, 'TrueVault Dark', 'Default dark theme with cyan/green accents', 1)");
        
        // Insert theme variables
        $variables = [
            ['colors', '--bg-primary', '#0f0f1a'],
            ['colors', '--bg-secondary', '#1a1a2e'],
            ['colors', '--bg-card', 'rgba(255,255,255,0.03)'],
            ['colors', '--text-primary', '#ffffff'],
            ['colors', '--text-secondary', '#888888'],
            ['colors', '--accent-cyan', '#00d9ff'],
            ['colors', '--accent-green', '#00ff88'],
            ['colors', '--accent-red', '#ff5050'],
            ['colors', '--accent-yellow', '#ffd700'],
            ['colors', '--border-color', 'rgba(255,255,255,0.08)'],
            ['fonts', '--font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'],
            ['fonts', '--font-size-base', '16px'],
            ['spacing', '--spacing-sm', '0.5rem'],
            ['spacing', '--spacing-md', '1rem'],
            ['spacing', '--spacing-lg', '1.5rem'],
            ['borders', '--border-radius-sm', '4px'],
            ['borders', '--border-radius-md', '8px'],
            ['borders', '--border-radius-lg', '12px'],
        ];
        
        $stmt = $db->prepare("INSERT INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES (1, ?, ?, ?)");
        foreach ($variables as $var) {
            $stmt->execute($var);
        }
        echo "<p class='success'>✓ Inserted default theme with " . count($variables) . " variables</p>";
    }
    
    $success[] = "themes.db";
} catch (Exception $e) {
    $errors[] = "themes.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 6. LOGS DATABASE (logs/logs.db)
// ============================================
echo "<div class='section'><h2>6. Logs Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/logs/logs.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // System logs
    $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        level TEXT NOT NULL,
        category TEXT NOT NULL,
        message TEXT NOT NULL,
        user_id INTEGER,
        ip_address TEXT,
        user_agent TEXT,
        extra_data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created system_logs table</p>";
    
    // Email queue
    $db->exec("CREATE TABLE IF NOT EXISTS email_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        recipient TEXT NOT NULL,
        subject TEXT NOT NULL,
        body TEXT NOT NULL,
        template TEXT,
        status TEXT DEFAULT 'pending',
        attempts INTEGER DEFAULT 0,
        last_attempt DATETIME,
        sent_at DATETIME,
        error TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created email_queue table</p>";
    
    // Scheduled tasks
    $db->exec("CREATE TABLE IF NOT EXISTS scheduled_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        task_type TEXT NOT NULL,
        task_data TEXT,
        execute_at DATETIME NOT NULL,
        status TEXT DEFAULT 'pending',
        result TEXT,
        executed_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created scheduled_tasks table</p>";
    
    // Indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_logs_created ON system_logs(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_email_status ON email_queue(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_status ON scheduled_tasks(status, execute_at)");
    echo "<p class='success'>✓ Created indexes</p>";
    
    $success[] = "logs.db";
} catch (Exception $e) {
    $errors[] = "logs.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// 7. ADMIN DATABASE (core/admin.db)
// ============================================
echo "<div class='section'><h2>7. Admin Database</h2>";
try {
    $db = new PDO("sqlite:{$basePath}/core/admin.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT,
        role TEXT DEFAULT 'admin',
        status TEXT DEFAULT 'active',
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Created admin_users table</p>";
    
    // Create default admin
    $count = $db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($count == 0) {
        $hash = password_hash('TrueVault2026!', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO admin_users (email, password_hash, name, role) VALUES 
        ('admin@truevault.com', '{$hash}', 'TrueVault Admin', 'super_admin')");
        echo "<p class='success'>✓ Created default admin (admin@truevault.com / TrueVault2026!)</p>";
    }
    
    $success[] = "admin.db";
} catch (Exception $e) {
    $errors[] = "admin.db: " . $e->getMessage();
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// ============================================
// SUMMARY
// ============================================
echo "<div class='section'><h2>📊 Summary</h2>";
echo "<p class='success'>✓ Successfully created: " . implode(", ", $success) . "</p>";

if (!empty($errors)) {
    echo "<p class='error'>✗ Errors:</p><ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ul>";
}

echo "<h3>Databases Created:</h3><pre>";
foreach ($dirs as $dir) {
    $path = "{$basePath}/{$dir}";
    if (is_dir($path)) {
        $files = glob("{$path}/*.db");
        foreach ($files as $file) {
            echo basename($dir) . "/" . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
    }
}
echo "</pre>";

echo "<h3>Next Steps:</h3>
<ol>
<li>Test login at <a href='/login.html'>/login.html</a></li>
<li>Test registration at <a href='/register.html'>/register.html</a></li>
<li>Access admin at <a href='/admin/'>/admin/</a></li>
</ol>";

echo "</div></body></html>";
?>
