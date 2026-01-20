# MASTER CHECKLIST - PART 16: TUTORIAL SYSTEM

**Created:** January 18, 2026 - 11:10 PM CST  
**Blueprint:** SECTION_19_TUTORIAL_SYSTEM.md (1,270 lines)  
**Status:** ⏳ NOT STARTED  
**Priority:** 🟡 MEDIUM - User Onboarding  
**Estimated Time:** 6-8 hours  
**Estimated Lines:** ~1,500 lines  

---

## 📋 OVERVIEW

Build an interactive tutorial system with 35 step-by-step lessons that teach by DOING, not reading.

**Core Principle:** *"Learning by doing, not by reading"*

**What This Includes:**
- 35 interactive lessons across 4 categories
- Getting Started (5 lessons)
- Database Builder tutorials (10 lessons)
- Form Builder tutorials (10 lessons)
- Marketing tutorials (10 lessons)
- Progress tracking with completion percentages
- Video tutorials embedded
- Help bubbles throughout system

---

## 🎯 KEY FEATURES

✅ 35 interactive step-by-step lessons  
✅ Click-to-advance progression  
✅ Real-time validation  
✅ Progress tracking & certificates  
✅ Video tutorials (3-5 min each)  
✅ Context-sensitive help bubbles  
✅ Animated pointers  
✅ Instant feedback  
✅ No boring documentation  

---

## 💾 TASK 16.1: Create Database Schema (tutorials.db)

**Time:** 20 minutes  
**Lines:** ~100 lines  
**File:** `/admin/tutorials/setup-tutorials.php`

### **Create tutorials.db with 3 tables:**

