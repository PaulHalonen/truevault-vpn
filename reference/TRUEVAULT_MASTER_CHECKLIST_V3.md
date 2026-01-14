# TRUEVAULT VPN - MASTER CHECKLIST v3
## Every Single Task to Launch
**Created:** January 13, 2026
**Status:** Work in progress

---

## HOW TO USE THIS CHECKLIST

- [ ] = Not started
- [~] = In progress
- [x] = Complete
- [!] = Blocked/Issue

Update this file after completing each task.
Current file location: `reference/TRUEVAULT_MASTER_CHECKLIST_V3.md`

---

## PHASE 1: DATABASE SETUP (18 tasks)
**Priority: CRITICAL - Must complete first**

### 1.1 Database Directory Structure
- [ ] Create `/databases/` directory on server
- [ ] Create `/databases/core/` subdirectory
- [ ] Create `/databases/vpn/` subdirectory  
- [ ] Create `/databases/billing/` subdirectory
- [ ] Create `/databases/cms/` subdirectory
- [ ] Create `/databases/logs/` subdirectory
- [ ] Set 777 permissions on all database directories

### 1.2 Core Database (users.db)
- [ ] Create users table with all fields
- [ ] Create user_devices table (consolidated)
- [ ] Create password_resets table
- [ ] Create indexes on email, public_key
- [ ] Verify VIP user detection works

### 1.3 VPN Database (servers.db, peers.db)
- [ ] Create vpn_servers table
- [ ] Insert 4 server records with real public keys
- [ ] Verify VIP server (id=2) has correct vip_user_email
- [ ] Create vpn_peers table
- [ ] Create server_health table

### 1.4 Billing Database (billing.db)
- [ ] Create plans table
- [ ] Insert 4 plan records (trial, personal, family, business)
- [ ] Create subscriptions table
- [ ] Create payments table

### 1.5 CMS Database (themes.db)
- [ ] Create themes table
- [ ] Create theme_variables table
- [ ] Insert default dark theme
- [ ] Insert all CSS variables (~25 variables)

### 1.6 Logs Database (logs.db)
- [ ] Create system_logs table
- [ ] Create email_queue table
- [ ] Create scheduled_tasks table
- [ ] Create indexes for performance

---

## PHASE 2: API FIXES (32 tasks)
**Priority: CRITICAL - Core functionality**

### 2.1 Fix Database Class Usage
- [ ] Remove all `getDatabase()` function calls
- [ ] Replace with `Database::getConnection()` class method
- [ ] Test database connections work
- [ ] Verify transactions work (begin, commit, rollback)

### 2.2 Authentication APIs
- [ ] Verify login.php works correctly
- [ ] Update register.php to create trial subscription
- [ ] Update register.php to check VIP email
- [ ] Update register.php to queue welcome email
- [ ] Implement forgot-password.php (token generation)
- [ ] Implement reset-password.php (password update)
- [ ] Test logout.php clears session
- [ ] Test refresh.php extends token

### 2.3 Device APIs (COMPLETE REWRITE)
- [ ] Rewrite list.php to use Database class
- [ ] Rewrite list.php to join with servers table
- [ ] Rewrite list.php to return correct response format
- [ ] Update add.php response format
- [ ] Create switch.php endpoint (server switching)
- [ ] Update remove.php to call server peer API
- [ ] Create config.php (re-download config data)
- [ ] Test device limit enforcement
- [ ] Test VIP unlimited devices

### 2.4 Server APIs
- [ ] Create /api/servers/ directory
- [ ] Create list.php that reads from database
- [ ] Remove hardcoded server data
- [ ] Add latency/load to response
- [ ] Filter VIP servers for non-VIP users
- [ ] Add recommended server logic

### 2.5 Billing APIs
- [ ] Verify subscription.php returns correct format
- [ ] Update checkout.php PayPal integration
- [ ] Complete webhook.php event handling
- [ ] Create cancel.php with retention
- [ ] Create history.php for payment list
- [ ] Test trial detection

### 2.6 User APIs
- [ ] Create /api/user/ directory
- [ ] Create profile.php GET endpoint
- [ ] Create profile.php PUT endpoint
- [ ] Create password.php PUT endpoint
- [ ] Test validation and error messages

---

## PHASE 3: DASHBOARD REBUILD (45 tasks)
**Priority: HIGH - User-facing**

