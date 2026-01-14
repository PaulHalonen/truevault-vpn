# TRUEVAULT VPN - STREAMLINED BUILD CHECKLIST
## One-Man Operation - Automated Everything
**Created:** January 14, 2026 - 1:00 AM CST
**Goal:** Launch in 5 weeks with zero daily admin work

---

# ⚠️ RULES

1. **MARK [x] WHEN COMPLETE** - Never delete items
2. **TEST BEFORE MARKING** - Verify it actually works
3. **NO PLACEHOLDERS** - Real working code only
4. **2-CLICK RULE** - If it takes more than 2 clicks, redesign it
5. **AUTOMATE EVERYTHING** - If you'd do it manually, automate it

---

# PHASE 1: FOUNDATION ✅ MOSTLY COMPLETE

## 1.1 Repository & Structure
- [x] GitHub repository created
- [x] Directory structure created
- [x] Reference documentation created

## 1.2 Database Setup
- [x] users.db schema (users, user_devices)
- [x] servers.db schema (vpn_servers with 4 servers)
- [x] billing.db schema (subscriptions, payments)
- [x] vip.db with seige235@yahoo.com
- [x] themes.db with default theme
- [x] automation.db schema (scheduled_tasks, email_log)
- [ ] Add device_server_history table
- [ ] Add display_name, country_flag to vpn_servers

## 1.3 Core API Files
- [x] api/config/database.php
- [x] api/config/jwt.php
- [x] api/helpers/response.php
- [x] api/helpers/auth.php
- [x] api/helpers/vip.php

---

# PHASE 2: PUBLIC PAGES (5 pages total)

## 2.1 Landing Page
- [x] public/index.html exists
- [ ] Redesign: Simple hero + pricing + FAQ
- [ ] Single "Start Free Trial" CTA button
- [ ] 3-plan pricing cards (Personal, Family, Business)
- [ ] Expandable FAQ section (5-10 common questions)
- [ ] Mobile responsive
- [ ] Uses theme CSS variables (no hardcoded colors)

## 2.2 Authentication Pages
- [x] public/login.html exists
- [ ] Simplify: Email + Password + Submit only
- [ ] Error messages inline (no alerts)
- [ ] "Forgot password?" link
- [ ] Auto-redirect to dashboard if logged in

- [x] public/register.html exists  
- [ ] Simplify: Email + Password + First Name (optional) + Submit
- [ ] "No credit card required" messaging
- [ ] Auto-login after registration
- [ ] Redirect to /dashboard/devices with welcome modal

- [ ] public/forgot-password.html
- [ ] Simple email input form
- [ ] Success message: "Check your email"

- [ ] public/reset-password.html
- [ ] New password + Confirm password
- [ ] Auto-login after reset

---

# PHASE 3: USER DASHBOARD (6 pages total)

## 3.1 Main Dashboard (Overview)
- [x] public/dashboard/index.html exists
- [ ] Redesign: Simple status overview
- [ ] Show: Current plan, devices used, trial days remaining
- [ ] Quick action buttons: Add Device, Upgrade
- [ ] In-app notification area (trial ending, etc.)

## 3.2 Devices Page (THE MOST IMPORTANT PAGE)
- [x] public/dashboard/devices.html exists
- [ ] Complete redesign for 2-click flow

### Add Device Modal
- [ ] Device name input
- [ ] Server selection (radio buttons with flags)
- [ ] "Add Device & Download Config" button
- [ ] Include tweetnacl.js for key generation
- [ ] Generate keypair in browser (private key never sent)
- [ ] Call API with public key only
- [ ] Show download screen with .conf file
- [ ] WireGuard download links

### Switch Server Modal
- [ ] Show current server
- [ ] Server selection (radio buttons)
- [ ] Generate new keypair on switch
- [ ] Call API to switch
- [ ] Show download screen with new .conf file

### Device List
- [ ] Card for each device showing name + server
- [ ] [Switch Server] button
- [ ] [Download Config] button (re-download)
- [ ] [Remove] button
- [ ] Empty state: "Add your first device"

## 3.3 Servers Page
- [x] public/dashboard/servers.html exists
- [ ] Simplify: Just show available servers with status
- [ ] Server name with flag
- [ ] Status indicator (green/yellow/red)
- [ ] Load percentage
- [ ] "Use this server" links to devices page

## 3.4 Account Page
- [x] public/dashboard/settings.html exists
- [ ] Rename to account.html
- [ ] Profile section: Name, Email (read-only)
- [ ] Change Password section
- [ ] Delete Account section (with confirmation)

## 3.5 Billing Page
- [x] public/dashboard/billing.html exists
- [ ] Current plan display
- [ ] Trial countdown (if on trial)
- [ ] Upgrade button → Plan selection modal
- [ ] Payment history list
- [ ] Cancel subscription button (with retention offer)

