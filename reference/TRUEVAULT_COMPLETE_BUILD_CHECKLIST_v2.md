# TrueVault VPN - COMPLETE BUILD CHECKLIST v2.0
## The REAL Checklist - No Placeholders, Real Code Only
**Created:** January 13, 2026 - 10:45 PM CST
**Purpose:** Replace the broken/placeholder system with WORKING code

---

# ⚠️ CRITICAL RULES - READ BEFORE ANY WORK

## RULE 1: NO PLACEHOLDERS
- Every file MUST contain FULLY FUNCTIONAL code
- NO "TODO", "Coming soon", "Implement later"
- NO hardcoded fake data
- NO mock API responses
- If it doesn't work, DON'T ship it

## RULE 2: NO HARDCODED STYLES
- ALL colors/fonts from themes.db → CSS variables
- If you see #ffffff in HTML, it's WRONG
- ONLY use: var(--colors-primary), etc.

## RULE 3: DATABASE-DRIVEN
- Everything configurable comes from database
- Page content, email templates, theme, settings

## RULE 4: VIP SERVER RULE  
- Server 144.126.133.253 is DEDICATED to seige235@yahoo.com
- Only VIP user can connect to it

---

# CURRENT STATE AUDIT (January 13, 2026)

## What's ACTUALLY Working ✅
| Component | Status | Notes |
|-----------|--------|-------|
| Login/Register API | ✅ Working | Fixed Jan 13 |
| servers.php API | ✅ Working | Returns real server data with public keys |
| VIP detection | ✅ Working | Checks vip.db |
| Theme CSS variables | ✅ Working | theme-loader.js loads from API |
| WireGuard on servers | ✅ Working | All 4 servers have WireGuard + public keys |

## What's BROKEN/PLACEHOLDER ❌
| Component | Status | Problem |
|-----------|--------|---------|
| devices.html | ❌ PLACEHOLDER | Hardcoded fake devices, no API calls |
| certificates.html | ❌ PLACEHOLDER | API may not exist, features show "coming soon" |
| connect.html | ❌ BROKEN | "Connect" button doesn't generate real config |
| Device add flow | ❌ MISSING | No API to add device + generate WireGuard config |
| QR code generation | ❌ MISSING | No real QR codes |
| Config file download | ❌ PLACEHOLDER | Shows fake config, not real keys |

---

# THE CORRECT WIREGUARD CONNECTION FLOW

## How It SHOULD Work (User Perspective)

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER ADDS A DEVICE                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. User logs into dashboard                                    │
│  2. Goes to "Devices" page                                      │
│  3. Clicks "➕ Add Device"                                       │
│  4. Enters device name (e.g., "My iPhone")                      │
│  5. Selects server (e.g., "New York")                           │
│  6. System generates config + QR code                           │
│  7. User scans QR OR downloads .conf file                       │
│  8. User imports into WireGuard app                             │
│  9. User toggles ON in WireGuard app                            │
│ 10. ✅ CONNECTED!                                                │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## How It SHOULD Work (Technical)

