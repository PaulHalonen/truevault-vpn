# TRUEVAULT VPN - MASTER BLUEPRINT v4
## Complete Rebuild for Launch-Ready State
**Created:** January 13, 2026
**Goal:** Zero placeholders, real functionality, 2-click simplicity

---

## 🎯 CORE PRINCIPLES

1. **2-CLICK MAXIMUM** - Any user action takes 2 clicks or less
2. **NO MOCKUPS** - Every UI element connects to real data
3. **DATABASE-DRIVEN** - All config, themes, servers from SQLite
4. **ONE-MAN OPERATION** - Fully automated, 5 min/day admin work
5. **PORTABLE** - Easy transfer to new owner

---

## 📁 FINAL FILE STRUCTURE

```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
│
├── .htaccess                    # URL routing
│
├── index.html                   # Landing page (public)
├── login.html                   # Login form (public)
├── register.html                # Registration (public)
├── forgot-password.html         # Password reset request
├── reset-password.html          # Password reset form
├── pricing.html                 # Pricing page
│
├── api/
│   ├── .htaccess                # API routing rules
│   │
│   ├── config/
│   │   ├── database.php         # ✅ Database class (KEEP)
│   │   ├── constants.php        # Environment constants
│   │   └── setup-all.php        # Database initialization
│   │
│   ├── helpers/
│   │   ├── auth.php             # ✅ Auth class (KEEP)
│   │   ├── response.php         # ✅ JSON responses (KEEP)
│   │   ├── vip.php              # ✅ VIP detection (KEEP)
│   │   ├── mailer.php           # 🔧 Email sending (FIX)
│   │   └── logger.php           # Logging helper
│   │
│   ├── auth/
│   │   ├── login.php            # ✅ Working
│   │   ├── register.php         # 🔧 Add trial creation
│   │   ├── logout.php           # ✅ Working
│   │   ├── forgot-password.php  # 🔧 Implement
│   │   ├── reset-password.php   # 🔧 Implement
│   │   └── refresh.php          # ✅ Token refresh
│   │
│   ├── devices/                 # 🔴 COMPLETE REWRITE NEEDED
│   │   ├── list.php             # List user's devices
│   │   ├── add.php              # Add device (2-click)
│   │   ├── switch.php           # Switch server
│   │   ├── remove.php           # Remove device
│   │   └── config.php           # Get config data for device
│   │
│   ├── servers/                 # 🔴 NEW DIRECTORY
│   │   └── list.php             # List available servers
│   │
│   ├── billing/
│   │   ├── subscription.php     # Get subscription status
│   │   ├── checkout.php         # Start PayPal checkout
│   │   ├── webhook.php          # PayPal webhook handler
│   │   ├── cancel.php           # Cancel subscription
│   │   └── history.php          # Payment history
│   │
│   ├── user/                    # 🔴 NEW DIRECTORY
│   │   ├── profile.php          # Get/update profile
│   │   └── password.php         # Change password
│   │
│   ├── admin/
│   │   ├── login.php            # Admin login
│   │   ├── stats.php            # Dashboard stats
│   │   ├── users.php            # User management
│   │   ├── servers.php          # Server management
│   │   └── settings.php         # Site settings
│   │
│   └── cron/
│       └── process.php          # All scheduled tasks
│
├── dashboard/                   # User dashboard
│   ├── index.html               # Dashboard home
│   ├── devices.html             # Device management (MAIN)
│   ├── servers.html             # Server list
│   ├── billing.html             # Subscription & payments
│   └── settings.html            # Account settings
│   │
│   └── assets/
│       ├── css/
│       │   └── dashboard.css    # All dashboard styles
│       └── js/
│           └── app.js           # All dashboard JS
│
├── admin/                       # Admin panel
│   ├── index.html               # Admin dashboard
│   ├── users.html               # User management
│   ├── servers.html             # Server management
│   └── settings.html            # Site settings
│
├── databases/                   # All SQLite databases
│   ├── core/
│   │   ├── users.db             # Users + devices
│   │   └── admin.db             # Admin users
│   ├── vpn/
│   │   ├── servers.db           # VPN servers
│   │   └── peers.db             # VPN peer connections
│   ├── billing/
│   │   └── billing.db           # Subscriptions, payments
│   ├── cms/
│   │   └── themes.db            # Theme variables
│   └── logs/
│       └── logs.db              # System logs
│
└── downloads/
    └── scanner/                 # Network scanner tool
        ├── truevault_scanner.py
        ├── run_scanner.bat
        └── run_scanner.sh
```