## 3.6 Help Page
- [ ] public/dashboard/help.html (NEW)
- [ ] FAQ accordion (same as landing page)
- [ ] Troubleshooting guide (common issues)
- [ ] Contact form (last resort)

---

# PHASE 4: DEVICE & SERVER APIs

## 4.1 Server API
- [x] api/vpn/servers.php exists
- [ ] Rename/create api/servers/available.php
- [ ] Return: id, name, display_name, flag, status, load
- [ ] Filter VIP server for non-VIP users
- [ ] Sort by display order

## 4.2 Device APIs
- [ ] api/devices/list.php - GET user's devices with server info
- [ ] api/devices/add.php - POST add device (THE 2-CLICK FLOW)
  - [ ] Validate device name
  - [ ] Validate server selection
  - [ ] Check device limit (VIP unlimited)
  - [ ] Call VPN server peer API
  - [ ] Save to database
  - [ ] Return config info (assigned_ip, server_public_key, endpoint)
- [ ] api/devices/switch.php - POST switch server
  - [ ] Remove peer from old server
  - [ ] Add peer to new server
  - [ ] Update database
  - [ ] Return new config info
- [ ] api/devices/remove.php - DELETE remove device
  - [ ] Remove peer from server
  - [ ] Delete from database
- [ ] api/devices/config.php - GET regenerate config (for re-download)

## 4.3 VPN Server Peer API (on each VPN server)
- [ ] peer_api.py deployed to all 4 servers
- [ ] POST /add_peer - Add WireGuard peer
- [ ] POST /remove_peer - Remove WireGuard peer
- [ ] GET /health - Health check
- [ ] GET /status - Peer count, load info

---

# PHASE 5: AUTHENTICATION APIs

## 5.1 Existing (Verify Working)
- [x] api/auth/login.php - FIXED
- [x] api/auth/register.php
- [ ] Update register to redirect to /dashboard/devices
- [ ] Update register to create trial subscription

## 5.2 Password Reset
- [ ] api/auth/forgot.php - POST request reset
  - [ ] Generate reset token
  - [ ] Send email with reset link
- [ ] api/auth/reset.php - POST reset password
  - [ ] Validate token
  - [ ] Update password
  - [ ] Auto-login

## 5.3 Profile
- [ ] api/user/profile.php - GET current user info
- [ ] api/user/profile.php - PUT update name
- [ ] api/user/password.php - POST change password

---

# PHASE 6: BILLING APIs

## 6.1 Subscription
- [x] api/billing/subscription.php exists
- [ ] Verify trial logic works
- [ ] VIP bypass working

## 6.2 PayPal Integration
- [x] api/billing/checkout.php exists
- [ ] Test with PayPal sandbox
- [x] api/billing/complete.php exists
- [ ] Test payment capture
- [x] api/billing/webhook.php exists
- [ ] Test webhook receives events
- [ ] Handle: PAYMENT.SALE.COMPLETED
- [ ] Handle: BILLING.SUBSCRIPTION.CANCELLED
- [ ] Handle: PAYMENT.SALE.DENIED

## 6.3 Cancellation
- [ ] api/billing/cancel.php - POST cancel subscription
  - [ ] Call PayPal API to cancel
  - [ ] Update database status
  - [ ] Schedule deactivation for end of period
  - [ ] Send confirmation email

## 6.4 Invoices
- [ ] api/billing/invoices.php - GET list payments
- [ ] Return: date, amount, status, receipt_url

---

# PHASE 7: AUTOMATION ENGINE

## 7.1 Core Engine
- [ ] api/automation/engine.php - AutomationEngine class
- [ ] Method: schedule($workflow, $data, $executeAt)
- [ ] Method: process() - Run due tasks
- [ ] Method: trigger($workflow, $data) - Immediate execution

## 7.2 Scheduled Task Processor
- [ ] api/cron/process.php - Main cron endpoint
- [ ] Query scheduled_tasks WHERE execute_at <= NOW AND status = 'pending'
- [ ] Execute each task
- [ ] Mark as 'completed' or 'failed'

## 7.3 Workflows to Implement
- [ ] trial_expiring_day5 - Show in-app notification
- [ ] trial_expiring_day6 - Show notification + send email
- [ ] trial_expired - Expire subscription, remove peers
- [ ] payment_failed_day0 - Send reminder email
- [ ] payment_failed_day3 - Send urgent email  
- [ ] payment_failed_day7 - Suspend access
- [ ] cancellation_confirmed - Send email, schedule deactivation
- [ ] winback_day14 - Send discount email

