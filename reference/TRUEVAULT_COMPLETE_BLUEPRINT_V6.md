# TRUEVAULT VPN - COMPLETE BUILD BLUEPRINT V6
## Comprehensive Handoff Document for Next Chat Session
**Created:** January 14, 2026, 3:15 AM CST
**Last Updated:** January 14, 2026, 4:20 AM CST
**Status:** CRITICAL - Full Investigation Complete

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
CORRECT Webhook URL: https://vpn.the-truth-publishing.com/api/billing/webhook.php
```

### Contabo Servers (paulhalonen@gmail.com / Asasasas4!)
- **Server 1 (US-East SHARED):** 66.94.103.91 - $6.75/mo
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
TrueVault2026SecretKey
Port: 8080
```

---

## 🖥️ VPN SERVERS - COMPLETE DETAILS

| ID | Name | IP | Location | Type | Port | Public Key |
|----|------|----|----|------|------|------------|
| 1 | TrueVaultNY | 66.94.103.91 | New York | Shared | 51820 | lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s= |
| 2 | TrueVaultSTL | 144.126.133.253 | St. Louis | VIP Only | 51820 | qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM= |
| 3 | TrueVaultTX | 66.241.124.4 | Dallas | Shared | 51820 | dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk= |
| 4 | TrueVaultCAN | 66.241.125.247 | Toronto | Shared | 51820 | O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk= |

### Server Rules:
- **NY (Server 1):** Gaming, Torrents, IP Cameras, Streaming - RECOMMENDED
- **STL (Server 2):** VIP ONLY - seige235@yahoo.com exclusive
- **Dallas (Server 3):** Streaming only - NO gaming/torrents/cameras
- **Toronto (Server 4):** Canadian streaming only - NO gaming/torrents/cameras

---

## 💰 PRICING STRUCTURE

| Plan | Monthly | Features |
|------|---------|----------|
| Basic | $9.99 | 3 devices, 1 camera (NY only) |
| Family | $14.99 | 5 devices, 2 cameras, device swapping |
| Dedicated | $29.99 | Unlimited devices, 12 cameras, own server |
| VIP Upgrade | $9.97 | For existing VIP users upgrading |

---

## 🎯 VIP SYSTEM (SECRET!)

### VIP Emails:
- `paulhalonen@gmail.com` - OWNER (all access)
- `seige235@yahoo.com` - VIP_DEDICATED (Server 2 exclusive)

### How VIP Works:
1. VIP signs up through NORMAL registration
2. System checks `vip_users` table in users.db
3. If VIP: `is_vip=1`, creates 100-year subscription automatically
4. VIP never sees payment page
5. VIP gets assigned dedicated server if configured

### VIP API (/api/helpers/vip.php):
```php
VIPManager::isVIP($email)           // Check if VIP
VIPManager::getVIPDetails($email)   // Get tier, server
VIPManager::getVIPType($email)      // owner/vip_dedicated/vip_basic
VIPManager::canAccessServer()       // Check server access
VIPManager::addVIP()               // Admin: add VIP
VIPManager::removeVIP()            // Admin: remove VIP
VIPManager::seedInitialVIPs()      // Setup initial VIPs
```

---

## 📁 DATABASE STRUCTURE

### Production Path:
`/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/`

### Database Files:
```
core/
  users.db        - User accounts
  sessions.db     - Login sessions
  admin.db        - Admin users

vpn/
  vpn.db          - VPN connections, peers
  servers.db      - Server definitions
  certificates.db - User certificates
  identities.db   - Regional identities

devices/
  devices.db      - User devices
  cameras.db      - IP cameras
  port_forwarding.db - Port forwarding rules
  mesh.db         - Mesh network

billing/
  billing.db      - Subscriptions, payments
  invoices.db     - Invoices

cms/
  cms.db          - Pages, content
  themes.db       - Theme settings

automation/
  automation.db   - Workflows
  logs.db         - System logs

analytics/
  analytics.db    - Usage analytics
```

---

## 📊 API ENDPOINTS - COMPLETE LIST

### Authentication (/api/auth/)
```
POST /api/auth/register.php     - Create account (VIP auto-detection)
POST /api/auth/login.php        - Login, get JWT
POST /api/auth/logout.php       - Logout
POST /api/auth/refresh.php      - Refresh JWT token
POST /api/auth/forgot-password.php - Request password reset
POST /api/auth/reset-password.php  - Reset with token
GET  /api/auth/verify-email.php    - Verify email
POST /api/auth/admin-login.php     - Admin login
```

### Billing (/api/billing/) - COMPLETE BUT NEEDS FRONTEND
```
POST /api/billing/checkout.php   - Create PayPal order
POST /api/billing/complete.php   - Capture payment
POST /api/billing/webhook.php    - PayPal webhooks
POST /api/billing/cancel.php     - Cancel subscription
GET  /api/billing/history.php    - Payment history
GET  /api/billing/subscription.php - Current subscription
GET  /api/billing/cron.php       - Process revocations
```

### VPN (/api/vpn/)
```
GET  /api/vpn/servers.php        - List servers (VIP filtering)
POST /api/vpn/connect.php        - Get connection config
POST /api/vpn/disconnect.php     - Record disconnect
GET  /api/vpn/status.php         - Connection status
```

