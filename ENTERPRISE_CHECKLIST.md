

---

# ═══════════════════════════════════════════════════════════════════════════════
# PART 3: REVISED CHECKLIST - WEB-FIRST PWA APPROACH
# Updated: January 17, 2026
# Simpler than desktop app - works on ALL devices
# ═══════════════════════════════════════════════════════════════════════════════

## 🎯 ARCHITECTURE CHANGE

**OLD:** Desktop Electron app with local databases  
**NEW:** Web-based PWA with centralized database on company's VPN server

**Benefits:**
- Works on phones, tablets, laptops, desktops
- No app installation (except WireGuard)
- Single codebase for all platforms
- Simpler sync (no local databases to sync)
- Faster development (12 weeks → 6 weeks)

---

## 📋 PHASE A: SERVER INFRASTRUCTURE (Week 1)

### A.1 - Server Provisioning Automation
- [ ] Create Contabo API integration script
- [ ] Auto-provision VPS for new company
- [ ] Auto-configure hostname and DNS
- [ ] Auto-install Ubuntu 22.04 LTS
- [ ] Auto-configure firewall (UFW)
- [ ] Test provisioning end-to-end

### A.2 - WireGuard Server Setup
- [ ] Install WireGuard on server
- [ ] Generate server key pair
- [ ] Create wg0.conf template
- [ ] Configure IP forwarding
- [ ] Setup NAT rules (iptables)
- [ ] Create peer management scripts
- [ ] Test VPN connectivity

### A.3 - Web Server Setup
- [ ] Install Nginx
- [ ] Configure SSL with Let's Encrypt
- [ ] Setup auto-renewal for certificates
- [ ] Configure reverse proxy to Node.js
- [ ] Enable GZIP compression
- [ ] Configure security headers
- [ ] Test HTTPS access

### A.4 - Application Server Setup
- [ ] Install Node.js 20 LTS
- [ ] Install PM2 for process management
- [ ] Configure PM2 startup script
- [ ] Setup log rotation
- [ ] Create deployment script
- [ ] Test Node.js server runs

### A.5 - Database Initialization
- [ ] Install SQLite3
- [ ] Create /var/truevault/data/ directory
- [ ] Initialize company.db with schema
- [ ] Initialize hr.db with schema
- [ ] Initialize dataforge.db with schema
- [ ] Initialize audit.db with schema
- [ ] Set proper file permissions
- [ ] Test database operations

### A.6 - Backup System
- [ ] Create backup script
- [ ] Configure daily cron job
- [ ] Setup backup directory
- [ ] Implement backup encryption
- [ ] Setup backup retention (30 days)
- [ ] Test backup/restore

---

## 📋 PHASE B: AUTHENTICATION & ROLES (Week 1-2)

### B.1 - User Authentication
- [ ] Create /api/auth/login endpoint
- [ ] Implement password hashing (bcrypt)
- [ ] Generate JWT tokens
- [ ] Create /api/auth/logout endpoint
- [ ] Create /api/auth/refresh endpoint
- [ ] Implement secure HTTP-only cookies
- [ ] Create /api/auth/me endpoint
- [ ] Test login/logout flow

### B.2 - Password Management
- [ ] Create /api/auth/password/change endpoint
- [ ] Create /api/auth/password/reset endpoint
- [ ] Generate secure reset tokens
- [ ] Send reset email
- [ ] Implement password requirements
- [ ] Force password change on first login
- [ ] Test password flows

### B.3 - SSO - Google
- [ ] Register Google OAuth application
- [ ] Create /api/auth/sso/google endpoint
- [ ] Handle OAuth callback
- [ ] Map Google user to employee
- [ ] Test Google SSO

### B.4 - SSO - Microsoft
- [ ] Register Azure AD application
- [ ] Create /api/auth/sso/microsoft endpoint
- [ ] Handle OAuth callback
- [ ] Map Microsoft user to employee
- [ ] Test Microsoft SSO

### B.5 - Role System
- [ ] Seed 7 default roles in database
- [ ] Seed 50+ permissions
- [ ] Create role-permission mappings
- [ ] Build permission check middleware
- [ ] Create hasPermission() utility
- [ ] Test permission enforcement

### B.6 - Session Management
- [ ] Track sessions in database
- [ ] Record device info per session
- [ ] Implement session expiration
- [ ] Create session listing API
- [ ] Create session revocation API
- [ ] Test multi-device sessions

---

## 📋 PHASE C: PWA FOUNDATION (Week 2)

### C.1 - React App Setup
- [ ] Initialize Vite + React project
- [ ] Install Tailwind CSS
- [ ] Install shadcn/ui components
- [ ] Setup React Router
- [ ] Configure TanStack Query
- [ ] Setup Zustand for state
- [ ] Configure environment variables

