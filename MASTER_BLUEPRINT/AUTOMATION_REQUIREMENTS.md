# TRUEVAULT VPN - COMPLETE AUTOMATION REQUIREMENTS

**Created:** January 19, 2026  
**Purpose:** Define all automation workflows for one-person $6M business operation  
**Status:** Master Requirements Document  

---

## 🎯 AUTOMATION PHILOSOPHY

**Goal:** Zero manual intervention for 99% of operations

**Principles:**
- ✅ Customer never waits for human
- ✅ Payments process automatically
- ✅ Servers provision automatically
- ✅ Support tickets auto-resolve where possible
- ✅ System self-heals common issues
- ✅ Alerts only sent for critical issues requiring human intervention
- ✅ All processes logged for audit trail

---

## 📧 EMAIL CONFIGURATION

### **Two Separate Email Systems:**

#### **1. Business Operations Email**
```yaml
Account: paulhalonen@gmail.com
Password: Asasasas4!
Purpose: Server provisioning, Contabo notifications, business purchases
Direction: RECEIVE ONLY - System parses these emails
Access: Gmail API, IMAP
Security: OAuth 2.0 preferred, App Password fallback
```

**What arrives here:**
- Contabo server provisioning emails
- Fly.io deployment notifications  
- PayPal business account notifications
- Domain registrar notices
- Hosting provider alerts

**System Actions:**
- Parse server credentials (IP, password, location)
- Trigger automated provisioning workflows
- Update server database
- Log all business transactions

#### **2. Customer Communications Email**
```yaml
Account: admin@the-truth-publishing.com
Password: A'ndassiAthena8
SMTP Server: the-truth-publishing.com
SMTP Port: 465 (SSL)
IMAP Server: the-truth-publishing.com
IMAP Port: 993 (SSL)
Purpose: All customer-facing emails
Direction: SEND & RECEIVE
```

**Outgoing emails:**
- Welcome emails (new signups)
- Payment receipts
- Password resets
- Support ticket responses
- Service notifications
- Promotional emails
- Cancellation confirmations

**Incoming emails:**
- Support requests
- Cancellation requests
- General inquiries
- Complaint emails

**Email Templates:**
All customer emails use templates from:
- `vpn.db -> email_templates` table
- Variables: {first_name}, {email}, {plan_name}, {amount}, etc.
- Branding: TrueVault VPN colors and logo
- Signature: "TrueVault VPN Team"

---

## 💳 PAYMENT AUTOMATION

### **PayPal Integration**

**Configuration:**
```yaml
Account: paulhalonen@gmail.com
App Name: MyApp_ConnectionPoint_Systems_Inc
Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
Secret Key: (stored encrypted in database)
Mode: LIVE (production)
Webhook URL: https://vpn.the-truth-publishing.com/api/paypal-webhook.php
Webhook ID: 46924926WL757580D
```

**Automated Workflows:**

#### **Workflow 1: New Subscription**
```
Customer clicks "Subscribe"
    ↓
Redirect to PayPal checkout
    ↓
Customer completes payment
    ↓
PayPal webhook: BILLING.SUBSCRIPTION.ACTIVATED
    ↓
System receives event
    ↓
Update user: status = "active", plan = selected_plan
    ↓
Generate WireGuard config (for shared servers)
    ↓
Email welcome + .conf file (admin@the-truth-publishing.com)
    ↓
User dashboard shows "Active" status
    ↓
Customer can connect immediately
```

#### **Workflow 2: Monthly Renewal**
```
PayPal auto-charges customer
    ↓
Webhook: PAYMENT.SALE.COMPLETED
    ↓
System logs payment
    ↓
Ensure account status = "active"
    ↓
Email payment receipt
    ↓
No service interruption
```

#### **Workflow 3: Payment Failed**
```
PayPal retry fails
    ↓
Webhook: PAYMENT.SALE.DENIED
    ↓
Update status = "grace_period"
    ↓
Email: Friendly payment reminder (Day 0)
    ↓
Wait 3 days
    ↓
Email: Urgent notice (Day 3)
    ↓
Wait 4 days
    ↓
Email: Final warning (Day 7)
    ↓
Wait 1 day
    ↓
Update status = "suspended"
    ↓
Email: Service suspended notice (Day 8)
    ↓
Disable WireGuard access
```

#### **Workflow 4: Dedicated Server Purchase**
```
Customer selects Dedicated Server ($39.97/month)
    ↓
PayPal processes payment
    ↓
Webhook: PAYMENT.SALE.COMPLETED (dedicated plan)
    ↓
System detects "dedicated" plan
    ↓
*** TRIGGER SERVER PROVISIONING WORKFLOW ***
(See Server Provisioning Automation below)
```