---

## 🗄️ DATABASE SCHEMAS (FINAL)

### 1. users.db - Core User Data

```sql
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active', -- active, suspended, cancelled
    is_vip INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- User devices (consolidated - ONE table for devices)
CREATE TABLE IF NOT EXISTS user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT DEFAULT 'unknown', -- phone, tablet, laptop, desktop
    public_key TEXT UNIQUE NOT NULL,
    server_id INTEGER,
    assigned_ip TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_connected DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, device_name)
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

-- Indexes
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id);
CREATE INDEX IF NOT EXISTS idx_devices_public_key ON user_devices(public_key);
```

### 2. servers.db - VPN Server Configuration

```sql
-- VPN Servers (THE source of truth)
CREATE TABLE IF NOT EXISTS vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                      -- Internal name: truevault-ny
    display_name TEXT NOT NULL,              -- Display: New York, USA
    country TEXT NOT NULL,                   -- USA
    country_code TEXT NOT NULL,              -- US
    country_flag TEXT NOT NULL,              -- 🇺🇸
    ip_address TEXT NOT NULL,                -- 66.94.103.91
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT,                         -- Server's WireGuard public key
    server_type TEXT DEFAULT 'shared',       -- shared, vip_dedicated
    vip_user_email TEXT,                     -- Only for vip_dedicated
    max_connections INTEGER DEFAULT 50,
    current_connections INTEGER DEFAULT 0,
    cpu_load INTEGER DEFAULT 0,
    latency_ms INTEGER DEFAULT 0,
    bandwidth_type TEXT DEFAULT 'unlimited', -- unlimited, limited
    status TEXT DEFAULT 'active',            -- active, maintenance, offline
    rules_title TEXT,
    rules_description TEXT,
    rules_allowed TEXT,                      -- JSON array
    rules_not_allowed TEXT,                  -- JSON array
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert the 4 servers
INSERT OR REPLACE INTO vpn_servers (id, name, display_name, country, country_code, country_flag, ip_address, wireguard_port, api_port, public_key, server_type, vip_user_email, max_connections, bandwidth_type, status, rules_title, rules_description, rules_allowed, rules_not_allowed) VALUES
(1, 'truevault-ny', 'New York, USA', 'USA', 'US', '🇺🇸', '66.94.103.91', 51820, 8080, 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=', 'shared', NULL, 50, 'unlimited', 'active', 'RECOMMENDED FOR HOME USE', 'Use for all home devices including gaming consoles, IP cameras, and streaming.', '["Gaming", "Torrents/P2P", "IP Cameras", "Streaming", "General browsing"]', '[]'),

(2, 'truevault-stl', 'St. Louis, USA (VIP)', 'USA', 'US', '🇺🇸', '144.126.133.253', 51820, 8080, 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=', 'vip_dedicated', 'seige235@yahoo.com', 1, 'unlimited', 'active', 'PRIVATE DEDICATED SERVER', 'Exclusively for VIP user. Unlimited bandwidth, no restrictions.', '["Everything - No restrictions", "Unlimited bandwidth", "Static IP address"]', '[]'),

(3, 'truevault-tx', 'Dallas, USA', 'USA', 'US', '🇺🇸', '66.241.124.4', 51820, 8443, 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=', 'shared', NULL, 50, 'limited', 'active', 'STREAMING OPTIMIZED', 'Optimized for Netflix and streaming services. Not flagged by streaming services.', '["Netflix", "Hulu", "Disney+", "Amazon Prime"]', '["Gaming (high latency)", "Torrents/P2P", "IP Cameras", "Heavy downloads"]'),

(4, 'truevault-ca', 'Toronto, Canada', 'Canada', 'CA', '🇨🇦', '66.241.125.247', 51820, 8080, 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=', 'shared', NULL, 50, 'limited', 'active', 'CANADIAN STREAMING', 'Access Canadian Netflix and streaming content.', '["Canadian Netflix", "CBC Gem", "Crave", "Canadian content"]', '["Gaming (latency)", "Torrents/P2P", "IP Cameras", "Heavy downloads"]');

-- Server health history
CREATE TABLE IF NOT EXISTS server_health (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    cpu_load INTEGER,
    memory_usage INTEGER,
    connection_count INTEGER,
    bytes_in INTEGER,
    bytes_out INTEGER,
    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);
```

