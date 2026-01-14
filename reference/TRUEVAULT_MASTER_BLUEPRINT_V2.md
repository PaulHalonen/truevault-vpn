# TRUEVAULT VPN - MASTER BLUEPRINT V2
## Complete System Architecture with Real Code
**Created:** January 13, 2026 - 11:15 PM CST
**Version:** 2.0 - No Placeholders Edition

---

# TABLE OF CONTENTS

1. [System Overview](#1-system-overview)
2. [Infrastructure Details](#2-infrastructure-details)
3. [Database Schemas](#3-database-schemas)
4. [API Specifications](#4-api-specifications)
5. [Automation Workflows](#5-automation-workflows)
6. [Frontend Architecture](#6-frontend-architecture)
7. [VPN Server Integration](#7-vpn-server-integration)
8. [Certificate System](#8-certificate-system)
9. [Camera Integration](#9-camera-integration)
10. [Security Implementation](#10-security-implementation)

---

# 1. SYSTEM OVERVIEW

## What TrueVault VPN Does

TrueVault VPN is a next-generation VPN service with these unique features:

1. **Smart Identity Router** - Persistent regional identities (same IP every time)
2. **Personal Certificate Authority** - Each user gets their own PKI
3. **Mesh Networking** - Family/team private overlay network
4. **Camera Dashboard** - Discover and control IP cameras
5. **Network Scanner** - Find all devices on home network
6. **VIP System** - Dedicated servers for premium users

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER'S BROWSER                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                 │
│  │ Login/Reg   │  │ Dashboard   │  │ Admin Panel │                 │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘                 │
└─────────┼────────────────┼────────────────┼─────────────────────────┘
          │                │                │
          ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    WEB SERVER (GoDaddy)                             │
│            vpn.the-truth-publishing.com                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                      PHP API LAYER                           │   │
│  │  /api/auth/     - Login, Register, JWT                       │   │
│  │  /api/vpn/      - Connect, Servers, Config                   │   │
│  │  /api/billing/  - PayPal, Subscriptions                      │   │
│  │  /api/devices/  - Scanner sync, Cameras                      │   │
│  │  /api/admin/    - User/Server management                     │   │
│  └─────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                   SQLite DATABASES                           │   │
│  │  users.db │ servers.db │ themes.db │ billing.db │ vip.db    │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
          │                │                │
          ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      VPN SERVER CLUSTER                             │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐           │
│  │ Contabo US-E  │  │ Contabo US-C  │  │ Fly.io Dallas │           │
│  │ 66.94.103.91  │  │144.126.133.253│  │ 66.241.124.4  │           │
│  │   (Shared)    │  │  (VIP ONLY)   │  │   (Shared)    │           │
│  │  WireGuard    │  │  WireGuard    │  │  WireGuard    │           │
│  │  peer_api.py  │  │  peer_api.py  │  │  peer_api.py  │           │
│  └───────────────┘  └───────────────┘  └───────────────┘           │
└─────────────────────────────────────────────────────────────────────┘
```

---

# 2. INFRASTRUCTURE DETAILS

## 2.1 Web Hosting (GoDaddy)

```
cPanel URL: https://the-truth-publishing.com:2083
Username: 26853687
Password: Asasasas4!

Document Root: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com
PHP Version: 8.2
```

## 2.2 FTP Access

```php
// Connection settings for deployment scripts
$ftp_config = [
    'host' => 'the-truth-publishing.com',
    'user' => 'kahlen@the-truth-publishing.com',
    'pass' => 'AndassiAthena8',
    'port' => 21,
    'remote_path' => '/public_html/vpn.the-truth-publishing.com'
];
```

## 2.3 VPN Servers - Complete Details

### Server 1: Contabo US-East (SHARED)
```php
$server1 = [
    'id' => 1,
    'name' => 'US East (New York)',
    'provider' => 'contabo',
    'region' => 'us-east',
    'ip' => '66.94.103.91',
    'ipv6' => '2605:a142:2299:0026:0000:0000:0000:0001',
    'wireguard_port' => 51820,
    'api_port' => 8080,
    'ssh_user' => 'root',
    'vnc' => '154.53.39.97:63031',
    'type' => 'shared',
    'bandwidth_limit' => true,
    'max_connections' => 50
];
```

### Server 2: Contabo US-Central (VIP DEDICATED)
```php
$server2 = [
    'id' => 2,
    'name' => 'US Central (St. Louis) - VIP',
    'provider' => 'contabo',
    'region' => 'us-central',
    'ip' => '144.126.133.253',
    'ipv6' => '2605:a140:2299:0005:0000:0000:0000:0001',
    'wireguard_port' => 51820,
    'api_port' => 8080,
    'ssh_user' => 'root',
    'vnc' => '207.244.248.38:63098',
    'type' => 'vip_dedicated',
    'vip_user' => 'seige235@yahoo.com',
    'bandwidth_limit' => false,
    'max_connections' => 1
];
```

### Server 3: Fly.io Dallas (SHARED)
```php
$server3 = [
    'id' => 3,
    'name' => 'US South (Dallas)',
    'provider' => 'fly.io',
    'region' => 'us-south',
    'ip' => '66.241.124.4',
    'release_ip' => '137.66.58.225',
    'wireguard_port' => 51820,
    'api_port' => 8443,
    'type' => 'shared',
    'bandwidth_limit' => true,
    'max_connections' => 50
];
```

### Server 4: Fly.io Toronto (SHARED)
```php
$server4 = [
    'id' => 4,
    'name' => 'Canada (Toronto)',
    'provider' => 'fly.io',
    'region' => 'ca-east',
    'ip' => '66.241.125.247',
    'release_ip' => '37.16.6.139',
    'wireguard_port' => 51820,
    'api_port' => 8080,
    'type' => 'shared',
    'bandwidth_limit' => true,
    'max_connections' => 50
];
```

## 2.4 PayPal Integration

```php
// PayPal API Configuration - LIVE CREDENTIALS
define('PAYPAL_CLIENT_ID', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk');
define('PAYPAL_SECRET', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN');
define('PAYPAL_MODE', 'live'); // 'sandbox' or 'live'
define('PAYPAL_BUSINESS_EMAIL', 'paulhalonen@gmail.com');
define('PAYPAL_WEBHOOK_ID', '46924926WL757580D');
define('PAYPAL_WEBHOOK_URL', 'https://builder.the-truth-publishing.com/api/paypal-webhook.php');

// PayPal API Base URLs
define('PAYPAL_API_URL', PAYPAL_MODE === 'live' 
    ? 'https://api-m.paypal.com' 
    : 'https://api-m.sandbox.paypal.com'
);

// Pricing Plans
$plans = [
    'personal' => [
        'name' => 'Personal',
        'price' => 9.99,
        'devices' => 3,
        'identities' => 3,
        'mesh_users' => 0,
        'features' => ['Smart Routing', '24/7 Support', 'Personal Certificates']
    ],
    'family' => [
        'name' => 'Family',
        'price' => 14.99,
        'devices' => 'unlimited',
        'identities' => 'all',
        'mesh_users' => 6,
        'features' => ['Everything in Personal', 'Mesh Networking', 'Priority Support']
    ],
    'business' => [
        'name' => 'Business',
        'price' => 29.99,
        'devices' => 'unlimited',
        'identities' => 'all',
        'mesh_users' => 25,
        'features' => ['Everything in Family', 'Admin Dashboard', 'API Access', 'Dedicated Support']
    ]
];
```

## 2.5 VIP System

```php
// VIP User Configuration
$vip_users = [
    [
        'email' => 'seige235@yahoo.com',
        'tier' => 'vip_dedicated',
        'dedicated_server_id' => 2,
        'dedicated_server_ip' => '144.126.133.253',
        'privileges' => [
            'bypass_payment' => true,
            'unlimited_devices' => true,
            'unlimited_cameras' => true,
            'unlimited_bandwidth' => true,
            'priority_support' => true,
            'all_features' => true
        ]
    ]
];

// VIP Check Function
function isVIP($email) {
    $vip_list = ['seige235@yahoo.com'];
    return in_array(strtolower($email), $vip_list);
}

// Get VIP Server
function getVIPServer($email) {
    if (strtolower($email) === 'seige235@yahoo.com') {
        return '144.126.133.253';
    }
    return null;
}
```

---

# 3. DATABASE SCHEMAS

## 3.1 Users Database (users.db)

```sql
-- Main users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('pending', 'active', 'suspended', 'cancelled')),
    plan_type TEXT DEFAULT 'free' CHECK(plan_type IN ('free', 'personal', 'family', 'business')),
    is_vip INTEGER DEFAULT 0,
    email_verified INTEGER DEFAULT 0,
    two_factor_enabled INTEGER DEFAULT 0,
    two_factor_secret TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- User settings (key-value pairs)
CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, setting_key)
);

-- User devices
CREATE TABLE IF NOT EXISTS user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_uuid TEXT UNIQUE NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT DEFAULT 'unknown',
    public_key TEXT,
    private_key_encrypted TEXT,
    is_active INTEGER DEFAULT 1,
    last_connected DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    verified INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_uuid ON users(uuid);
CREATE INDEX IF NOT EXISTS idx_user_devices_user ON user_devices(user_id);
```

## 3.2 VPN Servers Database (servers.db)

```sql
CREATE TABLE IF NOT EXISTS vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    provider TEXT NOT NULL,
    region TEXT NOT NULL,
    country TEXT NOT NULL,
    city TEXT,
    ip_address TEXT NOT NULL,
    ipv6_address TEXT,
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT,
    private_key_encrypted TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'maintenance', 'offline')),
    server_type TEXT DEFAULT 'shared' CHECK(server_type IN ('shared', 'vip_dedicated', 'premium')),
    vip_user_email TEXT,
    max_connections INTEGER DEFAULT 100,
    current_connections INTEGER DEFAULT 0,
    bandwidth_limit_mbps INTEGER,
    monthly_bandwidth_gb INTEGER,
    current_bandwidth_gb REAL DEFAULT 0,
    last_health_check DATETIME,
    health_status TEXT DEFAULT 'unknown',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert the 4 servers
INSERT OR REPLACE INTO vpn_servers (id, name, provider, region, country, city, ip_address, ipv6_address, wireguard_port, api_port, server_type, vip_user_email, max_connections) VALUES
(1, 'US East (New York)', 'contabo', 'us-east', 'USA', 'New York', '66.94.103.91', '2605:a142:2299:0026:0000:0000:0000:0001', 51820, 8080, 'shared', NULL, 50),
(2, 'US Central (St. Louis) - VIP', 'contabo', 'us-central', 'USA', 'St. Louis', '144.126.133.253', '2605:a140:2299:0005:0000:0000:0000:0001', 51820, 8080, 'vip_dedicated', 'seige235@yahoo.com', 1),
(3, 'US South (Dallas)', 'fly.io', 'us-south', 'USA', 'Dallas', '66.241.124.4', NULL, 51820, 8443, 'shared', NULL, 50),
(4, 'Canada (Toronto)', 'fly.io', 'ca-east', 'Canada', 'Toronto', '66.241.125.247', NULL, 51820, 8080, 'shared', NULL, 50);
```

## 3.3 VIP Database (vip.db)

```sql
CREATE TABLE IF NOT EXISTS vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    tier TEXT NOT NULL DEFAULT 'vip' CHECK(tier IN ('vip', 'vip_dedicated', 'vip_unlimited')),
    dedicated_server_id INTEGER,
    dedicated_server_ip TEXT,
    max_devices INTEGER DEFAULT -1,
    max_cameras INTEGER DEFAULT -1,
    bypass_payment INTEGER DEFAULT 1,
    notes TEXT,
    activated_at DATETIME,
    activated_by_user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert the VIP user
INSERT OR REPLACE INTO vip_users (email, tier, dedicated_server_id, dedicated_server_ip, max_devices, max_cameras, bypass_payment, notes) VALUES
('seige235@yahoo.com', 'vip_dedicated', 2, '144.126.133.253', -1, -1, 1, 'Dedicated VIP user with exclusive server access');
```

## 3.4 Themes Database (themes.db) - CRITICAL FOR STYLING

```sql
CREATE TABLE IF NOT EXISTS themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    is_active INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS theme_variables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    variable_name TEXT NOT NULL,
    variable_value TEXT NOT NULL,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE(theme_id, category, variable_name)
);

-- Insert default theme
INSERT OR REPLACE INTO themes (id, name, slug, is_active) VALUES (1, 'TrueVault Dark', 'truevault-dark', 1);

-- Insert theme variables (colors)
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
(1, 'colors', 'primary', '#00d9ff'),
(1, 'colors', 'secondary', '#00ff88'),
(1, 'colors', 'accent', '#ff6b6b'),
(1, 'colors', 'background', '#0f0f1a'),
(1, 'colors', 'background-secondary', '#1a1a2e'),
(1, 'colors', 'background-tertiary', '#252540'),
(1, 'colors', 'text', '#ffffff'),
(1, 'colors', 'text-muted', '#888888'),
(1, 'colors', 'success', '#00ff88'),
(1, 'colors', 'warning', '#ffbb00'),
(1, 'colors', 'error', '#ff5050'),
(1, 'colors', 'border', 'rgba(255,255,255,0.08)');

-- Insert theme variables (gradients)
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
(1, 'gradients', 'primary', 'linear-gradient(90deg, #00d9ff, #00ff88)'),
(1, 'gradients', 'background', 'linear-gradient(135deg, #0f0f1a, #1a1a2e)');

-- Insert theme variables (typography)
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
(1, 'typography', 'font-family', 'Inter, -apple-system, BlinkMacSystemFont, sans-serif'),
(1, 'typography', 'font-size-base', '16px'),
(1, 'typography', 'line-height', '1.5'),
(1, 'typography', 'heading-font', 'Inter, sans-serif');

-- Insert theme variables (buttons)
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
(1, 'buttons', 'border-radius', '8px'),
(1, 'buttons', 'padding', '10px 20px'),
(1, 'buttons', 'font-weight', '600');

-- Insert theme variables (cards)
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
(1, 'cards', 'border-radius', '14px'),
(1, 'cards', 'padding', '20px'),
(1, 'cards', 'background', 'rgba(255,255,255,0.03)');
```

## 3.5 Billing Database (billing.db)

```sql
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_type TEXT NOT NULL CHECK(plan_type IN ('personal', 'family', 'business')),
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'cancelled', 'expired', 'grace_period', 'suspended')),
    paypal_subscription_id TEXT,
    price REAL NOT NULL,
    billing_cycle TEXT DEFAULT 'monthly',
    current_period_start DATETIME,
    current_period_end DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    paypal_order_id TEXT,
    paypal_capture_id TEXT,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'failed', 'refunded')),
    payment_method TEXT DEFAULT 'paypal',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    payment_id INTEGER,
    invoice_number TEXT UNIQUE NOT NULL,
    amount REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'sent', 'paid', 'overdue', 'cancelled')),
    due_date DATE,
    paid_at DATETIME,
    pdf_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 3.6 Devices Database (devices.db)

