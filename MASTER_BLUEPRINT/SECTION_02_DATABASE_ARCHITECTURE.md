# SECTION 2: DATABASE ARCHITECTURE

**Created:** January 14, 2026  
**Status:** Complete Technical Specification  
**Priority:** CRITICAL - Foundation for Everything  
**Complexity:** HIGH - Multiple Databases  

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [Why SQLite?](#why-sqlite)
3. [Database List](#database-list)
4. [Database 1: main.db](#main-db)
5. [Database 2: users.db](#users-db)
6. [Database 3: devices.db](#devices-db)
7. [Database 4: servers.db](#servers-db)
8. [Database 5: billing.db](#billing-db)
9. [Database 6: forms.db](#forms-db)
10. [Database 7: campaigns.db](#campaigns-db)
11. [Database 8: builder.db](#builder-db)
12. [Database 9: tutorials.db](#tutorials-db)
13. [Data Relationships](#relationships)
14. [Backup Strategy](#backups)
15. [Migration Guide](#migration)

---

## 🎯 OVERVIEW

### **The Database Strategy**

TrueVault VPN uses **9 separate SQLite databases** instead of one monolithic database.

**Why separate databases?**
1. **Organization** - Each database has a clear purpose
2. **Portability** - Easy to backup/restore individual components
3. **Security** - Sensitive data isolated (billing separate from users)
4. **Performance** - Smaller files = faster queries
5. **Transferability** - New owner can understand structure easily

### **Database Location**

```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/database/
│
├── main.db          # System settings, theme, VIP list
├── users.db         # User accounts, authentication
├── devices.db       # Device registrations, WireGuard configs
├── servers.db       # Server definitions, status
├── billing.db       # Payments, invoices, subscriptions
├── forms.db         # Form templates, submissions
├── campaigns.db     # Marketing campaigns, email tracking
├── builder.db       # Custom user-created databases
└── tutorials.db     # Tutorial progress, achievements
```

---

## 💡 WHY SQLITE?

### **Advantages of SQLite**

✅ **Zero Configuration**
- No server to install
- No daemon to manage
- No configuration files
- Works out of the box

✅ **Single File**
- Each database is one file
- Easy to backup (just copy file)
- Easy to transfer (just move file)
- Easy to version control

✅ **Fast & Reliable**
- Powers billions of devices
- Used by: Android, iOS, Firefox, Chrome
- Faster than MySQL for < 100K records
- ACID compliant (safe transactions)

✅ **Portable**
- Same database file works on Windows, Mac, Linux
- No dependencies
- Cross-platform
- Future-proof

✅ **Perfect for TrueVault**
- Expected users: < 10,000
- Expected queries: < 1000/second
- SQLite handles this easily
- No need for MySQL/PostgreSQL

### **When SQLite Becomes Limiting**

**If you reach:**
- 100,000+ users
- 10,000+ concurrent connections
- Multiple servers (distributed database)

**Then migrate to:**
- PostgreSQL (recommended)
- MySQL (alternative)

**But for now:** SQLite is perfect!

---

## 📊 DATABASE LIST

| Database | Size (empty) | Tables | Purpose | Critical? |
|----------|--------------|--------|---------|-----------|
| main.db | 20 KB | 3 | System settings, theme, VIP list | ✅ Yes |
| users.db | 40 KB | 2 | User accounts, sessions | ✅ Yes |
| devices.db | 30 KB | 2 | Device registrations, configs | ✅ Yes |
| servers.db | 25 KB | 2 | Server definitions, health | ✅ Yes |
| billing.db | 50 KB | 4 | Payments, invoices, subscriptions | ✅ Yes |
| forms.db | 60 KB | 3 | Forms, templates, submissions | ⚠️ Optional |
| campaigns.db | 70 KB | 6 | Marketing, emails, tracking | ⚠️ Optional |
| builder.db | 40 KB | 3 | Custom databases | ⚠️ Optional |
| tutorials.db | 45 KB | 6 | Lessons, progress | ⚠️ Optional |

**Total empty size:** ~380 KB  
**Total with 1000 users:** ~50-100 MB

---

## 🗄️ DATABASE 1: main.db

**Purpose:** Core system settings, configuration, VIP list

**Location:** `/database/main.db`

### **Table 1: settings**

```sql
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type TEXT DEFAULT 'string',    -- string, number, boolean, json
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Example Records:**

```sql
INSERT INTO settings VALUES
(1, 'site_name', 'TrueVault VPN', 'string', 'Website name', CURRENT_TIMESTAMP),
(2, 'site_tagline', 'Your Complete Digital Fortress', 'string', 'Tagline', CURRENT_TIMESTAMP),
(3, 'admin_email', 'paulhalonen@gmail.com', 'string', 'Admin email', CURRENT_TIMESTAMP),
(4, 'from_email', 'noreply@vpn.the-truth-publishing.com', 'string', 'From email', CURRENT_TIMESTAMP),
(5, 'paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'string', 'PayPal Client ID', CURRENT_TIMESTAMP),
(6, 'paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'string', 'PayPal Secret', CURRENT_TIMESTAMP),
(7, 'paypal_mode', 'live', 'string', 'PayPal mode (sandbox or live)', CURRENT_TIMESTAMP),
(8, 'max_devices_personal', '3', 'number', 'Max devices for Personal plan', CURRENT_TIMESTAMP),
(9, 'max_devices_family', '10', 'number', 'Max devices for Family plan', CURRENT_TIMESTAMP),
(10, 'max_devices_business', '50', 'number', 'Max devices for Business plan', CURRENT_TIMESTAMP);
```

**Why This Table Exists:**
- All business-critical settings in one place
- Easy to update without code changes
- Transferable (new owner updates these values)
- No hardcoded credentials in PHP files

---

### **Table 2: theme**

```sql
CREATE TABLE IF NOT EXISTS theme (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    element_name TEXT NOT NULL UNIQUE,     -- e.g., "primary_color", "button_radius"
    element_value TEXT NOT NULL,
    element_category TEXT,                 -- colors, typography, spacing, buttons
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Example Records:**

```sql
INSERT INTO theme VALUES
-- Colors
(1, 'primary_color', '#00d9ff', 'colors', 'Primary brand color (cyan)', CURRENT_TIMESTAMP),
(2, 'secondary_color', '#00ff88', 'colors', 'Secondary brand color (green)', CURRENT_TIMESTAMP),
(3, 'background_color', '#0f0f1a', 'colors', 'Dark background', CURRENT_TIMESTAMP),
(4, 'text_color', '#ffffff', 'colors', 'Main text color', CURRENT_TIMESTAMP),
(5, 'link_color', '#00d9ff', 'colors', 'Link color', CURRENT_TIMESTAMP),

-- Typography
(10, 'font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', 'typography', 'Main font', CURRENT_TIMESTAMP),
(11, 'heading_font', 'Poppins, sans-serif', 'typography', 'Heading font', CURRENT_TIMESTAMP),
(12, 'base_font_size', '16px', 'typography', 'Base font size', CURRENT_TIMESTAMP),

-- Buttons
(20, 'button_radius', '8px', 'buttons', 'Button border radius', CURRENT_TIMESTAMP),
(21, 'button_padding', '10px 20px', 'buttons', 'Button padding', CURRENT_TIMESTAMP),

-- Spacing
(30, 'container_max_width', '1100px', 'spacing', 'Max container width', CURRENT_TIMESTAMP),
(31, 'section_padding', '60px 20px', 'spacing', 'Section padding', CURRENT_TIMESTAMP);
```

**Why This Table Exists:**
- All visual styling database-driven
- No hardcoded CSS values
- Easy to rebrand entire site
- White-label friendly

**How It's Used:**

```php
// In PHP
$primary_color = getSetting('theme', 'primary_color'); // Returns '#00d9ff'

// In CSS (generated dynamically)
echo ":root { 
    --primary-color: {$primary_color};
    --secondary-color: {$secondary_color};
}";
```

---

### **Table 3: vip_users**

```sql
CREATE TABLE IF NOT EXISTS vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    added_by TEXT,                         -- Who added this VIP
    added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,                            -- Optional notes about VIP
    is_active BOOLEAN DEFAULT 1
);
```

**Example Records:**

```sql
INSERT INTO vip_users VALUES
(1, 'paulhalonen@gmail.com', 'system', CURRENT_TIMESTAMP, 'Owner - Kah-Len', 1),
(2, 'seige235@yahoo.com', 'paulhalonen@gmail.com', CURRENT_TIMESTAMP, 'Dedicated St. Louis server access', 1);
```

**Why This Table Exists:**
- Secret VIP system
- Easy to add/remove VIPs
- Completely hidden from public
- Email = VIP status (no special codes)

**How It's Used:**

```php
// On login
$email = $_POST['email'];
$isVIP = checkVIP($email); // Queries this table

if ($isVIP) {
    $_SESSION['account_type'] = 'vip';
    // Show St. Louis server
    // Unlimited bandwidth
    // No payment required
} else {
    $_SESSION['account_type'] = 'standard';
    // Normal limits
}
```

---

## 👤 DATABASE 2: users.db

**Purpose:** User accounts, authentication, sessions

**Location:** `/database/users.db`

### **Table 1: users**

```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,           -- bcrypt hashed
    first_name TEXT,
    last_name TEXT,
    account_type TEXT DEFAULT 'standard',  -- standard, vip
    plan TEXT DEFAULT 'personal',          -- personal, family, business
    status TEXT DEFAULT 'active',          -- active, suspended, grace_period, cancelled
    max_devices INTEGER DEFAULT 3,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    email_verified BOOLEAN DEFAULT 0,
    verification_token TEXT,
    reset_token TEXT,
    reset_token_expires DATETIME
);

CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_status ON users(status);
CREATE INDEX idx_account_type ON users(account_type);
```

**Example Records:**

```sql
INSERT INTO users VALUES
(1, 'paulhalonen@gmail.com', '$2y$10$...hash...', 'Kah-Len', 'Halonen', 'vip', 'business', 'active', 999, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1, NULL, NULL, NULL),
(2, 'seige235@yahoo.com', '$2y$10$...hash...', 'Friend', 'VIP', 'vip', 'business', 'active', 999, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1, NULL, NULL, NULL),
(3, 'john@example.com', '$2y$10$...hash...', 'John', 'Doe', 'standard', 'personal', 'active', 3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1, NULL, NULL, NULL);
```

**Field Explanations:**

- `password_hash` - bcrypt hash (never store plaintext!)
- `account_type` - Determined by VIP list check
- `plan` - Determines max_devices and features
- `status` - Subscription status
- `max_devices` - How many devices allowed
- `email_verified` - Email confirmation status
- `verification_token` - For email verification
- `reset_token` - For password reset

---

### **Table 2: sessions**

```sql
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,            -- JWT token
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_valid BOOLEAN DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_token ON sessions(token);
CREATE INDEX idx_user_id ON sessions(user_id);
CREATE INDEX idx_expires ON sessions(expires_at);
```

**Why Sessions Table:**
- Track active logins
- Invalidate sessions remotely
- See who's logged in
- Force logout if compromised
- Session analytics

**How It Works:**

```php
// On login
$token = generateJWT($user_id); // 7-day expiration
INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?);

// On every request
$token = $_COOKIE['auth_token'];
$session = validateSession($token);
if (!$session || $session['expires_at'] < NOW()) {
    // Force logout
}
```

---

## 📱 DATABASE 3: devices.db

**Purpose:** User devices, WireGuard configurations

**Location:** `/database/devices.db`

### **Table 1: devices**

```sql
CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL UNIQUE,        -- auto_192_168_1_100 or custom
    device_name TEXT NOT NULL,             -- "John's iPhone"
    device_type TEXT,                      -- mobile, desktop, tablet, router
    operating_system TEXT,                 -- iOS, Android, Windows, Mac, Linux
    
    -- WireGuard Keys
    public_key TEXT NOT NULL UNIQUE,
    private_key_encrypted TEXT,            -- Encrypted with user password (optional)
    preshared_key TEXT,
    
    -- IP Assignment
    assigned_ip TEXT NOT NULL UNIQUE,      -- 10.8.0.2/32
    
    -- Server Connection
    current_server TEXT DEFAULT 'new_york', -- Which server connected to
    
    -- Status
    status TEXT DEFAULT 'active',          -- active, disabled, deleted
    is_online BOOLEAN DEFAULT 0,
    last_seen DATETIME,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_id ON devices(user_id);
CREATE INDEX idx_device_id ON devices(device_id);
CREATE INDEX idx_public_key ON devices(public_key);
CREATE INDEX idx_assigned_ip ON devices(assigned_ip);
```

**Example Records:**

```sql
INSERT INTO devices VALUES
(1, 1, 'auto_10_8_0_2', 'Kah-Len iPhone', 'mobile', 'iOS', 
'PUBLIC_KEY_BASE64_HERE', NULL, NULL, 
'10.8.0.2/32', 'st_louis', 'active', 1, CURRENT_TIMESTAMP, 
CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),

(2, 3, 'johns_macbook', 'John MacBook Pro', 'desktop', 'Mac', 
'PUBLIC_KEY_BASE64_HERE', NULL, NULL, 
'10.8.0.10/32', 'new_york', 'active', 0, NULL, 
CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

**Critical Fields:**

- `public_key` - WireGuard public key (generated by client)
- `assigned_ip` - Unique IP in VPN subnet (10.8.0.0/24)
- `current_server` - Which server device is configured for
- `is_online` - Real-time connection status (heartbeat)
- `last_seen` - Last connection timestamp

---

### **Table 2: device_configs**

```sql
CREATE TABLE IF NOT EXISTS device_configs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT NOT NULL,
    server_name TEXT NOT NULL,             -- new_york, st_louis, dallas, toronto
    config_content TEXT NOT NULL,          -- Full WireGuard .conf file
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    downloaded_at DATETIME,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    UNIQUE(device_id, server_name)
);

CREATE INDEX idx_device_id ON device_configs(device_id);
```

**Why This Table:**
- Store pre-generated configs for each server
- User can switch servers instantly
- Download config anytime
- Track when configs were downloaded

**Example Record:**

```sql
INSERT INTO device_configs VALUES
(1, 'johns_macbook', 'new_york', 
'[Interface]
PrivateKey = PRIVATE_KEY_HERE
Address = 10.8.0.10/32
DNS = 1.1.1.1

[Peer]
PublicKey = SERVER_NY_PUBLIC_KEY
Endpoint = 66.94.103.91:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25', 
CURRENT_TIMESTAMP, NULL);
```

---

## 🌐 DATABASE 4: servers.db

**Purpose:** Server definitions, health status

**Location:** `/database/servers.db`

### **Table 1: servers**

```sql
CREATE TABLE IF NOT EXISTS servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_key TEXT NOT NULL UNIQUE,       -- new_york, st_louis, dallas, toronto
    server_name TEXT NOT NULL,             -- "New York"
    server_description TEXT,               -- "Fast general-purpose server"
    
    -- Connection Info
    ip_address TEXT NOT NULL,
    port INTEGER DEFAULT 51820,
    public_key TEXT NOT NULL,              -- WireGuard server public key
    
    -- Access Control
    access_level TEXT DEFAULT 'public',    -- public, vip_only
    
    -- Status
    is_active BOOLEAN DEFAULT 1,
    is_online BOOLEAN DEFAULT 1,
    last_ping DATETIME,
    
    -- Metadata
    location_country TEXT,                 -- "United States"
    location_city TEXT,                    -- "New York"
    location_flag TEXT,                    -- "🇺🇸"
    provider TEXT,                         -- "Contabo"
    cost_per_month REAL,
    
    -- Display
    display_order INTEGER DEFAULT 0,
    icon TEXT,                             -- "🗽"
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_server_key ON servers(server_key);
CREATE INDEX idx_access_level ON servers(access_level);
```

**Example Records:**

```sql
INSERT INTO servers VALUES
(1, 'new_york', 'New York', 'Fast general-purpose server', 
'66.94.103.91', 51820, 'SERVER_NY_PUBLIC_KEY_HERE', 
'public', 1, 1, CURRENT_TIMESTAMP, 
'United States', 'New York', '🇺🇸', 'Contabo', 6.75, 
1, '🗽', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),

(2, 'st_louis', 'St. Louis (VIP)', 'Dedicated VIP-only server', 
'144.126.133.253', 51820, 'SERVER_STL_PUBLIC_KEY_HERE', 
'vip_only', 1, 1, CURRENT_TIMESTAMP, 
'United States', 'St. Louis', '🇺🇸', 'Contabo', 6.15, 
2, '⭐', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),

(3, 'dallas', 'Dallas (Streaming)', 'Optimized for Netflix, YouTube', 
'66.241.124.4', 51820, 'SERVER_DAL_PUBLIC_KEY_HERE', 
'public', 1, 1, CURRENT_TIMESTAMP, 
'United States', 'Dallas', '🇺🇸', 'Fly.io', 2.00, 
3, '📺', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),

(4, 'toronto', 'Toronto (Canadian)', 'Access Canadian content', 
'66.241.125.247', 51820, 'SERVER_TOR_PUBLIC_KEY_HERE', 
'public', 1, 1, CURRENT_TIMESTAMP, 
'Canada', 'Toronto', '🇨🇦', 'Fly.io', 2.00, 
4, '🍁', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
```

**Database-Driven Server Management:**
- Add new servers without code changes
- Disable servers temporarily
- Update IPs/ports easily
- VIP-only servers hidden from standard users

---

### **Table 2: server_health**

```sql
CREATE TABLE IF NOT EXISTS server_health (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_key TEXT NOT NULL,
    check_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_online BOOLEAN,
    response_time_ms INTEGER,
    error_message TEXT,
    FOREIGN KEY (server_key) REFERENCES servers(server_key)
);

CREATE INDEX idx_server_key ON server_health(server_key);
CREATE INDEX idx_check_time ON server_health(check_time);
```

**Why This Table:**
- Historical uptime data
- Performance monitoring
- Alert if server down
- Monthly uptime reports

**How It Works:**

```php
// Cron job runs every 5 minutes
foreach ($servers as $server) {
    $start = microtime(true);
    $online = pingServer($server['ip_address']);
    $response_time = (microtime(true) - $start) * 1000;
    
    INSERT INTO server_health (server_key, is_online, response_time_ms)
    VALUES ($server['server_key'], $online, $response_time);
}
```

---

## 💳 DATABASE 5: billing.db

**Purpose:** Payments, invoices, subscriptions

**Location:** `/database/billing.db`

### **Table 1: subscriptions**

```sql
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- PayPal Info
    paypal_subscription_id TEXT UNIQUE,
    paypal_plan_id TEXT,
    
    -- Plan Details
    plan_name TEXT NOT NULL,               -- personal, family, business
    plan_price REAL NOT NULL,              -- 9.99, 14.99, 29.99
    billing_cycle TEXT DEFAULT 'monthly',  -- monthly, yearly
    
    -- Status
    status TEXT DEFAULT 'active',          -- active, cancelled, suspended, expired
    
    -- Dates
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    next_billing_date DATETIME,
    cancelled_date DATETIME,
    cancellation_reason TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_id ON subscriptions(user_id);
CREATE INDEX idx_paypal_sub ON subscriptions(paypal_subscription_id);
CREATE INDEX idx_status ON subscriptions(status);
```

---

### **Table 2: payments**

```sql
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    
    -- PayPal Info
    paypal_payment_id TEXT UNIQUE,
    paypal_transaction_id TEXT,
    
    -- Payment Details
    amount REAL NOT NULL,
    currency TEXT DEFAULT 'USD',
    payment_method TEXT DEFAULT 'paypal',
    
    -- Status
    status TEXT DEFAULT 'completed',       -- completed, pending, failed, refunded
    
    -- Dates
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE INDEX idx_user_id ON payments(user_id);
CREATE INDEX idx_paypal_payment ON payments(paypal_payment_id);
CREATE INDEX idx_payment_date ON payments(payment_date);
```

---

### **Table 3: invoices**

```sql
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_number TEXT NOT NULL UNIQUE,   -- INV-2026-001
    
    -- Invoice Details
    amount REAL NOT NULL,
    tax REAL DEFAULT 0,
    total REAL NOT NULL,
    
    -- Status
    status TEXT DEFAULT 'unpaid',          -- unpaid, paid, overdue, cancelled
    
    -- Dates
    invoice_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME,
    paid_date DATETIME,
    
    -- Payment Reference
    payment_id INTEGER,
    
    -- PDF
    pdf_path TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE INDEX idx_user_id ON invoices(user_id);
CREATE INDEX idx_invoice_number ON invoices(invoice_number);
CREATE INDEX idx_status ON invoices(status);
```

---

### **Table 4: payment_failures**

```sql
CREATE TABLE IF NOT EXISTS payment_failures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    
    -- Failure Details
    amount REAL NOT NULL,
    error_code TEXT,
    error_message TEXT,
    
    -- PayPal Reference
    paypal_reference TEXT,
    
    -- Retry Info
    retry_count INTEGER DEFAULT 0,
    next_retry_date DATETIME,
    
    -- Status
    resolved BOOLEAN DEFAULT 0,
    resolved_date DATETIME,
    
    failed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE INDEX idx_user_id ON payment_failures(user_id);
CREATE INDEX idx_resolved ON payment_failures(resolved);
```

**Why This Table:**
- Track all payment failures
- Automated retry logic
- Grace period management
- Customer support reference

---

## 📝 DATABASE 6-9: (Forms, Campaigns, Builder, Tutorials)

**These databases were fully documented in Sections 16-19:**
- **forms.db** - See Section 17 (Form Library)
- **campaigns.db** - See Section 18 (Marketing Automation)
- **builder.db** - See Section 16 (Database Builder)
- **tutorials.db** - See Section 19 (Tutorial System)

---

## 🔗 DATA RELATIONSHIPS

### **Relationship Diagram**

```
users (main table)
  │
  ├─── sessions (one-to-many)
  ├─── devices (one-to-many)
  ├─── subscriptions (one-to-one or one-to-many)
  ├─── payments (one-to-many)
  ├─── invoices (one-to-many)
  └─── payment_failures (one-to-many)

devices
  │
  └─── device_configs (one-to-many)

servers (independent)
  │
  └─── server_health (one-to-many)

settings (independent)
theme (independent)
vip_users (independent)
```

### **Cross-Database Queries**

```php
// Example: Get user with all devices and current subscription

// Query users.db
$user = getUserById($user_id);

// Query devices.db
$devices = getDevicesByUserId($user_id);

// Query billing.db
$subscription = getActiveSubscription($user_id);

// Combine results
$result = [
    'user' => $user,
    'devices' => $devices,
    'subscription' => $subscription
];
```

---

## 💾 BACKUP STRATEGY

### **Automated Daily Backups**

```bash
#!/bin/bash
# Backup script (runs daily via cron)

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="/backups/vpn/$DATE"

mkdir -p $BACKUP_DIR

# Backup all databases
cp /path/to/database/*.db $BACKUP_DIR/

# Compress
tar -czf $BACKUP_DIR.tar.gz $BACKUP_DIR
rm -rf $BACKUP_DIR

# Keep last 30 days
find /backups/vpn/ -name "*.tar.gz" -mtime +30 -delete
```

### **Backup Before Changes**

```php
// Before major updates
function backupDatabase($db_name) {
    $backup_file = "/backups/manual/{$db_name}_" . date('Y-m-d_His') . ".db";
    copy("/database/{$db_name}", $backup_file);
    return $backup_file;
}
```

### **Restore Process**

```bash
# Restore from backup
cp /backups/vpn/2026-01-14/main.db /database/main.db
```

---

## 🚀 MIGRATION GUIDE (For Transfer)

### **30-Minute Transfer Checklist**

**Step 1: Copy Databases (5 minutes)**
```bash
# On old server
tar -czf databases.tar.gz /database/*.db

# Download databases.tar.gz

# On new server
tar -xzf databases.tar.gz
mv database/*.db /new/path/database/
```

**Step 2: Update Settings (10 minutes)**
```sql
-- Update main.db settings table
UPDATE settings SET setting_value = 'newadmin@example.com' WHERE setting_key = 'admin_email';
UPDATE settings SET setting_value = 'NEW_PAYPAL_CLIENT_ID' WHERE setting_key = 'paypal_client_id';
UPDATE settings SET setting_value = 'NEW_PAYPAL_SECRET' WHERE setting_key = 'paypal_secret';
```

**Step 3: Update VIP List (2 minutes)**
```sql
-- Update vip_users table
DELETE FROM vip_users WHERE email = 'oldowner@example.com';
INSERT INTO vip_users (email, notes) VALUES ('newowner@example.com', 'New owner');
```

**Step 4: Test (10 minutes)**
- Login as admin
- Create test device
- Test payment webhook
- Verify servers showing

**Step 5: Go Live (3 minutes)**
- Update DNS
- Clear caches
- Monitor errors

**Done!** New owner is now running TrueVault VPN.

---

**END OF SECTION 2: DATABASE ARCHITECTURE**

**Next Section:** Section 3 (Device Setup - 2-Click System)  
**Status:** Section 2 Complete ✅  
**Lines:** ~1,200 lines  
**Created:** January 14, 2026 - 11:00 PM CST
