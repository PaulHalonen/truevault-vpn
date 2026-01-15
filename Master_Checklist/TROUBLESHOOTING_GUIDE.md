# TRUEVAULT VPN - COMPLETE TROUBLESHOOTING GUIDE

**Version:** 1.0.0  
**Created:** January 15, 2026  
**For:** Kah-Len - TrueVault VPN  

---

## 🚨 COMMON ISSUES & SOLUTIONS

### **CATEGORY 1: SITE ACCESS ISSUES**

---

### **Problem: Cannot Access Site (404 Error)**

**Symptoms:**
- Browser shows "404 Not Found"
- Site completely inaccessible
- Error appears on all pages

**Diagnosis:**
```bash
# Check if files uploaded
ls -la /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/

# Check .htaccess exists
cat /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/.htaccess
```

**Solutions:**

1. **Verify Files Uploaded:**
   - Open FTP (FileZilla)
   - Navigate to `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/`
   - Ensure index.php exists
   - Check all folders are present

2. **Check .htaccess File:**
   - Must be in root directory
   - File must start with a dot: `.htaccess`
   - Verify contents:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Verify Domain Points to Correct Directory:**
   - GoDaddy cPanel → Domains
   - Check document root: `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/`

**Prevention:**
- Always test after uploading files
- Keep FTP connection open during development
- Use FileZilla's "Keep alive" feature

---

### **Problem: 500 Internal Server Error**

**Symptoms:**
- "Internal Server Error" message
- Site worked before, now broken
- Error appears randomly

**Diagnosis:**
```bash
# Check error logs
tail -f /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/logs/error.log

# Check PHP errors
grep -i "error" /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/logs/error.log
```

**Solutions:**

1. **Check PHP Syntax Errors:**
   - Most common cause of 500 errors
   - Missing semicolon, unclosed bracket, etc.
   - Look for line number in error log

2. **Verify File Permissions:**
   ```bash
   # Files should be 644
   chmod 644 /path/to/file.php
   
   # Folders should be 755
   chmod 755 /path/to/folder
   
   # Database files should be 664
   chmod 664 /path/to/database.db
   ```

3. **Check .htaccess Syntax:**
   - Rename .htaccess to .htaccess.bak
   - If site works, .htaccess has syntax error
   - Fix and rename back

4. **Enable Error Display (Temporarily):**
   Add to top of index.php:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
   Remove after finding error!

**Prevention:**
- Always test locally first
- Keep error logging enabled
- Review logs after every deployment

---

### **CATEGORY 2: DATABASE ISSUES**

---

### **Problem: Database File Not Found**

**Symptoms:**
- "Database not found" error
- "Unable to open database file"
- Site shows blank page

**Diagnosis:**
```bash
# Check if databases exist
ls -la /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/

# Check permissions
ls -la /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/databases/*.db
```

**Solutions:**

1. **Run Setup Script:**
   - Visit: https://vpn.the-truth-publishing.com/admin/setup-databases.php
   - Creates all 9 databases
   - Only run once!

2. **Check File Permissions:**
   ```bash
   # Database files must be writable
   chmod 664 /databases/*.db
   
   # Database folder must be writable
   chmod 755 /databases/
   ```

3. **Verify Path in config.php:**
   ```php
   // Should be absolute path, not relative
   define('DB_PATH', __DIR__ . '/../databases/');
   
   // NOT this:
   define('DB_PATH', '../databases/'); // WRONG
   ```

**Prevention:**
- Always use absolute paths
- Test database queries after setup
- Keep backups of database files

---

### **Problem: Database Locked Error**

**Symptoms:**
- "Database is locked" error
- Writes fail but reads work
- Random lock errors

**Diagnosis:**
```bash
# Check if database file is in use
lsof /databases/users.db

# Check database integrity
sqlite3 /databases/users.db "PRAGMA integrity_check;"
```

**Solutions:**

1. **Use Database Transactions:**
   ```php
   // Always use transactions for writes
   $db->beginTransaction();
   try {
       // Your queries here
       $db->commit();
   } catch (Exception $e) {
       $db->rollback();
   }
   ```

2. **Increase Busy Timeout:**
   ```php
   $db = new PDO('sqlite:' . $dbPath);
   $db->setAttribute(PDO::ATTR_TIMEOUT, 30); // 30 seconds
   ```