### 3.1 Create Unified JavaScript
- [ ] Create dashboard/assets/js/app.js
- [ ] Add API configuration constants
- [ ] Add token management functions
- [ ] Add API call wrapper function
- [ ] Add error handling
- [ ] Add toast notification system
- [ ] Add modal helper functions
- [ ] Remove references to missing api.js and auth.js

### 3.2 Dashboard Home (index.html)
- [ ] Simplify layout to 4 stat cards
- [ ] Add welcome message with user name
- [ ] Add plan status card with trial countdown
- [ ] Add quick action buttons
- [ ] Connect to /api/billing/subscription.php
- [ ] Connect to /api/devices/list.php
- [ ] Test data displays correctly

### 3.3 Devices Page (CRITICAL)
- [ ] Replace devices.html with devices-new.html content
- [ ] Fix API call to /api/devices/list.php
- [ ] Fix API call to /api/servers/list.php
- [ ] Verify TweetNaCl.js CDN loads
- [ ] Test key generation in browser
- [ ] Test add device modal flow
- [ ] Test server selection UI
- [ ] Test config file download
- [ ] Test switch server modal
- [ ] Test device removal with confirmation
- [ ] Test empty state display
- [ ] Test device limit enforcement
- [ ] Add loading states to buttons
- [ ] Add error messages for failures

### 3.4 Servers Page (servers.html)
- [ ] Update API call to /api/servers/list.php
- [ ] Display server cards with flags
- [ ] Show load percentage
- [ ] Show latency
- [ ] Show rules summary
- [ ] Add "Use This Server" button
- [ ] Handle VIP-only servers

### 3.5 Billing Page (billing.html)
- [ ] Display current plan
- [ ] Display trial countdown (if trial)
- [ ] Display payment method
- [ ] Add upgrade section
- [ ] Add cancel button with retention modal
- [ ] Display payment history
- [ ] Connect to all billing APIs

### 3.6 Settings Page (settings.html)
- [ ] Create profile edit form
- [ ] Create password change form
- [ ] Create delete account section
- [ ] Connect to /api/user/profile.php
- [ ] Connect to /api/user/password.php
- [ ] Add validation and error messages

### 3.7 Remove Unnecessary Pages
- [ ] Delete connect.html (redundant)
- [ ] Delete cameras.html (post-launch)
- [ ] Delete scanner.html (post-launch)
- [ ] Delete certificates.html (post-launch)
- [ ] Delete old devices.html (replaced)
- [ ] Update sidebar navigation

---

## PHASE 4: PUBLIC PAGES (18 tasks)
**Priority: HIGH - User onboarding**

### 4.1 Landing Page (index.html)
- [ ] Simplify hero section
- [ ] Add single CTA button
- [ ] Add 3-4 feature highlights
- [ ] Add pricing section
- [ ] Add FAQ accordion
- [ ] Remove complex feature grids
- [ ] Ensure mobile responsive

### 4.2 Login Page (login.html)
- [ ] Add forgot password link
- [ ] Add inline error messages
- [ ] Add loading state on button
- [ ] Redirect if already logged in
- [ ] Connect to /api/auth/login.php
- [ ] Handle VIP login special case

### 4.3 Register Page (register.html)
- [ ] Keep only essential fields (email, password, name)
- [ ] Add "No credit card required" message
- [ ] Add loading state
- [ ] Connect to /api/auth/register.php
- [ ] Auto-redirect to dashboard on success

### 4.4 Password Reset Pages
- [ ] Create forgot-password.html
- [ ] Create reset-password.html
- [ ] Connect to respective APIs
- [ ] Add success/error states

---

## PHASE 5: VPN SERVER DEPLOYMENT (20 tasks)
**Priority: CRITICAL - VPN functionality**

### 5.1 Prepare peer_api.py
- [ ] Finalize peer_api.py script
- [ ] Test locally if possible
- [ ] Create systemd service file
- [ ] Document API endpoints

### 5.2 Deploy to Server 1 (New York - 66.94.103.91)
- [ ] SSH into server
- [ ] Create /opt/truevault directory
- [ ] Copy peer_api.py
- [ ] Install Flask dependency
- [ ] Copy systemd service file
- [ ] Set API key environment variable
- [ ] Enable and start service
- [ ] Verify /health endpoint
- [ ] Test /add_peer endpoint
- [ ] Test /remove_peer endpoint

### 5.3 Deploy to Server 2 (St. Louis VIP - 144.126.133.253)
- [ ] Repeat deployment steps
- [ ] Verify exclusive VIP access