### 3. peers.db - VPN Peer Connections

```sql
-- Active VPN peers (synced with WireGuard servers)
CREATE TABLE IF NOT EXISTS vpn_peers (
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
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_peers_user ON vpn_peers(user_id);
CREATE INDEX IF NOT EXISTS idx_peers_server ON vpn_peers(server_id);
CREATE INDEX IF NOT EXISTS idx_peers_device ON vpn_peers(device_id);
```

### 4. billing.db - Subscriptions & Payments

```sql
-- Subscription plans
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,              -- personal, family, business
    display_name TEXT NOT NULL,      -- Personal, Family, Business
    price REAL NOT NULL,             -- 9.99
    billing_cycle TEXT DEFAULT 'monthly',
    max_devices INTEGER DEFAULT 3,
    features TEXT,                   -- JSON array
    paypal_plan_id TEXT,             -- PayPal subscription plan ID
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User subscriptions
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER,
    plan_type TEXT NOT NULL,         -- trial, personal, family, business
    status TEXT DEFAULT 'active',    -- active, trial, grace_period, suspended, cancelled
    paypal_subscription_id TEXT,
    price REAL DEFAULT 0,
    trial_ends_at DATETIME,
    current_period_start DATETIME,
    current_period_end DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payment history
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    paypal_payment_id TEXT,
    paypal_order_id TEXT,
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'pending',   -- pending, completed, failed, refunded
    payment_method TEXT DEFAULT 'paypal',
    receipt_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default plans
INSERT OR REPLACE INTO plans (id, name, display_name, price, max_devices, features) VALUES
(1, 'trial', 'Free Trial', 0, 1, '["1 Device", "All servers", "14-day trial"]'),
(2, 'personal', 'Personal', 9.99, 3, '["3 Devices", "All servers", "24/7 Support"]'),
(3, 'family', 'Family', 14.99, 999, '["Unlimited Devices", "All servers", "Priority Support"]'),
(4, 'business', 'Business', 29.99, 999, '["Unlimited Devices", "Dedicated Server", "API Access", "SLA"]');
```

### 5. themes.db - CMS Theme Variables

