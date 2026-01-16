# MASTER CHECKLIST - PART 11: ADVANCED PARENTAL CONTROLS

**Blueprint Section:** SECTION_22_ADVANCED_PARENTAL_CONTROLS.md  
**Created:** January 16, 2026  
**Estimated Time:** 20-25 hours (5 weeks development)  
**Priority:** HIGH - Family Plan Key Feature  

---

## 📋 OVERVIEW

Advanced parental control system building on basic controls (PART 6) with:
- Monthly calendar scheduling interface
- Multiple time windows per day
- Device-specific rules
- Gaming server controls (Xbox, PlayStation, Steam)
- Whitelist/blacklist with temporary blocks
- Recurring schedule templates
- Parent quick-action overrides
- Weekly statistics reports

**Current Status:** Basic parental controls implemented (category filters, domain blocking, blocked log)

---

## 📌 PREREQUISITES

Before starting PART 11, ensure:
- [ ] PART 6 completed (basic parental controls working)
- [ ] parental_rules table exists
- [ ] blocked_domains table exists
- [ ] blocked_categories table exists
- [ ] blocked_requests table exists
- [ ] Device management working

---

## 🔧 TASK 11.1: Database Schema Updates

**Time Estimate:** 1 hour

### 11.1.1 Create Schedule Tables
- [ ] Create parental_schedules table:
  ```sql
  CREATE TABLE IF NOT EXISTS parental_schedules (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      device_id INTEGER,
      schedule_name TEXT NOT NULL,
      is_template BOOLEAN DEFAULT 0,
      is_active BOOLEAN DEFAULT 1,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
  );
  ```

### 11.1.2 Create Time Windows Table
- [ ] Create schedule_windows table:
  ```sql
  CREATE TABLE IF NOT EXISTS schedule_windows (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      schedule_id INTEGER NOT NULL,
      day_of_week INTEGER,
      specific_date DATE,
      start_time TIME NOT NULL,
      end_time TIME NOT NULL,
      access_type TEXT NOT NULL,
      notes TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE CASCADE
  );
  ```
- [ ] access_type values: full, homework_only, streaming_only, blocked

### 11.1.3 Create Whitelist Table
- [ ] Create parental_whitelist table:
  ```sql
  CREATE TABLE IF NOT EXISTS parental_whitelist (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      domain TEXT NOT NULL,
      notes TEXT,
      added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      UNIQUE(user_id, domain)
  );
  ```

### 11.1.4 Create Temporary Blocks Table
- [ ] Create temporary_blocks table:
  ```sql
  CREATE TABLE IF NOT EXISTS temporary_blocks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      domain TEXT NOT NULL,
      blocked_until DATETIME NOT NULL,
      reason TEXT,
      added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  );
  ```

### 11.1.5 Create Gaming Restrictions Table
- [ ] Create gaming_restrictions table:
  ```sql
  CREATE TABLE IF NOT EXISTS gaming_restrictions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      device_id INTEGER,
      gaming_enabled BOOLEAN DEFAULT 1,
      last_toggled_at DATETIME,
      toggled_by TEXT,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
  );
  ```

### 11.1.6 Create Device Rules Table
- [ ] Create device_rules table:
  ```sql
  CREATE TABLE IF NOT EXISTS device_rules (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      device_id INTEGER NOT NULL,
      schedule_id INTEGER,
      override_enabled BOOLEAN DEFAULT 0,
      notes TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
      FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id) ON DELETE SET NULL
  );
  ```

**Verification:**
- [ ] All 6 tables created successfully
- [ ] Foreign keys working
- [ ] Indexes added for performance

---

## 🔧 TASK 11.2: Schedule Management Backend

**Time Estimate:** 3 hours

### 11.2.1 Create Schedule API
- [ ] Create /api/parental/schedules.php
- [ ] Implement GET /schedules - list user's schedules
- [ ] Implement GET /schedules/{id} - get schedule details
- [ ] Implement POST /schedules - create schedule
- [ ] Implement PUT /schedules/{id} - update schedule
- [ ] Implement DELETE /schedules/{id} - delete schedule

### 11.2.2 Create Time Windows API
- [ ] Create /api/parental/windows.php
- [ ] Implement GET /windows?schedule_id= - list windows
- [ ] Implement POST /windows - add time window
- [ ] Implement PUT /windows/{id} - update window
- [ ] Implement DELETE /windows/{id} - delete window
- [ ] Validate: no overlapping windows

