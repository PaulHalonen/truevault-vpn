# TRUEVAULT VPN - MASTER BUILD CHECKLIST (Part 1/4)

**Purpose:** Complete step-by-step checklist for building TrueVault VPN  
**Target:** Developers with ADHD - Clear, sequential, checkable tasks  
**Status:** Setup & Database Phase (Week 1)  
**Created:** January 15, 2026 - 7:40 AM CST  

---

## 📋 HOW TO USE THIS CHECKLIST

### **Rules:**
1. ✅ Check off each task as you complete it
2. 📝 Complete tasks in order (dependencies!)
3. 🔄 Test after each section before moving on
4. 💾 Commit to GitHub after each major milestone
5. 🚫 **NEVER skip a step** - even if it seems simple

### **Notation:**
- [ ] = Not started
- [⏳] = In progress
- [✅] = Complete
- [🔄] = Needs testing
- [❌] = Failed/blocked

---

## 🎯 WEEK 1: SETUP & DATABASE FOUNDATION

### **Goal:** Get environment ready, databases created, basic structure in place

---

## DAY 1: ENVIRONMENT SETUP (Monday)

### **Morning: Project Structure (2-3 hours)**

#### Task 1.1: Create Directory Structure
- [ ] Open FileZilla/FTP client
- [ ] Connect to: the-truth-publishing.com (FTP details in README)
- [ ] Navigate to: `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/`
- [ ] Create these folders (RIGHT CLICK > New Directory):

```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
├── api/                  ← [ ] Create this
├── includes/             ← [ ] Create this
├── assets/               ← [ ] Create this
│   ├── css/              ← [ ] Create this
│   ├── js/               ← [ ] Create this
│   └── images/           ← [ ] Create this
├── admin/                ← [ ] Create this
├── dashboard/            ← [ ] Create this
├── databases/            ← [ ] Create this
├── logs/                 ← [ ] Create this
├── configs/              ← [ ] Create this
└── temp/                 ← [ ] Create this
```

**Verification:**
- [ ] All 10 folders created
- [ ] Folder permissions set to 755 (check in FTP)
- [ ] Can navigate into each folder

---

#### Task 1.2: Create .htaccess for Security
- [ ] Create file: `/.htaccess`
- [ ] Add this code:

```apache
# TrueVault VPN - Root .htaccess
# Purpose: Security, redirects, and access control
# Created: January 2026

# Enable error handling
Options -Indexes

# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive directories
<FilesMatch "\.(db|log|ini|conf)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Block access to sensitive folders
RewriteRule ^databases/ - [F,L]
RewriteRule ^logs/ - [F,L]
RewriteRule ^configs/ - [F,L]
RewriteRule ^includes/ - [F,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# PHP Settings
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value max_input_time 300
</IfModule>
```

