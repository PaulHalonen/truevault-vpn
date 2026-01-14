# TrueVault VPN - COMPLETE BUILD CHECKLIST
## Every Step Required to Build the Entire System
**Created:** January 11, 2026
**Last Updated:** January 13, 2026 - 10:30 PM CST

---

# PROJECT INFORMATION

- **Project Name:** TrueVault VPN™
- **Local Development:** `E:\Documents\GitHub\truevault-vpn`
- **Production Server:** `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com`
- **Database Type:** SQLite (Separate files - NOT clumped)
- **Theme System:** 100% Database-driven (NO HARDCODE anywhere)

---

# ⚠️ CRITICAL BUILD RULES - READ BEFORE ANY WORK

## RULE 1: NO PLACEHOLDERS - REAL CODE ONLY
- Every file created MUST contain FULLY FUNCTIONAL code
- NO placeholder text like "TODO", "Coming soon", "Implement later"
- NO dummy functions that just return mock data
- NO commented-out code blocks waiting to be finished
- If a feature can't be built yet, DON'T create the file
- Every API endpoint must actually connect to the database and work
- Every frontend page must actually call real APIs

## RULE 2: NO HARDCODED STYLES
- ALL colors come from themes.db → CSS variables
- ALL fonts come from themes.db → CSS variables  
- ALL button styles come from themes.db → CSS variables
- ALL spacing/layout come from themes.db → CSS variables
- If you see ANY hex color (#ffffff) in HTML/CSS, it's WRONG
- If you see ANY font-family in HTML/CSS, it's WRONG
- ONLY use: var(--colors-primary), var(--typography-font-family), etc.

## RULE 3: DATABASE-DRIVEN EVERYTHING
- Page content: from database (CMS)
- Email templates: from database
- Theme/styling: from database (themes.db)
- Settings: from database
- Server configs: from database
- NOTHING hardcoded that should be configurable

## RULE 4: ALWAYS APPEND TO CHAT LOG
- Every session: APPEND to chat_log.txt
- Include: date, time, what was done, what was changed
- NEVER overwrite - only append
- This is the project history

## RULE 5: VIP SERVER RULE
- Server 144.126.133.253 is DEDICATED to seige235@yahoo.com
- Check VIP status on EVERY VPN connection
- VIP users bypass payment requirements
- VIP users get unlimited devices/cameras

---

# PLACEHOLDER AUDIT CHECKLIST (NEW)

## Files to Audit for Placeholders:
- [ ] Audit all /api/*.php files for placeholder code
- [ ] Audit all /public/*.html files for hardcoded styles
- [ ] Audit all /public/dashboard/*.html for hardcoded styles
- [ ] Audit all /public/admin/*.html for hardcoded styles  
- [ ] Audit /public/assets/css/*.css for hardcoded colors/fonts
- [ ] Replace any mock/dummy data with real database calls
- [ ] Replace any "TODO" comments with working code
- [ ] Remove any console.log debugging statements
- [ ] Verify all forms submit to real endpoints
- [ ] Verify all buttons have real click handlers

---

# PHASE 1: PROJECT SETUP & FOUNDATION

## 1.1 Repository Setup
- [x] Create GitHub repository "truevault-vpn"
- [x] Clone repository to E:\Documents\GitHub\truevault-vpn
- [x] Create .gitignore file
- [x] Create initial README.md

## 1.2 Directory Structure Creation
- [x] Create /api folder
- [x] Create /api/config folder
- [x] Create /api/auth folder
- [x] Create /api/users folder
- [x] Create /api/vpn folder
- [x] Create /api/certificates folder
- [x] Create /api/devices folder
- [x] Create /api/cameras folder
- [x] Create /api/port-forwarding folder
- [x] Create /api/mesh folder
- [x] Create /api/billing folder
- [x] Create /api/admin folder
- [x] Create /api/scanner folder
- [x] Create /api/automation folder
- [x] Create /api/helpers folder
- [x] Create /dashboard folder
- [x] Create /dashboard/assets folder
- [x] Create /dashboard/assets/css folder
- [x] Create /dashboard/assets/js folder
- [x] Create /admin folder
- [x] Create /admin/assets folder
- [x] Create /admin/assets/css folder
- [x] Create /admin/assets/js folder
- [x] Create /business folder
- [x] Create /business/assets folder
- [x] Create /business/assets/css folder
- [x] Create /business/assets/js folder
- [x] Create /business/db-designer folder
- [x] Create /business/page-builder folder
- [x] Create /business/accounting folder
- [x] Create /databases folder
- [x] Create /databases/core folder
- [x] Create /databases/vpn folder
- [x] Create /databases/devices folder
- [x] Create /databases/billing folder
- [x] Create /databases/cms folder
- [x] Create /databases/automation folder
- [x] Create /databases/analytics folder
- [x] Create /downloads folder
- [x] Create /downloads/scanner folder
- [x] Create /downloads/configs folder
- [x] Create /downloads/certificates folder
- [x] Create /reference folder

## 1.3 Reference Documentation
- [x] Create reference/TRUEVAULT_VPN_MASTER_PLAN.md
- [x] Create reference/TRUEVAULT_COMPLETE_BUILD_CHECKLIST.md (this file)
- [x] Create reference/TRUEVAULT_USER_VISION.md
- [x] Create reference/chat_log.txt

## 1.4 Server Verification
- [ ] SSH into Contabo Server 1 (66.94.103.91)
- [ ] Verify WireGuard is installed on Server 1
- [ ] Check for existing scripts on Server 1 (/opt/, /root/, /etc/wireguard/)
- [ ] Document Server 1 WireGuard public key
- [ ] SSH into Contabo Server 2 (144.126.133.253)
- [ ] Verify WireGuard is installed on Server 2
- [ ] Check for existing scripts on Server 2
- [ ] Document Server 2 WireGuard public key
- [ ] Verify Fly.io Dallas server (66.241.124.4) is running
- [ ] Check Fly.io Dallas WireGuard configuration
- [ ] Document Fly.io Dallas public key
- [ ] Verify Fly.io Toronto server (66.241.125.247) is running
- [ ] Check Fly.io Toronto WireGuard configuration
- [ ] Document Fly.io Toronto public key

## 1.5 Server API Setup (On VPN Servers)
- [ ] Create certificate generation script on Server 1
- [ ] Create WireGuard key generation script on Server 1
- [ ] Create peer management script on Server 1
- [ ] Create health check endpoint on Server 1
- [ ] Test Server 1 API endpoints
- [ ] Create certificate generation script on Server 2
- [ ] Create WireGuard key generation script on Server 2
- [ ] Create peer management script on Server 2
- [ ] Create health check endpoint on Server 2
- [ ] Test Server 2 API endpoints
- [ ] Verify/create Fly.io Dallas API endpoints
- [ ] Verify/create Fly.io Toronto API endpoints

---

# PHASE 2: DATABASE CREATION

## 2.1 Database Setup Script
- [ ] Create api/config/setup-databases.php
- [ ] Add function to create database directory if not exists
- [ ] Add function to create SQLite database file
- [ ] Add function to execute SQL schema
- [ ] Add error handling and logging

## 2.2 Core Databases

### 2.2.1 users.db
- [ ] Create databases/core/users.db file
- [ ] Create users table
- [ ] Create user_settings table
- [ ] Create user_devices table
- [ ] Create indexes on users.email
- [ ] Create indexes on users.uuid
- [ ] Create indexes on user_devices.user_id

### 2.2.2 sessions.db
- [ ] Create databases/core/sessions.db file
- [ ] Create sessions table (id, user_id, token, ip_address, user_agent, created_at, expires_at)
- [ ] Create refresh_tokens table
- [ ] Create indexes on sessions.token
- [ ] Create indexes on sessions.user_id

### 2.2.3 admin.db
- [ ] Create databases/core/admin.db file
- [ ] Create admin_users table
- [ ] Create admin_roles table
- [ ] Create admin_permissions table
- [ ] Create role_permissions table
- [ ] Insert default admin user (kahlen@truthvault.com)
- [ ] Insert default roles (super_admin, admin, moderator)
- [ ] Insert default permissions

## 2.3 VPN Databases

### 2.3.1 servers.db
- [ ] Create databases/vpn/servers.db file
- [ ] Create vpn_servers table
- [ ] Create server_configs table
- [ ] Insert Contabo US-East server record (66.94.103.91, shared)
- [ ] Insert Contabo US-Central server record (144.126.133.253, vip, seige235@yahoo.com)
- [ ] Insert Fly.io Dallas server record (66.241.124.4, shared)
- [ ] Insert Fly.io Toronto server record (66.241.125.247, shared)

### 2.3.2 connections.db
- [ ] Create databases/vpn/connections.db file
- [ ] Create active_connections table
- [ ] Create connection_history table
- [ ] Create indexes on active_connections.user_id
- [ ] Create indexes on active_connections.server_id

### 2.3.3 certificates.db
- [ ] Create databases/vpn/certificates.db file
- [ ] Create certificate_authority table
- [ ] Create user_certificates table
- [ ] Create certificate_revocations table
- [ ] Create indexes on user_certificates.user_id

### 2.3.4 identities.db
- [ ] Create databases/vpn/identities.db file
- [ ] Create regional_identities table
- [ ] Create identity_fingerprints table
- [ ] Insert default regions (us, ca, uk, eu, au, jp)
- [ ] Create indexes on regional_identities.user_id

### 2.3.5 routing.db
- [ ] Create databases/vpn/routing.db file
- [ ] Create routing_rules table
- [ ] Create routing_patterns table
- [ ] Insert default routing rules (banking, streaming, gaming, privacy)

## 2.4 Device Databases

### 2.4.1 discovered.db
- [ ] Create databases/devices/discovered.db file
- [ ] Create discovered_devices table
- [ ] Create device_ports table
- [ ] Create indexes on discovered_devices.user_id
- [ ] Create indexes on discovered_devices.mac_address

### 2.4.2 cameras.db
- [ ] Create databases/devices/cameras.db file
- [ ] Create discovered_cameras table
- [ ] Create camera_settings table
- [ ] Create camera_events table
- [ ] Create camera_recordings table
- [ ] Create indexes on discovered_cameras.user_id

### 2.4.3 port_forwarding.db
- [ ] Create databases/devices/port_forwarding.db file
- [ ] Create port_forwarding_rules table
- [ ] Create indexes on port_forwarding_rules.user_id

### 2.4.4 mesh_network.db
- [ ] Create databases/devices/mesh_network.db file
- [ ] Create mesh_networks table
- [ ] Create mesh_members table
- [ ] Create mesh_invitations table
- [ ] Create mesh_connections table
- [ ] Create indexes on mesh_members.user_id
- [ ] Create indexes on mesh_members.network_id

## 2.5 Billing Databases

### 2.5.1 subscriptions.db
- [ ] Create databases/billing/subscriptions.db file
- [ ] Create subscriptions table
- [ ] Create subscription_plans table
- [ ] Insert plan: Personal ($9.99, 3 devices, 3 identities)
- [ ] Insert plan: Family ($14.99, unlimited devices, 6 mesh users)
- [ ] Insert plan: Business ($29.99, unlimited, 25 mesh users, API)
- [ ] Create indexes on subscriptions.user_id

### 2.5.2 invoices.db
- [ ] Create databases/billing/invoices.db file
- [ ] Create invoices table
- [ ] Create invoice_items table
- [ ] Create indexes on invoices.user_id

### 2.5.3 payments.db
- [ ] Create databases/billing/payments.db file
- [ ] Create payments table
- [ ] Create payment_methods table
- [ ] Create indexes on payments.user_id
- [ ] Create indexes on payments.invoice_id

### 2.5.4 transactions.db
- [ ] Create databases/billing/transactions.db file
- [ ] Create transactions table
- [ ] Create indexes on transactions.user_id

## 2.6 CMS Databases

### 2.6.1 pages.db
- [ ] Create databases/cms/pages.db file
- [ ] Create pages table (id, slug, title, content, grapesjs_data, status, created_at, updated_at)
- [ ] Create page_versions table
- [ ] Insert default landing page

### 2.6.2 themes.db (CRITICAL - ALL STYLING HERE)
- [ ] Create databases/cms/themes.db file
- [ ] Create themes table
- [ ] Create theme_settings table
- [ ] Insert theme: "TrueVault Dark" (active)
- [ ] Insert color: primary = #00d9ff
- [ ] Insert color: secondary = #00ff88
- [ ] Insert color: accent = #ff6b6b
- [ ] Insert color: background = #0f0f1a
- [ ] Insert color: background_card = rgba(255,255,255,0.04)
- [ ] Insert color: text = #ffffff
- [ ] Insert color: text_muted = #888888
- [ ] Insert color: success = #00ff88
- [ ] Insert color: warning = #ffbb00
- [ ] Insert color: error = #ff5050
- [ ] Insert color: border = rgba(255,255,255,0.08)
- [ ] Insert typography: font_family = -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif
- [ ] Insert typography: heading_font = inherit
- [ ] Insert typography: font_size_base = 16px
- [ ] Insert typography: line_height = 1.5
- [ ] Insert buttons: border_radius = 8px
- [ ] Insert buttons: padding = 10px 20px
- [ ] Insert buttons: primary_gradient = linear-gradient(90deg, #00d9ff, #00ff88)
- [ ] Insert layout: max_width = 1200px
- [ ] Insert layout: sidebar_width = 250px
- [ ] Insert layout: spacing_unit = 8px

### 2.6.3 templates.db
- [ ] Create databases/cms/templates.db file
- [ ] Create email_templates table
- [ ] Create page_templates table
- [ ] Insert email template: welcome
- [ ] Insert email template: invoice
- [ ] Insert email template: payment_failed_day0
- [ ] Insert email template: payment_failed_day3
- [ ] Insert email template: payment_failed_day7
- [ ] Insert email template: scanner_ready
- [ ] Insert email template: certificate_ready
- [ ] Insert email template: subscription_reminder

### 2.6.4 media.db
- [ ] Create databases/cms/media.db file
- [ ] Create media_files table
- [ ] Create media_folders table

## 2.7 Automation Databases

### 2.7.1 workflows.db
- [ ] Create databases/automation/workflows.db file
- [ ] Create workflows table
- [ ] Create workflow_steps table
- [ ] Create workflow_triggers table
- [ ] Insert workflow: new_user_signup
- [ ] Insert workflow: scanner_sync
- [ ] Insert workflow: payment_success
- [ ] Insert workflow: payment_failed
- [ ] Insert workflow: certificate_generation
- [ ] Insert workflow: server_health_check
- [ ] Insert workflow: subscription_expiring

### 2.7.2 tasks.db
- [ ] Create databases/automation/tasks.db file
- [ ] Create scheduled_tasks table
- [ ] Create task_history table
- [ ] Create indexes on scheduled_tasks.execute_at

### 2.7.3 logs.db
- [ ] Create databases/automation/logs.db file
- [ ] Create automation_logs table
- [ ] Create indexes on automation_logs.workflow_id

### 2.7.4 emails.db
- [ ] Create databases/automation/emails.db file
- [ ] Create email_queue table
- [ ] Create email_history table
- [ ] Create indexes on email_queue.status

## 2.8 Analytics Databases

### 2.8.1 usage.db
- [ ] Create databases/analytics/usage.db file
- [ ] Create usage_stats table
- [ ] Create daily_usage table
- [ ] Create indexes on usage_stats.user_id

### 2.8.2 bandwidth.db
- [ ] Create databases/analytics/bandwidth.db file
- [ ] Create bandwidth_usage table
- [ ] Create bandwidth_daily table
- [ ] Create indexes on bandwidth_usage.user_id

### 2.8.3 events.db
- [ ] Create databases/analytics/events.db file
- [ ] Create events table
- [ ] Create indexes on events.user_id
- [ ] Create indexes on events.event_type

## 2.9 Database Verification
- [ ] Run setup-databases.php script
- [ ] Verify all 21 database files created
- [ ] Verify all tables created with correct schemas
- [ ] Verify all default data inserted
- [ ] Verify all indexes created
- [ ] Test database connections from PHP

---

# PHASE 3: API CORE INFRASTRUCTURE

## 3.1 Configuration Files

### 3.1.1 api/config/database.php
- [ ] Create database.php file
- [ ] Create DatabaseManager class
- [ ] Add method: getConnection($dbName) - returns SQLite PDO connection
- [ ] Add method: getCorePath() - returns core database path
- [ ] Add method: getVpnPath() - returns vpn database path
- [ ] Add method: getDevicesPath() - returns devices database path
- [ ] Add method: getBillingPath() - returns billing database path
- [ ] Add method: getCmsPath() - returns cms database path
- [ ] Add method: getAutomationPath() - returns automation database path
- [ ] Add method: getAnalyticsPath() - returns analytics database path
- [ ] Add error handling for missing databases
- [ ] Add connection caching to avoid multiple connections

### 3.1.2 api/config/jwt.php
- [ ] Create jwt.php file
- [ ] Define JWT_SECRET constant
- [ ] Define JWT_ALGORITHM constant (HS256)
- [ ] Define JWT_EXPIRY constant (7 days)
- [ ] Create JWTManager class
- [ ] Add method: generateToken($userId, $email, $isAdmin)
- [ ] Add method: validateToken($token)
- [ ] Add method: refreshToken($token)
- [ ] Add method: decodeToken($token)
- [ ] Add method: getTokenFromHeader()

### 3.1.3 api/config/settings.php
- [ ] Create settings.php file
- [ ] Create Settings class
- [ ] Add method: get($key) - get setting from database
- [ ] Add method: set($key, $value) - save setting to database
- [ ] Add method: getTheme() - get active theme settings
- [ ] Add method: getColors() - get theme colors
- [ ] Add method: getTypography() - get typography settings
- [ ] Add method: getButtons() - get button styles
- [ ] Add method: getLayout() - get layout settings
- [ ] Add caching for frequently accessed settings

### 3.1.4 api/config/constants.php
- [ ] Create constants.php file
- [ ] Define SITE_URL constant
- [ ] Define API_VERSION constant
- [ ] Define UPLOAD_PATH constant
- [ ] Define MAX_UPLOAD_SIZE constant
- [ ] Define ALLOWED_ORIGINS constant
- [ ] Define VPN server IPs
- [ ] Define PayPal credentials

## 3.2 Helper Files

### 3.2.1 api/helpers/response.php
- [ ] Create response.php file
- [ ] Create Response class
- [ ] Add method: success($data, $message, $code)
- [ ] Add method: error($message, $code, $errors)
- [ ] Add method: paginated($data, $page, $perPage, $total)
- [ ] Add method: sendJson($data, $code)
- [ ] Add CORS headers handling

### 3.2.2 api/helpers/validator.php
- [ ] Create validator.php file
- [ ] Create Validator class
- [ ] Add method: required($value, $field)
- [ ] Add method: email($value)
- [ ] Add method: minLength($value, $min)
- [ ] Add method: maxLength($value, $max)
- [ ] Add method: numeric($value)
- [ ] Add method: uuid($value)
- [ ] Add method: ip($value)
- [ ] Add method: mac($value)
- [ ] Add method: validate($data, $rules)
- [ ] Add method: getErrors()

### 3.2.3 api/helpers/mailer.php
- [ ] Create mailer.php file
- [ ] Create Mailer class
- [ ] Add PHPMailer integration or native mail()
- [ ] Add method: send($to, $subject, $body, $isHtml)
- [ ] Add method: sendTemplate($to, $templateName, $variables)
- [ ] Add method: queue($to, $subject, $body) - add to email queue
- [ ] Add method: processQueue() - send queued emails

### 3.2.4 api/helpers/encryption.php
- [ ] Create encryption.php file
- [ ] Create Encryption class
- [ ] Add method: encrypt($data, $key)
- [ ] Add method: decrypt($data, $key)
- [ ] Add method: hashPassword($password)
- [ ] Add method: verifyPassword($password, $hash)
- [ ] Add method: generateUUID()
- [ ] Add method: generateToken($length)
- [ ] Add method: generateApiKey()

### 3.2.5 api/helpers/logger.php
- [ ] Create logger.php file
- [ ] Create Logger class
- [ ] Add method: info($message, $context)
- [ ] Add method: error($message, $context)
- [ ] Add method: warning($message, $context)
- [ ] Add method: debug($message, $context)
- [ ] Add log file rotation
- [ ] Add log to database option

### 3.2.6 api/helpers/auth.php
- [ ] Create auth.php file
- [ ] Create Auth class
- [ ] Add method: requireAuth() - validate JWT, return user
- [ ] Add method: requireAdmin() - validate JWT + admin role
- [ ] Add method: optionalAuth() - validate if token present
- [ ] Add method: getCurrentUser()
- [ ] Add method: isVipUser($userId)

---

# PHASE 4: AUTHENTICATION API

## 4.1 api/auth/register.php
- [ ] Create register.php file
- [ ] Accept POST request with email, password, first_name, last_name
- [ ] Validate all input fields
- [ ] Check if email already exists
- [ ] Hash password
- [ ] Generate UUID for user
- [ ] Insert user into users.db
- [ ] Generate JWT token
- [ ] Create session record
- [ ] Trigger new_user_signup workflow
- [ ] Return success with token and user data
- [ ] Test registration endpoint

## 4.2 api/auth/login.php
- [ ] Create login.php file
- [ ] Accept POST request with email, password
- [ ] Validate input
- [ ] Find user by email
- [ ] Verify password hash
- [ ] Check if user is active
- [ ] Generate JWT token
- [ ] Create session record
- [ ] Update last_login timestamp
- [ ] Return success with token and user data
- [ ] Test login endpoint

## 4.3 api/auth/logout.php
- [ ] Create logout.php file
- [ ] Accept POST request with token
- [ ] Validate token
- [ ] Delete session record
- [ ] Return success
- [ ] Test logout endpoint

## 4.4 api/auth/refresh.php
- [ ] Create refresh.php file
- [ ] Accept POST request with refresh_token
- [ ] Validate refresh token
- [ ] Generate new JWT token
- [ ] Generate new refresh token
- [ ] Update session record
- [ ] Return new tokens
- [ ] Test refresh endpoint

## 4.5 api/auth/verify-email.php
- [ ] Create verify-email.php file
- [ ] Accept GET request with verification token
- [ ] Validate token
- [ ] Update user email_verified flag
- [ ] Return success or redirect
- [ ] Test verification endpoint

## 4.6 api/auth/forgot-password.php
- [ ] Create forgot-password.php file
- [ ] Accept POST request with email
- [ ] Find user by email
- [ ] Generate reset token
- [ ] Store reset token with expiry
- [ ] Send reset email
- [ ] Return success
- [ ] Test forgot password endpoint

## 4.7 api/auth/reset-password.php
- [ ] Create reset-password.php file
- [ ] Accept POST request with token, new_password
- [ ] Validate reset token
- [ ] Check token not expired
- [ ] Hash new password
- [ ] Update user password
- [ ] Invalidate reset token
- [ ] Return success
- [ ] Test reset password endpoint

---

# PHASE 5: USER API

## 5.1 api/users/profile.php
- [ ] Create profile.php file
- [ ] GET: Return current user profile
- [ ] PUT: Update user profile (first_name, last_name)
- [ ] Require authentication
- [ ] Return user data without sensitive fields
- [ ] Test profile endpoints

## 5.2 api/users/settings.php
- [ ] Create settings.php file
- [ ] GET: Return user settings
- [ ] PUT: Update user settings
- [ ] Support key-value setting pairs
- [ ] Require authentication
- [ ] Test settings endpoints

## 5.3 api/users/devices.php
- [ ] Create devices.php file
- [ ] GET: List user's registered devices
- [ ] POST: Register new device
- [ ] DELETE: Remove device
- [ ] Check device limit based on plan
- [ ] Require authentication
- [ ] Test device endpoints

## 5.4 api/users/change-password.php
- [ ] Create change-password.php file
- [ ] Accept POST with current_password, new_password
- [ ] Verify current password
- [ ] Hash and update new password
- [ ] Invalidate all sessions except current
- [ ] Return success
- [ ] Test change password endpoint

## 5.5 api/users/two-factor.php
- [ ] Create two-factor.php file
- [ ] POST: Enable 2FA - generate secret, return QR code
- [ ] PUT: Verify and activate 2FA
- [ ] DELETE: Disable 2FA
- [ ] Require authentication
- [ ] Test 2FA endpoints

---

# PHASE 6: VPN API

## 6.1 api/vpn/servers.php
- [ ] Create servers.php file
- [ ] GET: List all available servers
- [ ] GET /{id}: Get single server details
- [ ] Include server status, load, latency
- [ ] Filter VIP servers for non-VIP users
- [ ] Require authentication
- [ ] Test servers endpoints

## 6.2 api/vpn/connect.php
- [ ] Create connect.php file
- [ ] POST: Request VPN connection
- [ ] Validate user has active subscription
- [ ] Check user device limit
- [ ] For VIP user (seige235@yahoo.com), use dedicated server
- [ ] Call server API to add WireGuard peer
- [ ] Generate client configuration
- [ ] Record connection in database
- [ ] Return WireGuard config
- [ ] Test connect endpoint

## 6.3 api/vpn/disconnect.php
- [ ] Create disconnect.php file
- [ ] POST: Disconnect from VPN
- [ ] Call server API to remove WireGuard peer
- [ ] Update connection record
- [ ] Return success
- [ ] Test disconnect endpoint

## 6.4 api/vpn/status.php
- [ ] Create status.php file
- [ ] GET: Get current connection status
- [ ] Return connected server, duration, bandwidth
- [ ] Require authentication
- [ ] Test status endpoint

## 6.5 api/vpn/config.php
- [ ] Create config.php file
- [ ] GET: Download WireGuard configuration file
- [ ] GET /qr: Get QR code for mobile
- [ ] Require authentication
- [ ] Test config endpoint

---

# PHASE 7: CERTIFICATE API

## 7.1 api/certificates/generate.php
- [ ] Create generate.php file
- [ ] POST: Generate new certificate
- [ ] Accept certificate type (device, regional, mesh)
- [ ] Call VPN server API to generate certificate
- [ ] Encrypt private key before storing
- [ ] Store certificate in database
- [ ] Return certificate details
- [ ] Require authentication
- [ ] Test generate endpoint

## 7.2 api/certificates/list.php
- [ ] Create list.php file
- [ ] GET: List user's certificates
- [ ] Filter by type if specified
- [ ] Include expiration dates
- [ ] Require authentication
- [ ] Test list endpoint

## 7.3 api/certificates/download.php
- [ ] Create download.php file
- [ ] GET /{id}: Download certificate
- [ ] Support PEM and P12 formats
- [ ] Require authentication
- [ ] Verify certificate belongs to user
- [ ] Test download endpoint

## 7.4 api/certificates/revoke.php
- [ ] Create revoke.php file
- [ ] POST: Revoke certificate
- [ ] Update certificate status
- [ ] Add to revocation list
- [ ] Notify relevant servers
- [ ] Require authentication
- [ ] Test revoke endpoint

## 7.5 api/certificates/ca.php
- [ ] Create ca.php file
- [ ] GET: Get user's personal CA certificate
- [ ] POST: Generate personal CA (if not exists)
- [ ] Require authentication
- [ ] Test CA endpoint

---

# PHASE 8: DEVICE & CAMERA API

## 8.1 api/devices/list.php
- [ ] Create list.php file
- [ ] GET: List all discovered devices
- [ ] Support filtering by type
- [ ] Support pagination
- [ ] Require authentication
- [ ] Test list endpoint

## 8.2 api/devices/sync.php
- [ ] Create sync.php file
- [ ] POST: Sync devices from scanner
- [ ] Accept array of device objects
- [ ] Update existing devices
- [ ] Add new devices
- [ ] Mark missing devices as offline
- [ ] Trigger scanner_sync workflow
- [ ] Return sync summary
- [ ] Test sync endpoint

## 8.3 api/devices/delete.php
- [ ] Create delete.php file
- [ ] DELETE /{id}: Remove device
- [ ] Remove associated port forwarding rules
- [ ] Require authentication
- [ ] Test delete endpoint

## 8.4 api/devices/update.php
- [ ] Create update.php file
- [ ] PUT /{id}: Update device details
- [ ] Allow renaming device
- [ ] Require authentication
- [ ] Test update endpoint

## 8.5 api/cameras/list.php
- [ ] Create list.php file
- [ ] GET: List all cameras
- [ ] Include online status
- [ ] Include thumbnail URL
- [ ] Require authentication
- [ ] Test list endpoint

## 8.6 api/cameras/stream.php
- [ ] Create stream.php file
- [ ] GET /{id}: Get stream URL
- [ ] Return RTSP or HLS URL
- [ ] Generate authenticated stream URL if needed
- [ ] Require authentication
- [ ] Test stream endpoint

## 8.7 api/cameras/control.php
- [ ] Create control.php file
- [ ] POST /{id}/floodlight: Toggle floodlight on/off
- [ ] POST /{id}/motion: Toggle motion detection
- [ ] POST /{id}/audio: Toggle two-way audio
- [ ] POST /{id}/nightvision: Set night vision mode
- [ ] POST /{id}/ptz: PTZ control (pan, tilt, zoom)
- [ ] Communicate with camera via appropriate protocol
- [ ] Require authentication
- [ ] Test control endpoints

## 8.8 api/cameras/snapshot.php
- [ ] Create snapshot.php file
- [ ] GET /{id}: Capture snapshot
- [ ] Return image data or URL
- [ ] Require authentication
- [ ] Test snapshot endpoint

## 8.9 api/cameras/events.php
- [ ] Create events.php file
- [ ] GET /{id}: List camera events
- [ ] Support filtering by event type
- [ ] Support date range filtering
- [ ] Support pagination
- [ ] Require authentication
- [ ] Test events endpoint

## 8.10 api/cameras/settings.php
- [ ] Create settings.php file
- [ ] GET /{id}: Get camera settings
- [ ] PUT /{id}: Update camera settings
- [ ] Support resolution, fps, recording mode
- [ ] Require authentication
- [ ] Test settings endpoints

## 8.11 api/cameras/recordings.php
- [ ] Create recordings.php file
- [ ] GET /{id}: List recordings
- [ ] GET /{id}/{recording_id}: Download recording
- [ ] DELETE /{id}/{recording_id}: Delete recording
- [ ] Require authentication
- [ ] Test recordings endpoints

---

# PHASE 9: PORT FORWARDING API

## 9.1 api/port-forwarding/rules.php
- [ ] Create rules.php file
- [ ] GET: List all port forwarding rules
- [ ] POST: Create new rule
- [ ] PUT /{id}: Update rule
- [ ] DELETE /{id}: Delete rule
- [ ] Validate port ranges
- [ ] Prevent duplicate ports
- [ ] Require authentication
- [ ] Test rules endpoints

## 9.2 api/port-forwarding/toggle.php
- [ ] Create toggle.php file
- [ ] POST /{id}: Enable/disable rule
- [ ] Update firewall on VPN server
- [ ] Require authentication
- [ ] Test toggle endpoint

---

# PHASE 10: MESH NETWORK API

## 10.1 api/mesh/networks.php
- [ ] Create networks.php file
- [ ] GET: List user's mesh networks
- [ ] POST: Create new mesh network
- [ ] PUT /{id}: Update network details
- [ ] DELETE /{id}: Delete network
- [ ] Check mesh user limit based on plan
- [ ] Require authentication
- [ ] Test networks endpoints

## 10.2 api/mesh/invite.php
- [ ] Create invite.php file
- [ ] POST: Send mesh invitation
- [ ] Generate invitation token
- [ ] Send invitation email
- [ ] Return invitation link/QR code
- [ ] Require authentication
- [ ] Test invite endpoint

## 10.3 api/mesh/join.php
- [ ] Create join.php file
- [ ] POST: Accept mesh invitation
- [ ] Validate invitation token
- [ ] Add user to mesh network
- [ ] Generate mesh certificates
- [ ] Return success
- [ ] Test join endpoint

## 10.4 api/mesh/members.php
- [ ] Create members.php file
- [ ] GET /{network_id}: List network members
- [ ] Include online status
- [ ] Include device info
- [ ] Require authentication
- [ ] Test members endpoint

## 10.5 api/mesh/remove.php
- [ ] Create remove.php file
- [ ] DELETE /{network_id}/{user_id}: Remove member
- [ ] Revoke mesh certificates
- [ ] Require authentication
- [ ] Require network owner permission
- [ ] Test remove endpoint

---

# PHASE 11: BILLING API

## 11.1 api/billing/subscription.php
- [ ] Create subscription.php file
- [ ] GET: Get current subscription
- [ ] POST: Create/upgrade subscription
- [ ] PUT: Change plan
- [ ] DELETE: Cancel subscription
- [ ] Handle plan change proration
- [ ] Require authentication
- [ ] Test subscription endpoints

## 11.2 api/billing/invoices.php
- [ ] Create invoices.php file
- [ ] GET: List invoices
- [ ] GET /{id}: Get invoice details
- [ ] GET /{id}/pdf: Download invoice PDF
- [ ] Require authentication
- [ ] Test invoices endpoints

## 11.3 api/billing/payments.php
- [ ] Create payments.php file
- [ ] GET: List payment history
- [ ] POST: Process payment
- [ ] Integrate with PayPal API
- [ ] Require authentication
- [ ] Test payments endpoints

## 11.4 api/billing/paypal-webhook.php
- [ ] Create paypal-webhook.php file
- [ ] Handle payment.completed event
- [ ] Handle payment.failed event
- [ ] Handle subscription.cancelled event
- [ ] Verify webhook signature
- [ ] Update subscription status
- [ ] Trigger appropriate workflows
- [ ] Test webhook endpoint

---

# PHASE 12: ADMIN API

## 12.1 api/admin/users.php
- [ ] Create users.php file
- [ ] GET: List all users (paginated, searchable)
- [ ] GET /{id}: Get user details
- [ ] PUT /{id}: Update user
- [ ] DELETE /{id}: Delete user
- [ ] POST /{id}/suspend: Suspend user
- [ ] POST /{id}/activate: Activate user
- [ ] POST /{id}/vip: Toggle VIP status
- [ ] Require admin authentication
- [ ] Test admin users endpoints

## 12.2 api/admin/servers.php
- [ ] Create servers.php file
- [ ] GET: List all servers with status
- [ ] GET /{id}: Get server details
- [ ] PUT /{id}: Update server config
- [ ] POST /{id}/restart: Restart server services
- [ ] POST /{id}/health-check: Manual health check
- [ ] Require admin authentication
- [ ] Test admin servers endpoints

## 12.3 api/admin/stats.php
- [ ] Create stats.php file
- [ ] GET: Get dashboard statistics
- [ ] Total users, active users, revenue
- [ ] Server load, connections
- [ ] Recent activity
- [ ] Require admin authentication
- [ ] Test stats endpoint

## 12.4 api/admin/logs.php
- [ ] Create logs.php file
- [ ] GET: List system logs
- [ ] Filter by type, date, user
- [ ] Support pagination
- [ ] Require admin authentication
- [ ] Test logs endpoint

## 12.5 api/admin/themes.php
- [ ] Create themes.php file
- [ ] GET: List themes
- [ ] GET /{id}: Get theme settings
- [ ] PUT /{id}: Update theme settings
- [ ] POST /{id}/activate: Activate theme
- [ ] Require admin authentication
- [ ] Test themes endpoints

## 12.6 api/admin/automation.php
- [ ] Create automation.php file
- [ ] GET: List workflows
- [ ] GET /{id}: Get workflow details
- [ ] PUT /{id}: Update workflow
- [ ] POST /{id}/trigger: Manual trigger
- [ ] GET /tasks: List scheduled tasks
- [ ] Require admin authentication
- [ ] Test automation endpoints

---

# PHASE 13: SCANNER API

## 13.1 api/scanner/auth.php
- [ ] Create auth.php file
- [ ] POST: Validate scanner token
- [ ] Return user info if valid
- [ ] Test auth endpoint

## 13.2 api/scanner/sync.php
- [ ] Create sync.php file
- [ ] POST: Receive discovered devices
- [ ] Authenticate via scanner token
- [ ] Process device list
- [ ] Return sync results
- [ ] Test sync endpoint

## 13.3 api/scanner/download.php
- [ ] Create download.php file
- [ ] GET: Download scanner package
- [ ] Generate personalized scanner with embedded token
- [ ] Create ZIP file with scanner files
- [ ] Require authentication
- [ ] Test download endpoint

---

# PHASE 14: AUTOMATION API

## 14.1 api/automation/engine.php
- [ ] Create engine.php file
- [ ] Create AutomationEngine class
- [ ] Add method: trigger($workflowName, $data)
- [ ] Add method: processStep($step, $context)
- [ ] Add method: executeAction($action, $params)
- [ ] Support action types: email, api_call, db_update, wait, condition
- [ ] Add error handling and retry logic

## 14.2 api/automation/workflows.php
- [ ] Create workflows.php file
- [ ] GET: List workflows (admin)
- [ ] POST /{id}/trigger: Trigger workflow (admin)
- [ ] Require admin authentication

## 14.3 api/automation/cron.php
- [ ] Create cron.php file
- [ ] Process scheduled tasks
- [ ] Execute due automation steps
- [ ] Run health checks
- [ ] Clean up expired sessions
- [ ] Should be called every 5 minutes via cron

---

# PHASE 15: CLIENT DASHBOARD

## 15.1 Landing Page
- [ ] Create index.html in root
- [ ] Design hero section
- [ ] Add features section
- [ ] Add pricing section
- [ ] Add comparison table
- [ ] Add testimonials section
- [ ] Add footer
- [ ] Load theme from database
- [ ] Make fully responsive
- [ ] Test landing page

## 15.2 Dashboard Foundation
- [ ] Create dashboard/index.html
- [ ] Setup React application structure
- [ ] Create api/theme.php to serve theme CSS variables
- [ ] Load theme dynamically from database
- [ ] Create Header component
- [ ] Create Sidebar component
- [ ] Create MainContent component
- [ ] Setup React Router
- [ ] Setup Zustand state management
- [ ] Create API service layer
- [ ] Create authentication context
- [ ] Test dashboard loads

## 15.3 Authentication Pages
- [ ] Create Login page component
- [ ] Create Registration page component
- [ ] Create Forgot Password page component
- [ ] Create Reset Password page component
- [ ] Add form validation
- [ ] Add error handling
- [ ] Test authentication flow

## 15.4 Dashboard Home Page
- [ ] Create DashboardHome component
- [ ] Show connection status widget
- [ ] Show bandwidth usage chart
- [ ] Show devices summary
- [ ] Show cameras summary
- [ ] Show subscription info
- [ ] Test dashboard home

## 15.5 VPN Connect Page
- [ ] Create VPNConnect component
- [ ] Create ServerList component
- [ ] Create ServerCard component
- [ ] Show server locations on map or list
- [ ] Show server load indicators
- [ ] Show latency display
- [ ] Create QuickConnect button
- [ ] Create RegionalIdentitySelector
- [ ] Add kill switch toggle
- [ ] Test VPN connect page

## 15.6 Devices Page
- [ ] Create Devices component
- [ ] Create DeviceList component
- [ ] Create DeviceCard component
- [ ] Show all registered devices
- [ ] Show device type icons
- [ ] Show last connected time
- [ ] Add "Add Device" button
- [ ] Add device QR code modal
- [ ] Add "Remove Device" function
- [ ] Show device limit indicator
- [ ] Test devices page

## 15.7 Camera Dashboard
- [ ] Create CameraDashboard component
- [ ] Create CameraGrid component
- [ ] Create CameraCard component (with thumbnail)
- [ ] Show live preview thumbnails
- [ ] Show online/offline status
- [ ] Add "Run Scanner" button
- [ ] Test camera grid view

## 15.8 Single Camera View
- [ ] Create CameraView component
- [ ] Create VideoPlayer component (HLS.js)
- [ ] Show live video feed
- [ ] Create CameraControls component
- [ ] Add Floodlight toggle
- [ ] Add Motion Detection toggle
- [ ] Add Two-Way Audio button
- [ ] Add Night Vision toggle
- [ ] Add PTZ controls (if supported)
- [ ] Create SnapshotButton component
- [ ] Create RecordButton component
- [ ] Add Fullscreen button
- [ ] Create CameraEvents component
- [ ] Show recent motion events
- [ ] Add event clips playback
- [ ] Create CameraSettings panel
- [ ] Test camera view page

## 15.9 Port Forwarding Page
- [ ] Create PortForwarding component
- [ ] Create RulesList component
- [ ] Create RuleCard component
- [ ] Create AddRuleForm component
- [ ] Show all forwarding rules
- [ ] Add enable/disable toggle
- [ ] Add delete function
- [ ] Validate port inputs
- [ ] Test port forwarding page

## 15.10 Mesh Network Page
- [ ] Create MeshNetwork component
- [ ] Create NetworkList component
- [ ] Create MembersList component
- [ ] Create InviteForm component
- [ ] Show connected members
- [ ] Show member devices
- [ ] Add invite by email function
- [ ] Generate QR code for mobile
- [ ] Add remove member function
- [ ] Test mesh network page

## 15.11 Regional Identities Page
- [ ] Create Identities component
- [ ] Create IdentityList component
- [ ] Create IdentityCard component
- [ ] Show all regional identities
- [ ] Show assigned server per region
- [ ] Show last used date
- [ ] Add create identity button
- [ ] Test identities page

## 15.12 Certificates Page
- [ ] Create Certificates component
- [ ] Create CertificateList component
- [ ] Create CertificateCard component
- [ ] Show personal CA certificate
- [ ] Show device certificates
- [ ] Show regional certificates
- [ ] Show mesh certificates
- [ ] Add download buttons
- [ ] Show expiration dates
- [ ] Add renewal function
- [ ] Add revoke function
- [ ] Test certificates page

## 15.13 Settings Page
- [ ] Create Settings component
- [ ] Create ProfileSection component
- [ ] Create SecuritySection component
- [ ] Create NotificationsSection component
- [ ] Create BillingSection component
- [ ] Add change password function
- [ ] Add enable 2FA function
- [ ] Show subscription details
- [ ] Add cancel subscription button
- [ ] Test settings page

## 15.14 Dashboard Styling (DATABASE-DRIVEN)
- [ ] Create CSS that uses CSS variables
- [ ] Load CSS variables from theme API
- [ ] Apply colors from database
- [ ] Apply typography from database
- [ ] Apply button styles from database
- [ ] Apply layout settings from database
- [ ] Verify NO hardcoded colors
- [ ] Verify NO hardcoded fonts
- [ ] Test theme changes reflect in UI

---

# PHASE 16: MANAGEMENT DASHBOARD

## 16.1 Admin Foundation
- [ ] Create admin/index.html
- [ ] Setup React application structure
- [ ] Load admin theme from database
- [ ] Create AdminHeader component
- [ ] Create AdminSidebar component
- [ ] Setup React Router for admin
- [ ] Setup admin authentication
- [ ] Test admin dashboard loads

## 16.2 Admin Login
- [ ] Create AdminLogin component
- [ ] Validate admin credentials
- [ ] Redirect non-admins
- [ ] Test admin login

## 16.3 Admin Dashboard Home
- [ ] Create AdminDashboard component
- [ ] Create StatsCards component (total users, revenue, etc.)
- [ ] Create ServerStatusWidget component
- [ ] Create RecentActivityFeed component
- [ ] Create ConnectionsChart component
- [ ] Test admin dashboard home

## 16.4 User Management
- [ ] Create UserManagement component
- [ ] Create UserTable component (sortable, searchable)
- [ ] Create UserFilters component
- [ ] Create UserDetail component
- [ ] Create EditUserForm component
- [ ] Add view user's devices
- [ ] Add view user's cameras
- [ ] Add impersonate user function
- [ ] Add suspend/activate function
- [ ] Add VIP toggle function
- [ ] Test user management

## 16.5 Server Management
- [ ] Create ServerManagement component
- [ ] Create ServerList component
- [ ] Create ServerCard component
- [ ] Create ServerDetail component
- [ ] Show real-time status
- [ ] Show connection count
- [ ] Show bandwidth usage
- [ ] Add restart server button
- [ ] Add edit configuration
- [ ] Test server management

## 16.6 Certificate Management
- [ ] Create CertManagement component
- [ ] Create CAOverview component
- [ ] Create CertificatesList component
- [ ] Add revocation function
- [ ] Test certificate management

## 16.7 Subscription Management
- [ ] Create SubscriptionManagement component
- [ ] Create SubscriptionsList component
- [ ] Create SubscriptionDetail component
- [ ] Show subscription stats
- [ ] Add manual upgrade/downgrade
- [ ] Test subscription management

## 16.8 Automation Management
- [ ] Create AutomationManagement component
- [ ] Create WorkflowsList component
- [ ] Create WorkflowDetail component
- [ ] Create TasksList component
- [ ] Add manual trigger button
- [ ] Add edit workflow function
- [ ] Show execution history
- [ ] Test automation management

## 16.9 Theme Management (CRITICAL)
- [ ] Create ThemeManagement component
- [ ] Create ColorPicker component
- [ ] Create FontSelector component
- [ ] Create ButtonStyleEditor component
- [ ] Create LayoutEditor component
- [ ] Create LivePreview component
- [ ] Load current theme from database
- [ ] Save changes to database
- [ ] Show live preview of changes
- [ ] Add reset to default button
- [ ] Test theme changes propagate to all dashboards

## 16.10 CMS Management
- [ ] Create CMSManagement component
- [ ] Create PagesList component
- [ ] Create PageEditor component
- [ ] Add create page function
- [ ] Add edit page function
- [ ] Add delete page function
- [ ] Test CMS management

## 16.11 System Logs
- [ ] Create SystemLogs component
- [ ] Create LogViewer component
- [ ] Create LogFilters component
- [ ] Filter by type, date, user
- [ ] Add export function
- [ ] Test system logs

## 16.12 Admin Settings
- [ ] Create AdminSettings component
- [ ] Create GlobalSettings component
- [ ] Create EmailSettings component
- [ ] Create PayPalSettings component
- [ ] Test admin settings

---

# PHASE 17: BUSINESS DASHBOARD

## 17.1 Business Foundation
- [ ] Create business/index.html
- [ ] Setup React application structure
- [ ] Load theme from database
- [ ] Create BusinessHeader component
- [ ] Create BusinessSidebar component
- [ ] Setup React Router
- [ ] Test business dashboard loads

## 17.2 FileMaker-Style Database Creator

### 17.2.1 Database Designer
- [ ] Create DatabaseDesigner component
- [ ] Create TableList component
- [ ] Create AddTableForm component
- [ ] Add create table function
- [ ] Add delete table function
- [ ] Test table management

### 17.2.2 Field Designer
- [ ] Create FieldDesigner component
- [ ] Create FieldList component
- [ ] Create AddFieldForm component
- [ ] Support field types: Text, Number, Date, Email, Phone, URL, Dropdown, Checkbox, File, Image
- [ ] Add field validation options
- [ ] Add required field option
- [ ] Add unique field option
- [ ] Add default value option
- [ ] Test field management

### 17.2.3 Relationship Designer
- [ ] Create RelationshipDesigner component
- [ ] Create RelationshipList component
- [ ] Create AddRelationshipForm component
- [ ] Support one-to-many relationships
- [ ] Support many-to-many relationships
- [ ] Test relationship management

### 17.2.4 Form Generator
- [ ] Create FormGenerator component
- [ ] Create FormPreview component
- [ ] Auto-generate form from table schema
- [ ] Customize form layout
- [ ] Add/remove fields from form
- [ ] Test form generator

### 17.2.5 Record Viewer
- [ ] Create RecordViewer component
- [ ] Create RecordTable component
- [ ] Create RecordForm component
- [ ] Add create record function
- [ ] Add edit record function
- [ ] Add delete record function
- [ ] Add search function
- [ ] Add pagination
- [ ] Test record viewer

### 17.2.6 Sample Data Generator
- [ ] Create SampleDataGenerator component
- [ ] Create data generation rules per field type
- [ ] Generate realistic names, emails, phones
- [ ] Generate realistic dates
- [ ] Specify number of records to generate
- [ ] Test sample data generation

### 17.2.7 Export Functions
- [ ] Add export to CSV
- [ ] Add export to JSON
- [ ] Add export to SQL
- [ ] Test export functions

## 17.3 GrapesJS Page Builder

### 17.3.1 Page Builder Setup
- [ ] Create PageBuilder component
- [ ] Integrate GrapesJS library
- [ ] Configure GrapesJS for our use case
- [ ] Add custom CSS loading from database

### 17.3.2 Custom Blocks
- [ ] Create Hero block
- [ ] Create Features block
- [ ] Create Pricing Table block
- [ ] Create Testimonials block
- [ ] Create CTA block
- [ ] Create Footer block
- [ ] Create VPN-specific blocks
- [ ] Test custom blocks

### 17.3.3 Page Management
- [ ] Create page save function
- [ ] Create page load function
- [ ] Create page publish function
- [ ] Create page preview function
- [ ] Add version history
- [ ] Test page management

### 17.3.4 Template System
- [ ] Create template save function
- [ ] Create template load function
- [ ] Create template library UI
- [ ] Test template system

## 17.4 Accounting System

### 17.4.1 Revenue Dashboard
- [ ] Create RevenueDashboard component
- [ ] Create RevenueChart component
- [ ] Show MRR (Monthly Recurring Revenue)
- [ ] Show ARR (Annual Recurring Revenue)
- [ ] Show revenue growth
- [ ] Show revenue by plan
- [ ] Test revenue dashboard

### 17.4.2 Invoice Management
- [ ] Create InvoiceManagement component
- [ ] Create InvoiceList component
- [ ] Create InvoiceDetail component
- [ ] Create InvoicePreview component
- [ ] Add send invoice function
- [ ] Add download PDF function
- [ ] Test invoice management

### 17.4.3 Expense Tracking
- [ ] Create ExpenseTracking component
- [ ] Create ExpenseList component
- [ ] Create AddExpenseForm component
- [ ] Add expense categories (servers, software, marketing)
- [ ] Add receipt upload
- [ ] Test expense tracking

### 17.4.4 PayPal Transaction Viewer
- [ ] Create TransactionViewer component
- [ ] Create TransactionList component
- [ ] Create TransactionDetail component
- [ ] Show all PayPal transactions
- [ ] Filter by date, status
- [ ] Test transaction viewer

### 17.4.5 Financial Reports
- [ ] Create FinancialReports component
- [ ] Create Profit/Loss report
- [ ] Create Revenue by month report
- [ ] Create Subscription metrics report
- [ ] Add export to CSV
- [ ] Add export to PDF
- [ ] Test financial reports

### 17.4.6 Subscription Metrics
- [ ] Create SubscriptionMetrics component
- [ ] Show total subscribers by plan
- [ ] Show churn rate
- [ ] Show LTV (Lifetime Value)
- [ ] Show conversion rate
- [ ] Test subscription metrics

## 17.5 Marketing Tools

### 17.5.1 Campaign Management
- [ ] Create CampaignManagement component
- [ ] Create CampaignList component
- [ ] Create CreateCampaignForm component
- [ ] Add email campaign type
- [ ] Add landing page campaign type
- [ ] Test campaign management

### 17.5.2 Email Template Editor
- [ ] Create EmailTemplateEditor component
- [ ] Create visual email editor
- [ ] Add variable insertion
- [ ] Add preview function
- [ ] Add test send function
- [ ] Test email template editor

### 17.5.3 Landing Page Manager
- [ ] Create LandingPageManager component
- [ ] Create LandingPageList component
- [ ] Add duplicate page function
- [ ] Add analytics per page
- [ ] Test landing page manager

---

# PHASE 18: AUTOMATION ENGINE IMPLEMENTATION

## 18.1 Workflow: New User Signup
- [ ] Implement welcome email send
- [ ] Implement certificate generation trigger
- [ ] Implement default identities creation
- [ ] Implement scanner download link email
- [ ] Implement 24-hour follow-up scheduling
- [ ] Test new user signup workflow

## 18.2 Workflow: Scanner Sync
- [ ] Implement device processing
- [ ] Implement camera detection
- [ ] Implement RTSP stream discovery
- [ ] Implement notification send
- [ ] Implement port forwarding suggestions
- [ ] Test scanner sync workflow

## 18.3 Workflow: Payment Success
- [ ] Implement subscription update
- [ ] Implement invoice generation
- [ ] Implement receipt email send
- [ ] Implement plan upgrade handling
- [ ] Test payment success workflow

## 18.4 Workflow: Payment Failed
- [ ] Implement grace period setting
- [ ] Implement Day 0 reminder email
- [ ] Implement Day 3 urgent notice
- [ ] Implement Day 7 final warning
- [ ] Implement Day 8 service suspension
- [ ] Test payment failed workflow

## 18.5 Workflow: Certificate Generation
- [ ] Implement server API call
- [ ] Implement certificate storage
- [ ] Implement notification email
- [ ] Test certificate generation workflow

## 18.6 Workflow: VPN Connection
- [ ] Implement connection recording
- [ ] Implement bandwidth tracking start
- [ ] Implement regional identity application
- [ ] Test VPN connection workflow

## 18.7 Workflow: Server Health Check
- [ ] Implement health check API calls
- [ ] Implement status recording
- [ ] Implement alert on failure
- [ ] Implement user redirection on failure
- [ ] Test health check workflow

## 18.8 Workflow: Subscription Expiring
- [ ] Implement 7-day reminder
- [ ] Implement 3-day reminder
- [ ] Implement 1-day reminder
- [ ] Implement expiration handling
- [ ] Test subscription expiring workflow

## 18.9 Cron Job Setup
- [ ] Create cron job script
- [ ] Schedule every 5 minutes
- [ ] Test cron execution

---

# PHASE 19: NETWORK SCANNER ENHANCEMENT

## 19.1 Scanner Improvements
- [ ] Update MAC vendor database
- [ ] Add more device type detection
- [ ] Improve ARP scanning reliability
- [ ] Add scanner version check
- [ ] Test improved scanner

## 19.2 Camera Stream Discovery
- [ ] Add Geeni/Tuya stream URL patterns
- [ ] Add Hikvision stream URL patterns
- [ ] Add Dahua stream URL patterns
- [ ] Add Wyze stream URL patterns
- [ ] Add Amcrest stream URL patterns
- [ ] Add Reolink stream URL patterns
- [ ] Test stream discovery

## 19.3 Geeni/Tuya Local Control
- [ ] Integrate tinytuya library
- [ ] Add device ID extraction
- [ ] Add local key extraction
- [ ] Implement floodlight control
- [ ] Implement motion detection control
- [ ] Test Geeni local control

## 19.4 Scanner Download System
- [ ] Create scanner packaging script
- [ ] Implement token embedding
- [ ] Create ZIP file generation
- [ ] Test scanner download

## 19.5 Scanner Web UI
- [ ] Update scanner HTML template
- [ ] Load theme from database
- [ ] Improve device selection UI
- [ ] Test scanner web UI

---

# PHASE 20: TESTING

## 20.1 Authentication Testing
- [ ] Test user registration
- [ ] Test user login
- [ ] Test logout
- [ ] Test token refresh
- [ ] Test email verification
- [ ] Test password reset
- [ ] Test 2FA

## 20.2 VPN Testing
- [ ] Test server listing
- [ ] Test VPN connection
- [ ] Test VPN disconnection
- [ ] Test config download
- [ ] Test VIP server routing

## 20.3 Certificate Testing
- [ ] Test CA generation
- [ ] Test device certificate generation
- [ ] Test regional certificate generation
- [ ] Test certificate download
- [ ] Test certificate revocation

## 20.4 Device Testing
- [ ] Test device listing
- [ ] Test scanner sync
- [ ] Test device deletion
- [ ] Test device update

## 20.5 Camera Testing
- [ ] Test camera listing
- [ ] Test camera stream
- [ ] Test floodlight control
- [ ] Test motion detection control
- [ ] Test snapshot capture
- [ ] Test camera events

## 20.6 Port Forwarding Testing
- [ ] Test rule creation
- [ ] Test rule update
- [ ] Test rule deletion
- [ ] Test rule toggle

## 20.7 Mesh Network Testing
- [ ] Test network creation
- [ ] Test invitation send
- [ ] Test join network
- [ ] Test member removal

## 20.8 Billing Testing
- [ ] Test subscription creation
- [ ] Test plan change
- [ ] Test invoice generation
- [ ] Test PayPal webhook
- [ ] Test payment recording

## 20.9 Admin Testing
- [ ] Test admin login
- [ ] Test user management
- [ ] Test server management
- [ ] Test theme changes
- [ ] Test automation triggers

## 20.10 Business Dashboard Testing
- [ ] Test database creator
- [ ] Test page builder
- [ ] Test accounting reports
- [ ] Test marketing tools

## 20.11 Automation Testing
- [ ] Test all workflows trigger correctly
- [ ] Test scheduled tasks execute
- [ ] Test email sending
- [ ] Test error handling

## 20.12 Cross-Browser Testing
- [ ] Test in Chrome
- [ ] Test in Firefox
- [ ] Test in Safari
- [ ] Test in Edge

## 20.13 Mobile Testing
- [ ] Test responsive design on mobile
- [ ] Test touch interactions
- [ ] Test camera view on mobile

---

# PHASE 21: SECURITY AUDIT

## 21.1 Authentication Security
- [ ] Verify password hashing (bcrypt)
- [ ] Verify JWT implementation
- [ ] Test rate limiting on auth endpoints
- [ ] Test session expiration
- [ ] Test 2FA implementation

## 21.2 API Security
- [ ] Verify all endpoints require authentication
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention
- [ ] Test CSRF protection
- [ ] Verify CORS configuration

## 21.3 Data Security
- [ ] Verify database encryption at rest
- [ ] Verify private key encryption
- [ ] Verify SSL/TLS for all connections
- [ ] Test sensitive data exposure

## 21.4 Infrastructure Security
- [ ] Verify server hardening
- [ ] Check firewall rules
- [ ] Check fail2ban configuration
- [ ] Verify DDoS protection

---

# PHASE 22: DOCUMENTATION

## 22.1 User Documentation
- [ ] Create Getting Started guide
- [ ] Create VPN Connection guide
- [ ] Create Camera Setup guide
- [ ] Create Port Forwarding guide
- [ ] Create Mesh Network guide
- [ ] Create FAQ

## 22.2 Admin Documentation
- [ ] Create Admin Guide
- [ ] Create User Management guide
- [ ] Create Server Management guide
- [ ] Create Theme Customization guide

## 22.3 API Documentation
- [ ] Document all API endpoints
- [ ] Create authentication guide
- [ ] Create error codes reference
- [ ] Create webhook documentation

## 22.4 Developer Documentation
- [ ] Create setup guide
- [ ] Create database schema reference
- [ ] Create deployment guide

---

# PHASE 23: DEPLOYMENT

## 23.1 Pre-Deployment
- [ ] Create production .htaccess
- [ ] Update all URLs to production
- [ ] Create database backup script
- [ ] Verify all credentials

## 23.2 FTP Upload
- [ ] Upload /api folder
- [ ] Upload /dashboard folder
- [ ] Upload /admin folder
- [ ] Upload /business folder
- [ ] Upload /databases folder (empty, for structure)
- [ ] Upload /downloads folder
- [ ] Upload index.html
- [ ] Upload .htaccess

## 23.3 Server Configuration
- [ ] Verify PHP version
- [ ] Verify SQLite extension
- [ ] Set file permissions
- [ ] Configure error logging

## 23.4 Database Initialization
- [ ] Run setup-databases.php on production
- [ ] Verify all databases created
- [ ] Verify default data inserted
- [ ] Create production admin user

## 23.5 SSL Configuration
- [ ] Verify SSL certificate
- [ ] Force HTTPS redirect
- [ ] Test SSL configuration

## 23.6 Testing on Production
- [ ] Test landing page
- [ ] Test registration
- [ ] Test login
- [ ] Test VPN connection
- [ ] Test camera dashboard
- [ ] Test admin dashboard
- [ ] Test business dashboard

## 23.7 PayPal Webhook
- [ ] Update webhook URL to production
- [ ] Verify webhook receives events
- [ ] Test payment processing

---

# PHASE 24: LAUNCH

## 24.1 Final Checks
- [ ] Verify all features working
- [ ] Verify all databases populated
- [ ] Verify email sending works
- [ ] Verify PayPal integration works
- [ ] Verify VPN servers accessible

## 24.2 Monitoring Setup
- [ ] Setup server health monitoring
- [ ] Setup error alerting
- [ ] Setup uptime monitoring
- [ ] Create backup schedule

## 24.3 Launch
- [ ] Enable user registration
- [ ] Announce launch
- [ ] Monitor for issues
- [ ] Respond to support requests

---

# PROGRESS SUMMARY

**Total Items:** 497 (added 10 audit items)
**Completed:** ~150 (needs full audit)
**Remaining:** ~347
**Progress:** ~30%

**Last Work Session:** January 13, 2026 - 10:30 PM CST
**Last Item Completed:** Fixed login.php frontend bug (data.data.token)

---

# WORK COMPLETED (Sessions Jan 11-13, 2026)

## Phase 1 - COMPLETE
- [x] All directory structure
- [x] All reference docs
- [x] Server verification (all 4 servers)
- [x] WireGuard public keys documented
- [x] peer_api.py created

## Phase 2 - MOSTLY COMPLETE  
- [x] setup-databases.php created
- [x] All database schemas defined
- [x] themes.db with default theme
- [x] users.db schema
- [x] vip.db for VIP users
- [x] vpn_servers in database

## Phase 3 - COMPLETE
- [x] database.php - Database class
- [x] jwt.php - JWTManager class
- [x] settings.php - Settings class
- [x] constants.php
- [x] response.php - Response class
- [x] validator.php - Validator class
- [x] auth.php - Auth helper
- [x] vip.php - VIPManager, PlanLimits, ServerRules
- [x] encryption.php
- [x] logger.php

## Phase 4 - COMPLETE
- [x] register.php
- [x] login.php (fixed Jan 13)
- [x] logout.php
- [x] refresh.php
- [x] verify-email.php
- [x] forgot-password.php
- [x] reset-password.php

## Phase 6 - COMPLETE
- [x] servers.php
- [x] connect.php
- [x] config.php
- [x] status.php
- [x] provisioner.php

## Phase 11 - COMPLETE
- [x] subscription.php
- [x] checkout.php
- [x] complete.php
- [x] webhook.php
- [x] cron.php
- [x] billing-manager.php

## Phase 15 - HTML PAGES COMPLETE (needs style audit)
- [x] Landing page (index.html)
- [x] login.html
- [x] register.html
- [x] All 11 dashboard pages
- [x] All 13 admin pages
- [x] payment-success.html
- [x] payment-cancel.html
- [x] downloads/index.html

## STILL NEEDS WORK:
- [ ] Phase 5: User API (profile, settings, devices)
- [ ] Phase 7: Certificate API
- [ ] Phase 8: Device & Camera API (partial)
- [ ] Phase 9: Port Forwarding API
- [ ] Phase 10: Mesh Network API
- [ ] Phase 12: Admin API (partial)
- [ ] Phase 13: Scanner API
- [ ] Phase 14: Automation Engine
- [ ] Phase 16-17: Management & Business Dashboards
- [ ] Phase 18: Automation workflows
- [ ] Phase 19: Scanner enhancement
- [ ] **CRITICAL: Style audit - remove all hardcoded CSS**
- [ ] **CRITICAL: Placeholder audit - replace with real code**

---

# NOTES

- All styling MUST come from themes.db - NO HARDCODED COLORS/FONTS
- VIP server (144.126.133.253) is ONLY for seige235@yahoo.com
- Keys/certificates are generated ON THE VPN SERVERS, not web server
- System must be portable for future migration
- Update this checklist after completing each item

---