### C.2 - PWA Configuration
- [ ] Create manifest.json
- [ ] Create service worker
- [ ] Configure Vite PWA plugin
- [ ] Setup offline page
- [ ] Configure caching strategy
- [ ] Test PWA installation

### C.3 - Responsive Layout
- [ ] Create Layout component
- [ ] Create Sidebar (desktop)
- [ ] Create BottomNav (mobile)
- [ ] Create Header component
- [ ] Implement responsive breakpoints
- [ ] Test on mobile/tablet/desktop

### C.4 - Authentication UI
- [ ] Create Login page
- [ ] Create SSO buttons
- [ ] Create Password Reset page
- [ ] Create First Login (change password) page
- [ ] Implement auth context
- [ ] Create ProtectedRoute component
- [ ] Test auth flow

### C.5 - Navigation System
- [ ] Create nav items by role
- [ ] Implement role-based menu
- [ ] Create navigation context
- [ ] Handle route guards
- [ ] Test navigation for each role

---

## 📋 PHASE D: EMPLOYEE PORTAL (/my) (Week 2-3)

### D.1 - Employee Dashboard
- [ ] Create /my route
- [ ] VPN status card
- [ ] Welcome card with user info
- [ ] Quick action buttons
- [ ] Stats cards (tasks, PTO, etc.)
- [ ] Announcements feed
- [ ] Recent activity
- [ ] Test responsive layout

### D.2 - My Profile
- [ ] View profile page
- [ ] Edit profile form
- [ ] Upload avatar
- [ ] Emergency contact section
- [ ] Save changes API
- [ ] Test profile updates

### D.3 - My Devices (VPN)
- [ ] List my devices
- [ ] Add new device button
- [ ] Generate QR code for device
- [ ] Download config file
- [ ] Remove device
- [ ] Show connection status
- [ ] Test device management

### D.4 - My Time-Off
- [ ] View time-off balances
- [ ] Request time-off form
- [ ] Calendar date picker
- [ ] View request history
- [ ] Cancel pending request
- [ ] Test time-off flow

### D.5 - My Documents
- [ ] List my documents
- [ ] Download document
- [ ] Upload document
- [ ] Document categories
- [ ] Test document management

### D.6 - Company Directory
- [ ] Search employees
- [ ] View public profiles
- [ ] Filter by department
- [ ] Contact info display
- [ ] Org chart view (optional)

### D.7 - Settings
- [ ] Change password
- [ ] View active sessions
- [ ] Revoke sessions
- [ ] Notification preferences
- [ ] Theme preference (dark/light)

---

## 📋 PHASE E: HR PORTAL (/hr) (Week 3-4)

### E.1 - HR Dashboard
- [ ] Create /hr route (HR roles only)
- [ ] Employee count widgets
- [ ] Pending approvals count
- [ ] Upcoming birthdays
- [ ] Recent hires list
- [ ] Headcount by department chart
- [ ] Test dashboard

### E.2 - Employee Directory (HR View)
- [ ] List all employees
- [ ] Search/filter
- [ ] Status badges
- [ ] Quick actions
- [ ] Export list
- [ ] Test permissions

### E.3 - Employee Profile (HR View)
- [ ] Full profile view
- [ ] Edit employee form
- [ ] Employment details
- [ ] Emergency contacts
- [ ] Documents tab
- [ ] Time-off tab
- [ ] Reviews tab
- [ ] Compensation (HR_ADMIN only)
- [ ] Test role-based fields

### E.4 - Compensation Management
- [ ] View salary (HR_ADMIN only)
- [ ] Edit salary form
- [ ] Salary change reason
- [ ] Salary history
- [ ] Test permission restriction

### E.5 - Department Management
- [ ] List departments
- [ ] Create department
- [ ] Edit department
- [ ] Assign manager
- [ ] Delete department
- [ ] Test CRUD

### E.6 - Position Management
- [ ] List positions
- [ ] Create position
- [ ] Edit position
- [ ] Pay range settings
- [ ] Delete position
- [ ] Test CRUD

### E.7 - Time-Off Management
- [ ] Time-off types setup
- [ ] Policy configuration
- [ ] View all requests
- [ ] Approve/deny requests
- [ ] Calendar view
- [ ] Balance adjustments
- [ ] Test approval flow

### E.8 - Document Vault
- [ ] Upload documents
- [ ] Categorize documents
- [ ] Set confidential flag
- [ ] Expiration tracking
- [ ] Delete documents
- [ ] Test access control

### E.9 - Hiring & Onboarding
- [ ] New hire form
- [ ] Generate employee record
- [ ] Send welcome email
- [ ] Generate VPN config
- [ ] Onboarding checklist
- [ ] Test full hire flow