### 11.2.3 Create Schedule Templates
- [ ] Create default "School Day" template
- [ ] Create default "Weekend" template
- [ ] Create default "Holiday" template
- [ ] Implement clone template to user
- [ ] Allow users to save custom templates

### 11.2.4 Implement Recurring Schedules
- [ ] Handle day_of_week patterns (Mon-Fri, Weekends)
- [ ] Handle specific_date overrides (holidays)
- [ ] Implement getActiveWindowForTime($userId, $deviceId, $datetime)
- [ ] Cache schedule lookups for performance

**Verification:**
- [ ] Can create/edit/delete schedules via API
- [ ] Time windows save correctly
- [ ] Templates clone properly

---

## 🔧 TASK 11.3: Calendar UI Component

**Time Estimate:** 4 hours

### 11.3.1 Create Calendar Layout
- [ ] Create /dashboard/parental-calendar.php
- [ ] Full month view with day cells
- [ ] Previous/Next month navigation
- [ ] Color-coded days by access level
- [ ] Click day to edit schedule

### 11.3.2 Implement Day Cell Rendering
- [ ] Green = Full access
- [ ] Yellow = Restricted
- [ ] Red = Blocked
- [ ] Blue = Custom schedule
- [ ] Show mini-icons for active rules

### 11.3.3 Create Day Editor Modal
- [ ] Show selected date
- [ ] List existing time windows
- [ ] Add new window form
- [ ] Delete window buttons
- [ ] Quick preset buttons

### 11.3.4 Implement Time Window Visual
- [ ] Timeline bar (like Google Calendar)
- [ ] Drag handles to resize windows
- [ ] Different colors per access_type
- [ ] Tooltip showing details

### 11.3.5 Add Recurring Options
- [ ] "This day only" radio
- [ ] "Every [Day]" radio
- [ ] "All weekdays" radio
- [ ] "All weekends" radio
- [ ] "Custom days" multi-select

**Verification:**
- [ ] Calendar displays correctly
- [ ] Can click and edit days
- [ ] Time windows visual works
- [ ] Changes save to database

---

## 🔧 TASK 11.4: Device-Specific Rules

**Time Estimate:** 2 hours

### 11.4.1 Create Device Selector UI
- [ ] Show list of user's devices
- [ ] Device icons and names
- [ ] "Edit Rules" button per device
- [ ] "Apply to All" option

### 11.4.2 Create Per-Device Schedule Assignment
- [ ] Device → Schedule dropdown
- [ ] "Use default schedule" option
- [ ] "Custom schedule" option
- [ ] Create device_rules entry

### 11.4.3 Implement Device Groups
- [ ] Create device_groups table
- [ ] Allow grouping devices ("Kids Devices")
- [ ] Apply rules to entire group
- [ ] Group management UI

### 11.4.4 Show Device Status Dashboard
- [ ] Current access status per device
- [ ] Time until next change
- [ ] Active restrictions
- [ ] Quick override buttons

**Verification:**
- [ ] Can assign schedules to specific devices
- [ ] Device groups work
- [ ] Status shows correctly

---

## 🔧 TASK 11.5: Gaming Server Controls

**Time Estimate:** 3 hours

### 11.5.1 Create Gaming Detection
- [ ] Define gaming ports list:
  - Xbox Live: 3074, 3075
  - PlayStation Network: 3478-3480
  - Steam: 27000-27050
  - Epic Games: 5795-5847
- [ ] Define gaming domains list
- [ ] Create isGamingTraffic($domain, $port) function

### 11.5.2 Create Gaming Controls UI
- [ ] Create /dashboard/gaming-controls.php
- [ ] Main toggle: Gaming ON/OFF
- [ ] Per-device gaming toggles
- [ ] Quick action buttons:
  - "Block Gaming for 1 Hour"
  - "Block Until Bedtime"
  - "Allow Extra Hour"

### 11.5.3 Implement Gaming Enforcement
- [ ] Check gaming_restrictions table
- [ ] Block gaming ports when disabled
- [ ] Allow other traffic (streaming, etc)
- [ ] Update enforcement at VPN server

### 11.5.4 Create Override System
- [ ] "Emergency Gaming Block" button
- [ ] "+1 Hour" extension
- [ ] "Homework Mode" (whitelist only)
- [ ] Track override history

### 11.5.5 Show Active Gaming Sessions
- [ ] Detect active Xbox/PS connections
- [ ] Show device, duration
- [ ] "End Session" button (disconnect)

**Verification:**
- [ ] Gaming toggle works
- [ ] Gaming blocked when disabled
- [ ] Other traffic still works
- [ ] Override buttons function

