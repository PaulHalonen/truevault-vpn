# TrueVault VPN - SIMPLIFIED DEVICE & SERVER WORKFLOW
## The 2-Click Setup Process
**Created:** January 14, 2026 - 12:15 AM CST
**Status:** CRITICAL - This is how the system MUST work

---

# ⚠️ IMPORTANT DESIGN PRINCIPLE

**Most customers don't know what a VPN is.**

Therefore:
- NO technical jargon
- NO long instructions
- NO emails with setup steps
- NO waiting for anything
- INSTANT results
- 2 CLICKS maximum

---

# WORKFLOW 1: 7-Day Free Trial Signup

## What Happens

```
┌─────────────────────────────────────────────────────────────────┐
│                    SIGNUP FLOW (30 seconds)                      │
└─────────────────────────────────────────────────────────────────┘

Step 1: User visits website
        └─> Clicks "Start Free Trial"
        
Step 2: Simple form appears:
        ┌─────────────────────────────────────┐
        │  Start Your 7-Day Free Trial        │
        │                                     │
        │  Email: [________________]          │
        │  Password: [________________]       │
        │  First Name: [________________]     │
        │                                     │
        │  [  Start Free Trial  ]             │
        │                                     │
        │  ✓ No credit card required          │
        │  ✓ Cancel anytime                   │
        └─────────────────────────────────────┘

Step 3: Click submit
        └─> Account created instantly
        └─> Redirected to dashboard
        └─> Ready to add first device

** NO EMAIL VERIFICATION REQUIRED TO START **
** NO CREDIT CARD UNTIL DAY 7 **
```

## Database: Subscription Status

```sql
-- Subscription statuses
'trial'     = Free trial (7 days, no payment)
'active'    = Paid and active
'expired'   = Trial ended, didn't pay
'cancelled' = User cancelled
```

---

# WORKFLOW 2: Add Device (2-Click Process)

## The User Experience

```
┌─────────────────────────────────────────────────────────────────┐
│                    ADD DEVICE - CLICK 1                          │
└─────────────────────────────────────────────────────────────────┘

User is on Dashboard. Sees big button:

    ┌─────────────────────────────────────────────┐
    │                                             │
    │     🖥️  Your Devices                        │
    │                                             │
    │     You have no devices connected yet.      │
    │                                             │
    │     [  + Add Your First Device  ]           │
    │                                             │
    └─────────────────────────────────────────────┘

User clicks button. Modal popup appears:

    ┌─────────────────────────────────────────────┐
    │  Add New Device                        [X]  │
    ├─────────────────────────────────────────────┤
    │                                             │
    │  What do you want to call this device?      │
    │  [  My iPhone_________________ ]            │
    │                                             │
    │  Which server do you want to connect to?    │
    │                                             │
    │  ○ 🇨🇦 Canada (Toronto)                      │
    │  ● 🇺🇸 Texas (Dallas)         ← selected    │
    │  ○ 🇺🇸 New York                              │
    │                                             │
    │  [     Add Device & Get Config     ]        │
    │                                             │
    └─────────────────────────────────────────────┘
```

## What Happens On Submit (Behind the Scenes)

```
┌─────────────────────────────────────────────────────────────────┐
│                 CLICK 1: User clicks "Add Device"                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FRONTEND (JavaScript in browser)                                │
│                                                                  │
│  1. Generate WireGuard keypair locally:                         │
│     - Private key (stays in browser, NEVER sent to server)      │
│     - Public key (sent to our server)                           │
│                                                                  │
│  2. Send to API:                                                 │
│     POST /api/devices/add.php                                    │
│     {                                                            │
│       "device_name": "My iPhone",                                │
│       "server_id": 3,  // Texas                                  │
│       "public_key": "abc123..."                                  │
│     }                                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  API SERVER (our web server)                                     │
│                                                                  │
│  1. Validate user is logged in                                   │
│  2. Check device limit (trial = 1, personal = 3, etc.)          │
│  3. Get selected VPN server details                              │
│  4. Call VPN server's peer API:                                  │
│                                                                  │
│     POST http://66.241.124.4:8080/add_peer                       │
│     {                                                            │
│       "public_key": "abc123...",                                 │
│       "user_id": 123                                             │
│     }                                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  VPN SERVER (Texas - 66.241.124.4)                               │
│                                                                  │
│  1. Receive public key                                           │
│  2. Assign IP address from pool (e.g., 10.0.0.15)               │
│  3. Add peer to WireGuard:                                       │
│     wg set wg0 peer abc123... allowed-ips 10.0.0.15/32          │
│  4. Return assigned IP and server's public key                   │
│                                                                  │
│  Response:                                                       │
│  {                                                               │
│    "success": true,                                              │
│    "assigned_ip": "10.0.0.15",                                   │
│    "server_public_key": "xyz789..."                              │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  API SERVER (continued)                                          │
│                                                                  │
│  1. Save device to database:                                     │
│     - device_name, user_id, server_id, public_key, assigned_ip  │
│                                                                  │
│  2. Return to frontend:                                          │
│     {                                                            │
│       "success": true,                                           │
│       "device_id": 456,                                          │
│       "assigned_ip": "10.0.0.15",                                │
│       "server_public_key": "xyz789...",                          │
│       "server_endpoint": "66.241.124.4:51820"                    │
│     }                                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FRONTEND (continued)                                            │
│                                                                  │
│  1. Build complete WireGuard config using:                       │
│     - Private key (from step 1, still in browser memory)        │
│     - Assigned IP (from server response)                         │
│     - Server public key (from server response)                   │
│     - Server endpoint (from server response)                     │
│                                                                  │
│  2. Generate downloadable .conf file:                            │
│     [Interface]                                                  │
│     PrivateKey = <user's private key>                            │
│     Address = 10.0.0.15/32                                       │
│     DNS = 1.1.1.1                                                │
│                                                                  │
│     [Peer]                                                       │
│     PublicKey = xyz789...                                        │
│     Endpoint = 66.241.124.4:51820                                │
│     AllowedIPs = 0.0.0.0/0                                       │
│     PersistentKeepalive = 25                                     │
│                                                                  │
│  3. Show download ready screen                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Click 2: Download Config

```
┌─────────────────────────────────────────────────────────────────┐
│                    ADD DEVICE - CLICK 2                          │
└─────────────────────────────────────────────────────────────────┘

