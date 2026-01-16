# SECTION 22: ADVANCED PARENTAL CONTROLS

**Created:** January 17, 2026  
**Status:** Specification Complete - Ready to Build  
**Priority:** HIGH - Family Feature  
**Complexity:** HIGH - Complex Scheduling System  

---

## 🎯 OVERVIEW

Advanced parental control system with calendar-based scheduling, device-specific rules, gaming server controls, and whitelist/blacklist management.

**Current Status:** Basic parental controls implemented (category filters, domain blocking, blocked log)

**This Section Adds:**
- Monthly calendar scheduling interface
- Time window management (multiple per day)
- Device-specific rules
- Gaming server toggles
- Whitelist/blacklist with temporary blocks
- Recurring schedule templates

---

## 📋 FEATURES BREAKDOWN

### **Feature 1: Calendar Scheduling System** 📅

**Visual Monthly Calendar:**
- Full month view with clickable days
- Color-coded by access level (full/restricted/blocked)
- Quick day-type selection (school day, weekend, holiday)
- Drag-to-select multiple days

**Time Windows:**
- Multiple windows per day: 3-4pm, 5-6pm, 7-8pm
- Visual timeline (like Google Calendar)
- Drag handles to resize windows
- Quick presets: "Homework Time", "Free Time", "Bedtime"

**Recurring Schedules:**
- Daily: Same schedule every day
- Weekly: Monday-Friday vs. Weekend
- Custom: Select specific days (MWF, T/Th, etc.)
- School calendar integration (optional)

---

### **Feature 2: Device-Specific Rules** 📱

**Per-Device Control:**
```
Johnny's iPad:
  - Weekdays: 3-4pm (homework), 6-8pm (free time)
  - Weekends: 10am-9pm
  - Gaming: Blocked on weekdays
  
Suzy's Laptop:
  - Weekdays: 4-6pm, 7-9pm
  - Weekends: 9am-10pm
  - Social media: Blocked until 6pm
```

**Device Groups:**
- "Kids Devices" (apply rules to all)
- "Homework Devices" (laptops/tablets)
- "Entertainment Devices" (gaming consoles, TVs)

---

### **Feature 3: Gaming Server Controls** 🎮

**Granular Gaming Access:**
- Toggle gaming servers on/off instantly
- Block gaming but allow:
  - ✅ Educational sites (homework)
  - ✅ Streaming (Netflix, Disney+)
  - ✅ Email/communication
  - ❌ Gaming servers (Xbox Live, PlayStation Network)

**Parent Override Button:**
- "Emergency Gaming Block" - instant toggle
- "Extra Hour" - extend gaming by 1 hour
- "Homework Mode" - blocks everything except whitelist

**Implementation:**
```
Gaming detection:
- Xbox Live ports (3074, 3075)
- PlayStation Network (80, 443, 3478-3480)
- Steam (27000-27050)
- Epic Games (80, 443, 5795-5847)

Block at VPN server level when gaming=disabled
```

---

### **Feature 4: Whitelist/Blacklist Management** ✅❌

**Three Lists:**

**1. Whitelist (Always Allow):**
- Educational sites (khan academy, google classroom)
- School websites
- Parent-approved sites
- Never blocked even during restrictions

**2. Blacklist (Always Block):**
- Specific sites parent wants permanently blocked
- Overrides all other rules
- Cannot be bypassed by time windows

**3. Temporary Block:**
- Block site for 1 hour
- Block until bedtime (9pm)
- Block until tomorrow
- Block for 1 week (punishment mode)
- Auto-expires

**UI:**
```
Add to Whitelist: [google.com/classroom] [Add]
Add to Blacklist: [tiktok.com] [Add]
Temporary Block:  [youtube.com] [Duration: 2 hours] [Block]
```

---

### **Feature 5: Smart Templates** 🎨

**Pre-Built Schedules:**

