# TRUEVAULT VPN - MASTER BUILD CHECKLIST (Part 7)

**Section:** Day 7 - Complete Automation System  
**Lines This Section:** ~3,000 lines  
**Time Estimate:** 10-12 hours  
**Created:** January 15, 2026 - 9:30 AM CST  

---

## DAY 7: AUTOMATION ENGINE & EMAIL SYSTEM

### **Goal:** Build complete business automation with dual email system

**What we're building:**
- Automation engine (workflow processor)
- 12 automated workflows  
- Dual email system (SMTP for customers, Gmail for admin)
- 19 professional email templates
- Support ticket system with auto-categorization
- Knowledge base for auto-resolution
- Scheduled task processing

**IMPORTANT - SECRET VIP SYSTEM:**
- ✅ NO VIP advertising anywhere
- ✅ NO VIP signup page
- ✅ Only admin adds emails to VIP list in database
- ✅ VIP users bypass PayPal completely (even 7-day trial)
- ✅ VIP badge appears after page refresh
- ✅ Completely hidden from public

---

## MORNING SESSION: EMAIL SYSTEM (3-4 hours)

### **Task 7.1: Create Email Tables in logs.db**
**Lines:** ~60 lines  
**Database:** logs.db

- [ ] Add to `/admin/setup-databases.php` (in logs.db section)
- [ ] Add these table creations:

```sql
-- Email log table (already exists from Part 2, just verify)
CREATE TABLE IF NOT EXISTS email_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    method TEXT NOT NULL, -- 'smtp' or 'gmail'
    recipient TEXT NOT NULL,
    subject TEXT NOT NULL,
    body TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    error_message TEXT,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Email queue table
CREATE TABLE IF NOT EXISTS email_queue (
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

**Verification:**
- [ ] Tables created
- [ ] Indexes exist
- [ ] Can query both tables

---

### **Task 7.2: Create Email Helper Classes**
**Lines:** ~550 lines  
**Files:** `/includes/Email.php` and `/includes/EmailTemplate.php`

- [ ] Create Email.php with SMTP and Gmail support
- [ ] Create EmailTemplate.php with template rendering
- [ ] Upload both files
- [ ] Test email sending

**Key Features:**
- SMTP for customer emails (admin@the-truth-publishing.com)
- Gmail for admin notifications (paulhalonen@gmail.com)
- Template system with variable replacement
- Email queue for bulk operations
- Complete logging

---

### **Task 7.3: Add Email Settings to Admin Panel**
**Lines:** ~100 lines  
**File:** `/admin/settings.php` (add email section)

- [ ] Add email configuration section
- [ ] Fields needed:
  - SMTP Host the-truth-publishing.com (Imap)
  - Imap Port 993 (incoming)
  - SMTP Port 465 (outgoing)
  - SMTP Username (admin@the-truth-publishing.com)
  - SMTP Password A'ndassiAthena8
  - Gmail Username (paulhalonen@gmail.com)
  - Gmail App Password ezdq mgqk mrcn xovx
- [ ] Save to system_settings table
- [ ] Test email sending from settings page

---

### **Task 7.4: Create 19 Email Templates**
**Lines:** ~950 lines  
**File:** `/admin/install-email-templates.php`

- [ ] Create script to insert all email templates
- [ ] Run once to populate email_templates table
- [ ] Delete script after running (security)

**19 Templates:**

1. **welcome_basic** - Simple welcome for Standard tier
2. **welcome_formal** - Professional welcome for Pro tier  
3. **welcome_vip** - Executive welcome (SECRET - only triggered internally)
4. **payment_success_basic** - Thank you for payment
5. **payment_success_formal** - Professional payment confirmation
6. **payment_failed_reminder1** - Friendly reminder (Day 0)
7. **payment_failed_reminder2** - Urgent notice (Day 3)
8. **payment_failed_final** - Final warning (Day 7)
9. **ticket_received** - Support ticket acknowledgment
10. **ticket_resolved** - Ticket resolution notification
11. **complaint_acknowledge** - Apology for complaint
12. **complaint_resolved** - Complaint resolution
13. **server_down** - Server offline alert
14. **server_restored** - Server back online
15. **cancellation_survey** - Exit survey
16. **retention_offer** - Special offer to stay
17. **winback_campaign** - Come back email (30 days after cancel)
18. **vip_request_received** - VIP request confirmation (admin gets copy)
19. **vip_welcome_package** - Secret VIP welcome (NO public mention)

---

## AFTERNOON SESSION: AUTOMATION ENGINE (4-5 hours)

### **Task 7.5: Create Automation Tables**
**Lines:** ~120 lines  
**Database:** logs.db (add to setup script)

- [ ] Add automation tables to logs.db

```sql
-- Workflow executions table
CREATE TABLE IF NOT EXISTS workflow_executions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_name TEXT NOT NULL,
    trigger_event TEXT NOT NULL,
    user_id INTEGER,
    status TEXT NOT NULL DEFAULT 'running',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    error_message TEXT,
    execution_data TEXT -- JSON of variables used
);