```sql
CREATE TABLE IF NOT EXISTS discovered_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    mac_address TEXT,
    hostname TEXT,
    vendor TEXT,
    device_type TEXT DEFAULT 'unknown',
    type_name TEXT,
    icon TEXT DEFAULT '❓',
    open_ports TEXT,
    is_camera INTEGER DEFAULT 0,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, mac_address)
);

CREATE TABLE IF NOT EXISTS cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER,
    name TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    mac_address TEXT,
    vendor TEXT,
    model TEXT,
    rtsp_port INTEGER DEFAULT 554,
    http_port INTEGER DEFAULT 80,
    https_port INTEGER DEFAULT 443,
    username TEXT,
    password_encrypted TEXT,
    stream_url TEXT,
    snapshot_url TEXT,
    supports_ptz INTEGER DEFAULT 0,
    supports_audio INTEGER DEFAULT 0,
    supports_motion INTEGER DEFAULT 0,
    is_online INTEGER DEFAULT 1,
    last_seen DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES discovered_devices(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS camera_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    camera_id INTEGER NOT NULL,
    event_type TEXT NOT NULL CHECK(event_type IN ('motion', 'sound', 'person', 'vehicle', 'offline', 'online')),
    event_data TEXT,
    thumbnail_path TEXT,
    video_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES cameras(id) ON DELETE CASCADE
);
```