### E.10 - Termination
- [ ] Termination form
- [ ] Revoke VPN access
- [ ] Deactivate account
- [ ] Archive documents
- [ ] Test termination flow

---

## 📋 PHASE F: MANAGER PORTAL (/manager) (Week 4)

### F.1 - Manager Dashboard
- [ ] Create /manager route
- [ ] My team list
- [ ] Pending approvals
- [ ] Team schedule
- [ ] Quick actions

### F.2 - Team Management
- [ ] View direct reports only
- [ ] Team member profiles
- [ ] Limited edit access
- [ ] Team org view

### F.3 - Team Time-Off
- [ ] View team requests
- [ ] Approve/deny
- [ ] Team calendar
- [ ] Coverage check

### F.4 - Team Reviews (Future)
- [ ] Create reviews for team
- [ ] View team reviews
- [ ] Rating entry

---

## 📋 PHASE G: ADMIN PORTAL (/admin) (Week 4-5)

### G.1 - Admin Dashboard
- [ ] Create /admin route
- [ ] System health status
- [ ] User statistics
- [ ] VPN connections
- [ ] Storage usage
- [ ] Recent admin actions

### G.2 - User Management
- [ ] List all users
- [ ] Search/filter users
- [ ] View user profile
- [ ] Edit user
- [ ] Change user role
- [ ] Deactivate user
- [ ] Reset password
- [ ] View sessions
- [ ] Revoke sessions
- [ ] Test all operations

### G.3 - Employee Invitation
- [ ] Invite form (email, role)
- [ ] Generate invite code
- [ ] Send invite email with QR
- [ ] Track pending invites
- [ ] Resend invite
- [ ] Cancel invite
- [ ] Test full flow

### G.4 - SSO Configuration
- [ ] Google setup form
- [ ] Microsoft setup form
- [ ] Test connection button
- [ ] Enable/disable SSO
- [ ] Enforce SSO option
- [ ] Test each provider

### G.5 - VPN Administration
- [ ] View all VPN peers
- [ ] Connection status
- [ ] Revoke peer access
- [ ] Bandwidth stats
- [ ] Connection logs

### G.6 - Audit Logs
- [ ] List all audit entries
- [ ] Filter by user
- [ ] Filter by action
- [ ] Filter by date
- [ ] Search logs
- [ ] Export (CSV/JSON)

### G.7 - Backup Management
- [ ] List backups
- [ ] Manual backup button
- [ ] Download backup
- [ ] Restore backup
- [ ] Backup settings

### G.8 - System Settings
- [ ] Company name/logo
- [ ] Timezone setting
- [ ] Date format
- [ ] Email settings
- [ ] Save settings

---

## 📋 PHASE H: DATAFORGE BUILDER (/dataforge) (Week 5)

### H.1 - DataForge Dashboard
- [ ] Create /dataforge route
- [ ] List user's tables
- [ ] Table cards with stats
- [ ] Create table button
- [ ] Template gallery button

### H.2 - Table Designer
- [ ] Create table form
- [ ] Add fields
- [ ] Field type selector
- [ ] Field options
- [ ] Drag-drop ordering
- [ ] Delete field
- [ ] Save table

### H.3 - Field Types
- [ ] Text field
- [ ] Textarea field
- [ ] Number field
- [ ] Currency field
- [ ] Date field
- [ ] Checkbox field
- [ ] Select field
- [ ] Multi-select field
- [ ] Email field
- [ ] Phone field
- [ ] URL field
- [ ] File upload field
- [ ] User picker field
- [ ] Relation/lookup field

### H.4 - Data Grid
- [ ] Display records
- [ ] Column sorting
- [ ] Column filtering
- [ ] Global search
- [ ] Pagination
- [ ] Inline editing
- [ ] Test performance

### H.5 - Record Management
- [ ] Create record modal
- [ ] Edit record modal
- [ ] Delete record
- [ ] Bulk delete
- [ ] Export to CSV

### H.6 - Templates
- [ ] Browse by category
- [ ] Preview template
- [ ] Use template
- [ ] Seed 20 templates (Phase 1)
- [ ] Add 30 more later

### H.7 - Table Permissions
- [ ] Assign users
- [ ] Set access level
- [ ] Test permissions

---

## 📋 PHASE I: OWNER PORTAL (/owner) (Week 5)

### I.1 - Owner Dashboard
- [ ] Create /owner route (owner only)
- [ ] Company overview
- [ ] User count
- [ ] Subscription status
- [ ] Quick actions

### I.2 - Branding
- [ ] Upload logo
- [ ] Set colors
- [ ] Preview changes
- [ ] Save branding
- [ ] Apply to all pages

### I.3 - Billing (Future Integration)
- [ ] Show current plan
- [ ] Payment history
- [ ] Update payment link
- [ ] Upgrade/downgrade