3. **Close Database Connections:**
   ```php
   // Close when done
   $db = null;
   unset($db);
   ```

**Prevention:**
- Always use transactions
- Close connections properly
- Don't leave long-running queries

---

### **CATEGORY 3: AUTHENTICATION ISSUES**

---

### **Problem: Cannot Login (Invalid Token)**

**Symptoms:**
- Login appears successful but redirects back
- "Invalid token" error
- User logged out immediately

**Diagnosis:**
```php
// Check JWT secret is set
var_dump(JWT_SECRET);

// Check token in database
SELECT * FROM sessions WHERE user_id = 1;
```

**Solutions:**

1. **Verify JWT_SECRET is Set:**
   - Check config.php
   - Must be long random string (not 'your-secret-key-change-this')
   ```php
   define('JWT_SECRET', 'actual-random-string-here');
   ```

2. **Check Token Expiration:**
   - Tokens expire after 7 days
   - User needs to login again
   - Normal behavior

3. **Clear Browser Cookies:**
   - Stale cookies can cause issues
   - Clear all cookies for site
   - Try incognito mode

4. **Check Session Table:**
   ```sql
   -- Session should exist
   SELECT * FROM sessions WHERE user_id = ? AND expires_at > datetime('now');
   ```

**Prevention:**
- Use strong JWT_SECRET
- Implement token refresh
- Log authentication attempts

---

### **Problem: VIP User Not Detecting**

**Symptoms:**
- seige235@yahoo.com registers but not VIP
- VIP badge doesn't appear
- VIP still sees billing page

**Diagnosis:**
```sql
-- Check if email is in VIP list
SELECT * FROM vip_emails WHERE email = 'seige235@yahoo.com';

-- Check user tier
SELECT id, email, tier FROM users WHERE email = 'seige235@yahoo.com';
```

**Solutions:**

1. **Add Email to VIP List:**
   ```sql
   -- Via admin panel OR directly:
   INSERT INTO vip_emails (email) VALUES ('seige235@yahoo.com');
   ```

2. **Check Registration Logic:**
   - File: `/api/auth/register.php`
   - Should check vip_emails table during registration
   - Should set tier to 'vip' if found

3. **Manual VIP Upgrade:**
   ```sql
   -- If already registered, upgrade manually
   UPDATE users SET tier = 'vip', subscription_status = 'active' 
   WHERE email = 'seige235@yahoo.com';
   ```

4. **Refresh Page:**
   - User must refresh page after VIP upgrade
   - VIP badge won't show until refresh
   - Normal behavior

**Prevention:**
- Test VIP detection during development
- Keep VIP email list updated
- Log VIP detections in admin panel

---

### **CATEGORY 4: PAYPAL ISSUES**

---

### **Problem: PayPal Subscription Fails**

**Symptoms:**
- User clicks subscribe, redirects to error
- "Invalid credentials" error
- Payment not processing

**Diagnosis:**
```bash
# Check PayPal credentials in admin
SELECT setting_value FROM system_settings WHERE setting_key LIKE 'paypal_%';

# Check error logs
tail -f /logs/error.log
```

**Solutions:**

1. **Verify PayPal Credentials:**
   - Admin panel → Settings → PayPal
   - Client ID must match your PayPal app
   - Secret must be correct
   - Mode must be 'live' (not 'sandbox')

2. **Check Plan IDs:**
   ```sql
   SELECT setting_value FROM system_settings 
   WHERE setting_key IN ('paypal_plan_standard', 'paypal_plan_pro');
   ```
   - Must match actual plan IDs in PayPal dashboard
   - Create plans in PayPal first, then enter IDs

3. **Test API Connection:**
   - Create simple test script:
   ```php
   $paypal = new PayPal();
   $result = $paypal->getAccessToken();
   var_dump($result); // Should show token
   ```

4. **Switch to Sandbox for Testing:**
   - Set mode to 'sandbox' in admin
   - Use sandbox credentials
   - Test subscription flow
   - Switch back to 'live' when working

**Prevention:**
- Always test in sandbox first
- Keep credentials secure
- Log all PayPal API calls

---

### **Problem: Webhook Not Working**

**Symptoms:**
- Subscription created but not activated
- Payments received but not recorded
- User status not updating

**Diagnosis:**
```bash
# Check webhook logs
tail -f /logs/paypal_webhook.log

# Check webhook URL in PayPal
# Should be: https://vpn.the-truth-publishing.com/api/billing/paypal-webhook.php
```