## 7.4 Email Sending
- [ ] api/helpers/mailer.php - Mailer class
- [ ] Method: send($to, $subject, $body)
- [ ] Method: sendTemplate($to, $template, $data)
- [ ] Use PHP mail() or integrate SendGrid/Mailgun

## 7.5 Email Templates
- [ ] templates/trial_ending.html
- [ ] templates/payment_receipt.html
- [ ] templates/payment_failed.html
- [ ] templates/payment_urgent.html
- [ ] templates/service_suspended.html
- [ ] templates/cancellation_confirmed.html
- [ ] templates/winback.html

---

# PHASE 8: ADMIN DASHBOARD (6 pages)

## 8.1 Admin Login
- [x] public/admin/index.html exists
- [ ] Simplify login form
- [ ] Separate admin auth from user auth

## 8.2 Admin Dashboard (Stats)
- [x] public/admin/dashboard.html exists
- [ ] Redesign with key metrics:
  - [ ] Today's signups
  - [ ] Today's conversions
  - [ ] Today's revenue
  - [ ] Active issues (usually 0)
- [ ] Server status cards (green/yellow/red)
- [ ] Recent activity feed
- [ ] "Needs Attention" section (usually empty)

## 8.3 User Management
- [x] public/admin/users.html exists
- [ ] Search/filter users
- [ ] Click to view user details
- [ ] Refund button
- [ ] Suspend/activate toggle

## 8.4 Server Management
- [x] public/admin/servers.html exists
- [ ] Show all servers with status
- [ ] Restart button for each
- [ ] Current peer count
- [ ] Load percentage

## 8.5 Payment Log
- [x] public/admin/payments.html exists
- [ ] List all transactions
- [ ] Filter by status
- [ ] Refund button

## 8.6 Settings & Theme
- [x] public/admin/settings.html exists
- [ ] Site settings (name, contact email)
- [ ] Theme editor (colors, fonts)
- [ ] Test theme changes live

---

# PHASE 9: ADMIN APIs

## 9.1 Stats
- [ ] api/admin/stats.php - GET dashboard stats
  - [ ] Today's signups
  - [ ] Today's conversions  
  - [ ] Today's revenue
  - [ ] Total active users
  - [ ] Total MRR

## 9.2 Users
- [ ] api/admin/users.php - GET list (paginated, searchable)
- [ ] api/admin/users.php/:id - GET single user details
- [ ] api/admin/users.php/:id/suspend - POST suspend user
- [ ] api/admin/users.php/:id/activate - POST activate user

## 9.3 Servers
- [ ] api/admin/servers.php - GET all servers with status
- [ ] api/admin/servers.php/:id/restart - POST restart services

## 9.4 Payments
- [ ] api/admin/payments.php - GET list (paginated, filterable)
- [ ] api/admin/payments.php/:id/refund - POST process refund

## 9.5 Theme
- [ ] api/admin/theme.php - GET current theme
- [ ] api/admin/theme.php - PUT update theme

---

# PHASE 10: HEALTH & MONITORING

## 10.1 Server Health Checks
- [ ] api/cron/health.php - Check all VPN servers
- [ ] Ping peer API on each server
- [ ] If down >5 min, attempt restart via SSH
- [ ] If down >10 min, send admin alert
- [ ] Update server status in database

## 10.2 Daily Report
- [ ] api/cron/daily-report.php - Generate stats email
- [ ] New signups
- [ ] New payments
- [ ] Failed payments
- [ ] Server issues
- [ ] Send to admin email

## 10.3 Cleanup
- [ ] api/cron/cleanup.php - Clean old data
- [ ] Delete expired sessions
- [ ] Delete old activity logs (>90 days)
- [ ] Delete expired password reset tokens

---

# PHASE 11: VIP SYSTEM

## 11.1 VIP Detection
- [x] VIPManager::isVIP() exists
- [ ] Verify works on login
- [ ] Verify works on registration
- [ ] Verify works on device add

## 11.2 VIP Privileges
- [ ] Bypass payment requirements
- [ ] Unlimited devices
- [ ] Access to dedicated server (144.126.133.253)
- [ ] No trial limitations

## 11.3 VIP Server Routing
- [ ] When VIP adds device, show dedicated server option
- [ ] Route VIP to dedicated server by default
- [ ] VIP can also use shared servers

---

# PHASE 12: TESTING

## 12.1 User Flow Tests
- [ ] Register → Dashboard → Add Device → Download Config
- [ ] Login → Switch Server → Download New Config
- [ ] Upgrade → PayPal → Payment Success
- [ ] Cancel → Confirmation → Service ends at period end

## 12.2 Trial Tests
- [ ] Trial Day 5 notification appears
- [ ] Trial Day 6 email sends
- [ ] Trial Day 7 expires, peers removed

