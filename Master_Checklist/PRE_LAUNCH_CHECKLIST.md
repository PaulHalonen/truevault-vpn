# TRUEVAULT VPN - PRE-LAUNCH CHECKLIST

**Version:** 1.0.0  
**Created:** January 15, 2026  
**For:** Kah-Len - Final checks before going live  

---

## ⚡ CRITICAL: USE THIS BEFORE LAUNCH!

This checklist ensures your VPN business is 100% ready for customers.  
**Complete every item before accepting real money!**

---

## 🔒 SECTION 1: SECURITY (CRITICAL!)

### **Configuration Security:**
- [ ] **Change JWT_SECRET** from default value
  - Open: `/configs/config.php`
  - Replace: `define('JWT_SECRET', 'actual-random-string-256-chars');`
  - Use: https://randomkeygen.com/ (CodeIgniter Encryption Keys)

- [ ] **Change Admin Password** from admin123
  - Login to admin panel
  - Settings → Admin Account → Change Password
  - Use strong password (16+ characters)

- [ ] **Set ENVIRONMENT to 'production'**
  - Open: `/configs/config.php`
  - Change: `define('ENVIRONMENT', 'production');`
  - Disables debug output

- [ ] **Delete Setup Scripts**
  - Delete: `/admin/setup-databases.php`
  - Delete: `/admin/install-email-templates.php`
  - These are security risks if left

- [ ] **Verify .htaccess Protection**
  - Test: https://vpn.the-truth-publishing.com/configs/config.php
  - Should show: 403 Forbidden
  - Test: https://vpn.the-truth-publishing.com/databases/
  - Should show: 403 Forbidden

- [ ] **Check File Permissions**
  ```bash
  # All PHP files
  find . -type f -name "*.php" -exec chmod 644 {} \;
  
  # All folders
  find . -type d -exec chmod 755 {} \;
  
  # Database files
  chmod 664 /databases/*.db
  ```

- [ ] **Enable HTTPS Only**
  - Test: http://vpn.the-truth-publishing.com
  - Should redirect to: https://vpn.the-truth-publishing.com
  - Verify SSL certificate is valid

- [ ] **Verify Error Logging**
  - Check: `/logs/error.log` exists
  - Permissions: 644
  - Test: Trigger error, check logged

**Security Score: ___/8** (Must be 8/8 to launch!)

---

## 💳 SECTION 2: PAYPAL CONFIGURATION

### **PayPal Live Setup:**
- [ ] **Enter Live Credentials**
  - Admin Panel → Settings → PayPal
  - Client ID: ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk
  - Secret: [Enter your secret]
  - Save changes

- [ ] **Set Mode to 'live'**
  - PayPal Mode dropdown → Select "live"
  - NOT "sandbox"!
  - Save changes

- [ ] **Create Subscription Plans in PayPal**
  - Login to: https://www.paypal.com/businessmanage/products
  - Create Standard plan: $9.99/month
  - Create Pro plan: $14.99/month
  - Copy Plan IDs

- [ ] **Enter Plan IDs in Settings**
  - Admin Panel → Settings → PayPal
  - Standard Plan ID: [paste from PayPal]
  - Pro Plan ID: [paste from PayPal]
  - Save changes

- [ ] **Configure Webhook**
  - PayPal → Apps → Your App → Add Webhook
  - URL: https://vpn.the-truth-publishing.com/api/billing/paypal-webhook.php
  - Events: Select all billing events
  - Save Webhook ID: 46924926WL757580D

- [ ] **Enter Webhook ID**
  - Admin Panel → Settings → PayPal
  - Webhook ID: 46924926WL757580D
  - Save changes

- [ ] **Test Subscription Creation**
  - Register test account (NOT VIP email)
  - Click subscribe
  - Complete PayPal flow
  - Verify redirects back to site

- [ ] **Test Webhook Reception**
  - Check: `billing.db → transactions` table
  - Should have new transaction
  - Status should update to 'active'

- [ ] **Test Payment Failure**
  - Use PayPal sandbox with no funds
  - Verify Day 0 email sends
  - Verify grace period activates

**PayPal Score: ___/9** (Must be 9/9 to accept payments!)

---

## 📧 SECTION 3: EMAIL SYSTEM

### **SMTP Configuration (Customer Emails):**
- [ ] **Enter SMTP Settings**
  - Admin Panel → Settings → Email → SMTP
  - Host: mail.the-truth-publishing.com
  - Port: 587
  - Username: admin@vpn.the-truth-publishing.com
  - Password: [your email password]

