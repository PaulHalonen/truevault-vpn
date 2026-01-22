# TRUEVAULT VPN - CHAT HANDOFF DOCUMENT
**Created:** January 22, 2026 - 1:00 AM CST
**Purpose:** Continue build from where previous chat left off
**CRITICAL:** Follow the checklist EXACTLY - do not skim and write custom code!

---

## 🚨 IMPORTANT INSTRUCTIONS FOR NEW CHAT

1. **READ THE CHECKLIST FILES** - Don't skim them, READ them completely
2. **DO NOT REWRITE CODE** - Parts 4, 5, and 6a are ALREADY COMPLETE and DEPLOYED
3. **FOLLOW THE METHODOLOGY** - Build first, test last, small chunks, commit often
4. **CHECK chat_log.txt** - Contains all progress and what's been done
5. **USER HAS VISUAL IMPAIRMENT** - Claude does ALL editing, user cannot see code

---

## ✅ COMPLETED PARTS (DO NOT REDO!)

### Part 1-3: Foundation (COMPLETE)
- Directory structure created
- 9 SQLite3 databases created and deployed
- Config files, .htaccess security
- Database.php, JWT.php, Validator.php classes
- Auth endpoints: register.php, login.php, me.php, logout.php

### Part 4: Device Management (COMPLETE)
Files deployed to server:
- `/api/devices/list.php` - List user devices
- `/api/devices/add.php` - Add device with SERVER-SIDE key generation
- `/api/devices/delete.php` - Remove device
- `/api/devices/config.php` - Download WireGuard config
- `/dashboard/setup-device.php` - 1-click setup UI

### Part 5: PayPal Billing (COMPLETE)
Files deployed to server:
- `/includes/PayPal.php` - PayPal API helper class
- `/api/billing/create-subscription.php` - Start subscription
- `/api/billing/cancel-subscription.php` - Cancel subscription
- `/api/billing/paypal-webhook.php` - Handle PayPal events

### Part 6a: Port Forwarding (COMPLETE)
Files deployed to server:
- `/api/port-forwarding/list.php` - List port forwards
- `/api/port-forwarding/add.php` - Create port forward
- `/api/port-forwarding/delete.php` - Remove port forward
- `/dashboard/port-forwarding.php` - Port forwarding UI

---

## 🎯 WHERE TO CONTINUE

**Start at:** Part 6b or Part 7 in the checklist

Check the Master_Checklist folder:
```
E:\Documents\GitHub\truevault-vpn\Master_Checklist\
├── MASTER_CHECKLIST_PART1.md
├── MASTER_CHECKLIST_PART2.md
├── MASTER_CHECKLIST_PART3.md
├── MASTER_CHECKLIST_PART4.md
├── MASTER_CHECKLIST_PART5.md
├── MASTER_CHECKLIST_PART6.md  ← Check what's left here
├── MASTER_CHECKLIST_PART7.md
├── MASTER_CHECKLIST_PART8.md
└── ... etc
```

**Before writing ANY code:**
1. Read the specific checklist part completely
2. Check what files already exist on server
3. Only build what's NOT already deployed

---

## 📁 PROJECT LOCATIONS

**Local Repository:**
```
E:\Documents\GitHub\truevault-vpn\
├── website/           ← All PHP files go here
│   ├── api/
│   ├── admin/
│   ├── configs/
│   ├── dashboard/
│   ├── databases/     ← SQLite3 database files
│   └── includes/
├── Master_Checklist/  ← BUILD INSTRUCTIONS
├── MASTER_BLUEPRINT/  ← Technical specs (reference only)
└── chat_log.txt       ← Progress tracking
```

**Server Location:**
```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
```

**Live URL:**
```
https://vpn.the-truth-publishing.com/
```

---

## 🔐 CREDENTIALS (From User's Instructions)

**FTP:**
- Host: the-truth-publishing.com
- User: kahlen@the-truth-publishing.com
- Pass: AndassiAthena8
- Port: 21

