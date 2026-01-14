# PARENTAL CONTROLS SYSTEM - COMPLETE SPECIFICATION

**Version:** 1.0  
**Date:** January 14, 2026  
**For:** Family Plan Subscribers  

---

## 🎯 SYSTEM OVERVIEW

### Purpose
Give parents complete control over children's internet usage:
- **Content Filtering:** Block inappropriate websites (porn, gambling, violence)
- **Screen Time Management:** Set daily time limits with calendar scheduling
- **Multiple Time Windows:** School time, homework time, chores time, free time
- **Device-Level Control:** Different rules for each child's devices
- **Activity Monitoring:** See what children are accessing

---

## 🛡️ CONTENT FILTERING

### Multi-Layer Filtering System

**Layer 1: DNS-Based Filtering** (Instant, works everywhere)
```
User Request: pornsite.com
       ↓
TrueVault DNS Filter (checks blocklist)
       ↓
BLOCKED: Returns 0.0.0.0
       ↓
User sees: "This site is blocked by parental controls"
```

**Layer 2: Deep Packet Inspection** (Advanced, catches bypasses)
- Analyzes actual traffic content
- Detects VPN/proxy attempts to bypass filter
- Blocks HTTPS sites by domain (SNI inspection)
- Catches adult content even with obscured URLs

**Layer 3: AI Content Classification** (Smart, learns over time)
- Analyzes page content in real-time
- Classifies new/unknown sites automatically
- Catches adult content on legitimate sites (e.g., Twitter NSFW)
- Updates blocklist automatically

### Content Categories (Parent Can Enable/Disable Each)

```
┌─────────────────────────────────────────────────┐
│ Content Filter Settings                         │
├─────────────────────────────────────────────────┤
│                                                 │
│ ☑ Adult Content (Pornography)                  │
│   Blocks: Adult sites, explicit content        │
│                                                 │
│ ☑ Gambling & Betting                           │
│   Blocks: Online casinos, betting sites        │
│                                                 │
│ ☑ Violence & Gore                              │
│   Blocks: Extreme violence, graphic content    │
│                                                 │
│ ☑ Hate Speech & Extremism                      │
│   Blocks: Hate groups, extremist content       │
│                                                 │
│ ☑ Drugs & Alcohol                              │
│   Blocks: Drug marketplaces, alcohol sales     │
│                                                 │
│ ☐ Social Media (Optional)                      │
│   Blocks: Facebook, Instagram, TikTok, etc.    │
│                                                 │
│ ☐ Gaming (Optional)                            │
│   Blocks: Online games, gaming platforms       │
│                                                 │
│ ☐ Streaming (Optional)                         │
│   Blocks: YouTube, Netflix, Twitch, etc.       │
│                                                 │
│ ☑ Malware & Phishing                           │
│   Blocks: Known malicious sites (Always on)    │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Age Presets
```
┌─────────────────────────────────────────────────┐
│ Quick Setup: Choose Age Group                   │
├─────────────────────────────────────────────────┤
│                                                 │
│ ⚪ Child (6-12 years)                           │
│    ✓ Strict filtering                          │
│    ✓ Social media blocked                      │
│    ✓ YouTube Kids only                         │
│    ✓ Gaming limited                            │
│                                                 │
│ ● Teen (13-17 years)                            │
│    ✓ Adult content blocked                     │
│    ✓ Social media allowed with monitoring      │
│    ✓ YouTube allowed                           │
│    ✓ Gaming allowed with time limits           │
│                                                 │
│ ⚪ Young Adult (18+)                            │
│    ✓ Minimal filtering (malware only)          │
│    ✓ Everything allowed                        │
│    ✓ Optional monitoring                       │
│                                                 │
│ [Apply Preset]  [Custom Settings]              │
└─────────────────────────────────────────────────┘
```

### Whitelist & Blacklist
```
┌─────────────────────────────────────────────────┐
│ Custom Lists                                    │
├─────────────────────────────────────────────────┤
│                                                 │
│ Always Allow (Whitelist):                      │
│ • school.edu                                    │
│ • khanacademy.org                              │
│ • wikipedia.org                                │
│ [+ Add Site]                                   │
│                                                 │
│ Always Block (Blacklist):                      │
│ • specificgame.com                             │
│ • distractingsite.com                          │
│ [+ Add Site]                                   │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## ⏰ SCREEN TIME MANAGEMENT

