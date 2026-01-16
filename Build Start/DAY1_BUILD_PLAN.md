# 🎯 DAY 1 BUILD PLAN - TRUEVAULT VPN
**Date:** January 16, 2026  
**Goal:** Complete environment setup + database foundation  
**Time:** 3-4 hours (15-20 chunks)  
**Method:** Chunk-by-chunk (same as blueprint creation!)

---

## 📁 CORRECT PROJECT LOCATIONS

### **Documentation Repository (Local GitHub):**
```
E:\Documents\GitHub\truevault-vpn\
├── MASTER_BLUEPRINT/          (Technical specs - 20 sections)
├── Master_Checklist/           (Build instructions - 8 parts)
├── BUILD_PROGRESS.md           (Create this - tracks chunks)
└── README.md
```

### **VPN Build Location (GoDaddy Server via FTP):**
```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
├── api/                        (API endpoints)
├── public/                     (User-facing pages)
├── admin/                      (Admin panel)
├── database/                   (SQLite databases)
├── includes/                   (Helper classes)
├── config/                     (Configuration files)
├── assets/                     (CSS, JS, images)
└── logs/                       (Error logs)
```

**CRITICAL:** Never confuse with E:\Documents\GitHub\truth-publishing-reborn\ (that's your main website!)

---

## 📋 DAY 1 CHUNK CHECKLIST (15 chunks)

### **PHASE 1: Setup Documentation (2 chunks - 10 min)**

```
☐ CHUNK 1: Create BUILD_PROGRESS.md tracker (5 min)
  Location: E:\Documents\GitHub\truevault-vpn\BUILD_PROGRESS.md
  Purpose: Track all 200+ chunks across 8 days
  Template provided below

☐ CHUNK 2: Create BUILD_LOG.txt (5 min)
  Location: E:\Documents\GitHub\truevault-vpn\BUILD_LOG.txt
  Purpose: Append session notes after every 5 chunks
  First entry: "Day 1 started - [datetime]"
```

### **PHASE 2: Create Folder Structure (3 chunks - 20 min)**

```
☐ CHUNK 3: Create main folders via FTP (10 min)
  Reference: Master_Checklist/MASTER_CHECKLIST_PART1.md (lines 1-150)
  Create:
  - /vpn/api/
  - /vpn/public/
  - /vpn/admin/
  - /vpn/database/
  - /vpn/includes/
  - /vpn/config/
  - /vpn/assets/css/
  - /vpn/assets/js/
  - /vpn/assets/images/
  - /vpn/logs/
  Test: FTP into each folder to verify

☐ CHUNK 4: Create security .htaccess files (5 min)
  Files:
  - /vpn/.htaccess (main)
  - /vpn/database/.htaccess (block access)
  - /vpn/config/.htaccess (block access)
  - /vpn/logs/.htaccess (block access)
  - /vpn/includes/.htaccess (block access)
  Content: From PART1 lines 150-200

☐ CHUNK 5: Create index.php redirect (5 min)
  File: /vpn/index.php
  Purpose: Redirect root to /public/index.html
  Code: Simple header redirect (8 lines)
```

### **PHASE 3: Master Configuration (3 chunks - 25 min)**

```
☐ CHUNK 6: Create config/database.php (10 min)
  Reference: SECTION_02_DATABASE_ARCHITECTURE.md (lines 1-100)
  Contains:
  - Database paths (all 9 SQLite files)
  - Connection function
  - Error handling
  Location: /vpn/config/database.php

☐ CHUNK 7: Create config/servers.php (10 min)
  Reference: SECTION_10_SERVER_MANAGEMENT.md (lines 1-150)
  Contains:
  - 4 server configurations:
    * NY (Contabo 66.94.103.91 - shared)
    * Dallas (Fly.io 66.241.124.4 - shared)
    * Canada (Fly.io 66.241.125.247 - shared)
    * St. Louis (Contabo 144.126.133.253 - VIP ONLY)
  - Server status check function
  Location: /vpn/config/servers.php

☐ CHUNK 8: Create config/paypal.php (5 min)
  Reference: SECTION_09_PAYMENT_INTEGRATION.md (lines 1-100)
  Contains:
  - Live PayPal client ID (from project notes)
  - Live PayPal secret (from project notes)
  - Webhook URL
  - Plan IDs for Standard ($9.99) and Pro ($14.99)
  Location: /vpn/config/paypal.php
```

### **PHASE 4: Database Creation (5 chunks - 60 min)**

```
☐ CHUNK 9: Create users.db schema (15 min)
  Reference: SECTION_02 (lines 101-300)
  File: /vpn/database/schema-users.sql
  Tables:
  - users (id, email, password_hash, tier, status, vip, created_at)
  - sessions (id, user_id, token, expires_at)
  - auth_tokens (id, user_id, token, type, expires_at)
  - password_resets (id, user_id, token, expires_at)
  Indexes: user_id, email, token

☐ CHUNK 10: Create devices.db schema (10 min)
  Reference: SECTION_03 (lines 1-200)
  File: /vpn/database/schema-devices.sql
  Tables:
  - devices (id, user_id, name, public_key, server_id, ip_address, created_at)
  - device_configs (id, device_id, config_text, created_at)
  Indexes: user_id, public_key, server_id

☐ CHUNK 11: Create servers.db + billing.db schemas (10 min)
  Reference: SECTION_02 (lines 301-500)
  Files:
  - /vpn/database/schema-servers.sql
  - /vpn/database/schema-billing.sql
  Tables:
  - servers (id, name, ip, location, type, ports)
  - subscriptions (id, user_id, paypal_id, tier, status, start_date, end_date)
  - transactions (id, user_id, amount, status, paypal_txn_id)
  - invoices (id, user_id, amount, status, created_at)

☐ CHUNK 12: Create port_forwards.db + parental_controls.db schemas (10 min)
  Reference: SECTION_02 (lines 501-700)
  Files:
  - /vpn/database/schema-port-forwards.sql
  - /vpn/database/schema-parental-controls.sql
  Tables:
  - port_forward_rules (id, device_id, external_port, internal_port, enabled)
  - discovered_devices (id, user_id, ip, mac, type, name)
  - parental_rules (id, user_id, device_id, enabled)
  - blocked_categories (id, rule_id, category)
  - blocked_requests (id, rule_id, url, timestamp)

☐ CHUNK 13: Create admin.db + logs.db + support.db schemas (15 min)
  Reference: SECTION_02 (lines 701-1000)
  Files:
  - /vpn/database/schema-admin.sql
  - /vpn/database/schema-logs.sql
  - /vpn/database/schema-support.sql
  Tables:
  - admin_users (id, username, password_hash, email, created_at)
  - system_settings (id, key, value, category)
  - vip_list (id, email, notes, added_at)
  - security_events (id, event_type, ip_address, details, timestamp)
  - audit_log (id, user_id, action, details, timestamp)
  - api_requests (id, endpoint, method, status_code, timestamp)
  - errors (id, error_type, message, file, line, timestamp)
  - email_log (id, to_email, subject, status, sent_at)
  - email_queue (id, to_email, subject, body, scheduled_for, sent)
  - support_tickets (id, user_id, subject, status, created_at)
  - ticket_messages (id, ticket_id, user_id, message, timestamp)
  - knowledge_base (id, category, title, content, created_at)
```

### **PHASE 5: Database Initialization (2 chunks - 20 min)**

```
☐ CHUNK 14: Create database initialization script (15 min)
  Reference: PART1 (lines 400-550)
  File: /vpn/database/init.php
  Purpose:
  - Creates all 9 SQLite database files
  - Executes all schema files
  - Inserts 4 servers (NY, Dallas, Canada, St. Louis)
  - Creates admin user (kahlen@truthvault.com)
  - Creates VIP entry (seige235@yahoo.com)
  - Returns success/error report
  Run once: https://vpn.the-truth-publishing.com/database/init.php

☐ CHUNK 15: Test all databases (5 min)
  Reference: PART1 (lines 550-600)
  File: /vpn/database/test.php
  Tests:
  - All 9 .db files exist
  - All tables created
  - 4 servers inserted
  - Admin user exists
  - VIP email in vip_list
  - Connections work
  Output: Pass/fail for each
```

---

## 📊 PROGRESS TRACKING

**After EVERY chunk, update:**

**File:** `E:\Documents\GitHub\truevault-vpn\BUILD_PROGRESS.md`

```markdown
# TRUEVAULT VPN BUILD PROGRESS

**Last Updated:** [Date/Time]  
**Current Day:** Day 1 of 8  
**Current Chunk:** [X] of 200+  
**Completion:** [X]%

---

## Day 1: Environment Setup (15 chunks)

### Phase 1: Documentation (2 chunks)
- [✅] Chunk 1: BUILD_PROGRESS.md created
- [✅] Chunk 2: BUILD_LOG.txt created

### Phase 2: Folder Structure (3 chunks)
- [⏳] Chunk 3: Main folders (IN PROGRESS)
- [ ] Chunk 4: Security .htaccess
- [ ] Chunk 5: Index redirect

### Phase 3: Configuration (3 chunks)
- [ ] Chunk 6: database.php
- [ ] Chunk 7: servers.php
- [ ] Chunk 8: paypal.php

### Phase 4: Database Schemas (5 chunks)
- [ ] Chunk 9: users.db schema
- [ ] Chunk 10: devices.db schema
- [ ] Chunk 11: servers.db + billing.db
- [ ] Chunk 12: port_forwards.db + parental_controls.db
- [ ] Chunk 13: admin.db + logs.db + support.db

### Phase 5: Initialization (2 chunks)
- [ ] Chunk 14: init.php script
- [ ] Chunk 15: test.php verification

---

## Statistics
- **Day 1 Chunks Complete:** 2 / 15
- **Total Chunks Complete:** 2 / 200+
- **Files Created:** 2
- **Folders Created:** 0
- **Databases Created:** 0 / 9

---

## Next Action
**CHUNK 3:** Create main folder structure via FTP

---

## Session Log
**Session 1 - [Time]:**
- Created BUILD_PROGRESS.md
- Created BUILD_LOG.txt
- Ready for folder creation

---
```

---

## 🎯 CHAT INSTRUCTIONS FOR TOMORROW

**Save this message and use it to start tomorrow's build:**

```
📅 DAY 1 BUILD SESSION - TrueVault VPN

CRITICAL PROJECT LOCATIONS:
- Documentation: E:\Documents\GitHub\truevault-vpn\
- Build Location: /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/
- FTP: the-truth-publishing.com (user: kahlen@the-truth-publishing.com)

NEVER reference E:\Documents\GitHub\truth-publishing-reborn\ - that's a different project!

TODAY'S GOAL: Complete Day 1 - Environment Setup (15 chunks)

RULES:
1. Work on ONE chunk at a time
2. Read ONLY the file sections I specify
3. Update BUILD_PROGRESS.md after EACH chunk
4. Say "CHUNK X COMPLETE" after each one
5. I'll say "next" to continue
6. Stop after every 5 chunks (15-20 min break)

FILES TO CREATE FIRST:
1. E:\Documents\GitHub\truevault-vpn\BUILD_PROGRESS.md
2. E:\Documents\GitHub\truevault-vpn\BUILD_LOG.txt

THEN READ:
- E:\Documents\GitHub\truevault-vpn\Master_Checklist\MASTER_CHECKLIST_PART1.md (lines 1-150 only)

START WITH: Chunk 1 - Create BUILD_PROGRESS.md

Use the template from DAY1_BUILD_PLAN.md

Ready? Let's begin Chunk 1.
```

---

## ✅ CHUNK COMPLETION TEMPLATE

**After each chunk, Claude says:**

```
✅ CHUNK [X] COMPLETE: [task name]

FILES CREATED:
- [exact filepath]

VERIFICATION:
- [what was tested]

UPDATED: BUILD_PROGRESS.md
- Checked off Chunk [X]
- Updated statistics
- Logged in BUILD_LOG.txt

NEXT: Chunk [X+1] - [next task]

Type 'next' to continue, 'break' to pause, or 'stop' to end session.
```

---

## 🚫 WHAT NOT TO DO

**DON'T:**
- ❌ Reference E:\Documents\GitHub\truth-publishing-reborn\
- ❌ Try to build multiple chunks at once
- ❌ Read entire documentation files
- ❌ Skip BUILD_PROGRESS.md updates
- ❌ Work more than 5 chunks without a break

**DO:**
- ✅ Use correct paths (E:\Documents\GitHub\truevault-vpn\)
- ✅ Work one chunk at a time
- ✅ Read only specified line ranges
- ✅ Update progress after every chunk
- ✅ Take breaks every 5 chunks

---

## 📈 DAY 1 ESTIMATED TIMELINE

| Phase | Chunks | Time | Action |
|-------|--------|------|--------|
| **Session 1** | 1-5 | 30 min | Documentation + Folders |
| **BREAK** | - | 10 min | Check FTP, verify folders |
| **Session 2** | 6-10 | 45 min | Config + First 2 databases |
| **BREAK** | - | 10 min | Test database creation |
| **Session 3** | 11-15 | 45 min | Remaining 3 databases + Init |
| **DONE** | - | - | Day 1 complete! |

**Total Time:** ~2 hours 40 minutes (including breaks)

---

## 🎯 SUCCESS CRITERIA FOR DAY 1

**You're done when:**

✅ All 15 chunks checked off in BUILD_PROGRESS.md  
✅ 10 folders created on server via FTP  
✅ 4 security .htaccess files in place  
✅ 3 config files created (database.php, servers.php, paypal.php)  
✅ 9 SQLite schema files created  
✅ init.php runs successfully  
✅ test.php shows all databases working  
✅ 4 servers in servers.db  
✅ Admin user in admin_users table  
✅ seige235@yahoo.com in vip_list  

**Then commit to GitHub:**
```
git add BUILD_PROGRESS.md BUILD_LOG.txt
git commit -m "Day 1 complete: Environment setup + 9 databases initialized"
git push
```

---

**TOMORROW:** Day 2 - Authentication System (18 chunks, 5-6 hours)