```
┌─────────────────────────────────────────────────────────────────┐
│                    BACKEND FLOW                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  STEP 1: Frontend calls POST /api/devices/add.php               │
│          { name: "My iPhone", server_id: 1 }                    │
│                                                                 │
│  STEP 2: API checks device limit (plan based)                   │
│          Personal: 3 devices                                    │
│          Family: Unlimited                                      │
│          VIP: Unlimited                                         │
│                                                                 │
│  STEP 3: API generates WireGuard keypair                        │
│          - Private key (give to user, DELETE from server)       │
│          - Public key (store in database)                       │
│                                                                 │
│  STEP 4: API assigns IP address from pool                       │
│          10.0.0.2, 10.0.0.3, etc.                               │
│                                                                 │
│  STEP 5: API calls VPN server to add peer                       │
│          POST http://66.94.103.91:8080/add-peer                 │
│          { public_key: "xxx", allowed_ips: "10.0.0.2/32" }      │
│                                                                 │
│  STEP 6: API generates client config file:                      │
│          [Interface]                                            │
│          PrivateKey = <user's private key>                      │
│          Address = 10.0.0.2/32                                  │
│          DNS = 1.1.1.1                                          │
│                                                                 │
│          [Peer]                                                 │
│          PublicKey = <server's public key>                      │
│          AllowedIPs = 0.0.0.0/0                                 │
│          Endpoint = 66.94.103.91:51820                          │
│          PersistentKeepalive = 25                               │
│                                                                 │
│  STEP 7: API generates QR code from config                      │
│                                                                 │
│  STEP 8: API returns to frontend:                               │
│          { config_text, config_file_url, qr_code_base64 }       │
│                                                                 │
│  STEP 9: Frontend displays QR + download button                 │
│                                                                 │
│  STEP 10: User imports config into WireGuard app                │
│           → Connection established!                             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

# PHASE 1: FIX SERVER-SIDE PEER MANAGEMENT

## 1.1 peer_api.py on Each Server
The peer_api.py script must be running on each VPN server to:
- Add peers (register new devices)
- Remove peers (delete devices)
- List peers (for admin)
- Health check

### Files to verify/create on EACH server:
- [ ] SSH into 66.94.103.91 (NY)
- [ ] Verify peer_api.py exists at /opt/truevault/peer_api.py
- [ ] Verify it's running: `systemctl status peer-api`
- [ ] Test endpoint: `curl http://localhost:8080/health`
- [ ] Repeat for 144.126.133.253 (STL)
- [ ] Repeat for 66.241.124.4 (Dallas)
- [ ] Repeat for 66.241.125.247 (Toronto)

### peer_api.py Endpoints Required:
```
GET  /health              - Health check
POST /add-peer            - Add WireGuard peer
     { public_key, allowed_ips }
POST /remove-peer         - Remove WireGuard peer  
     { public_key }
GET  /peers               - List all peers (admin only)
GET  /server-info         - Get server public key
```

---

# PHASE 2: CREATE DEVICE MANAGEMENT API

## 2.1 /api/devices/add.php (NEW - MUST CREATE)
This is the CORE missing piece!

```php
<?php
// POST /api/devices/add.php
// Adds a new device and generates WireGuard config

// Input: { name, server_id }
// Output: { device_id, config_text, config_file, qr_code }

// Steps:
// 1. Authenticate user
// 2. Check device limit
// 3. Generate WireGuard keypair (wg genkey | wg pubkey)
// 4. Assign IP from pool (10.0.0.x)
// 5. Call VPN server API to add peer
// 6. Store device in database (public key only)
// 7. Generate config text
// 8. Generate QR code (base64)
// 9. Return config + QR to frontend
// 10. NEVER store private key - send to user only
```

### Checklist:
- [ ] Create /api/devices/add.php
- [ ] Implement WireGuard key generation (exec wg genkey)
- [ ] Implement IP pool allocation (10.0.0.2 - 10.0.0.254)
- [ ] Implement server API call to register peer
- [ ] Implement config file generation
- [ ] Implement QR code generation (use PHP library or exec qrencode)
- [ ] Store device record in database
- [ ] Return complete response to frontend

## 2.2 /api/devices/list.php (NEW)
- [ ] Create list.php
- [ ] Return all user's devices from database
- [ ] Include: name, server, created_at, last_seen, status

## 2.3 /api/devices/delete.php (NEW)
- [ ] Create delete.php
- [ ] Call VPN server API to remove peer
- [ ] Delete device from database
- [ ] Return success

## 2.4 /api/devices/config.php (NEW)
- [ ] Create config.php
- [ ] GET with device_id
- [ ] Regenerate config (new private key + same public key)
- [ ] Return config + QR
- [ ] For when user needs to re-download config

---

# PHASE 3: FIX DEVICES FRONTEND PAGE

## 3.1 Rewrite devices.html
The current page is 100% placeholder. Must rewrite to:

- [ ] Remove ALL hardcoded device cards
- [ ] Add loadDevices() function calling /api/devices/list.php
- [ ] Render devices dynamically from API response
- [ ] Add real "Add Device" flow:
  - [ ] Name input
  - [ ] Server selection dropdown
  - [ ] Call /api/devices/add.php
  - [ ] Display REAL QR code (base64 image)
  - [ ] Display REAL config text
  - [ ] Download button for .conf file
- [ ] Add delete device functionality
- [ ] Add regenerate config functionality
- [ ] Show device limit based on plan

---

# PHASE 4: FIX CONNECT PAGE

## 4.1 connect.html Flow
The connect page should:

1. Check if user has any devices registered
2. If no devices → redirect to devices.html with prompt to add device
3. If has devices → show connection status
4. The actual "connection" happens in WireGuard app on user's device
5. We can only show:
   - Which device configs they have
   - Server status (online/offline)
   - Bandwidth stats (if server reports them)

### Checklist:
- [ ] Rewrite connect.html
- [ ] Show list of user's devices with their assigned servers
- [ ] Show server status
- [ ] Remove fake "Connect" button that does nothing
- [ ] Add link to download WireGuard app
- [ ] Add instructions for connecting

---

# PHASE 5: FIX CERTIFICATES

## 5.1 Decide: Do We Need Certificates?
For WireGuard, we DON'T need traditional X.509 certificates.
WireGuard uses simple public/private keypairs.

The "certificates" feature was designed for:
- Regional identities (different IPs per region)
- Mesh networking between users
- Enterprise authentication

### If keeping certificates:
- [ ] Create /api/certificates/index.php (currently missing?)
- [ ] Implement real certificate generation
- [ ] Remove "coming soon" placeholders

### If removing certificates:
- [ ] Remove certificates.html from sidebar
- [ ] Or repurpose to show WireGuard keys

---

# PHASE 6: DATABASE SCHEMA FOR DEVICES

## 6.1 devices table in devices.db

```sql
CREATE TABLE devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    uuid TEXT UNIQUE NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT DEFAULT 'unknown',
    server_id INTEGER NOT NULL,
    assigned_ip TEXT NOT NULL,
    public_key TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_handshake DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE ip_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    ip_address TEXT NOT NULL,
    device_id INTEGER,
    assigned_at DATETIME,
    UNIQUE(server_id, ip_address)
);
```

### Checklist:
- [ ] Add devices table to setup-databases.php
- [ ] Add ip_pool table
- [ ] Pre-populate IP pool for each server (10.0.0.2 - 10.0.0.254)
- [ ] Run database setup

---

# PHASE 7: SERVERS PAGE IMPROVEMENTS

## 7.1 servers.html
The servers page WORKS (calls real API) but needs:

- [ ] Remove "Connect" button (users connect via WireGuard app)
- [ ] Add "Setup Device for This Server" button
- [ ] Show server status (ping/health check)
- [ ] Show current load (if available from server API)
- [ ] Show user's devices connected to each server

---

# PHASE 8: IP POOL MANAGEMENT

## 8.1 IP Allocation System
Each server needs its own IP pool:
- Server 1 (NY): 10.1.0.0/24 → 10.1.0.2 - 10.1.0.254
- Server 2 (STL): 10.2.0.0/24 → 10.2.0.2 - 10.2.0.254  
- Server 3 (Dallas): 10.3.0.0/24 → 10.3.0.2 - 10.3.0.254
- Server 4 (Toronto): 10.4.0.0/24 → 10.4.0.2 - 10.4.0.254

### Checklist:
- [ ] Create IP allocation function in PHP
- [ ] Assign next available IP when device is added
- [ ] Release IP when device is deleted
- [ ] Handle IP conflicts

---

# PHASE 9: QR CODE GENERATION

## 9.1 QR Code Options

### Option A: PHP Library (recommended)
```bash
composer require bacon/bacon-qr-code
```

