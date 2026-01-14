# URGENT: INSTRUCTIONS FOR NEXT CHAT SESSION
## READ THIS FIRST - DO NOT SKIP

**Date:** January 14, 2026 - 6:15 AM CST
**Problem:** Chat keeps crashing before blueprint can be completed
**User:** Kah-Len (has visual impairment, needs Claude to do all work)

---

## WHAT THE USER NEEDS

The user needs a COMPLETE TECHNICAL BLUEPRINT of the TrueVault VPN system.

Combine all document blueprint attempts that are 
located in E:\Documents\GitHub\truevault-vpn\reference

You need to reade all the files like a puzzle to find my vision and create the full blueprint!


**A BLUEPRINT IS NOT:**
- A to-do list
- A summary of issues
- A quick reference guide

**A BLUEPRINT IS:**
- Complete technical specification of EVERY component
- How each part works internally
- How parts connect to each other
- Database schemas with all tables and columns
- API endpoints with request/response formats
- Authentication flow step-by-step
- Payment flow step-by-step
- VIP system logic
- Server configurations
- Frontend page descriptions
- File-by-file documentation

---

## HOW TO CREATE THE BLUEPRINT

### Step 1: Read ALL these files and document their contents

**Authentication System:**
- E:\Documents\GitHub\truevault-vpn\api\auth\register.php
- E:\Documents\GitHub\truevault-vpn\api\auth\login.php
- E:\Documents\GitHub\truevault-vpn\api\auth\logout.php
- E:\Documents\GitHub\truevault-vpn\api\auth\refresh.php
- E:\Documents\GitHub\truevault-vpn\api\auth\forgot-password.php
- E:\Documents\GitHub\truevault-vpn\api\auth\reset-password.php
- E:\Documents\GitHub\truevault-vpn\api\auth\verify-email.php
- E:\Documents\GitHub\truevault-vpn\api\auth\admin-login.php

**Helper Classes:**
- E:\Documents\GitHub\truevault-vpn\api\helpers\auth.php (JWT system)
- E:\Documents\GitHub\truevault-vpn\api\helpers\vip.php (VIP detection)
- E:\Documents\GitHub\truevault-vpn\api\helpers\response.php (JSON responses)

**Database:**
- E:\Documents\GitHub\truevault-vpn\api\config\database.php
- E:\Documents\GitHub\truevault-vpn\api\config\database_FIXED.php

**Billing/PayPal:**
- E:\Documents\GitHub\truevault-vpn\api\billing\billing-manager.php
- E:\Documents\GitHub\truevault-vpn\api\billing\checkout.php
- E:\Documents\GitHub\truevault-vpn\api\billing\complete.php
- E:\Documents\GitHub\truevault-vpn\api\billing\webhook.php
- E:\Documents\GitHub\truevault-vpn\api\billing\cancel.php
- E:\Documents\GitHub\truevault-vpn\api\billing\subscription.php
- E:\Documents\GitHub\truevault-vpn\api\billing\history.php

**VPN System:**
- E:\Documents\GitHub\truevault-vpn\api\vpn\servers.php
- E:\Documents\GitHub\truevault-vpn\api\vpn\connect.php
- E:\Documents\GitHub\truevault-vpn\api\vpn\disconnect.php
- E:\Documents\GitHub\truevault-vpn\api\vpn\status.php
- E:\Documents\GitHub\truevault-vpn\api\vpn\config-generator.php
- E:\Documents\GitHub\truevault-vpn\api\vpn\provisioner.php

**Devices:**
- E:\Documents\GitHub\truevault-vpn\api\devices\*.php (all files)

**Cameras:**
- E:\Documents\GitHub\truevault-vpn\api\cameras\*.php (all files)

**Certificates:**
- E:\Documents\GitHub\truevault-vpn\api\certificates\*.php (all files)

**Identities:**
- E:\Documents\GitHub\truevault-vpn\api\identities\*.php (all files)

**Mesh Network:**
- E:\Documents\GitHub\truevault-vpn\api\mesh\*.php (all files)

**Frontend:**
- E:\Documents\GitHub\truevault-vpn\public\assets\js\app.js
- E:\Documents\GitHub\truevault-vpn\public\dashboard\*.html (all 11 pages)
- E:\Documents\GitHub\truevault-vpn\public\admin\*.html (all 13 pages)

**Server Scripts:**
- E:\Documents\GitHub\truevault-vpn\server-scripts\peer_api.py