### I.4 - Role Management
- [ ] View roles
- [ ] Create custom role
- [ ] Edit role permissions
- [ ] Delete custom role

### I.5 - Company Settings
- [ ] Company info
- [ ] Timezone
- [ ] Date format
- [ ] Other settings

---

## 📋 PHASE J: REAL-TIME & POLISH (Week 6)

### J.1 - WebSocket Integration
- [ ] Setup Socket.io server
- [ ] Authenticate connections
- [ ] Broadcast record changes
- [ ] Broadcast announcements
- [ ] Handle reconnection
- [ ] Test real-time updates

### J.2 - Push Notifications
- [ ] Request notification permission
- [ ] Send notification on announcement
- [ ] Send notification on time-off approval
- [ ] Test on mobile

### J.3 - Email System
- [ ] Configure email sending
- [ ] Welcome email template
- [ ] Invite email template
- [ ] Password reset template
- [ ] Time-off approval template
- [ ] Test all emails

### J.4 - QR Code Generation
- [ ] Install QRCode library
- [ ] Generate VPN config QR
- [ ] Embed in invite email
- [ ] Show on device page
- [ ] Test scanning

### J.5 - Performance Optimization
- [ ] Enable response caching
- [ ] Optimize database queries
- [ ] Add database indexes
- [ ] Lazy load routes
- [ ] Test load time

### J.6 - Security Audit
- [ ] Test SQL injection
- [ ] Test XSS
- [ ] Test CSRF
- [ ] Test permission bypass
- [ ] Verify audit logging
- [ ] Check for exposed secrets

### J.7 - Cross-Browser Testing
- [ ] Chrome (desktop)
- [ ] Firefox (desktop)
- [ ] Safari (desktop)
- [ ] Safari (iOS)
- [ ] Chrome (Android)
- [ ] Edge

### J.8 - Responsive Testing
- [ ] iPhone SE (small)
- [ ] iPhone 14 Pro
- [ ] iPad
- [ ] Android phone
- [ ] Android tablet
- [ ] Laptop (1366px)
- [ ] Desktop (1920px)

---

## 📋 PHASE K: DOCUMENTATION & LAUNCH (Week 6)

### K.1 - User Documentation
- [ ] Employee quick start guide
- [ ] HR user guide
- [ ] Admin user guide
- [ ] Owner user guide
- [ ] DataForge guide

### K.2 - Video Tutorials (Simple)
- [ ] Employee setup (2 min)
- [ ] How to request time-off
- [ ] How to use DataForge
- [ ] Admin: invite employees

### K.3 - In-App Help
- [ ] Tooltips on key features
- [ ] Help links in sidebar
- [ ] Contact support link

### K.4 - Marketing Page
- [ ] Features section
- [ ] Pricing section
- [ ] Demo request form
- [ ] FAQ section

### K.5 - Launch Prep
- [ ] Final testing
- [ ] Backup everything
- [ ] Monitor setup
- [ ] Support email ready
- [ ] Go live!

---

## 📊 REVISED TIMELINE

| Phase | Description | Duration | Status |
|-------|-------------|----------|--------|
| A | Server Infrastructure | 3 days | ⬜ TODO |
| B | Authentication & Roles | 4 days | ⬜ TODO |
| C | PWA Foundation | 3 days | ⬜ TODO |
| D | Employee Portal | 4 days | ⬜ TODO |
| E | HR Portal | 5 days | ⬜ TODO |
| F | Manager Portal | 2 days | ⬜ TODO |
| G | Admin Portal | 4 days | ⬜ TODO |
| H | DataForge Builder | 5 days | ⬜ TODO |
| I | Owner Portal | 2 days | ⬜ TODO |
| J | Real-Time & Polish | 4 days | ⬜ TODO |
| K | Docs & Launch | 3 days | ⬜ TODO |

**TOTAL: ~6 weeks (39 working days)**

---

## 🎯 MVP SCOPE (First Release)

### INCLUDED IN MVP:
- ✅ Employee self-service portal
- ✅ HR portal (basic)
- ✅ Admin portal
- ✅ Owner portal (basic)
- ✅ DataForge with 20 templates
- ✅ VPN connection management
- ✅ Time-off requests
- ✅ Employee directory
- ✅ Audit logging
- ✅ Mobile responsive

### DEFERRED TO V2:
- ⏸️ Performance reviews
- ⏸️ Advanced scheduling
- ⏸️ Full HR document vault
- ⏸️ Onboarding checklists
- ⏸️ DataForge automations
- ⏸️ DataForge reports/charts
- ⏸️ 30 additional templates
- ⏸️ Offline PWA mode
- ⏸️ SSO (Okta)

---

**Document Updated:** January 17, 2026  
**Status:** ✅ CHECKLIST READY FOR BUILD