**Verification:**
- [ ] .htaccess file uploaded
- [ ] HTTPS redirect working (visit http:// and see if redirects to https://)
- [ ] Can't access /databases/ directly in browser (should get 403 Forbidden)

---

#### Task 1.3: Create Config File
- [ ] Create file: `/configs/config.php`
- [ ] Add this code:

```php
<?php
/**
 * TrueVault VPN - Master Configuration
 * 
 * PURPOSE: Central configuration file for all settings
 * IMPORTANT: All sensitive data stored in database, not hardcoded!
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('TRUEVAULT_INIT')) {
    die('Direct access not permitted');
}

// ============================================
// ENVIRONMENT SETTINGS
// ============================================

// Set environment (development/production)
define('ENVIRONMENT', 'development'); // TODO: Change to 'production' before launch

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ============================================
// PATH SETTINGS
// ============================================

// Base path (absolute server path)
define('BASE_PATH', dirname(__DIR__) . '/');

// Database path (all SQLite databases stored here)
define('DB_PATH', BASE_PATH . 'databases/');

// Logs path
define('LOGS_PATH', BASE_PATH . 'logs/');

// Temp path for temporary files
define('TEMP_PATH', BASE_PATH . 'temp/');

// ============================================
// URL SETTINGS
// ============================================

// Base URL (with trailing slash)
define('BASE_URL', 'https://vpn.the-truth-publishing.com/');

// API URL
define('API_URL', BASE_URL . 'api/');

// Assets URL
define('ASSETS_URL', BASE_URL . 'assets/');

// ============================================
// DATABASE FILES
// ============================================

// Database file names (all in /databases/ folder)
define('DB_USERS', DB_PATH . 'users.db');
define('DB_DEVICES', DB_PATH . 'devices.db');
define('DB_SERVERS', DB_PATH . 'servers.db');
define('DB_BILLING', DB_PATH . 'billing.db');
define('DB_PORT_FORWARDS', DB_PATH . 'port_forwards.db');
define('DB_PARENTAL_CONTROLS', DB_PATH . 'parental_controls.db');
define('DB_ADMIN', DB_PATH . 'admin.db');
define('DB_LOGS', DB_PATH . 'logs.db');

// ============================================
// SESSION SETTINGS
// ============================================

// Session name
define('SESSION_NAME', 'truevault_session');

// Session timeout (7 days in seconds)
define('SESSION_LIFETIME', 604800);

// ============================================
// SECURITY SETTINGS
// ============================================

// JWT Secret Key (IMPORTANT: Change this to a random string!)
// Generate one at: https://randomkeygen.com/
define('JWT_SECRET', 'CHANGE_THIS_TO_RANDOM_STRING'); // TODO: Change before launch!

// JWT Expiration (7 days in seconds)
define('JWT_EXPIRATION', 604800);

// Password hash cost (bcrypt cost factor - higher = more secure but slower)
define('PASSWORD_COST', 12);

// Max login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);

// Lockout duration (15 minutes in seconds)
define('LOCKOUT_DURATION', 900);

// ============================================
// RATE LIMITING
// ============================================

// Rate limits by user tier (requests per minute)
define('RATE_LIMIT_STANDARD', 30);
define('RATE_LIMIT_PRO', 60);
define('RATE_LIMIT_VIP', 120);
define('RATE_LIMIT_ADMIN', 999);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================

// Max file upload size (10 MB in bytes)
define('MAX_UPLOAD_SIZE', 10485760);

// Allowed file extensions for uploads
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip']);

// ============================================
// EMAIL SETTINGS
// ============================================

// From email address
define('EMAIL_FROM', 'noreply@vpn.the-truth-publishing.com');

// From name
define('EMAIL_FROM_NAME', 'TrueVault VPN');

// Support email
define('EMAIL_SUPPORT', 'support@vpn.the-truth-publishing.com');

// ============================================
// WIREGUARD SETTINGS
// ============================================

// WireGuard port
define('WIREGUARD_PORT', 51820);

// WireGuard network (subnet for clients)
define('WIREGUARD_NETWORK', '10.8.0.0/24');

// ============================================
// APPLICATION SETTINGS
// ============================================

// Application name
define('APP_NAME', 'TrueVault VPN');

// Application version
define('APP_VERSION', '1.0.0');

// Timezone
define('TIMEZONE', 'America/Chicago');
date_default_timezone_set(TIMEZONE);

// ============================================
// DEBUG SETTINGS
// ============================================

// Enable debug mode (only in development)
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Debug log file
define('DEBUG_LOG', LOGS_PATH . 'debug.log');

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get database connection
 * 
 * @param string $dbFile Full path to database file
 * @return PDO Database connection
 */
function getDatabase($dbFile) {
    try {
        // Check if database file exists
        if (!file_exists($dbFile)) {
            throw new Exception("Database file not found: " . basename($dbFile));
        }
        
        // Create PDO connection
        $pdo = new PDO('sqlite:' . $dbFile);
        
        // Set error mode to exceptions
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON');
        
        return $pdo;
        
    } catch (Exception $e) {
        // Log error
        error_log("Database connection error: " . $e->getMessage());
        
        // Show user-friendly error in development
        if (DEBUG_MODE) {
            die("Database Error: " . $e->getMessage());
        } else {
            die("Database connection failed. Please contact support.");
        }
    }
}

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($message, $context = []) {
    $logFile = LOGS_PATH . 'error.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'file' => $context['file'] ?? 'unknown',
        'line' => $context['line'] ?? 0,
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND);
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $status HTTP status code
 */
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ============================================
// AUTOLOADER
// ============================================

/**
 * Autoload classes from /includes/ folder
 */
spl_autoload_register(function($class) {
    $file = BASE_PATH . 'includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================
// INITIALIZATION
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '.the-truth-publishing.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Create necessary directories if they don't exist
$dirs = [LOGS_PATH, TEMP_PATH];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Mark config as loaded
define('CONFIG_LOADED', true);

?>
```

**Verification:**
- [ ] config.php file uploaded to /configs/
- [ ] No syntax errors (check error log)
- [ ] Folders auto-created (logs, temp)

---

#### Task 1.4: Create Database Initialization Security
- [ ] Create file: `/databases/.htaccess`
- [ ] Add this code:

```apache
# Protect database files from direct access
Order Deny,Allow
Deny from all
```

**Verification:**
- [ ] .htaccess file in /databases/ folder
- [ ] Cannot access database files directly in browser

---

### **Afternoon: Database Creation (3-4 hours)**

#### Task 1.5: Create Database Setup Script
- [ ] Create file: `/admin/setup-databases.php`
- [ ] Add this code:

```php
<?php
/**
 * TrueVault VPN - Database Setup Script
 * 
 * PURPOSE: Creates all 8 SQLite databases with proper schemas
 * RUN ONCE: This should only be run during initial setup
 * 
 * @created January 2026
 * @version 1.0.0
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
        <p>This script will create all 8 SQLite databases with proper schemas.</p>

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
    $db = new PDO('sqlite:' . DB_USERS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    
    echo '<div class="success">✅ users.db created successfully!</div>';
    $results['users.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
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
    
    $db = new PDO('sqlite:' . DB_DEVICES);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    
    echo '<div class="success">✅ devices.db created successfully!</div>';
    $results['devices.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
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
    
    $db = new PDO('sqlite:' . DB_SERVERS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
            'vip_only' => 0
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
            'vip_only' => 0
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
            'vip_only' => 0
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO servers (name, location, country_code, endpoint, public_key, private_key_encrypted, 
                            ip_pool_start, ip_pool_end, vip_only, dedicated_user_email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($servers as $server) {
        $stmt->execute([
            $server['name'],
            $server['location'],
            $server['country_code'],
            $server['endpoint'],
            $server['public_key'],
            $server['private_key_encrypted'],
            $server['ip_pool_start'],
            $server['ip_pool_end'],
            $server['vip_only'],
            $server['dedicated_user_email'] ?? null
        ]);
    }
    
    echo '<div class="success">✅ servers.db created with 4 servers!</div>';
    $results['servers.db'] = 'success';
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    $results['servers.db'] = 'error';
}

echo '</div>';

// ============================================
// CONTINUE IN PART 2...
// ============================================

echo '<div class="info">';
echo '<h3>Setup Progress:</h3>';
echo '<ul>';
foreach ($results as $db => $status) {
    $icon = $status === 'success' ? '✅' : '❌';
    echo "<li>$icon $db</li>";
}
echo '</ul>';
echo '</div>';

?>

<p><strong>Next Step:</strong> Continue database setup in the admin panel.</p>

</div>
</body>
</html>
```

**Verification:**
- [ ] setup-databases.php file uploaded to /admin/
- [ ] Visit: https://vpn.the-truth-publishing.com/admin/setup-databases.php
- [ ] See setup page load without errors
- [ ] DON'T run it yet - we'll do that in the next task

---

**END OF DAY 1 MORNING/AFTERNOON TASKS**

**Before Moving to Day 2:**
- [ ] All folders created
- [ ] .htaccess files in place
- [ ] config.php uploaded and tested
- [ ] setup-databases.php ready (not run yet)
- [ ] Commit to GitHub: "Day 1 Complete - Project structure and config"

---

## DAY 2: DATABASE SETUP COMPLETION (Tuesday)

*To be continued in MASTER_CHECKLIST_PART2.md...*

---

**Status:** Part 1 of 4 Complete  
**Next:** Part 2 will cover remaining database setup and core features  
**Lines:** ~800 lines so far  
**Created:** January 15, 2026 - 7:45 AM CST