```sql
-- TABLE 1: tutorial_lessons (all 35 lessons)
CREATE TABLE IF NOT EXISTS tutorial_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_number INTEGER NOT NULL,
    category TEXT NOT NULL,                 -- getting_started, database, forms, marketing
    title TEXT NOT NULL,
    description TEXT,
    duration_minutes INTEGER DEFAULT 5,
    difficulty TEXT DEFAULT 'beginner',     -- beginner, intermediate, advanced
    lesson_content TEXT NOT NULL,           -- JSON: steps, validations, etc.
    video_url TEXT,
    completion_criteria TEXT,               -- JSON: what must be done to complete
    prerequisite_lesson_id INTEGER,         -- NULL or ID of required lesson
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 2: user_tutorial_progress (tracks completion)
CREATE TABLE IF NOT EXISTS user_tutorial_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    status TEXT DEFAULT 'not_started',      -- not_started, in_progress, completed, skipped
    current_step INTEGER DEFAULT 0,
    started_at TEXT,
    completed_at TEXT,
    time_spent_minutes INTEGER DEFAULT 0,
    FOREIGN KEY (lesson_id) REFERENCES tutorial_lessons(id),
    UNIQUE(user_id, lesson_id)
);

-- TABLE 3: help_bubbles (context-sensitive help)
CREATE TABLE IF NOT EXISTS help_bubbles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_url TEXT NOT NULL,                 -- Where to show this help
    element_selector TEXT NOT NULL,         -- CSS selector: #customer-table
    bubble_title TEXT NOT NULL,
    bubble_content TEXT NOT NULL,
    bubble_position TEXT DEFAULT 'right',   -- top, right, bottom, left
    show_on_hover INTEGER DEFAULT 1,
    auto_show INTEGER DEFAULT 0,            -- Show automatically on page load
    priority INTEGER DEFAULT 0,             -- Higher = shows first
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

### **Verification:**
- [ ] tutorials.db created
- [ ] All 3 tables exist
- [ ] Can insert test data

---

## 📚 TASK 16.2: Create 35 Tutorial Lessons

**Time:** 3 hours  
**Lines:** ~700 lines (20 lines per lesson)  
**Files:** 35 JSON files in `/admin/tutorials/lessons/`

### **Category 1: Getting Started (5 lessons)**

1. **Understanding Databases** (5 min) - What is a database, simple explanation
2. **Your First Table** (5 min) - Create "contacts" table
3. **Adding Fields** (5 min) - Add name, email, phone fields
4. **Adding Records** (5 min) - Insert first customer
5. **Viewing & Editing Data** (5 min) - Browse and edit records

### **Category 2: Database Builder (10 lessons)**

6. **Field Types Overview** (5 min) - Tour of 15 field types
7. **Required Fields** (5 min) - Make fields required
8. **Unique Fields** (5 min) - Prevent duplicate emails
9. **Default Values** (5 min) - Set automatic defaults
10. **Dropdown Options** (7 min) - Create dropdown menus
11. **One-to-Many Relationships** (10 min) - Link customers to tickets
12. **Validation Rules** (7 min) - Email format, number ranges
13. **Import from CSV** (7 min) - Bulk import existing data
14. **Export to Excel** (5 min) - Backup your data
15. **Advanced: Many-to-Many** (10 min) - Products & customers

### **Category 3: Form Builder (10 lessons)**

16. **Using Form Templates** (5 min) - Pick and customize
17. **Creating Custom Forms** (7 min) - Build from scratch
18. **Form Styling** (5 min) - Choose Casual/Business/Corporate
19. **Conditional Logic** (10 min) - Show/hide fields
20. **File Upload Fields** (7 min) - Accept documents/images
21. **Multi-Page Forms** (10 min) - Progress indicators
22. **Email Notifications** (7 min) - Auto-send confirmations
23. **Form Embedding** (5 min) - Add to website
24. **Viewing Submissions** (5 min) - Manage responses
25. **Export Form Data** (5 min) - Download to Excel

### **Category 4: Marketing Automation (10 lessons)**

26. **Platform Overview** (5 min) - Tour of 50+ platforms
27. **Activating Automation** (5 min) - Turn on daily posting
28. **Content Calendar** (7 min) - 365 days explained
29. **Customizing Posts** (7 min) - Edit scheduled content
30. **Manual Posting Queue** (7 min) - Copy/paste workflow
31. **Holiday Promotions** (7 min) - Auto-adjust pricing
32. **Analytics Dashboard** (7 min) - Track performance
33. **Adding New Platforms** (5 min) - Configure more channels
34. **API Integrations** (10 min) - Connect Facebook, Twitter
35. **Performance Optimization** (10 min) - Improve CTR

### **Lesson Structure (JSON):**

```json
{
  "lesson_number": 1,
  "category": "getting_started",
  "title": "Understanding Databases (for Beginners)",
  "description": "Learn what databases are in simple terms",
  "duration_minutes": 5,
  "difficulty": "beginner",
  "video_url": "https://youtube.com/watch?v=...",
  "steps": [
    {
      "step_number": 1,
      "instruction": "Think of a database like a filing cabinet",
      "action_required": "click_next",
      "validation": null
    },
    {
      "step_number": 2,
      "instruction": "Each table is like a folder in the cabinet",
      "action_required": "click_next",
      "validation": null
    },
    // ... more steps
  ],
  "completion_criteria": {
    "min_steps_completed": 5,
    "quiz_score": null
  }
}
```

### **Verification:**
- [ ] All 35 lessons created
- [ ] JSON valid
- [ ] Steps logical
- [ ] Duration accurate

---

## 🎓 TASK 16.3: Interactive Tutorial Engine

**Time:** 2 hours  
**Lines:** ~400 lines  
**File:** `/admin/tutorials/engine.php`

### **Tutorial Player Interface:**

```
┌────────────────────────────────────────────────────────────┐
│ 📚 Lesson 1 of 35: Understanding Databases                │
├────────────────────────────────────────────────────────────┤
│ Progress: ███████░░░░░░░░░░░░░░ 35% (Step 7 of 20)       │
│                                                            │
│ ┌────────────────────────────────────────────────────────┐│
│ │ 💡 CURRENT STEP:                                       ││
│ │                                                        ││
│ │ Think of a database like a super-organized filing     ││
│ │ cabinet for your computer.                             ││
│ │                                                        ││
│ │ Instead of paper files, you have:                      ││
│ │ • TABLES (like folders)                                ││
│ │ • RECORDS (like individual papers)                     ││
│ │ • FIELDS (like specific info on each paper)            ││
│ │                                                        ││
│ │ [Watch 2-min Video] [Skip Tutorial] [Mark as Complete]││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ [◄ Previous] [Next: Real-World Example ►]                 │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Step-by-step progression
- [ ] Progress bar
- [ ] Next/Previous navigation
- [ ] Skip tutorial option
- [ ] Mark as complete
- [ ] Video player embedded
- [ ] Animated pointers (arrows highlighting UI elements)
- [ ] Real-time validation

