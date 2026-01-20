# TRUEVAULT VPN - SUBDOMAIN CONFIGURATION

**Created:** January 19, 2026  
**Purpose:** Define correct subdomain usage across the entire platform  
**Status:** Official Reference Document  

---

## 🌐 SUBDOMAIN STRUCTURE

### **PRIMARY SUBDOMAIN (Production VPN Business)**

```
vpn.the-truth-publishing.com
```

**Purpose:** Complete TrueVault VPN platform  
**Status:** PRODUCTION - Customer-facing  
**Location:** /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com  

**Contains:**
- ✅ Customer website (homepage, pricing, features)
- ✅ User dashboard
- ✅ Admin control panel
- ✅ Database builder (VPN admin tool)
- ✅ API endpoints (PayPal webhooks, WireGuard config generation)
- ✅ Marketing automation platform
- ✅ Form library
- ✅ Tutorial system
- ✅ Support ticket system
- ✅ Payment processing
- ✅ Server provisioning automation
- ✅ All SQLite databases (vpn.db, payments.db, automation.db, etc.)

---

## ❌ DEPRECATED SUBDOMAINS (DO NOT USE)

### **builder.the-truth-publishing.com**
**Status:** ❌ DEPRECATED  
**Reason:** Original prototype/development subdomain  
**Action:** All references must be changed to `vpn.the-truth-publishing.com`  

### **sales.the-truth-publishing.com**
**Status:** ❌ NEVER CREATED  
**Reason:** Not needed - all sales happen on main VPN subdomain  

### **manage.the-truth-publishing.com**
**Status:** ❌ NEVER CREATED  
**Reason:** Not needed - admin panel is part of VPN subdomain  

---

## 📁 FILE STRUCTURE

```
/home/eybn38fwc55z/public_html/
│
├── the-truth-publishing.com/           # Kah-Len's personal book website
│   ├── index.html                      # DO NOT TOUCH - Personal site
│   ├── about.html
│   └── ... (book-related content)
│
└── vpn.the-truth-publishing.com/       # TrueVault VPN (ALL VPN FILES HERE)
    │
    ├── index.php                       # Homepage
    ├── pricing.php
    ├── features.php
    ├── about.php
    ├── contact.php
    ├── terms.php
    ├── privacy.php
    ├── refund.php
    │
    ├── dashboard/                      # Customer dashboard
    │   ├── index.php
    │   ├── servers.php
    │   ├── billing.php
    │   ├── support.php
    │   └── settings.php
    │
    ├── admin/                          # Admin control panel
    │   ├── index.php                   # Admin dashboard
    │   ├── customers.php
    │   ├── servers.php
    │   ├── payments.php
    │   ├── support.php
    │   ├── provisioning/               # Server automation
    │   │   ├── auto-provision.php
    │   │   ├── change-server-password.py
    │   │   ├── gmail-parser.php
    │   │   └── manual-provision.php
    │   ├── troubleshooting/            # Fix scripts
    │   │   ├── diagnostics-panel.php
    │   │   └── fix-scripts/
    │   └── database-builder/           # VPN admin DB builder
    │       ├── index.php
    │       └── setup-builder.php
    │
    ├── api/                            # API endpoints
    │   ├── paypal-webhook.php          # PayPal webhooks
    │   ├── contabo-api.php             # Contabo integration
    │   ├── automation-engine.php       # Workflow processor
    │   ├── generate-config.php         # WireGuard configs
    │   └── support-api.php             # Ticket system
    │
    ├── marketing/                      # Marketing automation
    │   ├── campaigns.php
    │   ├── analytics.php
    │   └── templates/
    │
    ├── forms/                          # Form library
    │   ├── library.php
    │   └── templates/
    │
    ├── tutorials/                      # Tutorial system
    │   ├── index.php
    │   └── lessons/
    │
    ├── enterprise/                     # Enterprise platform (future)
    │   ├── index.php
    │   ├── database-builder/           # Enterprise DB builder
    │   └── business-hub/
    │
    ├── databases/                      # SQLite databases
    │   ├── vpn.db                      # Users, servers, configs
    │   ├── payments.db                 # Transactions, subscriptions
    │   ├── automation.db               # Workflows, logs
    │   ├── support.db                  # Tickets, responses
    │   ├── marketing.db                # Campaigns, analytics
    │   ├── forms.db                    # Form templates, submissions
    │   └── themes.db                   # UI themes, styles
    │
    ├── server-scripts/                 # Scripts to run ON VPS servers
    │   ├── install-wireguard.sh
    │   ├── create-client-config.sh
    │   ├── health-check.sh
    │   └── auto-update.sh
    │
    └── cron/                           # Scheduled tasks
        ├── check-servers.php
        ├── process-emails.php
        ├── retry-failed.php
        └── monthly-billing.php
```

---

## 🔗 URL PATTERNS

### **Customer-Facing URLs**

```
Homepage:           https://vpn.the-truth-publishing.com/
Pricing:            https://vpn.the-truth-publishing.com/pricing.php
Sign Up:            https://vpn.the-truth-publishing.com/signup.php
Login:              https://vpn.the-truth-publishing.com/login.php
Dashboard:          https://vpn.the-truth-publishing.com/dashboard/
Support:            https://vpn.the-truth-publishing.com/support/
```

### **Admin URLs**

```
Admin Login:        https://vpn.the-truth-publishing.com/admin/
Server Management:  https://vpn.the-truth-publishing.com/admin/servers.php
Provisioning:       https://vpn.the-truth-publishing.com/admin/provisioning/
Troubleshooting:    https://vpn.the-truth-publishing.com/admin/troubleshooting/
Database Builder:   https://vpn.the-truth-publishing.com/admin/database-builder/
```