Modal changes to show success:

    ┌─────────────────────────────────────────────┐
    │  ✅ Device Added Successfully!         [X]  │
    ├─────────────────────────────────────────────┤
    │                                             │
    │  Your config file is ready!                 │
    │                                             │
    │  ┌─────────────────────────────────────┐   │
    │  │  📄 MyiPhone-Texas.conf              │   │
    │  │                                      │   │
    │  │  [  ⬇️ Download Config File  ]       │   │
    │  └─────────────────────────────────────┘   │
    │                                             │
    │  Quick Setup:                               │
    │  1. Download the file above                 │
    │  2. Open WireGuard app on your device       │
    │  3. Tap "+" then "Import from file"         │
    │  4. Select this file                        │
    │  5. Turn it ON - You're protected! 🎉       │
    │                                             │
    │  Don't have WireGuard?                      │
    │  [App Store] [Google Play] [Windows]        │
    │                                             │
    └─────────────────────────────────────────────┘

User clicks download → .conf file saves to their device
User imports into WireGuard → Connected!

TOTAL TIME: ~30 seconds
TOTAL CLICKS: 2
```

---

# WORKFLOW 3: Switch Server (Same Device)

## The Scenario

User has "My iPhone" connected to Canada server.
User needs to access US banking, wants Texas server.

## The User Experience

```
┌─────────────────────────────────────────────────────────────────┐
│                    DEVICE LIST VIEW                              │
└─────────────────────────────────────────────────────────────────┘

Dashboard shows their devices:

    ┌─────────────────────────────────────────────────────────────┐
    │  🖥️ Your Devices                                            │
    ├─────────────────────────────────────────────────────────────┤
    │                                                             │
    │  ┌─────────────────────────────────────────────────────┐   │
    │  │  📱 My iPhone                                        │   │
    │  │  Server: 🇨🇦 Canada (Toronto)                         │   │
    │  │  Status: ● Connected                                 │   │
    │  │                                                      │   │
    │  │  [Switch Server] [Download Config] [Remove]          │   │
    │  └─────────────────────────────────────────────────────┘   │
    │                                                             │
    │  [ + Add Another Device ]                                   │
    │                                                             │
    └─────────────────────────────────────────────────────────────┘

User clicks [Switch Server]
```

## Switch Server Modal

```
┌─────────────────────────────────────────────────────────────────┐
│                    SWITCH SERVER POPUP                           │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────┐
    │  Switch Server                         [X]  │
    ├─────────────────────────────────────────────┤
    │                                             │
    │  Device: 📱 My iPhone                       │
    │  Currently: 🇨🇦 Canada (Toronto)             │
    │                                             │
    │  Select new server:                         │
    │                                             │
    │  ○ 🇨🇦 Canada (Toronto)     ← current       │
    │  ● 🇺🇸 Texas (Dallas)       ← selecting     │
    │  ○ 🇺🇸 New York                              │
    │                                             │
    │  ⚠️ You'll need to download a new config   │
    │     file and import it into WireGuard.      │
    │                                             │
    │  [     Switch to Texas     ]                │
    │                                             │
    └─────────────────────────────────────────────┘
