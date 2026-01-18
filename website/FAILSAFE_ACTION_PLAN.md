# TRUEVAULT VPN - FAILSAFE ACTION PLAN
**Created:** January 17, 2026  
**Purpose:** Complete rebuild guide that survives chat crashes  
**Status:** 🔴 REBUILD REQUIRED - Previous build did NOT follow checklist

---

## 📊 QUICK STATUS OVERVIEW

**Can users register?** ❌ NO - Schema mismatch  
**Can users login?** ❌ NO - Schema mismatch  
**Can admin login?** ⚠️ PATCHED - Fragile  
**Do servers work?** ❓ UNKNOWN - Not verified  
**Is PayPal working?** ❓ UNKNOWN - Not tested  

---

## ⚠️ CRITICAL PROBLEM SUMMARY

The previous build was done **WITHOUT following the Master Checklist**. This caused:
1. Schema mismatches between code and databases
2. Missing database tables and columns
3. Wrong table/column names throughout
4. Servers not configured with WireGuard
5. User login broken (no user registration flow)
6. Admin login patched but fragile

**DECISION: Complete rebuild from Part 1, following checklist EXACTLY.**

---

## 📋 MASTER CHECKLIST PARTS (11 Total)

| Part | Description | Status | Notes |
|------|-------------|--------|-------|
| 1 | Environment Setup | ⬜ REDO | Folder structure exists but needs verification |
| 2 | All 9 Databases | ⬜ REDO | Schema mismatch - must recreate ALL |
| 3 | Authentication System | ⬜ REDO | Code doesn't match schema |
| 4 | Device Management | ⬜ REDO | Depends on correct auth |
| 5 | Admin & PayPal | ⬜ REDO | Admin panel exists but broken |
| 6 | Advanced Features | ⬜ NOT DONE | Port forwarding, cameras |
| 7 | Automation System | ⬜ NOT DONE | Email, workflows |
| 8 | Frontend & Transfer | ⬜ PARTIAL | Landing page exists |
| 9 | Server Management | ⬜ NOT DONE | **CRITICAL - Servers not configured** |
| 10 | Android App | ⬜ NOT DONE | Future phase |
| 11 | Parental Controls | ⬜ NOT DONE | Future phase |

---

## 🖥️ SERVER INFRASTRUCTURE (Must be done FIRST)

### 4 VPN Servers:

| # | Name | IP | Provider | Type | Status |
|---|------|-----|----------|------|--------|
| 1 | New York | 66.94.103.91 | Contabo | Shared | ⬜ WireGuard NOT verified |
| 2 | St. Louis | 144.126.133.253 | Contabo | VIP (seige235@yahoo.com) | ⬜ WireGuard NOT verified |
| 3 | Dallas | 66.241.124.4 | Fly.io | Shared | ⬜ WireGuard NOT verified |
| 4 | Toronto | 66.241.125.247 | Fly.io | Shared | ⬜ WireGuard NOT verified |

### Server Credentials:
- **Contabo Login:** paulhalonen@gmail.com / Asasasas4!
- **Fly.io Login:** paulhalonen@gmail.com / Asasasas4!
- **Contabo SSH:** root (need to verify keys)

---

## 🗄️ DATABASE REQUIREMENTS (9 Separate DBs)

Per checklist, these 9 databases must exist in `/databases/`:

| Database | Purpose | Status |
|----------|---------|--------|
| users.db | User accounts, sessions, tokens | ⬜ Schema wrong |
| devices.db | VPN devices, configs | ⬜ Schema wrong |
| servers.db | VPN server inventory | ⬜ Schema wrong |
| billing.db | Subscriptions, transactions | ⬜ Schema wrong |
| port_forwards.db | Port forwarding rules | ⬜ Unknown |
| parental_controls.db | Parental control rules | ⬜ Unknown |
| admin.db | Admin users, settings, VIP list | ⬜ Schema wrong |
| logs.db | Security events, audit trail | ⬜ Unknown |
| support.db | Support tickets | ⬜ Unknown |

---

## 🔑 CREDENTIALS REFERENCE

### FTP (Website):
```
Host: the-truth-publishing.com
User: kahlen@the-truth-publishing.com
Pass: AndassiAthena8
Port: 21
Path: /public_html/vpn.the-truth-publishing.com/
```