## 3.7 Mesh Network Database (mesh.db)

```sql
CREATE TABLE IF NOT EXISTS mesh_networks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    max_members INTEGER DEFAULT 6,
    network_key TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mesh_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    user_id INTEGER,
    email TEXT NOT NULL,
    role TEXT DEFAULT 'member' CHECK(role IN ('owner', 'admin', 'member')),
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'active', 'suspended')),
    permissions TEXT DEFAULT 'full',
    public_key TEXT,
    joined_at DATETIME,
    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS mesh_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    invite_code TEXT UNIQUE NOT NULL,
    permissions TEXT DEFAULT 'full',
    expires_at DATETIME NOT NULL,
    accepted INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS shared_resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    network_id INTEGER NOT NULL,
    owner_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('printer', 'camera', 'storage', 'device', 'other')),
    local_ip TEXT,
    local_port INTEGER,
    access_level TEXT DEFAULT 'view' CHECK(access_level IN ('view', 'use', 'full')),
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

# 4. API SPECIFICATIONS

## 4.1 Database Helper (api/config/database.php)

```php
<?php
/**
 * TrueVault VPN - Database Connection Manager
 * Handles SQLite database connections with separate files
 */

class Database {
    private static $connections = [];
    private static $basePath = null;
    
    // Database file mapping
    private static $dbFiles = [
        'users' => 'core/users.db',
        'sessions' => 'core/sessions.db',
        'admin' => 'core/admin.db',
        'vip' => 'core/vip.db',
        'servers' => 'vpn/servers.db',
        'connections' => 'vpn/connections.db',
        'certificates' => 'vpn/certificates.db',
        'identities' => 'vpn/identities.db',
        'devices' => 'devices/discovered.db',
        'cameras' => 'devices/cameras.db',
        'mesh' => 'devices/mesh.db',
        'billing' => 'billing/billing.db',
        'subscriptions' => 'billing/subscriptions.db',
        'themes' => 'cms/themes.db',
        'pages' => 'cms/pages.db',
        'templates' => 'cms/templates.db',
        'automation' => 'automation/workflows.db',
        'logs' => 'automation/logs.db'
    ];
    
    /**
     * Get database base path
     */
    private static function getBasePath() {
        if (self::$basePath === null) {
            self::$basePath = dirname(dirname(__DIR__)) . '/databases/';
        }
        return self::$basePath;
    }
    