**School Day Template:**
- Before school: Blocked (6-8am)
- School hours: Blocked (8am-3pm)
- Homework time: Whitelist only (3-4pm)
- Free time: Limited access (4-6pm)
- Dinner: Blocked (6-7pm)
- Evening: Limited access (7-8pm)
- Bedtime: Blocked (8pm+)

**Weekend Template:**
- Morning: Free (9am-12pm)
- Afternoon: Free (12-6pm)
- Evening: Limited (6-9pm)
- Bedtime: Blocked (9pm+)

**Holiday Template:**
- Extended hours
- More gaming allowed
- Fewer restrictions

**Custom Templates:**
- Parents can create and save their own
- Name templates ("Summer Schedule", "Grounded")
- Apply to multiple children

---

## 🎨 UI/UX DESIGN

### **Main Parental Controls Dashboard**

```
┌─────────────────────────────────────────────────┐
│  🛡️ Parental Controls                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│  │ Johnny's │ │  Suzy's  │ │  Billy's │        │
│  │  Devices │ │  Devices │ │  Devices │        │
│  │  [Edit]  │ │  [Edit]  │ │  [Edit]  │        │
│  └──────────┘ └──────────┘ └──────────┘        │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  📅 January 2026                                 │
│  ┌───┬───┬───┬───┬───┬───┬───┐                │
│  │ S │ M │ T │ W │ T │ F │ S │                │
│  ├───┼───┼───┼───┼───┼───┼───┤                │
│  │   │ 1 │ 2 │ 3 │ 4 │ 5 │ 6 │                │
│  │ 7 │ 8 │ 9 │10 │11 │12 │13 │                │
│  └───┴───┴───┴───┴───┴───┴───┘                │
│                                                  │
│  [School Day] [Weekend] [Holiday] [Custom]      │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  ⏰ Today's Schedule (January 17)                │
│  ╔═══════════════════════════════════════════╗  │
│  ║ 6am  7am  8am  9am  10am 11am 12pm 1pm    ║  │
│  ║ [────────BLOCKED─────] [──HOMEWORK──]     ║  │
│  ║                                            ║  │
│  ║ 2pm  3pm  4pm  5pm  6pm  7pm  8pm  9pm    ║  │
│  ║ [─FREE─] [BLK] [─────FREE─────] [BLOCKED] ║  │
│  ╚═══════════════════════════════════════════╝  │
│                                                  │
│  🎮 Gaming: [OFF] [Toggle]                      │
│  📱 Social Media: [RESTRICTED] [Manage]         │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  🚨 Quick Actions                                │
│  [🎮 Block Gaming Now] [📚 Homework Mode]       │
│  [⏰ +1 Hour Free Time] [🛑 Emergency Block]    │
└─────────────────────────────────────────────────┘
```

---

### **Time Window Editor**

```
┌─────────────────────────────────────────────────┐
│  ⏰ Edit Schedule: Monday, January 17            │
│                                                  │
│  Time Window 1:                                  │
│  ├─ Start: [3:00 PM] ▼                          │
│  ├─ End:   [4:00 PM] ▼                          │
│  ├─ Type:  [Homework Only] ▼                    │
│  └─ [Delete Window]                             │
│                                                  │
│  Time Window 2:                                  │
│  ├─ Start: [5:00 PM] ▼                          │
│  ├─ End:   [6:00 PM] ▼                          │
│  ├─ Type:  [Free Time] ▼                        │
│  └─ [Delete Window]                             │
│                                                  │
│  [+ Add Another Window]                         │
│                                                  │
│  Apply to:                                       │
│  ☑ This day only                                │
│  ☐ Every Monday                                 │
│  ☐ All weekdays (Mon-Fri)                       │
│  ☐ Custom days: [Select]                        │
│                                                  │
│  [Cancel] [Save Schedule]                       │
└─────────────────────────────────────────────────┘
```

---

### **Gaming Controls Panel**