### Calendar-Based Scheduling

**Daily Schedule Interface:**
```
┌─────────────────────────────────────────────────────────────┐
│ Screen Time Schedule - Child's Laptop                       │
│ Device: Sarah's MacBook (13" 2023)                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Monday Schedule:                                            │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━│
│                                                             │
│ 12am  2am  4am  6am  8am  10am 12pm 2pm  4pm  6pm  8pm  10pm│
│ ├────┼────┼────┼────┼────┼────┼────┼────┼────┼────┼────┼───│
│ │████│████│████│████│░░░░│░░░░│░░░░│████│░░░░│████│░░░░│███│
│ └────┴────┴────┴────┴────┴────┴────┴────┴────┴────┴────┴───│
│                                                             │
│ Legend:                                                     │
│ ████ Blocked  ░░░░ Allowed  ▓▓▓▓ Limited                   │
│                                                             │
│ Time Windows:                                               │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ 6:00 AM - 8:00 AM  | ALLOWED  | Get ready for school│   │
│ │ 8:00 AM - 3:00 PM  | BLOCKED  | School hours        │   │
│ │ 3:00 PM - 4:00 PM  | ALLOWED  | After school break  │   │
│ │ 4:00 PM - 6:00 PM  | BLOCKED  | Homework & chores   │   │
│ │ 6:00 PM - 8:00 PM  | ALLOWED  | Free time (2 hours) │   │
│ │ 8:00 PM - 6:00 AM  | BLOCKED  | Sleep time          │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                             │
│ [+ Add Time Window]  [Copy to Other Days]  [Save]          │
└─────────────────────────────────────────────────────────────┘
```

### Weekly Calendar View
```
┌─────────────────────────────────────────────────────────────┐
│ Weekly Schedule Overview                                     │
├─────────────────────────────────────────────────────────────┤
│           MON    TUE    WED    THU    FRI    SAT    SUN     │
│ 6-8am     ✓      ✓      ✓      ✓      ✓      ✓      ✓      │
│ 8-3pm     ✗      ✗      ✗      ✗      ✗      ✓      ✓      │
│ 3-4pm     ✓      ✓      ✓      ✓      ✓      ✓      ✓      │
│ 4-6pm     ✗      ✗      ✗      ✗      ✗      ✓      ✓      │
│ 6-8pm     ✓      ✓      ✓      ✓      ✓      ✓      ✓      │
│ 8pm-6am   ✗      ✗      ✗      ✗      ✗      ✗      ✗      │
│                                                             │
│ Total Screen Time:                                          │
│ Mon-Fri: 4 hours/day  |  Sat-Sun: 8 hours/day             │
└─────────────────────────────────────────────────────────────┘
```

