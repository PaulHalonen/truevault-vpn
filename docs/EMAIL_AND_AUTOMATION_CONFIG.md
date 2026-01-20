# EMAIL CONFIGURATION & AUTOMATION

**Created:** January 19, 2026  
**Status:** REQUIRED - Not Yet Implemented  
**Priority:** CRITICAL - Communication System  

---

## 📧 EMAIL ACCOUNTS

### **Two Separate Email Systems:**

#### **1. Business Operations Email**
```
Email: paulhalonen@gmail.com
Password: Asasasas4!
Purpose: BUSINESS PURCHASES ONLY
```

**Used for:**
- ✅ Contabo server purchase confirmations
- ✅ Fly.io billing notifications
- ✅ PayPal business account
- ✅ GoDaddy hosting
- ❌ NOT for customer communications

---

#### **2. VPN Customer Communications Email**
```
Email: admin@the-truth-publishing.com
Password: A'ndassiAthena8
Domain: the-truth-publishing.com
Purpose: ALL CUSTOMER EMAILS
```

**IMAP Settings (from screenshots):**
```
Server: the-truth-publishing.com
Port: 993 (IMAP with SSL)
Username: admin@the-truth-publishing.com
Password: A'ndassiAthena8
```

**SMTP Settings:**
```
Server: the-truth-publishing.com
Port: 465 (SMTP with SSL)
Username: admin@the-truth-publishing.com
Password: A'ndassiAthena8
```

**Used for:**
- ✅ Welcome emails to new customers
- ✅ VPN configuration files (.conf)
- ✅ Password reset emails
- ✅ Payment receipts
- ✅ Support ticket responses
- ✅ Server maintenance notifications
- ✅ All automated customer communications

---

## 🤖 AUTOMATION REQUIREMENTS

### **Full Business Automation Flow:**

```
CUSTOMER JOURNEY:
├─ Customer visits pricing page
├─ Clicks "Buy Dedicated Server"
├─ Redirects to PayPal
├─ Pays $39.97/month
│
└─ AUTOMATION BEGINS:
    │
    ├─ 1. PayPal Webhook → vpn.the-truth-publishing.com/api/paypal-webhook.php
    │   ├─ Detects payment
    │   ├─ Extracts: customer_id, email, plan_type, location_preference
    │   └─ Triggers: Server Provisioning Workflow
    │
    ├─ 2. Contabo API Purchase
    │   ├─ Uses PayPal funds to buy VPS
    │   ├─ Selects location: US-East, US-Central, or US-West
    │   ├─ Cost: $6.15-$6.75/month (profit margin built in)
    │   └─ Stores order details in database
    │
    ├─ 3. Email Parser (paulhalonen@gmail.com)
    │   ├─ Monitors inbox via IMAP
    │   ├─ Detects Contabo confirmation email
    │   ├─ Extracts: IP address, temp password, location, IPv6
    │   └─ Triggers: Server Configuration Workflow
    │
    ├─ 4. Server Password Standardization
    │   ├─ SSH into new server with temp password
    │   ├─ Changes root password to: Andassi8
    │   ├─ Verifies password change successful
    │   └─ Triggers: WireGuard Installation
    │
    ├─ 5. WireGuard Installation (on VPS)
    │   ├─ Uploads install-wireguard.sh via SSH
    │   ├─ Executes installation script
    │   ├─ Installs WireGuard + dependencies
    │   ├─ Generates server keys
    │   ├─ Configures firewall
    │   ├─ Starts WireGuard service
    │   └─ Returns: Server public key, server ready status
    │
    ├─ 6. Client Configuration Generation (on VPS)
    │   ├─ Uploads create-client-config.sh via SSH
    │   ├─ Executes with customer_id + email
    │   ├─ Generates client keys
    │   ├─ Creates .conf file
    │   ├─ Generates QR code
    │   └─ Returns: .conf file content
    │
    ├─ 7. Customer Notification (admin@the-truth-publishing.com)
    │   ├─ Composes welcome email
    │   ├─ Attaches: truthvault-vpn.conf file
    │   ├─ Includes: Setup instructions + app download links
    │   ├─ Sends via SMTP
    │   └─ Logs email sent
    │
    ├─ 8. Dashboard Update
    │   ├─ Updates database: server_status = "online"
    │   ├─ Stores: server_ip, location, vpn_config, provisioned_at
    │   ├─ Dashboard shows: Green "Online" status
    │   ├─ Provides: Download .conf button
    │   └─ Displays: QR code for mobile setup
    │
    └─ CUSTOMER RECEIVES:
        ├─ Email with .conf file (within 5-10 minutes)
        ├─ Dashboard access with download link
        ├─ QR code for mobile devices
        └─ Fully provisioned dedicated VPN server
```

**Total automation time:** 5-10 minutes from payment to ready server

---

## 🛠️ AUTOMATED TROUBLESHOOTING

### **Connection Issue Detection:**