-- Scheduled workflow steps table
CREATE TABLE IF NOT EXISTS scheduled_workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    execution_id INTEGER NOT NULL,
    step_name TEXT NOT NULL,
    step_data TEXT, -- JSON
    execute_at DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    executed_at DATETIME,
    FOREIGN KEY (execution_id) REFERENCES workflow_executions(id)
);

CREATE INDEX idx_scheduled_steps ON scheduled_workflow_steps(status, execute_at);
```

**Verification:**
- [ ] Tables created
- [ ] Foreign keys work
- [ ] Indexes exist

---

### **Task 7.6: Create Automation Engine**
**Lines:** ~400 lines  
**File:** `/includes/AutomationEngine.php`

- [ ] Create automation workflow processor
- [ ] Handles workflow triggers
- [ ] Processes scheduled steps
- [ ] Sends emails via Email class
- [ ] Logs all executions

**Core Features:**
- Workflow registration
- Step scheduling (delay support)
- Variable passing between steps
- Error handling and retry logic
- Complete execution tracking

---

### **Task 7.7: Create 12 Automated Workflows**
**Lines:** ~1,200 lines  
**File:** `/includes/Workflows.php`

- [ ] Create Workflows class with all 12 workflows
- [ ] Each workflow is a method
- [ ] Uses AutomationEngine to process
- [ ] All database-driven

**12 Workflows:**

1. **newCustomerOnboarding** - Welcome → Setup guide (1hr) → Follow-up (24hr)
2. **paymentFailedEscalation** - Day 0, 3, 7 reminders → Day 8 suspend
3. **paymentSuccess** - Invoice → Thank you → Update status
4. **supportTicketCreated** - Categorize → Check KB → Acknowledge → Assign
5. **supportTicketResolved** - Notification → Survey (1hr)
6. **complaintHandling** - Apology → Flag admin → Follow-up (7 days)
7. **serverDownAlert** - Alert admin → Notify customers (if unplanned)
8. **serverRestored** - All clear notification → Incident report
9. **cancellationRequest** - Survey → Retention offer (1hr) → Schedule cancel (2 days) → Win-back (30 days)
10. **monthlyInvoicing** - Generate invoices → Send emails → Schedule retries → Report
11. **vipRequestReceived** - Log request → Notify admin (Gmail)
12. **vipApproved** - Upgrade tier → Secret welcome email → Provision VIP resources

**SECRET VIP WORKFLOWS:**
- VIP workflows have NO public-facing components
- VIP emails never mention "VIP" in subject lines
- Use professional/executive styling but don't advertise the tier
- VIP badge only shows in dashboard after login

---

## EVENING SESSION: SUPPORT TICKET SYSTEM (3-4 hours)

### **Task 7.8: Create Support Ticket Tables**
**Lines:** ~100 lines  
**Database:** Create new support.db

- [ ] Create `/databases/support.db`
- [ ] Add to database setup script

```sql
-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subject TEXT NOT NULL,
    description TEXT NOT NULL,
    category TEXT, -- 'billing', 'technical', 'account'
    priority TEXT NOT NULL DEFAULT 'normal', -- 'low', 'normal', 'high', 'urgent'
    status TEXT NOT NULL DEFAULT 'open',
    assigned_to TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Support ticket messages
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,
    is_staff INTEGER DEFAULT 0,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id)
);

-- Knowledge base articles
CREATE TABLE IF NOT EXISTS knowledge_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    category TEXT NOT NULL,
    keywords TEXT, -- For searching
    view_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tickets_user ON support_tickets(user_id, status);