---

## 🔧 TASK 11.6: Whitelist/Blacklist Management

**Time Estimate:** 2 hours

### 11.6.1 Create Whitelist UI
- [ ] Create whitelist section in parental dashboard
- [ ] List current whitelist entries
- [ ] Add domain form
- [ ] Remove domain button
- [ ] Import from presets (educational sites)

### 11.6.2 Create Blacklist UI
- [ ] List current blacklist entries
- [ ] Add domain form
- [ ] Remove domain button
- [ ] Import from categories

### 11.6.3 Create Temporary Blocks UI
- [ ] List active temporary blocks
- [ ] Show time remaining
- [ ] "Unblock Now" button
- [ ] "Extend" button
- [ ] Add new temp block form:
  - Domain input
  - Duration dropdown (1 hour, until bedtime, until tomorrow, 1 week)

### 11.6.4 Implement Enforcement Priority
- [ ] Priority order:
  1. Blacklist (always block)
  2. Temporary blocks (check expiry)
  3. Whitelist (always allow)
  4. Schedule rules
  5. Category filters
- [ ] Create checkDomainAccess($userId, $domain) function

### 11.6.5 Add Suggested Sites
- [ ] Pre-populate educational whitelist suggestions
- [ ] Pre-populate social media blacklist suggestions
- [ ] One-click add from suggestions

**Verification:**
- [ ] Whitelist entries always allowed
- [ ] Blacklist entries always blocked
- [ ] Temporary blocks expire correctly
- [ ] Priority order enforced

---

## 🔧 TASK 11.7: Quick Actions Panel

**Time Estimate:** 1.5 hours

### 11.7.1 Create Quick Actions UI
- [ ] Add to main parental dashboard
- [ ] Large, touch-friendly buttons
- [ ] Icons for each action
- [ ] Confirmation dialogs

### 11.7.2 Implement Quick Actions
- [ ] "🎮 Block Gaming Now" - instant toggle
- [ ] "📚 Homework Mode" - whitelist only
- [ ] "⏰ +1 Hour Free Time" - extend current window
- [ ] "🛑 Emergency Block" - block everything
- [ ] "✅ Restore Normal" - undo overrides

### 11.7.3 Add Action Logging
- [ ] Log all quick actions with timestamp
- [ ] Show in parent activity feed
- [ ] Include in weekly report

### 11.7.4 Mobile-Optimized View
- [ ] Quick actions accessible from phone
- [ ] Push notification option for requests
- [ ] Widget for instant access (future)

**Verification:**
- [ ] All quick actions work
- [ ] Effects immediate
- [ ] Actions logged
- [ ] Works on mobile

---

## 🔧 TASK 11.8: Statistics & Reporting

**Time Estimate:** 3 hours

### 11.8.1 Create Statistics Tracking
- [ ] Track total screen time per device
- [ ] Track time by category (gaming, streaming, social)
- [ ] Track blocked request counts
- [ ] Track schedule adherence

### 11.8.2 Create Statistics Dashboard
- [ ] Create /dashboard/parental-stats.php
- [ ] Daily screen time chart
- [ ] Weekly trends
- [ ] Category breakdown pie chart
- [ ] Most blocked sites list

### 11.8.3 Implement Weekly Report
- [ ] Create generateWeeklyReport($userId) function
- [ ] Include:
  - Total screen time
  - Gaming hours
  - Streaming hours
  - Educational site hours
  - Top blocked sites
  - Schedule adherence %
- [ ] Format as HTML email

### 11.8.4 Setup Report Automation
- [ ] Create cron job for weekly reports
- [ ] Send every Sunday at 8 AM
- [ ] Include all children
- [ ] Option to disable

### 11.8.5 Add Comparison Features
- [ ] This week vs last week
- [ ] Child vs child (if multiple)
- [ ] Progress toward goals

**Verification:**
- [ ] Statistics accurate
- [ ] Charts render correctly
- [ ] Weekly email sends
- [ ] Data matches actual usage

---

## 🔧 TASK 11.9: Backend Enforcement Integration

**Time Estimate:** 3 hours

### 11.9.1 Create Master Enforcement Function
- [ ] Create /includes/parental-enforcement.php
- [ ] Implement isAccessAllowed($userId, $deviceId, $domain, $port)
- [ ] Check all rules in priority order
- [ ] Return: allowed (bool), reason (string), access_type

### 11.9.2 Integrate with VPN Server
- [ ] Create API endpoint for server to check access
- [ ] Cache results for performance (5-minute TTL)
- [ ] Handle DNS blocking for blocked domains
- [ ] Handle port blocking for gaming

