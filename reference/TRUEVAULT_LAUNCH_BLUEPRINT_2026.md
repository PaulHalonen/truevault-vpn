# TRUEVAULT VPN - LAUNCH-FOCUSED MASTER BLUEPRINT
**Created:** January 14, 2026 - 3:05 AM CST  
**Status:** Action Plan Based on Complete Audit  
**Goal:** Launch in 2 weeks with core features only

---

## 🎯 MISSION

Build a **simple, automated VPN service** that:
1. Lets users connect to VPN in 2 clicks
2. Handles payments automatically
3. Requires 5 minutes/day admin work
4. Works perfectly for VIP user (seige235@yahoo.com)

**EXCLUDED FROM V1:**
- ❌ Mesh networking
- ❌ Regional identities
- ❌ Certificate system (except VPN certs)
- ❌ Camera dashboard
- ❌ Network scanner (separate tool)

---

## 📁 FILE STRUCTURE (SIMPLIFIED)

```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
├── databases/               # All SQLite databases
│   ├── core/
│   │   ├── users.db        # Users, sessions
│   │   └── admin.db        # Admin users
│   ├── vpn/
│   │   ├── vpn.db          # Connections, user_peers
│   │   └── servers.db      # VPN servers (4 total)
│   ├── billing/
│   │   └── billing.db      # Subscriptions, payments
│   ├── cms/
│   │   └── themes.db       # Theme variables
│   └── automation/
│       ├── automation.db   # Workflows, tasks
│       └── logs.db         # System logs
│
├── api/                    # Backend PHP APIs
│   ├── config/            # Database, JWT, settings
│   ├── helpers/           # Auth, VIP, mailer, response
│   ├── auth/              # Login, register, password reset
│   ├── devices/           # Device management (CORE)
│   ├── vpn/               # VPN connection, servers
│   ├── billing/           # PayPal, subscriptions
│   ├── admin/             # Admin panel APIs
│   └── cron/              # Scheduled tasks
│
├── public/                # User-facing pages
│   ├── index.html         # Landing page
│   ├── login.html         # Login form
│   ├── register.html      # Registration
│   ├── forgot.html        # Password reset request
│   ├── reset.html         # Password reset form
│   └── dashboard/         # User dashboard
│       ├── index.html     # Overview
│       ├── devices.html   # Device management (CORE)
│       ├── servers.html   # Server list
│       ├── billing.html   # Subscription + payments
│       └── settings.html  # Account settings
│
├── admin/                 # Admin panel
│   ├── index.html         # Dashboard
│   ├── users.html         # User management
│   ├── servers.html       # Server management
│   └── settings.html      # Site settings
│
├── downloads/             # Scanner tool downloads
│   └── scanner/
│       ├── truthvault_scanner.py
│       ├── run_scanner.bat
│       └── run_scanner.sh
│
└── server-scripts/        # VPN server scripts
    ├── peer_api.py        # WireGuard peer management
    └── install.sh         # Server setup
```

---

## 🗄️ DATABASE SCHEMA (ESSENTIAL ONLY)

### 1. users.db
```sql
-- Users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active',
    plan_type TEXT DEFAULT 'trial',
    is_vip INTEGER DEFAULT 0,
    trial_ends_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

-- User devices
CREATE TABLE user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT DEFAULT 'unknown',
    public_key TEXT UNIQUE NOT NULL,
    current_server_id INTEGER,
    assigned_ip TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_connected DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, device_name)
);
```

### 2. vpn.db
```sql
-- User peers (multiple devices per user per server)
CREATE TABLE user_peers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,
    public_key TEXT NOT NULL,
    assigned_ip TEXT NOT NULL,
    allowed_ips TEXT DEFAULT '0.0.0.0/0',
    is_active INTEGER DEFAULT 1,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    last_handshake DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, device_name),
    UNIQUE(assigned_ip),
    UNIQUE(public_key)
);
```