```

## What Happens On Switch

```
┌─────────────────────────────────────────────────────────────────┐
│                 USER CLICKS "Switch to Texas"                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FRONTEND                                                        │
│                                                                  │
│  1. Generate NEW WireGuard keypair                               │
│     (Different key for different server = more secure)           │
│                                                                  │
│  2. Send to API:                                                 │
│     POST /api/devices/switch-server.php                          │
│     {                                                            │
│       "device_id": 456,                                          │
│       "new_server_id": 3,  // Texas                              │
│       "new_public_key": "newkey123..."                           │
│     }                                                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  API SERVER                                                      │
│                                                                  │
│  1. Get device's current server info                             │
│  2. Remove peer from OLD server (Canada):                        │
│     POST http://66.241.125.247:8080/remove_peer                  │
│     { "public_key": "oldkey..." }                                │
│                                                                  │
│  3. Add peer to NEW server (Texas):                              │
│     POST http://66.241.124.4:8080/add_peer                       │
│     { "public_key": "newkey123...", "user_id": 123 }             │
│                                                                  │
│  4. Update database:                                             │
│     UPDATE user_devices SET                                      │
│       server_id = 3,                                             │
│       public_key = 'newkey123...',                               │
│       assigned_ip = '10.0.0.22',                                 │
│       updated_at = NOW                                           │
│     WHERE id = 456                                               │
│                                                                  │
│  5. Return new config info                                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  FRONTEND                                                        │
│                                                                  │
│  1. Build new .conf file with Texas server details               │
│  2. Show download screen:                                        │
│                                                                  │
│     ┌─────────────────────────────────────────────┐             │
│     │  ✅ Server Switched!                   [X]  │             │
│     ├─────────────────────────────────────────────┤             │
│     │                                             │             │
│     │  📱 My iPhone is now on 🇺🇸 Texas           │             │
│     │                                             │             │
│     │  [  ⬇️ Download New Config  ]               │             │
│     │                                             │             │
│     │  Important: Delete your old Canada          │             │
│     │  config from WireGuard and import           │             │
│     │  this new one.                              │             │
│     │                                             │             │
│     └─────────────────────────────────────────────┘             │
└─────────────────────────────────────────────────────────────────┘

TOTAL TIME: ~15 seconds
TOTAL CLICKS: 2 (Switch Server button + Download)
```

---

# DATABASE SCHEMA FOR THIS WORKFLOW

```sql
-- User devices table (updated design)
CREATE TABLE IF NOT EXISTS user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_uuid TEXT UNIQUE NOT NULL,
    device_name TEXT NOT NULL,
    
    -- Current server connection
    current_server_id INTEGER NOT NULL,
    public_key TEXT NOT NULL,
    assigned_ip TEXT NOT NULL,
    
    -- Metadata
    device_type TEXT DEFAULT 'unknown', -- 'phone', 'computer', 'tablet', etc.
    is_active INTEGER DEFAULT 1,
    last_connected DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_server_id) REFERENCES vpn_servers(id)
);

-- Server switch history (for analytics)
CREATE TABLE IF NOT EXISTS device_server_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id INTEGER NOT NULL,
    from_server_id INTEGER,
    to_server_id INTEGER NOT NULL,
    switched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES user_devices(id) ON DELETE CASCADE
);