```sql
-- Themes
CREATE TABLE IF NOT EXISTS themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Theme CSS variables
CREATE TABLE IF NOT EXISTS theme_variables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    category TEXT NOT NULL,          -- colors, fonts, spacing
    variable_name TEXT NOT NULL,     -- --primary-color
    variable_value TEXT NOT NULL,    -- #00d9ff
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE(theme_id, variable_name)
);

-- Insert default dark theme
INSERT OR REPLACE INTO themes (id, name, description, is_active) VALUES 
(1, 'TrueVault Dark', 'Default dark theme with cyan/green accents', 1);

-- Insert theme variables
INSERT OR REPLACE INTO theme_variables (theme_id, category, variable_name, variable_value) VALUES
-- Colors
(1, 'colors', '--bg-primary', '#0f0f1a'),
(1, 'colors', '--bg-secondary', '#1a1a2e'),
(1, 'colors', '--bg-card', 'rgba(255,255,255,0.03)'),
(1, 'colors', '--text-primary', '#ffffff'),
(1, 'colors', '--text-secondary', '#888888'),
(1, 'colors', '--accent-cyan', '#00d9ff'),
(1, 'colors', '--accent-green', '#00ff88'),
(1, 'colors', '--accent-red', '#ff5050'),
(1, 'colors', '--accent-yellow', '#ffd700'),
(1, 'colors', '--border-color', 'rgba(255,255,255,0.08)'),
-- Fonts
(1, 'fonts', '--font-family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'),
(1, 'fonts', '--font-size-base', '16px'),
(1, 'fonts', '--font-size-small', '14px'),
(1, 'fonts', '--font-size-large', '18px'),
-- Spacing
(1, 'spacing', '--spacing-xs', '0.25rem'),
(1, 'spacing', '--spacing-sm', '0.5rem'),
(1, 'spacing', '--spacing-md', '1rem'),
(1, 'spacing', '--spacing-lg', '1.5rem'),
(1, 'spacing', '--spacing-xl', '2rem'),
-- Borders
(1, 'borders', '--border-radius-sm', '4px'),
(1, 'borders', '--border-radius-md', '8px'),
(1, 'borders', '--border-radius-lg', '12px');
```

### 6. logs.db - System Logs

```sql
-- System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,             -- info, warning, error, debug
    category TEXT NOT NULL,          -- auth, device, vpn, billing, system
    message TEXT NOT NULL,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    extra_data TEXT,                 -- JSON
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Email queue
CREATE TABLE IF NOT EXISTS email_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    recipient TEXT NOT NULL,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    template TEXT,
    status TEXT DEFAULT 'pending',   -- pending, sent, failed
    attempts INTEGER DEFAULT 0,
    last_attempt DATETIME,
    sent_at DATETIME,
    error TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Scheduled tasks
CREATE TABLE IF NOT EXISTS scheduled_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_type TEXT NOT NULL,
    task_data TEXT,                  -- JSON
    execute_at DATETIME NOT NULL,
    status TEXT DEFAULT 'pending',   -- pending, running, completed, failed
    result TEXT,
    executed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_logs_created ON system_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_logs_level ON system_logs(level);
CREATE INDEX IF NOT EXISTS idx_email_status ON email_queue(status);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON scheduled_tasks(status, execute_at);
```

---

## 🔌 API SPECIFICATIONS (FINAL)

### Standard Response Format
ALL APIs return this format:
```json
{
    "success": true|false,
    "data": { ... } | [...],
    "error": "Error message if success=false",
    "message": "Success message if applicable"
}
```

### Authentication APIs

#### POST /api/auth/login.php
```json
// Request
{
    "email": "user@example.com",
    "password": "password123"
}

// Response
{
    "success": true,
    "data": {
        "token": "jwt_token_here",
        "user": {
            "id": 1,
            "email": "user@example.com",
            "first_name": "John",
            "is_vip": false
        },
        "subscription": {
            "plan_type": "trial",
            "status": "active",
            "trial_ends_at": "2026-01-27T00:00:00Z",
            "days_remaining": 14,
            "max_devices": 1
        }
    }
}
```

#### POST /api/auth/register.php
```json
// Request
{
    "email": "newuser@example.com",
    "password": "password123",
    "first_name": "John"  // optional
}

// Response
{
    "success": true,
    "data": {
        "token": "jwt_token_here",
        "user": { ... },
        "subscription": {
            "plan_type": "trial",
            "trial_ends_at": "2026-01-27T00:00:00Z"
        }
    },
    "message": "Account created! Your 14-day trial has started."
}

// ACTIONS PERFORMED:
// 1. Create user with hashed password
// 2. Generate UUID
// 3. Create trial subscription (14 days)
// 4. Check if VIP email → mark as VIP
// 5. Queue welcome email
// 6. Return JWT token
```

### Device APIs