CREATE INDEX idx_tickets_status ON support_tickets(status, priority);
CREATE INDEX idx_kb_category ON knowledge_base(category);
```

---

### **Task 7.9: Create Support Ticket APIs**
**Lines:** ~300 lines  
**Files:** `/api/support/*.php`

- [ ] Create folder: `/api/support/`
- [ ] Create these endpoints:

**Files to create:**
1. `/api/support/create-ticket.php` (100 lines)
2. `/api/support/list-tickets.php` (80 lines)
3. `/api/support/get-ticket.php` (60 lines)
4. `/api/support/add-message.php` (60 lines)

**Features:**
- Auto-categorization (billing/technical/account)
- Priority assignment (VIP = high)
- Knowledge base search for auto-resolution
- Email notifications
- Admin escalation

---

### **Task 7.10: Create Ticket Dashboard for Users**
**Lines:** ~200 lines  
**File:** `/dashboard/support.php`

- [ ] Create support ticket interface
- [ ] List user's tickets
- [ ] Create new ticket form
- [ ] View ticket details and messages
- [ ] Upload and test

---

### **Task 7.11: Create Admin Ticket Management**
**Lines:** ~250 lines  
**File:** `/admin/support-tickets.php`

- [ ] Create admin ticket dashboard
- [ ] View all tickets
- [ ] Filter by status/priority
- [ ] Assign tickets
- [ ] Respond to tickets
- [ ] Close/resolve tickets
- [ ] Upload and test

---

## DAY 7 COMPLETION CHECKLIST

### **Files Created (15+ files):**
- [ ] /includes/Email.php (350 lines)
- [ ] /includes/EmailTemplate.php (200 lines)
- [ ] /includes/AutomationEngine.php (400 lines)
- [ ] /includes/Workflows.php (1,200 lines)
- [ ] /admin/install-email-templates.php (950 lines)
- [ ] /api/support/create-ticket.php (100 lines)
- [ ] /api/support/list-tickets.php (80 lines)
- [ ] /api/support/get-ticket.php (60 lines)
- [ ] /api/support/add-message.php (60 lines)
- [ ] /dashboard/support.php (200 lines)
- [ ] /admin/support-tickets.php (250 lines)

**Total Day 7:** ~3,850 lines

### **Database Tables Added:**
- [ ] email_log
- [ ] email_queue
- [ ] workflow_executions
- [ ] scheduled_workflow_steps
- [ ] support_tickets
- [ ] ticket_messages
- [ ] knowledge_base

### **Features Complete:**
- [ ] Dual email system (SMTP + Gmail)
- [ ] 19 email templates
- [ ] Automation engine
- [ ] 12 automated workflows
- [ ] Support ticket system
- [ ] Knowledge base
- [ ] Auto-categorization
- [ ] Admin ticket management
- [ ] Secret VIP workflows (no advertising!)

### **Testing:**
- [ ] Can send SMTP email
- [ ] Can send Gmail email
- [ ] Email templates render correctly
- [ ] Workflows trigger automatically
- [ ] Scheduled steps execute on time
- [ ] Support tickets create
- [ ] Knowledge base searches work
- [ ] VIP emails are SECRET (no public mention)

### **Secret VIP System Verified:**
- [ ] NO VIP on landing page
- [ ] NO VIP on pricing page
- [ ] NO VIP signup form
- [ ] Admin can add VIP emails via settings
- [ ] VIP users bypass PayPal on signup
- [ ] VIP badge shows after refresh
- [ ] VIP emails are professional (no "VIP" in subject)

### **Email System Configured:**
- [ ] SMTP settings in admin panel
- [ ] Gmail app password created
- [ ] Test emails send successfully
- [ ] Email logs recording
- [ ] Queue processing works

### **Cron Job Setup:**
- [ ] Add to server crontab:
```bash
*/5 * * * * php /path/to/process-automation.php
```
- [ ] Create `/cron/process-automation.php` to call:
  - AutomationEngine::processScheduled()
  - Email::processQueue()

---

## 📊 PROGRESS UPDATE

**Completed:**
- ✅ Day 1: Setup (~800 lines)
- ✅ Day 2: Databases (~700 lines)
- ✅ Day 3: Authentication (~1,300 lines)
- ✅ Day 4: Device Management (~1,120 lines)
- ✅ Day 5: Admin & PayPal (~1,630 lines)
- ✅ Day 6: Advanced Features (~2,000 lines)
- ✅ Day 7: Automation System (~3,850 lines)

**Total:** ~11,400 lines

**Remaining:**
- ⏳ Day 8: Frontend Pages & Business Transfer (~1,700 lines)

**Final Estimate:** ~13,100 lines complete system

---

**Status:** Day 7 Complete - Full automation with secret VIP system!  
**Next:** Day 8 - All frontend pages and business transfer wizard  

**IMPORTANT REMINDERS:**
- VIP system is COMPLETELY SECRET
- NO public advertising for VIP
- Only admin adds VIP emails
- VIP welcome emails are professional (no "VIP" branding)
- seige235@yahoo.com gets dedicated St. Louis server

**Say "next" for Day 8 (Frontend & Transfer)!** 🚀


---

## ADVANCED PARENTAL CONTROLS SESSION (4-5 hours) - ADDED JAN 17, 2026

### **Task 7.X: Advanced Parental Controls - Calendar Scheduling**
**Lines:** ~600 lines total
**Files:** Multiple files
**Database:** parental_controls.db (6 new tables)

⚠️ **IMPORTANT:** This extends the basic parental controls built in PART 6.
The basic system (category filters, domain blocking) is already complete.
This task adds the advanced scheduling and device control features.

---

### **Subtask 7.X.1: Create Advanced Parental Control Tables**
**Lines:** ~80 lines
**Database:** parental_controls.db

- [ ] Add to `/admin/setup-databases.php` (parental_controls.db section)
- [ ] Create 6 new tables:

```sql
-- Schedule templates
CREATE TABLE IF NOT EXISTS parental_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER,  -- NULL = applies to all devices
    schedule_name TEXT NOT NULL,  -- "School Day", "Weekend", etc.
    is_template INTEGER DEFAULT 0, -- 1 if reusable template
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Time windows for schedules
CREATE TABLE IF NOT EXISTS schedule_windows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    schedule_id INTEGER NOT NULL,
    day_of_week INTEGER, -- 0=Sunday, 1=Monday, etc. NULL=specific date
    specific_date TEXT,  -- NULL if using day_of_week
    start_time TEXT NOT NULL,     -- "15:00" (3pm)
    end_time TEXT NOT NULL,       -- "16:00" (4pm)
    access_type TEXT NOT NULL,    -- "full", "homework_only", "streaming_only", "blocked"
    FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE CASCADE
);