**PayPal (Already in database):**
- Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
- Secret: EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN
- Webhook ID: 46924926WL757580D

**Admin User (in database):**
- Email: paulhalonen@gmail.com
- Password: Asasasas4!

**VIP Users (in database):**
1. paulhalonen@gmail.com - Owner, regular VIP
2. seige235@yahoo.com - Dedicated St. Louis server (ID 2)

---

## 🖥️ SERVERS (4 Total)

| ID | Name | Location | IP | Type |
|----|------|----------|----|----|
| 1 | New York Shared | New York, USA | 66.94.103.91 | Shared |
| 2 | St. Louis VIP | St. Louis, USA | 144.126.133.253 | VIP ONLY (seige235) |
| 3 | Dallas Streaming | Dallas, USA | 66.241.124.4 | Shared |
| 4 | Toronto Canada | Toronto, Canada | 66.241.125.247 | Shared |

---

## 💾 DATABASES (All SQLite3)

Located at: `/website/databases/`

| Database | Purpose |
|----------|---------|
| users.db | User accounts, VIP status |
| devices.db | User devices, WireGuard configs |
| servers.db | VPN server list |
| billing.db | Subscriptions, invoices |
| port_forwards.db | Port forwarding rules |
| parental_controls.db | Content filtering |
| admin.db | Admin users, system settings, VIP list |
| logs.db | Audit logs, security events |
| themes.db | UI theming (database-driven) |

---

## ⚠️ CRITICAL RULES

1. **ALL SQLite3** - No PDO, use `SQLite3` class with `bindValue()` and `fetchArray()`
2. **NO HARDCODING** - All settings in database, editable via admin panel
3. **SERVER-SIDE KEYS** - WireGuard keys generated on server, not browser
4. **VIP AUTO-DETECTION** - Registration checks vip_list table automatically
5. **DEDICATED SERVER** - seige235@yahoo.com gets Server #2 exclusively
6. **BUILD FIRST** - No testing until section complete
7. **SMALL CHUNKS** - Commit every 2-3 files to prevent loss

---

## 📋 WORKFLOW

1. User says "next" or "continue"
2. Read the appropriate checklist part (use `view` tool)
3. Check what's already built (don't redo!)
4. Build the next task from checklist
5. Upload via FTP PowerShell script
6. Mark task complete
7. Git commit with descriptive message
8. Update chat_log.txt
9. Repeat

---

## 🔍 HOW TO CHECK WHAT'S DEPLOYED

```bash
# List files on server
curl -s "https://vpn.the-truth-publishing.com/api/devices/list.php"
# If returns JSON (even error), file exists

# Or use FTP to list directory
```

---

## 📝 CHAT LOG LOCATION

Always append progress to:
```
E:\Documents\GitHub\truevault-vpn\chat_log.txt
```

Format:
```
=================================================================
PART X: Description - Date Time
=================================================================
COMPLETED: list of files
STATUS: what's done
NEXT: what to do next
=================================================================
```

---

## 🚀 READY TO CONTINUE

Tell the new chat:
> "Read the handoff document at E:\Documents\GitHub\truevault-vpn\HANDOFF.md then continue building from the checklist. Parts 1-6a are complete. Say 'next' to see what's remaining."

The new chat should:
1. Read this HANDOFF.md file
2. Read chat_log.txt for recent progress
3. Check which checklist part is next
4. Continue building WITHOUT redoing completed work

---

## 📞 USER CONTEXT

- **Name:** Kah-Len
- **Visual Impairment:** Cannot see code, Claude does ALL editing
- **Project:** TrueVault VPN - one-person automated VPN business
- **Goal:** Fully transferable business (30-minute handoff to buyer)
- **Buyer:** seige235@yahoo.com (gets dedicated server)
- **Urgency:** "This is my life. Without this build, no money = no food"

---

**END OF HANDOFF DOCUMENT**