#### GET /api/devices/list.php
```json
// Headers: Authorization: Bearer {token}

// Response
{
    "success": true,
    "data": {
        "devices": [
            {
                "id": 1,
                "device_name": "My iPhone",
                "device_type": "phone",
                "server": {
                    "id": 1,
                    "name": "New York, USA",
                    "flag": "🇺🇸",
                    "ip": "66.94.103.91",
                    "status": "online"
                },
                "assigned_ip": "10.0.0.100",
                "is_active": true,
                "created_at": "2026-01-13T12:00:00Z",
                "last_connected": "2026-01-13T15:30:00Z"
            }
        ],
        "device_count": 1,
        "max_devices": 3,
        "can_add_more": true
    }
}
```

#### POST /api/devices/add.php
```json
// Request
{
    "device_name": "My MacBook",
    "device_type": "laptop",      // optional
    "server_id": 1,
    "public_key": "base64_public_key_from_browser"
}

// Response
{
    "success": true,
    "data": {
        "device": {
            "id": 2,
            "device_name": "My MacBook",
            "assigned_ip": "10.0.0.101"
        },
        "config": {
            "client_ip": "10.0.0.101/32",
            "server_public_key": "lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=",
            "server_endpoint": "66.94.103.91:51820",
            "dns": "1.1.1.1, 8.8.8.8",
            "allowed_ips": "0.0.0.0/0, ::/0"
        }
    },
    "message": "Device added successfully!"
}
```

#### POST /api/devices/switch.php
```json
// Request
{
    "device_id": 2,
    "new_server_id": 3,
    "new_public_key": "base64_new_public_key"
}

// Response (same format as add.php)
```

#### DELETE /api/devices/remove.php
```json
// Request
{
    "device_id": 2
}

// Response
{
    "success": true,
    "message": "Device removed successfully"
}
```

### Server APIs

#### GET /api/servers/list.php
```json
// Headers: Authorization: Bearer {token}

// Response
{
    "success": true,
    "data": {
        "servers": [
            {
                "id": 1,
                "name": "New York, USA",
                "country": "USA",
                "flag": "🇺🇸",
                "ip_address": "66.94.103.91",
                "port": 51820,
                "status": "online",
                "load": 45,
                "latency": 20,
                "bandwidth_type": "unlimited",
                "is_vip_only": false,
                "is_recommended": true,
                "rules": {
                    "title": "RECOMMENDED FOR HOME USE",
                    "allowed": ["Gaming", "Streaming", "Torrents"],
                    "not_allowed": []
                }
            },
            {
                "id": 2,
                "name": "St. Louis, USA (VIP)",
                "country": "USA",
                "flag": "🇺🇸",
                "is_vip_only": true,
                "is_your_dedicated": true  // Only for VIP user
            }
        ],
        "recommended_server_id": 1,
        "user_is_vip": false
    }
}
```

### Billing APIs

#### GET /api/billing/subscription.php
```json
// Response
{
    "success": true,
    "data": {
        "subscription": {
            "plan_type": "trial",
            "plan_name": "Free Trial",
            "status": "active",
            "price": 0,
            "max_devices": 1,
            "trial_ends_at": "2026-01-27T00:00:00Z",
            "days_remaining": 14,
            "current_period_end": null,
            "can_upgrade": true
        },
        "available_plans": [
            {
                "id": 2,
                "name": "personal",
                "display_name": "Personal",
                "price": 9.99,
                "max_devices": 3,
                "features": ["3 Devices", "All servers", "24/7 Support"]
            }
        ]
    }
}
```

---

## 🎨 DASHBOARD PAGES (5 TOTAL)

### 1. index.html - Dashboard Home
```
SECTIONS:
├── Welcome message (Hello, {first_name}!)
├── Plan Status Card
│   ├── Plan name + badge
│   ├── Device usage: 2/3
│   ├── Trial countdown (if trial)
│   └── [Upgrade Plan] button
├── Quick Stats (4 cards)
│   ├── Devices: 2
│   ├── Current Server: New York 🇺🇸
│   ├── Data Used: 5.2 GB
│   └── Status: Connected ✓
└── Quick Actions
    ├── [Add Device] → devices.html
    ├── [Switch Server] → devices.html
    └── [Upgrade Plan] → billing.html

API CALLS:
- GET /api/billing/subscription.php
- GET /api/devices/list.php
```