    /**
     * Get database connection
     */
    public static function getConnection($name) {
        if (!isset(self::$dbFiles[$name])) {
            throw new Exception("Unknown database: $name");
        }
        
        if (!isset(self::$connections[$name])) {
            $path = self::getBasePath() . self::$dbFiles[$name];
            
            // Create directory if it doesn't exist
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            try {
                $pdo = new PDO("sqlite:$path");
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->exec("PRAGMA foreign_keys = ON");
                self::$connections[$name] = $pdo;
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$connections[$name];
    }
    
    /**
     * Execute query and return all results
     */
    public static function query($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute query and return single result
     */
    public static function queryOne($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute insert/update/delete
     */
    public static function execute($dbName, $sql, $params = []) {
        $db = self::getConnection($dbName);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId($dbName) {
        return self::getConnection($dbName)->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction($dbName) {
        return self::getConnection($dbName)->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit($dbName) {
        return self::getConnection($dbName)->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback($dbName) {
        return self::getConnection($dbName)->rollBack();
    }
}
```

## 4.2 VIP Manager (api/helpers/vip.php)

```php
<?php
/**
 * TrueVault VPN - VIP User Management
 * Handles VIP status, privileges, and server routing
 */

require_once __DIR__ . '/../config/database.php';

class VIPManager {
    
    /**
     * Check if email is a VIP user
     */
    public static function isVIP($email) {
        $email = strtolower(trim($email));
        
        try {
            $vip = Database::queryOne('vip',
                "SELECT * FROM vip_users WHERE LOWER(email) = ?",
                [$email]
            );
            return $vip !== false;
        } catch (Exception $e) {
            // Fallback to hardcoded list if DB fails
            $vipList = ['seige235@yahoo.com'];
            return in_array($email, $vipList);
        }
    }
    
    /**
     * Get VIP user details
     */
    public static function getVIPDetails($email) {
        $email = strtolower(trim($email));
        
        try {
            return Database::queryOne('vip',
                "SELECT * FROM vip_users WHERE LOWER(email) = ?",
                [$email]
            );
        } catch (Exception $e) {
            // Fallback for seige235@yahoo.com
            if ($email === 'seige235@yahoo.com') {
                return [
                    'email' => 'seige235@yahoo.com',
                    'tier' => 'vip_dedicated',
                    'dedicated_server_id' => 2,
                    'dedicated_server_ip' => '144.126.133.253',
                    'max_devices' => -1,
                    'max_cameras' => -1,
                    'bypass_payment' => 1
                ];
            }
            return null;
        }
    }
    
    /**
     * Get VIP tier type
     */
    public static function getVIPType($email) {
        $vip = self::getVIPDetails($email);
        return $vip ? $vip['tier'] : null;
    }
    
    /**
     * Get VIP limits
     */
    public static function getVIPLimits($email) {
        $vip = self::getVIPDetails($email);
        
        if (!$vip) {
            return null;
        }
        
        return [
            'tier' => $vip['tier'],
            'max_devices' => $vip['max_devices'] == -1 ? 'unlimited' : $vip['max_devices'],
            'max_cameras' => $vip['max_cameras'] == -1 ? 'unlimited' : $vip['max_cameras'],
            'bypass_payment' => (bool)$vip['bypass_payment'],
            'dedicated_server_id' => $vip['dedicated_server_id'],
            'dedicated_server_ip' => $vip['dedicated_server_ip'],
            'badge' => $vip['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP'
        ];
    }
    
    /**
     * Activate VIP status for user
     */
    public static function activateVIP($email, $userId, $firstName, $lastName) {
        $email = strtolower(trim($email));
        
        Database::execute('vip',
            "UPDATE vip_users SET activated_at = datetime('now'), activated_by_user_id = ? WHERE LOWER(email) = ?",
            [$userId, $email]
        );
        
        // Update user record
        Database::execute('users',
            "UPDATE users SET is_vip = 1, plan_type = 'business' WHERE id = ?",
            [$userId]
        );
    }
}

/**
 * Plan Limits Manager
 */
class PlanLimits {
    
    private static $plans = [
        'free' => [
            'max_devices' => 1,
            'max_cameras' => 0,
            'max_identities' => 1,
            'mesh_enabled' => false,
            'mesh_users' => 0,
            'bandwidth_limit' => true,
            'features' => ['basic_vpn']
        ],
        'personal' => [
            'max_devices' => 3,
            'max_cameras' => 3,
            'max_identities' => 3,
            'mesh_enabled' => false,
            'mesh_users' => 0,
            'bandwidth_limit' => false,
            'features' => ['smart_routing', 'personal_certs', 'support_247']
        ],
        'family' => [
            'max_devices' => -1, // unlimited
            'max_cameras' => 10,
            'max_identities' => -1,
            'mesh_enabled' => true,
            'mesh_users' => 6,
            'bandwidth_limit' => false,
            'features' => ['smart_routing', 'personal_certs', 'mesh_network', 'priority_support']
        ],
        'business' => [
            'max_devices' => -1,
            'max_cameras' => -1,
            'max_identities' => -1,
            'mesh_enabled' => true,
            'mesh_users' => 25,
            'bandwidth_limit' => false,
            'features' => ['all_features', 'admin_dashboard', 'api_access', 'dedicated_support']
        ]
    ];
    
    public static function getPlan($planType) {
        return self::$plans[$planType] ?? self::$plans['free'];
    }
    
    public static function checkDeviceLimit($userId, $planType, $isVIP = false) {
        if ($isVIP) return true; // VIP = unlimited
        
        $plan = self::getPlan($planType);
        if ($plan['max_devices'] === -1) return true;
        
        $count = Database::queryOne('users',
            "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1",
            [$userId]
        );
        
        return ($count['count'] ?? 0) < $plan['max_devices'];
    }
    
    public static function checkCameraLimit($userId, $planType, $isVIP = false) {
        if ($isVIP) return true;
        
        $plan = self::getPlan($planType);
        if ($plan['max_cameras'] === -1) return true;
        
        $count = Database::queryOne('devices',
            "SELECT COUNT(*) as count FROM cameras WHERE user_id = ?",
            [$userId]
        );
        
        return ($count['count'] ?? 0) < $plan['max_cameras'];
    }
}

/**
 * Server Routing Rules
 */
class ServerRules {
    
    /**
     * Get available servers for a user
     */
    public static function getAvailableServers($email) {
        $isVIP = VIPManager::isVIP($email);
        $vipDetails = $isVIP ? VIPManager::getVIPDetails($email) : null;
        
        // Get all servers
        $servers = Database::query('servers',
            "SELECT * FROM vpn_servers WHERE status = 'active' ORDER BY region"
        );
        
        $available = [];
        
        foreach ($servers as $server) {
            // VIP dedicated servers
            if ($server['server_type'] === 'vip_dedicated') {
                // Only show to the assigned VIP user
                if ($isVIP && $vipDetails && 
                    strtolower($vipDetails['email']) === strtolower($server['vip_user_email'])) {
                    $server['is_dedicated'] = true;
                    $server['priority'] = 1; // Show first
                    $available[] = $server;
                }
                continue;
            }
            
            // Shared servers available to all
            $server['is_dedicated'] = false;
            $server['priority'] = 2;
            $available[] = $server;
        }
        
        // Sort by priority (dedicated first)
        usort($available, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return $available;
    }
    
    /**
     * Get the best server for a user
     */
    public static function getBestServer($email, $preferredRegion = null) {
        $servers = self::getAvailableServers($email);
        
        if (empty($servers)) {
            return null;
        }
        
        // If VIP has dedicated server, always use it
        $isVIP = VIPManager::isVIP($email);
        if ($isVIP) {
            foreach ($servers as $server) {
                if ($server['is_dedicated']) {
                    return $server;
                }
            }
        }
        
        // Otherwise find best match
        if ($preferredRegion) {
            foreach ($servers as $server) {
                if ($server['region'] === $preferredRegion) {
                    return $server;
                }
            }
        }
        
        // Return first available (lowest load)
        return $servers[0];
    }
}
```

## 4.3 Theme API (api/theme/index.php)

```php
<?php
/**
 * TrueVault VPN - Theme API
 * Returns theme variables from database for CSS
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

try {
    // Get active theme
    $theme = Database::queryOne('themes',
        "SELECT * FROM themes WHERE is_active = 1 LIMIT 1"
    );
    
    if (!$theme) {
        Response::error('No active theme found', 404);
    }
    
    // Get all variables for this theme
    $variables = Database::query('themes',
        "SELECT category, variable_name, variable_value FROM theme_variables WHERE theme_id = ?",
        [$theme['id']]
    );
    
    // Organize by category
    $organized = [];
    foreach ($variables as $var) {
        if (!isset($organized[$var['category']])) {
            $organized[$var['category']] = [];
        }
        $organized[$var['category']][$var['variable_name']] = $var['variable_value'];
    }
    
    Response::success([
        'id' => $theme['id'],
        'name' => $theme['name'],
        'slug' => $theme['slug'],
        'variables' => $organized
    ]);
    
} catch (Exception $e) {
    Response::serverError('Failed to load theme: ' . $e->getMessage());
}
```

---

# 5. AUTOMATION WORKFLOWS

## 5.1 Workflow Engine Architecture

```php
<?php
/**
 * TrueVault VPN - Automation Engine
 * Processes automated workflows with no placeholders
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/mailer.php';

class AutomationEngine {
    
    private static $workflows = [
        'new_user_signup' => [
            'name' => 'New User Signup',
            'steps' => [
                ['action' => 'send_email', 'template' => 'welcome', 'delay' => 0],
                ['action' => 'create_identities', 'count' => 3, 'delay' => 0],
                ['action' => 'generate_scanner_token', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'getting_started', 'delay' => 3600],
                ['action' => 'send_email', 'template' => 'tips', 'delay' => 86400]
            ]
        ],
        'payment_success' => [
            'name' => 'Payment Success',
            'steps' => [
                ['action' => 'update_subscription', 'status' => 'active', 'delay' => 0],
                ['action' => 'generate_invoice', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'payment_receipt', 'delay' => 0],
                ['action' => 'log_event', 'type' => 'payment_success', 'delay' => 0]
            ]
        ],
        'payment_failed' => [
            'name' => 'Payment Failed',
            'steps' => [
                ['action' => 'update_subscription', 'status' => 'grace_period', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'payment_failed_day0', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'payment_failed_day3', 'delay' => 259200],
                ['action' => 'send_email', 'template' => 'payment_failed_day7', 'delay' => 604800],
                ['action' => 'suspend_user', 'delay' => 691200]
            ]
        ],
        'vpn_connection' => [
            'name' => 'VPN Connection',
            'steps' => [
                ['action' => 'check_subscription', 'delay' => 0],
                ['action' => 'check_device_limit', 'delay' => 0],
                ['action' => 'select_server', 'delay' => 0],
                ['action' => 'add_wireguard_peer', 'delay' => 0],
                ['action' => 'generate_config', 'delay' => 0],
                ['action' => 'record_connection', 'delay' => 0]
            ]
        ],
        'scanner_sync' => [
            'name' => 'Scanner Sync',
            'steps' => [
                ['action' => 'validate_token', 'delay' => 0],
                ['action' => 'process_devices', 'delay' => 0],
                ['action' => 'detect_cameras', 'delay' => 0],
                ['action' => 'update_database', 'delay' => 0],
                ['action' => 'return_summary', 'delay' => 0]
            ]
        ],
        'subscription_expiring' => [
            'name' => 'Subscription Expiring',
            'steps' => [
                ['action' => 'send_email', 'template' => 'subscription_expiring_7days', 'delay' => 0],
                ['action' => 'send_email', 'template' => 'subscription_expiring_1day', 'delay' => 518400],
                ['action' => 'check_auto_renew', 'delay' => 604800]
            ]
        ]
    ];
    
    /**
     * Trigger a workflow
     */
    public static function trigger($workflowName, $context) {
        if (!isset(self::$workflows[$workflowName])) {
            throw new Exception("Unknown workflow: $workflowName");
        }
        
        $workflow = self::$workflows[$workflowName];
        $executionId = self::createExecution($workflowName, $context);
        
        foreach ($workflow['steps'] as $index => $step) {
            if ($step['delay'] > 0) {
                // Schedule for later
                self::scheduleStep($executionId, $index, $step, $context);
            } else {
                // Execute immediately
                self::executeStep($step, $context);
            }
        }
        
        return $executionId;
    }
    
    /**
     * Execute a single step
     */
    public static function executeStep($step, $context) {
        $action = $step['action'];
        
        switch ($action) {
            case 'send_email':
                return self::actionSendEmail($step['template'], $context);
                
            case 'update_subscription':
                return self::actionUpdateSubscription($step['status'], $context);
                
            case 'generate_invoice':
                return self::actionGenerateInvoice($context);
                
            case 'check_subscription':
                return self::actionCheckSubscription($context);
                
            case 'check_device_limit':
                return self::actionCheckDeviceLimit($context);
                
            case 'select_server':
                return self::actionSelectServer($context);
                
            case 'add_wireguard_peer':
                return self::actionAddWireGuardPeer($context);
                
            case 'generate_config':
                return self::actionGenerateConfig($context);
                
            case 'suspend_user':
                return self::actionSuspendUser($context);
                
            case 'log_event':
                return self::actionLogEvent($step['type'], $context);
                
            default:
                throw new Exception("Unknown action: $action");
        }
    }
    
    /**
     * Send email action
     */
    private static function actionSendEmail($template, $context) {
        $user = $context['user'] ?? null;
        if (!$user || !isset($user['email'])) {
            return false;
        }
        
        // Get template from database
        $tpl = Database::queryOne('templates',
            "SELECT * FROM email_templates WHERE slug = ?",
            [$template]
        );
        
        if (!$tpl) {
            // Use fallback templates
            $tpl = self::getFallbackTemplate($template);
        }
        
        // Replace variables
        $subject = self::replaceVariables($tpl['subject'], $context);
        $body = self::replaceVariables($tpl['body'], $context);
        
        // Send email
        return Mailer::send($user['email'], $subject, $body);
    }
    
    /**
     * Update subscription status
     */
    private static function actionUpdateSubscription($status, $context) {
        $userId = $context['user_id'] ?? null;
        if (!$userId) return false;
        
        // VIP users are exempt from suspension
        if ($status === 'suspended' || $status === 'grace_period') {
            $user = Database::queryOne('users', "SELECT email FROM users WHERE id = ?", [$userId]);
            if ($user && VIPManager::isVIP($user['email'])) {
                return true; // Skip - VIP is always active
            }
        }
        
        Database::execute('billing',
            "UPDATE subscriptions SET status = ?, updated_at = datetime('now') WHERE user_id = ?",
            [$status, $userId]
        );
        
        return true;
    }
    
    /**
     * Check subscription action
     */
    private static function actionCheckSubscription($context) {
        $userId = $context['user_id'] ?? null;
        $email = $context['email'] ?? null;
        
        // VIP bypasses subscription check
        if ($email && VIPManager::isVIP($email)) {
            $context['subscription_valid'] = true;
            $context['is_vip'] = true;
            return true;
        }
        
        $sub = Database::queryOne('billing',
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
            [$userId]
        );
        
        $context['subscription_valid'] = ($sub !== false);
        return $context['subscription_valid'];
    }
    
    /**
     * Select server action
     */
    private static function actionSelectServer($context) {
        $email = $context['email'] ?? null;
        $preferredRegion = $context['preferred_region'] ?? null;
        
        $server = ServerRules::getBestServer($email, $preferredRegion);
        
        if (!$server) {
            throw new Exception('No available servers');
        }
        
        $context['selected_server'] = $server;
        return true;
    }
    
    /**
     * Add WireGuard peer to server
     */
    private static function actionAddWireGuardPeer($context) {
        $server = $context['selected_server'] ?? null;
        $publicKey = $context['client_public_key'] ?? null;
        
        if (!$server || !$publicKey) {
            throw new Exception('Missing server or public key');
        }
        
        // Call server's peer API
        $apiUrl = "http://{$server['ip_address']}:{$server['api_port']}/add_peer";
        
        $data = [
            'public_key' => $publicKey,
            'allowed_ips' => $context['allowed_ips'] ?? '10.0.0.0/24'
        ];
        
        $response = self::httpPost($apiUrl, $data);
        
        if (!$response || !$response['success']) {
            throw new Exception('Failed to add peer to server');
        }
        
        $context['peer_added'] = true;
        $context['assigned_ip'] = $response['assigned_ip'] ?? null;
        
        return true;
    }
    
    /**
     * Generate WireGuard config
     */
    private static function actionGenerateConfig($context) {
        $server = $context['selected_server'];
        $privateKey = $context['client_private_key'];
        $assignedIp = $context['assigned_ip'];
        
        $config = "[Interface]
PrivateKey = {$privateKey}
Address = {$assignedIp}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = {$server['public_key']}
Endpoint = {$server['ip_address']}:{$server['wireguard_port']}
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25
";
        
        $context['wireguard_config'] = $config;
        return true;
    }
    
    /**
     * HTTP POST helper
     */
    private static function httpPost($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("HTTP POST error: $error");
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Replace template variables
     */
    private static function replaceVariables($text, $context) {
        $replacements = [
            '{first_name}' => $context['user']['first_name'] ?? 'User',
            '{last_name}' => $context['user']['last_name'] ?? '',
            '{email}' => $context['user']['email'] ?? '',
            '{plan_name}' => $context['plan_name'] ?? 'Personal',
            '{amount}' => $context['amount'] ?? '0.00',
            '{invoice_number}' => $context['invoice_number'] ?? '',
            '{dashboard_url}' => 'https://vpn.the-truth-publishing.com/dashboard/',
            '{support_email}' => 'paulhalonen@gmail.com'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Create execution record
     */
    private static function createExecution($workflowName, $context) {
        Database::execute('automation',
            "INSERT INTO workflow_executions (workflow_name, context, status, created_at) VALUES (?, ?, 'running', datetime('now'))",
            [$workflowName, json_encode($context)]
        );
        
        return Database::lastInsertId('automation');
    }
    
    /**
     * Schedule a step for later execution
     */
    private static function scheduleStep($executionId, $stepIndex, $step, $context) {
        $executeAt = date('Y-m-d H:i:s', time() + $step['delay']);
        
        Database::execute('automation',
            "INSERT INTO scheduled_tasks (execution_id, step_index, step_data, context, execute_at, status) VALUES (?, ?, ?, ?, ?, 'pending')",
            [$executionId, $stepIndex, json_encode($step), json_encode($context), $executeAt]
        );
    }
    
    /**
     * Get fallback email template
     */
    private static function getFallbackTemplate($name) {
        $templates = [
            'welcome' => [
                'subject' => 'Welcome to TrueVault VPN!',
                'body' => "Hi {first_name},\n\nWelcome to TrueVault VPN! Your account is ready.\n\nGet started: {dashboard_url}\n\nBest,\nTrueVault Team"
            ],
            'payment_receipt' => [
                'subject' => 'Payment Received - TrueVault VPN',
                'body' => "Hi {first_name},\n\nWe received your payment of \${amount}.\n\nInvoice: {invoice_number}\n\nThank you!\nTrueVault Team"
            ],
            'payment_failed_day0' => [
                'subject' => 'Payment Failed - Action Required',
                'body' => "Hi {first_name},\n\nYour payment could not be processed. Please update your payment method.\n\n{dashboard_url}\n\nTrueVault Team"
            ]
        ];
        
        return $templates[$name] ?? ['subject' => 'TrueVault VPN', 'body' => 'Message from TrueVault VPN'];
    }
}
```

## 5.2 Cron Job for Scheduled Tasks

```php
<?php
/**
 * TrueVault VPN - Cron Processor
 * Run every 5 minutes: */5 * * * * php /path/to/api/cron/process.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../automation/engine.php';

// Process due scheduled tasks
$tasks = Database::query('automation',
    "SELECT * FROM scheduled_tasks WHERE status = 'pending' AND execute_at <= datetime('now')"
);

foreach ($tasks as $task) {
    try {
        $step = json_decode($task['step_data'], true);
        $context = json_decode($task['context'], true);
        
        AutomationEngine::executeStep($step, $context);
        
        Database::execute('automation',
            "UPDATE scheduled_tasks SET status = 'completed', completed_at = datetime('now') WHERE id = ?",
            [$task['id']]
        );
        
    } catch (Exception $e) {
        Database::execute('automation',
            "UPDATE scheduled_tasks SET status = 'failed', error = ? WHERE id = ?",
            [$e->getMessage(), $task['id']]
        );
    }
}

// Health check all servers
$servers = Database::query('servers', "SELECT * FROM vpn_servers WHERE status = 'active'");

foreach ($servers as $server) {
    $healthy = @fsockopen($server['ip_address'], $server['wireguard_port'], $errno, $errstr, 5);
    
    Database::execute('servers',
        "UPDATE vpn_servers SET last_health_check = datetime('now'), health_status = ? WHERE id = ?",
        [$healthy ? 'healthy' : 'unhealthy', $server['id']]
    );
    
    if ($healthy) fclose($healthy);
}

echo "Cron completed: " . date('Y-m-d H:i:s') . "\n";
```

---

# 6. FRONTEND ARCHITECTURE

## 6.1 Theme Loader (public/assets/js/theme-loader.js)

```javascript
/**
 * TrueVault VPN - Theme Loader
 * Loads CSS variables from themes.db via API
 */

(function() {
    'use strict';

    async function loadTheme() {
        try {
            // Check cache (5 minute TTL)
            const cached = localStorage.getItem('truevault_theme');
            const cacheTime = localStorage.getItem('truevault_theme_time');
            
            if (cached && cacheTime && (Date.now() - parseInt(cacheTime)) < 300000) {
                applyTheme(JSON.parse(cached));
                return;
            }

            const response = await fetch('/api/theme/');
            const data = await response.json();
            
            if (data.success && data.data) {
                applyTheme(data.data.variables);
                localStorage.setItem('truevault_theme', JSON.stringify(data.data.variables));
                localStorage.setItem('truevault_theme_time', Date.now().toString());
            }
        } catch (error) {
            console.warn('Theme load failed, using defaults');
            applyDefaultTheme();
        }
    }

    function applyTheme(vars) {
        const root = document.documentElement;
        
        // Apply colors
        if (vars.colors) {
            Object.entries(vars.colors).forEach(([key, value]) => {
                root.style.setProperty(`--colors-${key}`, value);
            });
        }
        
        // Apply gradients
        if (vars.gradients) {
            Object.entries(vars.gradients).forEach(([key, value]) => {
                root.style.setProperty(`--gradients-${key}`, value);
            });
        }
        
        // Apply typography
        if (vars.typography) {
            Object.entries(vars.typography).forEach(([key, value]) => {
                root.style.setProperty(`--typography-${key}`, value);
            });
        }
        
        // Apply buttons
        if (vars.buttons) {
            Object.entries(vars.buttons).forEach(([key, value]) => {
                root.style.setProperty(`--buttons-${key}`, value);
            });
        }
        
        // Apply cards
        if (vars.cards) {
            Object.entries(vars.cards).forEach(([key, value]) => {
                root.style.setProperty(`--cards-${key}`, value);
            });
        }
    }

    function applyDefaultTheme() {
        const defaults = {
            colors: {
                primary: '#00d9ff',
                secondary: '#00ff88',
                accent: '#ff6b6b',
                background: '#0f0f1a',
                'background-secondary': '#1a1a2e',
                text: '#ffffff',
                'text-muted': '#888888',
                error: '#ff5050',
                border: 'rgba(255,255,255,0.08)'
            },
            gradients: {
                primary: 'linear-gradient(90deg, #00d9ff, #00ff88)',
                background: 'linear-gradient(135deg, #0f0f1a, #1a1a2e)'
            },
            typography: {
                'font-family': 'Inter, -apple-system, sans-serif',
                'font-size-base': '16px'
            },
            buttons: {
                'border-radius': '8px'
            },
            cards: {
                'border-radius': '14px'
            }
        };
        applyTheme(defaults);
    }

    // Load on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadTheme);
    } else {
        loadTheme();
    }

    window.reloadTheme = loadTheme;
})();
```

## 6.2 CSS Variables Usage (NO HARDCODED VALUES)

```css
/* public/assets/css/main.css */
/* ALL values come from CSS variables - NO hardcoded colors! */

:root {
    /* These are fallbacks - actual values loaded from themes.db */
    --colors-primary: #00d9ff;
    --colors-secondary: #00ff88;
    --colors-accent: #ff6b6b;
    --colors-background: #0f0f1a;
    --colors-background-secondary: #1a1a2e;
    --colors-text: #ffffff;
    --colors-text-muted: #888888;
    --colors-error: #ff5050;
    --colors-border: rgba(255,255,255,0.08);
    --gradients-primary: linear-gradient(90deg, #00d9ff, #00ff88);
    --gradients-background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
    --typography-font-family: Inter, -apple-system, sans-serif;
    --buttons-border-radius: 8px;
    --cards-border-radius: 14px;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--typography-font-family);
    background: var(--gradients-background);
    color: var(--colors-text);
    min-height: 100vh;
}

/* Buttons - NO hardcoded colors */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: var(--buttons-border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--gradients-primary);
    color: var(--colors-background);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 217, 255, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.08);
    color: var(--colors-text);
    border: 1px solid var(--colors-border);
}

/* Cards - NO hardcoded colors */
.card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--colors-border);
    border-radius: var(--cards-border-radius);
    padding: 20px;
}

/* Form elements */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--colors-text-muted);
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    background: var(--colors-background-secondary);
    border: 1px solid var(--colors-border);
    border-radius: var(--buttons-border-radius);
    color: var(--colors-text);
    font-size: 1rem;
}

.form-group input:focus {
    outline: none;
    border-color: var(--colors-primary);
}

/* Status colors */
.status-success { color: var(--colors-secondary); }
.status-error { color: var(--colors-error); }
.status-warning { color: var(--colors-accent); }

/* Text utilities */
.text-primary { color: var(--colors-primary); }
.text-secondary { color: var(--colors-secondary); }
.text-muted { color: var(--colors-text-muted); }

/* Gradients */
.gradient-text {
    background: var(--gradients-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
```

---

# 7. VPN SERVER INTEGRATION

## 7.1 Server-Side Peer API (server-scripts/peer_api.py)

```python
#!/usr/bin/env python3
"""
TrueVault VPN - Server Peer API
Runs on each VPN server to manage WireGuard peers
"""

from flask import Flask, request, jsonify
import subprocess
import os
import ipaddress
import json
from datetime import datetime

app = Flask(__name__)

# Configuration
WIREGUARD_INTERFACE = "wg0"
WIREGUARD_CONFIG = f"/etc/wireguard/{WIREGUARD_INTERFACE}.conf"
IP_POOL_START = "10.0.0.2"
IP_POOL_END = "10.0.0.254"
PEERS_FILE = "/var/lib/wireguard/peers.json"

# API Key for authentication (set via environment)
API_KEY = os.environ.get('TRUEVAULT_API_KEY', 'your-secret-key-here')

def require_api_key(f):
    """Decorator to require API key"""
    def decorated(*args, **kwargs):
        key = request.headers.get('X-API-Key')
        if key != API_KEY:
            return jsonify({'success': False, 'error': 'Invalid API key'}), 401
        return f(*args, **kwargs)
    decorated.__name__ = f.__name__
    return decorated

def load_peers():
    """Load peer data from file"""
    if os.path.exists(PEERS_FILE):
        with open(PEERS_FILE, 'r') as f:
            return json.load(f)
    return {}

def save_peers(peers):
    """Save peer data to file"""
    os.makedirs(os.path.dirname(PEERS_FILE), exist_ok=True)
    with open(PEERS_FILE, 'w') as f:
        json.dump(peers, f, indent=2)

def get_next_ip():
    """Get next available IP address"""
    peers = load_peers()
    used_ips = set(p.get('ip') for p in peers.values())
    
    start = ipaddress.IPv4Address(IP_POOL_START)
    end = ipaddress.IPv4Address(IP_POOL_END)
    
    for i in range(int(start), int(end) + 1):
        ip = str(ipaddress.IPv4Address(i))
        if ip not in used_ips:
            return ip
    
    return None

@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({
        'success': True,
        'status': 'healthy',
        'timestamp': datetime.utcnow().isoformat()
    })

@app.route('/add_peer', methods=['POST'])
@require_api_key
def add_peer():
    """Add a new WireGuard peer"""
    data = request.json
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'Public key required'}), 400
    
    # Get next available IP
    assigned_ip = get_next_ip()
    if not assigned_ip:
        return jsonify({'success': False, 'error': 'No available IPs'}), 503
    
    # Add peer to WireGuard
    try:
        subprocess.run([
            'wg', 'set', WIREGUARD_INTERFACE,
            'peer', public_key,
            'allowed-ips', f'{assigned_ip}/32'
        ], check=True)
        
        # Save config
        subprocess.run(['wg-quick', 'save', WIREGUARD_INTERFACE], check=True)
        
        # Track peer
        peers = load_peers()
        peers[public_key] = {
            'ip': assigned_ip,
            'added_at': datetime.utcnow().isoformat(),
            'user_id': data.get('user_id'),
            'device_id': data.get('device_id')
        }
        save_peers(peers)
        
        return jsonify({
            'success': True,
            'assigned_ip': assigned_ip,
            'message': 'Peer added successfully'
        })
        
    except subprocess.CalledProcessError as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/remove_peer', methods=['POST'])
@require_api_key
def remove_peer():
    """Remove a WireGuard peer"""
    data = request.json
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'Public key required'}), 400
    
    try:
        subprocess.run([
            'wg', 'set', WIREGUARD_INTERFACE,
            'peer', public_key, 'remove'
        ], check=True)
        
        subprocess.run(['wg-quick', 'save', WIREGUARD_INTERFACE], check=True)
        
        # Remove from tracking
        peers = load_peers()
        if public_key in peers:
            del peers[public_key]
            save_peers(peers)
        
        return jsonify({'success': True, 'message': 'Peer removed'})
        
    except subprocess.CalledProcessError as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status', methods=['GET'])
@require_api_key
def status():
    """Get server status"""
    try:
        result = subprocess.run(['wg', 'show', WIREGUARD_INTERFACE],
                              capture_output=True, text=True)
        
        peers = load_peers()
        
        return jsonify({
            'success': True,
            'interface': WIREGUARD_INTERFACE,
            'peer_count': len(peers),
            'wg_output': result.stdout
        })
        
    except subprocess.CalledProcessError as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/get_public_key', methods=['GET'])
def get_public_key():
    """Get server's public key"""
    try:
        with open('/etc/wireguard/publickey', 'r') as f:
            public_key = f.read().strip()
        
        return jsonify({
            'success': True,
            'public_key': public_key
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
```

---

# 8. CERTIFICATE SYSTEM

## 8.1 Certificate Generation (api/certificates/generate.php)

```php
<?php
/**
 * TrueVault VPN - Certificate Generation
 * Generates certificates on the VPN server
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();
if (!$user) exit;

Response::requireMethod('POST');

$input = Response::getJsonInput();
$certType = $input['type'] ?? 'device'; // device, regional, mesh

// Check plan limits
$isVIP = VIPManager::isVIP($user['email']);
if (!$isVIP) {
    $plan = PlanLimits::getPlan($user['plan_type']);
    if (!in_array('personal_certs', $plan['features'])) {
        Response::forbidden('Certificate generation requires Personal plan or higher');
    }
}

// Generate certificate on server
$server = ServerRules::getBestServer($user['email']);

$apiUrl = "http://{$server['ip_address']}:{$server['api_port']}/generate_cert";

$certData = [
    'user_id' => $user['id'],
    'type' => $certType,
    'common_name' => $input['name'] ?? "user_{$user['id']}_{$certType}",
    'validity_days' => $isVIP ? 3650 : 365 // VIP gets 10 year certs
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($certData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . getenv('VPN_API_KEY')
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    Response::serverError("Certificate generation failed: $error");
}

$result = json_decode($response, true);

if (!$result || !$result['success']) {
    Response::serverError($result['error'] ?? 'Certificate generation failed');
}

// Store certificate metadata in database
Database::execute('certificates',
    "INSERT INTO certificates (user_id, type, common_name, serial_number, fingerprint, valid_from, valid_to, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))",
    [
        $user['id'],
        $certType,
        $certData['common_name'],
        $result['serial_number'],
        $result['fingerprint'],
        $result['valid_from'],
        $result['valid_to']
    ]
);

$certId = Database::lastInsertId('certificates');

Response::success([
    'certificate_id' => $certId,
    'type' => $certType,
    'common_name' => $certData['common_name'],
    'valid_from' => $result['valid_from'],
    'valid_to' => $result['valid_to'],
    'download_url' => "/api/certificates/download.php?id=$certId"
], 'Certificate generated successfully');
```

---

# 9. CAMERA INTEGRATION

## 9.1 Camera Stream URLs by Vendor

```php
<?php
/**
 * TrueVault VPN - Camera Stream Patterns
 * RTSP URLs for different camera vendors
 */

class CameraStreams {
    
    private static $patterns = [
        // Geeni/Tuya cameras (use tinytuya for local control)
        'Geeni' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/stream1',
            'rtsp_sub' => 'rtsp://{user}:{pass}@{ip}:{port}/stream2',
            'snapshot' => 'http://{ip}:{http_port}/snapshot.jpg',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554,
            'notes' => 'May require local key from Tuya cloud'
        ],
        
        // Wyze cameras
        'Wyze' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/live',
            'snapshot' => 'http://{ip}:{http_port}/cgi-bin/snapshot.cgi',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554,
            'notes' => 'Requires Wyze RTSP firmware'
        ],
        
        // Hikvision cameras
        'Hikvision' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/Streaming/Channels/101',
            'rtsp_sub' => 'rtsp://{user}:{pass}@{ip}:{port}/Streaming/Channels/102',
            'snapshot' => 'http://{ip}:{http_port}/ISAPI/Streaming/channels/101/picture',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554,
            'notes' => 'Channel 101=main, 102=sub'
        ],
        
        // Dahua cameras
        'Dahua' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=0',
            'rtsp_sub' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=1',
            'snapshot' => 'http://{ip}:{http_port}/cgi-bin/snapshot.cgi',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554
        ],
        
        // Amcrest cameras
        'Amcrest' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=0',
            'rtsp_sub' => 'rtsp://{user}:{pass}@{ip}:{port}/cam/realmonitor?channel=1&subtype=1',
            'snapshot' => 'http://{ip}:{http_port}/cgi-bin/snapshot.cgi',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554
        ],
        
        // Reolink cameras
        'Reolink' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/h264Preview_01_main',
            'rtsp_sub' => 'rtsp://{user}:{pass}@{ip}:{port}/h264Preview_01_sub',
            'snapshot' => 'http://{ip}:{http_port}/cgi-bin/api.cgi?cmd=Snap&channel=0',
            'default_user' => 'admin',
            'default_pass' => '',
            'default_port' => 554
        ],
        