### Daily Time Budget
```
┌─────────────────────────────────────────────────────────────┐
│ Daily Time Limits                                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ School Days (Mon-Fri):                                      │
│ Total allowed: 4 hours                                      │
│ Used today: 2h 15m  [████████░░░░░░░░░░░░] 56%            │
│ Remaining: 1h 45m                                           │
│                                                             │
│ Weekends (Sat-Sun):                                         │
│ Total allowed: 8 hours                                      │
│ Used today: 3h 42m  [████████░░░░░░░░░░░░] 46%            │
│ Remaining: 4h 18m                                           │
│                                                             │
│ Category Limits:                                            │
│ • Gaming: 1h/day (Used: 45m)                               │
│ • Social Media: 1h/day (Used: 30m)                         │
│ • YouTube: 2h/day (Used: 1h 15m)                           │
│ • Educational: Unlimited ✓                                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Smart Scheduling Features

**1. Chores Reminder Integration**
```
┌─────────────────────────────────────────────────┐
│ Schedule Break: Chores Time                     │
├─────────────────────────────────────────────────┤
│                                                 │
│ Time: 4:00 PM - 6:00 PM (Daily)                │
│                                                 │
│ Action:                                         │
│ ● Block internet access completely             │
│ ⚪ Allow educational sites only                │
│                                                 │
│ Notification to child:                         │
│ "Time to do your chores! Internet access       │
│  will resume at 6:00 PM."                      │
│                                                 │
│ Chores checklist:                              │
│ ☐ Clean room                                   │
│ ☐ Do dishes                                    │
│ ☐ Homework                                     │
│                                                 │
│ [Save]  [Cancel]                               │
└─────────────────────────────────────────────────┘
```

**2. Bedtime Enforcement**
```
Bedtime Mode: 8:00 PM - 6:00 AM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Actions:
✓ Block all internet access
✓ Disable device after 15-minute warning
✓ Only emergency contacts allowed (parent's phone)

Warning Schedule:
• 7:45 PM: "15 minutes until bedtime"
• 7:55 PM: "5 minutes until bedtime"
• 8:00 PM: Internet blocked, device locks at 8:15 PM
```

**3. Homework Mode**
```
Homework Mode: 4:00 PM - 6:00 PM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Allowed:
✓ Educational sites (Khan Academy, Wikipedia, etc.)
✓ Google Docs/Drive (for homework)
✓ Email (school email only)

Blocked:
✗ Social media
✗ Gaming
✗ YouTube (except educational channels)
✗ Messaging apps

[Enable] [Disable] [Customize]
```

---

## 👨‍👩‍👧‍👦 FAMILY MANAGEMENT

### Family Dashboard
```
┌─────────────────────────────────────────────────────────────┐
│ Family Overview                                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 👨 Dad (Parent Account)                                     │
│    Devices: 2 (Laptop, Phone) | No restrictions            │
│                                                             │
│ 👩 Mom (Parent Account)                                     │
│    Devices: 2 (Laptop, Phone) | No restrictions            │
│                                                             │
│ 👧 Sarah (Age 14)                                           │
│    Devices: 3 (Laptop, Phone, Tablet)                      │
│    Screen Time Today: 2h 15m / 4h                          │
│    Status: ✓ Online | Last Activity: 5 min ago             │
│    [View Details] [Edit Rules]                             │
│                                                             │
│ 👦 Tommy (Age 10)                                           │
│    Devices: 2 (Tablet, Phone)                              │
│    Screen Time Today: 1h 30m / 3h                          │
│    Status: ✗ Blocked (Chores time) | Until: 6:00 PM       │
│    [View Details] [Edit Rules]                             │
│                                                             │
│ [+ Add Family Member]                                       │
└─────────────────────────────────────────────────────────────┘
```

### Per-Child Settings
```
┌─────────────────────────────────────────────────────────────┐
│ Sarah's Settings (Age 14)                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Content Filtering:                                          │
│ Preset: Teen (13-17)         [Change]                      │
│                                                             │
│ Screen Time:                                                │
│ School Days: 4 hours/day                                    │
│ Weekends: 8 hours/day                                       │
│ [Edit Schedule]                                             │
│                                                             │
│ Category Limits:                                            │
│ • Gaming: 1h/day                                           │
│ • Social Media: 1h/day                                     │
│ • YouTube: 2h/day                                          │
│ • Educational: Unlimited                                    │
│ [Edit Limits]                                              │
│                                                             │
│ Devices (3):                                                │
│ • MacBook 13" (sarah-macbook)                              │
│ • iPhone 14 (sarah-iphone)                                 │
│ • iPad Air (sarah-ipad)                                    │
│ [Manage Devices]                                            │
│                                                             │
│ Activity Monitoring:                                        │
│ ● Track browsing history                                   │
│ ● Get daily activity reports                               │
│ ● Alert on blocked attempts                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 ACTIVITY MONITORING & REPORTS

### Real-Time Activity Feed
```
┌─────────────────────────────────────────────────────────────┐
│ Sarah's Activity - Today                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 4:15 PM  ✓ Allowed  google.com (Search: "math homework")   │
│ 4:18 PM  ✓ Allowed  khanacademy.org (Watching video)       │
│ 4:25 PM  ✗ BLOCKED  instagram.com (Social media blocked)   │
│ 4:26 PM  ✗ BLOCKED  instagram.com (2nd attempt)            │
│ 4:30 PM  ✓ Allowed  docs.google.com (Working on document)  │
│ 5:15 PM  ✓ Allowed  youtube.com (Educational content)      │
│ 6:05 PM  ✓ Allowed  discord.com (Chatting with friends)    │
│ 6:45 PM  ✓ Allowed  roblox.com (Gaming - 45m used)         │
│                                                             │
│ Summary:                                                    │
│ • Total time: 2h 15m                                        │
│ • Educational: 1h 30m (homework, Khan Academy)             │
│ • Social: 30m (Discord)                                     │
│ • Gaming: 45m (Roblox)                                      │
│ • Blocked attempts: 2 (Instagram)                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Weekly Report (Email to Parents)
```
Subject: Sarah's Weekly Screen Time Report (Jan 8-14)

Hi Dad & Mom,

Here's Sarah's screen time summary for this week:

📊 TOTAL SCREEN TIME
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mon-Fri: 18h 30m (avg 3h 42m/day) ✓ Under limit
Sat-Sun: 14h 15m (avg 7h 8m/day)  ✓ Under limit

📱 TOP ACTIVITIES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. Educational: 12h 45m (39%)
   • Khan Academy: 5h 20m
   • Google Docs: 4h 30m
   • Wikipedia: 2h 55m

2. Gaming: 8h 30m (26%)
   • Roblox: 5h 15m
   • Minecraft: 3h 15m

3. Social Media: 6h 45m (21%)
   • Discord: 4h 30m
   • YouTube: 2h 15m

4. Other: 4h 35m (14%)

⚠️ BLOCKED ATTEMPTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
• Instagram: 8 attempts (Mon-Fri, during homework time)
• TikTok: 3 attempts (Tuesday, during chores time)

💡 INSIGHTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Good balance between educational and fun content
⚠ Attempting to access social media during homework time
✓ Respecting screen time limits well

[View Full Report] [Adjust Settings]
```

---

## 🚨 PARENT CONTROLS & OVERRIDES

### Emergency Override
```
┌─────────────────────────────────────────────────┐
│ Parent Override                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│ Temporarily bypass restrictions for:            │
│                                                 │
│ Device: Sarah's MacBook                         │
│ Duration: [15 min ▼]                           │
│ Reason: [School project needs YouTube__]       │
│                                                 │
│ Actions:                                        │
│ ☐ Disable content filtering                    │
│ ☑ Grant extra screen time                      │
│ ☐ Allow blocked categories                     │
│                                                 │
│ [Grant Override]  [Cancel]                     │
│                                                 │
│ Note: Override will be logged in activity feed │
└─────────────────────────────────────────────────┘
```

### Instant Pause
```
┌─────────────────────────────────────────────────┐
│ Pause All Devices                               │
├─────────────────────────────────────────────────┤
│                                                 │
│ Instantly block internet access on:             │
│                                                 │
│ ☑ Sarah's devices (3 devices)                  │
│ ☑ Tommy's devices (2 devices)                  │
│ ☐ All family devices                           │
│                                                 │
│ Reason:                                         │
│ ● Dinner time                                  │
│ ⚪ Family activity                             │
│ ⚪ Punishment                                   │
│ ⚪ Other: [___________]                        │
│                                                 │
│ Duration:                                       │
│ ● Until I unpause                              │
│ ⚪ For 30 minutes                               │
│ ⚪ For 1 hour                                   │
│                                                 │
│ [Pause Now]  [Cancel]                          │
└─────────────────────────────────────────────────┘
```

### Reward System
```
┌─────────────────────────────────────────────────┐
│ Bonus Screen Time                               │
├─────────────────────────────────────────────────┤
│                                                 │
│ Grant extra time for:                           │
│                                                 │
│ Child: Sarah                                    │
│ Amount: [+30 minutes]                          │
│ Reason: [Good grades on test_______]          │
│                                                 │
│ Add to:                                         │
│ ● Today only                                   │
│ ⚪ This week                                    │
│ ⚪ Permanent increase                           │
│                                                 │
│ [Grant Bonus Time]  [Cancel]                   │
└─────────────────────────────────────────────────┘
```

---

## 🔧 DATABASE SCHEMA

```sql
-- Family members
CREATE TABLE family_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL, -- Parent account
    name TEXT NOT NULL,
    age INTEGER,
    role TEXT, -- 'parent' or 'child'
    avatar_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Parental control profiles
CREATE TABLE parental_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    age_preset TEXT, -- 'child', 'teen', 'young_adult'
    content_filter_level TEXT DEFAULT 'strict', -- 'strict', 'moderate', 'minimal'
    
    -- Content categories
    block_adult_content BOOLEAN DEFAULT 1,
    block_gambling BOOLEAN DEFAULT 1,
    block_violence BOOLEAN DEFAULT 1,
    block_hate_speech BOOLEAN DEFAULT 1,
    block_drugs BOOLEAN DEFAULT 1,
    block_social_media BOOLEAN DEFAULT 0,
    block_gaming BOOLEAN DEFAULT 0,
    block_streaming BOOLEAN DEFAULT 0,
    
    -- Screen time limits
    daily_limit_weekday INTEGER DEFAULT 14400, -- seconds (4 hours)
    daily_limit_weekend INTEGER DEFAULT 28800, -- seconds (8 hours)
    
    -- Category limits
    gaming_daily_limit INTEGER DEFAULT 3600, -- 1 hour
    social_media_daily_limit INTEGER DEFAULT 3600, -- 1 hour
    streaming_daily_limit INTEGER DEFAULT 7200, -- 2 hours
    
    -- Monitoring
    track_browsing_history BOOLEAN DEFAULT 1,
    send_daily_reports BOOLEAN DEFAULT 1,
    alert_on_blocked_attempts BOOLEAN DEFAULT 1,
    
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id)
);