---

## 🖥️ SERVER PROVISIONING AUTOMATION

### **Dedicated Server Workflow (CRITICAL)**

**This is the most complex automation - requires multiple APIs:**

```
STEP 1: PAYMENT RECEIVED
    ↓
PayPal webhook confirms dedicated server payment
    ↓
System extracts: customer_id, customer_email, preferred_location
    ↓

STEP 2: PURCHASE CONTABO SERVER
    ↓
Contabo API call: Purchase VPS
    Location: Customer's choice (US-East, US-Central, US-West)
    Plan: Cloud VPS 10 SSD (150GB, $6.15-$6.75/month)
    Password: User sets during API call (we'll change it later)
    ↓
Contabo returns: order_id, estimated_provisioning_time
    ↓
System logs: order_id, customer_id, status="provisioning"
    ↓

STEP 3: WAIT FOR CONTABO EMAIL
    ↓
Monitor: paulhalonen@gmail.com inbox
    ↓
Parse subject: "Your login data!" from no-reply@contabo.com
    ↓
Extract from email body:
    - IP address: 144.126.133.253
    - Location: St. Louis (US-central)
    - Username: root
    - Password: "as chosen by you during order process"
    - IPv6 subnet: 2605:a140:2299:0005::/64
    ↓

STEP 4: STANDARDIZE PASSWORD
    ↓
Run: change-server-password.py
    SSH: root@{IP} with temp password
    Change to: Andassi8 (standard password)
    Verify: Reconnect with new password
    ↓

STEP 5: INSTALL WIREGUARD
    ↓
SSH into server: root@{IP} (password: Andassi8)
    ↓
Upload: install-wireguard.sh
    ↓
Execute script:
    - Install WireGuard + dependencies
    - Enable IP forwarding
    - Generate server keys
    - Configure firewall (UFW)
    - Start WireGuard service
    ↓
Capture output: server_public_key, server_status
    ↓

STEP 6: GENERATE CLIENT CONFIG
    ↓
Upload: create-client-config.sh
    ↓
Execute: ./create-client-config.sh {customer_id} {customer_email}
    ↓
Script generates:
    - Client private/public keys
    - Preshared key
    - Client IP: 10.8.0.2
    - .conf file
    - QR code (for mobile)
    ↓
Capture: .conf file content
    ↓

STEP 7: DELIVER TO CUSTOMER
    ↓
Store in database:
    UPDATE customers SET
        server_ip = '{IP}',
        server_location = '{Location}',
        vpn_config = '{conf_content}',
        server_status = 'online',
        provisioned_at = CURRENT_TIMESTAMP
    WHERE id = {customer_id}
    ↓
Email customer (admin@the-truth-publishing.com):
    Subject: "Your Dedicated VPN Server is Ready!"
    Attachments: truthvault-vpn.conf
    Body: Setup instructions + dashboard link
    ↓
Customer dashboard:
    Shows: "Server Online" (green status)
    Download button: .conf file
    QR code: For mobile devices
    Server details: IP, location, uptime
    ↓

CUSTOMER CAN NOW CONNECT (10-15 minutes after payment)
```

### **Shared Server Setup (Manual, One-Time)**

Shared servers (Fly.io + Contabo shared) are set up ONCE by admin:

```
Admin purchases server
    ↓
Manually run setup scripts
    ↓
Add to servers.db:
    INSERT INTO servers VALUES
    (name, location, ip, public_key, is_visible=1, access_level='public')
    ↓
Server appears in customer dashboard "Server Selection"
    ↓
Multiple customers can connect (bandwidth limited)
```

**Current Shared Servers:**
- Dallas, Texas (Fly.io) - 66.241.124.4
- Toronto, Canada (Fly.io) - 66.241.125.247
- New York, US-East (Contabo) - 66.94.103.91

**VIP-Only Server:**
- St. Louis, US-Central (Contabo) - 144.126.133.253
- Reserved for: seige235@yahoo.com ONLY
- Not visible to other users

---

## 🆘 SUPPORT AUTOMATION

### **Automated Ticket System**

#### **Tier 1: Keyword Auto-Resolution**

```
Customer opens ticket
    ↓
System scans message for keywords:
    - "can't connect" → Run connection diagnostics
    - "slow speed" → Check server load, suggest different server
    - "forgot password" → Send password reset link
    - "billing question" → Link to billing FAQ
    - "cancel" → Start cancellation workflow
    ↓
If keyword matched:
    Auto-reply with solution
    Status: "Auto-Resolved"
    Customer satisfaction survey (24 hours later)
    ↓
If no keyword match:
    Status: "Needs Human Review"
    Escalate to admin
```