-- Whitelist (always allow)
CREATE TABLE IF NOT EXISTS parental_whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    domain TEXT NOT NULL,
    notes TEXT,         -- "School website", "Khan Academy"
    added_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Temporary blocks
CREATE TABLE IF NOT EXISTS temporary_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    domain TEXT NOT NULL,
    blocked_until TEXT NOT NULL,
    reason TEXT,        -- "Punishment", "Focus time"
    added_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Gaming controls
CREATE TABLE IF NOT EXISTS gaming_restrictions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER NOT NULL,
    gaming_enabled INTEGER DEFAULT 1,
    last_toggled_at TEXT DEFAULT CURRENT_TIMESTAMP,
    toggled_by TEXT,     -- "parent" or "schedule"
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Device-specific rules
CREATE TABLE IF NOT EXISTS device_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER NOT NULL,
    schedule_id INTEGER, -- Links to parental_schedules
    override_enabled INTEGER DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE SET NULL
);
```

---

### **Subtask 7.X.2: Build Calendar Scheduling UI**
**Lines:** ~250 lines
**File:** `/dashboard/parental-schedule.php`

- [ ] Create monthly calendar view
- [ ] Features:
  - Full month calendar with clickable days
  - Color-coded days (full/restricted/blocked)
  - Day selection (click to edit)
  - Month navigation (prev/next)
  - Quick templates dropdown
- [ ] JavaScript calendar rendering
- [ ] Beautiful gradient UI matching TrueVault design
- [ ] Upload and test

**Calendar Features:**
- Visual monthly view
- Click day → Edit schedule modal
- Color coding: Green=Free, Yellow=Limited, Red=Blocked
- Template buttons: "School Day", "Weekend", "Holiday"

---

### **Subtask 7.X.3: Build Time Window Editor**
**Lines:** ~180 lines
**File:** `/dashboard/schedule-editor.php`

- [ ] Create time window management interface
- [ ] Features:
  - Add multiple time windows per day
  - Set start/end times (dropdown or time picker)
  - Select access type (Full, Homework Only, Streaming Only, Blocked)
  - Delete windows
  - Apply to: This day / Every Monday / Weekdays / Custom
- [ ] Save schedule via AJAX
- [ ] Upload and test

**Time Window Types:**
- **Full:** Everything allowed
- **Homework Only:** Only whitelist allowed
- **Streaming Only:** Netflix, Disney+, YouTube, etc.
- **Blocked:** Nothing allowed

---

### **Subtask 7.X.4: Build Gaming Controls Dashboard**
**Lines:** ~120 lines
**File:** `/dashboard/gaming-controls.php`

- [ ] Create gaming-specific control panel
- [ ] Features:
  - Master toggle: Gaming ON/OFF
  - Quick actions: "Block for 1 Hour", "Block Until Bedtime", "Allow Extra Hour"
  - Device status (which gaming devices are active)
  - Gaming server detection status
- [ ] Real-time toggle (AJAX)
- [ ] Upload and test

**Gaming Detection:**
- Detect Xbox Live, PlayStation Network, Steam, Epic Games
- Show which gaming devices are currently active
- Allow parent to block specific devices

---

### **Subtask 7.X.5: Build Whitelist/Blacklist Manager**
**Lines:** ~150 lines
**File:** Already in `/dashboard/parental-controls.php` - extend it

- [ ] Add Whitelist section
  - Form to add domains
  - List of whitelisted domains with remove button
  - Notes field (optional)
- [ ] Add Temporary Blocks section
  - Form to add domain + duration
  - List of active blocks with countdown
  - "Unblock Now" and "Extend" buttons
- [ ] Upload and test

**Whitelist Examples:**
- khanacademy.org (educational)
- classroom.google.com (school)
- school.edu (school website)

**Temporary Block Durations:**
- 1 hour
- Until bedtime (9pm)
- Until tomorrow
- 1 week

---

### **Subtask 7.X.6: Build Schedule Templates System**
**Lines:** ~100 lines
**File:** `/api/parental-controls/templates.php`

- [ ] Create API for template management
- [ ] Pre-built templates:
  - School Day (8am-3pm blocked, 3-4pm homework, 4-8pm limited)
  - Weekend (9am-9pm free with breaks)
  - Holiday (extended hours)
  - Grounded (very restricted)
- [ ] User can save custom templates
- [ ] Apply template to selected days
- [ ] Upload and test

---

### **Subtask 7.X.7: Build Schedule Enforcement Engine**
**Lines:** ~200 lines
**File:** `/api/parental-controls/enforce.php`

- [ ] Create enforcement logic
- [ ] Check current time against schedule windows
- [ ] Priority order:
  1. Blacklist (always blocked)
  2. Whitelist (always allowed)
  3. Temporary blocks
  4. Gaming restrictions
  5. Schedule windows
  6. Default (allow)
- [ ] Log blocked requests
- [ ] Return block/allow decision
- [ ] Upload and test

**Enforcement Logic:**
```php
function isAccessAllowed($userId, $deviceId, $domain, $currentTime) {
    // 1. Check if parental controls enabled
    // 2. Check blacklist (always block)
    // 3. Check whitelist (always allow)
    // 4. Check temporary blocks
    // 5. Check gaming restrictions
    // 6. Check current time window
    // 7. Return allow/block + reason
}
```

---

### **Subtask 7.X.8: Build Statistics Dashboard**
**Lines:** ~120 lines
**File:** `/dashboard/parental-stats.php`

- [ ] Create statistics and reporting dashboard
- [ ] Features:
  - Screen time per child (this week)
  - Most visited sites
  - Most blocked sites
  - Gaming hours
  - Schedule adherence percentage
- [ ] Weekly email report (optional)
- [ ] Upload and test

**Statistics Shown:**
- Total screen time (daily/weekly)
- Gaming hours
- Educational site time
- Top blocked sites
- Schedule compliance

---

### **Subtask 7.X.9: Mobile Responsive Design**
**Lines:** ~80 lines
**File:** Update all parental control pages

- [ ] Make calendar swipeable on mobile
- [ ] Collapsible time windows
- [ ] Bottom sheet for editing
- [ ] Quick toggle buttons optimized for mobile
- [ ] Test on real mobile devices
- [ ] Upload and test

---

### **Testing Checklist for Advanced Parental Controls:**

- [ ] Calendar displays correct month
- [ ] Can click days to edit schedules
- [ ] Time windows save correctly
- [ ] Templates apply correctly
- [ ] Gaming toggle works instantly
- [ ] Whitelist domains always allowed
- [ ] Blacklist domains always blocked
- [ ] Temporary blocks expire correctly
- [ ] Schedule enforcement works at correct times
- [ ] Statistics calculate correctly
- [ ] Mobile view works properly
- [ ] No conflicts between rules
- [ ] Real-time updates work

---

**Total Lines for Advanced Parental Controls:** ~1,280 lines

**Time Estimate:** 8-10 hours

**Priority:** HIGH - Family feature, competitive advantage

**Dependencies:**
- Basic parental controls (PART 6) ✅ Complete
- Device management system ✅ Complete
- User authentication ✅ Complete

---

**NOTE:** This is a MAJOR feature that transforms TrueVault into a family internet safety solution.
No other VPN offers calendar-based parental controls with this level of sophistication.



---

## THEME SYSTEM & DATABASE SETUP SESSION (4-5 hours) - ADDED JAN 18, 2026

**Blueprint Reference:** SECTION_24_THEME_AND_PAGE_BUILDER.md

### **Goal:** Build 100% database-driven theme and content system

**Why This Matters:**
- **6 million dollar goal** requires business transfer-readiness
- New owner with ZERO technical knowledge must run this
- NO hardcoded values anywhere
- Point-and-click customization
- 30-minute business handoff

**What We're Building:**
- Theme management database
- Theme helper classes
- 12 pre-built themes
- Seasonal auto-switching
- Global settings system
- 100% database-driven frontend

---

### **Task 7.Y.1: Create Theme Management Tables**
**Lines:** ~200 lines  
**Database:** New file `/databases/themes.db`

- [ ] Create `/databases/themes.db` database file
- [ ] Create 9 new tables (see blueprint SECTION_24)
- [ ] Add to setup script or create separate setup

**Tables to Create:**

```sql
-- Theme definitions
CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    description TEXT,
    preview_image TEXT,
    style TEXT NOT NULL, -- 'light', 'medium', 'dark'
    is_active INTEGER DEFAULT 0,
    is_seasonal INTEGER DEFAULT 0,
    season TEXT, -- 'winter', 'spring', 'summer', 'fall', null
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Color palettes
CREATE TABLE theme_colors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    color_key TEXT NOT NULL, -- 'primary', 'secondary', 'accent', etc.
    color_value TEXT NOT NULL, -- Hex code
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