### **Verification:**
- [ ] Lessons load
- [ ] Steps advance
- [ ] Progress tracked
- [ ] Videos play
- [ ] Completion works

---

## 💬 TASK 16.4: Help Bubbles & Tooltips

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/admin/tutorials/help-bubbles.js`

### **Help Bubble System:**

```javascript
// Show help bubble on hover
document.querySelectorAll('[data-help]').forEach(element => {
    element.addEventListener('mouseenter', showHelpBubble);
    element.addEventListener('mouseleave', hideHelpBubble);
});

function showHelpBubble(event) {
    const helpText = event.target.getAttribute('data-help');
    const bubble = createBubble(helpText);
    positionBubble(bubble, event.target);
    document.body.appendChild(bubble);
}
```

### **Help Bubble Types:**

**1. Hover Tooltips:**
```html
<button data-help="Click to add a new customer record">Add Customer</button>
```

**2. Auto-Show on Page Load:**
```html
<div data-help-auto="Welcome! This is your database dashboard.">
```

**3. Animated Pointers:**
```html
<div class="help-pointer" data-target="#customer-table">
    ⬇️ Your customer data appears here
</div>
```

### **Features:**
- [ ] Hover tooltips
- [ ] Auto-show bubbles
- [ ] Animated arrows pointing to elements
- [ ] Dismissible
- [ ] Position: top/right/bottom/left
- [ ] Context-sensitive (only show on relevant pages)

### **Verification:**
- [ ] Tooltips appear on hover
- [ ] Auto-bubbles show on load
- [ ] Pointers highlight correct elements
- [ ] Can dismiss bubbles

---

## 📊 TASK 16.5: Progress Tracking Dashboard

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/admin/tutorials/progress.php`

### **Progress Dashboard:**

```
┌────────────────────────────────────────────────────────────┐
│ 📚 Your Learning Progress                                  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ OVERALL: ████████████░░░░ 65% Complete (23 of 35 lessons)  │
│                                                            │
│ GETTING STARTED: ████████████████████ 100% (5/5) ✅        │
│ DATABASE BUILDER: ████████████░░░░░░ 60% (6/10)           │
│ FORM BUILDER: ████████░░░░░░░░░░░░░░ 40% (4/10)           │
│ MARKETING: ████░░░░░░░░░░░░░░░░░░░░ 20% (2/10)            │
│                                                            │
│ NEXT RECOMMENDED LESSON:                                    │
│ ┌────────────────────────────────────────────────────────┐│
│ │ 🔢 Lesson 11: One-to-Many Relationships                ││
│ │ Duration: 10 minutes                                   ││
│ │ [Start Lesson ►]                                       ││
│ └────────────────────────────────────────────────────────┘│
│                                                            │
│ ACHIEVEMENTS UNLOCKED:                                      │
│ 🏆 Database Beginner (Completed 5 lessons)                │
│ 🎓 Form Creator (Created first custom form)               │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Overall progress percentage
- [ ] Progress by category
- [ ] Next recommended lesson
- [ ] Time spent learning
- [ ] Achievement badges
- [ ] Certificate download (when 100%)

### **Verification:**
- [ ] Progress accurate
- [ ] Recommendations relevant
- [ ] Achievements unlock
- [ ] Certificate generates

---

## 🎥 TASK 16.6: Video Tutorial Integration

**Time:** 30 minutes  
**Lines:** ~100 lines  

### **Video Platform:**
- Use YouTube (free hosting)
- Embed in lessons
- Track completion

### **Video Topics (35 videos, 3-5 min each):**
- Screen recordings for each lesson
- Voiceover explanations
- Real-time demonstrations

### **Features:**
- [ ] Embedded YouTube player
- [ ] Auto-play on lesson start
- [ ] Track when video watched
- [ ] Playback controls

### **Verification:**
- [ ] Videos play
- [ ] Completion tracked
- [ ] No autoplay issues

---

## 🧪 TESTING CHECKLIST

### **Tutorial Lessons:**
- [ ] All 35 lessons load
- [ ] Steps advance correctly
- [ ] Validation works
- [ ] Videos embedded
- [ ] Progress tracked

### **Help System:**
- [ ] Tooltips appear on hover
- [ ] Help bubbles show on correct pages
- [ ] Pointers highlight elements
- [ ] Can dismiss bubbles

### **Progress Tracking:**
- [ ] Completion percentages accurate
- [ ] Achievements unlock at right time
- [ ] Certificate generates when 100%
- [ ] Can restart lessons

---

## 📦 FILE STRUCTURE

```
/admin/tutorials/
├── index.php (progress dashboard)
├── engine.php (tutorial player)
├── progress.php (progress tracking)
├── setup-tutorials.php (database setup)
├── lessons/ (35 JSON files)
│   ├── 01_understanding_databases.json
│   ├── 02_first_table.json
│   └── [33 more...]
├── assets/
│   ├── css/tutorials.css
│   └── js/
│       ├── tutorial-engine.js
│       └── help-bubbles.js
└── databases/
    └── tutorials.db
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] All files uploaded
- [ ] tutorials.db created
- [ ] All 35 lessons present
- [ ] Videos hosted on YouTube
- [ ] Help bubbles configured
- [ ] Progress tracking works
- [ ] No errors