**Solutions:**

1. **Verify Webhook URL:**
   - PayPal Dashboard → Apps → Your App → Webhooks
   - URL must be exact: https://vpn.the-truth-publishing.com/api/billing/paypal-webhook.php
   - Must be HTTPS (not HTTP)

2. **Check Webhook Events:**
   - PayPal → Webhooks → Select webhook
   - Must listen for these events:
     - BILLING.SUBSCRIPTION.CREATED
     - BILLING.SUBSCRIPTION.ACTIVATED
     - BILLING.SUBSCRIPTION.CANCELLED
     - PAYMENT.SALE.COMPLETED

3. **Verify Signature Validation:**
   - File: `/api/billing/paypal-webhook.php`
   - Must verify PayPal signature
   - Check webhook_id matches

4. **Manual Testing:**
   - PayPal → Webhooks → Your webhook → Webhooks simulator
   - Send test events
   - Check logs for receipt

**Prevention:**
- Test webhook after setup
- Log all webhook events
- Monitor webhook failures

---

### **CATEGORY 5: EMAIL ISSUES**

---

### **Problem: Emails Not Sending**

**Symptoms:**
- Welcome email not received
- No payment reminders
- Support tickets don't send emails

**Diagnosis:**
```sql
-- Check email log for failures
SELECT * FROM email_log WHERE status = 'failed' ORDER BY sent_at DESC LIMIT 10;

-- Check email queue
SELECT * FROM email_queue WHERE status = 'pending';
```

**Solutions:**

1. **Check SMTP Settings:**
   - Admin panel → Settings → Email
   - Host: mail.the-truth-publishing.com
   - Port: 587 (TLS)
   - Username: admin@vpn.the-truth-publishing.com
   - Password: [must be correct]

2. **Test Email Sending:**
   ```php
   $email = new Email();
   $result = $email->sendSMTP(
       'your-email@example.com',
       'Test Email',
       '<p>This is a test</p>'
   );
   var_dump($result); // Should be true
   ```

3. **Check Gmail App Password:**
   - For admin emails (Gmail)
   - Must use App Password (not regular password)
   - Google Account → Security → App passwords
   - Create new app password
   - Enter in admin settings

4. **Verify PHP mail() Function:**
   ```php
   // Simple test
   mail('your-email@example.com', 'Test', 'Test body');
   ```

5. **Check Email Queue Processing:**
   - Cron job must be running
   - Run manually: `php /cron/process-automation.php`
   - Check logs for errors

**Prevention:**
- Test email system during setup
- Monitor email_log table
- Keep SMTP credentials secure

---

### **Problem: Emails Go to Spam**

**Symptoms:**
- Emails sending but landing in spam
- Users don't receive welcome emails
- Payment reminders in junk folder

**Diagnosis:**
- Check email headers
- Look for SPF/DKIM issues
- Check sender reputation

**Solutions:**

1. **Configure SPF Record:**
   - GoDaddy DNS settings
   - Add TXT record:
   ```
   v=spf1 include:_spf.google.com ~all
   ```

2. **Enable DKIM:**
   - GoDaddy cPanel → Email → Authentication
   - Enable DKIM for domain
   - Copy DNS records

3. **Use Reply-To Header:**
   ```php
   $headers = [
       'From: TrueVault VPN <admin@vpn.the-truth-publishing.com>',
       'Reply-To: support@vpn.the-truth-publishing.com'
   ];
   ```

4. **Warm Up IP Address:**
   - Send small volume initially
   - Gradually increase over weeks
   - Maintain good sending reputation

5. **Avoid Spam Triggers:**
   - Don't use ALL CAPS in subject
   - Avoid excessive exclamation marks!!!
   - Include unsubscribe link
   - Use professional language

**Prevention:**
- Configure DNS properly
- Monitor spam complaints
- Keep email content professional

---

### **CATEGORY 6: DEVICE SETUP ISSUES**

---

### **Problem: Config File Won't Download**

**Symptoms:**
- User clicks download, nothing happens
- Config file is empty
- Browser shows error

**Diagnosis:**
```bash
# Check if WireGuard keys are being generated
# Check browser console for errors

# Test API endpoint
curl -X POST https://vpn.the-truth-publishing.com/api/devices/provision.php \
  -H "Authorization: Bearer TOKEN" \
  -d '{"device_name":"Test","server_id":1}'
```