### 2. devices.html - Device Management (CRITICAL)
```
LAYOUT:
├── Header: "My Devices" | 2/3 devices
├── Device Grid (cards)
│   └── Device Card:
│       ├── Icon + Name
│       ├── Server: 🇺🇸 New York
│       ├── Status: Active
│       ├── [Switch Server] button
│       ├── [Download Config] button
│       └── [Remove] button (with confirm)
├── Empty State (if no devices)
│   ├── 📱 icon
│   ├── "Add your first device"
│   └── [Add Device] button
└── Add Device Button (if devices exist)

MODALS:
├── Add Device Modal
│   ├── Device name input (auto-suggest)
│   ├── Server selection (radio buttons with flags)
│   └── [Add & Download Config] button
├── Download Success Modal
│   ├── ✅ Device added!
│   ├── [Download .conf File] button
│   ├── WireGuard app links
│   └── Import instructions
└── Switch Server Modal
    ├── Current server display
    ├── New server selection
    └── [Switch & Download] button

KEY BEHAVIORS:
1. Key generation happens in browser (TweetNaCl.js)
2. Private key NEVER sent to server
3. Config file generated in browser
4. Auto-download on add/switch
5. VIP user sees dedicated server first
```

### 3. servers.html - Server List
```
SECTIONS:
├── Header: "VPN Servers"
├── Server Cards Grid
│   └── Server Card:
│       ├── Flag + Name
│       ├── Status badge (Online/Offline)
│       ├── Load: 45%
│       ├── Latency: 20ms
│       ├── Rules summary
│       └── [Connect] → opens devices.html with server pre-selected
└── VIP Server (shown first for VIP users)
    ├── 👑 badge
    └── "Your Dedicated Server"

API CALLS:
- GET /api/servers/list.php
```

### 4. billing.html - Subscription & Payments
```
SECTIONS:
├── Current Plan Card
│   ├── Plan name + price
│   ├── Status badge
│   ├── Trial countdown (if trial)
│   ├── Next billing date
│   └── [Cancel] button (if paid)
├── Upgrade Section (if not max plan)
│   └── Plan comparison cards
│       ├── Features list
│       └── [Upgrade to X] button → PayPal
├── Payment Method
│   ├── PayPal email (if connected)
│   └── [Update] button
└── Payment History Table
    ├── Date | Amount | Status | Receipt
    └── [Download] links

API CALLS:
- GET /api/billing/subscription.php
- POST /api/billing/checkout.php
- GET /api/billing/history.php
```

### 5. settings.html - Account Settings
```
SECTIONS:
├── Profile Section
│   ├── Email (read-only)
│   ├── First name input
│   ├── Last name input
│   └── [Save Changes] button
├── Security Section
│   ├── Current password
│   ├── New password
│   ├── Confirm password
│   └── [Change Password] button
└── Danger Zone
    └── [Delete Account] button
        └── Confirm modal: "Type DELETE to confirm"

API CALLS:
- GET /api/user/profile.php
- PUT /api/user/profile.php
- PUT /api/user/password.php
```

---

## 🤖 AUTOMATION WORKFLOWS

### 1. New User Registration
```
TRIGGER: User submits registration form

ACTIONS:
1. Create user record in users.db
2. Hash password with bcrypt
3. Generate UUID
4. Check if email is VIP → set is_vip = 1
5. Create subscription in billing.db:
   - plan_type: 'trial'
   - status: 'active'
   - trial_ends_at: NOW + 14 days
6. Queue welcome email
7. Log registration event
8. Return JWT token

NO MANUAL WORK REQUIRED
```

### 2. Trial Expiration
```
TRIGGER: Cron job runs every hour

CHECK: subscriptions WHERE plan_type = 'trial' AND trial_ends_at <= NOW

ACTIONS:
1. 3 days before: Queue "Trial ending soon" email
2. 1 day before: Queue "Last day of trial" email  
3. On expiration:
   - Set status = 'suspended'
   - Disconnect all devices from VPN servers
   - Queue "Trial ended" email
   - Log suspension

NO MANUAL WORK REQUIRED
```