### 3. servers.db
```sql
CREATE TABLE vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    display_name TEXT NOT NULL,
    country TEXT NOT NULL,
    country_flag TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT,
    server_type TEXT DEFAULT 'shared',
    vip_user_email TEXT,
    status TEXT DEFAULT 'active',
    max_connections INTEGER DEFAULT 100,
    current_connections INTEGER DEFAULT 0,
    cpu_load INTEGER DEFAULT 0,
    latency_ms INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert 4 servers
INSERT INTO vpn_servers VALUES
(1, 'us-east', 'New York, USA', 'USA', '🇺🇸', '66.94.103.91', 51820, 8080, NULL, 'shared', NULL, 'active', 50, 0, 0, 0, datetime('now')),
(2, 'us-central', 'St. Louis, USA (VIP)', 'USA', '🇺🇸', '144.126.133.253', 51820, 8080, NULL, 'vip_dedicated', 'seige235@yahoo.com', 'active', 1, 0, 0, 0, datetime('now')),
(3, 'us-south', 'Dallas, USA', 'USA', '🇺🇸', '66.241.124.4', 51820, 8443, NULL, 'shared', NULL, 'active', 50, 0, 0, 0, datetime('now')),
(4, 'ca-east', 'Toronto, Canada', 'Canada', '🇨🇦', '66.241.125.247', 51820, 8080, NULL, 'shared', NULL, 'active', 50, 0, 0, 0, datetime('now'));
```

### 4. billing.db
```sql
-- Subscriptions
CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_type TEXT NOT NULL, -- 'trial', 'personal', 'family', 'business'
    status TEXT DEFAULT 'active',
    paypal_subscription_id TEXT,
    price REAL NOT NULL DEFAULT 0,
    trial_ends_at DATETIME,
    current_period_end DATETIME,
    cancelled_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    paypal_order_id TEXT,
    amount REAL NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 5. themes.db
```sql
-- Themes
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    is_active INTEGER DEFAULT 0
);

-- Theme variables
CREATE TABLE theme_variables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    category TEXT NOT NULL,
    variable_name TEXT NOT NULL,
    variable_value TEXT NOT NULL,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE(theme_id, category, variable_name)
);
```

### 6. automation.db
```sql
-- Email queue
CREATE TABLE email_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    recipient TEXT NOT NULL,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    attempts INTEGER DEFAULT 0,
    last_attempt DATETIME,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Scheduled tasks
CREATE TABLE scheduled_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_type TEXT NOT NULL,
    task_data TEXT,
    execute_at DATETIME NOT NULL,
    status TEXT DEFAULT 'pending',
    executed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔑 CORE APIs (ESSENTIAL)

### Authentication (`api/auth/`)

**login.php** ✅ COMPLETE
```php
POST /api/auth/login.php
Body: { "email": "user@example.com", "password": "secret" }
Returns: { "token": "jwt_token", "user": {...}, "subscription": {...} }
```

**register.php** ⚠️ NEEDS UPDATE
```php
POST /api/auth/register.php
Body: { "email": "user@example.com", "password": "secret", "first_name": "John" }
Actions:
1. Create user
2. Generate UUID
3. Create trial subscription (14 days)
4. Send welcome email
5. Return JWT token
Returns: { "token": "jwt_token", "user": {...} }
```

**forgot.php** ❌ NEW
```php
POST /api/auth/forgot.php
Body: { "email": "user@example.com" }
Actions:
1. Generate reset token
2. Store in password_resets table
3. Send email with reset link
Returns: { "success": true, "message": "Check your email" }
```

**reset.php** ❌ NEW
```php
POST /api/auth/reset.php
Body: { "token": "reset_token", "password": "new_password" }
Actions:
1. Validate token
2. Update password
3. Generate new JWT
4. Auto-login user
Returns: { "token": "jwt_token", "user": {...} }
```

### Devices (`api/devices/`)

**list.php** ⚠️ UPDATE
```php
GET /api/devices/list.php
Headers: Authorization: Bearer {token}
Returns: [
  {
    "id": 1,
    "device_name": "My Laptop",
    "device_type": "desktop",
    "server": {
      "id": 1,
      "name": "New York, USA",
      "flag": "🇺🇸",
      "status": "online"
    },
    "assigned_ip": "10.0.0.100",
    "last_connected": "2026-01-14T03:00:00Z",
    "is_active": true
  }
]
```