- [ ] **Test SMTP Sending**
  - Use: `/admin/test-email.php`
  - Send test to your email
  - Verify received

- [ ] **Check Welcome Email**
  - Register new account
  - Check email received within 1 minute
  - Verify professional styling

### **Gmail Configuration (Admin Notifications):**
- [ ] **Create Gmail App Password**
  - Google Account → Security → 2-Step Verification → App passwords
  - Generate new app password
  - Copy password

- [ ] **Enter Gmail Settings**
  - Admin Panel → Settings → Email → Gmail
  - Username: paulhalonen@gmail.com
  - App Password: [paste from Google]
  - Save changes

- [ ] **Test Gmail Sending**
  - Use: `/admin/test-email.php`
  - Test Gmail function
  - Check your Gmail inbox

### **Email Templates:**
- [ ] **Verify All 19 Templates Installed**
  - Run: `/admin/install-email-templates.php`
  - Check: `admin.db → email_templates` table
  - Should have 19 rows

- [ ] **Test Variable Replacement**
  - Send welcome email to test account
  - Check {first_name} replaced correctly
  - Check {email} replaced correctly

- [ ] **Check Email Branding**
  - All emails have TrueVault VPN header
  - All emails have unsubscribe link
  - All emails have professional footer

**Email Score: ___/9** (Must be 9/9 for automation!)

---

## 🤖 SECTION 4: AUTOMATION SYSTEM

### **Cron Job Configuration:**
- [ ] **Setup Cron Job**
  - SSH or cPanel → Cron Jobs
  - Add: `*/5 * * * * php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/cron/process-automation.php`
  - Runs every 5 minutes

- [ ] **Test Cron Manually**
  ```bash
  php /home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/cron/process-automation.php
  ```
  - Should run without errors
  - Check output

- [ ] **Verify Email Queue Processing**
  - Queue test email
  - Wait 5 minutes
  - Check email received

### **Workflow Testing:**
- [ ] **Test New Customer Onboarding**
  - Register new account
  - Check welcome email (immediate)
  - Check setup guide email (1 hour later)
  - Check follow-up email (24 hours later)

- [ ] **Test Payment Failed Escalation**
  - Create subscription with no funds (sandbox)
  - Day 0: Check reminder email
  - Day 3: Check urgent notice
  - Day 7: Check final warning
  - Day 8: Check account suspended

- [ ] **Test Support Ticket Workflow**
  - Create support ticket
  - Check acknowledgment email
  - Check auto-categorization
  - Check knowledge base search

- [ ] **Test VIP Approval Workflow**
  - Add test email to VIP list
  - Register with that email
  - Check VIP welcome email
  - Verify tier upgraded

### **Scheduled Tasks:**
- [ ] **Verify Scheduled Tasks Table**
  - Check: `logs.db → scheduled_workflow_steps`
  - Should have pending tasks

- [ ] **Test Task Execution**
  - Create task scheduled for 5 minutes from now
  - Wait 10 minutes
  - Check task status changed to 'completed'

**Automation Score: ___/9** (Must be 9/9 for hands-off operation!)

---

## 🛡️ SECTION 5: VPN SERVERS

### **Server Connectivity:**
- [ ] **Test New York Server (66.94.103.91)**
  - Ping: `ping 66.94.103.91`
  - Port check: `nc -zvu 66.94.103.91 51820`
  - Should respond

- [ ] **Test St. Louis VIP Server (144.126.133.253)**
  - Ping: `ping 144.126.133.253`
  - Port check: `nc -zvu 144.126.133.253 51820`
  - Reserved for seige235@yahoo.com only

- [ ] **Test Dallas Server (66.241.124.4)**
  - Ping: `ping 66.241.124.4`
  - Port check: `nc -zvu 66.241.124.4 51820`
  - Streaming optimized

- [ ] **Test Toronto Server (66.241.125.247)**
  - Ping: `ping 66.241.125.247`
  - Port check: `nc -zvu 66.241.125.247 51820`
  - Canadian content

### **WireGuard Configuration:**
- [ ] **Verify Server Keys**
  - Admin Panel → Settings → Servers
  - Each server has public_key filled
  - Keys are valid WireGuard format

- [ ] **Test Device Setup**
  - Register new account
  - Click "Add Device"
  - Complete in <30 seconds
  - Config file downloads