### **API Endpoints**

```
PayPal Webhook:     https://vpn.the-truth-publishing.com/api/paypal-webhook.php
Config Generator:   https://vpn.the-truth-publishing.com/api/generate-config.php
Automation Engine:  https://vpn.the-truth-publishing.com/api/automation-engine.php
Support API:        https://vpn.the-truth-publishing.com/api/support-api.php
```

### **Enterprise Platform (Future)**

```
Enterprise Home:    https://vpn.the-truth-publishing.com/enterprise/
Enterprise Builder: https://vpn.the-truth-publishing.com/enterprise/database-builder/
Business Hub:       https://vpn.the-truth-publishing.com/enterprise/business-hub/
```

---

## 📧 EMAIL CONFIGURATION

### **Customer Communications**

```
Email Address:      admin@the-truth-publishing.com
Password:           A'ndassiAthena8
SMTP Server:        the-truth-publishing.com
SMTP Port:          465 (SSL)
IMAP Server:        the-truth-publishing.com
IMAP Port:          993 (SSL)

Purpose:            All customer-facing emails
  - Welcome emails
  - Payment receipts
  - Password resets
  - Support responses
  - Service notifications
  - Marketing campaigns
```

### **Business Operations**

```
Email Address:      paulhalonen@gmail.com
Password:           Asasasas4!
Access Method:      Gmail API (OAuth 2.0)

Purpose:            System automation ONLY
  - Contabo server notifications (RECEIVE ONLY)
  - Fly.io deployment alerts (RECEIVE ONLY)
  - PayPal business notifications (RECEIVE ONLY)
  - System parses these emails automatically
```

---

## 🔐 PAYPAL CONFIGURATION

```
PayPal Account:     paulhalonen@gmail.com
App Name:           MyApp_ConnectionPoint_Systems_Inc
Client ID:          ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Mode:               LIVE (production)

Webhook URL:        https://vpn.the-truth-publishing.com/api/paypal-webhook.php
Webhook ID:         46924926WL757580D

Return URL:         https://vpn.the-truth-publishing.com/payment-success.php
Cancel URL:         https://vpn.the-truth-publishing.com/payment-cancelled.php
```

---

## 🗄️ DATABASE LOCATIONS

**All databases stored in:**
```
/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/
```

**Database List:**
```
vpn.db          - Users, servers, WireGuard configs, VIP list
payments.db     - Transactions, subscriptions, invoices
automation.db   - Workflows, scheduled tasks, logs
support.db      - Tickets, responses, knowledge base
marketing.db    - Campaigns, email templates, analytics
forms.db        - Form templates, submissions
tutorials.db    - Lessons, progress tracking
themes.db       - UI themes, colors, styles (Parts 1-11 use this)
enterprise.db   - Enterprise customers, custom databases
builder.db      - Database builder metadata
```

---

## ⚠️ CRITICAL RULES

### **Rule 1: Single Subdomain**
✅ **ALL** VPN functionality lives under `vpn.the-truth-publishing.com`  
❌ **NEVER** create separate subdomains for different features

### **Rule 2: Portability**
✅ All databases are SQLite (easy to move)  
✅ All paths relative to subdomain root  
✅ No hardcoded paths  
✅ System can be moved to new domain in minutes

### **Rule 3: Main Site Separation**
✅ VPN subdomain is completely independent  
❌ **NEVER** touch files in `/the-truth-publishing.com/`  
❌ Main site is Kah-Len's personal book website

### **Rule 4: Theme System**
✅ All VPN pages load colors from `themes.db`  
✅ No hardcoded colors in CSS  
✅ Theme changes apply instantly across entire site

### **Rule 5: URL References**
✅ Always use: `vpn.the-truth-publishing.com`  
❌ Never use: `builder.`, `sales.`, `manage.` subdomains  
✅ Search codebase and docs for wrong references

---

## 🔍 FINDING & FIXING WRONG REFERENCES

**Search for deprecated subdomains:**

```bash
# In code files
grep -r "builder\.the-truth-publishing" /path/to/vpn/
grep -r "sales\.the-truth-publishing" /path/to/vpn/
grep -r "manage\.the-truth-publishing" /path/to/vpn/

# In documentation
grep -r "builder\.the-truth-publishing" /path/to/docs/
```

**Replace with correct subdomain:**

```
OLD: https://builder.the-truth-publishing.com/api/paypal-webhook.php
NEW: https://vpn.the-truth-publishing.com/api/paypal-webhook.php

OLD: builder.the-truth-publishing.com/automation-dashboard.html
NEW: vpn.the-truth-publishing.com/admin/automation-dashboard.html
```

---

## 📊 VERIFICATION CHECKLIST

After any changes, verify:

- [ ] All URLs point to `vpn.the-truth-publishing.com`
- [ ] No references to `builder.`, `sales.`, or `manage.` subdomains
- [ ] PayPal webhook URL is correct
- [ ] Email FROM addresses use `admin@the-truth-publishing.com`
- [ ] Database paths are relative to `/vpn.the-truth-publishing.com/databases/`
- [ ] API endpoints accessible at `/vpn.the-truth-publishing.com/api/`
- [ ] Admin panel accessible at `/vpn.the-truth-publishing.com/admin/`
- [ ] All documentation updated

---

## 🎯 SUMMARY

**ONE SUBDOMAIN. ONE PLATFORM. ALL FEATURES.**

```
vpn.the-truth-publishing.com = EVERYTHING
```

**No exceptions. No additional subdomains. Ever.**

---

**✅ Use this document as the single source of truth for all subdomain questions.**