**add.php** ❌ COMPLETE REWRITE (v2)
```php
POST /api/devices/add.php
Headers: Authorization: Bearer {token}
Body: {
  "device_name": "My iPhone",
  "device_type": "mobile",
  "server_id": 1,
  "public_key": "base64_public_key_from_browser"
}

Actions:
1. Validate device limit (VIP = unlimited)
2. Check server availability
3. VIP check → if seige235@yahoo.com → force server_id=2
4. Call server peer API to add peer
5. Save to database
6. Return config data

Returns: {
  "device_id": 5,
  "assigned_ip": "10.0.0.105",
  "server": {
    "name": "New York, USA",
    "ip": "66.94.103.91",
    "port": 51820,
    "public_key": "server_public_key"
  },
  "dns": ["1.1.1.1", "8.8.8.8"]
}
```

**switch.php** ❌ NEW
```php
POST /api/devices/switch.php
Headers: Authorization: Bearer {token}
Body: {
  "device_id": 5,
  "new_server_id": 3,
  "new_public_key": "base64_new_key_from_browser"
}

Actions:
1. Get device details
2. Remove peer from old server
3. Add peer to new server
4. Update database
5. Return new config

Returns: { same as add.php }
```

**remove.php** ⚠️ UPDATE
```php
DELETE /api/devices/remove.php?device_id=5
Headers: Authorization: Bearer {token}

Actions:
1. Get device + server info
2. Call server API to remove peer
3. Delete from database

Returns: { "success": true }
```

**config.php** ❌ NEW
```php
GET /api/devices/config.php?device_id=5
Headers: Authorization: Bearer {token}

Actions:
1. Get device details
2. Get server public key
3. Return config data (same as add.php)

Returns: { config data }
```

### VPN (`api/vpn/`)

**servers.php** ⚠️ UPDATE
```php
GET /api/vpn/servers.php
Headers: Authorization: Bearer {token}

Actions:
1. Get user email
2. Check if VIP
3. If VIP → show dedicated server first
4. If not VIP → hide VIP servers
5. Return server list with status

Returns: [
  {
    "id": 1,
    "name": "New York, USA",
    "display_name": "New York, USA",
    "country": "USA",
    "flag": "🇺🇸",
    "status": "online",
    "load": 45,
    "latency": 20,
    "is_recommended": false
  },
  {
    "id": 2,
    "name": "St. Louis, USA (VIP)",
    "display_name": "St. Louis, USA - Dedicated",
    "country": "USA",
    "flag": "🇺🇸",
    "status": "online",
    "load": 5,
    "latency": 15,
    "is_recommended": true,
    "is_vip_only": true
  }
]
```

### Billing (`api/billing/`)

**subscription.php** ⚠️ UPDATE
```php
GET /api/billing/subscription.php
Headers: Authorization: Bearer {token}

Returns: {
  "plan_type": "trial",
  "status": "active",
  "trial_ends_at": "2026-01-28T00:00:00Z",
  "days_remaining": 14,
  "can_upgrade": true,
  "upgrade_url": "/dashboard/billing.html#upgrade"
}
```

**checkout.php** ⚠️ UPDATE
```php
POST /api/billing/checkout.php
Headers: Authorization: Bearer {token}
Body: { "plan": "personal" }

Actions:
1. Create PayPal subscription
2. Return approval URL

Returns: {
  "approval_url": "https://www.paypal.com/checkoutnow?token=..."
}
```

**webhook.php** ⚠️ UPDATE
```php
POST /api/billing/webhook.php (called by PayPal)

Actions:
1. Verify PayPal signature
2. Handle events:
   - PAYMENT.SALE.COMPLETED → activate subscription
   - BILLING.SUBSCRIPTION.CANCELLED → cancel subscription
   - BILLING.SUBSCRIPTION.SUSPENDED → suspend user
3. Update database
4. Send email notification
```

---

## 🎨 UI PAGES (SIMPLIFIED)

### Public Pages (4 total)

**1. Landing (index.html)** ⚠️ SIMPLIFY
```html
Sections:
- Hero: "Your Complete Digital Fortress" + "Start Free Trial" button
- Features: 3-4 key features with icons
- Pricing: 3 plans side-by-side
- FAQ: 5-10 expandable questions
- Footer: Links, copyright

Remove:
- Complex feature grid
- Multiple CTAs
- Advanced feature descriptions
```

**2. Login (login.html)** ⚠️ SIMPLIFY
```html
Form:
- Email input
- Password input
- "Forgot password?" link
- Submit button
- "Don't have an account? Sign up" link

Remove:
- Social login
- Remember me checkbox
- Extra links
```