### 5.4 Deploy to Server 3 (Dallas - 66.241.124.4)
- [ ] Repeat deployment steps
- [ ] Note: Uses port 8443

### 5.5 Deploy to Server 4 (Toronto - 66.241.125.247)
- [ ] Repeat deployment steps

### 5.6 Update Database with Server Public Keys
- [ ] Get public key from each server
- [ ] Update vpn_servers table
- [ ] Verify API can communicate with servers

---

## PHASE 6: EMAIL SYSTEM (12 tasks)
**Priority: MEDIUM - Automation**

### 6.1 Configure Mailer
- [ ] Choose method: PHP mail() or SMTP
- [ ] Configure credentials in mailer.php
- [ ] Test email delivery
- [ ] Set From address and name

### 6.2 Create Email Templates
- [ ] Create welcome email template
- [ ] Create trial ending template
- [ ] Create payment failed template
- [ ] Create payment received template
- [ ] Store templates in database or files

### 6.3 Email Queue Processing
- [ ] Implement queue processing in cron
- [ ] Add retry logic for failures
- [ ] Add logging for sent emails
- [ ] Test email workflow end-to-end

---

## PHASE 7: AUTOMATION & CRON (15 tasks)
**Priority: MEDIUM - Hands-off operation**

### 7.1 Create Cron Process Script
- [ ] Create api/cron/process.php
- [ ] Add trial expiration checker
- [ ] Add payment retry scheduler
- [ ] Add email queue processor
- [ ] Add server health checker
- [ ] Add logging

### 7.2 Trial Expiration Workflow
- [ ] Detect trials ending in 3 days
- [ ] Queue reminder email
- [ ] Detect expired trials
- [ ] Suspend expired accounts
- [ ] Disconnect devices from servers

### 7.3 Payment Failed Workflow
- [ ] Implement grace period logic
- [ ] Schedule follow-up emails
- [ ] Implement suspension after 10 days

### 7.4 Setup Cron on Server
- [ ] Add cron job to crontab
- [ ] Set to run every 5 minutes
- [ ] Verify cron executes
- [ ] Check logs for errors

---

## PHASE 8: PAYPAL INTEGRATION (14 tasks)
**Priority: HIGH - Revenue**

### 8.1 PayPal Configuration
- [ ] Verify PayPal API credentials
- [ ] Create subscription plans in PayPal dashboard
- [ ] Note plan IDs
- [ ] Update constants.php with IDs
- [ ] Verify webhook URL registered

### 8.2 Checkout Flow
- [ ] Test plan selection
- [ ] Test PayPal redirect
- [ ] Test return URL handling
- [ ] Test cancel URL handling

### 8.3 Webhook Handler
- [ ] Handle BILLING.SUBSCRIPTION.CREATED
- [ ] Handle BILLING.SUBSCRIPTION.ACTIVATED
- [ ] Handle PAYMENT.SALE.COMPLETED
- [ ] Handle BILLING.SUBSCRIPTION.CANCELLED
- [ ] Handle BILLING.SUBSCRIPTION.SUSPENDED
- [ ] Handle PAYMENT.SALE.DENIED
- [ ] Add signature verification
- [ ] Add logging

### 8.4 Testing
- [ ] Test with PayPal sandbox
- [ ] Create test subscription
- [ ] Verify database updates
- [ ] Test cancellation
- [ ] Switch to live mode

---

## PHASE 9: ADMIN PANEL (16 tasks)
**Priority: MEDIUM - Management**

### 9.1 Admin Authentication
- [ ] Verify admin login works
- [ ] Create admin user in database
- [ ] Test admin token validation

### 9.2 Admin Dashboard (index.html)
- [ ] Display user count
- [ ] Display active subscriptions
- [ ] Display server status
- [ ] Display recent signups
- [ ] Display recent payments

### 9.3 User Management (users.html)
- [ ] List all users
- [ ] Search users by email
- [ ] View user details
- [ ] Suspend/activate user
- [ ] Delete user
- [ ] View user's devices

### 9.4 Server Management (servers.html)
- [ ] List all servers
- [ ] Show connection count
- [ ] Show health status
- [ ] Edit server details
- [ ] Toggle server status

### 9.5 Site Settings (settings.html)
- [ ] PayPal credentials (editable)
- [ ] Business info (editable)
- [ ] Theme selection
- [ ] Email configuration

