# TRUEVAULT VPN - COMPLETE HANDOFF DOCUMENT
## FOR NEW CHAT SESSION CONTINUATION
**Created:** January 13, 2026 - 11:00 PM CST
**Purpose:** Complete context transfer for new chat session

---

# ⚠️ CRITICAL RULES - READ FIRST

## RULE 1: NEVER OVERWRITE - ONLY APPEND
- chat_log.txt: APPEND only with timestamps
- Checklist: Mark items [x] complete, never delete
- Code files: Edit existing, don't recreate from scratch
- Database: Add records, don't drop tables

## RULE 2: NO PLACEHOLDERS - REAL CODE ONLY
- Every function must actually work
- Every API must connect to real database
- Every button must have real click handler
- NO "TODO", "Coming soon", "Implement later"

## RULE 3: DATABASE-DRIVEN STYLING
- ALL colors: var(--colors-primary) from themes.db
- ALL fonts: var(--typography-font-family) from themes.db
- NO hardcoded hex colors (#ffffff)
- NO hardcoded font-family declarations

## RULE 4: CHECK BEFORE BUILDING
- Read TRUEVAULT_MASTER_CHECKLIST_V2.md first
- Check what's already done
- Mark items complete as you finish them
- APPEND progress to chat_log.txt

---

# 🔐 ALL CREDENTIALS

## Web Hosting (GoDaddy cPanel)
```
URL: https://the-truth-publishing.com:2083
Username: 26853687
Password: Asasasas4!
```

## FTP Access
```
Host: the-truth-publishing.com
Username: kahlen@the-truth-publishing.com
Password: AndassiAthena8
Port: 21
Remote Path: /public_html/vpn.the-truth-publishing.com
```

## PayPal API (LIVE)
```
App Name: MyApp_ConnectionPoint_Systems_Inc
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret Key: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
Business Email: paulhalonen@gmail.com
Webhook URL: https://builder.the-truth-publishing.com/api/paypal-webhook.php
Webhook ID: 46924926WL757580D
```

## Contabo VPN Servers
```
Server 1 (US-East, SHARED):
  IP: 66.94.103.91
  IPv6: 2605:a142:2299:0026:0000:0000:0000:0001
  VNC: 154.53.39.97:63031
  SSH: root@66.94.103.91
  WireGuard Port: 51820
  API Port: 8080
  Public Key: [Run: cat /etc/wireguard/publickey on server]
  Status: Shared - bandwidth constrained

Server 2 (US-Central, VIP DEDICATED):
  IP: 144.126.133.253
  IPv6: 2605:a140:2299:0005:0000:0000:0000:0001
  VNC: 207.244.248.38:63098
  SSH: root@144.126.133.253
  WireGuard Port: 51820
  API Port: 8080
  Public Key: [Run: cat /etc/wireguard/publickey on server]
  Status: DEDICATED to seige235@yahoo.com ONLY
```

## Fly.io VPN Servers
```
Server 3 (Dallas, SHARED):
  IP: 66.241.124.4 (Shared IPv4)
  Release IP: 137.66.58.225
  WireGuard Port: 51820
  API Port: 8443
  Status: Shared - bandwidth constrained

Server 4 (Toronto, SHARED):
  IP: 66.241.125.247 (Shared IPv4)
  Release IP: 37.16.6.139
  WireGuard Port: 51820
  API Port: 8080
  Status: Shared - bandwidth constrained
```

## Contabo/Fly.io Account Login
```
Email: paulhalonen@gmail.com
Password: Asasasas4!
```

## VIP Users (CRITICAL)
```
Email: seige235@yahoo.com
Dedicated Server: 144.126.133.253
Tier: vip_dedicated
Privileges:
  - Bypass ALL payment requirements
  - Unlimited devices
  - Unlimited cameras
  - Dedicated server (not shared)
  - Priority support
```

---

# 📁 FILE LOCATIONS

## Local Development
```
Repository: E:\Documents\GitHub\truevault-vpn
Reference Docs: E:\Documents\GitHub\truevault-vpn\reference\
API Code: E:\Documents\GitHub\truevault-vpn\api\
Frontend: E:\Documents\GitHub\truevault-vpn\public\
Server Scripts: E:\Documents\GitHub\truevault-vpn\server-scripts\
```

## Production Server
```
Root: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com
API: /public_html/vpn.the-truth-publishing.com/api/
Public: /public_html/vpn.the-truth-publishing.com/public/
Databases: /public_html/vpn.the-truth-publishing.com/databases/
```

## Key Files
```
Checklist: E:\Documents\GitHub\truevault-vpn\reference\TRUEVAULT_MASTER_CHECKLIST_V2.md
Blueprint: E:\Documents\GitHub\truevault-vpn\reference\TRUEVAULT_MASTER_BLUEPRINT_V2.md
Chat Log: E:\Documents\GitHub\truevault-vpn\reference\chat_log.txt
This Doc: E:\Documents\GitHub\truevault-vpn\reference\TRUEVAULT_HANDOFF_DOCUMENT.md
```

---

# 🏗️ SYSTEM ARCHITECTURE

## Three Dashboards
```
1. CLIENT DASHBOARD: /public/dashboard/
   - VPN connection, devices, cameras, mesh, certificates
   
2. ADMIN DASHBOARD: /public/admin/
   - User management, server management, themes, logs
   
3. BUSINESS DASHBOARD: /business/ (not yet built)
   - FileMaker-style DB creator, GrapesJS page builder, accounting
```

## Database Structure (SQLite - SEPARATE FILES)
```
/databases/
├── core/
│   ├── users.db        # User accounts
│   ├── sessions.db     # Active sessions
│   └── admin.db        # Admin users
├── vpn/
│   ├── servers.db      # VPN servers config
│   ├── connections.db  # Active connections
│   ├── certificates.db # User certificates
│   └── identities.db   # Regional identities
├── devices/
│   ├── discovered.db   # Discovered devices
│   ├── cameras.db      # Camera settings
│   └── mesh.db         # Mesh networks
├── billing/
│   ├── subscriptions.db
│   ├── invoices.db
│   └── payments.db
└── cms/
    ├── themes.db       # CRITICAL: All styling here
    ├── pages.db        # CMS pages
    └── templates.db    # Email templates
```

## API Structure
```
/api/
├── config/
│   ├── database.php    # Database connections
│   ├── jwt.php         # JWT token management
│   ├── constants.php   # Site constants
│   └── setup-databases.php  # DB initialization
├── helpers/
│   ├── response.php    # JSON responses
│   ├── auth.php        # Authentication
│   ├── vip.php         # VIP management (CRITICAL)
│   ├── validator.php   # Input validation
│   └── encryption.php  # Encryption utilities
├── auth/               # Login, register, etc.
├── users/              # User profile, settings
├── vpn/                # Servers, connect, config
├── billing/            # Subscription, checkout
├── devices/            # Device management
├── cameras/            # Camera control
├── mesh/               # Mesh networking
├── certificates/       # Certificate generation
├── admin/              # Admin functions
└── scanner/            # Network scanner sync
```

---

# ✅ WHAT'S ALREADY BUILT

## Phase 1: Foundation - COMPLETE ✅
- Directory structure created
- Git repository setup
- Reference documentation

## Phase 2: Databases - COMPLETE ✅
- setup-databases.php working
- All schemas defined
- themes.db with default theme
- VIP database with seige235@yahoo.com

## Phase 3: API Core - COMPLETE ✅
- database.php - Database class
- jwt.php - JWTManager class
- response.php - Response class
- auth.php - Auth helper
- vip.php - VIPManager, PlanLimits, ServerRules

## Phase 4: Auth API - COMPLETE ✅
- login.php (FIXED Jan 13 - data.data.token issue)
- register.php
- logout.php
- refresh.php
- verify-email.php
- forgot-password.php
- reset-password.php

## Phase 6: VPN API - COMPLETE ✅
- servers.php
- connect.php
- config.php
- status.php
- provisioner.php

## Phase 11: Billing API - COMPLETE ✅
- subscription.php
- checkout.php
- complete.php
- webhook.php
- cron.php

## Phase 15: Frontend HTML - PAGES EXIST ✅
- 27 HTML pages created
- Theme loader working
- NEEDS: Style audit for hardcoded values

---

# ❌ WHAT NEEDS TO BE BUILT

## Phase 5: User API
- [ ] profile.php - GET/PUT user profile
- [ ] settings.php - User preferences
- [ ] devices.php - User device management
- [ ] change-password.php
- [ ] two-factor.php

## Phase 7: Certificate API
- [ ] generate.php - Create certificates on VPN server
- [ ] list.php - User's certificates
- [ ] download.php - Download certificate files
- [ ] revoke.php - Revoke certificates
- [ ] ca.php - Personal CA management

## Phase 8: Device & Camera API
- [ ] devices/list.php - NEEDS WORK
- [ ] devices/sync.php - From scanner
- [ ] cameras/stream.php - Get RTSP URL
- [ ] cameras/control.php - Floodlight, motion, PTZ
- [ ] cameras/snapshot.php
- [ ] cameras/events.php

## Phase 9: Port Forwarding API
- [ ] rules.php - CRUD for port forwarding
- [ ] toggle.php - Enable/disable rules

## Phase 10: Mesh Network API
- [ ] mesh/index.php - EXISTS but needs testing
- [ ] mesh/invite.php
- [ ] mesh/join.php
- [ ] mesh/members.php

## Phase 12: Admin API
- [ ] admin/users.php - PARTIAL
- [ ] admin/servers.php - PARTIAL
- [ ] admin/themes.php - Edit themes in DB
- [ ] admin/stats.php
- [ ] admin/logs.php

## Phase 14: Automation Engine
- [ ] automation/engine.php
- [ ] automation/workflows.php
- [ ] automation/cron.php
- [ ] All workflow implementations

## Phase 16-17: Business Dashboard
- [ ] FileMaker-style database creator
- [ ] GrapesJS page builder
- [ ] Accounting system

## CRITICAL AUDITS NEEDED
- [ ] Remove ALL hardcoded colors from HTML/CSS
- [ ] Remove ALL hardcoded fonts from HTML/CSS
- [ ] Replace ALL placeholders with real code
- [ ] Test ALL API endpoints work with database

---

# 🔄 AUTOMATION WORKFLOWS

## Workflow 1: New User Signup
```
Trigger: User registers
Steps:
1. Create user in users.db
2. Send welcome email (use template from templates.db)
3. Create default regional identities
4. Generate scanner auth token
5. Schedule 24-hour follow-up email
```

## Workflow 2: VPN Connection
```
Trigger: User clicks Connect
Steps:
1. Check subscription status (VIP bypasses)
2. Check device limit (VIP unlimited)
3. Select server (VIP gets dedicated 144.126.133.253)
4. Call server peer_api.py to add WireGuard peer
5. Generate client config
6. Record connection in connections.db
7. Start bandwidth tracking
```

## Workflow 3: Payment Success
```
Trigger: PayPal webhook payment.completed
Steps:
1. Verify webhook signature
2. Update subscription status to 'active'
3. Generate invoice
4. Send receipt email
5. Log transaction
```

## Workflow 4: Payment Failed
```
Trigger: PayPal webhook payment.failed
Steps:
1. Day 0: Set status to 'grace_period', send reminder
2. Day 3: Send urgent notice
3. Day 7: Send final warning
4. Day 8: Suspend service (VIP users exempt)
```

## Workflow 5: Scanner Sync
```
Trigger: Scanner POSTs discovered devices
Steps:
1. Validate scanner token
2. Process each device
3. Detect cameras by MAC vendor
4. Store in discovered.db / cameras.db
5. Return sync summary
```

---

# 🛠️ HOW TO CONTINUE DEVELOPMENT

## Step 1: Read This Document
You're doing this now. Good.

## Step 2: Read the Checklist
```
File: E:\Documents\GitHub\truevault-vpn\reference\TRUEVAULT_MASTER_CHECKLIST_V2.md
```
Find the first unchecked item and work on it.

## Step 3: Work on ONE Item at a Time
- Build the feature completely
- Test it works
- Mark it [x] complete in checklist
- APPEND to chat_log.txt what you did

## Step 4: Upload to Production
```powershell
# FTP upload command
curl -u kahlen@the-truth-publishing.com:AndassiAthena8 -T "localfile.php" ftp://the-truth-publishing.com/public_html/vpn.the-truth-publishing.com/api/path/file.php
```

## Step 5: Test on Production
```
URL: https://vpn.the-truth-publishing.com/
```

---

# 📝 CHAT LOG FORMAT

When appending to chat_log.txt, use this format:

```
---

## SESSION: [Date]

### Start Time: [Time] CST

**Focus:** [What you're working on]

### Work Completed:

#### [Time] - [Task Name]
- What was done
- Files changed
- Issues found/fixed

### End Time: [Time] CST

---
```

---

# 🚨 COMMON ISSUES & FIXES

## Issue: Login fails with "Login failed"
**Fix:** Frontend expects data.data.token, not data.token
**File:** public/assets/js/app.js - Auth.login function
**Status:** FIXED Jan 13, 2026

## Issue: Hardcoded colors in HTML
**Fix:** Replace with CSS variables from themes.db
**Example:** 
- Wrong: `color: #00d9ff`
- Right: `color: var(--colors-primary)`

## Issue: VIP user can't access dedicated server
**Fix:** Check vip.php ServerRules::getAvailableServers()
**Database:** databases/core/vip.db

## Issue: Database connection fails
**Fix:** Check Database::getConnection() path
**File:** api/config/database.php

---

# 🎯 IMMEDIATE NEXT STEPS

1. **Run Style Audit** - Find/replace hardcoded colors
2. **Complete User API** - profile.php, settings.php
3. **Complete Certificate API** - Server-side generation
4. **Test All Endpoints** - Verify everything works
5. **Build Automation Engine** - Workflow processing

---

# 📞 SUPPORT CONTACT

**Project Owner:** Kah-Len (paulhalonen@gmail.com)
**VIP User:** seige235@yahoo.com

---

**END OF HANDOFF DOCUMENT**