**3. Register (register.html)** ⚠️ SIMPLIFY
```html
Form:
- Email input
- Password input (with strength indicator)
- First name input (optional)
- "No credit card required" message
- Submit button
- "Already have an account? Login" link

Actions:
- Create user
- Create trial subscription
- Auto-login
- Redirect to /dashboard/devices with welcome modal

Remove:
- Phone number
- Company field
- Marketing checkboxes
```

**4. Forgot Password (forgot.html)** ❌ NEW
```html
Form:
- Email input
- Submit button
- "Remember your password? Login" link

Success state:
- "Check your email for reset link"
- Countdown to resend (60 seconds)
```

**5. Reset Password (reset.html)** ❌ NEW
```html
Form:
- New password input
- Confirm password input
- Submit button

Actions:
- Validate token from URL
- Update password
- Auto-login
- Redirect to dashboard
```

### Dashboard Pages (5 total)

**1. Overview (index.html)** ⚠️ SIMPLIFY
```html
Sections:
- Welcome message with first name
- Current plan status (trial countdown or active subscription)
- Device usage (3/5 devices)
- Quick actions: "Add Device", "Upgrade Plan"
- Recent activity (last 5 connections)

Remove:
- Complex graphs
- Multiple stat cards
- Advanced features
```

**2. Devices (devices.html)** ❌ COMPLETE REBUILD
```html
THE MOST IMPORTANT PAGE

Components:
1. Device limit indicator (visual bar)
2. "Add Device" button (primary CTA)
3. Device grid:
   - Device name + type icon
   - Server location with flag
   - Status indicator (online/offline)
   - [Switch Server] button
   - [Download Config] button
   - [Remove] button

Modals:
1. Add Device Modal:
   - Device name input (auto-suggested)
   - Server selection (radio buttons with flags)
   - [Add Device & Download Config] button
   - On submit:
     * Generate keypair with TweetNaCl.js
     * Call API with public key
     * Generate .conf file
     * Auto-download file
     * Show WireGuard app links
   
2. Switch Server Modal:
   - Current server display
   - New server selection
   - [Switch & Download New Config] button
   - Same process as add

3. Welcome Modal (first time):
   - "Welcome to TrueVault!"
   - "Add your first device to get started"
   - [Let's Go] button → opens Add Device modal

Empty State:
- Icon + "No devices yet"
- "Add your first device to connect"
- Large "Add Device" button
```

**3. Servers (servers.html)** ⚠️ SIMPLIFY
```html
Components:
- Server list (cards or table)
- Each server shows:
  * Flag + Name
  * Status indicator
  * Load percentage
  * Latency
  * [Use This Server] button → redirects to devices page

VIP user:
- Dedicated server shows first
- Badge: "Your Dedicated Server"
- Different color/styling
```

**4. Billing (billing.html)** ⚠️ UPDATE
```html
Sections:
1. Current Plan:
   - Plan name (Trial / Personal / Family / Business)
   - Price
   - Renewal date or trial end date
   - [Upgrade] button (if on lower plan)
   - [Cancel] button (if paid)

2. Payment Method:
   - PayPal email (if connected)
   - [Update Payment Method] button

3. Payment History:
   - Table: Date, Amount, Status
   - [Download Invoice] links

Trial User:
- Prominent trial countdown
- Clear upgrade path
- "No credit card required" messaging
```

**5. Settings (settings.html)** ⚠️ SIMPLIFY
```html
Sections:
1. Profile:
   - Email (read-only)
   - First name
   - Last name
   - [Save Changes] button

2. Security:
   - Current password
   - New password
   - Confirm password
   - [Change Password] button

3. Danger Zone:
   - [Delete Account] button
   - Confirmation modal with "type DELETE to confirm"

Remove:
- Theme switching
- Notification preferences
- API keys section
- Advanced settings
```

---

## 🤖 AUTOMATION WORKFLOWS

### 1. New User Registration
```
Trigger: User submits registration form

Steps:
1. Create user account
2. Hash password
3. Generate UUID
4. Create trial subscription (14 days)
5. Send welcome email
6. Auto-login with JWT token
7. Redirect to /dashboard/devices
8. Show welcome modal

Timeline:
- Immediate: User created, logged in
- +1 minute: Welcome email sent
```

