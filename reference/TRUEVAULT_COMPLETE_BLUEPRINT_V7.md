# TRUEVAULT VPN - COMPLETE BUILD BLUEPRINT V7
## Comprehensive Handoff Document for Next Chat Session
**Created:** January 14, 2026, 3:15 AM CST
**Last Updated:** January 14, 2026, 5:00 AM CST
**Status:** CRITICAL - SERVER DEPLOYMENT ISSUES FOUND

---

## 🚨 CRITICAL DEPLOYMENT ISSUES

### BILLING FOLDER NOT ON SERVER!
The `/api/billing/` folder exists locally but is **NOT DEPLOYED** to the live server!
This means users CANNOT PAY - the entire payment system is offline!

### API Folders ON Server:
✅ /api/auth/
✅ /api/cameras/
✅ /api/certificates/
✅ /api/config/
✅ /api/debug/
✅ /api/devices/
✅ /api/helpers/
✅ /api/identities/
✅ /api/mesh/
✅ /api/plans/
✅ /api/scanner/
✅ /api/theme/
✅ /api/users/
✅ /api/vip/
✅ /api/vpn/
✅ /api/admin/

### API Folders MISSING from Server:
❌ /api/billing/ - NOT DEPLOYED!
❌ /api/port-forwarding/ - NOT DEPLOYED!
❌ /api/payments/ - NOT DEPLOYED!
❌ /api/cron/ - NOT DEPLOYED!
❌ /api/automation/ - NOT DEPLOYED!
❌ /api/servers/ - NOT DEPLOYED!

### Database Path MISMATCH!
- **Code expects:** /databases/category/name.db (organized structure)
- **Server has:** /data/name.db (flat structure)

This WILL cause database connection errors!

---

## ⚠️ CRITICAL NOTES FOR NEXT CHAT

1. **VIP SYSTEM IS SECRET** - Never advertise, no special login. VIPs sign up through normal 7-day trial and bypass payment automatically.
2. **Database-driven EVERYTHING** - No hardcoded styles, colors, or content
3. **SQLite databases** - Properly compartmentalized, NOT clumped
4. **Portable** - System must be movable to new server after launch
5. **100% Automated** - Goal: $6M/year with 1 person operating
6. **ONLY vpn subdomain** - Do NOT touch builder or other subdomains

---

## 🔐 ALL CREDENTIALS

### FTP Access
```
Host: the-truth-publishing.com
User: kahlen@the-truth-publishing.com
Pass: AndassiAthena8
Port: 21
Path: /public_html/vpn.the-truth-publishing.com
```

### GoDaddy cPanel
```
Username: 26853687
Password: Asasasas4!
```

### PayPal API (LIVE)
```
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
Business Email: paulhalonen@gmail.com
Webhook ID: 46924926WL757580D
WRONG Webhook URL: https://builder.the-truth-publishing.com/api/paypal-webhook.php
CORRECT Webhook URL: https://vpn.the-truth-publishing.com/api/billing/webhook.php
```

### Contabo Servers (paulhalonen@gmail.com / Asasasas4!)
- **Server 1 (US-East SHARED):** 66.94.103.91 - $6.75/mo - root access
- **Server 2 (US-Central VIP DEDICATED):** 144.126.133.253 - ONLY for seige235@yahoo.com - $6.15/mo

### Fly.io Servers (paulhalonen@gmail.com / Asasasas4!)
- **Server 3 (Dallas SHARED):** 66.241.124.4
- **Server 4 (Toronto SHARED):** 66.241.125.247

### JWT Configuration
```php
Secret: TrueVault2026JWTSecretKey!@#$
Expiry: 7 days (604800 seconds)
```

### Peer API Secret
```
TRUEVAULT_API_SECRET=TrueVault2026SecretKey
PEER_API_PORT=8080
```

---

## 🖥️ VPN SERVERS - COMPLETE DETAILS