-- VPN Servers (available to users)
CREATE TABLE IF NOT EXISTS vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,              -- "Canada (Toronto)"
    display_name TEXT NOT NULL,      -- "🇨🇦 Canada"
    country TEXT NOT NULL,           -- "Canada"
    city TEXT NOT NULL,              -- "Toronto"
    country_flag TEXT NOT NULL,      -- "🇨🇦"
    ip_address TEXT NOT NULL,        -- "66.241.125.247"
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT NOT NULL,        -- Server's WireGuard public key
    status TEXT DEFAULT 'active',
    server_type TEXT DEFAULT 'shared', -- 'shared' or 'vip_dedicated'
    max_connections INTEGER DEFAULT 100,
    current_load INTEGER DEFAULT 0,  -- Percentage
    is_available INTEGER DEFAULT 1,  -- Show in dropdown
    sort_order INTEGER DEFAULT 0,    -- Display order
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert the 4 servers
INSERT INTO vpn_servers (name, display_name, country, city, country_flag, ip_address, api_port, server_type, sort_order) VALUES
('Canada (Toronto)', '🇨🇦 Canada', 'Canada', 'Toronto', '🇨🇦', '66.241.125.247', 8080, 'shared', 1),
('Texas (Dallas)', '🇺🇸 Texas', 'USA', 'Dallas', '🇺🇸', '66.241.124.4', 8443, 'shared', 2),
('New York', '🇺🇸 New York', 'USA', 'New York', '🇺🇸', '66.94.103.91', 8080, 'shared', 3),
('VIP Dedicated', '👑 VIP Server', 'USA', 'St. Louis', '👑', '144.126.133.253', 8080, 'vip_dedicated', 99);
```

---

# API ENDPOINTS

## 1. Get Available Servers (for dropdown)

**GET /api/servers/available.php**

```php
<?php
/**
 * Get list of servers available for user to choose
 * Used in "Add Device" and "Switch Server" dropdowns
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();
if (!$user) exit;

$isVIP = VIPManager::isVIP($user['email']);

// Get servers
if ($isVIP) {
    // VIP users see all servers including their dedicated one
    $servers = Database::query('servers',
        "SELECT id, name, display_name, country, city, country_flag, ip_address, 
                wireguard_port, current_load, server_type
         FROM vpn_servers 
         WHERE status = 'active' AND is_available = 1
         ORDER BY sort_order ASC"
    );
} else {
    // Regular users only see shared servers
    $servers = Database::query('servers',
        "SELECT id, name, display_name, country, city, country_flag, ip_address, 
                wireguard_port, current_load, server_type
         FROM vpn_servers 
         WHERE status = 'active' AND is_available = 1 AND server_type = 'shared'
         ORDER BY sort_order ASC"
    );
}

// Format for frontend dropdown
$formatted = array_map(function($server) {
    return [
        'id' => $server['id'],
        'name' => $server['display_name'],
        'full_name' => $server['name'],
        'country' => $server['country'],
        'city' => $server['city'],
        'flag' => $server['country_flag'],
        'load' => $server['current_load'],
        'is_vip' => $server['server_type'] === 'vip_dedicated'
    ];
}, $servers);

Response::success(['servers' => $formatted]);
```

---

## 2. Add Device

**POST /api/devices/add.php**

```php
<?php
/**
 * Add a new device - THE 2-CLICK PROCESS
 * 
 * Input:
 * - device_name: "My iPhone"
 * - server_id: 3
 * - public_key: "abc123..." (generated by frontend)
 * 
 * Output:
 * - assigned_ip: "10.0.0.15"
 * - server_public_key: "xyz789..."
 * - server_endpoint: "66.241.124.4:51820"
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();
if (!$user) exit;

Response::requireMethod('POST');
$input = Response::getJsonInput();

// Validate input
if (empty($input['device_name'])) {
    Response::error('Device name is required', 400);
}
if (empty($input['server_id'])) {
    Response::error('Please select a server', 400);
}
if (empty($input['public_key'])) {
    Response::error('Public key is required', 400);
}

$deviceName = trim($input['device_name']);
$serverId = (int)$input['server_id'];
$publicKey = trim($input['public_key']);

// Check device limit
$isVIP = VIPManager::isVIP($user['email']);
$limits = [
    'trial' => 1,
    'personal' => 3,
    'family' => 10,
    'business' => -1 // unlimited
];

if (!$isVIP) {
    $maxDevices = $limits[$user['plan_type']] ?? 1;
    
    if ($maxDevices !== -1) {
        $currentCount = Database::queryOne('users',
            "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1",
            [$user['id']]
        );
        
        if (($currentCount['count'] ?? 0) >= $maxDevices) {
            Response::error("You've reached your device limit ($maxDevices). Upgrade to add more devices.", 403);
        }
    }
}

// Get selected server
$server = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ? AND status = 'active'",
    [$serverId]
);

if (!$server) {
    Response::error('Selected server is not available', 400);
}

// Check if VIP-only server
if ($server['server_type'] === 'vip_dedicated' && !$isVIP) {
    Response::error('This server is only available for VIP users', 403);
}

// Call VPN server's peer API to add the peer
$apiUrl = "http://{$server['ip_address']}:{$server['api_port']}/add_peer";

$peerData = [
    'public_key' => $publicKey,
    'user_id' => $user['id'],
    'device_name' => $deviceName
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($peerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . getenv('VPN_API_KEY')
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    Response::serverError("Could not connect to VPN server: $error");
}

$result = json_decode($response, true);

if (!$result || !$result['success']) {
    Response::serverError($result['error'] ?? 'Failed to add device to VPN server');
}

$assignedIp = $result['assigned_ip'];

// Generate device UUID
$deviceUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// Save device to database
Database::execute('users',
    "INSERT INTO user_devices (user_id, device_uuid, device_name, current_server_id, public_key, assigned_ip, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
    [$user['id'], $deviceUuid, $deviceName, $serverId, $publicKey, $assignedIp]
);

$deviceId = Database::lastInsertId('users');

// Log the addition
Database::execute('users',
    "INSERT INTO device_server_history (device_id, from_server_id, to_server_id, switched_at)
     VALUES (?, NULL, ?, datetime('now'))",
    [$deviceId, $serverId]
);

// Return everything frontend needs to build the .conf file
Response::success([
    'device_id' => $deviceId,
    'device_uuid' => $deviceUuid,
    'device_name' => $deviceName,
    'assigned_ip' => $assignedIp,
    'server' => [
        'id' => $server['id'],
        'name' => $server['name'],
        'display_name' => $server['display_name'],
        'flag' => $server['country_flag'],
        'public_key' => $server['public_key'],
        'endpoint' => $server['ip_address'] . ':' . $server['wireguard_port']
    ]
], 'Device added successfully! Download your config file.');
```

---

## 3. Switch Server

**POST /api/devices/switch-server.php**

```php
<?php
/**
 * Switch a device to a different server
 * 
 * Input:
 * - device_id: 456
 * - new_server_id: 3
 * - new_public_key: "newkey123..." (frontend generates new keypair)
 * 
 * Output:
 * - assigned_ip: "10.0.0.22"
 * - server_public_key: "xyz789..."
 * - server_endpoint: "66.241.124.4:51820"
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();
if (!$user) exit;

Response::requireMethod('POST');
$input = Response::getJsonInput();

// Validate input
if (empty($input['device_id'])) {
    Response::error('Device ID is required', 400);
}
if (empty($input['new_server_id'])) {
    Response::error('Please select a new server', 400);
}
if (empty($input['new_public_key'])) {
    Response::error('New public key is required', 400);
}

$deviceId = (int)$input['device_id'];
$newServerId = (int)$input['new_server_id'];
$newPublicKey = trim($input['new_public_key']);

// Get device (verify ownership)
$device = Database::queryOne('users',
    "SELECT * FROM user_devices WHERE id = ? AND user_id = ?",
    [$deviceId, $user['id']]
);

if (!$device) {
    Response::error('Device not found', 404);
}

// Get old server
$oldServer = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ?",
    [$device['current_server_id']]
);

// Get new server
$newServer = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ? AND status = 'active'",
    [$newServerId]
);

if (!$newServer) {
    Response::error('Selected server is not available', 400);
}

// Check VIP access
$isVIP = VIPManager::isVIP($user['email']);
if ($newServer['server_type'] === 'vip_dedicated' && !$isVIP) {
    Response::error('This server is only available for VIP users', 403);
}

// Step 1: Remove peer from OLD server
if ($oldServer) {
    $removeUrl = "http://{$oldServer['ip_address']}:{$oldServer['api_port']}/remove_peer";
    
    $ch = curl_init($removeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['public_key' => $device['public_key']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . getenv('VPN_API_KEY')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
    // Don't fail if removal fails - server might be down
}

// Step 2: Add peer to NEW server
$addUrl = "http://{$newServer['ip_address']}:{$newServer['api_port']}/add_peer";

$peerData = [
    'public_key' => $newPublicKey,
    'user_id' => $user['id'],
    'device_name' => $device['device_name']
];

$ch = curl_init($addUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($peerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . getenv('VPN_API_KEY')
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    Response::serverError("Could not connect to new VPN server: $error");
}

$result = json_decode($response, true);

if (!$result || !$result['success']) {
    Response::serverError($result['error'] ?? 'Failed to add device to new server');
}

$assignedIp = $result['assigned_ip'];

// Step 3: Update database
Database::execute('users',
    "UPDATE user_devices SET 
        current_server_id = ?,
        public_key = ?,
        assigned_ip = ?,
        updated_at = datetime('now')
     WHERE id = ?",
    [$newServerId, $newPublicKey, $assignedIp, $deviceId]
);

// Log the switch
Database::execute('users',
    "INSERT INTO device_server_history (device_id, from_server_id, to_server_id, switched_at)
     VALUES (?, ?, ?, datetime('now'))",
    [$deviceId, $device['current_server_id'], $newServerId]
);

// Return new config info
Response::success([
    'device_id' => $deviceId,
    'device_name' => $device['device_name'],
    'assigned_ip' => $assignedIp,
    'old_server' => $oldServer ? $oldServer['display_name'] : null,
    'new_server' => [
        'id' => $newServer['id'],
        'name' => $newServer['name'],
        'display_name' => $newServer['display_name'],
        'flag' => $newServer['country_flag'],
        'public_key' => $newServer['public_key'],
        'endpoint' => $newServer['ip_address'] . ':' . $newServer['wireguard_port']
    ]
], 'Server switched! Download your new config file.');
```

---

## 4. Get User's Devices

**GET /api/devices/list.php**

```php
<?php
/**
 * Get user's devices with current server info
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

$user = Auth::requireAuth();
if (!$user) exit;

$devices = Database::query('users',
    "SELECT d.*, s.name as server_name, s.display_name as server_display_name, 
            s.country_flag as server_flag, s.ip_address as server_ip
     FROM user_devices d
     LEFT JOIN vpn_servers s ON d.current_server_id = s.id
     WHERE d.user_id = ? AND d.is_active = 1
     ORDER BY d.created_at DESC",
    [$user['id']]
);

$formatted = array_map(function($device) {
    return [
        'id' => $device['id'],
        'uuid' => $device['device_uuid'],
        'name' => $device['device_name'],
        'server' => [
            'id' => $device['current_server_id'],
            'name' => $device['server_display_name'],
            'flag' => $device['server_flag']
        ],
        'assigned_ip' => $device['assigned_ip'],
        'created_at' => $device['created_at'],
        'last_connected' => $device['last_connected']
    ];
}, $devices);

Response::success(['devices' => $formatted]);
```

---

# FRONTEND JAVASCRIPT

## WireGuard Key Generation (in browser)

```javascript
/**
 * TrueVault VPN - WireGuard Key Generation
 * Generates keypairs entirely in the browser
 * Private key NEVER leaves the user's device
 */