```
┌─────────────────────────────────────────────────┐
│  🎮 Gaming Server Controls                       │
│                                                  │
│  Gaming Access: [ENABLED] ← Toggle switch       │
│                                                  │
│  When gaming is DISABLED:                       │
│  ✅ Allow: TV streaming (Netflix, Disney+)      │
│  ✅ Allow: Educational sites                    │
│  ✅ Allow: Email and messaging                  │
│  ❌ Block: Xbox Live                            │
│  ❌ Block: PlayStation Network                  │
│  ❌ Block: Steam                                │
│  ❌ Block: Epic Games                           │
│                                                  │
│  Current Status:                                 │
│  Xbox (Living Room): [ACTIVE] [Block Now]       │
│  PS5 (Johnny's Room): [IDLE]                    │
│                                                  │
│  Quick Actions:                                  │
│  [Block Gaming for 1 Hour]                      │
│  [Block Until Bedtime (9pm)]                    │
│  [Allow Extra Hour]                             │
└─────────────────────────────────────────────────┘
```

---

### **Whitelist/Blacklist Manager**

```
┌─────────────────────────────────────────────────┐
│  ✅ Whitelist (Always Allowed)                   │
│  ┌───────────────────────────────────────────┐  │
│  │ khanacademy.org          [Remove]        │  │
│  │ classroom.google.com     [Remove]        │  │
│  │ school.edu               [Remove]        │  │
│  └───────────────────────────────────────────┘  │
│  Add site: [____________] [Add to Whitelist]    │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  ❌ Blacklist (Always Blocked)                   │
│  ┌───────────────────────────────────────────┐  │
│  │ tiktok.com               [Remove]        │  │
│  │ instagram.com            [Remove]        │  │
│  │ snapchat.com             [Remove]        │  │
│  └───────────────────────────────────────────┘  │
│  Add site: [____________] [Add to Blacklist]    │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│  ⏱️ Temporary Blocks                             │
│  ┌───────────────────────────────────────────┐  │
│  │ youtube.com                               │  │
│  │ Blocked for: 1 hour 23 mins remaining    │  │
│  │ [Unblock Now] [Extend]                    │  │
│  └───────────────────────────────────────────┘  │
│                                                  │
│  Add temporary block:                            │
│  Site: [____________]                            │
│  Duration: [1 Hour] ▼ [Block]                   │
└─────────────────────────────────────────────────┘
```

---

## 🗄️ DATABASE SCHEMA

### **Existing Tables (Basic Controls):**
```sql
parental_rules (user_id, enabled)
blocked_domains (user_id, domain, added_at)
blocked_categories (user_id, category)
blocked_requests (user_id, domain, category, device_name, blocked_at)
```

### **New Tables (Advanced Controls):**

```sql
-- Schedule templates
parental_schedules (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    device_id INTEGER,  -- NULL = applies to all devices
    schedule_name TEXT,  -- "School Day", "Weekend", etc.
    is_template BOOLEAN, -- TRUE if reusable template
    created_at DATETIME
)

-- Time windows for schedules
schedule_windows (
    id INTEGER PRIMARY KEY,
    schedule_id INTEGER,
    day_of_week INTEGER, -- 0=Sunday, 1=Monday, etc. NULL=specific date
    specific_date DATE,  -- NULL if using day_of_week
    start_time TIME,     -- "15:00" (3pm)
    end_time TIME,       -- "16:00" (4pm)
    access_type TEXT,    -- "full", "homework_only", "streaming_only", "blocked"
    FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id)
)

-- Whitelist (always allow)
parental_whitelist (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    domain TEXT,
    notes TEXT,         -- "School website", "Khan Academy"
    added_at DATETIME
)

-- Temporary blocks
temporary_blocks (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    domain TEXT,
    blocked_until DATETIME,
    reason TEXT,        -- "Punishment", "Focus time"
    added_at DATETIME
)

-- Gaming controls
gaming_restrictions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    device_id INTEGER,
    gaming_enabled BOOLEAN,
    last_toggled_at DATETIME,
    toggled_by TEXT     -- "parent" or "schedule"
)

-- Device-specific rules
device_rules (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    device_id INTEGER,
    schedule_id INTEGER, -- Links to parental_schedules
    override_enabled BOOLEAN,
    notes TEXT,
    FOREIGN KEY (schedule_id) REFERENCES parental_schedules(id)
)
```