```
CUSTOMER OPENS SUPPORT TICKET:
├─ Subject: "Can't connect to VPN"
├─ Body: "I get error: handshake failed"
│
└─ AUTOMATION:
    │
    ├─ 1. Keyword Detection
    │   ├─ Scans ticket body for keywords
    │   ├─ Matches: "can't connect", "handshake", "timeout"
    │   └─ Categorizes: CONNECTION_ISSUE
    │
    ├─ 2. Diagnostic Scripts Selection
    │   ├─ CONNECTION_ISSUE → Run 5 diagnostic scripts:
    │   │   ├─ Check WireGuard service status
    │   │   ├─ Verify firewall rules
    │   │   ├─ Test port 51820 accessibility
    │   │   ├─ Check client key validity
    │   │   └─ Verify server load
    │   └─ Returns: Diagnostic results
    │
    ├─ 3. Automated Fixes
    │   ├─ If WireGuard stopped → Restart service
    │   ├─ If firewall blocking → Re-apply rules
    │   ├─ If keys expired → Regenerate keys
    │   ├─ If server overloaded → Alert admin
    │   └─ Log all actions taken
    │
    ├─ 4. Admin GUI Notification
    │   ├─ Shows ticket in dashboard
    │   ├─ Displays diagnostic results
    │   ├─ Lists automated fixes attempted
    │   ├─ Provides manual fix buttons:
    │   │   ├─ [Restart WireGuard]
    │   │   ├─ [Regenerate Keys]
    │   │   ├─ [Reset Firewall]
    │   │   ├─ [Check Logs]
    │   │   └─ [SSH into Server]
    │   └─ Shows step-by-step instructions
    │
    └─ 5. Customer Update Email
        ├─ If auto-fixed → "Issue resolved, please try again"
        ├─ If needs manual → "We're investigating, ETA 1 hour"
        └─ Sent from admin@the-truth-publishing.com
```

---

## 📊 DATABASE SCHEMA ADDITIONS

### **Email Log Table:**

```sql
CREATE TABLE IF NOT EXISTS email_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipient TEXT NOT NULL,
    sender TEXT DEFAULT 'admin@the-truth-publishing.com',
    subject TEXT NOT NULL,
    body TEXT,
    attachment_name TEXT,
    attachment_data BLOB,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'sent',
    smtp_response TEXT,
    customer_id INTEGER,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

### **Server Provisioning Log:**

```sql
CREATE TABLE IF NOT EXISTS provisioning_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    order_id TEXT,
    stage TEXT NOT NULL,  -- 'payment', 'purchase', 'email_received', 'password_changed', etc.
    status TEXT NOT NULL,  -- 'success', 'failed', 'pending'
    message TEXT,
    error TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

### **Automation Tasks Queue:**

```sql
CREATE TABLE IF NOT EXISTS automation_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_type TEXT NOT NULL,  -- 'provision_server', 'send_email', 'run_diagnostic', etc.
    customer_id INTEGER,
    payload TEXT,  -- JSON data
    priority INTEGER DEFAULT 5,  -- 1=highest, 10=lowest
    status TEXT DEFAULT 'pending',  -- 'pending', 'running', 'completed', 'failed'
    attempts INTEGER DEFAULT 0,
    max_attempts INTEGER DEFAULT 3,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    completed_at DATETIME,
    error TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

---

## 🔐 SECURITY CONSIDERATIONS

### **Email Password Storage:**

```php
// NEVER store plaintext passwords in code
// Store in database, encrypted

CREATE TABLE IF NOT EXISTS email_config (
    id INTEGER PRIMARY KEY,
    account_type TEXT NOT NULL,  -- 'business' or 'customer'
    email_address TEXT NOT NULL,
    password_encrypted TEXT NOT NULL,
    smtp_host TEXT,
    smtp_port INTEGER,
    imap_host TEXT,
    imap_port INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

// Encryption helper
function encryptPassword($password) {
    $key = getEncryptionKey();  // Stored in environment variable
    return openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}

function decryptPassword($encrypted) {
    $key = getEncryptionKey();
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
}
```

---

## 🚀 IMPLEMENTATION PRIORITY

### **Phase 1: Critical (Build First)**
1. ✅ Email configuration in database
2. ✅ PayPal webhook handler
3. ✅ Contabo API integration
4. ✅ Email parser (IMAP monitoring)
5. ✅ Server provisioning automation
6. ✅ Customer email sending (SMTP)

### **Phase 2: Important (Build Second)**
7. ⏳ Support ticket keyword detection
8. ⏳ Automated diagnostic scripts
9. ⏳ Admin GUI with manual fix buttons
10. ⏳ Automation queue processor

### **Phase 3: Enhancement (Build Third)**
11. ⏳ Email templates system
12. ⏳ Marketing automation
13. ⏳ Advanced analytics
14. ⏳ Self-healing failsafe systems

---

## 📝 NOTES FOR IMPLEMENTATION

**When building webhook handler:**
- Must verify PayPal signature for security
- Must handle duplicate events (idempotent)
- Must log all webhooks for debugging
- Must respond with 200 OK immediately

**When building email parser:**
- Check inbox every 1 minute
- Mark emails as read after processing
- Store raw email for debugging
- Handle parsing failures gracefully

**When sending customer emails:**
- Always use admin@the-truth-publishing.com as sender
- Include unsubscribe link (legally required)
- Log all sent emails
- Retry failed sends up to 3 times

**Server password security:**
- Standard password: Andassi8
- Only stored in secure database table
- Never hardcoded in PHP files
- Only used for SSH automation

---

## ✅ VERIFICATION CHECKLIST

Before deploying automation:

- [ ] Both email accounts tested and working
- [ ] SMTP sending works from admin@the-truth-publishing.com
- [ ] IMAP reading works from paulhalonen@gmail.com
- [ ] PayPal webhook URL updated in PayPal dashboard
- [ ] Webhook signature verification working
- [ ] Contabo API credentials tested
- [ ] SSH automation tested on real server
- [ ] .conf file generation verified
- [ ] Customer receives email within 10 minutes
- [ ] Dashboard updates correctly
- [ ] All automation logged to database
- [ ] Error handling tested (failed payments, etc.)
- [ ] Admin GUI displays all automation status

---

**🎯 GOAL: 100% hands-off operation from payment to provisioned server**