**Solutions:**

1. **Check TweetNaCl.js Loaded:**
   - View page source
   - Verify `<script src="/assets/js/tweetnacl.min.js">` exists
   - Check file exists on server

2. **Verify Server Keys:**
   - Admin panel → Settings → Servers
   - Each server must have public_key
   - Keys must be valid WireGuard keys

3. **Test Key Generation:**
   - Open browser console
   - Run:
   ```javascript
   let keypair = nacl.box.keyPair();
   console.log(keypair); // Should show keys
   ```

4. **Check File Download Headers:**
   ```php
   header('Content-Type: application/octet-stream');
   header('Content-Disposition: attachment; filename="wireguard.conf"');
   ```

**Prevention:**
- Test device setup with real device
- Keep libraries up to date
- Log key generation attempts

---

### **Problem: VPN Won't Connect**

**Symptoms:**
- Config imported successfully
- VPN shows "connecting..." forever
- Never establishes connection

**Diagnosis:**
```bash
# On VPN server
wg show

# Check if peer added
wg show wg0 peers

# Check firewall
iptables -L -n
```

**Solutions:**

1. **Verify Server Running:**
   - SSH into server
   - Check WireGuard: `systemctl status wg-quick@wg0`
   - If not running: `systemctl start wg-quick@wg0`

2. **Check Firewall Rules:**
   ```bash
   # Port 51820 must be open
   ufw allow 51820/udp
   
   # Or iptables:
   iptables -A INPUT -p udp --dport 51820 -j ACCEPT
   ```

3. **Verify IP Allocation:**
   - Each device must have unique IP
   - Check allowed_ips don't overlap
   - Range: 10.8.0.2 - 10.8.0.254

4. **Test Server Reachability:**
   ```bash
   # From client machine
   ping SERVER_IP
   nc -zvu SERVER_IP 51820
   ```

5. **Check DNS Settings:**
   - Config must have DNS line
   - DNS = 1.1.1.1, 1.0.0.1

**Prevention:**
- Monitor server status
- Keep WireGuard updated
- Test connections regularly

---

### **CATEGORY 7: AUTOMATION ISSUES**

---

### **Problem: Workflows Not Executing**

**Symptoms:**
- Payment fails but no reminder email
- New users don't get welcome email
- Scheduled tasks not running

**Diagnosis:**
```sql
-- Check workflow executions
SELECT * FROM workflow_executions ORDER BY started_at DESC LIMIT 10;

-- Check scheduled tasks
SELECT * FROM scheduled_workflow_steps WHERE status = 'pending';
```

**Solutions:**

1. **Verify Cron Job Running:**
   ```bash
   # Check crontab
   crontab -l
   
   # Should see:
   */5 * * * * php /path/to/cron/process-automation.php
   ```

2. **Run Manually:**
   ```bash
   # Test automation processor
   php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/cron/process-automation.php
   
   # Check output for errors
   ```

3. **Check Workflow Registration:**
   - File: `/includes/Workflows.php`
   - All 12 workflows must be registered
   - Check trigger conditions

4. **Verify Database Tables:**
   ```sql
   -- Tables must exist
   SELECT name FROM sqlite_master WHERE type='table' 
   AND name IN ('workflow_executions', 'scheduled_workflow_steps');
   ```

5. **Check Logs:**
   ```sql
   SELECT * FROM automation_log WHERE status = 'failed';
   ```

**Prevention:**
- Test workflows during development
- Monitor execution logs
- Set up cron email notifications

---

### **Problem: Emails Stuck in Queue**

**Symptoms:**
- Emails show in email_queue table
- Status remains 'pending'
- Never actually send

**Diagnosis:**
```sql
-- Check queue
SELECT * FROM email_queue WHERE status = 'pending' ORDER BY scheduled_for;

-- Check attempts
SELECT id, recipient, attempts FROM email_queue WHERE attempts > 0;
```

**Solutions:**

1. **Process Queue Manually:**
   ```php
   $email = new Email();
   $processed = $email->processQueue(50);
   echo "Processed $processed emails";
   ```

2. **Check Cron Job:**
   - Must run every 5 minutes
   - Calls `Email::processQueue()`