---

## 🔧 TECHNICAL IMPLEMENTATION

### **Calendar Scheduling Logic:**

```javascript
// Client-side calendar rendering
function renderCalendar(month, year, schedules) {
    const cal = document.getElementById('calendar');
    const days = getDaysInMonth(month, year);
    
    days.forEach(day => {
        const schedule = getScheduleForDay(day, schedules);
        const cell = createDayCell(day, schedule);
        cell.addEventListener('click', () => editDaySchedule(day));
        cal.appendChild(cell);
    });
}

// Time window editor
function createTimeWindow(start, end, type) {
    return {
        start_time: start,  // "15:00"
        end_time: end,      // "16:00"
        access_type: type   // "homework_only"
    };
}
```

### **Server-Side Enforcement:**

```php
// Check if current time is within allowed window
function isAccessAllowed($userId, $deviceId, $currentTime) {
    // 1. Check if parental controls enabled
    // 2. Check gaming restrictions
    // 3. Check blacklist (always blocked)
    // 4. Check whitelist (always allowed)
    // 5. Check temporary blocks
    // 6. Check schedule windows for current time
    // 7. Return true/false + access_type
}

// VPN server integration
function enforceParentalControls($userId, $deviceId, $requestedDomain) {
    $access = isAccessAllowed($userId, $deviceId, time());
    
    if (!$access['allowed']) {
        // Log blocked request
        logBlockedRequest($userId, $deviceId, $requestedDomain, $access['reason']);
        // Return DNS block response
        return blockDNS($requestedDomain);
    }
    
    // Check domain-specific rules
    if (isBlacklisted($requestedDomain)) {
        return blockDNS($requestedDomain);
    }
    
    if ($access['type'] === 'homework_only' && !isWhitelisted($requestedDomain)) {
        return blockDNS($requestedDomain);
    }
    
    // Allow access
    return allowDNS($requestedDomain);
}
```

### **Gaming Server Detection:**

```php
// Detect gaming traffic by ports and domains
$gamingPorts = [
    3074, 3075,           // Xbox Live
    3478, 3479, 3480,     // PlayStation Network
    27000-27050,          // Steam
    5795-5847             // Epic Games
];

$gamingDomains = [
    'xboxlive.com',
    'playstation.com',
    'steampowered.com',
    'epicgames.com'
];

function isGamingTraffic($domain, $port) {
    global $gamingPorts, $gamingDomains;
    
    // Check domain
    foreach ($gamingDomains as $gamingDomain) {
        if (strpos($domain, $gamingDomain) !== false) {
            return true;
        }
    }
    
    // Check port
    if (in_array($port, $gamingPorts)) {
        return true;
    }
    
    return false;
}
```

---

## 📱 RESPONSIVE DESIGN

**Mobile View:**
- Swipeable calendar (left/right for months)
- Collapsible time windows
- Quick toggle buttons for common actions
- Bottom sheet for editing schedules

**Desktop View:**
- Full calendar + sidebar with time windows
- Drag-and-drop time window creation
- Multi-device view (see all kids at once)

---

## 🎯 USER WORKFLOWS

### **Workflow 1: Set Up School Day Schedule**

```
1. Parent opens Parental Controls
2. Selects "Johnny's Devices"
3. Clicks "Use Template" → "School Day"
4. Adjusts times:
   - Homework: 3-4pm → 3:30-4:30pm
   - Free time: 4-6pm → 4:30-6:30pm
5. Clicks "Apply to Weekdays"
6. Saves
7. Done! ✅
```

### **Workflow 2: Block Gaming for Punishment**

```
1. Parent opens Gaming Controls
2. Sees Xbox is active
3. Clicks "Block Gaming Now"
4. Selects duration: "Until Tomorrow"
5. Gaming blocked instantly
6. Kid's Xbox disconnects from server
7. Done! ✅
```

### **Workflow 3: Allow Extra Hour**