---

## 📊 SUMMARY

**Total Tasks:** 6 major tasks  
**Total Lessons:** 35 interactive lessons  
**Total Videos:** 35 screen recordings  
**Total Lines:** ~1,500 lines  
**Total Time:** 6-8 hours  

**Dependencies:**
- Part 13 (Database Builder) ✅
- Part 14 (Form Library) ✅
- Part 15 (Marketing Automation) ✅

**Result:** Users learn by doing, not reading!

---

**END OF PART 16 CHECKLIST - TUTORIAL SYSTEM**

---

## 🔄 CRITICAL UPDATES - JANUARY 20, 2026

**USER DECISION:** Part 16 support system = SAME as Part 7 support system

**Integration:**
- Part 7 creates backend APIs (/api/support/)
- Part 7 creates admin interface (/admin/support-tickets.php)
- Part 7 creates user dashboard (/dashboard/support.php)
- **Part 16 adds public portal** (/support/) for non-logged-in users

**All use the SAME support.db database**

---

### **UNIFIED SUPPORT SYSTEM ARCHITECTURE:**

**Backend (Part 7):**
- /api/support/create-ticket.php
- /api/support/list-tickets.php
- /api/support/update-ticket.php
- /api/support/close-ticket.php

**Admin Interface (Part 7):**
- /admin/support-tickets.php (manage all tickets)

**User Dashboard (Part 7):**
- /dashboard/support.php (logged-in users view their tickets)

**Public Portal (Part 16):**
- /support/index.php (knowledge base homepage)
- /support/kb.php (browse articles)
- /support/submit.php (submit ticket without login)
- /support/api.php (public API wrapper)
- /support/config.php (portal settings)

---

### **UPDATED TASK 16.1: Create Public Support Portal Homepage**

**File:** `/support/index.php`
**Time:** 2 hours
**Lines:** ~400 lines
**Database:** support.db (from Part 7)

**Purpose:** Public-facing support portal for:
- Browsing knowledge base (before logging in)
- Submitting tickets (guest users)
- Searching articles
- Viewing FAQs

**Interface Sections:**

**1. Hero Section:**
```php
<div class="support-hero">
  <h1>How can we help you?</h1>
  <input type="text" id="searchArticles" placeholder="Search knowledge base...">
</div>
```

