# SECTION 7: PARENTAL CONTROLS

**Created:** January 15, 2026  
**Status:** Complete Technical Specification  
**Priority:** HIGH - Family Plan Feature  
**Complexity:** MEDIUM - DNS Filtering & Policies  

---

## 📋 TABLE OF CONTENTS

1. [What are Parental Controls?](#what-are)
2. [Why This Matters](#why-matters)
3. [Core Features](#core-features)
4. [Content Filtering](#content-filtering)
5. [Usage Limits](#usage-limits)
6. [Device Management](#device-management)
7. [Activity Monitoring](#activity-monitoring)
8. [Bedtime Mode](#bedtime-mode)
9. [Safe Search](#safe-search)
10. [Technical Implementation](#implementation)
11. [Setup Wizard](#setup)
12. [Reports](#reports)

---

## 👨‍👩‍👧‍👦 WHAT ARE PARENTAL CONTROLS?

### **Simple Explanation**

Parental Controls let parents **protect their children online** by:
- ✅ Blocking inappropriate websites
- ✅ Setting time limits (bedtime, homework time)
- ✅ Monitoring what kids access online
- ✅ Enforcing safe search on Google/Bing
- ✅ Getting alerts for concerning activity
- ✅ Managing multiple children's devices

### **Who Needs This?**

**Target Users:**
- Parents with children (ages 5-17)
- Families sharing internet
- Schools and educational institutions
- Anyone concerned about online safety

### **Key Differentiator**

**Most VPNs don't offer parental controls!**

**Competitors:**
- NordVPN: No parental controls ❌
- ExpressVPN: No parental controls ❌
- Surfshark: Basic content blocking only 🟡
- **TrueVault: Full parental control suite** ✅

---

## 💡 WHY THIS MATTERS

### **The Problem**

**Kids are exposed to dangers online:**
- 🚨 **Pornography** - 1 in 3 kids accidentally see it
- 🚨 **Violence** - Graphic content on YouTube, TikTok
- 🚨 **Predators** - Chat rooms, social media grooming
- 🚨 **Cyberbullying** - Harassment on social platforms
- 🚨 **Scams** - Phishing, fake giveaways
- 🚨 **Addiction** - Excessive gaming, social media

**Statistics:**
- Average child sees porn by age 11
- 70% of teens hide online activity from parents
- 42% of kids have been cyberbullied
- Children average 7+ hours screen time daily

### **Existing Solutions Are Inadequate**

**Router-Level Filtering:**
- ❌ Can't differentiate between family members
- ❌ Easy for kids to bypass (use mobile data)
- ❌ No device-specific controls
- ❌ Blocks everyone or no one

**Device-Level Filtering:**
- ❌ Must configure each device separately
- ❌ Kids can disable it (if they have admin access)
- ❌ Doesn't work across all devices
- ❌ No centralized management

**Third-Party Apps:**
- ❌ Monthly subscription fees ($10-15/month)
- ❌ Requires app installation (kids can uninstall)
- ❌ Privacy concerns (tracks all activity)
- ❌ Doesn't integrate with VPN

### **TrueVault Solution**

**Built-In Parental Controls:**
- ✅ **Per-device policies** - Different rules per child
- ✅ **Can't bypass** - Works at VPN level
- ✅ **Centralized dashboard** - Manage all devices
- ✅ **No extra cost** - Included with Family plan
- ✅ **Privacy-focused** - Only parent sees reports
- ✅ **Easy setup** - 5-minute wizard

---

## 🎯 CORE FEATURES

### **Feature Overview**

```
┌──────────────────────────────────────────────────┐
│ PARENTAL CONTROLS                                │
├──────────────────────────────────────────────────┤
│                                                  │
│ 1. Content Filtering                            │
│    ➜ Block adult content, violence, drugs       │
│    ➜ Custom block lists                         │
│    ➜ Category-based filtering                   │
│                                                  │
│ 2. Time Limits                                   │
│    ➜ Daily screen time limits                   │
│    ➜ Bedtime enforcement                        │
│    ➜ Homework/focus mode                        │
│                                                  │
│ 3. Device Management                             │
│    ➜ One profile per child                      │
│    ➜ Multiple devices per profile               │
│    ➜ Pause internet instantly                   │
│                                                  │
│ 4. Activity Monitoring                           │
│    ➜ See what sites they visit                  │
│    ➜ Search query logs                          │
│    ➜ App usage tracking                         │
│                                                  │
│ 5. Safe Search                                   │
│    ➜ Force safe search on Google                │
│    ➜ YouTube restricted mode                    │
│    ➜ Block explicit results                     │
│                                                  │
│ 6. Alerts                                        │
│    ➜ Notify when blocked site accessed          │
│    ➜ Time limit warnings                        │
│    ➜ Concerning search alerts                   │
│                                                  │
└──────────────────────────────────────────────────┘
```

---

## 🚫 CONTENT FILTERING

### **How It Works**

**DNS-Level Filtering:**

```
Child's Device
    ↓
Request: www.badsite.com
    ↓
TrueVault VPN (checks policy)
    ↓
Is site blocked? → YES
    ↓
Return: BLOCKED PAGE
    ↓
Child sees: "This site is blocked by parental controls"
```

**Benefits:**
- ✅ Works for ALL apps (not just browsers)
- ✅ Can't be bypassed (device must use VPN)
- ✅ Fast (DNS lookup is instant)
- ✅ No content inspection needed (privacy!)

### **Content Categories**

**Pre-defined categories:**

```
Adult Content
├── Pornography
├── Nudity
├── Sexual content
└── Adult dating

Violence & Gore
├── Graphic violence
├── Weapons
├── Gore/blood
└── Hate/extremism

Illegal Content
├── Drugs
├── Gambling
├── Piracy
└── Hacking

Social Media
├── Facebook
├── Instagram
├── TikTok
└── Snapchat

Gaming
├── Online games
├── Gaming streams
├── Gaming forums
└── Game stores

Other
├── Ads/trackers
├── Malware sites
├── Proxy/VPN sites (to bypass)
└── Anonymous browsing
```

**Customizable:**
- ✅ Toggle categories on/off
- ✅ Add custom domains to block
- ✅ Whitelist exceptions
- ✅ Schedule (block TikTok during school hours)

### **Content Filtering UI**

```html
┌─────────────────────────────────────────────────┐
│ Content Filtering - Emily's Profile             │
├─────────────────────────────────────────────────┤
│                                                 │
│ Quick Presets:                                  │
│ ○ Strict (ages 5-9)                            │
│ ● Moderate (ages 10-13)                        │
│ ○ Light (ages 14-17)                           │
│ ○ Custom                                        │
│                                                 │
│ Block Categories:                               │
│ ☑ Adult content                                │
│ ☑ Violence & gore                              │
│ ☑ Drugs & alcohol                              │
│ ☐ Social media (allow)                         │
│ ☑ Gaming during school hours (8 AM - 3 PM)    │
│ ☐ YouTube (allow with restrictions)            │
│                                                 │
│ Custom Block List:                              │
│ ┌─────────────────────────────────────────────┐│
│ │ badsite.com                         [X]     ││
│ │ anotherbadsite.com                  [X]     ││
│ └─────────────────────────────────────────────┘│
│ [+ Add Domain]                                  │
│                                                 │
│ Whitelist (Always Allow):                      │
│ ┌─────────────────────────────────────────────┐│
│ │ school.edu                          [X]     ││
│ │ khanacademy.org                     [X]     ││
│ └─────────────────────────────────────────────┘│
│ [+ Add Exception]                               │
│                                                 │
│        [Save Settings]                          │
│                                                 │
└─────────────────────────────────────────────────┘
```

### **Blocked Page**

**When child tries to access blocked site:**

```html
┌─────────────────────────────────────────────────┐
│                                                 │
│              🚫 Site Blocked                    │
│                                                 │
│        This website is not available            │
│                                                 │
│  www.badsite.com has been blocked by            │
│  your parental controls.                        │
│                                                 │
│  Reason: Adult Content                          │
│                                                 │
│  If you believe this is a mistake, ask          │
│  your parent to review the settings.            │
│                                                 │
│         [Go Back] [Request Access]              │
│                                                 │
└─────────────────────────────────────────────────┘
```

**"Request Access" feature:**
- Child clicks "Request Access"
- Parent gets notification
- Parent can approve temporarily or permanently
- Teaches communication!

---

## ⏰ USAGE LIMITS

### **Daily Screen Time**

**Set maximum daily usage:**

```
Monday-Friday: 2 hours/day
Saturday-Sunday: 4 hours/day
```

**When limit reached:**
```
┌─────────────────────────────────────────────────┐
│         ⏰ Daily Limit Reached                  │
│                                                 │
│  You've used your 2 hours for today.           │
│  Try again tomorrow!                            │
│                                                 │
│  Time used today: 2h 0m                         │
│  Time resets at: 12:00 AM                       │
│                                                 │
│  [Ask for More Time]                            │
│                                                 │
└─────────────────────────────────────────────────┘
```

### **Schedule-Based Limits**

**Example: School day schedule**

```
6:00 AM - 7:30 AM:  Allowed (getting ready)
7:30 AM - 3:00 PM:  BLOCKED (school hours)
3:00 PM - 6:00 PM:  Allowed (homework/free time)
6:00 PM - 7:00 PM:  BLOCKED (dinner time)
7:00 PM - 9:00 PM:  Allowed (evening)
9:00 PM - 6:00 AM:  BLOCKED (bedtime)
```

### **Usage Limits UI**

```html
┌─────────────────────────────────────────────────┐
│ Time Limits - Jake's Profile                    │
├─────────────────────────────────────────────────┤
│                                                 │
│ Daily Screen Time:                              │
│                                                 │
│ Weekdays (Mon-Fri):                            │
│ ├──●──────────────┤ 2 hours                    │
│                                                 │
│ Weekends (Sat-Sun):                            │
│ ├─────────●───────┤ 4 hours                    │
│                                                 │
│ ☑ Enforce daily limits                         │
│ ☑ Show warnings at 15 min remaining            │
│                                                 │
│ Scheduled Blocks:                               │
│                                                 │
│ ┌─────────────────────────────────────────────┐│
│ │ School Hours                                ││
│ │ Mon-Fri, 7:30 AM - 3:00 PM                 ││
│ │ [Edit] [Delete]                            ││
│ └─────────────────────────────────────────────┘│
│                                                 │
│ ┌─────────────────────────────────────────────┐│
│ │ Bedtime                                     ││
│ │ Every day, 9:00 PM - 6:00 AM               ││
│ │ [Edit] [Delete]                            ││
│ └─────────────────────────────────────────────┘│
│                                                 │
│ [+ Add Schedule]                                │
│                                                 │
│        [Save Settings]                          │
│                                                 │
└─────────────────────────────────────────────────┘
```

### **Bonus Time**

**Parents can grant extra time:**

```
Parent clicks "Grant 30 minutes"
    ↓
Child gets notification: "Mom granted you 30 extra minutes!"
    ↓
New limit: 2h 30m
```

---

## 📱 DEVICE MANAGEMENT

### **Child Profiles**

**Create profile for each child:**

```
Family Members:
┌─────────────────────────────────────────────────┐
│ 👧 Emily (Age 12)                               │
│    Devices: iPhone, iPad                        │
│    Status: Online (1h 23m used today)           │
│    [Manage] [Pause] [Report]                    │
├─────────────────────────────────────────────────┤
│ 👦 Jake (Age 8)                                 │
│    Devices: iPad, Xbox                          │
│    Status: Online (45m used today)              │
│    [Manage] [Pause] [Report]                    │
├─────────────────────────────────────────────────┤
│ 👶 Sophia (Age 5)                               │
│    Devices: iPad                                │
│    Status: Offline                              │
│    [Manage] [Pause] [Report]                    │
└─────────────────────────────────────────────────┘
[+ Add Child]
```

### **Device Assignment**

**Assign devices to profiles:**

```
Emily's Devices:
┌─────────────────────────────────────────────────┐
│ 📱 iPhone 13 (192.168.1.105)                    │
│    Last seen: 5 minutes ago                     │
│    [Remove]                                     │
├─────────────────────────────────────────────────┤
│ 📱 iPad Air (192.168.1.106)                     │
│    Last seen: 2 hours ago                       │
│    [Remove]                                     │
└─────────────────────────────────────────────────┘
[+ Add Device]
```

**How device assignment works:**
1. Child logs into VPN on their device
2. Parent assigns device to child's profile
3. All rules for that profile apply to device
4. Device can only be in one profile at a time

### **Instant Pause**

**Pause internet for any child:**

```
[Pause Jake's Internet] → Clicked
    ↓
All Jake's devices lose internet
    ↓
Jake sees: "Internet paused by parent"
    ↓
Parent can unpause anytime
```

**Use cases:**
- Dinner time
- Family movie night
- Punishment
- Emergency (need kid's attention)

---

## 📊 ACTIVITY MONITORING

### **What Parents Can See**

**Browsing History:**
```
Today's Activity - Emily
┌─────────────────────────────────────────────────┐
│ 3:45 PM  YouTube.com                            │
│          Video: "How to do algebra homework"    │
│                                                 │
│ 3:52 PM  KhanAcademy.org                        │
│          Math: Quadratic equations              │
│                                                 │
│ 4:15 PM  Instagram.com                          │
│          Browsing feed                          │
│                                                 │
│ 4:45 PM  TikTok.com ⚠️                          │
│          BLOCKED (School hours policy)          │
│                                                 │
│ 5:00 PM  Discord.com                            │
│          Chatting with friends                  │
└─────────────────────────────────────────────────┘
[View Full History] [Export Report]
```

**Search Queries:**
```
Recent Searches - Jake
┌─────────────────────────────────────────────────┐
│ "minecraft redstone tutorial"                   │
│ "cool math games"                               │
│ "fortnite battle pass" ⚠️                       │
│   └─ Gaming blocked during school hours        │
│ "how to draw dragon"                            │
└─────────────────────────────────────────────────┘
```

**App Usage:**
```
Top Apps Today - Emily
┌─────────────────────────────────────────────────┐
│ 1. Instagram      ████████░░  1h 23m           │
│ 2. YouTube        ██████░░░░  58m              │
│ 3. Safari         ████░░░░░░  35m              │
│ 4. Snapchat       ███░░░░░░░  28m              │
└─────────────────────────────────────────────────┘
```

### **Privacy Balance**

**What we track:**
- ✅ Domain names (google.com, youtube.com)
- ✅ Search queries (keywords only)
- ✅ Time spent on sites
- ✅ Block attempts

**What we DON'T track:**
- ❌ Actual page content
- ❌ Passwords or login info
- ❌ Private messages
- ❌ Exact URLs (just domains)

**Why this balance?**
- Parents need to keep kids safe
- Kids deserve some privacy
- We only log what's necessary

### **Alerts**

**Parent gets notified when:**

```
⚠️ Alert: Concerning Activity
┌─────────────────────────────────────────────────┐
│ Emily (12) searched:                            │
│ "how to skip school"                            │
│                                                 │
│ Time: 3:45 PM today                             │
│ Device: iPhone                                  │
│                                                 │
│ [View Full Report] [Talk to Emily]             │
└─────────────────────────────────────────────────┘
```

**Alert triggers:**
- 🚨 Searches with concerning keywords
- 🚨 Multiple block attempts (trying to bypass)
- 🚨 Time limit exceeded attempts
- 🚨 New device added to profile
- 🚨 VPN disconnected (trying to bypass)

---

## 🌙 BEDTIME MODE

### **Enforce Sleep Schedule**

**Bedtime enforcement:**

```
Bedtime Settings - All Children
┌─────────────────────────────────────────────────┐
│                                                 │
│ School Nights (Sun-Thu):                       │
│ Bedtime: [9:00 PM ▼]                           │
│ Wake up: [6:30 AM ▼]                           │
│                                                 │
│ Weekends (Fri-Sat):                            │
│ Bedtime: [10:00 PM ▼]                          │
│ Wake up: [8:00 AM ▼]                           │
│                                                 │
│ During bedtime:                                 │
│ ☑ Block all internet                           │
│ ☑ Only allow emergency calls                   │
│ ☐ Allow music/podcasts                         │
│                                                 │
│ Grace period: [15 minutes ▼]                   │
│ (Time to finish what they're doing)            │
│                                                 │
│        [Save Settings]                          │
│                                                 │
└─────────────────────────────────────────────────┘
```

**How it works:**

```
8:45 PM - 15 min warning
Child sees: "Bedtime in 15 minutes. Finish up!"

9:00 PM - Internet blocked
Child sees: "Bedtime! Internet is off until 6:30 AM. Good night! 🌙"

Device still works for:
✅ Emergency calls
✅ Alarm clock
✅ Offline apps (if allowed)

But no:
❌ Internet browsing
❌ Social media
❌ Online games
❌ YouTube
```

### **Bedtime Override**

**Parents can override temporarily:**

```
[Override Bedtime] → Clicked
"Allow until: [10:00 PM ▼] [11:00 PM] [12:00 AM]"
Reason: "Family movie night"
[Confirm]
```

---

## 🔍 SAFE SEARCH

### **Force Safe Search**

**Automatically enables safe search on:**
- ✅ Google (SafeSearch)
- ✅ Bing (SafeSearch)
- ✅ YouTube (Restricted Mode)
- ✅ DuckDuckGo (Safe Search)

**How it works:**

```
Child searches Google for: "dogs"
    ↓
TrueVault intercepts request
    ↓
Adds &safe=strict parameter
    ↓
Google returns filtered results
    ↓
No explicit content shown
```

**Benefits:**
- ✅ Filters explicit images
- ✅ Hides adult videos on YouTube
- ✅ Removes inappropriate suggestions
- ✅ Works automatically (can't be disabled)

### **YouTube Restricted Mode**

**Additional YouTube protections:**

```
YouTube Settings (Applied Automatically):
☑ Restricted Mode (hides mature content)
☑ Disable comments viewing
☑ Block live streams
☑ Block shorts (optional)
☑ Hide recommended videos with mature thumbnails
```

---

## 💻 TECHNICAL IMPLEMENTATION

### **Database Schema**

**Table: child_profiles (in main.db)**

```sql
CREATE TABLE IF NOT EXISTS child_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_user_id INTEGER NOT NULL,
    
    -- Child Info
    child_name TEXT NOT NULL,
    child_age INTEGER,
    profile_color TEXT DEFAULT '#3b82f6',
    
    -- Filtering Settings
    content_filter_level TEXT DEFAULT 'moderate',  -- strict, moderate, light, custom
    blocked_categories TEXT,                       -- JSON array
    custom_blocklist TEXT,                         -- JSON array of domains
    whitelist TEXT,                                -- JSON array of allowed domains
    
    -- Time Limits
    weekday_limit_minutes INTEGER DEFAULT 120,     -- 2 hours
    weekend_limit_minutes INTEGER DEFAULT 240,     -- 4 hours
    enforce_limits BOOLEAN DEFAULT 1,
    
    -- Schedules
    bedtime_school TEXT DEFAULT '21:00',           -- 9 PM
    wakeup_school TEXT DEFAULT '06:30',            -- 6:30 AM
    bedtime_weekend TEXT DEFAULT '22:00',          -- 10 PM
    wakeup_weekend TEXT DEFAULT '08:00',           -- 8 AM
    
    -- Features
    safe_search_enabled BOOLEAN DEFAULT 1,
    youtube_restricted BOOLEAN DEFAULT 1,
    block_vpn_proxies BOOLEAN DEFAULT 1,
    
    -- Alerts
    alert_on_blocks BOOLEAN DEFAULT 1,
    alert_on_concerning_searches BOOLEAN DEFAULT 1,
    alert_email TEXT,
    
    -- Status
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Table: profile_devices**

```sql
CREATE TABLE IF NOT EXISTS profile_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    
    -- Assignment
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INTEGER,                           -- User ID who assigned
    
    FOREIGN KEY (profile_id) REFERENCES child_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
);
```

**Table: activity_log**

```sql
CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    
    -- Activity
    activity_type TEXT NOT NULL,                   -- browsing, search, app_usage, block
    domain TEXT,
    url_path TEXT,                                 -- Just path, not full URL
    search_query TEXT,
    
    -- Result
    action TEXT,                                   -- allowed, blocked, flagged
    block_reason TEXT,
    
    -- Timing
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INTEGER,
    
    FOREIGN KEY (profile_id) REFERENCES child_profiles(id) ON DELETE CASCADE
);
```

**Table: usage_tracking**

```sql
CREATE TABLE IF NOT EXISTS usage_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    profile_id INTEGER NOT NULL,
    
    -- Daily Stats
    date DATE DEFAULT CURRENT_DATE,
    total_minutes INTEGER DEFAULT 0,
    
    -- Breakdown
    browsing_minutes INTEGER DEFAULT 0,
    gaming_minutes INTEGER DEFAULT 0,
    social_media_minutes INTEGER DEFAULT 0,
    youtube_minutes INTEGER DEFAULT 0,
    
    -- Limits
    daily_limit_minutes INTEGER,
    limit_reached_at TIME,
    bonus_minutes_granted INTEGER DEFAULT 0,
    
    FOREIGN KEY (profile_id) REFERENCES child_profiles(id) ON DELETE CASCADE,
    UNIQUE(profile_id, date)
);
```

---

### **DNS Filtering Implementation**

**How DNS filtering works:**

```php
<?php
// ============================================
// DNS FILTER CHECK
// ============================================

function checkDNSFilter($domain, $deviceId) {
    // Get device's profile
    $profile = getDeviceProfile($deviceId);
    
    if (!$profile) {
        return ['allowed' => true]; // No profile = no restrictions
    }
    
    // Check if domain is whitelisted
    if (isWhitelisted($domain, $profile)) {
        logActivity($profile['id'], $deviceId, 'allowed', $domain);
        return ['allowed' => true];
    }
    
    // Check if domain is in custom blocklist
    if (isBlocked($domain, $profile)) {
        logActivity($profile['id'], $deviceId, 'blocked', $domain, 'Custom blocklist');
        return [
            'allowed' => false,
            'reason' => 'Blocked by parental controls',
            'category' => 'Custom block'
        ];
    }
    
    // Check category blocks
    $category = getDomainCategory($domain);
    if ($category && isCategoryBlocked($category, $profile)) {
        logActivity($profile['id'], $deviceId, 'blocked', $domain, $category);
        return [
            'allowed' => false,
            'reason' => 'Blocked by parental controls',
            'category' => $category
        ];
    }
    
    // Check time-based blocks (e.g., gaming during school hours)
    $timeBlock = checkTimeBasedBlock($domain, $profile);
    if ($timeBlock) {
        logActivity($profile['id'], $deviceId, 'blocked', $domain, 'Time restriction');
        return [
            'allowed' => false,
            'reason' => $timeBlock['reason'],
            'category' => 'Time restriction'
        ];
    }
    
    // Check daily usage limit
    $usageLimit = checkUsageLimit($profile);
    if (!$usageLimit['allowed']) {
        logActivity($profile['id'], $deviceId, 'blocked', $domain, 'Daily limit reached');
        return [
            'allowed' => false,
            'reason' => 'Daily screen time limit reached',
            'limit_info' => $usageLimit
        ];
    }
    
    // Allowed - log and return
    logActivity($profile['id'], $deviceId, 'allowed', $domain);
    return ['allowed' => true];
}

// ============================================
// CATEGORY DATABASE
// ============================================

$CATEGORY_DATABASE = [
    'adult' => [
        'pornhub.com', 'xvideos.com', 'xhamster.com',
        // ... 10,000+ adult domains
    ],
    'violence' => [
        'liveleak.com', 'bestgore.com',
        // ... violence/gore sites
    ],
    'social_media' => [
        'facebook.com', 'instagram.com', 'tiktok.com', 
        'snapchat.com', 'twitter.com'
    ],
    'gaming' => [
        'roblox.com', 'minecraft.net', 'fortnite.com',
        'steam.com', 'twitch.tv'
    ],
    // ... more categories
];

function getDomainCategory($domain) {
    global $CATEGORY_DATABASE;
    
    foreach ($CATEGORY_DATABASE as $category => $domains) {
        if (in_array($domain, $domains)) {
            return $category;
        }
    }
    
    return null; // Unknown category
}
```

---

### **API Endpoints**

**Endpoint 1: Create Child Profile**

**URL:** `POST /api/parental-controls.php?action=create_profile`

**Request:**
```json
{
  "child_name": "Emily",
  "child_age": 12,
  "content_filter_level": "moderate",
  "weekday_limit_minutes": 120,
  "weekend_limit_minutes": 240
}
```

**Response:**
```json
{
  "success": true,
  "profile_id": 1,
  "message": "Profile created for Emily"
}
```

---

**Endpoint 2: Assign Device to Profile**

**URL:** `POST /api/parental-controls.php?action=assign_device`

**Request:**
```json
{
  "profile_id": 1,
  "device_id": "auto_10_8_0_15"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Device assigned to Emily's profile"
}
```

---

**Endpoint 3: Get Activity Report**

**URL:** `GET /api/parental-controls.php?action=activity_report&profile_id=1&date=2026-01-15`

**Response:**
```json
{
  "success": true,
  "profile": {
    "child_name": "Emily",
    "child_age": 12
  },
  "date": "2026-01-15",
  "usage": {
    "total_minutes": 143,
    "limit_minutes": 120,
    "over_limit": true,
    "bonus_granted": 30
  },
  "activities": [
    {
      "time": "15:45",
      "domain": "youtube.com",
      "action": "allowed",
      "duration_minutes": 23
    },
    {
      "time": "16:30",
      "domain": "tiktok.com",
      "action": "blocked",
      "reason": "Social media blocked during school hours"
    }
  ],
  "top_sites": [
    {"domain": "youtube.com", "minutes": 58},
    {"domain": "instagram.com", "minutes": 45},
    {"domain": "khanacademy.org", "minutes": 28}
  ],
  "blocks": [
    {"domain": "tiktok.com", "count": 5, "reason": "Time restriction"},
    {"domain": "badsite.com", "count": 2, "reason": "Adult content"}
  ]
}
```

---

## 🛠️ SETUP WIZARD

### **5-Minute Setup**

**Step 1: Create Child Profile**
```
┌─────────────────────────────────────────────────┐
│ Add Child Profile                      [Step 1/4]│
├─────────────────────────────────────────────────┤
│                                                 │
│ Child's Name:                                   │
│ ┌─────────────────────────────────────────────┐ │
│ │ Emily                                       │ │
│ └─────────────────────────────────────────────┘ │
│                                                 │
│ Age:                                            │
│ ┌─────┐                                         │
│ │ 12  │                                         │
│ └─────┘                                         │
│                                                 │
│ Profile Color:                                  │
│ ● 🔵 Blue  ○ 🟢 Green  ○ 🟣 Purple  ○ 🟡 Yellow│
│                                                 │
│             [Next →]                            │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Step 2: Choose Protection Level**
```
┌─────────────────────────────────────────────────┐
│ Choose Protection Level                [Step 2/4]│
├─────────────────────────────────────────────────┤
│                                                 │
│ ○ Strict (Ages 5-9)                            │
│   • Blocks: Adult, violence, social media      │
│   • Screen time: 1 hour/day                    │
│   • Safe search: Always on                     │
│                                                 │
│ ● Moderate (Ages 10-13)                        │
│   • Blocks: Adult, violence                    │
│   • Screen time: 2 hours/day                   │
│   • Safe search: On                            │
│   • Social media: Scheduled                    │
│                                                 │
│ ○ Light (Ages 14-17)                           │
│   • Blocks: Adult content only                 │
│   • Screen time: 3 hours/day                   │
│   • Safe search: Optional                      │
│   • More freedom                               │
│                                                 │
│      [← Back]           [Next →]               │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Step 3: Set Schedule**
```
┌─────────────────────────────────────────────────┐
│ Set Daily Schedule                     [Step 3/4]│
├─────────────────────────────────────────────────┤
│                                                 │
│ School Days (Mon-Fri):                         │
│                                                 │
│ Bedtime: [9:00 PM ▼]                           │
│ Wake up: [6:30 AM ▼]                           │
│                                                 │
│ Internet blocked during:                        │
│ ☑ School hours (7:30 AM - 3:00 PM)            │
│ ☑ Dinner time (6:00 PM - 7:00 PM)             │
│ ☑ Bedtime (9:00 PM - 6:30 AM)                 │
│                                                 │
│ Weekends (Sat-Sun):                            │
│                                                 │
│ Bedtime: [10:00 PM ▼]                          │
│ Wake up: [8:00 AM ▼]                           │
│                                                 │
│      [← Back]           [Next →]               │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Step 4: Assign Devices**
```
┌─────────────────────────────────────────────────┐
│ Assign Devices                         [Step 4/4]│
├─────────────────────────────────────────────────┤
│                                                 │
│ Which devices does Emily use?                   │
│                                                 │
│ ☑ 📱 iPhone 13 (192.168.1.105)                 │
│ ☑ 📱 iPad Air (192.168.1.106)                  │
│ ☐ 💻 MacBook Pro (192.168.1.150)               │
│ ☐ 🎮 Xbox (192.168.1.125)                      │
│                                                 │
│ [+ Add Device]                                  │
│                                                 │
│      [← Back]         [Finish Setup]           │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Step 5: Done!**
```
┌─────────────────────────────────────────────────┐
│ ✅ Setup Complete!                              │
├─────────────────────────────────────────────────┤
│                                                 │
│ Emily's profile is ready!                       │
│                                                 │
│ What's protected:                               │
│ ✅ Adult content blocked                        │
│ ✅ Violence & gore blocked                      │
│ ✅ 2 hours/day screen time                      │
│ ✅ Bedtime: 9 PM - 6:30 AM                      │
│ ✅ School hours blocked                         │
│ ✅ Safe search enabled                          │
│                                                 │
│ Devices: iPhone, iPad                           │
│                                                 │
│    [Go to Dashboard] [Add Another Child]        │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 📈 REPORTS

### **Daily Report Email**

**Parents receive daily summary:**

```
Subject: Daily Activity Report - Emily (Jan 15, 2026)

Hi Dad,

Here's Emily's internet activity for today:

📊 USAGE SUMMARY
---------------
Total screen time: 2h 23m (limit: 2h 0m)
  • Over limit by 23 minutes
  • Bonus time granted: 30 minutes

⏰ ACTIVITY TIMELINE
-------------------
3:45 PM - YouTube (23 min) ✅
4:15 PM - Instagram (45 min) ✅
5:00 PM - TikTok - BLOCKED 🚫
5:15 PM - KhanAcademy (28 min) ✅
6:30 PM - Snapchat (32 min) ✅

🚫 BLOCKED ATTEMPTS
------------------
• TikTok (5 attempts) - Time restriction
• badsite.com (2 attempts) - Adult content

🔍 CONCERNING SEARCHES
---------------------
No concerning searches today ✅

📱 DEVICES
----------
• iPhone: 1h 45m
• iPad: 38m

Need to adjust settings? Click here:
https://vpn.the-truth-publishing.com/parental-controls

---
TrueVault Parental Controls
```

### **Weekly Summary**

**Dashboard shows weekly trends:**

```
┌─────────────────────────────────────────────────┐
│ Weekly Report - Emily                           │
│ Jan 8 - Jan 14, 2026                           │
├─────────────────────────────────────────────────┤
│                                                 │
│ Average Daily Usage: 2h 15m                    │
│                                                 │
│ Daily Breakdown:                                │
│ Mon ████████░░  2h 5m                          │
│ Tue ██████████  2h 45m ⚠️ Over limit          │
│ Wed ███████░░░  1h 52m                          │
│ Thu ████████░░  2h 18m                          │
│ Fri █████████░  2h 32m                          │
│ Sat ████████████ 3h 45m (weekend)              │
│ Sun ███████████░ 3h 22m (weekend)              │
│                                                 │
│ Most Visited:                                   │
│ 1. Instagram (8h 23m this week)                │
│ 2. YouTube (6h 45m)                            │
│ 3. Snapchat (4h 12m)                           │
│                                                 │
│ Blocks This Week: 23                            │
│ • Social media (15)                            │
│ • Adult content (5)                            │
│ • Time restriction (3)                         │
│                                                 │
│        [Export Report] [Email Report]          │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

**END OF SECTION 7: PARENTAL CONTROLS**

**Next Section:** Section 8 (Admin Control Panel)  
**Status:** Section 7 Complete ✅  
**Lines:** ~1,500 lines  
**Created:** January 15, 2026 - 3:50 AM CST