- [ ] **Test VPN Connection**
  - Import config to WireGuard client
  - Connect to VPN
  - Check IP changed: https://whatismyipaddress.com
  - Test internet browsing works

### **Server Monitoring:**
- [ ] **Setup Server Health Checks**
  - Create monitoring script
  - Check server status every 5 minutes
  - Alert if server down

**Server Score: ___/11** (Must be 11/11 for VPN functionality!)

---

## 🎯 SECTION 6: VIP SYSTEM

### **VIP Configuration (SECRET!):**
- [ ] **Add seige235@yahoo.com to VIP List**
  - Admin Panel → Settings → VIP List
  - Add: seige235@yahoo.com
  - Assign: St. Louis Dedicated (144.126.133.253)

- [ ] **Test VIP Auto-Detection**
  - Register with: seige235@yahoo.com
  - Should bypass PayPal completely
  - Should auto-upgrade to VIP tier

- [ ] **Test VIP Badge Display**
  - Login as VIP user
  - Refresh page
  - Check top right corner for: ⭐ VIP
  - Gold gradient styling

- [ ] **Verify No Public VIP Advertising**
  - Check landing page: NO VIP mention ✅
  - Check pricing page: Only Standard & Pro ✅
  - Check features page: NO VIP features ✅
  - Check registration: NO VIP option ✅

- [ ] **Test VIP Billing Page**
  - Login as VIP
  - Go to billing page
  - Should show: "Your account is VIP - no billing needed"
  - NO PayPal buttons

- [ ] **Test VIP Server Access**
  - VIP user adds device
  - Check St. Louis server available
  - Connect to dedicated server
  - Verify exclusive access

**VIP Score: ___/6** (Must be 6/6 for secret VIP system!)

---

## 🌐 SECTION 7: FRONTEND PAGES

### **Public Pages (NO VIP!):**
- [ ] **Landing Page**
  - Visit: https://vpn.the-truth-publishing.com
  - Loads without errors
  - Gradient design beautiful
  - NO VIP tier mentioned
  - Call-to-action buttons work

- [ ] **Pricing Page**
  - Click pricing link
  - Shows Standard ($9.99) and Pro ($14.99)
  - NO VIP tier shown
  - 7-day free trial badge visible
  - Feature comparison clear

- [ ] **Features Page**
  - Click features link
  - Lists all features
  - NO VIP features mentioned
  - Icons display correctly

- [ ] **Registration Page**
  - Click "Sign Up" button
  - Form displays correctly
  - Plan selection works
  - 7-day trial option visible
  - NO VIP option shown

### **User Account Pages:**
- [ ] **Login Page**
  - Visit /login.php
  - Can login successfully
  - Redirects to dashboard

- [ ] **User Dashboard**
  - Shows account stats
  - Device list displays
  - Server status visible
  - Quick actions work
  - VIP badge only if VIP

- [ ] **Device Management**
  - Can add device
  - Can delete device
  - Can switch servers
  - Downloads work

- [ ] **Billing Management**
  - Subscription details show
  - Payment history displays
  - Can cancel subscription
  - VIP sees "no billing needed"

### **Mobile Responsiveness:**
- [ ] **Test on Mobile Device**
  - All pages responsive
  - Buttons clickable
  - Forms usable
  - No horizontal scroll

**Frontend Score: ___/13** (Must be 13/13 for professional appearance!)

---

## 🔍 SECTION 8: TESTING & QUALITY

### **End-to-End User Flow:**
- [ ] **New User Journey**
  1. Visit landing page
  2. Click "Sign Up"
  3. Fill registration form
  4. Select Standard plan
  5. Click 7-day free trial
  6. Complete PayPal (sandbox)
  7. Return to site
  8. Receive welcome email
  9. Add device (<30 seconds)
  10. Connect to VPN
  11. Browse internet
  12. Check dashboard stats

- [ ] **VIP User Journey**
  1. Admin adds email to VIP list
  2. User registers with VIP email
  3. Skips PayPal completely
  4. User refreshes page
  5. VIP badge appears
  6. User goes to billing
  7. Sees "no billing needed"
  8. Adds device
  9. Sees St. Louis dedicated
  10. Connects successfully

### **Error Handling:**
- [ ] **Test Invalid Inputs**
  - Register with invalid email
  - Login with wrong password
  - Try to hack JWT token
  - All show proper errors

- [ ] **Test Edge Cases**
  - Register duplicate email
  - Exceed device limit
  - Cancel then resubscribe
  - All handle gracefully

