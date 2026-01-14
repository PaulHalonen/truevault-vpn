# DUAL EMAIL SYSTEM - SPECIFICATION

**Version:** 1.0  
**Date:** January 14, 2026  
**Critical:** Owner's personal email stays clean, customer emails separated  

---

## 🎯 SYSTEM OVERVIEW

### The Smart Approach

**TWO SEPARATE EMAIL CHANNELS:**

**1. Gmail API (Internal Alerts - Owner's Personal Email)**
- **Recipient:** paulhalonen@gmail.com (current owner)
- **Purpose:** Critical system notifications ONLY
- **Types:** Server alerts, bandwidth warnings, system errors
- **Volume:** Low (5-10 emails per month)
- **Transferable:** New owner disconnects Gmail, connects their Yahoo

**2. SMTP (Customer-Facing - Business Email)**
- **Recipient:** admin@the-truth-publishing.com
- **Purpose:** ALL customer communications
- **Types:** Support, receipts, welcome emails, reports
- **Volume:** High (100+ emails per month)
- **Transferable:** Just update email in database

---

## 📧 EMAIL CHANNEL 1: GMAIL API (INTERNAL ALERTS)

### What Gets Sent to Owner's Personal Gmail

**ONLY Critical System Alerts:**
```
✓ Server down/offline
✓ Bandwidth approaching limit (90%, 95%, 100%)
✓ Payment processing errors (PayPal API failures)
✓ Database errors
✓ Security alerts (unauthorized access attempts)
✓ System maintenance reminders
✓ Critical bugs/crashes

✗ Customer support emails (NO)
✗ New signups (NO)
✗ Payment receipts to customers (NO)
✗ Welcome emails (NO)
✗ Password resets (NO)
```

### Example Internal Alert Email
```
From: TrueVault VPN System <noreply@truevault.com>
To: paulhalonen@gmail.com
Subject: 🚨 CRITICAL: Dallas Server at 95% Bandwidth Limit

Hi Kah-Len,

CRITICAL ALERT: Your Dallas server has reached 95% of its monthly bandwidth limit.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SERVER: Dallas (Fly.io)
STATUS: Critical - Immediate Action Required
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

USAGE:
• Used: 95.2 GB / 100 GB (95%)
• Remaining: 4.8 GB
• Days Until Reset: 2 days

PROJECTION:
• Expected Total: 106 GB
• Overage: 6 GB ($0.12)

ACTIONS TAKEN AUTOMATICALLY:
✓ All users redirected to NY server
✓ New connections blocked to Dallas
✓ Existing users notified

NO ACTION NEEDED - System handled automatically.

[View Dashboard] [Manually Redirect Remaining Users]

This is an internal alert. Customers were not affected.
```

### Gmail API Configuration
```javascript
// Use Gmail API for internal alerts
const sendInternalAlert = async (subject, body) => {
    const Gmail = require('gmail-api-wrapper'); // Use connected Gmail
    
    await Gmail.sendEmail({
        to: getConfig('owner_email'), // paulhalonen@gmail.com
        from: 'TrueVault VPN System <noreply@truevault.com>',
        subject: subject,
        body: body,
        priority: 'high'
    });
    
    // Log alert
    logEmailSent('gmail_api', 'internal_alert', subject);
};
```

---

## 📨 EMAIL CHANNEL 2: SMTP (CUSTOMER-FACING)

### What Gets Sent via SMTP

**ALL Customer Communications:**
```
✓ Welcome emails to new customers
✓ Payment receipts
✓ Password reset emails
✓ Support ticket responses
✓ Parental control weekly reports
✓ Cancellation confirmations
✓ Device setup instructions
✓ Server maintenance notifications
✓ Marketing emails (if enabled)
✓ Newsletter
```

### Customer Support Email Address
**admin@the-truth-publishing.com**
- Customers send support requests here
- System monitors this inbox (IMAP)
- Auto-creates support tickets
- All responses sent from this address

### Example Customer Email
```
From: TrueVault VPN <admin@the-truth-publishing.com>
To: customer@example.com
Subject: Welcome to TrueVault VPN!

Hi John,

Welcome to TrueVault VPN! Your Family Plan is now active.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ACCOUNT DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Email: customer@example.com
Plan: Family Plan ($14.99/month)
Next Billing: February 14, 2026

QUICK START GUIDE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Download TrueVault VPN app
2. Log in with your email
3. Connect to recommended server
4. You're protected!

FEATURES INCLUDED
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Parental Controls
✓ Screen Time Management
✓ Port Forwarding
✓ Network Scanner
✓ 4 Global Servers

NEED HELP?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Email: admin@the-truth-publishing.com
Website: vpn.the-truth-publishing.com/support

Thanks for choosing TrueVault VPN!

- The TrueVault Team
```

### SMTP Configuration (From Your Screenshot)
```
Incoming Server (IMAP): 993 (SSL)
Outgoing Server (SMTP): 465 (SSL)
Connection Type: SSL
Server Timeout: Short to Long (1 minute)

Username: admin@the-truth-publishing.com
Password: [stored encrypted in database]
Server: mail.the-truth-publishing.com (or your email provider)
```

### SMTP Code Implementation
```php
<?php
// Send customer email via SMTP

function sendCustomerEmail($to, $subject, $body) {
    // Load SMTP settings from database
    $smtp_host = getConfig('smtp_host');
    $smtp_port = getConfig('smtp_port');
    $smtp_user = getConfig('smtp_username');
    $smtp_pass = decryptConfig('smtp_password');
    $from_email = getConfig('customer_support_email'); // admin@the-truth-publishing.com
    $from_name = getConfig('business_name'); // TrueVault VPN
    
    // Configure PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port = $smtp_port; // 465
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($from_email, $from_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        // Send
        $mail->send();
        
        // Log success
        logEmailSent('smtp', 'customer', $to, $subject, 'sent');
        
        return true;
        
    } catch (Exception $e) {
        // Log error
        logEmailSent('smtp', 'customer', $to, $subject, 'failed', $e->getMessage());
        
        // Send internal alert to owner
        sendInternalAlert(
            '⚠️ Customer Email Failed',
            "Failed to send email to {$to}. Error: {$e->getMessage()}"
        );
        
        return false;
    }
}
?>
```

---

## 🔄 EMAIL ROUTING LOGIC

### Decision Tree
```
Email Trigger Event
       |
       ├─ Is it a SYSTEM ALERT?
       │  ├─ YES → Send via Gmail API to owner's personal email
       │  │        (paulhalonen@gmail.com)
       │  │
       │  └─ Types:
       │     • Server down
       │     • Bandwidth limit
       │     • Payment API failure
       │     • Critical errors
       │
       └─ Is it a CUSTOMER COMMUNICATION?
          ├─ YES → Send via SMTP to/from business email
          │        (admin@the-truth-publishing.com)
          │
          └─ Types:
             • Welcome emails
             • Receipts
             • Support responses
             • Password resets
             • Reports
```

### Code Implementation
```php
<?php
function sendEmail($type, $recipient, $subject, $body) {
    // Determine which email channel to use
    
    $internal_alert_types = [
        'server_down',
        'bandwidth_alert',
        'payment_api_error',
        'security_alert',
        'database_error',
        'critical_bug'
    ];
    
    if (in_array($type, $internal_alert_types)) {
        // INTERNAL ALERT → Gmail API to owner
        return sendInternalAlert($subject, $body);
    } else {
        // CUSTOMER EMAIL → SMTP from business address
        return sendCustomerEmail($recipient, $subject, $body);
    }
}

// Usage examples:

// Internal alert (goes to owner's Gmail)
sendEmail(
    'bandwidth_alert',
    null, // no recipient needed, goes to owner
    '🚨 Dallas Server at 95% Bandwidth',
    $alert_body
);

// Customer email (goes via SMTP)
sendEmail(
    'welcome_email',
    'customer@example.com',
    'Welcome to TrueVault VPN!',
    $welcome_body
);
?>
```

---

## 🗄️ DATABASE CONFIGURATION

### Email Settings Table
```sql
CREATE TABLE email_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key TEXT UNIQUE NOT NULL,
    config_value TEXT,
    config_type TEXT, -- 'text', 'encrypted'
    channel TEXT, -- 'gmail', 'smtp', 'both'
    description TEXT,
    is_sensitive BOOLEAN DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Gmail API (Internal Alerts)
INSERT INTO email_config (config_key, config_value, config_type, channel, description, is_sensitive) VALUES
('gmail_enabled', '1', 'text', 'gmail', 'Enable Gmail API for internal alerts', 0),
('owner_email', 'paulhalonen@gmail.com', 'text', 'gmail', 'Owner personal email (receives alerts)', 0),
('gmail_api_connected', '1', 'text', 'gmail', 'Gmail API connection status', 0),

-- SMTP (Customer-Facing)
('smtp_enabled', '1', 'text', 'smtp', 'Enable SMTP for customer emails', 0),
('smtp_host', 'mail.the-truth-publishing.com', 'text', 'smtp', 'SMTP server hostname', 0),
('smtp_port', '465', 'text', 'smtp', 'SMTP port (465 for SSL)', 0),
('smtp_encryption', 'ssl', 'text', 'smtp', 'SSL or TLS', 0),
('smtp_username', 'admin@the-truth-publishing.com', 'text', 'smtp', 'SMTP username', 0),
('smtp_password', '[ENCRYPTED]', 'encrypted', 'smtp', 'SMTP password', 1),

-- Customer Support
('customer_support_email', 'admin@the-truth-publishing.com', 'text', 'smtp', 'Customer-facing email address', 0),
('business_name', 'TrueVault VPN', 'text', 'both', 'Business name for emails', 0),

-- IMAP (Receive customer emails)
('imap_enabled', '1', 'text', 'smtp', 'Enable IMAP to read customer emails', 0),
('imap_host', 'mail.the-truth-publishing.com', 'text', 'smtp', 'IMAP server hostname', 0),
('imap_port', '993', 'text', 'smtp', 'IMAP port (993 for SSL)', 0),
('imap_username', 'admin@the-truth-publishing.com', 'text', 'smtp', 'IMAP username', 0),
('imap_password', '[ENCRYPTED]', 'encrypted', 'smtp', 'IMAP password', 1);
```

### Email Log Table
```sql
CREATE TABLE email_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    channel TEXT, -- 'gmail_api' or 'smtp'
    email_type TEXT, -- 'internal_alert', 'customer_welcome', 'receipt', etc.
    recipient TEXT,
    sender TEXT,
    subject TEXT,
    body TEXT,
    status TEXT, -- 'sent', 'failed', 'queued'
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔄 BUSINESS TRANSFER - EMAIL CONFIGURATION

### Current Owner (Kah-Len - Canada)
```
INTERNAL ALERTS:
• Method: Gmail API
• Email: paulhalonen@gmail.com
• Connected: Yes

CUSTOMER EMAILS:
• Method: SMTP
• Email: admin@the-truth-publishing.com
• Server: mail.the-truth-publishing.com
```

### After Transfer (New Owner - USA, Yahoo User)
```
INTERNAL ALERTS:
• Method: Gmail/Yahoo API (or SMTP)
• Email: newowner@yahoo.com
• Connected: No (new owner connects their account)

CUSTOMER EMAILS:
• Method: SMTP
• Email: support@newdomain.com (or keep admin@the-truth-publishing.com)
• Server: mail.newdomain.com (or keep existing)
```

### Transfer Process (30 Minutes)

**Step 1: Disconnect Current Owner's Gmail**
```
1. Go to Transfer Wizard
2. Click "Disconnect Gmail API"
3. Owner's personal email disconnected ✓
```

**Step 2: Update SMTP Settings**
```
1. Update customer support email:
   admin@the-truth-publishing.com → support@newdomain.com
   
2. Update SMTP credentials:
   Username: support@newdomain.com
   Password: [new owner's password]
   
3. Test email sending ✓
```

**Step 3: New Owner Connects Their Email**
```
1. New owner logs into admin panel
2. Go to Settings > Email Configuration
3. Click "Connect Gmail" or "Connect Yahoo"
4. Authorize connection
5. Internal alerts now go to new owner ✓
```

**Total Time: 5-10 minutes!**

---

## 📊 EMAIL VOLUME ESTIMATES

### Internal Alerts (Gmail API to Owner)
```
MONTHLY VOLUME: 5-10 emails

Breakdown:
• Server alerts: 2-3/month (if any issues)
• Bandwidth warnings: 2-4/month (near limit)
• Payment errors: 0-1/month (rare)
• Security alerts: 0-1/month (rare)

CLEAN INBOX ✓
```

### Customer Emails (SMTP via Business Address)
```
MONTHLY VOLUME: 100-500+ emails

Breakdown:
• Welcome emails: 10-50/month (new signups)
• Payment receipts: 80-100/month (monthly billing)
• Support responses: 5-20/month (customer questions)
• Password resets: 10-30/month (user requests)
• Parental reports: 20-100/month (weekly to parents)
• Marketing: 0-100/month (if enabled)

BUSINESS INBOX ONLY ✓
```

---

## 🛡️ BENEFITS OF DUAL EMAIL SYSTEM

### For Current Owner (Kah-Len)
✅ **Personal Gmail stays clean** (only critical alerts)  
✅ **No customer spam** in personal inbox  
✅ **Instant critical notifications** (server down, etc.)  
✅ **Professional separation** (business vs personal)  

### For Customers
✅ **Professional business email** (admin@the-truth-publishing.com)  
✅ **Consistent sender address** (brand recognition)  
✅ **Better deliverability** (SMTP more reliable for bulk)  
✅ **No confusion** (clear support contact)  

### For Business Transfer
✅ **Easy disconnect** (owner's Gmail disconnected in 1 click)  
✅ **New owner connects their email** (Gmail, Yahoo, Outlook)  
✅ **Customer emails unaffected** (SMTP just needs credential update)  
✅ **Zero downtime** (system continues working)  

### For System Reliability
✅ **Redundancy** (two channels, if one fails, other works)  
✅ **Better deliverability** (right tool for each purpose)  
✅ **Spam prevention** (business email has reputation)  
✅ **Monitoring** (log all emails in database)  

---

## 🔧 IMPLEMENTATION CHECKLIST

### Phase 1: Gmail API (Internal Alerts)
```
□ Connect Gmail API to Claude
□ Create sendInternalAlert() function
□ Test server down alert
□ Test bandwidth alert
□ Test payment error alert
□ Verify emails arrive in paulhalonen@gmail.com
```

### Phase 2: SMTP Setup (Customer Emails)
```
□ Configure SMTP in database (host, port, credentials)
□ Test SMTP connection
□ Create sendCustomerEmail() function
□ Test welcome email
□ Test payment receipt
□ Test password reset
□ Verify emails sent from admin@the-truth-publishing.com
```

### Phase 3: IMAP (Receive Customer Emails)
```
□ Configure IMAP in database
□ Create email monitoring script
□ Auto-create support tickets from emails
□ Test customer email → ticket creation
□ Set up cron job (check every 5 minutes)
```

### Phase 4: Email Routing Logic
```
□ Implement email type detection
□ Route internal alerts → Gmail API
□ Route customer emails → SMTP
□ Test both channels working simultaneously
□ Verify logs in email_log table
```

### Phase 5: Transfer Wizard Integration
```
□ Add "Email Configuration" section
□ Disconnect Gmail button
□ Update SMTP settings form
□ Test transfer flow with dummy data
□ Document for new owner
```

---

## 📝 CUSTOMER SUPPORT EMAIL WORKFLOW

### Auto-Create Tickets from Emails

**Cron Job (Every 5 Minutes):**
```php
<?php
// Check IMAP inbox for new customer emails

function checkCustomerEmails() {
    // Connect to IMAP
    $imap_host = getConfig('imap_host');
    $imap_port = getConfig('imap_port');
    $imap_user = getConfig('imap_username');
    $imap_pass = decryptConfig('imap_password');
    
    $inbox = imap_open(
        "{{$imap_host}:{$imap_port}/imap/ssl}INBOX",
        $imap_user,
        $imap_pass
    );
    
    // Get unseen emails
    $emails = imap_search($inbox, 'UNSEEN');
    
    if ($emails) {
        foreach ($emails as $email_number) {
            $overview = imap_fetch_overview($inbox, $email_number, 0);
            $message = imap_fetchbody($inbox, $email_number, 1);
            
            $from = $overview[0]->from;
            $subject = $overview[0]->subject;
            $body = quoted_printable_decode($message);
            
            // Create support ticket
            createSupportTicket([
                'customer_email' => extractEmail($from),
                'subject' => $subject,
                'message' => $body,
                'source' => 'email',
                'status' => 'open'
            ]);
            
            // Mark as read
            imap_setflag_full($inbox, $email_number, "\\Seen");
        }
    }
    
    imap_close($inbox);
}

// Run via cron every 5 minutes
// */5 * * * * php /path/to/check-customer-emails.php
?>
```

---

## 🚀 API ENDPOINTS

### Email Management
```
POST /api/email/send-internal-alert.php
     Send alert to owner's personal email (Gmail API)

POST /api/email/send-customer-email.php
     Send email to customer (SMTP)

GET  /api/email/test-connection.php?channel=gmail
     Test Gmail API or SMTP connection

GET  /api/email/logs.php
     View email send history

POST /api/email/update-settings.php
     Update SMTP or Gmail settings
```

### Transfer Wizard
```
POST /api/transfer/disconnect-gmail.php
     Disconnect current owner's Gmail

POST /api/transfer/update-smtp.php
     Update SMTP settings for new owner

GET  /api/transfer/email-test.php
     Test both email channels
```

---

## 📧 EMAIL TEMPLATES

### Internal Alert Template (Gmail)
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .alert-critical { background: #ff4444; color: white; padding: 10px; }
        .alert-warning { background: #ffaa00; color: white; padding: 10px; }
        .details { background: #f5f5f5; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="alert-{{level}}">
        🚨 {{alert_type}}: {{title}}
    </div>
    
    <div class="details">
        <h3>Details:</h3>
        {{details}}
    </div>
    
    <p><strong>Actions Taken:</strong></p>
    <ul>
        {{actions}}
    </ul>
    
    <p>
        <a href="{{dashboard_url}}">View Dashboard</a>
    </p>
    
    <p style="color: #666; font-size: 12px;">
        This is an internal system alert sent to the owner only.
        Customers were not affected.
    </p>
</body>
</html>
```

### Customer Email Template (SMTP)
```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #0066cc; color: white; padding: 20px; }
        .content { padding: 20px; }
        .footer { background: #f5f5f5; padding: 15px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔒 TrueVault VPN</h1>
    </div>
    
    <div class="content">
        <p>Hi {{customer_name}},</p>
        
        {{email_body}}
        
        <p>
            Need help?<br>
            Email: <a href="mailto:admin@the-truth-publishing.com">admin@the-truth-publishing.com</a><br>
            Website: <a href="https://vpn.the-truth-publishing.com">vpn.the-truth-publishing.com</a>
        </p>
    </div>
    
    <div class="footer">
        <p>
            You're receiving this because you have a TrueVault VPN account.<br>
            To unsubscribe, log in and update your email preferences.
        </p>
    </div>
</body>
</html>
```

---

**Status:** Complete Specification - Ready for Implementation  
**Priority:** High (critical for system operation)  
**Gmail API:** Already connected ✓  
**SMTP:** Need configuration details  
**Estimated Implementation Time:** 2-3 days
