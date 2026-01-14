# TRUEVAULT VPN - COMPLETE TECHNICAL BLUEPRINT
**Created:** January 14, 2026 - 7:00 AM CST  
**Version:** 1.0 - Master Reference Document  
**Author:** System Analysis from Complete Codebase Review

---

## TABLE OF CONTENTS

1. [SYSTEM OVERVIEW](#1-system-overview)
2. [CREDENTIALS & KEYS](#2-credentials--keys)
3. [DATABASE ARCHITECTURE](#3-database-architecture)
4. [AUTHENTICATION SYSTEM](#4-authentication-system)
5. [VIP SYSTEM (SECRET)](#5-vip-system-secret)
6. [BILLING & PAYPAL SYSTEM](#6-billing--paypal-system)
7. [VPN SERVER SYSTEM](#7-vpn-server-system)
8. [DEVICE MANAGEMENT](#8-device-management)
9. [CAMERA SYSTEM](#9-camera-system)
10. [CERTIFICATE SYSTEM](#10-certificate-system)
11. [IDENTITY SYSTEM](#11-identity-system)
12. [MESH NETWORK](#12-mesh-network)
13. [FRONTEND PAGES](#13-frontend-pages)
14. [API ENDPOINT REFERENCE](#14-api-endpoint-reference)
15. [DEPLOYMENT STATUS](#15-deployment-status)
16. [PRICING PLANS](#16-pricing-plans)
17. [SERVER SCRIPTS](#17-server-scripts)

---

## 1. SYSTEM OVERVIEW

### What is TrueVault VPN?

TrueVault VPN is an advanced VPN service with the following unique features:

1. **Smart Identity Router** - Persistent digital identities for each region
2. **Mesh Family Network** - Private overlay network for trusted devices
3. **Decentralized Bandwidth** - Shared bandwidth marketplace (future)
4. **Context-Aware Routing** - Automatic server selection based on usage
5. **Personal Certificates** - Each user gets unique PKI certificates
6. **IP Camera Support** - Direct streaming of home cameras through VPN
7. **Port Forwarding** - Expose local devices securely
8. **Network Scanner** - Auto-discover devices on home network
9. **VIP System** - Secret VIP access that bypasses payment entirely

### Architecture Diagram (Text-Based)

```
┌─────────────────────────────────────────────────────────────┐
│                    TRUEVAULT VPN SYSTEM                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐         ┌──────────────┐                 │
│  │   Frontend   │────────▶│  PHP Backend │                 │
│  │ (HTML/JS/CSS)│         │  (API Layer) │                 │
│  └──────────────┘         └──────┬───────┘                 │
│                                   │                          │
│                          ┌────────▼────────┐                │
│                          │  SQLite Databases │               │
│                          │  (18 databases)  │               │
│                          └────────┬────────┘                │
│                                   │                          │
│              ┌────────────────────┼────────────────────┐    │
│              │                    │                     │    │
│         ┌────▼────┐         ┌────▼────┐         ┌─────▼───┐│
│         │  VIP    │         │ Billing │         │   VPN   ││
│         │ Manager │         │ Manager │         │ Manager ││
│         └─────────┘         └────┬────┘         └────┬────┘│
│                                   │                    │     │
│                          ┌────────▼────────┐   ┌──────▼────┐│
│                          │  PayPal API     │   │  Peer API ││
│                          │  (Live Mode)    │   │ (Python)  ││
│                          └─────────────────┘   └──────┬────┘│
│                                                        │     │
│                                              ┌─────────▼────┐│
│                                              │  WireGuard   ││
│                                              │   Servers    ││
│                                              │ (4 locations)││
│                                              └──────────────┘│
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

**Backend:**
- PHP 7.4+ (API layer)
- Python 3.8+ (Peer management)
- SQLite 3 (Database)
- WireGuard (VPN protocol)

**Frontend:**
- HTML5
- JavaScript (ES6+)
- Tailwind CSS
- No frameworks (vanilla JS)

**Infrastructure:**
- Contabo VPS (2 servers)
- Fly.io (2 servers)
- GoDaddy Shared Hosting (website)
- PayPal Live API

**APIs:**
- PayPal Checkout API v2
- WireGuard
- Custom Peer Management API

---

## 2. CREDENTIALS & KEYS

### FTP Access
```
Host: the-truth-publishing.com
User: kahlen@the-truth-publishing.com
Pass: AndassiAthena8
Port: 21
```

### PayPal API (LIVE MODE)
```
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
Business Email: paulhalonen@gmail.com
Webhook: https://vpn.the-truth-publishing.com/api/billing/webhook.php
Webhook ID: 46924926WL757580D
Events: All Events
Mode: LIVE (production)
```

### JWT Authentication
```
Secret Key: TrueVault2026JWTSecretKey!@#$
Algorithm: HS256
Token Expiry: 604800 seconds (7 days)
```

### Peer API (Server Communication)
```
Secret: TrueVault2026SecretKey
Port: 8080
Authorization: Bearer TrueVault2026SecretKey
```

### GoDaddy cPanel
```
Username: 26853687
Password: Asasasas4!
URL: https://the-truth-publishing.com/cpanel
```

### Contabo Account
```
Email: paulhalonen@gmail.com
Password: Asasasas4!
URL: https://my.contabo.com
```

### Fly.io Account
```
Email: paulhalonen@gmail.com
Password: Asasasas4!
URL: https://fly.io/dashboard
```

---

## 3. DATABASE ARCHITECTURE

### Database Location

**Production Path:**
```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data/
```

**Local Development Path:**
```
E:\Documents\GitHub\truevault-vpn\data\
```

### Database File Structure (FLAT)

The system uses a FLAT database structure (all .db files in one /data/ folder):

```
/data/
├── users.db              # User accounts and profiles
├── sessions.db           # Active sessions and refresh tokens
├── admin_users.db        # Admin accounts
├── vpn.db                # VPN servers and connections
├── servers.db            # Server configurations
├── certificates.db       # User certificates (WireGuard keys)
├── identities.db         # Regional digital identities
├── devices.db            # Registered user devices
├── cameras.db            # IP camera configurations
├── port_forwarding.db    # Port forwarding rules
├── mesh.db               # Mesh network connections
├── subscriptions.db      # Active subscriptions
├── payments.db           # Payment history and invoices
├── pages.db              # CMS pages
├── themes.db             # UI themes (database-driven styling)
├── settings.db           # System settings
├── media.db              # Uploaded media files
├── emails.db             # Email templates and log
├── automation.db         # Automation workflows
├── logs.db               # System activity logs
├── notifications.db      # User notifications
├── analytics.db          # Usage analytics
├── bandwidth.db          # Bandwidth tracking
├── support.db            # Support tickets
└── vip.db                # VIP user list (SECRET)
```

### Database Schema Details

#### users.db - User Accounts
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active',  -- active, suspended, cancelled, refunded
    plan_type TEXT,  -- basic, family, dedicated
    is_vip INTEGER DEFAULT 0,
    email_verified INTEGER DEFAULT 0,
    email_verification_token TEXT,
    password_reset_token TEXT,
    password_reset_expires DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
```

#### vip.db - VIP Users (SECRET)
```sql
CREATE TABLE vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL DEFAULT 'vip_basic',  -- owner, vip_dedicated, vip_basic
    plan TEXT NOT NULL DEFAULT 'family',  -- dedicated, family, personal
    dedicated_server_id INTEGER,  -- NULL for owner, server ID for dedicated
    description TEXT,
    added_by TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_vip_email ON vip_users(email);
```

**SEED DATA (must be inserted on setup):**
```sql
INSERT INTO vip_users (email, type, plan, dedicated_server_id, description)
VALUES 
    ('paulhalonen@gmail.com', 'owner', 'dedicated', NULL, 'System Owner'),
    ('seige235@yahoo.com', 'vip_dedicated', 'dedicated', 2, 'VIP Dedicated - St. Louis Server');
```

#### subscriptions.db - Active Subscriptions
```sql
CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_type TEXT NOT NULL,  -- basic, family, dedicated
    status TEXT DEFAULT 'active',  -- active, cancelled, payment_failed, superseded
    payment_id TEXT,  -- PayPal order ID
    max_devices INTEGER,
    max_cameras INTEGER,
    start_date DATETIME,
    end_date DATETIME,
    cancelled_at DATETIME,
    cancel_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_subs_user ON subscriptions(user_id);
CREATE INDEX idx_subs_status ON subscriptions(status);
```

#### payments.db - Payment History
```sql
CREATE TABLE pending_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id TEXT UNIQUE NOT NULL,  -- PayPal order ID
    plan_id TEXT,
    amount REAL,
    status TEXT DEFAULT 'pending',  -- pending, completed, failed
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    invoice_number TEXT UNIQUE,  -- Format: TV-YYYYMMDD-####
    plan_id TEXT,
    amount REAL,
    payment_id TEXT,
    status TEXT DEFAULT 'paid',  -- paid, pending, failed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE webhook_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id TEXT UNIQUE,
    event_type TEXT,
    payload TEXT,
    processed INTEGER DEFAULT 0,
    processed_at DATETIME,
    error TEXT,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payment_failures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    failure_date DATETIME,
    grace_end_date DATETIME,
    notified INTEGER DEFAULT 0
);

CREATE TABLE scheduled_revocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    revoke_at DATETIME NOT NULL,
    status TEXT DEFAULT 'pending',  -- pending, completed, cancelled
    completed_at DATETIME,
    UNIQUE(user_id)  -- Only one scheduled revocation per user
);
```

#### vpn.db - VPN Servers & Connections
```sql
CREATE TABLE vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,  -- TrueVaultNY, TrueVaultSTL, etc.
    location TEXT,  -- New York, USA
    country TEXT,  -- US, CA
    ip_address TEXT NOT NULL,
    port INTEGER DEFAULT 51820,
    public_key TEXT NOT NULL,  -- Server's WireGuard public key
    network_prefix TEXT,  -- 10.0.0 for NY
    api_port INTEGER DEFAULT 8080,
    dns TEXT DEFAULT '1.1.1.1, 8.8.8.8',
    type TEXT,  -- shared, dedicated
    bandwidth_limit TEXT,  -- unlimited, limited
    is_vip INTEGER DEFAULT 0,
    vip_user_email TEXT,  -- Email of VIP who owns this server (if dedicated)
    allowed_uses TEXT,  -- Comma-separated: gaming,streaming,torrents,cameras
    instructions TEXT,  -- Usage instructions/rules
    status TEXT DEFAULT 'online',  -- online, offline, maintenance
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_peers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    public_key TEXT NOT NULL,  -- User's WireGuard public key
    assigned_ip TEXT,  -- 10.0.0.X
    status TEXT DEFAULT 'active',  -- active, revoked
    revoked_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);

CREATE TABLE vpn_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    server_id INTEGER NOT NULL,
    status TEXT,  -- active, disconnected
    assigned_ip TEXT,
    connected_at DATETIME,
    disconnected_at DATETIME
);
```

#### certificates.db - WireGuard Keys
```sql
CREATE TABLE user_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT,
    type TEXT DEFAULT 'wireguard',
    public_key TEXT UNIQUE NOT NULL,
    private_key TEXT NOT NULL,  -- Encrypted in production
    status TEXT DEFAULT 'active',  -- active, revoked
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### devices.db - User Devices
```sql
CREATE TABLE user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT UNIQUE NOT NULL,  -- dev_xxxxxxxx
    name TEXT NOT NULL,
    type TEXT,  -- xbox, playstation, camera, laptop, phone, etc.
    mac_address TEXT,
    ip_address TEXT,
    swapped_from TEXT,  -- device_id of previous device (if swapped)
    status TEXT DEFAULT 'active',  -- active, removed, swapped
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    removed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE user_cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    camera_id TEXT UNIQUE NOT NULL,  -- cam_xxxxxxxx
    name TEXT NOT NULL,
    type TEXT,  -- geeni, wyze, hikvision, generic
    ip_address TEXT NOT NULL,
    port INTEGER DEFAULT 554,  -- RTSP port
    server_id INTEGER NOT NULL,  -- Which VPN server to use
    status TEXT DEFAULT 'active',  -- active, removed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    removed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);
```

#### sessions.db - JWT Sessions
```sql
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    refresh_token TEXT,
    is_valid INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE refresh_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    is_revoked INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME
);
```

#### logs.db - Activity Logging
```sql
CREATE TABLE activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,  -- login, logout, vpn_connect, config_generated, etc.
    details TEXT,  -- JSON
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_log_user ON activity_log(user_id);
CREATE INDEX idx_log_action ON activity_log(action);
CREATE INDEX idx_log_created ON activity_log(created_at);
```

### Database Connection Code

**File:** `/api/config/database.php` (or `database_FIXED.php` for production)

The Database class provides:
- PDO connections with error handling
- Automatic directory creation
- Foreign key support
- Transaction support
- Helper methods: `query()`, `queryOne()`, `execute()`, `lastInsertId()`

**Usage Example:**
```php
// Get connection
$db = Database::getConnection('users');

// Query one row
$user = Database::queryOne('users', 
    "SELECT * FROM users WHERE email = ?", 
    [$email]
);

// Execute insert/update
Database::execute('users',
    "UPDATE users SET last_login = datetime('now') WHERE id = ?",
    [$userId]
);

// Get last insert ID
$newId = Database::lastInsertId('users');
```

---

## 4. AUTHENTICATION SYSTEM

### JWT Token Structure

TrueVault uses JWT (JSON Web Tokens) for authentication with the following structure:

**Token Components:**
```
Header.Payload.Signature
```

**Header:**
```json
{
  "typ": "JWT",
  "alg": "HS256"
}
```

**Payload:**
```json
{
  "user_id": 123,
  "email": "user@example.com",
  "is_admin": false,
  "is_vip": false,
  "iat": 1736854800,
  "exp": 1737459600
}
```

**Signature:**
```
HMACSHA256(
  base64UrlEncode(header) + "." + base64UrlEncode(payload),
  "TrueVault2026JWTSecretKey!@#$"
)
```

### Token Generation Process

**File:** `/api/helpers/auth.php`

```php
public static function generateToken($userId, $email, $isAdmin = false) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'is_admin' => $isAdmin,
        'is_vip' => VIPManager::isVIP($email),
        'iat' => time(),
        'exp' => time() + 604800  // 7 days
    ]);
    
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);
    
    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", SECRET_KEY, true);
    $base64Signature = base64UrlEncode($signature);
    
    return "$base64Header.$base64Payload.$base64Signature";
}
```

### Registration Flow

**Endpoint:** `POST /api/auth/register.php`

**Steps:**
1. User submits: email, password, first_name, last_name
2. System validates email format
3. System validates password length (min 8 chars)
4. System checks if email already exists
5. System checks VIP list (`VIPManager::isVIP($email)`)
6. System generates UUID
7. System hashes password with `PASSWORD_DEFAULT`
8. System inserts user into database with `is_vip` flag
9. If VIP: Auto-create subscription with 100-year expiration
10. System generates JWT token
11. System returns token + user data

**Request:**
```json
POST /api/auth/register.php
{
  "email": "user@example.com",
  "password": "SecurePass123",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 123,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "is_vip": false
    }
  }
}
```

### Login Flow

**Endpoint:** `POST /api/auth/login.php`

**Steps:**
1. User submits email + password
2. System finds user by email
3. System verifies password with `password_verify()`
4. System checks user status (must be 'active')
5. System updates last_login timestamp
6. System generates new JWT token
7. System fetches active subscription
8. System returns token + user data + subscription

**Request:**
```json
POST /api/auth/login.php
{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 123,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "is_vip": false,
      "vip_type": null
    },
    "subscription": {
      "id": 45,
      "plan_type": "family",
      "status": "active",
      "max_devices": 5,
      "max_cameras": 2,
      "end_date": "2026-02-14 12:00:00"
    }
  }
}
```

### Token Validation

**Process:**
1. Extract token from `Authorization: Bearer {token}` header
2. Split token into 3 parts (header, payload, signature)
3. Verify signature matches expected HMAC
4. Decode payload
5. Check expiration (`exp` field)
6. Return payload or null

**Code:**
```php
public static function validateToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    list($base64Header, $base64Payload, $base64Signature) = $parts;
    
    // Verify signature
    $signature = base64UrlDecode($base64Signature);
    $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", SECRET_KEY, true);
    
    if (!hash_equals($expectedSignature, $signature)) return null;
    
    // Decode payload
    $payload = json_decode(base64UrlDecode($base64Payload), true);
    
    // Check expiration
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }
    
    return $payload;
}
```

### Protected Endpoints

Any endpoint requiring authentication calls:
```php
$user = Auth::requireAuth();
```

This:
1. Gets token from header
2. Validates token
3. Gets user from database
4. Checks user status
5. Adds VIP info to user object
6. Returns user object OR sends 401 error

### Admin Authentication

**Endpoint:** `POST /api/auth/admin-login.php`

Separate from regular users. Admin accounts stored in `admin_users.db`:

```sql
CREATE TABLE admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name TEXT,
    role TEXT DEFAULT 'admin',
    status TEXT DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

Admin tokens have `is_admin: true` in payload.

Protected admin endpoints call:
```php
$admin = Auth::requireAdmin();
```

### Password Reset Flow

**Step 1:** Request Reset - `POST /api/auth/forgot-password.php`
1. User submits email
2. System generates random reset token (32 bytes)
3. System stores token + expiry (1 hour) in users table
4. System sends email with reset link
5. System returns success (even if email not found - prevents enumeration)

**Step 2:** Reset Password - `POST /api/auth/reset-password.php`
1. User submits token + new password + confirmation
2. System finds user with valid token (not expired)
3. System hashes new password
4. System updates password and clears reset token
5. System invalidates ALL existing sessions
6. System revokes ALL refresh tokens
7. User must login again with new password

### Email Verification

**Endpoint:** `GET /api/auth/verify-email.php?token=xxx`

1. Find user with verification token
2. Check if already verified
3. Set `email_verified = 1`
4. Clear verification token
5. Redirect to dashboard or return success

### Logout

**Endpoint:** `POST /api/auth/logout.php`

1. Get token from header
2. Validate token
3. Invalidate session (if tracking sessions)
4. Always return success (even if token invalid)

### Session Management

Optional feature - tokens can be tracked in `sessions.db`:

```sql
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL,
    is_valid INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME
);
```

Allows:
- Forced logout (invalidate specific token)
- Logout all devices (invalidate all user's tokens)
- Session listing

---

## 5. VIP SYSTEM (SECRET)

### Overview

The VIP system allows certain users to bypass payment entirely and get premium access. This is **NEVER advertised publicly** and users don't know they're VIP until they try to pay.

### VIP Detection

**When Checked:**
- During registration (auto-activate if VIP)
- During login (add VIP flag to user object)
- During checkout (bypass payment if VIP)
- During subscription checks (VIPs always have active subscription)
- During server access (VIPs can access VIP-only servers)

**How It Works:**

**File:** `/api/helpers/vip.php`

```php
public static function isVIP($email) {
    $email = strtolower(trim($email));
    $result = Database::queryOne('users',
        "SELECT id FROM vip_users WHERE LOWER(email) = ?",
        [$email]
    );
    return $result !== false && $result !== null;
}
```

### VIP Types

1. **Owner** (`type: 'owner'`)
   - System owner (paulhalonen@gmail.com)
   - Full access to everything
   - Can access ALL servers
   - Cannot be removed from VIP list
   
2. **VIP Dedicated** (`type: 'vip_dedicated'`)
   - Has own dedicated server
   - Unlimited devices/cameras
   - Can only access their dedicated server + shared servers
   - Example: seige235@yahoo.com (STL server)
   
3. **VIP Basic** (`type: 'vip_basic'`)
   - Free access to shared servers
   - Limited devices/cameras based on plan
   - Cannot access dedicated servers (unless upgraded)

### VIP Database Schema

**File:** `vip.db`

```sql
CREATE TABLE vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL DEFAULT 'vip_basic',
    plan TEXT NOT NULL DEFAULT 'family',
    dedicated_server_id INTEGER,
    description TEXT,
    added_by TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Initial VIP Seed Data

**MUST BE INSERTED ON SETUP:**

```sql
INSERT INTO vip_users (email, type, plan, dedicated_server_id, description, added_by)
VALUES 
    ('paulhalonen@gmail.com', 'owner', 'dedicated', NULL, 'System Owner', 'system_setup'),
    ('seige235@yahoo.com', 'vip_dedicated', 'dedicated', 2, 'VIP Dedicated - St. Louis Server', 'system_setup');
```

### VIP Registration Flow

When a VIP user registers:

1. Normal registration process starts
2. System checks: `VIPManager::isVIP($email)` → TRUE
3. System sets `is_vip = 1` in users table
4. System gets VIP plan type (`VIPManager::getVIPPlan($email)`)
5. System auto-creates subscription:
   ```sql
   INSERT INTO subscriptions 
   (user_id, plan_type, status, start_date, end_date)
   VALUES 
   (?, ?, 'active', datetime('now'), datetime('now', '+100 years'))
   ```
6. Subscription end date is 100 years in future (never expires)
7. User receives normal JWT token (no special indication)

### VIP Checkout Flow

When a VIP user tries to checkout:

**File:** `/api/billing/checkout.php`

```php
// Check if VIP
if (VIPManager::isVIP($user['email'])) {
    // Auto-activate subscription (no PayPal)
    BillingManager::activateSubscription($user['id'], 'vip_basic', null);
    
    return Response::success([
        'vip' => true,
        'message' => 'VIP access activated - no payment required'
    ]);
}
```

**Frontend sees:**
```json
{
  "success": true,
  "data": {
    "vip": true,
    "message": "VIP access activated - no payment required"
  }
}
```

Frontend skips PayPal redirect and shows success immediately.

### VIP Server Access

**File:** `/api/vpn/servers.php`

When listing servers:
```php
foreach ($allServers as $id => $server) {
    // Check VIP-only servers
    if ($server['vip_only']) {
        if (strtolower($server['vip_only']) === $userEmail) {
            $server['access'] = 'exclusive';
            $availableServers[] = $server;
        }
        continue; // Skip for non-VIP users
    }
    
    $server['access'] = 'available';
    $availableServers[] = $server;
}
```

**Example:** seige235@yahoo.com sees:
- Server 1 (NY) - Shared ✓
- Server 2 (STL) - Exclusive to them ✓
- Server 3 (TX) - Shared ✓
- Server 4 (CAN) - Shared ✓

Regular users see:
- Server 1 (NY) - Shared ✓
- Server 3 (TX) - Shared ✓
- Server 4 (CAN) - Shared ✓
- (Server 2 is hidden)

### VIP Subscription Management

VIP subscriptions:
- Never expire (`end_date` = 100 years from now)
- Status always 'active'
- No payment_id (payment_id = NULL)
- Unlimited devices/cameras (999)
- Cannot be cancelled via normal flow
- Payment failures ignored

**Check in code:**
```php
// Before processing payment failure:
if (VIPManager::isVIP($user['email'])) {
    return; // VIPs never lose access
}
```

### VIP Management (Admin Functions)

**Add VIP:**
```php
VIPManager::addVIP($email, $type, $plan, $dedicatedServerId, $description, $addedBy)
```

**Remove VIP:**
```php
VIPManager::removeVIP($email)
```
(Cannot remove owner)

**Update VIP:**
```php
VIPManager::updateVIP($email, ['plan' => 'dedicated', 'dedicated_server_id' => 2])
```

**Get All VIPs:**
```php
$vips = VIPManager::getAllVIPs();
```

**Get VIP Count:**
```php
$counts = VIPManager::getVIPCounts();
// Returns: ['owner' => 1, 'vip_dedicated' => 1, 'vip_basic' => 0, 'total' => 2]
```

### Security Notes

1. **Never mention "VIP" in public UI**
   - No "VIP Login" button
   - No "Are you a VIP?" prompts
   - VIPs log in like normal users
   
2. **VIP badge only shown in dashboard AFTER login**
   - "👑 VIP Dedicated" badge
   - "⭐ VIP" badge
   - Only visible to the VIP user themselves
   
3. **VIP list is in database, not hardcoded**
   - Admin can add/remove VIPs via admin panel
   - No code changes needed to add VIPs
   
4. **VIP access cannot be bypassed**
   - Even if user tries to pay, they're redirected to success
   - PayPal never charges VIPs
   
5. **VIP servers hidden from non-VIPs**
   - Server list API filters based on VIP access
   - Non-VIPs don't even know VIP servers exist

---

## 6. BILLING & PAYPAL SYSTEM

### PayPal Integration

**Mode:** LIVE (Production)

**API Base URL:** `https://api-m.paypal.com`

**Authentication:** OAuth 2.0 Client Credentials

**Token Request:**
```bash
curl -X POST https://api-m.paypal.com/v1/oauth2/token \
  -H "Accept: application/json" \
  -u "CLIENT_ID:SECRET" \
  -d "grant_type=client_credentials"
```

**Response:**
```json
{
  "access_token": "A21AAKxxx...",
  "token_type": "Bearer",
  "expires_in": 32400
}
```

### Pricing Plans

**File:** `/api/billing/billing-manager.php`

```php
$PLANS = [
    'basic' => [
        'name' => 'Basic',
        'price' => 9.99,
        'max_devices' => 3,
        'max_cameras' => 1,
        'camera_server' => 'ny',
        'features' => [
            '3 devices',
            '1 IP camera (NY only)',
            'All shared servers',
            'Network scanner'
        ]
    ],
    'family' => [
        'name' => 'Family',
        'price' => 14.99,
        'max_devices' => 5,
        'max_cameras' => 2,
        'camera_server' => 'ny',
        'features' => [
            '5 devices',
            '2 IP cameras',
            'All shared servers',
            'Device swapping',
            'Priority support'
        ]
    ],
    'dedicated' => [
        'name' => 'Dedicated',
        'price' => 29.99,
        'max_devices' => 999,
        'max_cameras' => 12,
        'camera_server' => 'any',
        'features' => [
            'Unlimited devices',
            '12 IP cameras',
            'Own dedicated server',
            'Static IP',
            'Port forwarding',
            'Terminal access'
        ]
    ],
    'vip_upgrade' => [
        'name' => 'VIP Dedicated Upgrade',
        'price' => 9.97,
        'max_devices' => 999,
        'max_cameras' => 12,
        'camera_server' => 'any',
        'features' => [
            'VIP exclusive rate',
            'Unlimited devices',
            '12 IP cameras',
            'Own dedicated server'
        ]
    ]
];
```

### Checkout Flow (Regular Users)

**Endpoint:** `POST /api/billing/checkout.php`

**Step 1:** User clicks "Subscribe to Family Plan"

**Step 2:** Frontend sends request:
```json
POST /api/billing/checkout.php
Authorization: Bearer {user_token}
{
  "plan_id": "family"
}
```

**Step 3:** Backend creates PayPal order:
```php
$data = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id' => "truevault_{$userId}_{$planId}_" . time(),
        'description' => "TrueVault VPN - Family Plan",
        'amount' => [
            'currency_code' => 'USD',
            'value' => '14.99'
        ],
        'custom_id' => json_encode([
            'user_id' => $userId,
            'plan_id' => 'family',
            'email' => $email
        ])
    ]],
    'application_context' => [
        'brand_name' => 'TrueVault VPN',
        'return_url' => 'https://vpn.the-truth-publishing.com/payment-success.html',
        'cancel_url' => 'https://vpn.the-truth-publishing.com/payment-cancel.html'
    ]
];

$result = PayPalAPI::request('POST', '/v2/checkout/orders', $data);
```

**Step 4:** Backend stores pending order:
```sql
INSERT INTO pending_orders (user_id, order_id, plan_id, amount, status)
VALUES (123, 'PAYPAL_ORDER_ID', 'family', 14.99, 'pending');
```

**Step 5:** Backend returns approval URL:
```json
{
  "success": true,
  "data": {
    "order_id": "PAYPAL_ORDER_ID",
    "approval_url": "https://www.paypal.com/checkoutnow?token=xxx"
  }
}
```

**Step 6:** Frontend redirects user to PayPal

**Step 7:** User approves payment on PayPal

**Step 8:** PayPal redirects back to:
```
https://vpn.the-truth-publishing.com/payment-success.html?token=PAYPAL_ORDER_ID
```

**Step 9:** Frontend calls complete endpoint:
```json
POST /api/billing/complete.php
Authorization: Bearer {user_token}
{
  "order_id": "PAYPAL_ORDER_ID"
}
```

**Step 10:** Backend captures payment:
```php
$result = PayPalAPI::capturePayment($orderId);
```

**Step 11:** Backend processes completion:
1. Update pending_orders to 'completed'
2. Create invoice (TV-YYYYMMDD-####)
3. Activate subscription
4. Provision VPN access (add user to servers)

**Step 12:** Backend returns:
```json
{
  "success": true,
  "message": "Payment completed",
  "data": {
    "invoice_id": "TV-20260114-0042"
  }
}
```

### Payment Capture

**File:** `/api/billing/billing-manager.php`

```php
public static function completePayment($orderId) {
    // Get pending order
    $order = Database::queryOne('billing',
        "SELECT * FROM pending_orders WHERE order_id = ? AND status = 'pending'",
        [$orderId]
    );
    
    // Capture payment via PayPal
    $result = PayPalAPI::capturePayment($orderId);
    
    if ($result['status'] === 201 && $result['data']['status'] === 'COMPLETED') {
        // Update order
        Database::execute('billing',
            "UPDATE pending_orders SET status = 'completed' WHERE order_id = ?",
            [$orderId]
        );
        
        // Create invoice
        $invoiceId = self::createInvoice($order['user_id'], $order['plan_id'], $order['amount'], $orderId);
        
        // Activate subscription
        self::activateSubscription($order['user_id'], $order['plan_id'], $orderId);
        
        // Provision VPN
        PeerManager::provisionUser($order['user_id']);
        
        return ['success' => true, 'invoice_id' => $invoiceId];
    }
    
    return ['success' => false, 'error' => 'Payment capture failed'];
}
```

### Subscription Activation

```php
public static function activateSubscription($userId, $planId, $paymentId) {
    $plan = $GLOBALS['PLANS'][$planId];
    
    // Deactivate old subscriptions
    Database::execute('billing',
        "UPDATE subscriptions SET status = 'superseded' WHERE user_id = ? AND status = 'active'",
        [$userId]
    );
    
    // Calculate end date (1 month)
    $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    // Create new subscription
    Database::execute('billing',
        "INSERT INTO subscriptions 
        (user_id, plan_type, status, payment_id, max_devices, max_cameras, start_date, end_date)
        VALUES (?, ?, 'active', ?, ?, ?, datetime('now'), ?)",
        [$userId, $planId, $paymentId, $plan['max_devices'], $plan['max_cameras'], $endDate]
    );
    
    // Update user's plan
    Database::execute('users',
        "UPDATE users SET plan_type = ?, status = 'active' WHERE id = ?",
        [$planId, $userId]
    );
}
```

### PayPal Webhook Handler

**Endpoint:** `POST /api/billing/webhook.php`

**Webhook URL:** `https://vpn.the-truth-publishing.com/api/billing/webhook.php`

**Events Handled:**

1. **CHECKOUT.ORDER.APPROVED**
   - Order approved by user
   - Auto-capture payment

2. **PAYMENT.CAPTURE.COMPLETED**
   - Payment successfully captured
   - Activate subscription
   - Provision VPN access

3. **PAYMENT.CAPTURE.DENIED / DECLINED**
   - Payment failed
   - Mark subscription as payment_failed
   - Start 7-day grace period

4. **BILLING.SUBSCRIPTION.CANCELLED**
   - User cancelled subscription
   - Schedule access revocation for end_date

5. **BILLING.SUBSCRIPTION.SUSPENDED**
   - Payment issues (retry failed)
   - Start grace period

6. **PAYMENT.CAPTURE.REFUNDED**
   - Refund issued
   - IMMEDIATELY revoke all VPN access
   - Set user status to 'refunded'

7. **CUSTOMER.DISPUTE.CREATED**
   - Chargeback/dispute opened
   - Log for manual review

**Webhook Logging:**

All webhooks are logged:
```sql
INSERT INTO webhook_log (webhook_id, payload, event_type, received_at)
VALUES (?, ?, ?, datetime('now'));
```

After processing:
```sql
UPDATE webhook_log 
SET processed = 1, processed_at = datetime('now') 
WHERE webhook_id = ?;
```

### Payment Failure Handling

**Grace Period:** 7 days

**Process:**
1. Payment fails
2. Update subscription: `status = 'payment_failed'`
3. Calculate grace end: `datetime('now', '+7 days')`
4. Insert into `payment_failures` table
5. Schedule revocation:
   ```sql
   INSERT INTO scheduled_revocations (user_id, revoke_at, status)
   VALUES (?, ?, 'pending');
   ```
6. Send notification email (TODO)

**After 7 Days:**

Cron job runs: `BillingManager::processRevocations()`

1. Get pending revocations where `revoke_at <= now()`
2. For each:
   - Check if user has paid since (active subscription)
   - If yes: Cancel revocation
   - If no: Revoke VPN access (`PeerManager::revokeAllAccess()`)
   - Update user: `status = 'suspended'`
   - Mark revocation: `status = 'completed'`

###VPN Provisioning

**File:** `/api/billing/billing-manager.php` - class `PeerManager`

**When User Pays:**

```php
public static function provisionUser($userId) {
    // Get or create WireGuard keys
    $userKey = self::getOrCreateUserKey($userId);
    
    // Determine accessible servers
    $accessibleServers = self::getAccessibleServers($user['email']);
    
    // Add peer to each server
    foreach ($accessibleServers as $serverId => $server) {
        $result = self::serverRequest($serverId, 'POST', '/peers/add', [
            'public_key' => $userKey['public_key'],
            'user_id' => $userId
        ]);
        
        if ($result['success']) {
            // Store peer record
            Database::execute('vpn',
                "INSERT INTO user_peers 
                (user_id, server_id, public_key, assigned_ip, status)
                VALUES (?, ?, ?, ?, 'active')",
                [$userId, $serverId, $userKey['public_key'], $result['allowed_ip']]
            );
        }
    }
}
```

**Server Request:**

```php
private static function serverRequest($serverId, $method, $endpoint, $data) {
    $server = self::$servers[$serverId];
    $url = "http://{$server['ip']}:{$server['api_port']}{$endpoint}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer TrueVault2026SecretKey'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
```

**Revoking Access:**

```php
public static function revokeAllAccess($userId) {
    // Get user's public key
    $userKey = Database::queryOne('certificates',
        "SELECT public_key FROM user_certificates 
         WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
        [$userId]
    );
    
    // Remove from all servers
    foreach (self::$servers as $serverId => $server) {
        self::serverRequest($serverId, 'POST', '/peers/remove', [
            'public_key' => $userKey['public_key']
        ]);
    }
    
    // Update peer records
    Database::execute('vpn',
        "UPDATE user_peers SET status = 'revoked', revoked_at = datetime('now') 
         WHERE user_id = ?",
        [$userId]
    );
}
```

### Invoice Generation

```php
public static function createInvoice($userId, $planId, $amount, $paymentId) {
    $invoiceNumber = 'TV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    Database::execute('billing',
        "INSERT INTO invoices (user_id, invoice_number, plan_id, amount, payment_id, status)
         VALUES (?, ?, ?, ?, ?, 'paid')",
        [$userId, $invoiceNumber, $planId, $amount, $paymentId]
    );
    
    return $invoiceNumber;
}
```

**Invoice Format:** `TV-YYYYMMDD-####`

Examples:
- TV-20260114-0001
- TV-20260114-0042
- TV-20260115-1234

### Subscription Management

**Get Current Subscription:**

```php
public static function getCurrentSubscription($userId) {
    $user = Auth::getUserById($userId);
    
    // VIPs bypass normal subscription
    if (VIPManager::isVIP($user['email'])) {
        $vipDetails = VIPManager::getVIPDetails($user['email']);
        return [
            'plan_type' => $vipDetails['plan'],
            'status' => 'active',
            'is_vip' => true,
            'max_devices' => 999,
            'max_cameras' => 12,
            'end_date' => null,
            'bypass_payment' => true
        ];
    }
    
    return Database::queryOne('billing',
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND status IN ('active', 'cancelled')
         ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
}
```

**Cancel Subscription:**

```php
public static function cancelSubscription($userId, $reason) {
    $sub = Database::queryOne('billing',
        "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
        [$userId]
    );
    
    // Mark as cancelled (remains active until end_date)
    Database::execute('billing',
        "UPDATE subscriptions 
         SET status = 'cancelled', cancelled_at = datetime('now'), cancel_reason = ? 
         WHERE id = ?",
        [$reason, $sub['id']]
    );
    
    // Schedule access revocation for end_date
    self::scheduleAccessRevocation($userId, $sub['end_date']);
    
    return [
        'success' => true,
        'message' => 'Subscription cancelled. Access continues until ' . $sub['end_date']
    ];
}
```

---

## 7. VPN SERVER SYSTEM

### Server Overview

TrueVault VPN operates 4 WireGuard servers across 2 hosting providers:

**Contabo (2 servers):**
- Server 1: New York (Shared)
- Server 2: St. Louis (VIP Dedicated)

**Fly.io (2 servers):**
- Server 3: Dallas (Shared, Streaming Only)
- Server 4: Toronto (Shared, Canadian Streaming)

### Server 1: New York (Shared)

**Hosting:** Contabo VPS  
**Location:** US-East  
**Type:** Shared (All users)

```
IP Address: 66.94.103.91
Port: 51820
Public Key: lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=
Network: 10.0.0.x
DNS: 1.1.1.1, 8.8.8.8
Peer API: http://66.94.103.91:8080
Config Name: TrueVaultNY.conf
```

**Contabo Details:**
```
VM ID: vmi2990026
Host System: 21597
Region: US-east
MAC Address: 00:50:56:5f:37:1f
IPv6: 2605:a142:2299:0026:0000:0000:0000:0001/64
VNC: 154.53.39.97:63031
Disk: 150 GB
Monthly Cost: $6.75
```

**Rules:**
```
Title: RECOMMENDED FOR HOME USE
Description: Full-featured server for all your home devices

ALLOWED:
  ✓ Xbox/PlayStation Gaming
  ✓ Torrents/P2P
  ✓ IP Cameras (all plans)
  ✓ Netflix/Streaming
  ✓ General browsing
  ✓ All traffic types

RESTRICTIONS: None

BANDWIDTH: Unlimited

BEST FOR:
  - Gaming consoles
  - IP cameras
  - Home devices
  - Daily browsing
```

**Accessible By:**
- All Basic, Family, Dedicated users
- All VIP users
- Owner

---

### Server 2: St. Louis (VIP Dedicated)

**Hosting:** Contabo VPS  
**Location:** US-Central  
**Type:** Dedicated (VIP Only)

```
IP Address: 144.126.133.253
Port: 51820
Public Key: qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=
Network: 10.0.1.x
DNS: 1.1.1.1, 8.8.8.8
Peer API: http://144.126.133.253:8080
Config Name: TrueVaultSTL.conf
VIP Only: seige235@yahoo.com
```

**Contabo Details:**
```
VM ID: vmi2990005
Host System: 22638
Region: US-central
MAC Address: 00:50:56:5f:37:1c
IPv6: 2605:a140:2299:0005:0000:0000:0000:0001/64
VNC: 207.244.248.38:63098
Disk: 150 GB
Monthly Cost: $6.15
```

**Rules:**
```
Title: PRIVATE DEDICATED SERVER
Description: Exclusively for VIP user. Unlimited bandwidth, no restrictions.

ALLOWED:
  ✓ Everything - No restrictions
  ✓ Unlimited bandwidth
  ✓ Static IP address
  ✓ Port forwarding
  ✓ Full terminal access

RESTRICTIONS: None

BANDWIDTH: Unlimited

BEST FOR:
  - VIP exclusive access
  - Mission-critical applications
  - High-bandwidth needs
```

**Accessible By:**
- seige235@yahoo.com ONLY
- Owner (paulhalonen@gmail.com)

---

### Server 3: Dallas (Shared, Streaming Only)

**Hosting:** Fly.io  
**Location:** Dallas, Texas  
**Type:** Shared (Streaming Only)

```
IP Address: 66.241.124.4
Port: 51820
Public Key: dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=
Network: 10.10.1.x
DNS: 1.1.1.1, 8.8.8.8
Peer API: http://66.241.124.4:8080
Config Name: TrueVaultTX.conf
```

**Fly.io Details:**
```
App Name: truevault-dallas
Machine Size: shared-1x-cpu@256MB
Region: dfw (Dallas)
Shared IPv4: 66.241.124.4
Release IPv4: 137.66.58.225
Latest Release: v6
Services:
  - WireGuard: :51820
  - Peer API: :8080 (→ 8443)
```

**Rules:**
```
Title: STREAMING ONLY
Description: Optimized for Netflix and streaming services. This IP is NOT flagged.

ALLOWED:
  ✓ Netflix (not flagged as VPN)
  ✓ Hulu
  ✓ Disney+
  ✓ Amazon Prime Video
  ✓ YouTube
  ✓ Streaming services

RESTRICTED:
  ✗ Gaming (high latency)
  ✗ Torrents/P2P (bandwidth)
  ✗ IP Cameras (use NY instead)
  ✗ Heavy downloads

BANDWIDTH: Limited

BEST FOR:
  - Netflix streaming
  - Video streaming
  - Light browsing
```

**Accessible By:**
- All Basic, Family, Dedicated users
- All VIP users
- Owner

---

### Server 4: Toronto (Shared, Canadian Streaming)

**Hosting:** Fly.io  
**Location:** Toronto, Canada  
**Type:** Shared (Canadian Streaming Only)

```
IP Address: 66.241.125.247
Port: 51820
Public Key: O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=
Network: 10.10.0.x
DNS: 1.1.1.1, 8.8.8.8
Peer API: http://66.241.125.247:8080
Config Name: TrueVaultCAN.conf
```

**Fly.io Details:**
```
App Name: truevault-toronto
Machine Size: shared-1x-cpu@256MB
Region: yyz (Toronto)
Shared IPv4: 66.241.125.247
Release IPv4: 37.16.6.139
Latest Release: v3
Services:
  - WireGuard: :51820
  - Peer API: :8080
```

**Rules:**
```
Title: CANADIAN STREAMING
Description: Access Canadian Netflix and streaming content. This IP is NOT flagged.

ALLOWED:
  ✓ Canadian Netflix
  ✓ CBC Gem
  ✓ Crave
  ✓ Canadian content
  ✓ Streaming services

RESTRICTED:
  ✗ Gaming (latency)
  ✗ Torrents/P2P
  ✗ IP Cameras (use NY instead)
  ✗ Heavy downloads

BANDWIDTH: Limited

BEST FOR:
  - Canadian Netflix
  - Canadian streaming
  - Light browsing
```

**Accessible By:**
- All Basic, Family, Dedicated users
- All VIP users
- Owner

---

### WireGuard Configuration

**Interface Settings:**
```ini
[Interface]
PrivateKey = {user_private_key}
Address = {assigned_ip}/32
DNS = 1.1.1.1, 8.8.8.8
```

**Peer Settings:**
```ini
[Peer]
PublicKey = {server_public_key}
Endpoint = {server_ip}:51820
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
```

### IP Assignment Logic

**Formula:**
```
Last Octet = (user_id % 250) + 2
Assigned IP = {network_prefix}.{last_octet}
```

**Examples:**
- User ID 1 → 10.0.0.3
- User ID 2 → 10.0.0.4
- User ID 100 → 10.0.0.102
- User ID 250 → 10.0.0.2
- User ID 251 → 10.0.0.3

**Why 250?** Leaves room for:
- `.1` = Server gateway
- `.254` = Reserved
- `.255` = Broadcast

### Server API Endpoints

Each server runs a Python Flask app on port 8080 that accepts:

**Authentication:**
```
Authorization: Bearer TrueVault2026SecretKey
```

**Endpoints:**

1. **POST /peers/add**
   ```json
   Request:
   {
     "public_key": "user_wireguard_public_key",
     "user_id": 123
   }
   
   Response:
   {
     "success": true,
     "message": "Peer added",
     "allowed_ip": "10.0.0.125"
   }
   ```

2. **POST /peers/remove**
   ```json
   Request:
   {
     "public_key": "user_wireguard_public_key"
   }
   
   Response:
   {
     "success": true,
     "message": "Peer removed"
   }
   ```

3. **GET /peers/list**
   ```json
   Response:
   {
     "success": true,
     "count": 42,
     "peers": [
       {
         "public_key": "xxx",
         "endpoint": "1.2.3.4:12345",
         "allowed_ips": "10.0.0.5/32",
         "latest_handshake": 1736854800,
         "transfer_rx": 1024000,
         "transfer_tx": 2048000
       }
     ]
   }
   ```

4. **GET /peers/status?public_key=xxx**
   ```json
   Response:
   {
     "success": true,
     "found": true,
     "peer": {
       "public_key": "xxx",
       "allowed_ips": "10.0.0.5/32",
       "latest_handshake": 1736854800
     }
   }
   ```

5. **GET /health**
   ```json
   Response:
   {
     "status": "healthy",
     "server": "TrueVaultNY",
     "interface": "wg0",
     "timestamp": "2026-01-14T12:00:00"
   }
   ```

### Connection Flow

**Step 1:** User authenticates and requests connection

```
POST /api/vpn/connect.php
Authorization: Bearer {jwt_token}
{
  "server_id": 1
}
```

**Step 2:** Backend validates:
- User has active subscription
- Server exists and is online
- User has access to this server (VIP check)
- Device limits not exceeded

**Step 3:** Backend gets/creates user's WireGuard keys:

```php
$userKey = PeerManager::getOrCreateUserKey($user['id']);
```

If keys don't exist:
```php
// Generate private key
$privateKey = base64_encode(random_bytes(32));

// Generate public key (using sodium if available)
$publicKey = base64_encode(sodium_crypto_scalarmult_base(base64_decode($privateKey)));

// Store in certificates.db
Database::execute('certificates',
    "INSERT INTO user_certificates (user_id, name, type, public_key, private_key, status)
     VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active')",
    [$userId, $publicKey, $privateKey]
);
```

**Step 4:** Backend checks if peer already exists:

```sql
SELECT * FROM user_peers 
WHERE user_id = ? AND server_id = ? AND status = 'active'
```

**Step 5:** If not exists, add peer to server:

```php
$result = PeerManager::serverRequest($serverId, 'POST', '/peers/add', [
    'public_key' => $userKey['public_key'],
    'user_id' => $userId
]);
```

**Step 6:** Server (peer_api.py) processes:

```python
def add_peer():
    public_key = data['public_key']
    user_id = data.get('user_id')
    
    # Get next available IP
    allowed_ip = get_next_ip()  # e.g., "10.0.0.15"
    
    # Add to WireGuard config
    with open('/etc/wireguard/wg0.conf', 'a') as f:
        f.write(f'''
[Peer]
# User: {user_id} - Added: {datetime.now().isoformat()}
PublicKey = {public_key}
AllowedIPs = {allowed_ip}/32
''')
    
    # Apply config
    subprocess.run(['wg', 'set', 'wg0', 'peer', public_key, 'allowed-ips', f'{allowed_ip}/32'])
    
    return {
        'success': True,
        'allowed_ip': allowed_ip
    }
```

**Step 7:** Backend stores peer record:

```sql
INSERT INTO user_peers (user_id, server_id, public_key, assigned_ip, status)
VALUES (?, ?, ?, ?, 'active')
```

**Step 8:** Backend generates WireGuard config:

```ini
[Interface]
# TrueVault VPN - New York
# Config: TrueVaultNY.conf
# User: user@example.com
# Generated: 2026-01-14 12:00:00

PrivateKey = {user_private_key}
Address = 10.0.0.15/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
# TrueVault New York Server
PublicKey = lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=
Endpoint = 66.94.103.91:51820
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
```

**Step 9:** Backend returns config to user:

```json
{
  "success": true,
  "message": "Configuration generated",
  "data": {
    "server": {
      "id": 1,
      "name": "New York",
      "location": "🇺🇸 New York",
      "ip": "66.94.103.91"
    },
    "assigned_ip": "10.0.0.15",
    "config_name": "TrueVaultNY.conf",
    "config": "... full WireGuard config ...",
    "instructions": [
      "1. Copy the configuration text below",
      "2. Open WireGuard on your device",
      "3. Click 'Add Tunnel' → 'Add empty tunnel'",
      "4. Paste the configuration",
      "5. Save as 'TrueVaultNY'",
      "6. Activate the tunnel"
    ]
  }
}
```

**Step 10:** User activates WireGuard tunnel

### Disconnection Flow

**Manual Disconnect:**
```
POST /api/vpn/disconnect.php
Authorization: Bearer {jwt_token}
{
  "server_id": 1
}
```

Backend logs disconnection but peer remains active on server (user can reconnect anytime).

**Forced Revocation (payment failure, refund, cancellation):**

```php
PeerManager::revokeAllAccess($userId);
```

This:
1. Gets user's public key from certificates.db
2. Calls `/peers/remove` on ALL servers
3. Updates `user_peers` table: `status = 'revoked'`
4. User cannot reconnect until they pay again

### Server Monitoring

**Health Check:**
```bash
curl http://66.94.103.91:8080/health \
  -H "Authorization: Bearer TrueVault2026SecretKey"
```

**Expected Response:**
```json
{
  "status": "healthy",
  "server": "TrueVaultNY",
  "interface": "wg0",
  "timestamp": "2026-01-14T12:00:00"
}
```

**Monitoring Dashboard (Admin):**
- Current peer count per server
- Server uptime
- Bandwidth usage per server
- Connection success rate
- Average latency

### Server Recommendations

Backend provides recommendations based on use case:

```json
{
  "recommendations": {
    "gaming": 1,           // NY - Low latency
    "streaming_us": 3,     // Dallas - Netflix not flagged
    "streaming_ca": 4,     // Toronto - Canadian content
    "cameras": 1,          // NY - Unlimited bandwidth
    "general": 1           // NY - All-purpose
  }
}
```

### Server Access Matrix

| Plan | NY (1) | STL (2) | TX (3) | CAN (4) |
|------|--------|---------|--------|---------|
| Basic | ✓ | ✗ | ✓ | ✓ |
| Family | ✓ | ✗ | ✓ | ✓ |
| Dedicated | ✓ | ✗ | ✓ | ✓ |
| VIP Basic | ✓ | ✗ | ✓ | ✓ |
| VIP Dedicated (seige235) | ✓ | ✓ (Exclusive) | ✓ | ✓ |
| Owner (paulhalonen) | ✓ | ✓ | ✓ | ✓ |

### Camera Server Restrictions

| Plan | Allowed Camera Servers |
|------|------------------------|
| Basic | NY only |
| Family | NY only |
| Dedicated | Any server |
| VIP | Any server |

**Reason:** TX and CAN have limited bandwidth. Cameras require consistent streaming.

---

## 8. DEVICE MANAGEMENT

### Device Limits by Plan

```
Basic:      3 devices, 1 camera
Family:     5 devices, 2 cameras (can swap)
Dedicated:  999 devices, 12 cameras (can swap)
VIP:        999 devices, 12 cameras (can swap)
```

### Device Registration

**Endpoint:** `POST /api/devices/register.php`

**Request:**
```json
{
  "name": "Xbox Series X",
  "type": "gaming_console",
  "mac_address": "00:1A:2B:3C:4D:5E",
  "ip_address": "192.168.1.50"
}
```

**Process:**
1. Get user's device limits
2. Count current active devices
3. Check if limit reached
4. If limit reached and plan supports swapping → Return swap error
5. If limit reached and no swap → Return upgrade error
6. Generate device ID: `dev_{16_hex_chars}`
7. Insert into `user_devices` table
8. Return device ID + remaining slots

**Response:**
```json
{
  "success": true,
  "data": {
    "device_id": "dev_a1b2c3d4e5f67890",
    "remaining": 2
  }
}
```

### Device Types

```
- laptop
- desktop
- phone
- tablet
- gaming_console (Xbox, PlayStation, Nintendo)
- smart_tv
- camera
- router
- iot_device
- unknown
```

### Device Swapping

**Available On:** Family, Dedicated, VIP plans

**Endpoint:** `POST /api/devices/swap.php`

**Use Case:** User has 5 devices (limit reached) and wants to replace one.

**Request:**
```json
{
  "old_device_id": "dev_abc123",
  "new_device": {
    "name": "New Xbox",
    "type": "gaming_console",
    "mac_address": "00:AA:BB:CC:DD:EE"
  }
}
```

**Process:**
1. Verify user has swap capability
2. Verify old device exists
3. Mark old device as `status = 'swapped'`
4. Register new device with `swapped_from = old_device_id`
5. Log the swap in activity_log

**Response:**
```json
{
  "success": true,
  "data": {
    "new_device_id": "dev_xyz789",
    "message": "Device swapped successfully"
  }
}
```

### Device Removal

**Endpoint:** `POST /api/devices/remove.php`

**Request:**
```json
{
  "device_id": "dev_abc123"
}
```

**Process:**
1. Verify device belongs to user
2. Update: `status = 'removed'`, `removed_at = now()`
3. Free up device slot

### Device Listing

**Endpoint:** `GET /api/devices/list.php`

**Response:**
```json
{
  "success": true,
  "data": {
    "devices": [
      {
        "device_id": "dev_abc123",
        "name": "Xbox Series X",
        "type": "gaming_console",
        "mac_address": "00:1A:2B:3C:4D:5E",
        "ip_address": "192.168.1.50",
        "status": "active",
        "created_at": "2026-01-10 14:30:00"
      }
    ],
    "limits": {
      "max_devices": 5,
      "current": 3,
      "remaining": 2,
      "can_swap": true
    }
  }
}
```

---

## 8.5. SIMPLIFIED 2-CLICK DEVICE SETUP (USER FLOW)

### Overview

The 2-click setup provides the easiest way for users to connect devices to TrueVault VPN without technical knowledge. The entire process is automated: key generation, server handshake, peer provisioning, and config file generation.

**User Experience:**
1. Click "Add Device" button
2. Enter device name
3. Select server
4. Click "Connect"
5. Download config file
6. Install in WireGuard app

**Time:** < 30 seconds from start to connected

---

### Step-by-Step User Flow

#### Step 1: Click "Add Device"

**Location:** Dashboard > Devices page  
**UI Element:** Large blue button "➕ Add New Device"

**Frontend Action:**
```javascript
document.getElementById('add-device-btn').addEventListener('click', () => {
    showModal('device-setup-modal');
    loadAvailableServers();
});
```

**Modal Opens:**
```
┌─────────────────────────────────────────┐
│  Add New Device                    ✕    │
├─────────────────────────────────────────┤
│                                         │
│  Device Name:                           │
│  ┌─────────────────────────────────┐   │
│  │ My Laptop                       │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Select Server:                         │
│  ┌─────────────────────────────────┐   │
│  │ 🗽 New York (Recommended)       │   │
│  │ 🤠 Dallas - Streaming Only      │   │
│  │ 🍁 Toronto - Canadian Content   │   │
│  └─────────────────────────────────┘   │
│                                         │
│          [Cancel]  [Connect] →          │
│                                         │
└─────────────────────────────────────────┘
```

---

#### Step 2: Enter Device Name

**Field:** Text input with validation  
**Required:** Yes  
**Max Length:** 50 characters  
**Validation:** Alphanumeric + spaces/dashes

**Smart Suggestions:**
- Detects OS: "Windows Laptop", "MacBook Pro", "Android Phone"
- Uses device type if detected: "Gaming Console", "iPhone"

**Frontend Code:**
```javascript
// Auto-detect platform
function suggestDeviceName() {
    const platform = navigator.platform;
    const userAgent = navigator.userAgent;
    
    if (platform.includes('Win')) return 'Windows Laptop';
    if (platform.includes('Mac')) return 'MacBook';
    if (userAgent.includes('Android')) return 'Android Phone';
    if (userAgent.includes('iPhone')) return 'iPhone';
    
    return 'My Device';
}

document.getElementById('device-name').value = suggestDeviceName();
```

---

#### Step 3: Select Server

**UI:** Dropdown with server cards

**Server Display:**
```
┌──────────────────────────────────────┐
│ 🗽 New York (Recommended)            │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ • Unlimited bandwidth                │
│ • Gaming optimized                   │
│ • IP cameras supported               │
│ • Latency: ~15ms                     │
│ • Status: 🟢 Online                  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ 🤠 Dallas                            │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ • Netflix/streaming ONLY             │
│ • Not flagged as VPN                 │
│ • Limited bandwidth                  │
│ • Status: 🟢 Online                  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ 🍁 Toronto                           │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ • Canadian content                   │
│ • CBC, Crave, Canadian Netflix       │
│ • Limited bandwidth                  │
│ • Status: 🟢 Online                  │
└──────────────────────────────────────┘
```

**Smart Recommendations:**

Backend provides recommendations based on:
- User's subscription plan
- Server restrictions (camera requirements)
- Geographic location
- Use case detection

**API Call:**
```javascript
const response = await apiClient.get('/api/vpn/servers.php');

// Response includes:
{
  "servers": [...],
  "recommended": 1,  // Server ID
  "reason": "All-purpose, unlimited bandwidth"
}
```

**Locked Servers (if applicable):**

If user has Basic/Family plan and tries to connect camera:
```
┌──────────────────────────────────────┐
│ 🤠 Dallas                     🔒     │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ Upgrade to Dedicated for camera      │
│ access on this server                │
└──────────────────────────────────────┘
```

---

#### Step 4: Click "Connect" (Backend Magic Happens)

**Button State Changes:**
```
[Connect] → → [⏳ Connecting...] → [✓ Connected!]
```

**Frontend Request:**
```javascript
async function connectDevice() {
    const deviceName = document.getElementById('device-name').value;
    const serverId = document.getElementById('server-select').value;
    
    // Show loading
    setButtonLoading(true);
    
    try {
        const response = await apiClient.post('/api/vpn/quick-connect.php', {
            device_name: deviceName,
            server_id: serverId
        });
        
        if (response.success) {
            // Show success + download button
            showDownloadScreen(response.data);
        }
    } catch (error) {
        showError(error.message);
    } finally {
        setButtonLoading(false);
    }
}
```

---

**Backend Process (Automated):**

**File:** `/api/vpn/quick-connect.php`

```php
<?php
require_once '../config/database.php';
require_once '../helpers/auth.php';
require_once '../helpers/response.php';
require_once '../helpers/peer-manager.php';

Auth::require();
$user = Auth::getUser();
$userId = $user['id'];

$data = json_decode(file_get_contents('php://input'), true);
$deviceName = $data['device_name'];
$serverId = $data['server_id'];

// STEP 1: Validate device limits
$deviceCount = Database::queryOne('devices',
    "SELECT COUNT(*) as count FROM user_devices 
     WHERE user_id = ? AND status = 'active'",
    [$userId]
)['count'];

$subscription = BillingManager::getCurrentSubscription($userId);
$maxDevices = $subscription['max_devices'];

if ($deviceCount >= $maxDevices) {
    Response::error('Device limit reached. Remove a device or upgrade plan.');
}

// STEP 2: Validate server access
$serverAccess = PeerManager::checkServerAccess($userId, $serverId);
if (!$serverAccess['allowed']) {
    Response::error($serverAccess['reason']);
}

// STEP 3: Get or create WireGuard keys for user
$userKey = PeerManager::getOrCreateUserKey($userId);

// STEP 4: Check if peer already exists on this server
$existingPeer = Database::queryOne('vpn',
    "SELECT * FROM user_peers 
     WHERE user_id = ? AND server_id = ? AND status = 'active'",
    [$userId, $serverId]
);

if (!$existingPeer) {
    // STEP 5: Add peer to server via Peer API
    $peerResult = PeerManager::addPeerToServer(
        $serverId,
        $userKey['public_key'],
        $userId
    );
    
    if (!$peerResult['success']) {
        Response::error('Failed to provision VPN access: ' . $peerResult['error']);
    }
    
    $assignedIp = $peerResult['allowed_ip'];
    
    // STEP 6: Store peer record in database
    Database::execute('vpn',
        "INSERT INTO user_peers (user_id, server_id, public_key, assigned_ip, status)
         VALUES (?, ?, ?, ?, 'active')",
        [$userId, $serverId, $userKey['public_key'], $assignedIp]
    );
} else {
    $assignedIp = $existingPeer['assigned_ip'];
}

// STEP 7: Register device
$deviceId = 'dev_' . bin2hex(random_bytes(8));
Database::execute('devices',
    "INSERT INTO user_devices (device_id, user_id, name, type, status)
     VALUES (?, ?, ?, 'vpn_device', 'active')",
    [$deviceId, $userId, $deviceName]
);

// STEP 8: Generate WireGuard config
$server = PeerManager::getServerDetails($serverId);
$config = PeerManager::generateConfig(
    $userKey['private_key'],
    $assignedIp,
    $server
);

// STEP 9: Return config + download info
Response::success([
    'device_id' => $deviceId,
    'server_name' => $server['name'],
    'config' => $config,
    'config_filename' => $server['config_filename'],
    'instructions' => [
        'windows' => 'Install WireGuard, import this file, click Activate',
        'mac' => 'Install WireGuard, drag this file to app, toggle on',
        'ios' => 'Install WireGuard app, scan QR code or import file',
        'android' => 'Install WireGuard app, tap + and import file'
    ]
]);
?>
```

**What Happens Automatically:**

1. ✅ Validates device limits
2. ✅ Validates server access
3. ✅ Generates WireGuard keys (if first time)
4. ✅ Adds peer to server via API
5. ✅ Server assigns IP address
6. ✅ Server updates wg0.conf
7. ✅ Stores peer record in database
8. ✅ Registers device in user's account
9. ✅ Generates complete WireGuard config
10. ✅ Returns ready-to-use config file

**Time:** 2-3 seconds

---

#### Step 5: Download Config File

**Success Screen:**
```
┌─────────────────────────────────────────┐
│  ✓ Device Connected Successfully!       │
├─────────────────────────────────────────┤
│                                         │
│  Device: My Laptop                      │
│  Server: 🗽 New York                    │
│  IP Assigned: 10.0.0.15                 │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  📥 Download TrueVaultNY.conf   │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │  📱 Show QR Code                │   │
│  └─────────────────────────────────┘   │
│                                         │
│  Next Steps:                            │
│  1. Download the config file            │
│  2. Install WireGuard app               │
│  3. Import the config file              │
│  4. Toggle connection ON                │
│                                         │
│          [View Instructions]            │
│                                         │
└─────────────────────────────────────────┘
```

**Download Button:**
```javascript
function downloadConfig(configContent, filename) {
    const blob = new Blob([configContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

document.getElementById('download-btn').addEventListener('click', () => {
    downloadConfig(configData.config, configData.config_filename);
    
    // Track download
    apiClient.post('/api/logs/track.php', {
        action: 'config_downloaded',
        device_id: configData.device_id
    });
});
```

**QR Code Generation:**

For mobile devices:
```javascript
function showQRCode(configContent) {
    // Generate QR code from config
    const qr = new QRCode(document.getElementById('qr-container'), {
        text: configContent,
        width: 300,
        height: 300
    });
    
    showModal('qr-modal');
}
```

---

#### Step 6: Installation Instructions

**Platform-Specific:**

**Windows:**
```
1. Download WireGuard: https://wireguard.com/install/
2. Install the application
3. Click "Import tunnel(s) from file"
4. Select TrueVaultNY.conf
5. Click "Activate"
6. Status will show "Active" with green indicator
```

**macOS:**
```
1. Download WireGuard from App Store
2. Open WireGuard app
3. Drag TrueVaultNY.conf into the app window
4. Click the toggle switch to connect
5. Status will show "Active"
```

**iOS:**
```
1. Install WireGuard from App Store
2. Open WireGuard app
3. Tap "+"
4. Choose "Create from file or archive"
5. Select TrueVaultNY.conf from Files app
   OR scan the QR code shown in dashboard
6. Tap the toggle to connect
```

**Android:**
```
1. Install WireGuard from Play Store
2. Open WireGuard app
3. Tap "+" (bottom right)
4. Choose "Import from file"
5. Select TrueVaultNY.conf
   OR tap "Scan from QR code"
6. Tap toggle to activate
```

**Linux:**
```bash
# Install WireGuard
sudo apt install wireguard  # Ubuntu/Debian
sudo dnf install wireguard-tools  # Fedora
sudo pacman -S wireguard-tools  # Arch

# Copy config
sudo cp TrueVaultNY.conf /etc/wireguard/

# Start VPN
sudo wg-quick up TrueVaultNY

# Enable on boot (optional)
sudo systemctl enable wg-quick@TrueVaultNY
```

---

### Server Switching (Post-Setup)

**Location:** Dashboard > Devices > [Device Card] > "Switch Server"

**Use Case:** User wants to switch from New York to Dallas for Netflix streaming

**UI Flow:**

1. User clicks "Switch Server" on device card
2. Modal shows available servers (excluding current)
3. User selects new server
4. System provisions access on new server
5. System removes access from old server
6. System generates new config file
7. User downloads updated config
8. User imports new config in WireGuard app

---

**Frontend:**

```javascript
async function switchServer(deviceId, currentServerId) {
    // Show server selection modal
    const servers = await apiClient.get('/api/vpn/servers.php');
    const availableServers = servers.filter(s => s.id !== currentServerId);
    
    showServerSwitchModal(availableServers, async (newServerId) => {
        try {
            const response = await apiClient.post('/api/vpn/switch-server.php', {
                device_id: deviceId,
                old_server_id: currentServerId,
                new_server_id: newServerId
            });
            
            if (response.success) {
                // Show new config download
                showDownloadScreen(response.data);
                
                // Update device card
                updateDeviceCard(deviceId, {
                    server_name: response.data.server_name,
                    server_id: newServerId
                });
            }
        } catch (error) {
            showError(error.message);
        }
    });
}
```

---

**Backend:**

**File:** `/api/vpn/switch-server.php`

```php
<?php
require_once '../config/database.php';
require_once '../helpers/auth.php';
require_once '../helpers/peer-manager.php';

Auth::require();
$user = Auth::getUser();

$data = json_decode(file_get_contents('php://input'), true);
$deviceId = $data['device_id'];
$oldServerId = $data['old_server_id'];
$newServerId = $data['new_server_id'];

// Verify device belongs to user
$device = Database::queryOne('devices',
    "SELECT * FROM user_devices WHERE device_id = ? AND user_id = ?",
    [$deviceId, $user['id']]
);

if (!$device) {
    Response::error('Device not found');
}

// Validate new server access
$serverAccess = PeerManager::checkServerAccess($user['id'], $newServerId);
if (!$serverAccess['allowed']) {
    Response::error($serverAccess['reason']);
}

// Get user's WireGuard key
$userKey = Database::queryOne('certificates',
    "SELECT * FROM user_certificates 
     WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
    [$user['id']]
);

// STEP 1: Remove peer from old server
PeerManager::removePeerFromServer($oldServerId, $userKey['public_key']);

// Update old peer record
Database::execute('vpn',
    "UPDATE user_peers 
     SET status = 'removed', removed_at = datetime('now')
     WHERE user_id = ? AND server_id = ?",
    [$user['id'], $oldServerId]
);

// STEP 2: Add peer to new server
$peerResult = PeerManager::addPeerToServer(
    $newServerId,
    $userKey['public_key'],
    $user['id']
);

if (!$peerResult['success']) {
    Response::error('Failed to switch server: ' . $peerResult['error']);
}

// STEP 3: Store new peer record
Database::execute('vpn',
    "INSERT INTO user_peers (user_id, server_id, public_key, assigned_ip, status)
     VALUES (?, ?, ?, ?, 'active')",
    [$user['id'], $newServerId, $userKey['public_key'], $peerResult['allowed_ip']]
);

// STEP 4: Update device record
Database::execute('devices',
    "UPDATE user_devices 
     SET server_id = ?, updated_at = datetime('now')
     WHERE device_id = ?",
    [$newServerId, $deviceId]
);

// STEP 5: Generate new config
$newServer = PeerManager::getServerDetails($newServerId);
$config = PeerManager::generateConfig(
    $userKey['private_key'],
    $peerResult['allowed_ip'],
    $newServer
);

// STEP 6: Log the switch
Database::execute('logs',
    "INSERT INTO activity_log (user_id, action, details)
     VALUES (?, 'server_switch', ?)",
    [$user['id'], json_encode([
        'device_id' => $deviceId,
        'from_server' => $oldServerId,
        'to_server' => $newServerId
    ])]
);

Response::success([
    'config' => $config,
    'config_filename' => $newServer['config_filename'],
    'server_name' => $newServer['name'],
    'assigned_ip' => $peerResult['allowed_ip'],
    'message' => 'Server switched successfully. Download and import the new config.'
]);
?>
```

---

### Server Switch UI (Device Card)

**Before Switch:**
```
┌────────────────────────────────────┐
│ 💻 My Laptop                       │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ Server: 🗽 New York                │
│ IP: 10.0.0.15                      │
│ Status: 🟢 Connected               │
│ Added: 2026-01-14                  │
│                                    │
│ [🔄 Switch Server] [❌ Remove]     │
└────────────────────────────────────┘
```

**After Clicking "Switch Server":**
```
┌────────────────────────────────────┐
│ Switch Server for "My Laptop"      │
├────────────────────────────────────┤
│                                    │
│ Current: 🗽 New York               │
│                                    │
│ Select New Server:                 │
│                                    │
│ ○ 🤠 Dallas - Streaming Only       │
│ ○ 🍁 Toronto - Canadian Content    │
│                                    │
│      [Cancel]  [Switch] →          │
│                                    │
└────────────────────────────────────┘
```

**After Switch Complete:**
```
┌────────────────────────────────────┐
│ ✓ Server Switched!                 │
├────────────────────────────────────┤
│                                    │
│ New Server: 🤠 Dallas              │
│ New IP: 10.10.1.15                 │
│                                    │
│ Download updated config:           │
│                                    │
│ ┌────────────────────────────────┐ │
│ │ 📥 Download TrueVaultTX.conf   │ │
│ └────────────────────────────────┘ │
│                                    │
│ Import this file in WireGuard app  │
│ to connect to the new server       │
│                                    │
└────────────────────────────────────┘
```

---

### Benefits of 2-Click Setup

**For Users:**
✅ No manual key generation required
✅ No copy/paste of keys or config
✅ No understanding of WireGuard internals needed
✅ Server switching without losing device
✅ QR code for mobile devices
✅ Platform-specific instructions
✅ Everything automated

**For System:**
✅ Centralized key management
✅ Automatic peer provisioning
✅ Immediate access revocation capability
✅ Device tracking and limits enforced
✅ Activity logging for support
✅ Server load balancing possible

---

### Error Handling

**Common Errors:**

1. **Device Limit Reached**
   ```
   ❌ Device limit reached (3/3 devices)
   
   Options:
   • Remove an existing device
   • Upgrade to Family plan (5 devices)
   ```

2. **Server Access Denied**
   ```
   ❌ Cannot access St. Louis server
   
   This is a VIP-only server.
   Contact support for VIP access.
   ```

3. **Server Offline**
   ```
   ❌ Server temporarily unavailable
   
   Please try:
   • Different server
   • Again in a few minutes
   ```

4. **Provisioning Failed**
   ```
   ❌ Failed to provision VPN access
   
   This is usually temporary. Please:
   • Try again
   • Contact support if persists
   ```

---

### Mobile QR Code Flow

**For iOS/Android Users:**

Instead of downloading file:

1. Click "Show QR Code" button
2. QR code appears on screen
3. Open WireGuard app on phone
4. Tap "Scan QR code"
5. Point camera at screen
6. App imports config automatically
7. Toggle connection on

**QR Code Generation:**
```javascript
async function generateQR(deviceId) {
    const response = await apiClient.get(`/api/vpn/config-qr.php?device_id=${deviceId}`);
    
    // response.data.qr_data contains the full WireGuard config
    const qr = new QRCode(document.getElementById('qr-container'), {
        text: response.data.qr_data,
        width: 400,
        height: 400,
        colorDark: '#000000',
        colorLight: '#ffffff'
    });
}
```

---

### Dashboard Device Management

**Devices Page Layout:**

```
┌─────────────────────────────────────────────────┐
│ My Devices                    [➕ Add Device]   │
├─────────────────────────────────────────────────┤
│                                                 │
│ ┌───────────────────────┐ ┌──────────────────┐ │
│ │ 💻 My Laptop          │ │ 📱 iPhone        │ │
│ │ ━━━━━━━━━━━━━━━━━━━━ │ │ ━━━━━━━━━━━━━━━ │ │
│ │ 🗽 New York           │ │ 🗽 New York      │ │
│ │ 10.0.0.15             │ │ 10.0.0.16        │ │
│ │ 🟢 Connected          │ │ 🔴 Disconnected  │ │
│ │                       │ │                  │ │
│ │ [🔄 Switch] [❌ Remove]│ │ [🔄 Switch] [❌]│ │
│ └───────────────────────┘ └──────────────────┘ │
│                                                 │
│ ┌───────────────────────┐                      │
│ │ 🎮 Xbox Series X      │                      │
│ │ ━━━━━━━━━━━━━━━━━━━━ │                      │
│ │ 🗽 New York           │                      │
│ │ 10.0.0.17             │                      │
│ │ 🟢 Connected          │                      │
│ │                       │                      │
│ │ [🔄 Switch] [❌ Remove]│                      │
│ └───────────────────────┘                      │
│                                                 │
│ Devices: 3/5 used                               │
│ Cameras: 0/2 used                               │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 9. CAMERA SYSTEM

### Supported Camera Types

```
- geeni (Tuya-based)
- wyze
- hikvision
- dahua
- amcrest
- reolink
- ring
- nest
- generic (RTSP)
```

### Camera Registration

**Endpoint:** `POST /api/devices/cameras.php` (action=register)

**Request:**
```json
{
  "name": "Front Door Camera",
  "type": "wyze",
  "ip_address": "192.168.1.100",
  "port": 554,
  "server_id": 1
}
```

**Validation:**
1. Check camera limits (Basic=1, Family=2, Dedicated=12)
2. Check server restrictions (Basic/Family = NY only)
3. Verify server exists and is online
4. Check if user can access this server

**Process:**
1. Generate camera ID: `cam_{16_hex_chars}`
2. Insert into `user_cameras` table
3. Create port forwarding rule (if needed)
4. Return camera ID + RTSP URL

**Response:**
```json
{
  "success": true,
  "data": {
    "camera_id": "cam_a1b2c3d4e5f67890",
    "rtsp_url": "rtsp://10.0.0.15:554/stream",
    "instructions": [
      "1. Connect to VPN (NY server)",
      "2. Open VLC or camera app",
      "3. Enter RTSP URL: rtsp://10.0.0.15:554/stream",
      "4. Stream will begin automatically"
    ],
    "remaining": 0
  }
}
```

### RTSP URL Patterns

**Geeni/Wyze:**
```
rtsp://{camera_ip}:554/live
```

**Hikvision:**
```
rtsp://{username}:{password}@{camera_ip}:554/Streaming/Channels/101
```

**Dahua:**
```
rtsp://{username}:{password}@{camera_ip}:554/cam/realmonitor?channel=1&subtype=0
```

**Generic:**
```
rtsp://{camera_ip}:554/stream
```

### Camera Access Flow

1. User connects to VPN (must use NY server for Basic/Family)
2. User's device gets VPN IP (e.g., 10.0.0.15)
3. Camera is on home network (e.g., 192.168.1.100)
4. VPN tunnel allows direct access to home network
5. User opens RTSP stream: `rtsp://192.168.1.100:554/live`
6. Stream flows through VPN tunnel

### Port Forwarding (Advanced)

For users who want external access without VPN:

**Endpoint:** `POST /api/port-forwarding/create.php`

**Request:**
```json
{
  "camera_id": "cam_abc123",
  "external_port": 8554
}
```

**Process:**
1. Allocate external port on VPN server
2. Forward: `{server_ip}:{external_port}` → `{camera_ip}:554`
3. Store mapping in `port_forwarding.db`

**Result:**
User can access camera at: `rtsp://66.94.103.91:8554/stream`

### Network Scanner Integration

The TruthVault Network Scanner (separate Python tool) can:
1. Scan user's local network
2. Identify cameras by MAC vendor
3. Auto-fill camera registration form
4. Suggest RTSP URLs based on detected brand

**Scanner Database:**
- MAC vendor to camera brand mapping
- Port detection (554, 8080, 8000, etc.)
- Automatic hostname resolution

---

## 10. CERTIFICATE SYSTEM

### WireGuard Key Generation

**File:** `/api/billing/billing-manager.php` - class `PeerManager`

### Private Key Generation

```php
private static function generatePrivateKey() {
    $bytes = random_bytes(32);
    $bytes[0] = chr(ord($bytes[0]) & 248);
    $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
    return base64_encode($bytes);
}
```

**Explanation:**
- Generate 32 random bytes
- Clamp first byte: `& 248` (sets lowest 3 bits to 0)
- Clamp last byte: `& 127` sets high bit to 0, `| 64` sets 2nd-highest bit to 1
- Base64 encode for text representation

### Public Key Generation

**Method 1: Using Sodium (Preferred)**
```php
private static function generatePublicKey($privateKey) {
    if (function_exists('sodium_crypto_scalarmult_base')) {
        $privateBytes = base64_decode($privateKey);
        $publicBytes = sodium_crypto_scalarmult_base($privateBytes);
        return base64_encode($publicBytes);
    }
    
    // Fallback method
    $hash = hash('sha256', base64_decode($privateKey), true);
    return base64_encode($hash);
}
```

**Note:** Production MUST use sodium. Fallback is insecure placeholder.

### Certificate Storage

**Table:** `user_certificates` in `certificates.db`

```sql
CREATE TABLE user_certificates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT,
    type TEXT DEFAULT 'wireguard',
    public_key TEXT UNIQUE NOT NULL,
    private_key TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME
);
```

**Security Note:** In production, `private_key` should be encrypted at rest.

### Key Lifecycle

**Creation:**
```php
public static function getOrCreateUserKey($userId) {
    // Check if key exists
    $existing = Database::queryOne('certificates',
        "SELECT * FROM user_certificates 
         WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
        [$userId]
    );
    
    if ($existing) {
        return $existing;
    }
    
    // Generate new keypair
    $privateKey = self::generatePrivateKey();
    $publicKey = self::generatePublicKey($privateKey);
    
    // Store
    Database::execute('certificates',
        "INSERT INTO user_certificates 
         (user_id, name, type, public_key, private_key, status, created_at)
         VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
        [$userId, $publicKey, $privateKey]
    );
    
    return [
        'private_key' => $privateKey,
        'public_key' => $publicKey
    ];
}
```

**Revocation:**
```php
Database::execute('certificates',
    "UPDATE user_certificates SET status = 'revoked', revoked_at = datetime('now')
     WHERE user_id = ? AND type = 'wireguard'",
    [$userId]
);
```

---

## 11. IDENTITY SYSTEM

**Status:** Planned but not yet implemented

### Concept

Each user can have multiple "digital identities" for different regions:

```
User: john@example.com
Identities:
  - USA (IP: 66.94.103.91, Browser: Chrome/Win10, Timezone: EST)
  - Canada (IP: 66.241.125.247, Browser: Firefox/MacOS, Timezone: EST)
  - Europe (IP: TBD, Browser: Safari/iOS, Timezone: CET)
```

### Purpose

When connecting from same region repeatedly, user appears as the same persistent identity:
- Same IP address
- Same browser fingerprint
- Same timezone settings
- Consistent behavioral patterns

Banks and services won't flag as "suspicious login from new location".

### Database Schema (Planned)

```sql
CREATE TABLE user_identities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    region TEXT NOT NULL,  -- usa, canada, uk, etc.
    server_id INTEGER NOT NULL,
    fingerprint TEXT,  -- Stored browser fingerprint
    timezone TEXT,
    locale TEXT,
    persistent_ip TEXT,  -- Always use this IP
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id)
);
```

### Future Implementation

1. Browser extension captures fingerprint
2. Fingerprint stored per region
3. Extension automatically applies fingerprint when connecting
4. Server always assigns same IP for that region/user combo

---

## 12. MESH NETWORK

**Status:** Planned but not yet implemented

### Concept

Private overlay network connecting trusted devices as if on same LAN.

**Example:**
```
Family Network:
  - Dad's laptop (in California)
  - Mom's phone (in Texas)  
  - Kid's Xbox (at college in NY)
  - Grandma's tablet (in Florida)
```

All appear to be on same local network: `10.99.x.x`

### Use Cases

1. **Remote Support**
   - Access family member's computer directly
   - No port forwarding needed
   - Works through firewalls

2. **File Sharing**
   - Share printers across country
   - Access NAS from anywhere
   - LAN gaming online

3. **Home Automation**
   - Control smart home while traveling
   - Access security cameras
   - Manage IoT devices remotely

### Database Schema (Planned)

```sql
CREATE TABLE mesh_networks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    owner_user_id INTEGER NOT NULL,
    network_prefix TEXT,  -- e.g., 10.99.0
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id)
);

CREATE TABLE mesh_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mesh_id INTEGER NOT NULL,
    user_id INTEGER,
    device_id TEXT,
    assigned_ip TEXT,
    status TEXT DEFAULT 'pending',  -- pending, active, blocked
    added_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mesh_id) REFERENCES mesh_networks(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (added_by) REFERENCES users(id)
);

CREATE TABLE mesh_invites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mesh_id INTEGER NOT NULL,
    invite_code TEXT UNIQUE NOT NULL,
    email TEXT,
    status TEXT DEFAULT 'pending',  -- pending, accepted, expired
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mesh_id) REFERENCES mesh_networks(id)
);
```

### Implementation Plan

1. User creates mesh network: "Family Network"
2. System assigns network prefix: `10.99.0.x`
3. User invites members via email/code
4. Members accept invite
5. Each member's devices get IP in `10.99.0.x` range
6. WireGuard config includes routes to mesh network
7. Devices can now communicate directly

---

## 13. FRONTEND PAGES

### Public Pages (No Authentication)

**Location:** `/public/`

1. **index.html** - Landing page
   - Hero section with VPN features
   - Pricing table
   - "Start Free Trial" CTA
   
2. **pricing.html** - Pricing details
   - All 3 plans comparison
   - FAQ section
   - Payment options

3. **login.html** - User login
   - Email + password form
   - "Forgot password" link
   - "Create account" link
   
4. **register.html** - User registration
   - Email, password, name fields
   - Terms acceptance
   - Auto-redirect to dashboard after signup

5. **forgot-password.html** - Password reset request
   - Email input
   - Send reset link

6. **reset-password.html** - Password reset form
   - New password + confirmation
   - Token validation

7. **payment-success.html** - PayPal return page
   - "Processing payment..."
   - Calls `/api/billing/complete.php`
   - Redirects to dashboard

8. **payment-cancel.html** - PayPal cancel page
   - "Payment cancelled"
   - Return to pricing link

### User Dashboard Pages (Authenticated)

**Location:** `/public/dashboard/`

All require valid JWT token in localStorage.

1. **dashboard.html** - Main dashboard
   - Welcome message
   - Active subscription status
   - VPN connection status
   - Quick actions (Connect, Manage Devices, etc.)

2. **servers.html** - Server list
   - All available servers
   - Server cards with:
     * Location + flag
     * Type (Shared/Dedicated)
     * Bandwidth indicator
     * Rules (Allowed/Restricted)
     * "Connect" button
   - Recommendations panel

3. **connect.html** - VPN connection
   - Selected server details
   - WireGuard config display
   - Copy button
   - Download button
   - Step-by-step instructions

4. **devices.html** - Device management
   - List of registered devices
   - Device limits indicator
   - "Add Device" button
   - "Swap Device" button (if eligible)
   - Remove device option

5. **cameras.html** - Camera management
   - List of registered cameras
   - Camera limits indicator
   - "Add Camera" button
   - RTSP URLs
   - Server restrictions info

6. **network-scanner.html** - Network scanner
   - Upload scanner results
   - Auto-detect cameras
   - One-click camera registration
   - Device discovery

7. **account.html** - Account settings
   - Profile information
   - Email/password change
   - Subscription details
   - Billing history

8. **billing.html** - Billing & invoices
   - Current plan
   - Payment history
   - Invoices (download)
   - Upgrade/downgrade options

9. **support.html** - Support center
   - FAQ
   - Submit ticket
   - Live chat (future)
   - Documentation links

10. **certificates.html** - Certificates
    - WireGuard keys view
    - Download config files
    - Regenerate keys option

11. **settings.html** - User preferences
    - Notifications
    - Theme selection
    - Language
    - Privacy settings

### Admin Dashboard Pages (Admin Authentication)

**Location:** `/public/admin/`

Require admin JWT token (with `is_admin: true`).

1. **admin-dashboard.html** - Admin overview
   - Total users count
   - Active subscriptions
   - Revenue metrics
   - Server health status

2. **admin-users.html** - User management
   - User list (paginated)
   - Search users
   - View user details
   - Suspend/activate users
   - VIP management

3. **admin-vip.html** - VIP management
   - VIP user list
   - Add VIP user
   - Edit VIP details
   - Remove VIP (except owner)
   - Dedicated server assignment

4. **admin-servers.html** - Server management
   - Server list
   - Server health checks
   - Peer counts
   - Bandwidth usage
   - Add/remove servers

5. **admin-billing.html** - Billing overview
   - Revenue reports
   - Subscription analytics
   - Payment failures
   - Refund requests

6. **admin-devices.html** - Device analytics
   - Most popular devices
   - Device trends
   - Camera usage stats

7. **admin-payments.html** - Payment details
   - All payments
   - Pending orders
   - Failed payments
   - PayPal webhook log

8. **admin-support.html** - Support tickets
   - Open tickets
   - Ticket management
   - Respond to tickets

9. **admin-logs.html** - Activity logs
   - User activity
   - System logs
   - Error logs
   - Audit trail

10. **admin-analytics.html** - Analytics
    - User growth charts
    - Revenue trends
    - Server usage
    - Popular features

11. **admin-cms.html** - Content management
    - Edit pages
    - Manage themes
    - Email templates
    - Settings

12. **admin-settings.html** - System settings
    - PayPal configuration
    - Email settings
    - Feature toggles
    - Maintenance mode

13. **admin-database.html** - Database viewer
    - Browse tables
    - Run queries (read-only)
    - Export data
    - Backup/restore

### Frontend JavaScript

**File:** `/public/assets/js/app.js`

**Key Features:**

1. **Authentication Manager**
```javascript
const Auth = {
    token: localStorage.getItem('truevault_token'),
    
    isAuthenticated() {
        return this.token !== null;
    },
    
    setToken(token) {
        this.token = token;
        localStorage.setItem('truevault_token', token);
    },
    
    clearToken() {
        this.token = null;
        localStorage.removeItem('truevault_token');
    },
    
    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.token}`
        };
    }
};
```

2. **API Client**
```javascript
const API = {
    baseURL: 'https://vpn.the-truth-publishing.com/api',
    
    async request(method, endpoint, data = null) {
        const options = {
            method,
            headers: Auth.getHeaders()
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.baseURL + endpoint, options);
        return await response.json();
    },
    
    // Convenience methods
    get(endpoint) { return this.request('GET', endpoint); },
    post(endpoint, data) { return this.request('POST', endpoint, data); },
    put(endpoint, data) { return this.request('PUT', endpoint, data); },
    delete(endpoint) { return this.request('DELETE', endpoint); }
};
```

3. **User Authentication**
```javascript
async function login(email, password) {
    const result = await API.post('/auth/login.php', {email, password});
    
    if (result.success) {
        Auth.setToken(result.data.token);
        window.location.href = '/dashboard';
    } else {
        showError(result.error);
    }
}

async function logout() {
    await API.post('/auth/logout.php');
    Auth.clearToken();
    window.location.href = '/login.html';
}
```

4. **VPN Connection**
```javascript
async function connectToServer(serverId) {
    const result = await API.post('/vpn/connect.php', {server_id: serverId});
    
    if (result.success) {
        displayConfig(result.data.config, result.data.config_name);
    } else {
        showError(result.error);
    }
}
```

5. **Protected Route Guard**
```javascript
function requireAuth() {
    if (!Auth.isAuthenticated()) {
        window.location.href = '/login.html';
        return false;
    }
    return true;
}

// Call on every dashboard page load
if (!requireAuth()) {
    // Will redirect to login
}
```

---

## 14. API ENDPOINT REFERENCE

### Authentication Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register.php` | No | Register new user |
| POST | `/auth/login.php` | No | User login |
| POST | `/auth/logout.php` | Yes | User logout |
| POST | `/auth/refresh.php` | No | Refresh JWT token |
| POST | `/auth/forgot-password.php` | No | Request password reset |
| POST | `/auth/reset-password.php` | No | Reset password with token |
| GET | `/auth/verify-email.php` | No | Verify email address |
| POST | `/auth/admin-login.php` | No | Admin login |

### VPN Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/vpn/servers.php` | Yes | List available servers |
| POST | `/vpn/connect.php` | Yes | Generate config for server |
| POST | `/vpn/disconnect.php` | Yes | Disconnect from server |
| GET | `/vpn/status.php` | Yes | Get connection status |
| GET | `/vpn/config-generator.php?action=list` | Yes | List servers |
| POST | `/vpn/config-generator.php?action=generate` | Yes | Generate config |
| GET | `/vpn/config-generator.php?action=download` | Yes | Download config file |

### Billing Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/billing/checkout.php` | Yes | Create PayPal checkout |
| POST | `/billing/complete.php` | Yes | Complete payment after PayPal |
| POST | `/billing/webhook.php` | No* | PayPal webhook handler |
| POST | `/billing/cancel.php` | Yes | Cancel subscription |
| GET | `/billing/subscription.php` | Yes | Get current subscription |
| GET | `/billing/history.php` | Yes | Get payment history |

*Webhook uses PayPal signature validation, not JWT

### Device Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/devices/list.php` | Yes | List user's devices |
| POST | `/devices/register.php` | Yes | Register new device |
| POST | `/devices/remove.php` | Yes | Remove device |
| POST | `/devices/swap.php` | Yes | Swap device (Family+) |
| GET | `/devices/cameras.php?action=list` | Yes | List cameras |
| POST | `/devices/cameras.php?action=register` | Yes | Register camera |
| POST | `/devices/cameras.php?action=remove` | Yes | Remove camera |

### Admin Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/admin/users.php` | Admin | List all users |
| GET | `/admin/users.php?id={id}` | Admin | Get user details |
| POST | `/admin/users.php?action=suspend` | Admin | Suspend user |
| POST | `/admin/users.php?action=activate` | Admin | Activate user |
| GET | `/admin/vip.php?action=list` | Admin | List VIP users |
| POST | `/admin/vip.php?action=add` | Admin | Add VIP user |
| POST | `/admin/vip.php?action=remove` | Admin | Remove VIP user |
| POST | `/admin/vip.php?action=update` | Admin | Update VIP details |
| GET | `/admin/billing.php` | Admin | Billing analytics |
| GET | `/admin/servers.php` | Admin | Server status |
| GET | `/admin/logs.php` | Admin | Activity logs |

### Common Response Format

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "error": "Error message",
  "errors": { ... }  // Optional validation errors
}
```

**Paginated:**
```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 100,
    "total_pages": 4
  }
}
```

---

## 15. DEPLOYMENT STATUS

### Production Server

```
Host: GoDaddy Shared Hosting
Domain: vpn.the-truth-publishing.com
Path: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com
```

### Deployed Files

**✅ DEPLOYED:**

```
/public_html/vpn.the-truth-publishing.com/
├── index.html
├── login.html
├── register.html
├── pricing.html
├── /api/
│   ├── /auth/
│   │   ├── login.php ✓
│   │   ├── register.php ✓
│   │   ├── logout.php ✓
│   │   └── admin-login.php ✓
│   ├── /config/
│   │   └── database.php ✓
│   ├── /helpers/
│   │   ├── auth.php ✓
│   │   ├── vip.php ✓
│   │   └── response.php ✓
│   ├── /vpn/
│   │   ├── servers.php ✓
│   │   └── connect.php ✓
│   └── /devices/
│       └── list.php ✓
├── /data/
│   ├── users.db ✓
│   ├── vip.db ✓ (with seed data)
│   ├── subscriptions.db ✓
│   └── vpn.db ✓
└── /assets/
    ├── /css/
    │   └── styles.css ✓
    └── /js/
        └── app.js ✓
```

### ❌ NOT YET DEPLOYED:

**CRITICAL MISSING:**
```
/api/billing/  ← USERS CANNOT PAY!
├── checkout.php
├── complete.php
├── webhook.php
├── billing-manager.php
└── cancel.php
```

**Other Missing:**
```
/api/vpn/
├── config-generator.php
├── disconnect.php
└── status.php

/api/devices/
├── register.php
├── remove.php
├── swap.php
└── cameras.php

/api/admin/
├── users.php
├── vip.php
├── billing.php
└── servers.php

/public/dashboard/
├── dashboard.html
├── servers.html
├── devices.html
└── [8 more pages]

/public/admin/
├── admin-dashboard.html
└── [12 more pages]
```

### Deployment Checklist

**Priority 1 (URGENT):**
- [ ] Upload `/api/billing/` folder (5 files)
- [ ] Test PayPal checkout flow
- [ ] Verify webhook endpoint works
- [ ] Test payment completion

**Priority 2 (High):**
- [ ] Upload remaining `/api/vpn/` files
- [ ] Upload `/api/devices/` folder
- [ ] Upload `/public/dashboard/` pages
- [ ] Test user dashboard workflow

**Priority 3 (Medium):**
- [ ] Upload `/api/admin/` folder
- [ ] Upload `/public/admin/` pages
- [ ] Setup admin account
- [ ] Test admin functions

**Priority 4 (Low):**
- [ ] Upload network scanner tool
- [ ] Setup camera port forwarding
- [ ] Create email templates
- [ ] Setup CRON jobs

### Database Status

**✅ Exist on Server:**
- users.db
- vip.db (seeded with owner + seige235)
- subscriptions.db
- vpn.db

**❌ Need Creation:**
- sessions.db
- admin_users.db
- certificates.db
- devices.db
- cameras.db
- payments.db
- logs.db

**Setup Script Needed:**
```sql
-- Run this on server to create missing databases
-- See section 3 for complete schemas
```

---

## 16. PRICING PLANS

### Plan Comparison

| Feature | Basic | Family | Dedicated |
|---------|-------|--------|-----------|
| **Price** | $9.99/mo | $14.99/mo | $29.99/mo |
| **Devices** | 3 | 5 | Unlimited |
| **IP Cameras** | 1 (NY only) | 2 (NY only) | 12 (any server) |
| **Device Swapping** | ❌ | ✅ | ✅ |
| **Servers** | NY, TX, CAN | NY, TX, CAN | NY, TX, CAN + Own Dedicated |
| **Streaming** | ✅ | ✅ | ✅ |
| **Gaming** | ✅ (NY only) | ✅ (NY only) | ✅ (All servers) |
| **Torrents** | ✅ (NY only) | ✅ (NY only) | ✅ (All servers) |
| **Port Forwarding** | ❌ | ❌ | ✅ |
| **Static IP** | ❌ | ❌ | ✅ |
| **Priority Support** | ❌ | ✅ | ✅ |
| **Network Scanner** | ✅ | ✅ | ✅ |

### VIP Upgrade (SECRET)

**Price:** $9.97/mo (VIP exclusive rate)

VIP users see this plan if they want to upgrade from VIP Basic to VIP Dedicated.

**Features:**
- Same as Dedicated plan
- Special VIP pricing
- Never expires
- Can't be cancelled
- No PayPal required (auto-approved)

### Trial Period

Currently: No free trial

**Planned:**
- 3-day free trial
- No credit card required
- Full access to Basic plan features
- Auto-downgrade after 3 days if no payment

---

## 17. SERVER SCRIPTS

### Peer Management API (peer_api.py)

**File:** Located on each VPN server at `/root/peer_api.py`

**Installation:**
```bash
# On each server
pip3 install flask

# Create systemd service
sudo nano /etc/systemd/system/peer-api.service

# Service file content:
[Unit]
Description=TrueVault Peer Management API
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root
Environment="SERVER_NAME=TrueVaultNY"
Environment="SERVER_NETWORK=10.0.0"
Environment="TRUEVAULT_API_SECRET=TrueVault2026SecretKey"
Environment="PEER_API_PORT=8080"
ExecStart=/usr/bin/python3 /root/peer_api.py
Restart=always

[Install]
WantedBy=multi-user.target

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable peer-api
sudo systemctl start peer-api
```

**Environment Variables:**

Server 1 (NY):
```bash
SERVER_NAME=TrueVaultNY
SERVER_NETWORK=10.0.0
```

Server 2 (STL):
```bash
SERVER_NAME=TrueVaultSTL
SERVER_NETWORK=10.0.1
```

Server 3 (TX):
```bash
SERVER_NAME=TrueVaultTX
SERVER_NETWORK=10.10.1
```

Server 4 (CAN):
```bash
SERVER_NAME=TrueVaultCAN
SERVER_NETWORK=10.10.0
```

**Key Functions:**

1. **verify_auth()** - Validates Bearer token
2. **get_next_ip()** - Finds next available IP in range
3. **add_peer_to_config()** - Adds peer to wg0.conf
4. **remove_peer_from_config()** - Removes peer from wg0.conf
5. **get_peers()** - Lists all active peers

**WireGuard Commands Used:**
```bash
# Show interface
wg show wg0

# Show allowed IPs
wg show wg0 allowed-ips

# Add peer
wg set wg0 peer {PUBLIC_KEY} allowed-ips {IP}/32

# Remove peer
wg set wg0 peer {PUBLIC_KEY} remove

# Show dump (for peer list)
wg show wg0 dump
```

**Config File Location:**
```
/etc/wireguard/wg0.conf
```

**Logging:**
All peer additions/removals include:
- User ID (in comment)
- Timestamp
- Public key

**Error Handling:**
- Invalid token → 401 Unauthorized
- Missing public_key → 400 Bad Request
- No available IPs → 500 Internal Server Error
- Peer already exists → Return false, message
- Peer not found → Return false, message

**Health Check:**
```bash
curl http://66.94.103.91:8080/health \
  -H "Authorization: Bearer TrueVault2026SecretKey"
```

Expected:
```json
{
  "status": "healthy",
  "server": "TrueVaultNY",
  "interface": "wg0",
  "timestamp": "2026-01-14T12:00:00"
}
```

---

## 18. SECURITY CONSIDERATIONS

### Authentication Security

1. **Password Hashing**
   - Uses PHP `password_hash()` with `PASSWORD_DEFAULT`
   - Currently bcrypt (cost 10)
   - Never store plaintext passwords

2. **JWT Security**
   - 7-day expiration
   - HMAC-SHA256 signature
   - Secret key: 256-bit strength
   - Tokens can't be tampered with

3. **Session Management**
   - Optional session tracking
   - Can invalidate specific tokens
   - Logout invalidates all user sessions

### VIP System Security

1. **Database-Driven**
   - VIP list not in code
   - Admin panel to manage VIPs
   - Audit trail for VIP changes

2. **Never Advertised**
   - No public VIP mention
   - No VIP login page
   - VIPs log in like normal users

3. **VIP Detection**
   - Happens at registration
   - Happens at login
   - Happens at checkout

### PayPal Security

1. **Webhook Validation**
   - PayPal sends signature
   - Should verify signature (TODO)
   - Log all webhooks

2. **Payment Verification**
   - Always capture payment via API
   - Don't trust client-side success
   - Verify order status before activation

3. **Refund Handling**
   - Immediate revocation on refund
   - User status set to 'refunded'
   - Can't reactivate without new payment

### Server Communication Security

1. **Peer API Authentication**
   - Bearer token required
   - Token: `TrueVault2026SecretKey`
   - 401 if missing/invalid

2. **Server-to-Server**
   - Currently HTTP (port 8080)
   - Should upgrade to HTTPS (TODO)
   - Or use VPN tunnel between servers

3. **WireGuard Security**
   - End-to-end encryption
   - Perfect forward secrecy
   - No traffic logging

### Database Security

1. **SQLite Permissions**
   - 0644 for database files
   - Owned by web server user
   - Not world-writable

2. **SQL Injection Prevention**
   - Always use prepared statements
   - Never concatenate user input
   - PDO with parameter binding

3. **Private Key Storage**
   - Currently plaintext (TODO: Encrypt)
   - Should use encryption at rest
   - Consider HSM for production

### CORS & Headers

1. **CORS Headers**
   - Allow all origins (currently)
   - Should restrict to vpn.the-truth-publishing.com
   - Allow credentials

2. **Security Headers** (TODO)
   - Content-Security-Policy
   - X-Frame-Options
   - X-Content-Type-Options
   - Strict-Transport-Security

---

## 19. BACKUP & RECOVERY

### Database Backup

**Location:** All databases in `/data/`

**Backup Script:**
```bash
#!/bin/bash
BACKUP_DIR="/home/eybn38fwc55z/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_PATH="/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data"

mkdir -p $BACKUP_DIR

# Backup all databases
tar -czf $BACKUP_DIR/truevault_dbs_$DATE.tar.gz $DB_PATH/*.db

# Keep only last 30 days
find $BACKUP_DIR -name "truevault_dbs_*.tar.gz" -mtime +30 -delete
```

**CRON:** Run daily at 3 AM
```
0 3 * * * /home/eybn38fwc55z/backup_truevault.sh
```

### Recovery Process

1. Stop web server
2. Extract backup: `tar -xzf backup.tar.gz`
3. Copy databases to `/data/`
4. Set permissions: `chmod 0644 *.db`
5. Restart web server
6. Verify data integrity

### Disaster Recovery

**Critical Data:**
- users.db (user accounts)
- vip.db (VIP list)
- subscriptions.db (active subs)
- payments.db (billing history)
- certificates.db (WireGuard keys)

**Recovery Priority:**
1. Restore databases
2. Verify VIP list
3. Test authentication
4. Test VPN connections
5. Verify PayPal webhook

---

## 20. MONITORING & LOGS

### Activity Logging

**Table:** `activity_log` in `logs.db`

**Logged Events:**
- login, logout
- vpn_connect, vpn_disconnect
- config_generated
- device_register, device_remove
- camera_register
- payment_success, payment_failed
- subscription_cancelled

**Log Format:**
```sql
INSERT INTO activity_log (user_id, action, details, ip_address)
VALUES (?, 'vpn_connect', '{"server": "NY", "ip": "10.0.0.15"}', '1.2.3.4');
```

### Error Logging

**PHP Errors:**
```php
error_log("TrueVault Error: " . $e->getMessage());
```

**Log Location:**
- GoDaddy: `/home/eybn38fwc55z/logs/error_log`
- Local: `error_log.txt`

### PayPal Webhook Logging

**Table:** `webhook_log` in `payments.db`

Every webhook logged:
- webhook_id
- event_type
- payload (full JSON)
- processed (0/1)
- error (if any)

### Server Health Monitoring

**Metrics to Track:**
- Peer count per server
- Connection success rate
- Average connection time
- Bandwidth usage
- Server uptime
- API response time

**Monitoring Script (TODO):**
```bash
#!/bin/bash
# Check all servers
for SERVER in ny stl tx can; do
    curl -s http://${SERVER}.truevault.internal:8080/health
done
```

---

## APPENDIX A: DEPLOYMENT COMMANDS

### Initial Server Setup

```bash
# Install WireGuard
sudo apt update
sudo apt install wireguard python3 python3-pip

# Install Flask
pip3 install flask

# Generate server keys
wg genkey | tee privatekey | wg pubkey > publickey

# Create WireGuard config
sudo nano /etc/wireguard/wg0.conf

# Config content:
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = {SERVER_PRIVATE_KEY}
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Enable IP forwarding
echo "net.ipv4.ip_forward=1" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p

# Start WireGuard
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0

# Deploy peer API (see section 17)
```

### Web Server Deployment

```bash
# Connect via FTP
ftp the-truth-publishing.com
# User: kahlen@the-truth-publishing.com
# Pass: AndassiAthena8

# Navigate to VPN subdomain
cd public_html/vpn.the-truth-publishing.com

# Upload files (use FileZilla or similar)
# Structure:
# /api/ → PHP backend files
# /public/ → HTML/CSS/JS files
# /data/ → SQLite databases

# Set permissions
chmod 755 api/
chmod 644 api/**/*.php
chmod 755 data/
chmod 0644 data/*.db

# Test API
curl https://vpn.the-truth-publishing.com/api/auth/login.php

# Verify databases exist
ls -la data/
```

---

## APPENDIX B: TESTING CHECKLIST

### Authentication Testing

- [ ] Register new user
- [ ] Login with correct credentials
- [ ] Login with wrong password (should fail)
- [ ] Logout
- [ ] Token expires after 7 days
- [ ] Password reset flow
- [ ] Email verification

### VIP Testing

- [ ] Register as VIP (paulhalonen@gmail.com)
- [ ] Subscription auto-created
- [ ] Checkout bypasses PayPal
- [ ] VIP can access VIP server (STL)
- [ ] Non-VIP cannot see VIP server
- [ ] VIP never loses access on payment failure

### Billing Testing

- [ ] Checkout flow (non-VIP)
- [ ] PayPal redirect works
- [ ] Payment capture on approval
- [ ] Subscription created
- [ ] Invoice generated
- [ ] VPN access provisioned
- [ ] Webhook receives events
- [ ] Payment failure handling
- [ ] Grace period (7 days)
- [ ] Access revocation after grace

### VPN Testing

- [ ] List servers API
- [ ] Connect to NY server
- [ ] Config generation
- [ ] WireGuard keys created
- [ ] Peer added to server
- [ ] Can ping VPN IP
- [ ] Internet works through VPN
- [ ] Disconnect works

### Device Testing

- [ ] Register device
- [ ] Device limits enforced
- [ ] Remove device
- [ ] Swap device (Family+)
- [ ] Camera registration
- [ ] Camera server restrictions

### Admin Testing

- [ ] Admin login
- [ ] View users list
- [ ] Add VIP user
- [ ] Remove VIP user
- [ ] View billing data
- [ ] View server status
- [ ] View activity logs

---

## APPENDIX C: TROUBLESHOOTING

### "No database file" Error

**Cause:** Database file missing or wrong path

**Solution:**
```bash
# Check path
ls -la /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/data/

# Create missing databases
touch users.db vip.db subscriptions.db vpn.db

# Set permissions
chmod 0644 *.db
```

### "Payment not captured" Error

**Cause:** Billing folder not deployed

**Solution:**
```bash
# Upload /api/billing/ folder
# Verify files exist:
ls -la api/billing/
```

### "Server unreachable" Error

**Cause:** Peer API not running

**Solution:**
```bash
# On VPN server
sudo systemctl status peer-api
sudo systemctl restart peer-api

# Check logs
sudo journalctl -u peer-api -f
```

### "VIP not recognized" Error

**Cause:** VIP database not seeded

**Solution:**
```sql
-- Connect to vip.db
INSERT INTO vip_users (email, type, plan, description)
VALUES ('paulhalonen@gmail.com', 'owner', 'dedicated', 'System Owner');
```

### "Token expired" Error

**Cause:** JWT token older than 7 days

**Solution:**
- User needs to log in again
- Or implement refresh token flow

---

**END OF BLUEPRINT**

**Document Status:** Complete  
**Total Sections:** 20 + 3 Appendices  
**Lines:** ~2800  
**Created:** January 14, 2026 - 7:45 AM CST  
**Author:** System Analysis

This blueprint documents the complete TrueVault VPN system as it exists in the codebase. All credentials, server details, database schemas, API endpoints, and implementation details are included.