### 11.9.3 Create Real-Time Updates
- [ ] When parent changes setting → push to server
- [ ] Server applies new rules immediately
- [ ] No reconnection required for user
- [ ] Log all enforcement actions

### 11.9.4 Handle Edge Cases
- [ ] Device offline during rule change
- [ ] Multiple devices same rules
- [ ] Schedule change mid-window
- [ ] Timezone handling

**Verification:**
- [ ] Rules enforced in real-time
- [ ] No bypass possible
- [ ] Changes apply immediately
- [ ] Logging complete

---

## 🔧 TASK 11.10: UI Polish & Mobile

**Time Estimate:** 2 hours

### 11.10.1 Responsive Design
- [ ] Calendar works on mobile
- [ ] Time windows scrollable
- [ ] Quick actions large buttons
- [ ] Device selector mobile-friendly

### 11.10.2 Add Animations
- [ ] Smooth transitions
- [ ] Loading indicators
- [ ] Success feedback
- [ ] Calendar swipe gestures

### 11.10.3 Add Help & Tutorials
- [ ] First-time setup wizard
- [ ] Tooltips on complex features
- [ ] Help documentation page
- [ ] Video tutorials (optional)

### 11.10.4 Accessibility
- [ ] Keyboard navigation
- [ ] Screen reader support
- [ ] High contrast mode
- [ ] Large text support

**Verification:**
- [ ] Works on mobile devices
- [ ] Smooth user experience
- [ ] Help accessible
- [ ] WCAG 2.1 AA compliant

---

## ✅ PART 11 COMPLETION CHECKLIST

### Database
- [ ] All 6 tables created
- [ ] Indexes added
- [ ] Default templates inserted
- [ ] Migration tested

### Backend APIs
- [ ] Schedule CRUD API working
- [ ] Time windows API working
- [ ] Whitelist/blacklist API working
- [ ] Gaming controls API working
- [ ] Statistics API working

### User Interface
- [ ] Calendar component complete
- [ ] Time window editor working
- [ ] Gaming controls panel complete
- [ ] Whitelist/blacklist manager complete
- [ ] Quick actions panel complete
- [ ] Statistics dashboard complete
- [ ] Mobile responsive

### Enforcement
- [ ] Rules enforced at VPN level
- [ ] Real-time updates working
- [ ] Priority order correct
- [ ] Gaming blocked when disabled

### Automation
- [ ] Weekly report email working
- [ ] Statistics tracking accurate
- [ ] Cron jobs configured

---

## 🧪 TESTING CHECKLIST

### Schedule Testing
- [ ] Create schedule with multiple windows
- [ ] Test day-of-week patterns
- [ ] Test specific date overrides
- [ ] Test overlapping window prevention

### Gaming Testing
- [ ] Block gaming, verify Xbox disconnects
- [ ] Allow gaming, verify reconnects
- [ ] Test "+1 Hour" extension
- [ ] Test "Homework Mode"

### Access Testing
- [ ] Whitelist domain, verify always allowed
- [ ] Blacklist domain, verify always blocked
- [ ] Temp block, verify expires
- [ ] Test priority order

### Time Window Testing
- [ ] Verify access changes at window boundaries
- [ ] Test homework_only mode
- [ ] Test streaming_only mode
- [ ] Test full vs blocked

### Report Testing
- [ ] Generate weekly report
- [ ] Verify statistics accurate
- [ ] Test email delivery
- [ ] Check charts render

---

## 📝 DOCUMENTATION

After completing PART 11:
- [ ] Update BUILD_PROGRESS.md
- [ ] Update chat_log.txt
- [ ] Create PARENTAL_CONTROLS_GUIDE.md for users
- [ ] Update admin documentation
- [ ] Update MAPPING.md

---

## 📊 SUCCESS METRICS

After launch, track:
- [ ] Family plan signups (+30% target)
- [ ] Feature usage rate (70% of families)
- [ ] Support tickets (<2% of users)
- [ ] User satisfaction ratings

**Business Impact:**
- Key differentiator vs other VPNs
- Higher perceived value
- Lower churn (sticky feature)
- Press coverage potential

---

## ⏭️ NEXT STEPS

After PART 11 complete:
- All 22 blueprint sections covered! ✅
- Run comprehensive testing
- Launch marketing campaign
- Monitor and iterate

---

**PART 11 STATUS:** ⬜ NOT STARTED  
**Last Updated:** January 16, 2026

