# TRUEVAULT VPN - MASTER BLUEPRINT V3
## Complete Launch-Ready Build Guide
**Created:** January 13, 2026 - 10:50 PM CST
**Author:** Claude AI Assistant
**Status:** DEFINITIVE BUILD GUIDE

---

# TABLE OF CONTENTS

1. [EXECUTIVE SUMMARY](#1-executive-summary)
2. [CURRENT STATE AUDIT](#2-current-state-audit)
3. [INFRASTRUCTURE](#3-infrastructure)
4. [DATABASE ARCHITECTURE](#4-database-architecture)
5. [API IMPLEMENTATION STATUS](#5-api-implementation-status)
6. [FRONTEND STATUS](#6-frontend-status)
7. [VPN SERVER INTEGRATION](#7-vpn-server-integration)
8. [LAUNCH CHECKLIST](#8-launch-checklist)
9. [BUILD SEQUENCE](#9-build-sequence)

---

# 1. EXECUTIVE SUMMARY

## What TrueVault VPN Is
TrueVault VPN is an advanced VPN service with these unique features:
- **WireGuard-based VPN** with 4 servers (2 Contabo, 2 Fly.io)
- **IP Camera Integration** - View cameras remotely through VPN tunnel
- **Network Scanner** - Desktop tool to discover devices
- **VIP System** - Dedicated server for premium users
- **Database-Driven Theming** - All styles from database, editable via CMS

## Current Progress
- **Overall: ~50% Complete**
- Backend APIs: ~70% functional
- Frontend Pages: Exist but need database-driven styling
- VPN Servers: Configured and running
- Billing: PayPal integration ready
- Missing: Certificates, Port Forwarding, Automation, Business Dashboard

## Critical Path to Launch
1. Fix hardcoded styles → Make database-driven
2. Complete VPN connection flow end-to-end
3. Test payment flow with real PayPal
4. Deploy scanner tool downloads
5. Test with VIP user (seige235@yahoo.com)

---

# 2. CURRENT STATE AUDIT

## 2.1 What's Working ✅

| Component | Status | Details |
|-----------|--------|---------|
| User Registration | ✅ Working | Creates user, hashes password, generates UUID |
| User Login | ✅ Working | JWT tokens, VIP detection, session management |
| VPN Server List | ✅ Working | Returns 4 servers, filters VIP servers |
| Theme API | ✅ Working | Returns theme with database/fallback values |
| Database Setup | ✅ Working | Creates all tables, seeds VIP users |
| VIP System | ✅ Working | Identifies seige235@yahoo.com, bypasses payment |
| PayPal Config | ✅ Ready | Live credentials configured |

## 2.2 What Has Issues ⚠️

| Component | Issue | Fix Required |
|-----------|-------|--------------|
| Landing Page (index.html) | ALL CSS hardcoded in <style> tags | Convert to CSS variables + theme loader |
| Dashboard Pages | May have hardcoded colors | Audit and convert |
| Emoji Display | Shows "???" on server | UTF-8 encoding headers |
| VPN Connect | Needs peer provisioning test | Test with real device |
| PayPal Webhook | Points to builder subdomain | Should point to vpn subdomain |
| Scanner Downloads | Files exist but not zipped | Create download packages |

## 2.3 What's Missing ❌

| Component | Priority | Effort |
|-----------|----------|--------|
| Certificate System | Medium | 3-4 hours |
| Port Forwarding UI | Medium | 2-3 hours |
| Automation Engine | Low | 4-5 hours |
| Business Dashboard | Low | 8-10 hours |
| Camera Controls | Low | 3-4 hours |
| Mesh Invitations | Low | 2-3 hours |
| PDF Invoices | Low | 2-3 hours |

---

# 3. INFRASTRUCTURE

## 3.1 Web Hosting (GoDaddy)

```
Domain: vpn.the-truth-publishing.com
Document Root: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com
PHP Version: 8.2
cPanel: https://the-truth-publishing.com:2083
Username: 26853687
Password: Asasasas4!
```

## 3.2 FTP Access

```
Host: the-truth-publishing.com
User: kahlen@the-truth-publishing.com
Password: AndassiAthena8
Port: 21
Remote Path: /public_html/vpn.the-truth-publishing.com
```

## 3.3 VPN Servers

### Server 1: Contabo US-East (SHARED)
```
Name: TrueVaultNY
IP: 66.94.103.91
IPv6: 2605:a142:2299:0026:0000:0000:0000:0001
WireGuard Port: 51820
SSH: root / TrueVault2026
Public Key: lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=
Subnet: 10.0.0.0/24
Type: Shared (gaming, cameras, torrents OK)
```

### Server 2: Contabo US-Central (VIP DEDICATED)
```
Name: TrueVaultSTL
IP: 144.126.133.253
IPv6: 2605:a140:2299:0005:0000:0000:0000:0001
WireGuard Port: 51820
SSH: root / TrueVault2026
Public Key: qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=
Subnet: 10.0.1.0/24
Type: VIP Dedicated (seige235@yahoo.com ONLY)
```

### Server 3: Fly.io Dallas (SHARED)
```
Name: TrueVaultTX
App: truthvault-usa
IP: 66.241.124.4
Release IP: 137.66.58.225
WireGuard Port: 51820
API Port: 8443
Public Key: dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=
Subnet: 10.0.2.0/24
Type: Shared (streaming only, no torrents)
```

### Server 4: Fly.io Toronto (SHARED)
```
Name: TrueVaultCAN
App: truthvault-canada
IP: 66.241.125.247
Release IP: 37.16.6.139
WireGuard Port: 51820
API Port: 8080
Public Key: O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=
Subnet: 10.0.3.0/24
Type: Shared (Canadian streaming)
```

## 3.4 PayPal (LIVE)

```
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
Business Email: paulhalonen@gmail.com
Webhook ID: 46924926WL757580D
Webhook URL: https://vpn.the-truth-publishing.com/api/billing/webhook.php
```

---

# 4. DATABASE ARCHITECTURE

## 4.1 Database Files on Server

The site uses SQLite databases stored in `/data/` directory:

| Database | Purpose | Tables |
|----------|---------|--------|
| users.db | User accounts | users, vip_users, user_settings |
| admin_users.db | Admin accounts | admin_users |
| vpn.db | VPN connections | user_peers, connection_logs |
| servers.db | Server list | vpn_servers |
| certificates.db | PKI certs | certificates, certificate_requests |
| devices.db | Discovered devices | discovered_devices |
| cameras.db | IP cameras | cameras, camera_events |
| identities.db | Regional IDs | regional_identities |
| mesh.db | Mesh networks | mesh_networks, mesh_members |
| themes.db | UI themes | themes, theme_variables |
| settings.db | System settings | settings |
| plans.db | Subscription plans | plans |
| payments.db | Payment history | payments |
| subscriptions.db | User subs | subscriptions |
| automation.db | Workflows | workflows, workflow_steps |
| logs.db | System logs | access_logs, error_logs |
| support.db | Support tickets | tickets, ticket_replies |
| vip.db | VIP users | vip_users |
| analytics.db | Usage stats | page_views, events |
| bandwidth.db | Usage tracking | bandwidth_usage |
| notifications.db | User notifs | notifications |
| emails.db | Email templates | email_templates, email_log |
| media.db | Media files | media_files |
| pages.db | CMS pages | pages, page_revisions |

## 4.2 Key Schema: users.db

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    status TEXT DEFAULT 'active',
    is_vip INTEGER DEFAULT 0,
    email_verified INTEGER DEFAULT 0,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vip_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL DEFAULT 'vip_basic',
    plan TEXT NOT NULL DEFAULT 'family',
    dedicated_server_id INTEGER,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 4.3 Key Schema: themes.db

```sql
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    is_active INTEGER DEFAULT 0,
    variables TEXT, -- JSON blob of all theme variables
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Default Theme Variables (JSON):**
```json
{
    "colors": {
        "primary": "#00d9ff",
        "secondary": "#00ff88",
        "accent": "#ff6b6b",
        "background": "#0f0f1a",
        "backgroundSecondary": "#1a1a2e",
        "text": "#ffffff",
        "textMuted": "#888888",
        "success": "#00ff88",
        "warning": "#ffbb00",
        "error": "#ff5050",
        "border": "rgba(255,255,255,0.08)"
    },
    "gradients": {
        "primary": "linear-gradient(90deg, #00d9ff, #00ff88)",
        "background": "linear-gradient(135deg, #0f0f1a, #1a1a2e)"
    },
    "typography": {
        "fontFamily": "Inter, sans-serif",
        "fontSizeBase": "16px"
    },
    "buttons": {
        "borderRadius": "8px",
        "padding": "10px 20px"
    }
}
```

---

# 5. API IMPLEMENTATION STATUS

## 5.1 Authentication APIs ✅ COMPLETE

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/auth/login.php | POST | ✅ Working | Login with VIP detection |
| /api/auth/register.php | POST | ✅ Working | Registration with UUID |
| /api/auth/logout.php | POST | ✅ Working | Invalidate token |
| /api/auth/refresh.php | POST | ✅ Working | Refresh JWT |
| /api/auth/forgot-password.php | POST | ✅ Exists | Password reset email |
| /api/auth/reset-password.php | POST | ✅ Exists | Reset with token |

## 5.2 VPN APIs ✅ COMPLETE

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/vpn/servers.php | GET | ✅ Working | List servers with VIP filter |
| /api/vpn/connect.php | POST | ✅ Exists | Request VPN connection |
| /api/vpn/config.php | GET | ✅ Exists | Download WireGuard config |
| /api/vpn/status.php | GET | ✅ Exists | Connection status |
| /api/vpn/disconnect.php | POST | ✅ Exists | Disconnect from VPN |

## 5.3 Billing APIs ✅ MOSTLY COMPLETE

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/billing/plans.php | GET | ✅ Working | List pricing plans |
| /api/billing/checkout.php | POST | ✅ Exists | Create PayPal order |
| /api/billing/complete.php | POST | ✅ Exists | Capture payment |
| /api/billing/subscription.php | GET | ✅ Exists | Get subscription |
| /api/billing/webhook.php | POST | ⚠️ Needs Fix | Webhook URL wrong |
| /api/billing/invoices.php | GET | ❌ Missing | List invoices |

## 5.4 Device/Camera APIs 🔄 PARTIAL

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/devices/list.php | GET | ✅ Exists | List discovered devices |
| /api/devices/sync.php | POST | ⚠️ Needs Test | Sync from scanner |
| /api/cameras/list.php | GET | ✅ Exists | List cameras |
| /api/cameras/stream.php | GET | ❌ Missing | Get stream URL |
| /api/cameras/control.php | POST | ❌ Missing | Camera controls |

## 5.5 Admin APIs 🔄 PARTIAL

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/admin/users.php | GET | ✅ Exists | List users |
| /api/admin/servers.php | GET | ✅ Exists | List servers |
| /api/admin/stats.php | GET | ✅ Exists | Dashboard stats |
| /api/admin/logs.php | GET | ✅ Exists | System logs |
| /api/admin/themes.php | GET/PUT | ⚠️ Needs Work | Theme management |

## 5.6 Theme API ✅ COMPLETE

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/theme/index.php | GET | ✅ Working | Get active theme |
| /api/theme/index.php | PUT | ⚠️ Needs Admin | Update theme |

## 5.7 Scanner API 🔄 PARTIAL

| Endpoint | Method | Status | Description |
|----------|--------|--------|-------------|
| /api/scanner/token.php | GET | ✅ Exists | Generate auth token |
| /api/scanner/sync.php | POST | ⚠️ Needs Test | Sync discovered devices |
| /api/scanner/download.php | GET | ❌ Missing | Download scanner package |

## 5.8 Missing API Groups ❌

| Group | Status | Priority |
|-------|--------|----------|
| /api/certificates/* | ❌ Not Started | Medium |
| /api/port-forwarding/* | ❌ Not Started | Medium |
| /api/mesh/* (full CRUD) | 🔄 Partial | Low |
| /api/automation/* | ❌ Not Started | Low |

---

# 6. FRONTEND STATUS

## 6.1 Public Pages

| Page | Location | Status | Issues |
|------|----------|--------|--------|
| Landing | /index.html | ✅ Exists | Hardcoded CSS |
| Login | /public/login.html | ✅ Working | Needs style audit |
| Register | /public/register.html | ✅ Working | Needs style audit |
| Payment Success | /public/payment-success.html | ✅ Exists | |
| Payment Cancel | /public/payment-cancel.html | ✅ Exists | |

## 6.2 Dashboard Pages

| Page | Location | Status | Issues |
|------|----------|--------|--------|
| Overview | /public/dashboard/index.html | ✅ Exists | Needs audit |
| Connect | /public/dashboard/connect.html | ✅ Exists | Needs testing |
| Servers | /public/dashboard/servers.html | ✅ Exists | |
| Devices | /public/dashboard/devices.html | ✅ Exists | |
| Cameras | /public/dashboard/cameras.html | ✅ Exists | |
| Scanner | /public/dashboard/scanner.html | ✅ Exists | |
| Settings | /public/dashboard/settings.html | ✅ Exists | |
| Certificates | /public/dashboard/certificates.html | ✅ Exists | Placeholder |
| Identities | /public/dashboard/identities.html | ✅ Exists | Placeholder |
| Mesh | /public/dashboard/mesh.html | ✅ Exists | Needs work |
| Billing | /dashboard/billing.html | ✅ Exists | |

## 6.3 Admin Pages

| Page | Location | Status |
|------|----------|--------|
| Login | /public/admin/index.html | ✅ Exists |
| Dashboard | /admin/index.html | ✅ Exists |
| Users | /admin/users.html | ✅ Exists |
| Servers | /admin/servers.html | ✅ Exists |
| VIP | /admin/vip.html | ✅ Exists |
| Settings | /admin/settings.html | ✅ Exists |
| Logs | /admin/logs.html | ✅ Exists |
| Billing | /admin/billing.html | ✅ Exists |

## 6.4 Assets

| Asset | Location | Status |
|-------|----------|--------|
| Main CSS | /public/assets/css/main.css | ⚠️ Needs audit |
| Dashboard CSS | /public/assets/css/dashboard.css | ⚠️ Needs audit |
| App JS | /public/assets/js/app.js | ✅ Fixed |
| Theme Loader | /public/assets/js/theme-loader.js | ✅ Exists |

---

# 7. VPN SERVER INTEGRATION

## 7.1 Peer API (peer_api.py)

Each VPN server needs the peer_api.py script running to handle peer provisioning.

**Script Location:** `/root/peer_api.py` (on each server)

**API Endpoints:**
- `POST /add_peer` - Add new WireGuard peer
- `DELETE /remove_peer` - Remove peer
- `GET /status` - Server status

**Deployment Status:**
| Server | IP | peer_api.py | Status |
|--------|-----|-------------|--------|
| US-East | 66.94.103.91 | ⚠️ Needs deploy | Manual |
| US-Central | 144.126.133.253 | ⚠️ Needs deploy | Manual |
| Dallas | 66.241.124.4 | ⚠️ Needs verify | Via fly.io |
| Canada | 66.241.125.247 | ⚠️ Needs verify | Via fly.io |

## 7.2 Connection Flow

```
User Dashboard → VPN Connect Button
        ↓
POST /api/vpn/connect.php
        ↓
Server generates WireGuard keypair for user
        ↓
POST to VPN Server peer_api.py /add_peer
        ↓
VPN Server adds peer to wg0.conf
        ↓
Return WireGuard config to user
        ↓
User imports config to WireGuard client
        ↓
User connects to VPN
```

## 7.3 WireGuard Config Format

```ini
[Interface]
PrivateKey = {user_private_key}
Address = 10.0.X.Y/32
DNS = 1.1.1.1

[Peer]
PublicKey = {server_public_key}
Endpoint = {server_ip}:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25
```

---

# 8. LAUNCH CHECKLIST

## 8.1 CRITICAL (Must Do Before Launch)

- [ ] Fix PayPal webhook URL (change to vpn subdomain)
- [ ] Test full registration → payment → VPN connect flow
- [ ] Test VIP login (seige235@yahoo.com)
- [ ] Verify VIP gets dedicated server access
- [ ] Deploy peer_api.py to Contabo servers
- [ ] Test WireGuard connection end-to-end
- [ ] Audit and fix hardcoded styles in index.html
- [ ] Ensure theme-loader.js runs on all pages
- [ ] Create scanner download packages (zip files)
- [ ] Test network scanner sync to user account

## 8.2 IMPORTANT (Should Do)

- [ ] Complete style audit on all dashboard pages
- [ ] Remove all hardcoded colors from CSS
- [ ] Test camera discovery and listing
- [ ] Implement certificate download page
- [ ] Add proper error pages (404, 500)
- [ ] Set up admin user for CMS access
- [ ] Create default email templates
- [ ] Test password reset flow

## 8.3 NICE TO HAVE (Can Do Later)

- [ ] Port forwarding management UI
- [ ] Automation engine for workflows
- [ ] Business dashboard (db-designer, etc.)
- [ ] PDF invoice generation
- [ ] Mesh network invitation emails
- [ ] Camera streaming integration
- [ ] Two-factor authentication
- [ ] Usage analytics dashboard

---

# 9. BUILD SEQUENCE

## PHASE 1: Fix Critical Issues (Priority: NOW)

### Step 1.1: Fix PayPal Webhook
- [ ] Update /api/billing/webhook.php to handle events
- [ ] Update PayPal webhook URL in dashboard to: `https://vpn.the-truth-publishing.com/api/billing/webhook.php`

### Step 1.2: Convert index.html to Database-Driven
- [ ] Create /public/assets/js/theme-loader.js (if not exists)
- [ ] Add theme-loader.js to all HTML pages
- [ ] Replace hardcoded CSS variables with theme API values
- [ ] Test theme changes reflect immediately

### Step 1.3: Deploy peer_api.py
- [ ] SSH to 66.94.103.91
- [ ] Upload peer_api.py to /root/
- [ ] Install dependencies: `pip install flask`
- [ ] Create systemd service for peer_api
- [ ] Start and enable service
- [ ] Repeat for 144.126.133.253

### Step 1.4: Test VPN Flow
- [ ] Log in as regular user
- [ ] Navigate to Connect page
- [ ] Click connect to US-East
- [ ] Download WireGuard config
- [ ] Import to WireGuard client
- [ ] Verify connection works

### Step 1.5: Test VIP Flow
- [ ] Log in as seige235@yahoo.com
- [ ] Verify "VIP" badge shows
- [ ] Verify US-Central server is visible
- [ ] Verify US-Central is marked as "exclusive"
- [ ] Connect to VIP server
- [ ] Verify works

## PHASE 2: Complete Frontend (Priority: HIGH)

### Step 2.1: Style Audit
- [ ] Audit public/assets/css/main.css
- [ ] Audit public/assets/css/dashboard.css
- [ ] Replace ALL hex colors with CSS variables
- [ ] Verify theme API provides all variables

### Step 2.2: Dashboard Completion
- [ ] Verify all dashboard pages load
- [ ] Verify all dashboard pages call theme API
- [ ] Test each dashboard function
- [ ] Fix any broken API calls

### Step 2.3: Scanner Downloads
- [ ] Create /downloads/scanner/ directory
- [ ] Zip Windows files: truthvault_scanner.py, run_scanner.bat
- [ ] Zip Mac/Linux files: truthvault_scanner.py, run_scanner.sh
- [ ] Create download page with links
- [ ] Test downloads work

## PHASE 3: Complete APIs (Priority: MEDIUM)

### Step 3.1: User Profile API
- [ ] GET /api/users/profile.php - Return user data
- [ ] PUT /api/users/profile.php - Update profile
- [ ] Test from dashboard settings page

### Step 3.2: Camera Streaming
- [ ] Implement /api/cameras/stream.php
- [ ] Return RTSP URLs based on vendor
- [ ] Test with discovered camera

### Step 3.3: Admin Theme Editor
- [ ] Implement PUT /api/admin/themes.php
- [ ] Create admin theme editor UI
- [ ] Test theme changes propagate

## PHASE 4: Testing & Polish (Priority: MEDIUM)

### Step 4.1: End-to-End Testing
- [ ] New user registration
- [ ] Login
- [ ] Payment (sandbox first)
- [ ] VPN connection
- [ ] Scanner sync
- [ ] Camera viewing
- [ ] Settings change
- [ ] Password reset
- [ ] Account cancellation

### Step 4.2: Mobile Responsiveness
- [ ] Test all pages on mobile viewport
- [ ] Fix any responsive issues
- [ ] Verify touch interactions work

### Step 4.3: Error Handling
- [ ] Add proper error messages to all API calls
- [ ] Create 404.html page
- [ ] Create 500.html page
- [ ] Test error scenarios

## PHASE 5: Future Enhancements (Priority: LOW)

- [ ] Certificate Authority system
- [ ] Port forwarding UI
- [ ] Automation engine
- [ ] Business dashboard
- [ ] Advanced analytics
- [ ] Multi-language support

---

# END OF MASTER BLUEPRINT V3

**Next Action:** Start with Phase 1, Step 1.1 - Fix PayPal Webhook

**Files to Create/Modify:**
1. /api/billing/webhook.php (update URL)
2. /public/assets/js/theme-loader.js (ensure exists)
3. index.html (convert to database-driven)
4. server-scripts/peer_api.py (deploy to servers)