#### **Tier 2: Self-Healing Scripts**

When customer reports connection issues:

```
System identifies issue type
    ↓
Admin panel shows:
    🔧 DETECTED ISSUE: Connection Failure
    
    Possible Causes:
    ☐ WireGuard service down
    ☐ Firewall blocking port 51820
    ☐ Server out of memory
    ☐ IP routing issue
    
    Quick Fixes (Click to Run):
    [Restart WireGuard Service]
    [Reset Firewall Rules]
    [Clear DNS Cache]
    [Regenerate Client Keys]
    [Reboot Server]
    
    Diagnostic Info:
    - Server: Dallas, Texas (66.241.124.4)
    - Uptime: 47 days
    - Load: 0.23, 0.31, 0.28
    - Memory: 45% used
    - WireGuard Status: Running ✅
    
    Manual Intervention Needed?
    [Yes - Show Instructions] [No - Customer Resolved]
```

**Each button runs a script:**
- `restart-wireguard.sh` - Safely restarts service
- `reset-firewall.sh` - Resets UFW rules to defaults
- `flush-dns.sh` - Clears DNS cache
- `regenerate-keys.sh {customer_id}` - Creates new .conf
- `reboot-server.sh` - Graceful reboot with customer notification

---

## 🚨 MONITORING & ALERTS

### **What Gets Monitored:**

**Server Health (Every 5 minutes):**
- WireGuard service status
- CPU usage (alert if >80% for 10min)
- Memory usage (alert if >90%)
- Disk usage (alert if >85%)
- Network connectivity (ping test)
- Open ports (51820/udp, 22/tcp)

**Customer Experience (Real-time):**
- Connection success rate
- Average connection speed
- Failed authentication attempts
- Disconnection frequency

**Business Metrics (Hourly):**
- Active subscriptions
- Revenue today/month
- Failed payments count
- Support tickets unresolved
- Server costs vs revenue

### **Alert Levels:**

**Level 1: INFO (No Action Needed)**
- Customer signed up
- Payment received
- Ticket auto-resolved
- Server provisioned successfully

**Level 2: WARNING (Monitor)**
- Server CPU >60% for 10 minutes
- Disk usage >70%
- Failed payment (Day 0)
- Support ticket >24hrs old

**Level 3: CRITICAL (Immediate Action)**
- WireGuard service down
- Server unreachable
- Memory >95%
- Disk >95%
- Payment gateway error

**Alert Delivery:**
- Email: paulhalonen@gmail.com
- SMS: (if configured)
- Admin dashboard: Red banner
- Slack: (if configured)

---

## 📊 DATABASE-DRIVEN AUTOMATION

### **All Automation Rules Stored in Database:**

**Table: automation_workflows**
```sql
CREATE TABLE automation_workflows (
    id INTEGER PRIMARY KEY,
    name TEXT,
    trigger_event TEXT,  -- 'payment_received', 'ticket_created', etc.
    conditions JSON,     -- When to run
    actions JSON,        -- What to do
    is_active BOOLEAN,
    priority INTEGER,
    retry_on_failure BOOLEAN,
    max_retries INTEGER,
    created_at DATETIME
);
```

**Example Workflow:**
```json
{
  "name": "Dedicated Server Provisioning",
  "trigger_event": "payment_received",
  "conditions": {
    "plan_type": "dedicated",
    "amount": ">=39.97"
  },
  "actions": [
    {"type": "contabo_purchase", "params": {"plan": "VPS_10", "location": "customer_preference"}},
    {"type": "wait_for_email", "params": {"from": "no-reply@contabo.com", "timeout": 3600}},
    {"type": "parse_email", "params": {"extract": ["ip", "password", "location"]}},
    {"type": "change_password", "params": {"new_password": "Andassi8"}},
    {"type": "run_remote_script", "params": {"script": "install-wireguard.sh"}},
    {"type": "run_remote_script", "params": {"script": "create-client-config.sh"}},
    {"type": "send_email", "params": {"template": "dedicated_server_ready"}},
    {"type": "update_database", "params": {"table": "customers", "status": "online"}}
  ],
  "is_active": true,
  "priority": 1,
  "retry_on_failure": true,
  "max_retries": 3
}
```

---

## 🔐 API INTEGRATIONS REQUIRED