---

## PHASE 10: CSS & THEMING (8 tasks)
**Priority: LOW - Polish**

### 10.1 Database-Driven CSS
- [ ] Create API endpoint for theme variables
- [ ] Generate CSS from database on page load
- [ ] Remove all hardcoded colors from CSS
- [ ] Test theme changes apply

### 10.2 Responsive Design
- [ ] Test on mobile devices
- [ ] Fix any layout issues
- [ ] Test sidebar collapse
- [ ] Test modals on mobile

---

## PHASE 11: TESTING (20 tasks)
**Priority: CRITICAL - Quality**

### 11.1 Registration Flow
- [ ] Test new user registration
- [ ] Verify trial created
- [ ] Verify welcome email sent
- [ ] Test VIP email registration
- [ ] Test duplicate email rejection

### 11.2 Device Flow (MOST IMPORTANT)
- [ ] Test add device with real server
- [ ] Verify WireGuard config is valid
- [ ] Test VPN connection works
- [ ] Test switch server
- [ ] Test remove device
- [ ] Test device limit enforcement

### 11.3 VIP Flow
- [ ] Register with seige235@yahoo.com
- [ ] Verify VIP badge shown
- [ ] Verify dedicated server assigned
- [ ] Verify unlimited devices
- [ ] Verify payment bypassed

### 11.4 Payment Flow
- [ ] Test trial to paid upgrade
- [ ] Test PayPal redirect
- [ ] Test webhook updates subscription
- [ ] Test payment receipt email
- [ ] Test cancellation

### 11.5 Edge Cases
- [ ] Test expired token handling
- [ ] Test invalid API requests
- [ ] Test server offline handling
- [ ] Test database errors

---

## PHASE 12: DEPLOYMENT (10 tasks)
**Priority: CRITICAL - Go live**

### 12.1 Pre-Deployment
- [ ] Backup current production (if any)
- [ ] Document rollback procedure
- [ ] Prepare FTP credentials

### 12.2 Production Deploy
- [ ] Upload all files via FTP
- [ ] Run setup-all.php to initialize databases
- [ ] Verify all databases created
- [ ] Set correct file permissions
- [ ] Configure .htaccess

### 12.3 Post-Deploy Verification
- [ ] Test login works
- [ ] Test registration works
- [ ] Test device add works
- [ ] Test VPN connection works
- [ ] Test payment works (sandbox)
- [ ] Enable live PayPal mode
- [ ] Monitor logs for errors

---

## PHASE 13: DOCUMENTATION (6 tasks)
**Priority: LOW - Transfer prep**

### 13.1 User Documentation
- [ ] Create quick start guide
- [ ] Create FAQ page
- [ ] Add help tooltips in UI

### 13.2 Transfer Documentation
- [ ] Document admin credentials
- [ ] Document PayPal configuration
- [ ] Document server access
- [ ] Create transfer checklist

---

## QUICK REFERENCE: CURRENT STATUS

**As of January 13, 2026:**

| Phase | Tasks | Complete | Remaining |
|-------|-------|----------|-----------|
| 1. Database | 18 | 0 | 18 |
| 2. API Fixes | 32 | ~10 | ~22 |
| 3. Dashboard | 45 | ~15 | ~30 |
| 4. Public Pages | 18 | ~5 | ~13 |
| 5. Server Deploy | 20 | 0 | 20 |
| 6. Email | 12 | 0 | 12 |
| 7. Automation | 15 | 0 | 15 |
| 8. PayPal | 14 | ~5 | ~9 |
| 9. Admin | 16 | ~8 | ~8 |
| 10. CSS | 8 | ~2 | ~6 |
| 11. Testing | 20 | 0 | 20 |
| 12. Deploy | 10 | 0 | 10 |
| 13. Docs | 6 | 0 | 6 |
| **TOTAL** | **234** | **~45** | **~189** |

**Estimated Completion: ~19% (45/234 tasks)**

---

## FAILSAFE RECOVERY

If chat crashes or resets:
1. Read this checklist to see progress
2. Read chat_log.txt for session history
3. Continue from last checked item
4. Always update checklist as you work

**Git Commit Frequently:**
```bash
cd E:\Documents\GitHub\truevault-vpn
git add -A
git commit -m "Progress update: [what was done]"
git push
```

---

**END OF MASTER CHECKLIST v3**

Last updated: January 13, 2026