class WireGuardKeys {
    
    /**
     * Generate a new WireGuard keypair
     * Uses Web Crypto API + curve25519
     */
    static async generateKeypair() {
        // Generate 32 random bytes for private key
        const privateKeyBytes = new Uint8Array(32);
        crypto.getRandomValues(privateKeyBytes);
        
        // Clamp private key per WireGuard spec
        privateKeyBytes[0] &= 248;
        privateKeyBytes[31] &= 127;
        privateKeyBytes[31] |= 64;
        
        // Generate public key from private key using curve25519
        const publicKeyBytes = await this.curve25519ScalarMultBase(privateKeyBytes);
        
        // Convert to base64
        const privateKey = this.bytesToBase64(privateKeyBytes);
        const publicKey = this.bytesToBase64(publicKeyBytes);
        
        return { privateKey, publicKey };
    }
    
    /**
     * Curve25519 scalar multiplication with base point
     * This derives the public key from private key
     */
    static async curve25519ScalarMultBase(privateKey) {
        // Using tweetnacl library for curve25519
        // Include via: <script src="https://cdnjs.cloudflare.com/ajax/libs/tweetnacl/1.0.3/nacl-fast.min.js"></script>
        
        if (typeof nacl !== 'undefined') {
            return nacl.scalarMult.base(privateKey);
        }
        
        // Fallback: Use SubtleCrypto (less compatible)
        throw new Error('nacl library required for key generation');
    }
    