-- Device assignments
CREATE TABLE family_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    device_id INTEGER NOT NULL,
    device_nickname TEXT,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id),
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);

-- Screen time schedules
CREATE TABLE screen_time_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    day_of_week INTEGER, -- 0=Sunday, 1=Monday, ..., 6=Saturday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    action TEXT, -- 'allow', 'block', 'homework_mode'
    label TEXT, -- 'School time', 'Homework', 'Chores', 'Free time', etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id)
);

-- Whitelist/Blacklist
CREATE TABLE content_filters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    domain TEXT NOT NULL,
    filter_type TEXT, -- 'whitelist' or 'blacklist'
    category TEXT, -- 'educational', 'gaming', 'social', etc.
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id)
);

-- Activity log
CREATE TABLE family_activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    device_id INTEGER,
    action TEXT, -- 'allowed', 'blocked', 'override_granted', etc.
    url TEXT,
    domain TEXT,
    category TEXT,
    duration INTEGER, -- seconds spent on site
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id),
    FOREIGN KEY (device_id) REFERENCES user_devices(id)
);

-- Screen time usage
CREATE TABLE screen_time_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    device_id INTEGER,
    date DATE NOT NULL,
    category TEXT, -- 'gaming', 'social', 'educational', 'streaming', 'other'
    seconds_used INTEGER DEFAULT 0,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id),
    FOREIGN KEY (device_id) REFERENCES user_devices(id),
    UNIQUE(family_member_id, device_id, date, category)
);