### **1. PayPal API**
```yaml
Purpose: Payment processing, subscriptions, webhooks
Endpoints:
  - POST /v1/oauth2/token (auth)
  - POST /v1/billing/subscriptions (create)
  - GET /v1/billing/subscriptions/{id} (status)
  - POST /v1/billing/subscriptions/{id}/cancel
  - Webhook listener (all events)
Authentication: OAuth 2.0
Credentials: Stored encrypted in payment_settings table
```

### **2. Contabo API**
```yaml
Purpose: Purchase and manage VPS servers
Endpoints:
  - POST /v1/compute/instances (create server)
  - GET /v1/compute/instances/{id} (status)
  - PUT /v1/compute/instances/{id} (update)
  - DELETE /v1/compute/instances/{id} (cancel)
Authentication: Bearer token
Credentials: TBD (need Contabo API access)
```

### **3. Gmail API**
```yaml
Purpose: Parse incoming Contabo emails
Endpoints:
  - GET /gmail/v1/users/me/messages (list)
  - GET /gmail/v1/users/me/messages/{id} (get)
Authentication: OAuth 2.0
Scopes: gmail.readonly
Account: paulhalonen@gmail.com
```

### **4. SMTP (Outgoing Email)**
```yaml
Purpose: Send customer emails
Server: the-truth-publishing.com
Port: 465 (SSL)
Username: admin@the-truth-publishing.com
Password: A'ndassiAthena8
From: admin@the-truth-publishing.com
From Name: TrueVault VPN Team
```

---

## 📁 FILE STRUCTURE FOR AUTOMATION

```
vpn.the-truth-publishing.com/
├── api/
│   ├── paypal-webhook.php          # PayPal event handler
│   ├── contabo-api.php              # Contabo integration
│   ├── gmail-parser.php             # Email parsing
│   └── automation-engine.php        # Main workflow processor
│
├── admin/
│   ├── provisioning/
│   │   ├── auto-provision.php       # Orchestrator
│   │   ├── change-server-password.py
│   │   └── manual-provision.php     # For shared servers
│   │
│   └── troubleshooting/
│       ├── diagnostics-panel.php    # Admin GUI
│       └── fix-scripts/             # One-click fixes
│           ├── restart-wireguard.sh
│           ├── reset-firewall.sh
│           ├── regenerate-keys.sh
│           └── reboot-server.sh
│
├── server-scripts/                  # Run ON VPS servers
│   ├── install-wireguard.sh
│   ├── create-client-config.sh
│   ├── health-check.sh
│   └── auto-update.sh
│
├── cron/
│   ├── check-servers.php            # Every 5 min
│   ├── process-emails.php           # Every 5 min
│   ├── retry-failed.php             # Every 30 min
│   └── monthly-billing.php          # 1st of month
│
└── databases/
    ├── vpn.db                       # Users, servers, configs
    ├── payments.db                  # Transactions, subscriptions
    ├── automation.db                # Workflows, logs, tasks
    └── support.db                   # Tickets, responses
```

---

## ⏰ CRON JOBS REQUIRED

```cron
# Server health monitoring (every 5 minutes)
*/5 * * * * php /home/vpn/public_html/cron/check-servers.php

# Process incoming emails (every 5 minutes)
*/5 * * * * php /home/vpn/public_html/cron/process-emails.php

# Retry failed automation tasks (every 30 minutes)
*/30 * * * * php /home/vpn/public_html/cron/retry-failed.php

# Daily backup (2 AM)
0 2 * * * /home/vpn/backup.sh

# Monthly billing (1st of month, 12:01 AM)
1 0 1 * * php /home/vpn/public_html/cron/monthly-billing.php

# Weekly reports (Monday 9 AM)
0 9 * * 1 php /home/vpn/public_html/cron/weekly-report.php
```

---

## 🎯 SUCCESS METRICS

**Automation is successful when:**

- ✅ 95%+ of new signups activate within 5 minutes
- ✅ 90%+ of support tickets auto-resolve
- ✅ 99.9% payment success rate
- ✅ Dedicated servers provision in <15 minutes
- ✅ <1 manual intervention per day
- ✅ All servers maintain 99.5%+ uptime
- ✅ Customer satisfaction >4.5/5

---

## 📞 HUMAN INTERVENTION POINTS

**You ONLY need to intervene when:**

1. **Critical server failure** - Server completely offline, can't auto-restart
2. **Payment gateway down** - PayPal API unreachable for >1 hour
3. **Angry customer escalation** - Complaint ticket flagged "high priority"
4. **Contabo account issue** - API fails repeatedly, needs login
5. **Legal/compliance** - DMCA, abuse report, law enforcement request

**Everything else runs automatically.**

---

**🚀 This is the blueprint for a truly automated VPN business.**