### 3. Payment Success (PayPal Webhook)
```
TRIGGER: POST /api/billing/webhook.php
EVENT: BILLING.SUBSCRIPTION.ACTIVATED or PAYMENT.SALE.COMPLETED

ACTIONS:
1. Verify PayPal signature
2. Find user by PayPal subscription ID
3. Update subscription:
   - plan_type: from PayPal
   - status: 'active'
   - current_period_end: from PayPal
4. If was suspended → reactivate devices
5. Queue "Payment received" email
6. Log payment

NO MANUAL WORK REQUIRED
```

### 4. Payment Failed
```
TRIGGER: PayPal webhook BILLING.SUBSCRIPTION.PAYMENT.FAILED

ACTIONS:
Day 0:
- Set status = 'grace_period'
- Queue "Payment failed" email

Day 3 (scheduled task):
- Queue "Payment still pending" email

Day 7 (scheduled task):
- Queue "Final warning" email

Day 10 (scheduled task):
- Set status = 'suspended'
- Disconnect all devices
- Queue "Account suspended" email

NO MANUAL WORK REQUIRED
```

### 5. VIP Detection
```
TRIGGER: Any auth check (login, register, API call)

CHECK: Is user email in VIP list?
CURRENT VIP: seige235@yahoo.com

ACTIONS:
- Mark user as VIP
- Force server_id = 2 (dedicated St. Louis)
- Unlimited devices
- Bypass payment requirements
- Show dedicated server in server list

VIP LIST IS IN: api/helpers/vip.php
```

---

## 🖥️ VPN SERVER INTEGRATION

### peer_api.py (Deploy to all 4 servers)

```python
#!/usr/bin/env python3
"""
TrueVault VPN - Server Peer API
Manages WireGuard peers on VPN servers
"""

from flask import Flask, request, jsonify
import subprocess
import os
import ipaddress

app = Flask(__name__)
API_KEY = os.environ.get('TRUEVAULT_API_KEY', 'truevault-api-key-2026')

def verify_api_key():
    key = request.headers.get('X-API-Key')
    return key == API_KEY

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'healthy', 'server': 'truevault'})

@app.route('/add_peer', methods=['POST'])
def add_peer():
    if not verify_api_key():
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.json
    public_key = data.get('public_key')
    allowed_ips = data.get('allowed_ips', '10.0.0.0/32')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'Public key required'}), 400
    
    try:
        # Add peer to WireGuard
        cmd = f'wg set wg0 peer {public_key} allowed-ips {allowed_ips}'
        subprocess.run(cmd, shell=True, check=True)
        
        # Save config
        subprocess.run('wg-quick save wg0', shell=True, check=True)
        
        return jsonify({'success': True, 'message': 'Peer added'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/remove_peer', methods=['POST'])
def remove_peer():
    if not verify_api_key():
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    data = request.json
    public_key = data.get('public_key')
    
    if not public_key:
        return jsonify({'success': False, 'error': 'Public key required'}), 400
    
    try:
        cmd = f'wg set wg0 peer {public_key} remove'
        subprocess.run(cmd, shell=True, check=True)
        subprocess.run('wg-quick save wg0', shell=True, check=True)
        
        return jsonify({'success': True, 'message': 'Peer removed'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/status', methods=['GET'])
def status():
    if not verify_api_key():
        return jsonify({'success': False, 'error': 'Unauthorized'}), 401
    
    try:
        result = subprocess.run('wg show wg0', shell=True, capture_output=True, text=True)
        peer_count = result.stdout.count('peer:')
        
        return jsonify({
            'success': True,
            'peer_count': peer_count,
            'interface': 'wg0',
            'status': 'active'
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

@app.route('/public_key', methods=['GET'])
def public_key():
    try:
        result = subprocess.run('wg show wg0 public-key', shell=True, capture_output=True, text=True)
        return jsonify({'success': True, 'public_key': result.stdout.strip()})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
```