### 2. Trial Expiration Warning
```
Trigger: Cron job runs daily

Check: Users with trial_ends_at within 3 days

Steps:
1. Find users with trials ending soon
2. Skip if already upgraded
3. Send "Trial ending in X days" email
4. Include upgrade link

Timeline:
- 3 days before: First reminder
- 1 day before: Final reminder
- On expiration: Service suspended
```

### 3. Payment Success
```
Trigger: PayPal webhook (PAYMENT.SALE.COMPLETED)

Steps:
1. Verify webhook signature
2. Find user by PayPal subscription ID
3. Update subscription status to "active"
4. Update trial_ends_at to NULL
5. Update current_period_end
6. Send "Payment received" email
7. Generate invoice PDF
8. Log payment

Timeline:
- Immediate: User activated
- +1 minute: Receipt email sent
```

### 4. Payment Failed
```
Trigger: PayPal webhook (BILLING.SUBSCRIPTION.PAYMENT.FAILED)

Steps:
Day 0:
1. Update subscription status to "grace_period"
2. Send "Payment failed - please update" email

Day 3:
3. Send "Urgent: Payment still failed" email

Day 7:
4. Send "Final warning: Service will be suspended" email

Day 10:
5. Suspend user account (status = 'suspended')
6. Disconnect all devices
7. Send "Account suspended" email

Timeline:
- Immediate: Grace period starts
- +3 days: Second email
- +7 days: Final warning
- +10 days: Suspension
```

### 5. Subscription Cancelled
```
Trigger: User clicks "Cancel Subscription" OR PayPal webhook

Steps:
1. Show retention offer modal
2. If confirmed:
   - Set cancelled_at timestamp
   - Allow service until current_period_end
   - Send "We're sorry to see you go" email
3. On period end:
   - Set status to 'cancelled'
   - Disconnect all devices
   - Send "Subscription ended" email

Timeline:
- Immediate: Cancellation scheduled
- On period end: Service stopped
```

---

## 🖥️ SERVER INTEGRATION

### VPN Server Peer API (peer_api.py)

**Deployment:** All 4 servers  
**Port:** 8080 (8443 for Fly.io Dallas)  
**Authentication:** API key in header

**Endpoints:**

```python
GET /health
→ Returns server health status

POST /add_peer
Body: { "public_key": "...", "user_id": 123, "device_id": 456 }
→ Adds WireGuard peer
→ Returns: { "success": true, "assigned_ip": "10.0.0.100" }

POST /remove_peer
Body: { "public_key": "..." }
→ Removes WireGuard peer
→ Returns: { "success": true }

GET /status
→ Returns peer count, load, etc.

GET /public_key
→ Returns server's WireGuard public key
```

**Systemd Service:**
```ini
[Unit]
Description=TrueVault VPN Peer API
After=network.target wg-quick@wg0.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/truevault
ExecStart=/usr/bin/python3 /opt/truevault/peer_api.py
Restart=always
Environment="TRUEVAULT_API_KEY=your-secret-key-here"

[Install]
WantedBy=multi-user.target
```

---

## 🚀 DEPLOYMENT PROCESS

### Step 1: Database Setup (Production)
```bash
# Upload api/config/setup-all.php
# Visit: https://vpn.the-truth-publishing.com/api/config/setup-all.php
# Verify all tables created
# Check VIP user exists
# Check 4 servers inserted
```

### Step 2: FTP Upload
```powershell
# Upload all files
$files = @(
    "api/",
    "public/",
    "admin/",
    "downloads/",
    ".htaccess"
)

foreach ($file in $files) {
    # Upload via FTP
    # Set permissions: 755 dirs, 644 files
}

# Create databases directory
# Set 777 permissions on databases/
```

### Step 3: Server Deployment
```bash
# For each VPN server:

# 1. Copy peer_api.py
scp peer_api.py root@{server_ip}:/opt/truevault/

# 2. Copy systemd service
scp truevault-peer-api.service root@{server_ip}:/etc/systemd/system/

# 3. Set API key
ssh root@{server_ip} 'echo "TRUEVAULT_API_KEY=your-key" >> /etc/environment'

# 4. Start service
ssh root@{server_ip} 'systemctl enable truevault-peer-api && systemctl start truevault-peer-api'

# 5. Verify
curl http://{server_ip}:8080/health
```