CREATE INDEX idx_theme_colors ON theme_colors(theme_id, color_key);

-- Theme settings
CREATE TABLE theme_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type TEXT NOT NULL, -- 'text', 'number', 'boolean', 'json'
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

CREATE INDEX idx_theme_settings ON theme_settings(theme_id, setting_key);

-- Page definitions
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE, -- URL path
    title TEXT NOT NULL,
    meta_description TEXT,
    meta_keywords TEXT,
    is_public INTEGER DEFAULT 1, -- 1 = anyone, 0 = logged in only
    is_active INTEGER DEFAULT 1,
    layout_template TEXT NOT NULL, -- 'default', 'blank', 'landing'
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Page content blocks
CREATE TABLE page_sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    section_type TEXT NOT NULL, -- 'hero', 'features', 'pricing', etc.
    section_data TEXT NOT NULL, -- JSON content
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE INDEX idx_page_sections ON page_sections(page_id, sort_order);

-- Global site settings
CREATE TABLE site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type TEXT NOT NULL, -- 'text', 'number', 'boolean', 'json', 'image'
    category TEXT, -- 'general', 'branding', 'seo', 'social'
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Navigation menus
CREATE TABLE navigation_menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    menu_location TEXT NOT NULL, -- 'header', 'footer', 'sidebar'
    label TEXT NOT NULL,
    url TEXT NOT NULL,
    target TEXT DEFAULT '_self', -- '_self' or '_blank'
    icon TEXT, -- Icon class or emoji
    parent_id INTEGER, -- For dropdowns
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    required_role TEXT -- null, 'user', 'admin'
);