3. **Reset Failed Emails:**
   ```sql
   -- Reset emails with too many attempts
   UPDATE email_queue 
   SET status = 'pending', attempts = 0 
   WHERE attempts > 3 AND status = 'failed';
   ```

4. **Check Email Settings:**
   - SMTP/Gmail credentials correct?
   - Test email sending works

**Prevention:**
- Monitor email queue size
- Set up alerts for stuck emails
- Log processing attempts

---

### **CATEGORY 8: SUPPORT TICKET ISSUES**

---

### **Problem: Tickets Not Auto-Categorizing**

**Symptoms:**
- New tickets have NULL category
- No knowledge base suggestions
- All tickets marked as "general"

**Diagnosis:**
```sql
-- Check tickets without category
SELECT * FROM support_tickets WHERE category IS NULL;

-- Check knowledge base
SELECT COUNT(*) FROM knowledge_base;
```

**Solutions:**

1. **Check Categorization Logic:**
   - File: `/api/support/create-ticket.php`
   - Keywords must match categories:
     - billing, payment, refund → "billing"
     - connect, setup, device → "technical"
     - password, login, email → "account"

2. **Populate Knowledge Base:**
   ```sql
   -- Must have KB articles
   INSERT INTO knowledge_base (title, content, category, keywords) VALUES
   ('How to Connect', 'Instructions...', 'technical', 'connect,setup,vpn'),
   ('Billing Questions', 'Info...', 'billing', 'payment,charge,refund');
   ```

3. **Test Auto-Categorization:**
   ```php
   // Create test ticket
   $category = categorizeTicket("I can't connect to VPN");
   // Should return "technical"
   ```

4. **Manual Categorization:**
   - Admin can change category
   - Admin panel → Support Tickets → Edit

**Prevention:**
- Add more KB articles
- Improve categorization keywords
- Train system with real tickets

---

### **CATEGORY 9: VIP SYSTEM ISSUES**

---

### **Problem: VIP Badge Not Showing**

**Symptoms:**
- User is VIP in database
- No badge after login
- Page refreshed multiple times

**Diagnosis:**
```sql
-- Verify user is VIP
SELECT id, email, tier FROM users WHERE email = 'seige235@yahoo.com';

-- Check session
SELECT * FROM sessions WHERE user_id = 1;
```

**Solutions:**

1. **Verify Tier in Database:**
   ```sql
   -- Must be exactly 'vip' (lowercase)
   UPDATE users SET tier = 'vip' WHERE email = 'seige235@yahoo.com';
   ```

2. **Check Dashboard Code:**
   - File: `/dashboard/index.php`
   - Must check `$user['tier'] === 'vip'`
   - Badge HTML must exist

3. **Clear Browser Cache:**
   - Hard refresh: Ctrl+Shift+R
   - Clear all cookies
   - Try incognito mode

4. **Check CSS:**
   - `.vip-badge` class must exist
   - Verify styling:
   ```css
   .vip-badge {
       background: linear-gradient(135deg, #ffd700, #ffed4e);
       color: #000;
       padding: 8px 16px;
       border-radius: 20px;
       font-weight: 700;
   }
   ```

**Prevention:**
- Test VIP detection during development
- Use consistent tier naming
- Log VIP badge renders

---

### **Problem: VIP Seeing Billing Page**

**Symptoms:**
- VIP user can access billing page
- Shows PayPal options
- Shouldn't see any billing

**Diagnosis:**
```sql
-- Verify VIP status
SELECT email, tier, subscription_status FROM users WHERE tier = 'vip';
```

**Solutions:**

1. **Update Billing Page:**
   - File: `/dashboard/billing.php`
   - Add at top:
   ```php
   if ($user['tier'] === 'vip') {
       echo '<p>Your account is VIP - no billing needed!</p>';
       exit;
   }
   ```

2. **Hide Billing Link:**
   - Dashboard navigation
   - Don't show billing link if VIP:
   ```php
   <?php if ($user['tier'] !== 'vip'): ?>
       <a href="/dashboard/billing.php">Billing</a>
   <?php endif; ?>
   ```

**Prevention:**
- Test all VIP workflows
- Hide billing completely for VIP
- Log VIP access attempts

---

### **CATEGORY 10: TRANSFER SYSTEM ISSUES**

---

### **Problem: Settings Export Empty**

**Symptoms:**
- Click "Export Settings"
- Downloads empty JSON file
- or download fails completely