| ID | Name | IP | Location | Type | Port | Public Key |
|----|------|----|----|------|------|------------|
| 1 | TrueVaultNY | 66.94.103.91 | New York | Shared | 51820 | lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s= |
| 2 | TrueVaultSTL | 144.126.133.253 | St. Louis | VIP Only | 51820 | qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM= |
| 3 | TrueVaultTX | 66.241.124.4 | Dallas | Shared | 51820 | dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk= |
| 4 | TrueVaultCAN | 66.241.125.247 | Toronto | Shared | 51820 | O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk= |

### Server Rules (from /api/vpn/servers.php):
- **NY (Server 1):** Gaming ✓, Torrents ✓, IP Cameras ✓, Streaming ✓ - RECOMMENDED
- **STL (Server 2):** VIP ONLY for seige235@yahoo.com - Unlimited, No restrictions
- **Dallas (Server 3):** Streaming only - NO gaming/torrents/cameras (Netflix unblocked)
- **Toronto (Server 4):** Canadian streaming only - NO gaming/torrents/cameras (Netflix unblocked)

### Server Network Ranges (from code):
- Server 1: 10.0.0.x
- Server 2: 10.0.1.x
- Server 3: 10.10.1.x
- Server 4: 10.10.0.x

---

## 💰 PRICING STRUCTURE

| Plan | Monthly | Devices | Cameras | Features |
|------|---------|---------|---------|----------|
| Basic | $9.99 | 3 | 1 | NY server only |
| Family | $14.99 | 5 | 2 | Device swapping |
| Dedicated | $29.99 | Unlimited | 12 | Own server, port forwarding |
| VIP Upgrade | $9.97 | Unlimited | 12 | For existing VIPs |

---

## 🎯 VIP SYSTEM (SECRET - NEVER ADVERTISE!)

### VIP Emails:
- `paulhalonen@gmail.com` - OWNER tier (all access, all servers)
- `seige235@yahoo.com` - VIP_DEDICATED tier (Server 2 exclusive)

### How VIP Works:
1. VIP signs up through NORMAL registration (no special URL)
2. /api/auth/register.php checks VIPManager::isVIP($email)
3. If VIP: sets is_vip=1, creates 100-year subscription automatically
4. VIP never sees payment page
5. VIP_DEDICATED gets exclusive server access

### VIP Code Location: /api/helpers/vip.php
```php
class VIPManager {
    public static function isVIP($email)           // Returns true/false
    public static function getVIPDetails($email)   // Returns tier, server, features
    public static function getVIPType($email)      // Returns owner/vip_dedicated/vip_basic
    public static function canAccessServer($email, $serverId)  // Check server access
    public static function addVIP($email, $tier)   // Admin function
    public static function removeVIP($email)       // Admin function
    public static function seedInitialVIPs()       // Creates initial VIPs
    public static function getVIPPlan($email)      // Returns plan type for VIP
}
```

### VIP Database Table (in users.db):
```sql
CREATE TABLE vip_users (
    id INTEGER PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    tier TEXT NOT NULL,  -- owner, vip_dedicated, vip_basic
    dedicated_server_id INTEGER,
    features TEXT,  -- JSON
    created_at DATETIME,
    created_by TEXT
);
```

---

## 📁 DATABASE STRUCTURE

### Server Database Path (ACTUAL):
`/public_html/vpn.the-truth-publishing.com/data/`

### Databases ON Server (25 total):
```
admin_users.db    - Admin accounts
analytics.db      - Usage analytics
automation.db     - Workflow automation
bandwidth.db      - Bandwidth tracking
cameras.db        - IP cameras
certificates.db   - User certificates
devices.db        - User devices
emails.db         - Email templates
identities.db     - Regional identities
logs.db           - System logs
media.db          - Media files
mesh.db           - Mesh network
notifications.db  - User notifications
pages.db          - CMS pages
payments.db       - Payment records
plans.db          - Subscription plans
servers.db        - VPN servers
settings.db       - System settings
subscriptions.db  - User subscriptions
support.db        - Support tickets
themes.db         - UI themes
users.db          - User accounts
vip.db            - VIP users
vpn.db            - VPN connections
```

### Code Database Config (/api/config/database.php):
The code expects organized structure but server has flat structure!
```php
// Code expects:
'users' => 'core/users.db'
'billing' => 'billing/billing.db'

// Server has:
/data/users.db
/data/payments.db  // (not billing.db)
```