CREATE INDEX idx_navigation_menus ON navigation_menus(menu_location, sort_order);

-- Content versioning
CREATE TABLE page_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    revision_data TEXT NOT NULL, -- Full page JSON
    created_by INTEGER, -- Admin user ID
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

-- Media library
CREATE TABLE media_library (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_type TEXT NOT NULL, -- 'image', 'video', 'document'
    file_size INTEGER NOT NULL, -- bytes
    mime_type TEXT,
    alt_text TEXT,
    uploaded_by INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Verification:**
- [ ] Database created
- [ ] All 9 tables exist
- [ ] Indexes created
- [ ] Foreign keys working

---

### **Task 7.Y.2: Insert 12 Pre-built Themes**
**Lines:** ~300 lines  
**Database:** themes.db

- [ ] Insert 12 theme records
- [ ] Insert color palettes for each theme (11 colors × 12 themes = 132 color records)
- [ ] Insert theme settings for each

**Themes to Create:**

**Base Themes (3):**
1. `default_light` - Clean white background, blue accent
2. `default_medium` - Soft gray tones, purple accent
3. `default_dark` - Deep blue/purple gradient (CURRENT)

**Winter Themes (2):**
4. `winter_light` - Snow white, ice blue
5. `winter_dark` - Midnight blue, snowflake effects

**Spring Themes (2):**
6. `spring_light` - Pastel greens, floral pink
7. `spring_dark` - Deep forest green, nature

**Summer Themes (2):**
8. `summer_light` - Bright sunshine yellow, ocean blue
9. `summer_dark` - Deep ocean blue, sunset orange

**Fall Themes (2):**
10. `fall_light` - Warm orange, golden brown
11. `fall_dark` - Deep burgundy, autumn red

**VIP Theme (1):**
12. `vip_gold` - Gold gradient, black background (for VIP users only)

**Color Keys (11 per theme):**
- `primary` - Main brand color
- `secondary` - Secondary brand color
- `accent` - Accent/highlight color
- `background` - Page background
- `surface` - Card/panel background
- `text_primary` - Main text color
- `text_secondary` - Muted text
- `success` - Green
- `warning` - Yellow
- `error` - Red
- `info` - Blue

**Verification:**
- [ ] 12 themes inserted
- [ ] 132 color records inserted
- [ ] default_dark is set as active
- [ ] Seasonal themes flagged correctly

---

### **Task 7.Y.3: Insert Global Site Settings**
**Lines:** ~100 lines  
**Database:** themes.db (site_settings table)

- [ ] Insert 25+ essential site settings
- [ ] All current hardcoded values moved to database

**Settings to Insert:**

**General:**
- `site_title` = "TrueVault VPN"
- `site_tagline` = "Your Complete Digital Fortress"
- `site_logo` = "/assets/logo.png"
- `site_favicon` = "/assets/favicon.ico"
- `contact_email` = "admin@the-truth-publishing.com"
- `support_email` = "admin@the-truth-publishing.com"
- `admin_email` = "admin@the-truth-publishing.com"
- `company_name` = "TrueVault VPN"
- `company_address` = "(to be filled by owner)"
- `support_phone` = "(to be filled by owner)"

**Branding:**
- `active_theme_id` = 3 (default_dark)
- `enable_seasonal_themes` = 1
- `logo_width` = 180
- `logo_height` = 50

**SEO:**
- `meta_description` = "Advanced VPN with parental controls, port forwarding, and camera dashboard"
- `meta_keywords` = "VPN, privacy, security, parental controls, port forwarding"
- `google_analytics_id` = ""

**Social Media:**
- `facebook_url` = ""
- `twitter_url` = ""
- `linkedin_url` = ""
- `youtube_url` = ""

**Features:**
- `maintenance_mode` = 0
- `enable_registration` = 1
- `enable_free_trial` = 1
- `trial_days` = 7

**Pricing (for display):**
- `price_standard` = 9.97
- `price_pro` = 14.97
- `currency_symbol` = "$"

**Verification:**
- [ ] All settings inserted
- [ ] Can query settings
- [ ] Values make sense

---

### **Task 7.Y.4: Create Theme Helper Class**
**Lines:** ~250 lines  
**File:** `/includes/Theme.php`

- [ ] Create Theme.php class
- [ ] Methods for theme management
- [ ] Caching for performance
- [ ] Upload and test

**Key Methods:**

```php
class Theme {
    // Get active theme
    public static function getActiveTheme()
    
    // Get color with fallback
    public static function getColor($key, $fallback = '#667eea')
    
    // Get setting with fallback
    public static function getSetting($key, $fallback = '')
    
    // Switch theme
    public static function switchTheme($themeId)
    
    // List all themes
    public static function listThemes($style = null, $seasonal = null)
    
    // Auto-switch seasonal
    public static function autoSwitchSeasonal()
    
    // Determine current season
    public static function getCurrentSeason()
    
    // Clear theme cache
    public static function clearCache()
}
```

**Caching Strategy:**
- Cache theme data in PHP session
- Cache expires after 1 hour
- Clear on theme switch
- Reduces database queries

**Verification:**
- [ ] Class loads without errors
- [ ] Can get active theme
- [ ] Can get colors
- [ ] Can switch themes
- [ ] Cache works

---

### **Task 7.Y.5: Create Content Helper Class**
**Lines:** ~150 lines  
**File:** `/includes/Content.php`

- [ ] Create Content.php class
- [ ] Methods for site settings
- [ ] Shortcode support (optional)
- [ ] Upload and test

**Key Methods:**

```php
class Content {
    // Get site setting
    public static function get($key, $fallback = '')
    
    // Set site setting
    public static function set($key, $value, $type = 'text')
    
    // Get multiple settings at once
    public static function getMany($keys)
    
    // Check if setting exists
    public static function exists($key)
    
    // Delete setting
    public static function delete($key)
    
    // Get all settings by category
    public static function getByCategory($category)
}
```

**Usage Examples:**

```php
// Instead of hardcoding:
$siteTitle = "TrueVault VPN"; // BAD

// Use database:
$siteTitle = Content::get('site_title'); // GOOD

// With fallback:
$phone = Content::get('support_phone', '1-800-VPN-HELP');

// Set value:
Content::set('site_title', 'New Title');
```

**Verification:**
- [ ] Class loads without errors
- [ ] Can get settings
- [ ] Can set settings
- [ ] Fallbacks work
- [ ] Category filtering works

---

### **Task 7.Y.6: Create PageBuilder Helper Class**
**Lines:** ~300 lines  
**File:** `/includes/PageBuilder.php`

- [ ] Create PageBuilder.php class
- [ ] Methods for page/section management
- [ ] JSON handling
- [ ] Upload and test

**Key Methods:**

```php
class PageBuilder {
    // Get page by slug
    public static function getPage($slug)
    
    // Get all sections for page
    public static function getSections($pageId)
    
    // Add section to page
    public static function addSection($pageId, $type, $data, $order = null)
    
    // Update section
    public static function updateSection($sectionId, $data)
    
    // Delete section
    public static function deleteSection($sectionId)
    
    // Reorder sections
    public static function reorderSections($pageId, $orderArray)
    
    // Render page
    public static function render($slug)
    
    // Save revision
    public static function saveRevision($pageId, $adminId)
    
    // Get revisions
    public static function getRevisions($pageId, $limit = 10)
    
    // Restore revision
    public static function restoreRevision($revisionId)
}
```

**Section Types Supported:**
- `hero` - Hero banner
- `features` - Feature grid
- `pricing` - Pricing cards
- `testimonials` - Customer quotes
- `cta` - Call-to-action
- `text` - Rich text
- `image` - Image gallery
- `video` - Video embed
- `faq` - FAQ accordion
- `stats` - Statistics
- `form` - Contact form

**Verification:**
- [ ] Class loads without errors
- [ ] Can get pages
- [ ] Can add/edit/delete sections
- [ ] JSON parsing works
- [ ] Reordering works

---

### **Task 7.Y.7: Update Database Helper to Support themes.db**
**Lines:** ~20 lines  
**File:** `/includes/Database.php`

- [ ] Add themes.db to connection pool
- [ ] Update getInstance() method
- [ ] Test connection
- [ ] Upload

**Add to Database.php:**

```php
private static $databases = [
    'users' => '/databases/users.db',
    'devices' => '/databases/devices.db',
    'servers' => '/databases/servers.db',
    'billing' => '/databases/billing.db',
    'port_forwards' => '/databases/port_forwards.db',
    'parental_controls' => '/databases/parental_controls.db',
    'admin' => '/databases/admin.db',
    'logs' => '/databases/logs.db',
    'support' => '/databases/support.db',
    'themes' => '/databases/themes.db', // ADD THIS LINE
];
```

**Verification:**
- [ ] Can connect to themes.db
- [ ] All helper classes work with new DB
- [ ] No connection errors

---

### **Task 7.Y.8: Create Seasonal Theme Cron Job**
**Lines:** ~80 lines  
**File:** `/cron/switch-seasonal-theme.php`

- [ ] Create cron script
- [ ] Check if seasonal themes enabled
- [ ] Determine current season
- [ ] Switch to appropriate theme
- [ ] Log changes
- [ ] Upload

**Cron Schedule:** Daily at 2 AM

```bash
0 2 * * * /usr/bin/php /path/to/cron/switch-seasonal-theme.php
```

**Season Detection:**
- Winter: Dec 1 - Feb 28/29
- Spring: Mar 1 - May 31
- Summer: Jun 1 - Aug 31
- Fall: Sep 1 - Nov 30

**Logic:**

```php
// Pseudo-code
if (Content::get('enable_seasonal_themes') == 1) {
    $currentSeason = Theme::getCurrentSeason();
    $activeTheme = Theme::getActiveTheme();
    
    // Only switch if current theme isn't seasonal for this season
    if ($activeTheme['season'] != $currentSeason) {
        // Find seasonal theme matching current base style
        $newTheme = Theme::getSeasonalTheme($currentSeason, $activeTheme['style']);
        Theme::switchTheme($newTheme['id']);
        
        // Log change
        error_log("Auto-switched to {$currentSeason} theme");
    }
}
```

**Verification:**
- [ ] Cron job runs without errors
- [ ] Seasons detected correctly
- [ ] Theme switches automatically
- [ ] Logs recorded

---

### **Testing Checklist for Theme System:**

- [ ] Database created successfully
- [ ] All 12 themes present
- [ ] Color palettes complete (132 colors)
- [ ] Site settings populated (25+ settings)
- [ ] Theme::getActiveTheme() works
- [ ] Theme::getColor() returns correct hex
- [ ] Theme::switchTheme() changes active theme
- [ ] Content::get() returns settings
- [ ] Content::set() updates settings
- [ ] PageBuilder::getPage() loads pages
- [ ] Seasonal auto-switch works
- [ ] Cache improves performance
- [ ] No hardcoded values in frontend

---

**Total Lines for Theme System:** ~1,400 lines

**Time Estimate:** 4-5 hours

**Priority:** CRITICAL - Required for business transfer and 6M goal

**Dependencies:**
- Database.php ✅ Complete
- Admin authentication ✅ Complete

**Next:** Part 8 will build the frontend pages and page builder UI

---

**END OF PART 7 ADDITIONS**