**Diagnosis:**
```bash
# Check PHP errors
tail -f /logs/error.log

# Test JSON encoding
php -r "echo json_encode(['test' => 'value']);"
```

**Solutions:**

1. **Check Database Connection:**
   - Export script must connect to admin.db
   - Verify path to database

2. **Test Export Manually:**
   ```php
   // Run this script
   $settings = Database::query('admin', 
       "SELECT * FROM system_settings"
   );
   echo json_encode($settings, JSON_PRETTY_PRINT);
   ```

3. **Check File Permissions:**
   - Script must be able to read database
   - Database file: 664 permissions

4. **Verify JSON Encoding:**
   ```php
   $data = ['settings' => $settings];
   $json = json_encode($data);
   if ($json === false) {
       echo json_last_error_msg();
   }
   ```

**Prevention:**
- Test export during development
- Log export attempts
- Validate JSON before download

---

## 🔧 DIAGNOSTIC TOOLS

### **Tool 1: Database Integrity Checker**

Create file: `/admin/check-databases.php`
```php
<?php
require_once '../configs/config.php';
require_once '../includes/Database.php';

$databases = ['users', 'devices', 'servers', 'billing', 
              'port_forwards', 'parental_controls', 'admin', 
              'logs', 'support'];

echo "<h1>Database Integrity Check</h1>";

foreach ($databases as $db) {
    echo "<h2>$db.db</h2>";
    
    try {
        $tables = Database::query($db, 
            "SELECT name FROM sqlite_master WHERE type='table'"
        );
        
        echo "<p>✅ Connected - " . count($tables) . " tables</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            $count = Database::queryOne($db, 
                "SELECT COUNT(*) as count FROM " . $table['name']
            );
            echo "<li>" . $table['name'] . ": " . $count['count'] . " rows</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>
```

---

### **Tool 2: Email Test Script**

Create file: `/admin/test-email.php`
```php
<?php
require_once '../configs/config.php';
require_once '../includes/Email.php';

$email = new Email();

// Test SMTP
echo "<h2>Testing SMTP</h2>";
$result = $email->sendSMTP(
    'your-test-email@example.com',
    'SMTP Test',
    '<p>This is a test email via SMTP</p>'
);
echo $result ? "✅ SMTP Success" : "❌ SMTP Failed";

// Test Gmail
echo "<h2>Testing Gmail</h2>";
$result = $email->sendGmail(
    'your-test-email@example.com',
    'Gmail Test',
    '<p>This is a test email via Gmail</p>'
);
echo $result ? "✅ Gmail Success" : "❌ Gmail Failed";
?>
```

---

### **Tool 3: API Endpoint Tester**

Create file: `/admin/test-api.php`
```php
<?php
// Test all API endpoints

$endpoints = [
    'POST /api/auth/register.php',
    'POST /api/auth/login.php',
    'GET /api/devices/list.php',
    'POST /api/devices/provision.php',
    'POST /api/billing/create-subscription.php',
    // Add more...
];

foreach ($endpoints as $endpoint) {
    echo "<h3>$endpoint</h3>";
    // Test logic here
}
?>
```

---

## 📞 GETTING HELP

### **When to Contact Hosting Support:**
- Server is completely down
- Cannot access cPanel
- FTP not connecting
- SSL certificate issues
- Server performance problems

### **Self-Service Resources:**
- Error logs: `/logs/error.log`
- PayPal logs: Check webhook history
- Database queries: Use phpLiteAdmin
- Email logs: Check `email_log` table

### **Before Asking for Help:**
1. Check error logs
2. Try the solutions in this guide
3. Test in incognito mode
4. Clear cache and cookies
5. Try different browser
6. Verify credentials

---

## 🎯 QUICK REFERENCE

### **File Permissions:**
- Folders: 755
- PHP files: 644
- Database files: 664
- .htaccess: 644

### **Important Paths:**
- Root: `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/`
- Databases: `/databases/`
- Logs: `/logs/`
- Config: `/configs/config.php`

### **Common Commands:**
```bash
# Check PHP version
php -v

# Test PHP file
php -l /path/to/file.php

# Check disk space
df -h

# Check database
sqlite3 /path/to/database.db "SELECT * FROM table LIMIT 5;"
```

---

**Remember:** Most issues are simple configuration problems. Work through this guide systematically and you'll solve 95% of issues! 🚀