        // Ring (requires cloud auth, limited local access)
        'Ring' => [
            'notes' => 'Ring requires cloud authentication - limited local support'
        ],
        
        // Generic ONVIF cameras
        'Generic' => [
            'rtsp' => 'rtsp://{user}:{pass}@{ip}:{port}/stream1',
            'snapshot' => 'http://{ip}:{http_port}/snapshot.jpg',
            'default_user' => 'admin',
            'default_pass' => 'admin',
            'default_port' => 554
        ]
    ];
    
    /**
     * Get stream URL for a camera
     */
    public static function getStreamUrl($camera, $quality = 'main') {
        $vendor = $camera['vendor'] ?? 'Generic';
        $pattern = self::$patterns[$vendor] ?? self::$patterns['Generic'];
        
        $template = $quality === 'sub' && isset($pattern['rtsp_sub']) 
            ? $pattern['rtsp_sub'] 
            : $pattern['rtsp'];
        
        if (!$template) {
            return null;
        }
        
        return self::buildUrl($template, $camera, $pattern);
    }
    
    /**
     * Get snapshot URL for a camera
     */
    public static function getSnapshotUrl($camera) {
        $vendor = $camera['vendor'] ?? 'Generic';
        $pattern = self::$patterns[$vendor] ?? self::$patterns['Generic'];
        
        if (!isset($pattern['snapshot'])) {
            return null;
        }
        
        return self::buildUrl($pattern['snapshot'], $camera, $pattern);
    }
    
    /**
     * Build URL from template
     */
    private static function buildUrl($template, $camera, $pattern) {
        $replacements = [
            '{ip}' => $camera['ip_address'],
            '{port}' => $camera['rtsp_port'] ?? $pattern['default_port'] ?? 554,
            '{http_port}' => $camera['http_port'] ?? 80,
            '{user}' => $camera['username'] ?? $pattern['default_user'] ?? 'admin',
            '{pass}' => $camera['password'] ?? $pattern['default_pass'] ?? ''
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
```

---

# 10. SECURITY IMPLEMENTATION

## 10.1 JWT Token Management

```php
<?php
/**
 * TrueVault VPN - JWT Manager
 * Handles token generation and validation
 */

class JWTManager {
    
    private static $secret = 'truevault-jwt-secret-change-in-production-2026';
    private static $algorithm = 'HS256';
    private static $tokenExpiry = 604800; // 7 days
    private static $refreshExpiry = 2592000; // 30 days
    
    /**
     * Generate access token
     */
    public static function generateToken($userId, $email, $isAdmin = false) {
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ]));
        
        $payload = self::base64UrlEncode(json_encode([
            'sub' => $userId,
            'email' => $email,
            'admin' => $isAdmin,
            'iat' => time(),
            'exp' => time() + self::$tokenExpiry
        ]));
        
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::$secret, true)
        );
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::$refreshExpiry);
        
        Database::execute('sessions',
            "INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, hash('sha256', $token), $expiresAt]
        );
        
        return $token;
    }
    
    /**
     * Validate token
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::$secret, true)
        );
        
        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }
        
        $data = json_decode(self::base64UrlDecode($payload), true);
        
        if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Validate refresh token
     */
    public static function validateRefreshToken($token, $userId) {
        $hash = hash('sha256', $token);
        
        $record = Database::queryOne('sessions',
            "SELECT * FROM refresh_tokens WHERE user_id = ? AND token = ? AND expires_at > datetime('now')",
            [$userId, $hash]
        );
        
        return $record !== false;
    }
    
    /**
     * Revoke refresh token
     */
    public static function revokeRefreshToken($token, $userId) {
        $hash = hash('sha256', $token);
        
        Database::execute('sessions',
            "DELETE FROM refresh_tokens WHERE user_id = ? AND token = ?",
            [$userId, $hash]
        );
    }
    
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
```

---

# DEPLOYMENT COMMANDS

## Upload via FTP
```powershell
# PowerShell FTP upload
$ftpHost = "the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"

# Upload single file
curl -u "${ftpUser}:${ftpPass}" -T "localfile.php" "ftp://${ftpHost}/public_html/vpn.the-truth-publishing.com/api/path/file.php"

# Upload entire folder (using ncftpput if available)
ncftpput -R -v -u $ftpUser -p $ftpPass $ftpHost /public_html/vpn.the-truth-publishing.com/api ./api
```

## Initialize Databases on Production
```
Visit: https://vpn.the-truth-publishing.com/api/config/setup-databases.php
```

## Setup Cron Job
```bash
# Add to server crontab
*/5 * * * * php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/api/cron/process.php >> /var/log/truevault-cron.log 2>&1
```

---

**END OF MASTER BLUEPRINT V2**
