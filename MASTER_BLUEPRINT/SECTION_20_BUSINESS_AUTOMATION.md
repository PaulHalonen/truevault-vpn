# SECTION 20: COMPLETE BUSINESS AUTOMATION SYSTEM

**Created:** January 15, 2026  
**Status:** Complete Specification  
**Priority:** CRITICAL - Core Business Operations  
**Complexity:** HIGH  

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [Dual Email System](#dual-email-system)
3. [19 Email Templates](#email-templates)
4. [Automation Engine](#automation-engine)
5. [12 Automated Workflows](#automated-workflows)
6. [Support Ticket System](#support-ticket-system)
7. [Knowledge Base](#knowledge-base)
8. [Scheduled Task Processing](#scheduled-tasks)
9. [Database Schema](#database-schema)
10. [API Endpoints](#api-endpoints)
11. [Admin Interfaces](#admin-interfaces)
12. [Implementation Guide](#implementation-guide)

---

## 🎯 OVERVIEW

### **The Problem**
Running a VPN business requires:
- Customer onboarding emails
- Payment failure handling
- Support ticket management
- Server downtime notifications
- Retention campaigns
- Billing operations

**Manually managing these = 40+ hours/week**

### **The Solution**
Complete automation system that:
- Sends emails automatically (19 templates)
- Processes workflows 24/7 (12 workflows)
- Handles support tickets
- Categorizes and escalates issues
- Retains customers automatically
- Operates without human intervention

**Result: 5-10 minutes/day management time**

### **Key Benefits**
✅ **100% Automated** - Runs 24/7 without human intervention  
✅ **Dual Email System** - SMTP for customers, Gmail for admin  
✅ **19 Email Templates** - Professional, tier-appropriate  
✅ **12 Workflows** - Complete business lifecycle  
✅ **Support Automation** - Auto-categorization and escalation  
✅ **Knowledge Base** - Auto-resolution for common issues  
✅ **Payment Escalation** - Day 0, 3, 7, 8 reminder sequence  
✅ **Single-Person Operation** - Designed for solo management  

### **What Gets Automated**
Every single business process:
- Customer onboarding (welcome → setup → follow-up)
- Payment failures (4-stage escalation)
- Payment successes (invoice → thank you)
- Support tickets (categorize → assign → notify)
- Complaints (apology → flag → follow-up)
- Server alerts (down → notify → restored → report)
- Cancellations (survey → retention → win-back)
- Monthly billing (invoices → emails → retries)
- VIP approvals (SECRET - upgrade → provision)

---

## 📧 DUAL EMAIL SYSTEM

### **Architecture**

**Two Email Methods:**

1. **SMTP** (for customers)
   - Domain: admin@vpn.the-truth-publishing.com
   - Used for: All customer communications
   - Professional appearance
   - Domain-branded

2. **Gmail API** (for admin)
   - Account: paulhalonen@gmail.com
   - Used for: Admin notifications only
   - SMS forwarding enabled
   - Mobile accessible

### **Why Two Systems?**

**SMTP for Customers:**
- Professional sender address
- Domain branding builds trust
- No Google spam filtering issues
- Full control over delivery

**Gmail for Admin:**
- Reliable notifications
- SMS forwarding works
- Mobile app access
- No server dependencies

### **Email Class Structure**

```php
class Email {
    private $db;
    private $settings;
    
    /**
     * Send email using appropriate method
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @param string $type 'customer' or 'admin'
     * @return bool Success status
     */
    public function send($to, $subject, $body, $type = 'customer') {
        if ($type === 'admin') {
            return $this->sendViaGmail($to, $subject, $body);
        } else {
            return $this->sendViaSMTP($to, $subject, $body);
        }
    }
    
    /**
     * Send via SMTP
     */
    private function sendViaSMTP($to, $subject, $body) {
        // Use PHP mail() with custom headers
        // Or PHPMailer for advanced SMTP
        $headers = "From: admin@vpn.the-truth-publishing.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $success = mail($to, $subject, $body, $headers);
        
        $this->log($to, $subject, 'smtp', $success);
        return $success;
    }
    
    /**
     * Send via Gmail API
     */
    private function sendViaGmail($to, $subject, $body) {
        // Use Gmail API with OAuth2
        // Requires: composer require google/apiclient
        
        $client = new Google_Client();
        $client->setAuthConfig('/path/to/credentials.json');
        $client->addScope(Gmail::MAIL_GOOGLE_COM);
        
        $service = new Gmail($client);
        
        // Create message
        $message = new Gmail_Message();
        $rawMessage = $this->createRawMessage($to, $subject, $body);
        $message->setRaw($rawMessage);
        
        try {
            $service->users_messages->send('me', $message);
            $this->log($to, $subject, 'gmail', true);
            return true;
        } catch (Exception $e) {
            $this->log($to, $subject, 'gmail', false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add email to queue for batch sending
     */
    public function queue($to, $subject, $template, $variables = [], $delay = 0) {
        $executeAt = date('Y-m-d H:i:s', strtotime("+$delay seconds"));
        
        $stmt = $this->db->prepare("
            INSERT INTO email_queue 
            (recipient, subject, template_name, template_variables, scheduled_for)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $to,
            $subject,
            $template,
            json_encode($variables),
            $executeAt
        ]);
    }
    
    /**
     * Process email queue (called by cron)
     */
    public function processQueue() {
        $stmt = $this->db->query("
            SELECT * FROM email_queue
            WHERE status = 'pending'
            AND scheduled_for <= datetime('now')
            ORDER BY scheduled_for ASC
            LIMIT 50
        ");
        
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($emails as $email) {
            $template = new EmailTemplate($this->db);
            $body = $template->render(
                $email['template_name'],
                json_decode($email['template_variables'], true)
            );
            
            $success = $this->send(
                $email['recipient'],
                $email['subject'],
                $body,
                $email['email_type']
            );
            
            if ($success) {
                $this->markQueuedEmailSent($email['id']);
            } else {
                $this->incrementQueuedEmailAttempts($email['id']);
            }
        }
    }
    
    /**
     * Log email sending
     */
    private function log($to, $subject, $method, $success, $error = null) {
        $stmt = $this->db->prepare("
            INSERT INTO email_log 
            (method, recipient, subject, status, error_message)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $method,
            $to,
            $subject,
            $success ? 'sent' : 'failed',
            $error
        ]);
    }
}
```

### **Email Configuration (Admin Panel)**

**SMTP Settings:**
```
Host: mail.the-truth-publishing.com
Port: 587 (TLS) or 465 (SSL)
Username: admin@vpn.the-truth-publishing.com
Password: [stored encrypted in system_settings]
From Name: TrueVault VPN
```

**Gmail Settings:**
```
Account: paulhalonen@gmail.com
OAuth2 Client ID: [from Google Cloud Console]
OAuth2 Client Secret: [stored encrypted]
Refresh Token: [obtained during setup]
```

### **Email Queue System**

**Purpose:** Batch sending, rate limiting, retry logic

**Database Schema:**
```sql
CREATE TABLE email_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipient TEXT NOT NULL,
    subject TEXT NOT NULL,
    template_name TEXT NOT NULL,
    template_variables TEXT, -- JSON
    email_type TEXT NOT NULL DEFAULT 'customer', -- 'customer' or 'admin'
    status TEXT NOT NULL DEFAULT 'pending',
    scheduled_for DATETIME NOT NULL,
    sent_at DATETIME,
    attempts INTEGER DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_email_queue_status ON email_queue(status, scheduled_for);
```

**Queue Processing:**
- Cron runs every 5 minutes
- Processes up to 50 emails per run
- Retries failed emails (max 3 attempts)
- Logs all sending activity

---

## 📝 19 EMAIL TEMPLATES

### **Template System Architecture**

**EmailTemplate Class:**
```php
class EmailTemplate {
    private $db;
    
    /**
     * Render email template with variables
     * 
     * @param string $templateName Template identifier
     * @param array $variables Key-value pairs for replacement
     * @return string Rendered HTML email
     */
    public function render($templateName, $variables = []) {
        // Get template from database
        $stmt = $this->db->prepare("
            SELECT * FROM email_templates 
            WHERE name = ? AND active = 1
        ");
        $stmt->execute([$templateName]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            throw new Exception("Template not found: $templateName");
        }
        
        // Replace variables in subject and body
        $subject = $this->replaceVariables($template['subject'], $variables);
        $body = $this->replaceVariables($template['body'], $variables);
        
        // Wrap in HTML template
        return $this->wrapInLayout($body, $subject);
    }
    
    /**
     * Replace {variable} placeholders
     */
    private function replaceVariables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }
    
    /**
     * Wrap email content in branded layout
     */
    private function wrapInLayout($content, $subject) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$subject</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a2e; color: #fff; padding: 20px; text-align: center; }
        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 12px 30px; background: #00d9ff; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 TrueVault VPN</h1>
        </div>
        <div class="content">
            $content
        </div>
        <div class="footer">
            <p>&copy; 2026 TrueVault VPN. All rights reserved.</p>
            <p><a href="https://vpn.the-truth-publishing.com">Website</a> | <a href="https://vpn.the-truth-publishing.com/support">Support</a></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
```

### **Complete Template Library (19 Templates)**

---

#### **1. welcome_basic** (Standard Tier)
**Trigger:** New customer signup  
**Tier:** Standard  
**Subject:** Welcome to TrueVault VPN!

```html
<h2>Welcome {first_name}!</h2>

<p>Thank you for joining TrueVault VPN. Your account is now active!</p>

<p><strong>Your Account Details:</strong></p>
<ul>
    <li>Email: {email}</li>
    <li>Plan: Standard ($9.99/month)</li>
    <li>Device Limit: 3 devices</li>
</ul>

<p><strong>Get Started in 2 Clicks:</strong></p>
<ol>
    <li>Go to Dashboard → Devices</li>
    <li>Click "Add Device" and download config</li>
    <li>That's it! You're protected in 30 seconds.</li>
</ol>

<p><a href="{dashboard_url}" class="button">Go to Dashboard</a></p>

<p>Need help? Check our <a href="{help_url}">Setup Guide</a> or contact support.</p>

<p>Stay secure,<br>The TrueVault Team</p>
```

---

#### **2. welcome_formal** (Pro Tier)
**Trigger:** New customer signup  
**Tier:** Pro  
**Subject:** Welcome to TrueVault VPN - Premium Protection Activated

```html
<h2>Welcome to TrueVault VPN, {first_name}</h2>

<p>Thank you for choosing our Pro plan. Your premium account is now active with advanced features.</p>

<p><strong>Your Account Summary:</strong></p>
<ul>
    <li>Email: {email}</li>
    <li>Plan: Pro ($14.99/month)</li>
    <li>Device Limit: 5 devices</li>
    <li>Features: VPN + Parental Controls + Priority Support</li>
</ul>

<p><strong>Quick Setup Process:</strong></p>
<ol>
    <li>Access your <a href="{dashboard_url}">Dashboard</a></li>
    <li>Navigate to Devices section</li>
    <li>Click "Add Device" and select your platform</li>
    <li>Download configuration file (instant setup)</li>
</ol>

<p><a href="{dashboard_url}" class="button">Access Dashboard</a></p>

<p><strong>Premium Features Available:</strong></p>
<ul>
    <li>✅ Port Forwarding (access home devices remotely)</li>
    <li>✅ Parental Controls (protect your family)</li>
    <li>✅ Camera Dashboard (monitor security cameras)</li>
    <li>✅ Priority Support (24-hour response time)</li>
</ul>

<p>Within the next hour, you'll receive a detailed setup guide with platform-specific instructions.</p>

<p>Best regards,<br>TrueVault VPN Team</p>
```

---

#### **3. welcome_vip** (SECRET - VIP Tier)
**Trigger:** VIP approval (admin only)  
**Tier:** VIP (NEVER ADVERTISED)  
**Subject:** Your Premium Account Is Ready

**IMPORTANT:** This template NEVER mentions "VIP". It's professional/executive styling without advertising the tier.

```html
<h2>Welcome, {first_name}</h2>

<p>Your premium account has been activated with full access to all features.</p>

<p><strong>Your Account:</strong></p>
<ul>
    <li>Email: {email}</li>
    <li>Device Limit: Unlimited</li>
    <li>All Features: Enabled</li>
</ul>

<p><strong>What's Included:</strong></p>
<ul>
    <li>✅ Unlimited devices</li>
    <li>✅ Dedicated server access</li>
    <li>✅ Priority routing</li>
    <li>✅ All premium features</li>
    <li>✅ Direct support line</li>
</ul>

<p><a href="{dashboard_url}" class="button">Access Your Dashboard</a></p>

<p>Your account is ready to use immediately. No additional setup required.</p>

<p>For assistance, contact us directly at paulhalonen@gmail.com.</p>

<p>Welcome aboard,<br>TrueVault VPN</p>
```

---

#### **4. payment_success_basic** (Standard)
**Trigger:** Successful payment  
**Tier:** Standard  
**Subject:** Payment Received - Thank You!

```html
<h2>Payment Confirmed</h2>

<p>Hi {first_name},</p>

<p>Your payment has been received. Thank you!</p>

<p><strong>Payment Details:</strong></p>
<ul>
    <li>Amount: ${amount}</li>
    <li>Invoice: #{invoice_number}</li>
    <li>Date: {payment_date}</li>
    <li>Next Billing: {next_billing_date}</li>
</ul>

<p>Your service continues uninterrupted.</p>

<p><a href="{invoice_url}" class="button">View Invoice</a></p>

<p>Thanks for being a customer,<br>TrueVault VPN</p>
```

---

#### **5. payment_success_formal** (Pro)
**Trigger:** Successful payment  
**Tier:** Pro  
**Subject:** Payment Confirmation - Invoice #{invoice_number}

```html
<h2>Payment Received</h2>

<p>Dear {first_name},</p>

<p>Thank you for your payment. Your Pro subscription has been renewed successfully.</p>

<p><strong>Transaction Summary:</strong></p>
<ul>
    <li>Amount Paid: ${amount}</li>
    <li>Invoice Number: #{invoice_number}</li>
    <li>Payment Method: {payment_method}</li>
    <li>Transaction Date: {payment_date}</li>
    <li>Next Billing Date: {next_billing_date}</li>
</ul>

<p><a href="{invoice_url}" class="button">Download Invoice</a></p>

<p>Your premium service remains active with all features enabled.</p>

<p>Best regards,<br>TrueVault VPN Team</p>
```

---

#### **6. payment_failed_reminder1** (Day 0 - Friendly)
**Trigger:** Payment failure  
**Delay:** Immediate  
**Subject:** Payment Issue - Let's Fix This

```html
<h2>Quick Payment Heads-Up</h2>

<p>Hi {first_name},</p>

<p>We had trouble processing your payment today. This happens sometimes!</p>

<p><strong>What Happened:</strong></p>
<ul>
    <li>Amount: ${amount}</li>
    <li>Card: {card_last4}</li>
    <li>Error: {payment_error}</li>
</ul>

<p><strong>Easy Fix:</strong></p>
<ol>
    <li>Check your card has sufficient funds</li>
    <li>Update payment info in your <a href="{billing_url}">Dashboard</a></li>
    <li>We'll retry automatically in 24 hours</li>
</ol>

<p><a href="{billing_url}" class="button">Update Payment Info</a></p>

<p>Your service continues normally. No interruption yet!</p>

<p>Questions? Reply to this email.</p>

<p>Thanks,<br>TrueVault Team</p>
```

---

#### **7. payment_failed_reminder2** (Day 3 - Urgent)
**Trigger:** Payment still failing  
**Delay:** 3 days after initial failure  
**Subject:** Urgent: Payment Required to Continue Service

```html
<h2>Action Required</h2>

<p>Hi {first_name},</p>

<p>Your payment is still overdue after multiple retry attempts.</p>

<p><strong>Account Status:</strong></p>
<ul>
    <li>Amount Due: ${amount}</li>
    <li>Days Overdue: 3</li>
    <li>Service Status: Active (grace period)</li>
</ul>

<p><strong>⚠️ Important:</strong> If payment isn't received within 4 days, your service will be suspended on {suspension_date}.</p>

<p><a href="{billing_url}" class="button">Update Payment Now</a></p>

<p>We want to keep you connected! Update your payment method today to avoid interruption.</p>

<p>Need help? Contact support: support@vpn.the-truth-publishing.com</p>

<p>TrueVault VPN Team</p>
```

---

#### **8. payment_failed_final** (Day 7 - Final Warning)
**Trigger:** Payment still failing  
**Delay:** 7 days after initial failure  
**Subject:** FINAL NOTICE: Service Suspension Tomorrow

```html
<h2>Final Payment Notice</h2>

<p>Dear {first_name},</p>

<p>This is your final notice regarding overdue payment.</p>

<p><strong>Critical Information:</strong></p>
<ul>
    <li>Amount Due: ${amount}</li>
    <li>Days Overdue: 7</li>
    <li>⚠️ Service Will Suspend: Tomorrow at {suspension_time}</li>
</ul>

<p><strong>To Prevent Suspension:</strong></p>
<ol>
    <li>Update payment method immediately</li>
    <li>Or contact us to arrange payment</li>
</ol>

<p><a href="{billing_url}" class="button">Pay Now to Avoid Suspension</a></p>

<p>After suspension, you'll need to pay outstanding balance plus reactivation fee to restore service.</p>

<p><strong>Contact Us:</strong></p>
<p>Email: support@vpn.the-truth-publishing.com<br>
Phone: [Your phone number]</p>

<p>TrueVault VPN Billing Department</p>
```

---

#### **9. ticket_received** (Support)
**Trigger:** New support ticket created  
**Subject:** Support Ticket #{ticket_id} - We're On It!

```html
<h2>Support Ticket Received</h2>

<p>Hi {first_name},</p>

<p>We've received your support request and we're here to help!</p>

<p><strong>Ticket Details:</strong></p>
<ul>
    <li>Ticket ID: #{ticket_id}</li>
    <li>Subject: {ticket_subject}</li>
    <li>Category: {ticket_category}</li>
    <li>Priority: {ticket_priority}</li>
    <li>Submitted: {created_at}</li>
</ul>

<p><strong>What Happens Next:</strong></p>
<ul>
    <li>We've automatically checked our knowledge base</li>
    <li>{auto_resolution_message}</li>
    <li>Response time: {expected_response_time}</li>
</ul>

<p><a href="{ticket_url}" class="button">View Ticket</a></p>

<p><strong>While You Wait:</strong></p>
<ul>
    <li>Check our <a href="{help_center_url}">Help Center</a></li>
    <li>Browse <a href="{kb_url}">Knowledge Base</a></li>
    <li>View <a href="{faq_url}">Common Questions</a></li>
</ul>

<p>We'll get back to you soon!</p>

<p>TrueVault Support Team</p>
```

---

#### **10. ticket_resolved** (Support)
**Trigger:** Support ticket marked resolved  
**Subject:** Ticket #{ticket_id} Resolved

```html
<h2>Your Issue Has Been Resolved</h2>

<p>Hi {first_name},</p>

<p>Great news! We've resolved your support ticket.</p>

<p><strong>Ticket Summary:</strong></p>
<ul>
    <li>Ticket ID: #{ticket_id}</li>
    <li>Subject: {ticket_subject}</li>
    <li>Resolution: {resolution_summary}</li>
    <li>Resolved: {resolved_at}</li>
</ul>

<p><a href="{ticket_url}" class="button">View Full Resolution</a></p>

<p><strong>Was This Helpful?</strong></p>
<p>We'd love your feedback! In about an hour, you'll receive a brief satisfaction survey.</p>

<p>If you need further assistance, just reply to this email or reopen the ticket.</p>

<p>Thanks for being a valued customer!</p>

<p>TrueVault Support Team</p>
```

---

#### **11. complaint_acknowledge** (Complaints)
**Trigger:** Complaint received  
**Subject:** We're Sorry - Your Feedback Matters

```html
<h2>We Sincerely Apologize</h2>

<p>Dear {first_name},</p>

<p>We're truly sorry you've had a negative experience with TrueVault VPN.</p>

<p><strong>Your Complaint:</strong></p>
<p>{complaint_summary}</p>

<p><strong>Immediate Actions We're Taking:</strong></p>
<ul>
    <li>✅ Your complaint flagged for priority review</li>
    <li>✅ Senior management notified</li>
    <li>✅ Investigation started immediately</li>
    <li>✅ You'll receive personal follow-up within 24 hours</li>
</ul>

<p>Your feedback helps us improve. We take every complaint seriously and use them to make TrueVault better.</p>

<p><strong>While We Investigate:</strong></p>
<ul>
    <li>If you need immediate assistance: paulhalonen@gmail.com</li>
    <li>If you'd like to discuss by phone: [Your phone]</li>
    <li>Reference Number: {complaint_id}</li>
</ul>

<p>We'll make this right.</p>

<p>Sincerely,<br>TrueVault VPN Management</p>
```

---

#### **12. complaint_resolved** (Complaints)
**Trigger:** Complaint marked resolved  
**Subject:** Complaint Resolution - Thank You For Your Patience

```html
<h2>Complaint Resolved</h2>

<p>Dear {first_name},</p>

<p>We wanted to update you on your recent complaint.</p>

<p><strong>What We Did:</strong></p>
<p>{resolution_details}</p>

<p><strong>Changes We've Made:</strong></p>
<ul>
{improvement_list}
</ul>

<p>Your feedback directly led to these improvements. Thank you for helping us serve you better.</p>

<p><strong>What's Next:</strong></p>
<ul>
    <li>{compensation_message}</li>
    <li>We'll follow up in 7 days to ensure satisfaction</li>
    <li>Direct line for future issues: paulhalonen@gmail.com</li>
</ul>

<p>We hope you'll give us another chance to earn your trust.</p>

<p>With gratitude,<br>TrueVault VPN Team</p>
```

---

#### **13. server_down** (Server Alerts)
**Trigger:** Server goes offline  
**Subject:** Service Alert: Temporary Connection Issue

```html
<h2>Service Alert</h2>

<p>Hi {first_name},</p>

<p>We're experiencing a temporary issue with one of our servers.</p>

<p><strong>Affected Server:</strong></p>
<ul>
    <li>Location: {server_location}</li>
    <li>Status: Offline</li>
    <li>Detected: {down_time}</li>
    <li>Type: {incident_type}</li>
</ul>

<p><strong>What This Means:</strong></p>
<ul>
    <li>If you're connected to {server_location}, you may experience disconnection</li>
    <li>Other servers remain fully operational</li>
    <li>Your service continues uninterrupted on alternative servers</li>
</ul>

<p><strong>Recommended Action:</strong></p>
<ol>
    <li>Switch to an alternate server in your Dashboard</li>
    <li>Available servers: {available_servers}</li>
    <li>Your device configs work with all servers</li>
</ol>

<p><a href="{dashboard_url}" class="button">Switch Server Now</a></p>

<p>We're working to restore {server_location} and will notify you when it's back online.</p>

<p>TrueVault Operations Team</p>
```

---

#### **14. server_restored** (Server Alerts)
**Trigger:** Server comes back online  
**Subject:** All Clear: Service Fully Restored

```html
<h2>Service Restored</h2>

<p>Hi {first_name},</p>

<p>Good news! Our {server_location} server is back online and fully operational.</p>

<p><strong>Incident Summary:</strong></p>
<ul>
    <li>Server: {server_location}</li>
    <li>Downtime: {downtime_duration}</li>
    <li>Resolved: {restored_time}</li>
    <li>Cause: {incident_cause}</li>
</ul>

<p><strong>What We Did:</strong></p>
<p>{resolution_summary}</p>

<p><strong>Prevention Measures:</strong></p>
<ul>
{prevention_measures}
</ul>

<p>All services are now running normally. You can safely reconnect to {server_location}.</p>

<p>Thank you for your patience during this brief interruption.</p>

<p>TrueVault Operations Team</p>
```

---

#### **15. cancellation_survey** (Retention)
**Trigger:** User clicks cancel subscription  
**Subject:** We're Sorry to See You Go

```html
<h2>Before You Go...</h2>

<p>Hi {first_name},</p>

<p>We're sad to see you're considering canceling your TrueVault VPN subscription.</p>

<p><strong>Help Us Improve:</strong></p>
<p>Would you mind sharing why you're leaving? Your feedback helps us serve customers better.</p>

<p><a href="{survey_url}" class="button">Take 2-Minute Survey</a></p>

<p><strong>Your Cancellation Details:</strong></p>
<ul>
    <li>Current Plan: {plan_name}</li>
    <li>Account Since: {signup_date}</li>
    <li>Cancellation Date: {cancellation_date}</li>
    <li>Refund Eligible: {refund_status}</li>
</ul>

<p><strong>What Happens Next:</strong></p>
<ul>
    <li>Your service continues until {end_date}</li>
    <li>No additional charges</li>
    <li>You can reactivate anytime before {end_date}</li>
</ul>

<p>In about an hour, we'll send you a special retention offer. If there's anything we can do to keep you as a customer, please let us know.</p>

<p>Best wishes,<br>TrueVault VPN Team</p>
```

---

#### **16. retention_offer** (Retention)
**Trigger:** 1 hour after cancellation survey  
**Subject:** Special Offer Just For You - 50% Off

```html
<h2>We Want You Back!</h2>

<p>Hi {first_name},</p>

<p>We hate to see you go. Here's a special offer to stay with TrueVault:</p>

<p><strong>🎁 EXCLUSIVE OFFER:</strong></p>
<div style="background: #f0f9ff; padding: 20px; border-radius: 8px; text-align: center;">
    <h3>50% OFF Your Next 3 Months</h3>
    <p style="font-size: 24px; color: #00d9ff; font-weight: bold;">${discounted_price}/month</p>
    <p>(Regular price: ${regular_price}/month)</p>
</div>

<p><strong>Why Stay With TrueVault:</strong></p>
<ul>
    <li>✅ Military-grade encryption</li>
    <li>✅ {server_count} servers worldwide</li>
    <li>✅ Zero-logs policy</li>
    <li>✅ 24/7 support</li>
    <li>✅ {device_limit} device connections</li>
</ul>

<p><a href="{accept_offer_url}" class="button">Accept 50% Off Offer</a></p>

<p><strong>This Offer:</strong></p>
<ul>
    <li>Valid for: 48 hours</li>
    <li>Cancels your pending cancellation</li>
    <li>Applies to next 3 months</li>
    <li>No commitment after discounted period</li>
</ul>

<p>This offer expires {offer_expires}. Click above to stay protected at half price!</p>

<p>We hope to keep you as part of the TrueVault family.</p>

<p>TrueVault VPN Team</p>
```

---

#### **17. winback_campaign** (Retention)
**Trigger:** 30 days after cancellation  
**Subject:** We Miss You! Come Back to TrueVault

```html
<h2>We Miss You, {first_name}!</h2>

<p>It's been a month since you left TrueVault VPN. We wanted to reach out and see if you'd like to come back.</p>

<p><strong>What's New Since You Left:</strong></p>
<ul>
{new_features_list}
</ul>

<p><strong>🎁 WELCOME BACK OFFER:</strong></p>
<div style="background: #f0f9ff; padding: 20px; border-radius: 8px; text-align: center;">
    <h3>60% OFF First Month</h3>
    <p style="font-size: 24px; color: #00d9ff; font-weight: bold;">Just ${winback_price}</p>
    <p>Then ${regular_price}/month (cancel anytime)</p>
</div>

<p><a href="{reactivate_url}" class="button">Reactivate Account</a></p>

<p><strong>Your Old Account:</strong></p>
<ul>
    <li>Email: {email}</li>
    <li>Previous Plan: {old_plan}</li>
    <li>Active Until: {was_active_until}</li>
    <li>Total Saved Data: {saved_settings_summary}</li>
</ul>

<p><strong>Everything Still There:</strong></p>
<ul>
    <li>✅ Your device configurations</li>
    <li>✅ Your port forwarding rules</li>
    <li>✅ Your parental control settings</li>
    <li>✅ Your camera dashboard setup</li>
</ul>

<p>Reactivate now and pick up right where you left off - with 60% off your first month!</p>

<p>Offer expires in 7 days.</p>

<p>Hope to see you again soon,<br>TrueVault VPN Team</p>
```

---

#### **18. vip_request_received** (VIP - Admin Notification)
**Trigger:** Someone requests VIP access (extremely rare)  
**Recipient:** Admin only (paulhalonen@gmail.com via Gmail)  
**Subject:** VIP Access Request - Action Required

```html
<h2>VIP Access Request</h2>

<p>A user has requested VIP access (this should be rare).</p>

<p><strong>Requester Information:</strong></p>
<ul>
    <li>Email: {requester_email}</li>
    <li>Name: {requester_name}</li>
    <li>Current Tier: {current_tier}</li>
    <li>Account Since: {signup_date}</li>
    <li>Request Date: {request_date}</li>
    <li>Reason Given: {request_reason}</li>
</ul>

<p><strong>Account History:</strong></p>
<ul>
    <li>Total Paid: ${lifetime_value}</li>
    <li>Tickets Opened: {ticket_count}</li>
    <li>Payment Issues: {payment_failures}</li>
    <li>Status: {account_status}</li>
</ul>

<p><strong>Actions:</strong></p>
<ul>
    <li><a href="{admin_approve_url}">Approve VIP Access</a></li>
    <li><a href="{admin_deny_url}">Deny Request</a></li>
    <li><a href="{admin_contact_url}">Contact User First</a></li>
</ul>

<p><strong>Reminder:</strong> VIP approval grants:</p>
<ul>
    <li>Unlimited devices</li>
    <li>Payment-free access</li>
    <li>Dedicated server (if seige235@yahoo.com)</li>
    <li>Priority routing</li>
    <li>Direct support line</li>
</ul>

<p>TrueVault Admin System</p>
```

---

#### **19. vip_welcome_package** (VIP - SECRET)
**Trigger:** Admin approves VIP manually  
**Subject:** Your Premium Access Is Active

**CRITICAL:** This is the SECRET VIP welcome. NEVER mentions "VIP" anywhere. Professional executive styling only.

```html
<h2>Welcome, {first_name}</h2>

<p>Your premium account has been activated with full system access.</p>

<p><strong>Your Account Status:</strong></p>
<ul>
    <li>Email: {email}</li>
    <li>Access Level: Premium (Full)</li>
    <li>Device Limit: Unlimited</li>
    <li>All Features: Enabled</li>
    <li>Support Priority: Highest</li>
</ul>

<p><strong>Premium Features Now Active:</strong></p>
<ul>
    <li>✅ Unlimited device connections</li>
    <li>✅ Dedicated server routing</li>
    <li>✅ Priority bandwidth allocation</li>
    <li>✅ Advanced port forwarding</li>
    <li>✅ Enhanced security features</li>
    <li>✅ Direct support channel</li>
    <li>✅ Zero billing requirements</li>
</ul>

<p><a href="{dashboard_url}" class="button">Access Your Dashboard</a></p>

<p><strong>Premium Support:</strong></p>
<p>For any assistance, contact us directly:</p>
<ul>
    <li>Email: paulhalonen@gmail.com</li>
    <li>Priority Line: [Your direct number]</li>
    <li>Response Time: Immediate</li>
</ul>

<p><strong>Your Dedicated Resources:</strong></p>
<ul>
{dedicated_resources_list}
</ul>

<p>Your account is fully provisioned and ready for immediate use. No additional configuration needed.</p>

<p>Welcome to the premium experience.</p>

<p>Best regards,<br>TrueVault VPN Executive Team</p>
```

---

### **Template Variable System**

**Common Variables Available in All Templates:**
```php
$variables = [
    // User info
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'customer_id' => $user['id'],
    
    // Account info
    'plan_name' => $user['tier'],
    'signup_date' => $user['created_at'],
    'account_status' => $user['status'],
    
    // URLs
    'dashboard_url' => 'https://vpn.the-truth-publishing.com/dashboard/',
    'billing_url' => 'https://vpn.the-truth-publishing.com/dashboard/billing.php',
    'support_url' => 'https://vpn.the-truth-publishing.com/support/',
    'help_url' => 'https://vpn.the-truth-publishing.com/help/',
    
    // System
    'current_date' => date('F j, Y'),
    'current_year' => date('Y'),
    'company_name' => 'TrueVault VPN',
    'support_email' => 'support@vpn.the-truth-publishing.com',
    'admin_email' => 'admin@vpn.the-truth-publishing.com'
];
```

**Template-Specific Variables:**
- Payment emails: `amount`, `invoice_number`, `payment_date`, `card_last4`
- Support emails: `ticket_id`, `ticket_subject`, `resolution_summary`
- Server emails: `server_location`, `downtime_duration`, `incident_cause`
- VIP emails: `dedicated_resources_list`, `priority_level`

---

## 🤖 AUTOMATION ENGINE

### **Architecture**

**AutomationEngine Class:**

```php
class AutomationEngine {
    private $db;
    private $email;
    
    /**
     * Trigger a workflow
     * 
     * @param string $workflowName Name of workflow to run
     * @param array $data Variables to pass to workflow
     * @return int Execution ID
     */
    public function triggerWorkflow($workflowName, $data = []) {
        // Log execution start
        $executionId = $this->logExecution($workflowName, $data);
        
        // Get workflow definition
        $workflow = $this->getWorkflow($workflowName);
        
        if (!$workflow) {
            throw new Exception("Workflow not found: $workflowName");
        }
        
        // Execute workflow steps
        try {
            foreach ($workflow['steps'] as $step) {
                if (isset($step['delay']) && $step['delay'] > 0) {
                    // Schedule for later
                    $this->scheduleStep($executionId, $step, $data);
                } else {
                    // Execute immediately
                    $this->executeStep($step, $data);
                }
            }
            
            $this->markExecutionComplete($executionId);
        } catch (Exception $e) {
            $this->markExecutionFailed($executionId, $e->getMessage());
            throw $e;
        }
        
        return $executionId;
    }
    
    /**
     * Process scheduled workflow steps (called by cron)
     */
    public function processScheduledSteps() {
        $stmt = $this->db->query("
            SELECT * FROM scheduled_workflow_steps
            WHERE status = 'pending'
            AND execute_at <= datetime('now')
            ORDER BY execute_at ASC
            LIMIT 100
        ");
        
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($steps as $step) {
            try {
                $data = json_decode($step['step_data'], true);
                $this->executeStep($step, $data);
                $this->markStepComplete($step['id']);
            } catch (Exception $e) {
                $this->markStepFailed($step['id'], $e->getMessage());
            }
        }
    }
    
    /**
     * Execute a single workflow step
     */
    private function executeStep($step, $data) {
        switch ($step['type']) {
            case 'email':
                $this->sendEmail($step, $data);
                break;
            case 'database':
                $this->updateDatabase($step, $data);
                break;
            case 'notification':
                $this->sendNotification($step, $data);
                break;
            case 'webhook':
                $this->callWebhook($step, $data);
                break;
            default:
                throw new Exception("Unknown step type: " . $step['type']);
        }
    }
    
    /**
     * Schedule a step for future execution
     */
    private function scheduleStep($executionId, $step, $data) {
        $executeAt = date('Y-m-d H:i:s', strtotime("+{$step['delay']} seconds"));
        
        $stmt = $this->db->prepare("
            INSERT INTO scheduled_workflow_steps
            (execution_id, step_name, step_data, execute_at)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $executionId,
            $step['name'],
            json_encode($data),
            $executeAt
        ]);
    }
    
    /**
     * Send email step
     */
    private function sendEmail($step, $data) {
        $this->email->send(
            $data['recipient'],
            $step['subject'],
            $this->renderTemplate($step['template'], $data),
            $step['email_type'] ?? 'customer'
        );
    }
    
    /**
     * Update database step
     */
    private function updateDatabase($step, $data) {
        $sql = $step['query'];
        $params = [];
        
        // Replace placeholders with actual values
        foreach ($data as $key => $value) {
            $sql = str_replace(":{$key}", '?', $sql);
            $params[] = $value;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * Log workflow execution
     */
    private function logExecution($workflowName, $data) {
        $stmt = $this->db->prepare("
            INSERT INTO workflow_executions
            (workflow_name, trigger_event, user_id, execution_data)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $workflowName,
            $data['trigger_event'] ?? 'manual',
            $data['user_id'] ?? null,
            json_encode($data)
        ]);
        
        return $this->db->lastInsertId();
    }
}
```

### **Workflow Definition Structure**

```php
$workflows = [
    'newCustomerOnboarding' => [
        'name' => 'New Customer Onboarding',
        'trigger' => 'user_signup',
        'steps' => [
            [
                'name' => 'send_welcome',
                'type' => 'email',
                'template' => 'welcome_basic', // or welcome_formal based on tier
                'subject' => 'Welcome to TrueVault VPN!',
                'email_type' => 'customer',
                'delay' => 0
            ],
            [
                'name' => 'send_setup_guide',
                'type' => 'email',
                'template' => 'setup_guide',
                'subject' => 'Quick Setup Guide',
                'email_type' => 'customer',
                'delay' => 3600 // 1 hour
            ],
            [
                'name' => 'schedule_followup',
                'type' => 'email',
                'template' => 'followup_checkin',
                'subject' => 'How's everything going?',
                'email_type' => 'customer',
                'delay' => 86400 // 24 hours
            ]
        ]
    ],
    
    'paymentFailedEscalation' => [
        'name' => 'Payment Failed Escalation',
        'trigger' => 'payment_failed',
        'steps' => [
            [
                'name' => 'grace_period_start',
                'type' => 'database',
                'query' => "UPDATE users SET status = 'grace_period' WHERE id = :user_id",
                'delay' => 0
            ],
            [
                'name' => 'reminder1',
                'type' => 'email',
                'template' => 'payment_failed_reminder1',
                'subject' => 'Payment Issue - Let's Fix This',
                'email_type' => 'customer',
                'delay' => 0
            ],
            [
                'name' => 'reminder2',
                'type' => 'email',
                'template' => 'payment_failed_reminder2',
                'subject' => 'Urgent: Payment Required',
                'email_type' => 'customer',
                'delay' => 259200 // 3 days
            ],
            [
                'name' => 'final_warning',
                'type' => 'email',
                'template' => 'payment_failed_final',
                'subject' => 'FINAL NOTICE: Service Suspension Tomorrow',
                'email_type' => 'customer',
                'delay' => 604800 // 7 days
            ],
            [
                'name' => 'suspend_service',
                'type' => 'database',
                'query' => "UPDATE users SET status = 'suspended' WHERE id = :user_id",
                'delay' => 691200 // 8 days
            ]
        ]
    ]
];
```

---

## 🔄 12 AUTOMATED WORKFLOWS

### **Complete Workflow Specifications**

---

#### **Workflow 1: New Customer Onboarding**
**Trigger:** User completes signup  
**Duration:** 24 hours  
**Steps:** 3

```php
'newCustomerOnboarding' => [
    'steps' => [
        // Step 1: Immediate welcome (tier-appropriate)
        [
            'delay' => 0,
            'type' => 'email',
            'template' => function($user) {
                return $user['tier'] === 'standard' ? 'welcome_basic' : 'welcome_formal';
            },
            'subject' => 'Welcome to TrueVault VPN!'
        ],
        
        // Step 2: Setup guide (1 hour later)
        [
            'delay' => 3600,
            'type' => 'email',
            'template' => 'setup_guide_detailed',
            'subject' => 'Your Complete Setup Guide'
        ],
        
        // Step 3: Follow-up check-in (24 hours later)
        [
            'delay' => 86400,
            'type' => 'email',
            'template' => 'onboarding_checkin',
            'subject' => 'Quick Check-In: How's Everything Going?'
        ]
    ]
]
```

**Trigger Code:**
```php
// In /api/register.php after successful signup
$automation = new AutomationEngine($db);
$automation->triggerWorkflow('newCustomerOnboarding', [
    'user_id' => $newUserId,
    'email' => $email,
    'first_name' => $firstName,
    'tier' => $tier,
    'signup_date' => date('Y-m-d H:i:s')
]);
```

---

#### **Workflow 2: Payment Failed Escalation**
**Trigger:** PayPal payment fails  
**Duration:** 8 days  
**Steps:** 5

```php
'paymentFailedEscalation' => [
    'steps' => [
        // Step 1: Set grace period immediately
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                // Update user status
                $db->prepare("UPDATE users SET status = 'grace_period' WHERE id = ?")
                   ->execute([$data['user_id']]);
            }
        ],
        
        // Step 2: Friendly reminder (Day 0)
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'payment_failed_reminder1',
            'subject' => 'Payment Issue - Let's Fix This'
        ],
        
        // Step 3: Urgent notice (Day 3)
        [
            'delay' => 259200, // 3 days
            'type' => 'email',
            'template' => 'payment_failed_reminder2',
            'subject' => 'Urgent: Payment Required to Continue Service'
        ],
        
        // Step 4: Final warning (Day 7)
        [
            'delay' => 604800, // 7 days
            'type' => 'email',
            'template' => 'payment_failed_final',
            'subject' => 'FINAL NOTICE: Service Suspension Tomorrow'
        ],
        
        // Step 5: Suspend service (Day 8)
        [
            'delay' => 691200, // 8 days
            'type' => 'database',
            'action' => function($data) {
                // Suspend user account
                $db->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")
                   ->execute([$data['user_id']]);
                
                // Delete all device configs
                $db->prepare("DELETE FROM user_devices WHERE user_id = ?")
                   ->execute([$data['user_id']]);
            }
        ]
    ]
]
```

**Trigger Code:**
```php
// In /api/paypal-webhook.php when payment fails
$automation = new AutomationEngine($db);
$automation->triggerWorkflow('paymentFailedEscalation', [
    'user_id' => $userId,
    'email' => $user['email'],
    'first_name' => $user['first_name'],
    'amount' => $invoice['amount'],
    'invoice_number' => $invoice['id'],
    'card_last4' => $paymentMethod['last4'],
    'payment_error' => $webhookData['error_message']
]);
```

---

#### **Workflow 3: Payment Success**
**Trigger:** PayPal payment succeeds  
**Duration:** Immediate  
**Steps:** 3

```php
'paymentSuccess' => [
    'steps' => [
        // Step 1: Generate invoice immediately
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                // Create invoice record
                $db->prepare("
                    INSERT INTO invoices 
                    (user_id, amount, status, payment_date, invoice_number)
                    VALUES (?, ?, 'paid', datetime('now'), ?)
                ")->execute([$data['user_id'], $data['amount'], $data['invoice_number']]);
            }
        ],
        
        // Step 2: Send thank you email
        [
            'delay' => 0,
            'type' => 'email',
            'template' => function($user) {
                return $user['tier'] === 'standard' ? 'payment_success_basic' : 'payment_success_formal';
            },
            'subject' => 'Payment Received - Thank You!'
        ],
        
        // Step 3: Update status to active
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("UPDATE users SET status = 'active' WHERE id = ?")
                   ->execute([$data['user_id']]);
            }
        ]
    ]
]
```

---

#### **Workflow 4: Support Ticket Created**
**Trigger:** User creates support ticket  
**Duration:** 24 hours  
**Steps:** 6

```php
'supportTicketCreated' => [
    'steps' => [
        // Step 1: Auto-categorize ticket
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                // Categorize based on keywords
                $category = categorizeTicket($data['subject'], $data['description']);
                $priority = $data['is_vip'] ? 'urgent' : 'normal';
                
                $db->prepare("
                    UPDATE support_tickets 
                    SET category = ?, priority = ?
                    WHERE id = ?
                ")->execute([$category, $priority, $data['ticket_id']]);
            }
        ],
        
        // Step 2: Check knowledge base for solution
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                // Search KB for matching articles
                $articles = searchKnowledgeBase($data['subject'], $data['description']);
                if (!empty($articles)) {
                    // Auto-attach suggested articles to ticket
                    foreach ($articles as $article) {
                        addTicketNote($data['ticket_id'], "Suggested KB: {$article['title']}");
                    }
                }
            }
        ],
        
        // Step 3: Send acknowledgment email
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'ticket_received',
            'subject' => 'Support Ticket #' . $data['ticket_id'] . ' - We're On It!'
        ],
        
        // Step 4: Notify admin if VIP or high priority
        [
            'delay' => 0,
            'type' => 'conditional',
            'condition' => function($data) {
                return $data['is_vip'] || $data['priority'] === 'urgent';
            },
            'action' => function($data) {
                // Send Gmail notification to admin
                $email = new Email($db);
                $email->send(
                    'paulhalonen@gmail.com',
                    "URGENT: Support Ticket #{$data['ticket_id']}",
                    "VIP customer needs immediate attention...",
                    'admin'
                );
            }
        ],
        
        // Step 5: Auto-escalate if unresolved after 24 hours
        [
            'delay' => 86400, // 24 hours
            'type' => 'database',
            'action' => function($data) {
                // Check if still open
                $ticket = getTicket($data['ticket_id']);
                if ($ticket['status'] === 'open') {
                    // Escalate priority
                    $db->prepare("
                        UPDATE support_tickets 
                        SET priority = 'high'
                        WHERE id = ?
                    ")->execute([$data['ticket_id']]);
                    
                    // Notify admin
                    $email = new Email($db);
                    $email->send(
                        'paulhalonen@gmail.com',
                        "Escalated: Ticket #{$data['ticket_id']}",
                        "Ticket unresolved for 24 hours...",
                        'admin'
                    );
                }
            }
        ]
    ]
]
```

---

#### **Workflow 5: Support Ticket Resolved**
**Trigger:** Admin marks ticket resolved  
**Duration:** 1 hour  
**Steps:** 3

```php
'supportTicketResolved' => [
    'steps' => [
        // Step 1: Send resolution notification immediately
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'ticket_resolved',
            'subject' => 'Ticket #' . $data['ticket_id'] . ' Resolved'
        ],
        
        // Step 2: Send satisfaction survey (1 hour later)
        [
            'delay' => 3600, // 1 hour
            'type' => 'email',
            'template' => 'satisfaction_survey',
            'subject' => 'How Did We Do? Quick Survey'
        ],
        
        // Step 3: Log completion
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    UPDATE support_tickets 
                    SET resolved_at = datetime('now')
                    WHERE id = ?
                ")->execute([$data['ticket_id']]);
            }
        ]
    ]
]
```

---

#### **Workflow 6: Complaint Handling**
**Trigger:** User submits complaint  
**Duration:** 7 days  
**Steps:** 4

```php
'complaintHandling' => [
    'steps' => [
        // Step 1: Send immediate apology
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'complaint_acknowledge',
            'subject' => 'We're Sorry - Your Feedback Matters'
        ],
        
        // Step 2: Flag for manual review (high priority)
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    INSERT INTO admin_notifications
                    (type, priority, title, message, created_at)
                    VALUES ('complaint', 'high', ?, ?, datetime('now'))
                ")->execute([
                    "Complaint from {$data['first_name']}",
                    $data['complaint_summary']
                ]);
            }
        ],
        
        // Step 3: Notify admin immediately (Gmail)
        [
            'delay' => 0,
            'type' => 'email',
            'email_type' => 'admin',
            'to' => 'paulhalonen@gmail.com',
            'subject' => 'COMPLAINT: ' . $data['first_name'],
            'body' => "Complaint received from {$data['email']}...\n\n{$data['complaint_summary']}"
        ],
        
        // Step 4: Schedule 7-day follow-up
        [
            'delay' => 604800, // 7 days
            'type' => 'email',
            'template' => 'complaint_followup',
            'subject' => 'Following Up on Your Recent Feedback'
        ]
    ]
]
```

---

#### **Workflow 7: Server Down Alert**
**Trigger:** Server health check fails  
**Duration:** Immediate  
**Steps:** 4

```php
'serverDownAlert' => [
    'steps' => [
        // Step 1: Send admin alert immediately (SMS + Email)
        [
            'delay' => 0,
            'type' => 'email',
            'email_type' => 'admin',
            'to' => 'paulhalonen@gmail.com',
            'subject' => '🚨 SERVER DOWN: ' . $data['server_name'],
            'body' => "Server {$data['server_name']} went offline at {$data['down_time']}"
        ],
        
        // Step 2: Check if planned maintenance
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $maintenance = checkPlannedMaintenance($data['server_name']);
                $data['is_planned'] = !empty($maintenance);
            }
        ],
        
        // Step 3: Notify customers (if unplanned)
        [
            'delay' => 300, // 5 minutes (confirm it's actually down)
            'type' => 'conditional',
            'condition' => function($data) {
                return !$data['is_planned'];
            },
            'action' => function($data) {
                // Email all users connected to this server
                $users = getUsersOnServer($data['server_name']);
                foreach ($users as $user) {
                    $email = new Email($db);
                    $email->send(
                        $user['email'],
                        'Service Alert: Temporary Connection Issue',
                        renderTemplate('server_down', [
                            'first_name' => $user['first_name'],
                            'server_location' => $data['server_name']
                        ])
                    );
                }
            }
        ],
        
        // Step 4: Log incident
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    INSERT INTO server_incidents
                    (server_name, incident_type, started_at, is_planned)
                    VALUES (?, 'downtime', datetime('now'), ?)
                ")->execute([$data['server_name'], $data['is_planned'] ? 1 : 0]);
            }
        ]
    ]
]
```

---

#### **Workflow 8: Server Restored**
**Trigger:** Server health check passes after being down  
**Duration:** Immediate  
**Steps:** 3

```php
'serverRestored' => [
    'steps' => [
        // Step 1: Send "all clear" to customers
        [
            'delay' => 0,
            'type' => 'email',
            'action' => function($data) {
                // Email all users who were notified of downtime
                $users = getUsersNotifiedOfDowntime($data['server_name']);
                foreach ($users as $user) {
                    $email = new Email($db);
                    $email->send(
                        $user['email'],
                        'All Clear: Service Fully Restored',
                        renderTemplate('server_restored', [
                            'first_name' => $user['first_name'],
                            'server_location' => $data['server_name'],
                            'downtime_duration' => $data['downtime_duration']
                        ])
                    );
                }
            }
        ],
        
        // Step 2: Generate incident report
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    UPDATE server_incidents
                    SET resolved_at = datetime('now'),
                        downtime_duration = ?,
                        resolution_summary = ?
                    WHERE server_name = ? AND resolved_at IS NULL
                ")->execute([
                    $data['downtime_duration'],
                    $data['resolution_summary'],
                    $data['server_name']
                ]);
            }
        ],
        
        // Step 3: Log restoration
        [
            'delay' => 0,
            'type' => 'email',
            'email_type' => 'admin',
            'to' => 'paulhalonen@gmail.com',
            'subject' => '✅ SERVER RESTORED: ' . $data['server_name'],
            'body' => "Server {$data['server_name']} back online. Downtime: {$data['downtime_duration']}"
        ]
    ]
]
```

---

#### **Workflow 9: Cancellation Request**
**Trigger:** User clicks cancel subscription  
**Duration:** 30 days  
**Steps:** 4

```php
'cancellationRequest' => [
    'steps' => [
        // Step 1: Send exit survey immediately
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'cancellation_survey',
            'subject' => 'We're Sorry to See You Go'
        ],
        
        // Step 2: Send retention offer (1 hour later)
        [
            'delay' => 3600, // 1 hour
            'type' => 'email',
            'template' => 'retention_offer',
            'subject' => 'Special Offer Just For You - 50% Off'
        ],
        
        // Step 3: Schedule actual cancellation (2 days if not reversed)
        [
            'delay' => 172800, // 2 days
            'type' => 'conditional',
            'condition' => function($data) {
                // Check if user accepted retention offer
                $user = getUser($data['user_id']);
                return $user['status'] === 'canceling'; // Still canceling
            },
            'action' => function($data) {
                // Actually cancel
                $db->prepare("UPDATE users SET status = 'canceled' WHERE id = ?")
                   ->execute([$data['user_id']]);
                
                // Delete devices
                $db->prepare("DELETE FROM user_devices WHERE user_id = ?")
                   ->execute([$data['user_id']]);
            }
        ],
        
        // Step 4: Start win-back campaign (30 days after cancellation)
        [
            'delay' => 2592000, // 30 days
            'type' => 'email',
            'template' => 'winback_campaign',
            'subject' => 'We Miss You! Come Back to TrueVault'
        ]
    ]
]
```

---

#### **Workflow 10: Monthly Invoicing**
**Trigger:** Last day of month (cron job)  
**Duration:** 1 day  
**Steps:** 4

```php
'monthlyInvoicing' => [
    'steps' => [
        // Step 1: Generate all invoices
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function() {
                // Get all active subscriptions
                $users = $db->query("
                    SELECT * FROM users 
                    WHERE status = 'active' 
                    AND tier IN ('standard', 'pro')
                ")->fetchAll();
                
                foreach ($users as $user) {
                    // Create invoice
                    $amount = $user['tier'] === 'standard' ? 9.99 : 14.99;
                    $db->prepare("
                        INSERT INTO invoices
                        (user_id, amount, status, due_date)
                        VALUES (?, ?, 'pending', date('now', '+7 days'))
                    ")->execute([$user['id'], $amount]);
                }
            }
        ],
        
        // Step 2: Send invoice emails (tier-appropriate)
        [
            'delay' => 3600, // 1 hour after generation
            'type' => 'email',
            'action' => function() {
                $invoices = getPendingInvoices();
                foreach ($invoices as $invoice) {
                    $user = getUser($invoice['user_id']);
                    $template = $user['tier'] === 'standard' ? 
                        'invoice_basic' : 'invoice_formal';
                    
                    $email = new Email($db);
                    $email->send(
                        $user['email'],
                        "Invoice #{$invoice['id']} for " . date('F Y'),
                        renderTemplate($template, [
                            'first_name' => $user['first_name'],
                            'invoice_number' => $invoice['id'],
                            'amount' => $invoice['amount'],
                            'due_date' => $invoice['due_date']
                        ])
                    );
                }
            }
        ],
        
        // Step 3: Schedule payment retries
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function() {
                // Will be processed by PayPal subscription system
                // Just log that retries are scheduled
                logEvent('monthly_billing', 'Payment retries scheduled');
            }
        ],
        
        // Step 4: Generate revenue report for admin
        [
            'delay' => 86400, // 24 hours (end of billing cycle)
            'type' => 'email',
            'email_type' => 'admin',
            'to' => 'paulhalonen@gmail.com',
            'subject' => 'Monthly Revenue Report - ' . date('F Y'),
            'body' => generateRevenueReport()
        ]
    ]
]
```

---

#### **Workflow 11: VIP Request Received**
**Trigger:** Someone requests VIP (rare - for completeness only)  
**Duration:** Immediate  
**Steps:** 2

```php
'vipRequestReceived' => [
    'steps' => [
        // Step 1: Log the request
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    INSERT INTO vip_requests
                    (user_id, reason, status, created_at)
                    VALUES (?, ?, 'pending', datetime('now'))
                ")->execute([$data['user_id'], $data['reason']]);
            }
        ],
        
        // Step 2: Notify admin (Gmail for SMS forwarding)
        [
            'delay' => 0,
            'type' => 'email',
            'email_type' => 'admin',
            'to' => 'paulhalonen@gmail.com',
            'subject' => 'VIP Access Request - Action Required',
            'template' => 'vip_request_received'
        ]
    ]
]
```

**IMPORTANT:** VIP requests should be extremely rare. Most VIPs are added manually by admin, not through user requests.

---

#### **Workflow 12: VIP Approved**
**Trigger:** Admin approves VIP manually  
**Duration:** Immediate  
**Steps:** 4

```php
'vipApproved' => [
    'steps' => [
        // Step 1: Upgrade user tier
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    UPDATE users 
                    SET tier = 'vip',
                        status = 'active',
                        payment_required = 0
                    WHERE id = ?
                ")->execute([$data['user_id']]);
            }
        ],
        
        // Step 2: Send SECRET welcome email (executive styling, NO "VIP" mention)
        [
            'delay' => 0,
            'type' => 'email',
            'template' => 'vip_welcome_package',
            'subject' => 'Your Premium Account Is Ready'
        ],
        
        // Step 3: Provision VIP resources
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                // If user is seige235@yahoo.com, assign St. Louis dedicated server
                if ($data['email'] === 'seige235@yahoo.com') {
                    $db->prepare("
                        UPDATE user_server_assignments
                        SET dedicated_server = 'stlouis-vip'
                        WHERE user_id = ?
                    ")->execute([$data['user_id']]);
                }
                
                // Unlock all features
                $db->prepare("
                    INSERT INTO user_features (user_id, feature, enabled)
                    SELECT ?, feature, 1 FROM available_features
                    WHERE tier = 'vip'
                ")->execute([$data['user_id']]);
            }
        ],
        
        // Step 4: Log VIP activation
        [
            'delay' => 0,
            'type' => 'database',
            'action' => function($data) {
                $db->prepare("
                    INSERT INTO audit_log
                    (event_type, user_id, details, created_at)
                    VALUES ('vip_activated', ?, ?, datetime('now'))
                ")->execute([$data['user_id'], json_encode($data)]);
            }
        ]
    ]
]
```

**CRITICAL REMINDER:** VIP system is SECRET. Never advertise publicly. Never mention "VIP" in subject lines or visible content.

---

## 🎫 SUPPORT TICKET SYSTEM

### **Database Schema**

```sql
-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subject TEXT NOT NULL,
    description TEXT NOT NULL,
    category TEXT, -- Auto-assigned: 'billing', 'technical', 'account', 'general'
    priority TEXT NOT NULL DEFAULT 'normal', -- 'low', 'normal', 'high', 'urgent'
    status TEXT NOT NULL DEFAULT 'open', -- 'open', 'in_progress', 'resolved', 'closed'
    assigned_to TEXT, -- Admin username or NULL
    auto_resolution_attempted INTEGER DEFAULT 0,
    auto_resolution_success INTEGER DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Ticket messages
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER, -- NULL if staff message
    is_staff INTEGER DEFAULT 0,
    message TEXT NOT NULL,
    attachments TEXT, -- JSON array of file paths
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
);

-- Knowledge base articles
CREATE TABLE IF NOT EXISTS knowledge_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    category TEXT NOT NULL,
    keywords TEXT, -- Comma-separated for searching
    view_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Auto-resolution tracking
CREATE TABLE IF NOT EXISTS ticket_auto_resolutions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    kb_article_id INTEGER NOT NULL,
    suggested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted INTEGER DEFAULT 0,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id),
    FOREIGN KEY (kb_article_id) REFERENCES knowledge_base(id)
);

CREATE INDEX idx_tickets_user ON support_tickets(user_id, status);
CREATE INDEX idx_tickets_status ON support_tickets(status, priority);
CREATE INDEX idx_tickets_category ON support_tickets(category);
CREATE INDEX idx_kb_category ON knowledge_base(category);
CREATE INDEX idx_kb_keywords ON knowledge_base(keywords);
```

### **Auto-Categorization System**

```php
class TicketCategorizer {
    private $categories = [
        'billing' => [
            'payment', 'invoice', 'charge', 'refund', 'subscription',
            'paypal', 'billing', 'cancel', 'upgrade', 'downgrade',
            'price', 'cost', 'fee'
        ],
        'technical' => [
            'connect', 'connection', 'error', 'vpn', 'wireguard',
            'config', 'server', 'slow', 'speed', 'disconnect',
            'install', 'setup', 'device', 'port', 'forwarding'
        ],
        'account' => [
            'password', 'login', 'email', 'username', 'access',
            'forgot', 'reset', 'locked', 'suspended', 'disabled'
        ]
    ];
    
    /**
     * Auto-categorize ticket based on content
     */
    public function categorize($subject, $description) {
        $text = strtolower($subject . ' ' . $description);
        $scores = [];
        
        foreach ($this->categories as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score++;
                }
            }
            $scores[$category] = $score;
        }
        
        // Return category with highest score
        arsort($scores);
        return key($scores);
    }
    
    /**
     * Determine priority based on user and content
     */
    public function determinePriority($userId, $text) {
        // VIP users get urgent priority
        $user = getUser($userId);
        if ($user['tier'] === 'vip') {
            return 'urgent';
        }
        
        // Check for urgent keywords
        $urgentKeywords = ['urgent', 'critical', 'emergency', 'asap', 'immediately'];
        $text = strtolower($text);
        foreach ($urgentKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'high';
            }
        }
        
        return 'normal';
    }
}
```

### **Knowledge Base Search**

```php
class KnowledgeBaseSearch {
    private $db;
    
    /**
     * Search KB for relevant articles
     * 
     * @param string $subject Ticket subject
     * @param string $description Ticket description
     * @return array Matching articles
     */
    public function search($subject, $description) {
        $keywords = $this->extractKeywords($subject . ' ' . $description);
        
        if (empty($keywords)) {
            return [];
        }
        
        // Search KB articles
        $placeholders = str_repeat('?,', count($keywords) - 1) . '?';
        $query = "
            SELECT *, 
                   (LENGTH(keywords) - LENGTH(REPLACE(keywords, ?, ''))) as relevance
            FROM knowledge_base
            WHERE " . implode(' OR ', array_fill(0, count($keywords), "keywords LIKE ?")) . "
            ORDER BY relevance DESC, helpful_count DESC
            LIMIT 5
        ";
        
        $params = array_merge($keywords, array_map(function($k) {
            return "%$k%";
        }, $keywords));
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Extract meaningful keywords from text
     */
    private function extractKeywords($text) {
        // Remove common words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'were'];
        $words = preg_split('/\s+/', strtolower($text));
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        return array_unique($words);
    }
    
    /**
     * Suggest auto-resolution
     */
    public function suggestResolution($ticketId, $articles) {
        if (empty($articles)) {
            return false;
        }
        
        // Insert auto-resolution suggestions
        foreach ($articles as $article) {
            $this->db->prepare("
                INSERT INTO ticket_auto_resolutions
                (ticket_id, kb_article_id)
                VALUES (?, ?)
            ")->execute([$ticketId, $article['id']]);
        }
        
        return true;
    }
}
```

---

## ⏱️ SCHEDULED TASK PROCESSING

### **Cron Job Setup**

**Add to server crontab:**
```bash
*/5 * * * * php /path/to/cron/process-automation.php
```

**Create `/cron/process-automation.php`:**
```php
<?php
require_once '../includes/db.php';
require_once '../includes/AutomationEngine.php';
require_once '../includes/Email.php';

// Initialize
$db = new Database();
$automation = new AutomationEngine($db);
$email = new Email($db);

// Process scheduled workflow steps
echo "[" . date('Y-m-d H:i:s') . "] Processing scheduled workflow steps...\n";
$processed = $automation->processScheduledSteps();
echo "Processed $processed steps.\n";

// Process email queue
echo "[" . date('Y-m-d H:i:s') . "] Processing email queue...\n";
$sent = $email->processQueue();
echo "Sent $sent emails.\n";

// Check server health
echo "[" . date('Y-m-d H:i:s') . "] Checking server health...\n";
$servers = checkServerHealth();
foreach ($servers as $server) {
    if (!$server['is_online'] && !$server['was_offline']) {
        // Server just went down
        $automation->triggerWorkflow('serverDownAlert', [
            'server_name' => $server['name'],
            'down_time' => date('Y-m-d H:i:s')
        ]);
    } elseif ($server['is_online'] && $server['was_offline']) {
        // Server just came back
        $automation->triggerWorkflow('serverRestored', [
            'server_name' => $server['name'],
            'restored_time' => date('Y-m-d H:i:s'),
            'downtime_duration' => $server['downtime']
        ]);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Automation processing complete.\n\n";
```

### **Server Health Check**

```php
function checkServerHealth() {
    $servers = [
        ['name' => 'New York', 'ip' => '66.94.103.91', 'port' => 51820],
        ['name' => 'St. Louis VIP', 'ip' => '144.126.133.253', 'port' => 51820],
        ['name' => 'Dallas', 'ip' => '66.241.124.4', 'port' => 51820],
        ['name' => 'Toronto', 'ip' => '66.241.125.247', 'port' => 51820]
    ];
    
    $results = [];
    
    foreach ($servers as $server) {
        $wasOffline = getServerStatus($server['name']) === 'offline';
        $isOnline = checkServerOnline($server['ip'], $server['port']);
        
        $results[] = [
            'name' => $server['name'],
            'is_online' => $isOnline,
            'was_offline' => $wasOffline
        ];
        
        // Update status in database
        updateServerStatus($server['name'], $isOnline ? 'online' : 'offline');
    }
    
    return $results;
}

function checkServerOnline($ip, $port) {
    $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
    if ($socket) {
        fclose($socket);
        return true;
    }
    return false;
}
```

---

## 📊 ADMIN INTERFACES

### **Automation Dashboard**

**File:** `/admin/automation-dashboard.php`

```php
<?php
require_once '../includes/session.php';
requireAdmin();

$db = new Database();

// Get statistics
$stats = [
    'workflows_today' => $db->query("
        SELECT COUNT(*) as count FROM workflow_executions
        WHERE DATE(started_at) = DATE('now')
    ")->fetch()['count'],
    
    'emails_today' => $db->query("
        SELECT COUNT(*) as count FROM email_log
        WHERE DATE(sent_at) = DATE('now')
    ")->fetch()['count'],
    
    'scheduled_tasks' => $db->query("
        SELECT COUNT(*) as count FROM scheduled_workflow_steps
        WHERE status = 'pending'
    ")->fetch()['count'],
    
    'open_tickets' => $db->query("
        SELECT COUNT(*) as count FROM support_tickets
        WHERE status = 'open'
    ")->fetch()['count']
];

// Get recent workflow executions
$recentWorkflows = $db->query("
    SELECT * FROM workflow_executions
    ORDER BY started_at DESC
    LIMIT 20
")->fetchAll();

// Get pending scheduled tasks
$scheduledTasks = $db->query("
    SELECT * FROM scheduled_workflow_steps
    WHERE status = 'pending'
    ORDER BY execute_at ASC
    LIMIT 50
")->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Automation Dashboard</title>
    <style>
        /* Modern dashboard styling */
        body { font-family: system-ui; background: #0f0f1a; color: #fff; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .stat-card { background: #1a1a2e; padding: 20px; border-radius: 10px; }
        .stat-number { font-size: 48px; color: #00d9ff; font-weight: bold; }
        .table { background: #1a1a2e; padding: 20px; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🤖 Automation Dashboard</h1>
    
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['workflows_today'] ?></div>
            <div>Workflows Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['emails_today'] ?></div>
            <div>Emails Sent Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['scheduled_tasks'] ?></div>
            <div>Scheduled Tasks</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['open_tickets'] ?></div>
            <div>Open Tickets</div>
        </div>
    </div>
    
    <div class="table">
        <h2>Recent Workflow Executions</h2>
        <table>
            <tr>
                <th>Workflow</th>
                <th>Trigger</th>
                <th>Status</th>
                <th>Started</th>
                <th>Duration</th>
            </tr>
            <?php foreach ($recentWorkflows as $workflow): ?>
                <tr>
                    <td><?= h($workflow['workflow_name']) ?></td>
                    <td><?= h($workflow['trigger_event']) ?></td>
                    <td><?= h($workflow['status']) ?></td>
                    <td><?= h($workflow['started_at']) ?></td>
                    <td>
                        <?php if ($workflow['completed_at']): ?>
                            <?= round((strtotime($workflow['completed_at']) - strtotime($workflow['started_at'])), 2) ?>s
                        <?php else: ?>
                            Running...
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="table">
        <h2>Scheduled Tasks</h2>
        <table>
            <tr>
                <th>Step</th>
                <th>Execute At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($scheduledTasks as $task): ?>
                <tr>
                    <td><?= h($task['step_name']) ?></td>
                    <td><?= h($task['execute_at']) ?></td>
                    <td><?= h($task['status']) ?></td>
                    <td>
                        <button onclick="executeNow(<?= $task['id'] ?>)">Execute Now</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
```

---

## 🎯 IMPLEMENTATION SUMMARY

### **What This System Provides**

**Complete Business Automation:**
- ✅ 24/7 operation
- ✅ Zero manual intervention
- ✅ Professional customer experience
- ✅ Comprehensive logging
- ✅ Error handling and recovery
- ✅ Scalable architecture

**Dual Email System:**
- ✅ SMTP for customers (domain-branded)
- ✅ Gmail for admin (SMS forwarding)
- ✅ 19 professional templates
- ✅ Queue processing
- ✅ Retry logic

**12 Automated Workflows:**
- ✅ Customer onboarding
- ✅ Payment processing (success & failure)
- ✅ Support tickets
- ✅ Complaints
- ✅ Server monitoring
- ✅ Retention campaigns
- ✅ Monthly billing
- ✅ SECRET VIP system

**Support Automation:**
- ✅ Auto-categorization
- ✅ Knowledge base search
- ✅ Priority assignment
- ✅ Admin escalation
- ✅ Auto-resolution suggestions

**Result:**
**5-10 minutes/day** management time  
**Professional 24/7 operation**  
**Single-person business**  
**Transferable system**

---

## 🚀 NEXT STEPS

### **Implementation Order:**

1. **Day 7 Morning:** Email system (3-4 hours)
   - Create email tables
   - Build Email class
   - Build EmailTemplate class
   - Configure SMTP & Gmail
   - Test both email methods

2. **Day 7 Afternoon:** Automation engine (4-5 hours)
   - Create automation tables
   - Build AutomationEngine class
   - Build Workflows class
   - Test workflow triggers

3. **Day 7 Evening:** Support tickets (3-4 hours)
   - Create support tables
   - Build TicketCategorizer
   - Build KnowledgeBase search
   - Create user & admin interfaces

4. **Day 7 Final:** Cron setup (1 hour)
   - Create cron script
   - Add to server crontab
   - Test scheduled processing
   - Monitor logs

**Total Day 7:** ~11-14 hours

---

**Status:** Complete specification for business automation  
**Lines:** ~13,800 lines  
**Next:** Implementation & testing  

**IMPORTANT REMINDERS:**
- VIP system is COMPLETELY SECRET
- NO public advertising for VIP
- seige235@yahoo.com gets dedicated server
- Only admin can add VIP emails
- VIP emails use executive styling (no "VIP" branding)

---

**Created:** January 15, 2026  
**Last Updated:** January 15, 2026  
**Status:** âœ… COMPLETE & READY FOR IMPLEMENTATION


---

# SECTION 20B: SUPPORT AUTOMATION TIERED SYSTEM

**Added:** January 24, 2026  
**Status:** Complete Specification  
**Priority:** CRITICAL - One-Person Operation Enabler  

---

## 🎯 OVERVIEW: 5-TIER FAILSAFE SYSTEM

### **The Problem**
One person managing hundreds of support tickets = impossible without automation.

### **The Solution**
5-tier failsafe system that resolves 80-90% of issues WITHOUT human intervention.

```
TICKET COMES IN
      ↓
┌─────────────────────────────────────────────────────────┐
│ TIER 1: AUTO-RESOLUTION (No human needed)               │
│ - Knowledge base keyword match → Auto-reply sent        │
│ - Customer gets resolution steps immediately            │
│ - Ticket marked "auto-resolved, pending confirmation"   │
│ - If customer replies "didn't work" → escalate Tier 3   │
└─────────────────────────────────────────────────────────┘
      ↓ (no KB match)
┌─────────────────────────────────────────────────────────┐
│ TIER 2: SELF-SERVICE REDIRECT (No human needed)         │
│ - System detects intent (password, billing, config)     │
│ - Auto-reply: "You can do this yourself! [LINK]"        │
│ - Links to self-service portal action                   │
│ - Ticket auto-closed if customer completes action       │
└─────────────────────────────────────────────────────────┘
      ↓ (can't self-serve)
┌─────────────────────────────────────────────────────────┐
│ TIER 3: CANNED RESPONSE (1-click human action)          │
│ - Dashboard shows suggested canned response             │
│ - Admin clicks "Send" - done in 2 seconds               │
│ - Variables auto-filled ({name}, {ticket_id}, etc.)     │
└─────────────────────────────────────────────────────────┘
      ↓ (no canned response fits)
┌─────────────────────────────────────────────────────────┐
│ TIER 4: MANUAL RESPONSE (Human writes reply)            │
│ - Only for unique/complex issues                        │
│ - After resolving, option to "Save as Canned Response"  │
│ - System learns from your manual resolutions            │
└─────────────────────────────────────────────────────────┘
      ↓ (VIP customer detected)
┌─────────────────────────────────────────────────────────┐
│ TIER 5: VIP ESCALATION (Immediate priority)             │
│ - Bypasses auto-resolution, goes straight to top        │
│ - SMS/email alert to admin                              │
│ - Dedicated server issues = highest priority            │
└─────────────────────────────────────────────────────────┘
```

---

## 📊 DATABASE SCHEMA: SUPPORT TABLES

### **Table: support_tickets**
```sql
CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number TEXT UNIQUE,              -- TV-2026-00001
    customer_id INTEGER,
    customer_email TEXT NOT NULL,
    customer_name TEXT,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    category TEXT,                          -- billing, technical, account, general
    priority TEXT DEFAULT 'normal',         -- low, normal, high, urgent
    status TEXT DEFAULT 'new',              -- new, auto_resolved, pending_confirmation, 
                                           -- awaiting_response, in_progress, resolved, closed
    tier_resolved INTEGER,                  -- 1-5 which tier resolved it
    resolution_method TEXT,                 -- auto, self_service, canned, manual, vip
    assigned_to TEXT,
    is_vip INTEGER DEFAULT 0,
    auto_resolution_id INTEGER,             -- FK to knowledge_base if auto-resolved
    canned_response_id INTEGER,             -- FK to canned_responses if used
    self_service_action TEXT,               -- What self-service action was suggested
    customer_rating INTEGER,                -- 1-5 satisfaction
    response_count INTEGER DEFAULT 0,
    first_response_at TEXT,
    resolved_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

### **Table: ticket_responses**
```sql
CREATE TABLE IF NOT EXISTS ticket_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    sender_type TEXT NOT NULL,              -- customer, admin, system
    message TEXT NOT NULL,
    is_auto_response INTEGER DEFAULT 0,
    canned_response_id INTEGER,
    attachments TEXT,                       -- JSON array of file paths
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
);
```

### **Table: canned_responses**
```sql
CREATE TABLE IF NOT EXISTS canned_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,                 -- billing, technical, account, general
    title TEXT NOT NULL,                    -- Short name for admin to identify
    trigger_keywords TEXT,                  -- Comma-separated keywords that suggest this response
    subject TEXT,                           -- Optional email subject override
    body TEXT NOT NULL,                     -- HTML with {variables}
    variables TEXT,                         -- JSON: list of variables used
    times_used INTEGER DEFAULT 0,
    success_rate REAL DEFAULT 0.0,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

### **Table: self_service_actions**
```sql
CREATE TABLE IF NOT EXISTS self_service_actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action_key TEXT UNIQUE NOT NULL,        -- reset_password, download_config, etc.
    display_name TEXT NOT NULL,
    description TEXT,
    trigger_keywords TEXT,                  -- Keywords that suggest this action
    portal_url TEXT NOT NULL,               -- Deep link to portal action
    instructions TEXT,                      -- Step-by-step for customer
    category TEXT,                          -- account, billing, technical
    is_active INTEGER DEFAULT 1,
    times_used INTEGER DEFAULT 0
);
```

### **Table: ticket_escalations**
```sql
CREATE TABLE IF NOT EXISTS ticket_escalations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    from_tier INTEGER,
    to_tier INTEGER,
    reason TEXT,
    escalated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
);
```

---

## 📖 TIER 1: KNOWLEDGE BASE AUTO-RESOLUTION

### **How It Works**
1. New ticket arrives
2. System extracts keywords from subject + message
3. Matches against knowledge_base.keywords
4. If confidence >= 60%, sends auto-reply with resolution steps
5. Ticket marked "auto_resolved, pending_confirmation"
6. Customer can reply "didn't work" to escalate

### **Auto-Resolution Class**
```php
class KnowledgeBaseResolver {
    private $db;
    private $confidenceThreshold = 0.6;
    
    public function attemptResolution($ticketId) {
        $ticket = $this->getTicket($ticketId);
        $content = strtolower($ticket['subject'] . ' ' . $ticket['message']);
        
        // Extract meaningful keywords (remove stop words)
        $keywords = $this->extractKeywords($content);
        
        // Find best matching KB entry
        $match = $this->findBestMatch($keywords);
        
        if ($match && $match['score'] >= $this->confidenceThreshold) {
            // Send auto-resolution email
            $this->sendAutoResolution($ticket, $match['entry']);
            
            // Update ticket status
            $this->updateTicket($ticketId, [
                'status' => 'auto_resolved',
                'tier_resolved' => 1,
                'resolution_method' => 'auto',
                'auto_resolution_id' => $match['entry']['id']
            ]);
            
            // Track usage
            $this->incrementKBUsage($match['entry']['id']);
            
            return true;
        }
        
        return false;
    }
    
    private function extractKeywords($text) {
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 
                      'in', 'with', 'to', 'for', 'of', 'not', 'be', 'are', 'was', 'were',
                      'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
                      'could', 'should', 'may', 'might', 'must', 'can', 'i', 'my', 'me',
                      'you', 'your', 'it', 'its', 'this', 'that', 'these', 'those'];
        
        $words = preg_split('/\s+/', $text);
        $keywords = [];
        
        foreach ($words as $word) {
            $word = preg_replace('/[^a-z0-9]/', '', $word);
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    private function findBestMatch($keywords) {
        $entries = $this->getAllActiveEntries();
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($entries as $entry) {
            $entryKeywords = array_map('trim', explode(',', strtolower($entry['keywords'])));
            $matchCount = count(array_intersect($keywords, $entryKeywords));
            $score = $matchCount / max(count($entryKeywords), 1);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = ['entry' => $entry, 'score' => $score];
            }
        }
        
        return $bestMatch;
    }
}
```

### **25+ Knowledge Base Entries**

**Billing (6 entries):**
| Keywords | Question | Resolution |
|----------|----------|------------|
| payment, failed, declined, card | Why did my payment fail? | 1. Check card expiration 2. Verify funds 3. Update billing address 4. Try different card |
| refund, money back, cancel payment | How do I get a refund? | 30-day guarantee: Dashboard > Billing > Request Refund |
| change, plan, upgrade, downgrade | How do I change my plan? | Dashboard > Account > Change Plan (prorated billing) |
| invoice, receipt, billing history | Where are my invoices? | Dashboard > Billing > Invoice History |
| pricing, cost, price, how much | What are your prices? | Personal $9.97/mo, Family $14.97/mo, Dedicated $39.97/mo |
| promo, code, discount, coupon | How do I use a promo code? | Enter during checkout or Dashboard > Billing > Apply Code |

**Technical (8 entries):**
| Keywords | Question | Resolution |
|----------|----------|------------|
| slow, speed, connection, lag | VPN is slow | 1. Switch to closer server 2. Try WireGuard protocol 3. Check base internet speed |
| connect, can't, unable, error | Can't connect | 1. Check internet 2. Restart app 3. Try different server 4. Switch protocol |
| leak, ip, dns, exposed | IP/DNS leak detected | Dashboard > Security > Leak Test. Enable Kill Switch + DNS Protection |
| kill, switch, disconnect | What is kill switch? | Blocks internet if VPN drops. Settings > Security > Kill Switch |
| split, tunneling, exclude, app | Split tunneling | Settings > Split Tunneling > Add apps (Windows/Android only) |
| streaming, netflix, blocked | Streaming not working | 1. Clear cookies 2. Try streaming-optimized server 3. Different region |
| protocol, wireguard, openvpn | Which protocol to use? | WireGuard = fastest, OpenVPN = most compatible, IKEv2 = best for mobile |
| router, setup, whole house | Router VPN setup | Dashboard > Setup Guides > Router (may reduce speeds) |

**Account (6 entries):**
| Keywords | Question | Resolution |
|----------|----------|------------|
| email, change, update email | Change email address | Dashboard > Account > Change Email (verification required) |
| password, reset, forgot | Reset password | Dashboard > Account > Change Password OR Login > Forgot Password |
| 2fa, authenticator, two factor | Enable 2FA | Dashboard > Security > Two-Factor Auth (save backup codes!) |
| delete, account, close | Delete my account | Cancel subscription first, then Account > Delete Account |
| device, limit, too many | Device limit reached | Personal=5, Family=10, Dedicated=Unlimited. Remove old devices first |
| username, change name | Change username | Dashboard > Profile > Edit (changes display name only) |

**Setup (3 entries):**
| Keywords | Question | Resolution |
|----------|----------|------------|
| install, download, setup | How to install | Dashboard > Download > Select device > Follow installer |
| config, wireguard, file | Download config file | Dashboard > Devices > Download Config (select device type) |
| first, connection, start | First connection | Install app > Login > Click Connect > Select server |

**General (2 entries):**
| Keywords | Question | Resolution |
|----------|----------|------------|
| what, vpn, how work | What is a VPN? | Encrypts traffic + masks IP for privacy, security, geo-access |
| log, logging, privacy, store | Logging policy | Strict no-logs. Only store: email, payment status (no activity) |

---

## 🔧 TIER 2: SELF-SERVICE PORTAL

### **9 Self-Service Actions**

| Action Key | Display Name | Ticket-Deflecting | Portal URL |
|------------|--------------|-------------------|------------|
| reset_password | Reset Password | HIGH | /self-service/reset-password |
| download_configs | Download VPN Configs | HIGH | /self-service/download-configs |
| view_invoices | View Invoices | MEDIUM | /self-service/view-invoices |
| update_payment | Update Payment Method | HIGH | /self-service/update-payment |
| view_devices | View Connected Devices | MEDIUM | /self-service/view-devices |
| regenerate_keys | Regenerate WireGuard Keys | HIGH | /self-service/regenerate-keys |
| pause_subscription | Pause Subscription | MEDIUM | /self-service/pause-subscription |
| cancel_subscription | Cancel Subscription | MEDIUM | /self-service/cancel-subscription |
| connection_test | Run Connection Test | HIGH | /self-service/connection-test |

### **Intent Detection**
```php
class SelfServiceDetector {
    private $actionMappings = [
        'reset_password' => ['password', 'forgot', 'login', 'can\'t sign in', 'locked out', 'reset'],
        'download_configs' => ['config', 'download', 'setup', 'install', 'wireguard file', 'ovpn'],
        'view_invoices' => ['invoice', 'receipt', 'billing history', 'payment history', 'statement'],
        'update_payment' => ['card', 'payment method', 'update billing', 'new card', 'credit card'],
        'regenerate_keys' => ['new key', 'regenerate', 'keypair', 'certificate', 'key expired'],
        'connection_test' => ['not working', 'can\'t connect', 'connection issue', 'troubleshoot', 'diagnose'],
        'pause_subscription' => ['pause', 'hold', 'temporary stop', 'vacation'],
        'cancel_subscription' => ['cancel', 'stop', 'end subscription', 'unsubscribe'],
        'view_devices' => ['devices', 'connected', 'sessions', 'logged in where']
    ];
    
    public function detectIntent($content) {
        $content = strtolower($content);
        
        foreach ($this->actionMappings as $action => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    return $action;
                }
            }
        }
        
        return null;
    }
}
```

### **Auto-Redirect Email Template**
```html
<h2>Hi {first_name}!</h2>

<p>Good news! You can handle this yourself in just a few clicks:</p>

<div style="background: #f0f8ff; padding: 20px; border-radius: 10px; text-align: center;">
    <h3>{action_name}</h3>
    <p>{action_description}</p>
    <a href="{portal_url}" style="display: inline-block; padding: 12px 30px; 
       background: linear-gradient(135deg, #00d4ff, #7b2cbf); color: white; 
       text-decoration: none; border-radius: 8px; font-weight: bold;">
       Do It Now →
    </a>
</div>

<p style="margin-top: 20px;">
    <strong>Still need help?</strong> Just reply to this email and a human will assist you.
</p>
```

---

## 💬 TIER 3: CANNED RESPONSES

### **20+ Canned Responses**

**Billing (5):**
```
1. PAYMENT_RETRY
   Title: Payment Retry Instructions
   Body: "Your payment didn't go through. Please try: 1) Update card... 2) Check funds..."

2. REFUND_CONFIRMED
   Title: Refund Confirmation
   Body: "Your refund of {amount} has been processed. Allow 3-5 business days..."

3. PLAN_UPGRADE_CONFIRMED
   Title: Plan Upgrade Confirmation
   Body: "You've been upgraded to {new_plan}! New features: ..."

4. INVOICE_RESENT
   Title: Invoice Resent
   Body: "Your invoice #{invoice_number} has been resent to {email}..."

5. PROMO_APPLIED
   Title: Promo Code Applied
   Body: "Code {promo_code} applied! You saved {discount_amount}..."
```

**Technical (8):**
```
6. SERVER_SWITCH_GUIDE
   Title: Server Switching Guide
   Body: "To get better speeds: 1) Open app 2) Click server list 3) Choose closest..."

7. CLEAR_CACHE_REINSTALL
   Title: Clear Cache & Reinstall
   Body: "Let's do a fresh start: 1) Uninstall app 2) Restart device 3) Download fresh..."

8. FIREWALL_CHECK
   Title: Firewall/Antivirus Check
   Body: "Your security software may be blocking VPN. Allow TrueVault in: ..."

9. PROTOCOL_CHANGE
   Title: Protocol Change Guide
   Body: "Try switching protocols: Settings > Protocol > WireGuard (fastest)..."

10. SPEED_TEST_INSTRUCTIONS
    Title: Speed Test Instructions
    Body: "Let's diagnose: 1) Disconnect VPN 2) Run speedtest.net 3) Connect VPN..."

11. ROUTER_RESET
    Title: Router Reset Guide
    Body: "Router issues? Try: 1) Unplug router 30 seconds 2) Reconnect 3) Wait 2 min..."

12. DNS_LEAK_FIX
    Title: DNS Leak Fix
    Body: "To fix DNS leaks: Dashboard > Security > Enable DNS Leak Protection..."

13. KILL_SWITCH_ENABLE
    Title: Kill Switch Enable
    Body: "Enable kill switch for safety: Settings > Security > Kill Switch ON..."
```

**Account (5):**
```
14. PASSWORD_RESET_SENT
    Title: Password Reset Sent
    Body: "Password reset link sent to {email}. Check spam folder. Link expires in 1 hour..."

15. 2FA_SETUP_GUIDE
    Title: 2FA Setup Guide
    Body: "To enable 2FA: 1) Download authenticator app 2) Dashboard > Security..."

16. DEVICE_LIMIT_REACHED
    Title: Device Limit Reached
    Body: "You've hit your {device_limit} device limit. Remove old devices: Dashboard > Devices..."

17. ACCOUNT_DELETION_CONFIRMED
    Title: Account Deletion Confirmed
    Body: "Your account is scheduled for deletion. Data removed in 30 days..."

18. EMAIL_CHANGE_CONFIRMED
    Title: Email Change Confirmed
    Body: "Your email has been changed to {new_email}. Verification sent..."
```

**General (2):**
```
19. THANK_YOU_PATIENCE
    Title: Thank You for Patience
    Body: "Thank you for your patience! We're working on this and will update you soon..."

20. ESCALATION_NOTICE
    Title: Escalation Notice
    Body: "I've escalated your issue to our senior team. You'll hear back within 24 hours..."
```

### **Variable Support**
```
{first_name}        - Customer first name
{email}             - Customer email
{ticket_id}         - Ticket number (TV-2026-00001)
{plan_name}         - Current plan (Personal, Family, Dedicated)
{device_limit}      - Plan device limit (5, 10, unlimited)
{device_count}      - Current device count
{days_as_customer}  - Account age in days
{amount}            - Dollar amount
{invoice_number}    - Invoice ID
{promo_code}        - Promo code used
{discount_amount}   - Discount amount
{new_plan}          - Upgraded plan name
{new_email}         - New email address
{dashboard_url}     - https://vpn.the-truth-publishing.com/dashboard
{self_service_url}  - Specific self-service action URL
```

---

## 🎫 TIER 3-4: SMART TICKET DASHBOARD

### **Dashboard Layout Specification**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ 🎫 SUPPORT TICKETS                              [📊 Stats] [⚙️ Settings]    │
├──────────────────────────────────────────────────────────────────────────────┤
│ QUICK STATS:  Open: 12  |  Urgent: 2  |  VIP: 1  |  Avg Response: 2.3 hrs   │
├──────────────────────────────────────────────────────────────────────────────┤
│ FILTERS: [Status ▼] [Category ▼] [Priority ▼]  SEARCH: [__________] 🔍      │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐  │
│ │ ⚡ #TV-2026-00042  URGENT  👑 VIP                         5 min ago    │  │
│ │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │  │
│ │ Subject: Can't connect to dedicated server                             │  │
│ │ From: john@example.com • Category: Technical                           │  │
│ │                                                                         │  │
│ │ ┌─────────────────────────────────────────────────────────────────────┐│  │
│ │ │ 🤖 AUTO-SUGGESTION                                                  ││  │
│ │ │ KB Match: 85% confidence - "Connection Issues"                      ││  │
│ │ │ [📧 Send Auto-Reply] [👁️ Preview] [❌ Dismiss]                      ││  │
│ │ └─────────────────────────────────────────────────────────────────────┘│  │
│ │                                                                         │  │
│ │ ┌─────────────────────────────────────────────────────────────────────┐│  │
│ │ │ 💬 SUGGESTED CANNED RESPONSES                                       ││  │
│ │ │ 1. Server Switching Guide .......................... [Send] [View]  ││  │
│ │ │ 2. Clear Cache & Reinstall ......................... [Send] [View]  ││  │
│ │ │ 3. Protocol Change Guide ........................... [Send] [View]  ││  │
│ │ └─────────────────────────────────────────────────────────────────────┘│  │
│ │                                                                         │  │
│ │ 👤 CUSTOMER: VIP • Dedicated Plan • 847 days • 3 previous tickets      │  │
│ │                                                                         │  │
│ │ [📧 Reply] [⏫ Escalate] [✅ Resolve] [🔄 Self-Service] [🗑️ Delete]    │  │
│ └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐  │
│ │ 🔵 #TV-2026-00041  NORMAL                                 2 hrs ago    │  │
│ │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │  │
│ │ Subject: How do I download my config?                                  │  │
│ │ From: sarah@example.com • Category: Technical                          │  │
│ │                                                                         │  │
│ │ ┌─────────────────────────────────────────────────────────────────────┐│  │
│ │ │ 🔧 SELF-SERVICE REDIRECT AVAILABLE                                  ││  │
│ │ │ Action: "Download Configs" - Customer can do this themselves        ││  │
│ │ │ [📧 Send Self-Service Link] [👁️ Preview]                            ││  │
│ │ └─────────────────────────────────────────────────────────────────────┘│  │
│ │                                                                         │  │
│ │ [📧 Reply] [⏫ Escalate] [✅ Resolve] [🔄 Self-Service]                 │  │
│ └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### **Ticket Detail Modal**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Ticket #TV-2026-00042                                              [✕ Close]│
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│ ┌─────────────────────────────────┐  ┌────────────────────────────────────┐ │
│ │ CONVERSATION                     │  │ CUSTOMER PROFILE                   │ │
│ │                                  │  │                                    │ │
│ │ 👤 John (5 min ago)              │  │ 👤 john@example.com                │ │
│ │ ─────────────────────────        │  │ Plan: Dedicated ($39.97/mo)        │ │
│ │ Can't connect to my dedicated    │  │ Status: Active                     │ │
│ │ server. Getting timeout errors.  │  │ Member since: May 2023 (847 days)  │ │
│ │ I've tried restarting the app    │  │ 👑 VIP Customer                    │ │
│ │ but still nothing works.         │  │                                    │ │
│ │                                  │  │ TICKET HISTORY                     │ │
│ │ [Attached: screenshot.png]       │  │ • #00039 - Billing (Resolved)      │ │
│ │                                  │  │ • #00028 - Technical (Resolved)    │ │
│ │ ─────────────────────────        │  │ • #00015 - Account (Resolved)      │ │
│ │ 🤖 System (5 min ago)            │  │                                    │ │
│ │ Ticket received. We're looking   │  │ QUICK ACTIONS                      │ │
│ │ into this for you.               │  │ [💰 Refund] [⬆️ Upgrade]          │ │
│ │                                  │  │ [🎁 Add Credit] [⏸️ Pause]        │ │
│ └─────────────────────────────────┘  └────────────────────────────────────┘ │
│                                                                              │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ 📝 REPLY                                                                 ││
│ │                                                                          ││
│ │ [Variables: {first_name} {ticket_id} {plan_name} ▼]                      ││
│ │                                                                          ││
│ │ ┌──────────────────────────────────────────────────────────────────────┐││
│ │ │ Hi John,                                                             │││
│ │ │                                                                      │││
│ │ │ [Type your response here...]                                         │││
│ │ │                                                                      │││
│ │ └──────────────────────────────────────────────────────────────────────┘││
│ │                                                                          ││
│ │ [📎 Attach] [💾 Save as Canned] [📧 Send & Resolve] [📤 Send Reply]     ││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
│ ┌──────────────────────────────────────────────────────────────────────────┐│
│ │ 📝 INTERNAL NOTES (Not sent to customer)                                ││
│ │ ┌──────────────────────────────────────────────────────────────────────┐││
│ │ │ [Add internal note for team reference...]                            │││
│ │ └──────────────────────────────────────────────────────────────────────┘││
│ └──────────────────────────────────────────────────────────────────────────┘│
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 📡 API ENDPOINTS

### **Ticket Operations**
```
POST   /api/support/tickets              - Create new ticket
GET    /api/support/tickets              - List tickets (with filters)
GET    /api/support/tickets/{id}         - Get single ticket
PUT    /api/support/tickets/{id}         - Update ticket
DELETE /api/support/tickets/{id}         - Delete ticket (admin only)
POST   /api/support/tickets/{id}/reply   - Add response
GET    /api/support/tickets/{id}/history - Get conversation thread
```

### **Auto-Resolution**
```
POST   /api/support/auto-resolve         - Attempt auto-resolution for ticket
POST   /api/support/self-service-check   - Check if self-service can handle
GET    /api/support/suggestions/{id}     - Get all suggestions for ticket
```

### **Canned Responses**
```
GET    /api/support/canned               - List all canned responses
POST   /api/support/canned               - Create new canned response
PUT    /api/support/canned/{id}          - Update canned response
DELETE /api/support/canned/{id}          - Delete canned response
GET    /api/support/canned/suggest/{id}  - Get matching canned for ticket
```

### **Knowledge Base**
```
GET    /api/support/kb                   - List KB entries
POST   /api/support/kb                   - Create KB entry
PUT    /api/support/kb/{id}              - Update KB entry
DELETE /api/support/kb/{id}              - Delete KB entry
GET    /api/support/kb/search            - Search KB by keywords
```

### **Statistics**
```
GET    /api/support/stats                - Dashboard statistics
GET    /api/support/stats/resolution     - Resolution tier breakdown
GET    /api/support/stats/trends         - Ticket trends over time
```

---

## 📊 EXPECTED RESOLUTION DISTRIBUTION

With properly seeded KB and canned responses:

| Tier | Method | Expected % | Human Time |
|------|--------|------------|------------|
| 1 | Auto-Resolution | 30-40% | 0 seconds |
| 2 | Self-Service Redirect | 20-25% | 0 seconds |
| 3 | Canned Response | 25-30% | 2-5 seconds |
| 4 | Manual Response | 10-15% | 2-5 minutes |
| 5 | VIP Escalation | 5% | Varies |

**Result:** 50-65% of tickets resolved with ZERO human interaction!

---

**END OF SECTION 20B - SUPPORT AUTOMATION TIERED SYSTEM**