```
1. Kid asks for more time
2. Parent opens app on phone
3. Clicks "Quick Actions" → "+1 Hour"
4. Current window extended by 1 hour
5. Kid continues playing
6. Done! ✅ (Takes 5 seconds)
```

---

## 📊 STATISTICS & REPORTING

**Parent Dashboard Shows:**
- Total screen time this week (per child)
- Most visited sites
- Blocked requests (top 10)
- Gaming hours (daily/weekly)
- Schedule adherence (% of time within rules)

**Weekly Report Email:**
```
Johnny's Screen Time Report - Week of Jan 15-21

Total Screen Time: 18 hours 42 minutes (-12% vs last week)
Gaming: 6 hours 15 minutes
Streaming: 8 hours 30 minutes
Homework Sites: 3 hours 57 minutes

Most Blocked Sites:
1. tiktok.com (47 attempts)
2. youtube.com (32 attempts)
3. instagram.com (18 attempts)

Schedule Adherence: 94% ✅
```

---

## 🚀 DEVELOPMENT PLAN

### **Phase 1: Database & Backend (Week 1)**
- [ ] Create 6 new database tables
- [ ] Build schedule API endpoints
- [ ] Implement time window logic
- [ ] Add whitelist/blacklist management
- [ ] Build enforcement engine

### **Phase 2: Calendar UI (Week 2)**
- [ ] Build monthly calendar component
- [ ] Time window editor (drag-and-drop)
- [ ] Template selector
- [ ] Device selector
- [ ] Mobile-responsive design

### **Phase 3: Gaming Controls (Week 3)**
- [ ] Gaming server detection
- [ ] Quick toggle buttons
- [ ] Parent override system
- [ ] Device status monitoring
- [ ] Real-time notifications

### **Phase 4: Advanced Features (Week 4)**
- [ ] Recurring schedules
- [ ] Temporary blocks with countdown
- [ ] Statistics dashboard
- [ ] Weekly email reports
- [ ] Multi-device view

### **Phase 5: Polish & Testing (Week 5)**
- [ ] Comprehensive testing
- [ ] Mobile app integration
- [ ] User onboarding flow
- [ ] Help documentation
- [ ] Release!

---

## 💰 BUSINESS VALUE

**Target Market:**
- Families with children (ages 6-16)
- 47% of VPN users have children
- Parents willing to pay premium for parental controls

**Pricing Strategy:**
- Include in Family plan ($14.97) as key feature
- Advertise as "Most Advanced Parental Controls in Any VPN"
- Marketing: "Screen Time Management + VPN Protection"

**Competitive Advantage:**
- No other VPN offers calendar-based scheduling
- Gaming server controls = unique
- Device-specific rules = advanced
- Temporary blocks = flexible

**Expected Impact:**
- +30% Family plan signups
- Lower churn (sticky feature)
- Higher perceived value
- Press coverage (innovative feature)

---

## ✅ SUCCESS METRICS

**Technical:**
- Schedule enforcement accuracy: >99%
- Real-time toggle response: <2 seconds
- Mobile app sync: <5 seconds
- Zero false blocks on whitelist

**Business:**
- Family plan conversion: +30%
- Feature usage: 70% of families
- Support tickets: <2% of users
- User satisfaction: 4.5+ stars

---

## 🎉 SUMMARY

Advanced Parental Controls transforms TrueVault from "just a VPN" into a **comprehensive family internet safety solution**.

**Key Features:**
✅ Calendar-based scheduling (no other VPN has this)  
✅ Gaming server controls (unique)  
✅ Device-specific rules (advanced)  
✅ Whitelist/blacklist with temporary blocks  
✅ Parent quick actions (toggle gaming in 2 seconds)  
✅ Statistics and weekly reports  
✅ Template system for easy setup  

**Development Time:** 5 weeks  
**Target Market:** Families (47% of VPN market)  
**Competitive Advantage:** Most advanced parental controls in VPN industry  

**Status:** ✅ Fully specified, ready to build after PART 7/8 automation complete

---

**END OF SPECIFICATION**