### **Performance:**
- [ ] **Page Load Speed**
  - Landing page < 2 seconds
  - Dashboard < 1 second
  - Device setup instant
  - No lag anywhere

- [ ] **Database Performance**
  - All queries < 100ms
  - No N+1 queries
  - Proper indexes used

**Testing Score: ___/7** (Must be 7/7 for quality assurance!)

---

## 📚 SECTION 9: DOCUMENTATION

### **User Documentation:**
- [ ] **User Guide Complete**
  - How to register
  - How to add devices
  - How to switch servers
  - How to get support

- [ ] **FAQ Page**
  - Common questions answered
  - Clear and concise
  - Searchable

- [ ] **Troubleshooting Guide**
  - Connection issues
  - Payment problems
  - Device setup help

### **Admin Documentation:**
- [ ] **Admin Guide Complete**
  - How to manage users
  - How to approve VIP
  - How to configure settings
  - How to monitor system

- [ ] **Transfer Documentation**
  - Business transfer guide
  - 30-minute takeover process
  - Canadian → USA conversion
  - Owner handoff checklist

**Documentation Score: ___/7** (Must be 7/7 for user support!)

---

## 🚀 SECTION 10: FINAL CHECKS

### **Pre-Launch Verification:**
- [ ] **All Previous Sections Complete**
  - Security: 8/8 ✅
  - PayPal: 9/9 ✅
  - Email: 9/9 ✅
  - Automation: 9/9 ✅
  - Servers: 11/11 ✅
  - VIP: 6/6 ✅
  - Frontend: 13/13 ✅
  - Testing: 7/7 ✅
  - Documentation: 7/7 ✅

- [ ] **Review Terms of Service**
  - Legal compliance
  - Privacy policy
  - Refund policy
  - Acceptable use policy

- [ ] **Backup Everything**
  - Download all database files
  - Backup all code files
  - Export PayPal settings
  - Save email templates

- [ ] **Monitor Setup Ready**
  - Error log monitoring
  - Server uptime monitoring
  - Email delivery monitoring
  - Payment webhook monitoring

- [ ] **Support System Ready**
  - Support email working
  - Ticket system tested
  - Knowledge base populated
  - Response templates ready

### **Launch Readiness:**
- [ ] **Total Score: ___/89**
  - **89/89 = READY TO LAUNCH!** 🚀
  - **< 89 = DO NOT LAUNCH YET!** ⚠️

---

## 🎊 LAUNCH DAY CHECKLIST

When everything above is complete:

1. **Announce Launch**
   - [ ] Post on social media
   - [ ] Email existing contacts
   - [ ] Update website live

2. **Monitor First 24 Hours**
   - [ ] Check error logs every hour
   - [ ] Monitor new registrations
   - [ ] Check first subscriptions
   - [ ] Verify emails sending
   - [ ] Watch for support tickets

3. **First Week Activities**
   - [ ] Monitor daily stats
   - [ ] Respond to all support tickets
   - [ ] Fix any bugs immediately
   - [ ] Gather user feedback
   - [ ] Optimize based on data

---

## ⚠️ DO NOT LAUNCH UNTIL...

**CRITICAL - YOU MUST:**
✅ Score 89/89 on this checklist  
✅ Test with real PayPal transactions  
✅ Verify VIP system hidden from public  
✅ Test on real devices  
✅ Backup everything  
✅ Have monitoring in place  

**IF ANYTHING FAILS:**
- Fix it immediately
- Re-test completely
- Don't skip steps
- Don't launch until perfect

---

## 🎯 YOUR SCORE

**Section 1 - Security:** ___/8  
**Section 2 - PayPal:** ___/9  
**Section 3 - Email:** ___/9  
**Section 4 - Automation:** ___/9  
**Section 5 - Servers:** ___/11  
**Section 6 - VIP:** ___/6  
**Section 7 - Frontend:** ___/13  
**Section 8 - Testing:** ___/7  
**Section 9 - Documentation:** ___/7  
**Section 10 - Final:** ___/10  

**TOTAL SCORE:** ___/89

---

## ✅ LAUNCH DECISION

- **89/89** = **READY TO LAUNCH!** 🚀🎉
- **80-88** = Almost there, fix remaining items
- **< 80** = NOT READY - complete more sections

---

**Remember: It's better to delay launch and do it right than to launch broken and lose customers!**

**Good luck with your launch!** 🚀