### Step 4: Cron Setup
```bash
# Add to crontab on hosting:
*/5 * * * * php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/api/cron/process.php >> /var/log/truevault-cron.log 2>&1

# Test manually:
php /home/.../api/cron/process.php
```

### Step 5: Email Configuration
```php
// In api/helpers/mailer.php:

// Option 1: PHP mail() function (if enabled)
define('EMAIL_METHOD', 'mail');
define('EMAIL_FROM', 'noreply@vpn.the-truth-publishing.com');

// Option 2: SMTP (recommended)
define('EMAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

// Test:
// api/helpers/test-email.php
```

### Step 6: PayPal Webhook
```
1. Log into PayPal Developer Dashboard
2. Go to Webhooks
3. Add webhook URL:
   https://vpn.the-truth-publishing.com/api/billing/webhook.php
4. Select events:
   - PAYMENT.SALE.COMPLETED
   - BILLING.SUBSCRIPTION.CREATED
   - BILLING.SUBSCRIPTION.CANCELLED
   - BILLING.SUBSCRIPTION.SUSPENDED
5. Save webhook ID in api/config/constants.php
```

---

## ✅ LAUNCH CHECKLIST

### Pre-Launch (Day -1)
- [ ] All code uploaded via FTP
- [ ] Databases initialized (setup-all.php)
- [ ] VIP user verified (seige235@yahoo.com)
- [ ] 4 servers in database
- [ ] Theme variables loaded
- [ ] peer_api.py deployed to all 4 servers
- [ ] Server health checks passing
- [ ] Cron job configured and running
- [ ] Email system tested
- [ ] PayPal webhook configured
- [ ] PayPal in SANDBOX mode

### Day 0 (Soft Launch)
- [ ] Test full registration flow
- [ ] Test device addition (2 clicks)
- [ ] Test device connection to VPN
- [ ] Test VIP user gets dedicated server
- [ ] Test payment flow (sandbox)
- [ ] Test trial creation
- [ ] Test email delivery
- [ ] Monitor logs for errors

### Day 1-7 (Beta Testing)
- [ ] Invite VIP user (seige235@yahoo.com)
- [ ] Monitor VIP experience
- [ ] Fix any bugs found
- [ ] Verify automated emails
- [ ] Check cron job execution
- [ ] Monitor server health

### Day 7 (Go Live)
- [ ] Switch PayPal to LIVE mode
- [ ] Test real payment with test account
- [ ] Verify subscription activation
- [ ] Monitor payment webhooks
- [ ] Enable public registration
- [ ] Announce launch

---

## 📊 SUCCESS METRICS

**Day 1:**
- [ ] VIP user can register
- [ ] VIP user can add device
- [ ] VIP user can connect to dedicated server
- [ ] Config file downloads correctly
- [ ] VPN connection works

**Week 1:**
- [ ] 5+ registered users
- [ ] All automation working
- [ ] Zero manual interventions needed
- [ ] Email delivery rate > 95%
- [ ] Server uptime > 99%

**Week 4:**
- [ ] 20+ registered users
- [ ] 3+ paid subscribers
- [ ] Admin time < 5 minutes/day
- [ ] All servers operational
- [ ] VIP user satisfaction

---

## 🎯 FINAL RECOMMENDATIONS

### For Immediate Focus
1. **Device Workflow** - Get the 2-click flow perfect
2. **Server Integration** - Deploy peer_api.py and test
3. **Email System** - Configure and test welcome emails
4. **Payment Flow** - Complete trial → paid conversion

### For Post-Launch
1. Add camera dashboard
2. Add network scanner integration
3. Add mesh networking
4. Add regional identities
5. Build mobile apps (iOS/Android)

### For Scale
1. Add more servers (EU, Asia)
2. Implement load balancing
3. Add CDN for downloads
4. Build monitoring dashboard
5. Add customer support chat

---

**END OF LAUNCH-FOCUSED BLUEPRINT**

This blueprint focuses on **essential features only**.  
Get these working perfectly, then add advanced features.  
Launch in 2 weeks, iterate based on feedback.

Generated: January 14, 2026 - 3:05 AM CST