**2. Popular Articles (from KB):**
```php
<?php
$popular = $db->query("
  SELECT * FROM knowledge_base 
  WHERE is_published = 1 
  ORDER BY view_count DESC 
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($popular as $article):
?>
<div class="kb-card">
  <h3><?= htmlspecialchars($article['title']) ?></h3>
  <p><?= htmlspecialchars($article['excerpt']) ?></p>
  <a href="/support/kb.php?id=<?= $article['id'] ?>">Read more →</a>
</div>
<?php endforeach; ?>
```

**3. Categories:**
- Getting Started
- Billing & Payments
- Technical Support
- Account Management
- Troubleshooting
- VIP Features

**4. Quick Actions:**
- Browse Knowledge Base
- Submit Ticket
- Check Ticket Status
- Contact Support

**Automation Integration:**
- Search suggests KB articles before ticket submission
- Auto-categorize tickets based on keywords
- Auto-assign priority (VIP users = high)
- Auto-suggest solutions from KB

---

### **UPDATED TASK 16.2: Knowledge Base Browser**

**File:** `/support/kb.php`
**Time:** 3 hours
**Lines:** ~500 lines

**Purpose:** Browse and search knowledge base articles

**Features:**
- Category browser
- Article search (full-text)
- Related articles
- Article voting (helpful/not helpful)
- Print-friendly view
- Share article link

**Auto-Resolution:**
When user searches KB before submitting ticket:
1. Find matching articles
2. Show top 3 matches
3. Ask "Did this solve your problem?"
4. If YES → No ticket created (auto-resolved)
5. If NO → Pre-fill ticket with context

**Example:**
```php
// User searches "cannot connect"
$matches = $db->query("
  SELECT * FROM knowledge_base 
  WHERE 
    title LIKE '%cannot connect%' OR 
    content LIKE '%cannot connect%' OR
    tags LIKE '%connection%'
  ORDER BY relevance DESC
  LIMIT 3
")->fetchAll();

if (count($matches) > 0) {
  echo "<div class='suggested-solutions'>";
  echo "<h3>We found these articles that might help:</h3>";
  foreach ($matches as $match) {
    // Show article
  }
  echo "<p>Did this solve your problem?</p>";
  echo "<button onclick='solved()'>Yes, I'm good!</button>";
  echo "<button onclick='createTicket()'>No, I need more help</button>";
  echo "</div>";
}
```

---

### **UPDATED TASK 16.3: Guest Ticket Submission**

**File:** `/support/submit.php`
**Time:** 2 hours
**Lines:** ~350 lines

**Purpose:** Allow non-logged-in users to submit tickets

**Form Fields:**
- Name
- Email
- Subject
- Description
- Category (dropdown)
- Priority (auto-detected or manual)
- Attachments (optional)

**Auto-Features:**
- KB article suggestions as user types
- Email verification (send verification link)
- Auto-categorization based on keywords
- VIP detection (check email against VIP list)
- Spam protection (CAPTCHA)

**Workflow:**
1. User fills form
2. KB articles suggested based on description
3. User clicks "Submit Ticket"
4. Email verification sent
5. User clicks link in email
6. Ticket created in support.db
7. Confirmation email sent
8. Admin notified (if VIP or urgent)

**Integration with Part 7:**
- Uses same /api/support/create-ticket.php
- Stores in same support.db
- Triggers same automation workflows
- Admin sees ticket in /admin/support-tickets.php

---

### **UPDATED Part 16 Summary:**

**Clarification:** Part 16 is the PUBLIC PORTAL for Part 7's support system

**Part 7 (Backend):**
- API endpoints
- Admin interface
- User dashboard (logged-in)
- Automation workflows

**Part 16 (Frontend):**
- Public portal (guest access)
- Knowledge base browser
- Guest ticket submission
- KB search

**Same Database:** support.db (shared)

**Integration Points:**
- Part 16 calls Part 7's APIs
- Part 16 uses Part 7's automation
- Part 16 tickets appear in Part 7's admin
- Part 7 KB articles shown in Part 16 portal

**Time Estimate:**
- Original: 4-5 hours
- Updated: 4-5 hours (no change, just clarification)

---