---

## 📊 API ENDPOINTS - COMPLETE LIST

### Authentication (/api/auth/) - DEPLOYED ✅
```
POST /api/auth/register.php     - Create account + VIP auto-detect
POST /api/auth/login.php        - Login, returns JWT
POST /api/auth/logout.php       - Logout
POST /api/auth/refresh.php      - Refresh JWT
POST /api/auth/forgot-password.php
POST /api/auth/reset-password.php
GET  /api/auth/verify-email.php
POST /api/auth/admin-login.php
```

### Billing (/api/billing/) - NOT DEPLOYED ❌
```
POST /api/billing/checkout.php   - Create PayPal order
POST /api/billing/complete.php   - Capture payment
POST /api/billing/webhook.php    - PayPal webhooks
POST /api/billing/cancel.php     - Cancel subscription
GET  /api/billing/history.php    - Payment history
GET  /api/billing/subscription.php
GET  /api/billing/cron.php
```

### VPN (/api/vpn/) - DEPLOYED ✅
```
GET  /api/vpn/servers.php        - List servers (tested, works)
POST /api/vpn/connect.php        - Get WireGuard config
POST /api/vpn/disconnect.php
GET  /api/vpn/status.php
```

### Users (/api/users/) - DEPLOYED ✅
```
GET  /api/users/billing.php
GET  /api/users/settings.php
PUT  /api/users/settings.php
GET  /api/users/sessions.php
DELETE /api/users/sessions.php
GET  /api/users/export.php
GET  /api/users/profile.php
DELETE /api/users/delete.php
```

### Devices (/api/devices/) - DEPLOYED ✅
```
GET  /api/devices/list.php       - Tested, returns auth required
POST /api/devices/index.php
PUT  /api/devices/manage.php
DELETE /api/devices/manage.php
```

### Cameras (/api/cameras/) - DEPLOYED ✅
```
GET  /api/cameras/index.php      - Tested, returns auth required
POST /api/cameras/add.php
POST /api/cameras/register.php
PUT  /api/cameras/manage.php
DELETE /api/cameras/manage.php
POST /api/cameras/sync.php
```

### Certificates (/api/certificates/) - DEPLOYED ✅
```
GET  /api/certificates/index.php  - Tested, returns auth required
POST /api/certificates/generate.php
POST /api/certificates/revoke.php
GET  /api/certificates/ca.php
GET  /api/certificates/backup.php
GET  /api/certificates/download.php
```

### Identities (/api/identities/) - DEPLOYED ✅
```
GET  /api/identities/index.php
POST /api/identities/index.php
PUT  /api/identities/index.php
DELETE /api/identities/index.php
```

### Mesh (/api/mesh/) - DEPLOYED ✅
```
GET  /api/mesh/index.php
POST /api/mesh/index.php
POST /api/mesh/invite.php
GET  /api/mesh/members.php
DELETE /api/mesh/members.php
```

### Plans (/api/plans/) - DEPLOYED ✅
```
GET  /api/plans/index.php        - Public, no auth needed
```

### Theme (/api/theme/) - DEPLOYED ✅
```
GET  /api/theme/index.php        - Tested, works, returns theme variables
```

### Scanner (/api/scanner/) - DEPLOYED ✅
```
GET  /api/scanner/token.php
POST /api/scanner/sync.php
```

### Port Forwarding (/api/port-forwarding/) - NOT DEPLOYED ❌
```
ENTIRE FOLDER EMPTY - NEEDS CREATION
```

---

## 🔧 IMMEDIATE ACTION REQUIRED (Priority Order)

### PRIORITY 1: Deploy Billing Folder
Upload these files from local to server via FTP:
```
FROM: E:\Documents\GitHub\truevault-vpn\api\billing\
TO:   /public_html/vpn.the-truth-publishing.com/api/billing/

Files to upload:
- billing-manager.php
- checkout.php
- complete.php
- webhook.php
- cancel.php
- history.php
- subscription.php
- cron.php
- index.php
- setup-billing.php
```