### Users (/api/users/)
```
GET  /api/users/billing.php      - Billing overview
GET  /api/users/settings.php     - User settings
PUT  /api/users/settings.php     - Update settings
GET  /api/users/sessions.php     - Active sessions
DELETE /api/users/sessions.php   - Revoke session
GET  /api/users/export.php       - GDPR data export
GET  /api/users/profile.php      - User profile
DELETE /api/users/delete.php     - Delete account
```

### Devices (/api/devices/)
```
GET  /api/devices/list.php       - List devices
POST /api/devices/index.php      - Add device
PUT  /api/devices/manage.php     - Update device
DELETE /api/devices/manage.php   - Remove device
```

### Cameras (/api/cameras/)
```
GET  /api/cameras/index.php      - List cameras
POST /api/cameras/add.php        - Add camera
POST /api/cameras/register.php   - Register camera
PUT  /api/cameras/manage.php     - Update camera
DELETE /api/cameras/manage.php   - Remove camera
POST /api/cameras/sync.php       - Scanner sync
```

### Certificates (/api/certificates/)
```
GET  /api/certificates/index.php  - List certificates
POST /api/certificates/generate.php - Generate cert
POST /api/certificates/revoke.php   - Revoke cert
GET  /api/certificates/ca.php       - Get CA cert
GET  /api/certificates/backup.php   - Backup all certs
GET  /api/certificates/download.php - Download cert file
```

### Identities (/api/identities/)
```
GET  /api/identities/index.php    - List identities
POST /api/identities/index.php    - Create identity
PUT  /api/identities/index.php    - Update identity
DELETE /api/identities/index.php  - Delete identity
```

### Mesh Network (/api/mesh/)
```
GET  /api/mesh/index.php          - Network overview
POST /api/mesh/index.php          - Create network
POST /api/mesh/invite.php         - Invite member
GET  /api/mesh/members.php        - List members
DELETE /api/mesh/members.php      - Remove member
```

### Plans (/api/plans/)
```
GET  /api/plans/index.php         - List plans (public)
```

### Theme (/api/theme/)
```
GET  /api/theme/index.php         - Get active theme
```

### Scanner (/api/scanner/)
```
GET  /api/scanner/token.php       - Get auth token
POST /api/scanner/sync.php        - Sync devices
```

### Port Forwarding (/api/port-forwarding/) - EMPTY!
```
NEEDS: index.php, create.php, delete.php, update.php
```

---

## ✅ WHAT'S WORKING

1. ✅ Authentication (register, login, JWT)
2. ✅ VIP detection and auto-subscription
3. ✅ VPN servers list with access control
4. ✅ Plans API (database-driven)
5. ✅ Theme API (database-driven)
6. ✅ User management
7. ✅ Device management
8. ✅ Camera management (basic)
9. ✅ Certificate management
10. ✅ Identity management
11. ✅ Mesh network management
12. ✅ Billing API backend (PayPal code complete)
13. ✅ Webhook handler (PayPal events)
14. ✅ Dashboard pages (11 pages)
15. ✅ Admin pages (13 pages)
16. ✅ Frontend JS API client

---

## ❌ WHAT'S MISSING

1. **PayPal Frontend:** billing.html needs PayPal JS SDK buttons
2. **PayPal Webhook URL:** Currently points to wrong subdomain
3. **Port Forwarding API:** Entire folder empty
4. **Camera Controls:** No floodlight/motion/PTZ/RTSP APIs
5. **VPN Server Deployment:** peer_api.py may not be running
6. **Database Seeding:** Tables may need initial data

---

## 🔧 NEXT STEPS CHECKLIST

### PRIORITY 1: PayPal Integration
- [ ] Update webhook URL in PayPal dashboard
- [ ] Add PayPal JS SDK to billing.html
- [ ] Test checkout flow end-to-end
- [ ] Test VIP bypass (seige235@yahoo.com)

### PRIORITY 2: Port Forwarding
- [ ] Create /api/port-forwarding/index.php
- [ ] Create /api/port-forwarding/create.php
- [ ] Create /api/port-forwarding/delete.php
- [ ] Add port forwarding table to vpn.db
- [ ] Connect to peer_api.py on servers

### PRIORITY 3: VPN Server Verification
- [ ] SSH to all 4 servers
- [ ] Verify WireGuard installed
- [ ] Deploy and start peer_api.py
- [ ] Test peer add/remove

### PRIORITY 4: Database Seeding
- [ ] Seed subscription_plans table
- [ ] Seed vpn_servers table
- [ ] Seed vip_users table
- [ ] Seed themes table

---

## 📞 OWNER INFO

- **Email:** paulhalonen@gmail.com
- **Visual Impairment:** Needs Claude to do all editing
- **Goal:** $6M revenue in 1 year, 1 person operation
- **Brand:** TrueVault VPN (trademark)

---

## 📝 CHAT LOG

Always append to: `E:\Documents\GitHub\truevault-vpn\chat_log.txt`

Format:
```
=== [DATE TIME] ===
[Work completed]
=== END ===
```

---

**END OF BLUEPRINT V6**