    /**
     * Convert bytes to base64
     */
    static bytesToBase64(bytes) {
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }
    
    /**
     * Generate WireGuard config file content
     */
    static generateConfig(privateKey, assignedIp, serverPublicKey, serverEndpoint) {
        return `[Interface]
PrivateKey = ${privateKey}
Address = ${assignedIp}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = ${serverPublicKey}
Endpoint = ${serverEndpoint}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
`;
    }
    
    /**
     * Trigger download of config file
     */
    static downloadConfig(configContent, deviceName, serverName) {
        // Create filename: DeviceName-ServerName.conf
        const filename = `${deviceName.replace(/[^a-z0-9]/gi, '')}-${serverName.replace(/[^a-z0-9]/gi, '')}.conf`;
        
        const blob = new Blob([configContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}
```

## Add Device Flow

```javascript
/**
 * Add Device - The 2-Click Process
 */

class DeviceManager {
    
    /**
     * Show "Add Device" modal
     */
    static async showAddDeviceModal() {
        // Load available servers
        const servers = await this.loadServers();
        
        // Build modal HTML
        const modalHtml = `
            <div class="modal-overlay" id="add-device-modal">
                <div class="modal">
                    <div class="modal-header">
                        <h2>Add New Device</h2>
                        <button class="modal-close" onclick="DeviceManager.closeModal()">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>What do you want to call this device?</label>
                            <input type="text" id="device-name" placeholder="My iPhone" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label>Which server do you want to connect to?</label>
                            <div class="server-list">
                                ${servers.map((s, i) => `
                                    <label class="server-option">
                                        <input type="radio" name="server" value="${s.id}" ${i === 0 ? 'checked' : ''}>
                                        <span class="server-flag">${s.flag}</span>
                                        <span class="server-name">${s.full_name}</span>
                                        ${s.is_vip ? '<span class="vip-badge">VIP</span>' : ''}
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="DeviceManager.addDevice()">
                            Add Device & Get Config
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * Add device - called when user clicks submit
     */
    static async addDevice() {
        const deviceName = document.getElementById('device-name').value.trim();
        const serverId = document.querySelector('input[name="server"]:checked')?.value;
        
        if (!deviceName) {
            App.showToast('Please enter a device name', 'error');
            return;
        }
        
        if (!serverId) {
            App.showToast('Please select a server', 'error');
            return;
        }
        
        // Show loading
        const btn = document.querySelector('.modal-footer .btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner"></span> Adding...';
        btn.disabled = true;
        
        try {
            // Step 1: Generate keypair in browser
            const keys = await WireGuardKeys.generateKeypair();
            
            // Step 2: Send public key to server
            const response = await fetch('/api/devices/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + Auth.getToken()
                },
                body: JSON.stringify({
                    device_name: deviceName,
                    server_id: parseInt(serverId),
                    public_key: keys.publicKey
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to add device');
            }
            
            // Step 3: Generate config file using private key (still in memory)
            const configContent = WireGuardKeys.generateConfig(
                keys.privateKey,
                data.data.assigned_ip,
                data.data.server.public_key,
                data.data.server.endpoint
            );
            
            // Step 4: Show success screen with download button
            this.showDownloadScreen(deviceName, data.data.server, configContent);
            
        } catch (error) {
            App.showToast(error.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    /**
     * Show download screen after device is added
     */
    static showDownloadScreen(deviceName, server, configContent) {
        const modal = document.getElementById('add-device-modal');
        
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header success">
                    <h2>✅ Device Added Successfully!</h2>
                    <button class="modal-close" onclick="DeviceManager.closeModal()">×</button>
                </div>
                <div class="modal-body">
                    <div class="download-card">
                        <div class="file-icon">📄</div>
                        <div class="file-name">${deviceName}-${server.display_name.replace(/[^a-z0-9]/gi, '')}.conf</div>
                        <button class="btn btn-primary btn-large" onclick="DeviceManager.downloadConfig()">
                            ⬇️ Download Config File
                        </button>
                    </div>
                    
                    <div class="setup-steps">
                        <h3>Quick Setup:</h3>
                        <ol>
                            <li>Download the file above</li>
                            <li>Open WireGuard app on your device</li>
                            <li>Tap <strong>+</strong> then <strong>Import from file</strong></li>
                            <li>Select this file</li>
                            <li>Turn it <strong>ON</strong> - You're protected! 🎉</li>
                        </ol>
                    </div>
                    
                    <div class="wireguard-links">
                        <p>Don't have WireGuard?</p>
                        <a href="https://apps.apple.com/app/wireguard/id1441195209" target="_blank">App Store</a>
                        <a href="https://play.google.com/store/apps/details?id=com.wireguard.android" target="_blank">Google Play</a>
                        <a href="https://www.wireguard.com/install/" target="_blank">Windows/Mac/Linux</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="DeviceManager.closeModal()">Done</button>
                </div>
            </div>
        `;
        
        // Store config for download
        modal.dataset.config = configContent;
        modal.dataset.deviceName = deviceName;
        modal.dataset.serverName = server.display_name;
    }
    
    /**
     * Trigger config download
     */
    static downloadConfig() {
        const modal = document.getElementById('add-device-modal');
        WireGuardKeys.downloadConfig(
            modal.dataset.config,
            modal.dataset.deviceName,
            modal.dataset.serverName
        );
    }
    
    /**
     * Close modal and refresh device list
     */
    static closeModal() {
        const modal = document.getElementById('add-device-modal');
        if (modal) {
            modal.remove();
        }
        // Refresh device list
        this.loadDevices();
    }
    
    /**
     * Load available servers from API
     */
    static async loadServers() {
        const response = await fetch('/api/servers/available.php', {
            headers: { 'Authorization': 'Bearer ' + Auth.getToken() }
        });
        const data = await response.json();
        return data.success ? data.data.servers : [];
    }
    
    /**
     * Load user's devices from API
     */
    static async loadDevices() {
        const response = await fetch('/api/devices/list.php', {
            headers: { 'Authorization': 'Bearer ' + Auth.getToken() }
        });
        const data = await response.json();
        
        if (data.success) {
            this.renderDeviceList(data.data.devices);
        }
    }
    
    /**
     * Render device list on dashboard
     */
    static renderDeviceList(devices) {
        const container = document.getElementById('device-list');
        
        if (!devices.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🖥️</div>
                    <h3>No devices connected yet</h3>
                    <p>Add your first device to start using TrueVault VPN</p>
                    <button class="btn btn-primary" onclick="DeviceManager.showAddDeviceModal()">
                        + Add Your First Device
                    </button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = devices.map(device => `
            <div class="device-card">
                <div class="device-info">
                    <div class="device-icon">📱</div>
                    <div class="device-details">
                        <h3>${device.name}</h3>
                        <p>Server: ${device.server.flag} ${device.server.name}</p>
                    </div>
                </div>
                <div class="device-actions">
                    <button class="btn btn-secondary btn-small" onclick="DeviceManager.showSwitchServerModal(${device.id}, '${device.name}', ${device.server.id})">
                        Switch Server
                    </button>
                    <button class="btn btn-secondary btn-small" onclick="DeviceManager.redownloadConfig(${device.id})">
                        Download Config
                    </button>
                    <button class="btn btn-danger btn-small" onclick="DeviceManager.removeDevice(${device.id})">
                        Remove
                    </button>
                </div>
            </div>
        `).join('');
        
        // Add "Add Device" button
        container.innerHTML += `
            <button class="btn btn-primary add-device-btn" onclick="DeviceManager.showAddDeviceModal()">
                + Add Another Device
            </button>
        `;
    }
    
    /**
     * Show switch server modal
     */
    static async showSwitchServerModal(deviceId, deviceName, currentServerId) {
        const servers = await this.loadServers();
        
        const modalHtml = `
            <div class="modal-overlay" id="switch-server-modal">
                <div class="modal">
                    <div class="modal-header">
                        <h2>Switch Server</h2>
                        <button class="modal-close" onclick="DeviceManager.closeModal('switch-server-modal')">×</button>
                    </div>
                    <div class="modal-body">
                        <p class="device-context">Device: <strong>${deviceName}</strong></p>
                        
                        <div class="form-group">
                            <label>Select new server:</label>
                            <div class="server-list">
                                ${servers.map(s => `
                                    <label class="server-option ${s.id === currentServerId ? 'current' : ''}">
                                        <input type="radio" name="new-server" value="${s.id}" 
                                               ${s.id === currentServerId ? 'disabled' : ''}>
                                        <span class="server-flag">${s.flag}</span>
                                        <span class="server-name">${s.full_name}</span>
                                        ${s.id === currentServerId ? '<span class="current-badge">Current</span>' : ''}
                                        ${s.is_vip ? '<span class="vip-badge">VIP</span>' : ''}
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="warning-note">
                            ⚠️ You'll need to download a new config file and import it into WireGuard.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="DeviceManager.switchServer(${deviceId}, '${deviceName}')">
                            Switch Server
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * Switch server - called when user confirms
     */
    static async switchServer(deviceId, deviceName) {
        const newServerId = document.querySelector('input[name="new-server"]:checked')?.value;
        
        if (!newServerId) {
            App.showToast('Please select a new server', 'error');
            return;
        }
        
        const btn = document.querySelector('#switch-server-modal .modal-footer .btn');
        btn.innerHTML = '<span class="spinner"></span> Switching...';
        btn.disabled = true;
        
        try {
            // Generate new keypair for new server
            const keys = await WireGuardKeys.generateKeypair();
            
            const response = await fetch('/api/devices/switch-server.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + Auth.getToken()
                },
                body: JSON.stringify({
                    device_id: deviceId,
                    new_server_id: parseInt(newServerId),
                    new_public_key: keys.publicKey
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to switch server');
            }
            
            // Generate new config
            const configContent = WireGuardKeys.generateConfig(
                keys.privateKey,
                data.data.assigned_ip,
                data.data.new_server.public_key,
                data.data.new_server.endpoint
            );
            
            // Show download screen
            const modal = document.getElementById('switch-server-modal');
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header success">
                        <h2>✅ Server Switched!</h2>
                        <button class="modal-close" onclick="DeviceManager.closeModal('switch-server-modal')">×</button>
                    </div>
                    <div class="modal-body">
                        <p><strong>${deviceName}</strong> is now on <strong>${data.data.new_server.display_name}</strong></p>
                        
                        <div class="download-card">
                            <button class="btn btn-primary btn-large" id="download-new-config">
                                ⬇️ Download New Config
                            </button>
                        </div>
                        
                        <div class="important-note">
                            <strong>Important:</strong> Delete your old config from WireGuard and import this new one.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="DeviceManager.closeModal('switch-server-modal')">Done</button>
                    </div>
                </div>
            `;
            
            document.getElementById('download-new-config').onclick = () => {
                WireGuardKeys.downloadConfig(configContent, deviceName, data.data.new_server.display_name);
            };
            
        } catch (error) {
            App.showToast(error.message, 'error');
            btn.innerHTML = 'Switch Server';
            btn.disabled = false;
        }
    }
}
```

---

# UPDATED CHECKLIST ITEMS

Add these to TRUEVAULT_MASTER_CHECKLIST_V2.md:

```markdown
## PHASE 5A: SIMPLIFIED DEVICE WORKFLOW ❌ NOT STARTED

### 5A.1 Database Updates
- [ ] Add device_server_history table
- [ ] Update user_devices table schema
- [ ] Add display_name and country_flag to vpn_servers
- [ ] Insert server display data

### 5A.2 Server APIs
- [ ] /api/servers/available.php - GET servers for dropdown
- [ ] Test server dropdown shows flags and names

### 5A.3 Device APIs
- [ ] /api/devices/add.php - POST add new device (2-click flow)
- [ ] /api/devices/switch-server.php - POST switch device server
- [ ] /api/devices/list.php - GET user's devices with server info
- [ ] /api/devices/remove.php - DELETE remove device

### 5A.4 Frontend - Add Device Modal
- [ ] Add "Add Device" button on dashboard
- [ ] Create modal with device name input
- [ ] Create server selection radio buttons with flags
- [ ] Implement WireGuardKeys.generateKeypair() in browser
- [ ] Call /api/devices/add.php with public key
- [ ] Show download screen after success
- [ ] Generate .conf file in browser
- [ ] Download file with device-server naming

### 5A.5 Frontend - Switch Server Modal
- [ ] Add "Switch Server" button on each device card
- [ ] Create modal showing current server
- [ ] Show available servers with flags
- [ ] Generate new keypair on switch
- [ ] Call /api/devices/switch-server.php
- [ ] Show download screen for new config

### 5A.6 Frontend - Device List
- [ ] Display all devices with server info
- [ ] Show server flag and name for each device
- [ ] Add Switch Server, Download, Remove buttons
- [ ] Show "Add Your First Device" for empty state

### 5A.7 Include Required Libraries
- [ ] Include tweetnacl.js for key generation
- [ ] Test key generation works in all browsers
```

---

# SUMMARY: HOW IT ALL WORKS

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         THE SIMPLE USER JOURNEY                              │
└─────────────────────────────────────────────────────────────────────────────┘

1. SIGNUP (30 seconds)
   - Enter email, password, name
   - Click "Start Free Trial"
   - No credit card
   - Logged into dashboard

2. ADD DEVICE (30 seconds)
   - Click "Add Device" 
   - Enter device name: "My iPhone"
   - Select server: 🇨🇦 Canada
   - Click "Add Device & Get Config"
   - → Keys generated instantly (in browser)
   - → Server handshake happens (2 seconds)
   - → Download button appears
   - Click download
   - Import into WireGuard
   - DONE!

3. SWITCH SERVER (15 seconds)
   - Click "Switch Server" on device
   - Select new server: 🇺🇸 Texas
   - Click "Switch"
   - → New keys generated
   - → Old server peer removed
   - → New server peer added
   - Download new config
   - Replace in WireGuard
   - DONE!

NO EMAILS. NO WAITING. NO CONFUSION.
JUST CLICKS AND DOWNLOADS.
```

---

**END OF SIMPLIFIED WORKFLOW DOCUMENTATION**