### PRIORITY 2: Fix Database Paths
Either:
A) Update /api/config/database.php to use flat /data/ structure
OR
B) Reorganize server databases into folders

### PRIORITY 3: Update PayPal Webhook
1. Log into PayPal Developer Dashboard
2. Go to Webhooks
3. Change URL from builder subdomain to:
   `https://vpn.the-truth-publishing.com/api/billing/webhook.php`

### PRIORITY 4: Create Port Forwarding API
Create files in /api/port-forwarding/:
- index.php (list forwards)
- create.php (add forward)
- delete.php (remove forward)
- update.php (modify forward)

### PRIORITY 5: Test VIP Flow
1. Register with seige235@yahoo.com
2. Verify auto-subscription creation
3. Verify Server 2 access
4. Verify no payment required

---

## 📁 LOCAL FILE STRUCTURE

```
E:\Documents\GitHub\truevault-vpn\
├── api/
│   ├── admin/
│   ├── auth/
│   ├── automation/
│   ├── billing/          <- NEEDS DEPLOYMENT!
│   ├── cameras/
│   ├── certificates/
│   ├── config/
│   ├── cron/
│   ├── debug/
│   ├── devices/
│   ├── helpers/
│   ├── identities/
│   ├── mesh/
│   ├── payments/         <- EMPTY
│   ├── plans/
│   ├── port-forwarding/  <- EMPTY, NEEDS CREATION
│   ├── scanner/
│   ├── servers/
│   ├── theme/
│   ├── user/
│   ├── users/
│   ├── vip/
│   └── vpn/
├── public/
│   ├── admin/           (13 pages)
│   ├── assets/
│   ├── dashboard/       (11 pages)
│   └── downloads/
├── server-scripts/
│   └── peer_api.py
├── reference/
│   ├── TRUEVAULT_COMPLETE_BLUEPRINT_V7.md
│   └── NEXT_CHAT_READ_FIRST.md
└── chat_log.txt
```

---

## 🌐 FRONTEND PAGES

### Dashboard Pages (/public/dashboard/):
1. index.html - Overview
2. connect.html - VPN connection
3. servers.html - Server list
4. identities.html - Regional identities
5. certificates.html - Certificate management
6. devices.html - Device management
7. cameras.html - Camera management
8. mesh.html - Mesh network
9. scanner.html - Network scanner
10. settings.html - User settings
11. billing.html - Billing & payments

### Admin Pages (/public/admin/):
1. index.html - Admin dashboard
2. users.html - User management
3. servers.html - Server management
4. plans.html - Plan management
5. billing.html - Billing overview
6. cameras.html - Camera overview
7. support.html - Support tickets
8. settings.html - System settings
9. themes.html - Theme editor
10. vip.html - VIP management
11. analytics.html - Analytics
12. logs.html - System logs
13. automation.html - Automation workflows

---

## 🔑 KEY CODE FILES

### /api/config/database.php
- PDO connection class
- Database path definitions
- Helper functions: query(), queryOne(), execute()

### /api/helpers/auth.php
- JWT generation and validation
- Token from header extraction
- requireAuth(), requireAdmin(), optionalAuth()
- Password hashing

### /api/helpers/vip.php
- VIP detection and management
- Tier definitions
- Server access control

### /api/helpers/response.php
- JSON response formatting
- Error handling
- HTTP status codes

### /api/billing/billing-manager.php
- PayPal API integration
- Subscription management
- Payment capture
- Webhook handling

### /public/assets/js/app.js
- Frontend API client
- Token management
- User session handling
- Toast notifications

---

## 📞 OWNER INFO

- **Name:** Kah-Len (paulhalonen@gmail.com)
- **Visual Impairment:** Needs Claude to do all editing
- **Goal:** $6M revenue in 1 year, 1 person operation
- **Brand:** TrueVault VPN (trademark)

---

## 📝 ALWAYS APPEND TO CHAT LOG

File: `E:\Documents\GitHub\truevault-vpn\chat_log.txt`

Format:
```
=== [DATE TIME] ===
[Work completed]
=== END ===
```

---

**END OF BLUEPRINT V7**