### PayPal (LIVE):
```
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
Webhook: https://builder.the-truth-publishing.com/api/paypal-webhook.php
```

### VIP Users:
```
seige235@yahoo.com - Dedicated St. Louis server, 999 devices, FREE
paulhalonen@gmail.com - Site owner, full admin access
```

---

## 📍 CURRENT SESSION CHECKPOINT

**Where we stopped:** Identified complete rebuild is needed

**Next action:** Start Part 9 (Server Management) FIRST because:
1. Servers must have WireGuard configured before VPN works
2. Server public keys needed for device configs
3. Can't test anything without working servers

---

## ✅ REBUILD ORDER (Recommended)

### Phase 1: Infrastructure (Do First)
1. ⬜ **Part 9:** Configure all 4 servers with WireGuard
2. ⬜ Get server public keys
3. ⬜ Verify SSH access to all servers
4. ⬜ Test WireGuard connectivity

### Phase 2: Foundation
5. ⬜ **Part 1:** Verify/fix folder structure
6. ⬜ **Part 2:** Recreate ALL 9 databases with correct schemas
7. ⬜ **Part 3:** Build authentication (following checklist EXACTLY)

### Phase 3: Core Features
8. ⬜ **Part 4:** Device management
9. ⬜ **Part 5:** Admin panel & PayPal
10. ⬜ **Part 6:** Advanced features

### Phase 4: Polish
11. ⬜ **Part 7:** Automation
12. ⬜ **Part 8:** Frontend pages

---

## 🚨 RULES FOR REBUILD

1. **FOLLOW CHECKLIST EXACTLY** - No improvisation
2. **Copy code from checklist** - Don't write custom code
3. **Test after each task** - Before moving to next
4. **Update this document** - After each completed task
5. **Small chunks** - Don't try to do too much at once

---

## 📂 CURRENT FILE STATE

### Local Repository (E:\Documents\GitHub\truevault-vpn\website\)
- databases/ folder: **EMPTY** (only .htaccess)
- Databases only exist on LIVE SERVER

### Local /includes/ Files:
| File | Checklist Requires | Status |
|------|-------------------|--------|
| Auth.php | ✅ Yes | ⚠️ Exists but may not match checklist |
| Database.php | ✅ Yes | ⚠️ Exists but had missing getInstance() |
| JWT.php | ✅ Yes | ❌ MISSING |
| Validator.php | ✅ Yes | ❌ MISSING |

**⚠️ WARNING:** Code exists but doesn't follow checklist schemas!

---

## 🚨 IMMEDIATE NEXT STEPS (Copy-Paste Ready)

### Step 1: Verify Server SSH Access
```bash
# Test SSH to each server (run from terminal)
ssh root@66.94.103.91      # New York
ssh root@144.126.133.253   # St. Louis (VIP)
ssh root@66.241.124.4      # Dallas (Fly.io - may need flyctl)
ssh root@66.241.125.247    # Toronto (Fly.io - may need flyctl)
```

### Step 2: Check WireGuard Status on Each Server
```bash
# Run on each server after SSH:
wg show
systemctl status wg-quick@wg0
```

### Step 3: Get Server Public Keys
```bash
# Run on each server:
cat /etc/wireguard/publickey
```

### Step 4: Record Results Here
| Server | SSH Works? | WireGuard Installed? | Public Key |
|--------|-----------|---------------------|------------|
| NY (66.94.103.91) | ⬜ | ⬜ | |
| STL (144.126.133.253) | ⬜ | ⬜ | |
| Dallas (66.241.124.4) | ⬜ | ⬜ | |
| Toronto (66.241.125.247) | ⬜ | ⬜ | |

### Step 5: If WireGuard NOT Installed (Contabo)
```bash
apt update && apt upgrade -y
apt install wireguard -y
wg genkey | tee /etc/wireguard/privatekey | wg pubkey > /etc/wireguard/publickey
chmod 600 /etc/wireguard/privatekey
```

---

## 📝 SESSION LOG

### January 17, 2026 - Evening
- Identified schema mismatches causing login failures
- Admin login patched but user login still broken
- Decision: Complete rebuild following checklist
- Created this failsafe document

**NEXT SESSION STARTS HERE: Part 9 - Server Management**