### Option B: System command (qrencode)
```bash
apt install qrencode
qrencode -o qr.png "config text"
```

### Checklist:
- [ ] Install QR code library or binary
- [ ] Create qrcode.php helper function
- [ ] Generate QR code as base64 data URL
- [ ] Display in modal on devices page

---

# PHASE 10: COMPLETE API LIST

## APIs That EXIST and WORK ✅
- [x] /api/auth/login.php
- [x] /api/auth/register.php
- [x] /api/auth/logout.php
- [x] /api/auth/refresh.php
- [x] /api/vpn/servers.php
- [x] /api/theme.php

## APIs That NEED TO BE CREATED ❌
- [ ] /api/devices/add.php - ADD DEVICE + GENERATE CONFIG
- [ ] /api/devices/list.php - LIST USER DEVICES
- [ ] /api/devices/delete.php - DELETE DEVICE
- [ ] /api/devices/config.php - REGENERATE CONFIG
- [ ] /api/devices/status.php - CHECK DEVICE STATUS
- [ ] /api/vpn/health.php - CHECK SERVER HEALTH
- [ ] /api/admin/peers.php - ADMIN VIEW ALL PEERS
- [ ] /api/admin/servers.php - ADMIN SERVER MANAGEMENT

## APIs That MAY BE PLACEHOLDER
- [ ] /api/certificates/index.php - VERIFY OR REMOVE
- [ ] /api/vpn/connect.php - VERIFY WHAT IT DOES
- [ ] /api/vpn/disconnect.php - VERIFY WHAT IT DOES

---

# PHASE 11: FRONTEND PAGES AUDIT

## Pages That WORK ✅
- [x] login.html - Works
- [x] register.html - Works
- [x] servers.html - Calls real API (needs UI tweaks)

## Pages That Are PLACEHOLDER ❌
- [ ] devices.html - 100% HARDCODED - REWRITE
- [ ] certificates.html - PLACEHOLDER - REWRITE OR REMOVE
- [ ] connect.html - MISLEADING - REWRITE
- [ ] identities.html - CHECK IF WORKS
- [ ] cameras.html - CHECK IF WORKS
- [ ] mesh.html - CHECK IF WORKS
- [ ] scanner.html - CHECK IF WORKS
- [ ] settings.html - CHECK IF WORKS
- [ ] billing.html - CHECK IF WORKS

---

# PRIORITY ORDER

## MUST DO FIRST (Critical Path)
1. [ ] Verify peer_api.py running on all 4 servers
2. [ ] Create /api/devices/add.php with REAL key generation
3. [ ] Create /api/devices/list.php
4. [ ] Rewrite devices.html to use real APIs
5. [ ] Test complete flow: Add device → Get config → Connect

## SHOULD DO SECOND
6. [ ] Fix connect.html to show actual status
7. [ ] Add QR code generation
8. [ ] Create /api/devices/delete.php
9. [ ] Fix servers.html UI

## CAN DO LATER
10. [ ] Certificates system (if keeping)
11. [ ] Cameras integration
12. [ ] Mesh networking
13. [ ] Admin dashboard

---

# TEST CHECKLIST

## End-to-End Test
1. [ ] Register new user
2. [ ] Login
3. [ ] Go to Devices page
4. [ ] Click "Add Device"
5. [ ] Enter name, select server
6. [ ] Get real config with real keys
7. [ ] Scan QR code with WireGuard app
8. [ ] Toggle ON in WireGuard
9. [ ] Verify connection works (check IP shows VPN server)
10. [ ] Disconnect
11. [ ] Delete device from dashboard
12. [ ] Verify device removed from server

---

# NOTES

- The "Connect" concept is CONFUSING because users don't connect VIA the dashboard
- Users connect via WireGuard app on their device
- Dashboard is for MANAGING devices/configs, not connecting
- Rename "Connect" page to "Connection Status" or remove it
- Focus on DEVICE MANAGEMENT as the primary feature

---

**END OF CHECKLIST v2.0**