-- Parent overrides
CREATE TABLE parent_overrides (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    family_member_id INTEGER NOT NULL,
    device_id INTEGER,
    override_type TEXT, -- 'content_filter', 'screen_time', 'category_block'
    duration_minutes INTEGER,
    reason TEXT,
    expires_at DATETIME,
    granted_by INTEGER, -- parent user_id
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_member_id) REFERENCES family_members(id),
    FOREIGN KEY (granted_by) REFERENCES users(id)
);
```

---

## 🚀 API ENDPOINTS

### Family Management
```
POST   /api/family/add-member.php        - Add child to family
GET    /api/family/list-members.php      - List all family members
DELETE /api/family/remove-member.php     - Remove family member
POST   /api/family/assign-device.php     - Assign device to family member
```

### Parental Controls
```
POST   /api/parental/set-profile.php     - Configure parental profile
GET    /api/parental/get-profile.php     - Get parental settings
POST   /api/parental/set-schedule.php    - Set screen time schedule
GET    /api/parental/get-schedule.php    - Get schedule
POST   /api/parental/add-filter.php      - Add whitelist/blacklist entry
DELETE /api/parental/remove-filter.php   - Remove filter entry
```

### Activity & Monitoring
```
GET    /api/parental/activity-feed.php   - Get real-time activity
GET    /api/parental/daily-usage.php     - Get daily screen time usage
GET    /api/parental/weekly-report.php   - Get weekly report
POST   /api/parental/send-report.php     - Email weekly report
```

### Parent Controls
```
POST   /api/parental/grant-override.php  - Grant temporary override
POST   /api/parental/pause-device.php    - Instantly pause device
POST   /api/parental/bonus-time.php      - Grant bonus screen time
POST   /api/parental/instant-block.php   - Block device immediately
```

### Content Filtering (Server-Side)
```
POST   /api/filter/check-url.php         - Check if URL is allowed
POST   /api/filter/log-access.php        - Log access attempt
GET    /api/filter/blocklist.php         - Get current blocklist
```

---

## 📱 CHILD EXPERIENCE

### On-Device Notifications
```
┌─────────────────────────────────────┐
│ 🕐 Screen Time Reminder             │
├─────────────────────────────────────┤
│                                     │
│ You have 30 minutes remaining      │
│ today.                              │
│                                     │
│ Used: 3h 30m / 4h 00m              │
│                                     │
│ [OK]                                │
└─────────────────────────────────────┘
```

```
┌─────────────────────────────────────┐
│ ⏰ Chores Time!                      │
├─────────────────────────────────────┤
│                                     │
│ Time to do your chores.             │
│ Internet access will resume at:     │
│                                     │
│ 6:00 PM (in 2 hours)                │
│                                     │
│ Chores to complete:                 │
│ ☐ Clean room                        │
│ ☐ Do dishes                         │
│ ☐ Homework                          │
│                                     │
│ [Mark Complete]                     │
└─────────────────────────────────────┘
```

```
┌─────────────────────────────────────┐
│ 🚫 Content Blocked                  │
├─────────────────────────────────────┤
│                                     │
│ This website is blocked by          │
│ parental controls.                  │
│                                     │
│ Category: Social Media              │
│ Reason: Homework time               │
│                                     │
│ Access resumes at: 6:00 PM          │
│                                     │
│ [Request Override] [OK]             │
└─────────────────────────────────────┘
```

### Request Override (Child Initiated)
```
┌─────────────────────────────────────────┐
│ Request Access                          │
├─────────────────────────────────────────┤
│                                         │
│ I need access to:                       │
│ youtube.com                             │
│                                         │
│ Reason:                                 │
│ [Need for school project video__]      │
│                                         │
│ Request for:                            │
│ ⚪ 15 minutes                           │
│ ● 30 minutes                            │
│ ⚪ 1 hour                               │
│                                         │
│ [Send Request to Parents]              │
│                                         │
│ Parents will be notified and can       │
│ approve or deny your request.           │
└─────────────────────────────────────────┘
```

---

**Status:** Complete Specification - Ready for Implementation  
**Priority:** High (valuable feature for Family plan)  
**Estimated Implementation Time:** 7-10 days