### Deployment Commands
```bash
# For each server (66.94.103.91, 144.126.133.253, 66.241.124.4, 66.241.125.247)

# 1. Copy API script
scp peer_api.py root@{server}:/opt/truevault/

# 2. Install dependencies
ssh root@{server} 'pip3 install flask'

# 3. Create systemd service
cat > /etc/systemd/system/truevault-peer-api.service << 'EOF'
[Unit]
Description=TrueVault Peer API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
Environment="TRUEVAULT_API_KEY=truevault-api-key-2026"
ExecStart=/usr/bin/python3 /opt/truevault/peer_api.py
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# 4. Enable and start
systemctl enable truevault-peer-api
systemctl start truevault-peer-api

# 5. Verify
curl http://localhost:8080/health
```

---

## 📧 EMAIL TEMPLATES (Essential Only)

### 1. Welcome Email
```
Subject: Welcome to TrueVault VPN! 🔐

Hi {first_name},

Your TrueVault account is ready! You have 14 days of free access.

Quick Start:
1. Log in at https://vpn.the-truth-publishing.com/dashboard
2. Click "Add Device" 
3. Download your config file
4. Import into WireGuard app

Need help? Reply to this email.

— TrueVault Team
```

### 2. Trial Ending
```
Subject: Your trial ends in {days} days

Hi {first_name},

Your TrueVault trial ends on {trial_end_date}.

Upgrade now to keep your devices connected:
{upgrade_link}

Plans start at just $9.99/month.

— TrueVault Team
```

### 3. Payment Failed
```
Subject: ⚠️ Payment failed - action needed

Hi {first_name},

We couldn't process your payment of ${amount}.

Please update your payment method to avoid service interruption:
{billing_link}

— TrueVault Team
```

### 4. Payment Received
```
Subject: ✅ Payment received - ${amount}

Hi {first_name},

Thank you! We've received your payment of ${amount}.

Your subscription is active until {next_billing_date}.

Receipt: {receipt_link}

— TrueVault Team
```

---

## 🚀 DEPLOYMENT CHECKLIST

### Step 1: Prepare Databases
```bash
# Create database directories
mkdir -p databases/{core,vpn,billing,cms,logs}

# Run setup script on production
curl https://vpn.the-truth-publishing.com/api/config/setup-all.php
```

### Step 2: Upload Files
```bash
# FTP upload entire project
# Set permissions: 755 directories, 644 files
# Set 777 on databases/ directory
```

### Step 3: Deploy Server APIs
```bash
# Deploy peer_api.py to all 4 VPN servers
# Configure API keys
# Start systemd services
# Verify health endpoints
```

### Step 4: Configure Email
```bash
# Edit api/helpers/mailer.php
# Set SMTP credentials OR enable PHP mail()
# Test email delivery
```

### Step 5: Setup Cron
```bash
# Add to crontab:
*/5 * * * * php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/api/cron/process.php
```

### Step 6: PayPal Configuration
```
1. Verify webhook URL in PayPal dashboard
2. Set PayPal mode to SANDBOX for testing
3. Create subscription plans in PayPal
4. Link plan IDs in billing.db
5. Switch to LIVE after testing
```

---

## ✅ SUCCESS CRITERIA

**Launch Ready When:**
- [ ] User can register and gets trial subscription
- [ ] User can add device in 2 clicks
- [ ] Config file downloads correctly
- [ ] VPN connection works (test with WireGuard)
- [ ] VIP user gets dedicated server
- [ ] Payment flow works (sandbox then live)
- [ ] Emails send automatically
- [ ] Cron jobs run without errors
- [ ] Admin can manage users and servers
- [ ] All pages load without console errors

**Admin Daily Routine:**
1. Check admin dashboard (1 min)
2. Review any failed payments (2 min)
3. Check server health (1 min)
4. Review new signups (1 min)
**TOTAL: 5 minutes/day**

---

**END OF MASTER BLUEPRINT v4**

Generated: January 13, 2026
This is the FINAL, COMPLETE specification for TrueVault VPN launch.