## 12.3 Payment Tests
- [ ] PayPal checkout works (sandbox)
- [ ] Webhook updates subscription
- [ ] Failed payment triggers emails
- [ ] Refund processes correctly

## 12.4 VIP Tests
- [ ] seige235@yahoo.com logs in
- [ ] Bypasses payment
- [ ] Sees dedicated server option
- [ ] Unlimited devices

## 12.5 Admin Tests
- [ ] Admin login works
- [ ] Stats display correctly
- [ ] User search works
- [ ] Refund button works
- [ ] Theme changes apply

---

# PHASE 13: DEPLOYMENT

## 13.1 Pre-Deployment
- [ ] All APIs tested locally
- [ ] All frontend pages working
- [ ] PayPal switched to production credentials
- [ ] Remove any test/debug code

## 13.2 Upload
- [ ] Upload all files to vpn.the-truth-publishing.com
- [ ] Run database setup scripts
- [ ] Verify file permissions

## 13.3 Post-Deployment
- [ ] Test registration
- [ ] Test device add
- [ ] Test PayPal (small real payment)
- [ ] Verify webhooks receiving
- [ ] Setup cron jobs

## 13.4 Cron Jobs
- [ ] */5 * * * * - process.php (tasks + health)
- [ ] 0 * * * * - cleanup.php
- [ ] 0 0 * * * - daily-report.php

---

# PROGRESS TRACKER

| Phase | Status | Items | Complete |
|-------|--------|-------|----------|
| 1. Foundation | ✅ | 12 | 10 |
| 2. Public Pages | 🔄 | 15 | 4 |
| 3. User Dashboard | 🔄 | 25 | 6 |
| 4. Device/Server APIs | ❌ | 12 | 0 |
| 5. Auth APIs | 🔄 | 8 | 2 |
| 6. Billing APIs | 🔄 | 10 | 4 |
| 7. Automation | ❌ | 18 | 0 |
| 8. Admin Dashboard | 🔄 | 12 | 6 |
| 9. Admin APIs | ❌ | 10 | 0 |
| 10. Health/Monitoring | ❌ | 6 | 0 |
| 11. VIP System | 🔄 | 6 | 2 |
| 12. Testing | ❌ | 15 | 0 |
| 13. Deployment | ❌ | 10 | 0 |

**TOTAL: ~159 items**
**Complete: ~34 items (~21%)**

---

# NEXT ACTIONS (Priority Order)

## This Week
1. [ ] Redesign /dashboard/devices page with 2-click flow
2. [ ] Create api/devices/add.php
3. [ ] Create api/devices/switch.php
4. [ ] Create api/servers/available.php
5. [ ] Deploy peer_api.py to VPN servers

## Next Week
1. [ ] Complete billing flow (checkout → webhook → activation)
2. [ ] Create automation engine
3. [ ] Create email templates
4. [ ] Setup cron jobs

## Week After
1. [ ] Admin dashboard redesign
2. [ ] Admin APIs
3. [ ] Testing all flows

---

---

# PHASE 14: BUSINESS SETTINGS (Transfer-Ready) ❌ NOT STARTED

## 14.1 Database
- [ ] Create settings.db with business_settings table
- [ ] Create settings_history table (audit trail)
- [ ] Insert default settings structure
- [ ] Migrate PayPal credentials from hardcode to database

## 14.2 Settings API
- [ ] api/admin/settings.php - GET settings by category
- [ ] api/admin/settings.php - PUT update settings (with password confirm)
- [ ] api/admin/settings-history.php - GET change history
- [ ] Update Settings helper class with database getters

## 14.3 Admin UI - Business Settings Page
- [ ] public/admin/business.html - New page
- [ ] PayPal settings section (Client ID, Secret, Webhook ID, Mode)
- [ ] Bank account section (Bank name, Account #, Routing #, etc.)
- [ ] Business info section (Name, Email, Address, Tax ID)
- [ ] Technical settings section (Site URL, Admin Email, Timezone)
- [ ] Edit modals for each section
- [ ] Password confirmation for changes
- [ ] Settings change log display

## 14.4 Integration Updates
- [ ] Update PayPal checkout to use Settings::getPayPal*()
- [ ] Update PayPal webhook to use database credentials
- [ ] Update email templates to use Settings::getBusinessName()
- [ ] Update invoice generation to use business info
- [ ] Test settings changes apply immediately (no cache issues)

## 14.5 Transfer Documentation
- [ ] Create TRANSFER_CHECKLIST.md for new owners
- [ ] Document all settings that need updating
- [ ] Test complete transfer process

---

**END OF STREAMLINED CHECKLIST**