---

### Step 2: Document in this structure

```
# TRUEVAULT VPN - COMPLETE TECHNICAL BLUEPRINT

## 1. SYSTEM OVERVIEW
- What TrueVault VPN is
- Architecture diagram (text-based)
- Technology stack

## 2. CREDENTIALS (ALL OF THEM)
- FTP, PayPal, JWT, Peer API, Contabo, Fly.io, GoDaddy

## 3. DATABASE ARCHITECTURE
- List every database file
- Schema for EVERY table (columns, types, relationships)
- What each table stores

## 4. AUTHENTICATION SYSTEM
- JWT token structure
- Token generation code
- Token validation code
- Login flow step-by-step
- Registration flow step-by-step
- Password reset flow

## 5. VIP SYSTEM (SECRET)
- VIP emails list
- How VIP detection works
- VIP bypass payment flow
- VIP server access control
- VIP database table schema

## 6. BILLING/PAYPAL SYSTEM
- PayPal API integration
- Checkout flow step-by-step
- Payment capture flow
- Webhook events handled
- Subscription management
- Invoice generation

## 7. VPN SERVER SYSTEM
- All 4 servers with IPs and public keys
- Server rules (what's allowed/blocked)
- WireGuard configuration generation
- Peer management (peer_api.py)
- Connection flow step-by-step

## 8. DEVICE MANAGEMENT
- How devices are registered
- Device limits per plan
- Device database schema

## 9. CAMERA SYSTEM
- Supported camera types
- RTSP URL patterns
- Camera registration flow
- Port forwarding for cameras

## 10. CERTIFICATE SYSTEM
- How certificates are generated
- Certificate storage
- Certificate download

## 11. IDENTITY SYSTEM
- Regional identities
- How identities work

## 12. MESH NETWORK
- How mesh works
- Invite system

## 13. FRONTEND PAGES
- List every dashboard page and what it does
- List every admin page and what it does
- JavaScript API client (app.js) functions

## 14. API ENDPOINT REFERENCE
- Every endpoint with method, URL, request body, response format

## 15. DEPLOYMENT STATUS
- What's deployed vs missing
- Server file structure

## 16. PRICING PLANS
- All plans with features and limits
```

---

## CRITICAL INFO THE BLUEPRINT MUST INCLUDE

### All Credentials:
```
FTP: kahlen@the-truth-publishing.com / AndassiAthena8
PayPal Client: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
PayPal Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
JWT Secret: TrueVault2026JWTSecretKey!@#$
Peer API Secret: TrueVault2026SecretKey
GoDaddy: 26853687 / Asasasas4!
Contabo/Fly.io: paulhalonen@gmail.com / Asasasas4!
```

### VPN Servers:
```
1. 66.94.103.91 (NY) - Public Key: lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=
2. 144.126.133.253 (STL VIP) - Public Key: qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=
3. 66.241.124.4 (Dallas) - Public Key: dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=
4. 66.241.125.247 (Toronto) - Public Key: O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=
```

### VIP Users (SECRET - never advertise):
```
paulhalonen@gmail.com - Owner (all access)
seige235@yahoo.com - VIP Dedicated (Server 2 only)
```

### Pricing:
```
Basic: $9.99/mo - 3 devices, 1 camera
Family: $14.99/mo - 5 devices, 2 cameras
Dedicated: $29.99/mo - Unlimited devices, 12 cameras
```

### Critical Issue Found:
```
/api/billing/ folder is NOT DEPLOYED to server!
Users cannot pay! Must upload immediately.
```

---

## RULES FOR THE BLUEPRINT

1. **Database-driven everything** - No hardcoded styles/colors
2. **100% automated** - Goal is 1-person operation for $6M/year
3. **VIP is SECRET** - Never mention in UI, no special login
4. **Portable** - SQLite databases, can move to new server
5. **VPN subdomain only** - Don't touch builder or other subdomains

---

## WRITE THE BLUEPRINT TO:

E:\Documents\GitHub\truevault-vpn\reference\TRUEVAULT_COMPLETE_TECHNICAL_BLUEPRINT.md

Make it COMPREHENSIVE. Read every file. Document everything.
The user has visual impairment and needs this to be complete.

---

## ALSO UPDATE CHAT LOG:

E:\Documents\GitHub\truevault-vpn\chat_log.txt

Always append progress so nothing is lost if chat crashes again.

---

**END OF INSTRUCTIONS**
