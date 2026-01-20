# MASTER CHECKLIST - PART 13: DATABASE BUILDER

**Created:** January 18, 2026 - 10:30 PM CST  
**Blueprint:** SECTION_16_DATABASE_BUILDER.md (2,105 lines)  
**Status:** ⏳ NOT STARTED  
**Priority:** 🟠 HIGH - Business Management Tool  
**Estimated Time:** 10-12 hours  
**Estimated Lines:** ~3,000 lines  

---

## 📋 OVERVIEW

**CRITICAL:** Use SQLite3 PHP class (NOT PDO)! Server has SQLite3 extension enabled.

Build a complete visual database builder for non-technical users.

**Core Principle:** *"If you can use Excel, you can build databases"*

**What This Enables:**
- FileMaker Pro alternative (FileMaker costs $588/year!)
- Airtable-style interface
- No SQL knowledge required
- Perfect for managing customers, tickets, inventory, etc.

---

## 🎯 KEY FEATURES

✅ Drag-and-drop field creation  
✅ 15+ field types (text, email, number, date, dropdown, etc.)  
✅ Visual relationship builder  
✅ Spreadsheet-like data editing  
✅ CSV/Excel import/export  
✅ Real-time preview  
✅ No coding required  

---

## 💾 TASK 13.1: Create Database Schema (builder.db)

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/admin/database-builder/setup-builder.php`

### **Create builder.db with 3 tables:**

```sql
-- TABLE 1: custom_tables (registry of user-created tables)
CREATE TABLE IF NOT EXISTS custom_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,        -- Internal: "customers"
    display_name TEXT NOT NULL,             -- User-friendly: "Customer Records"
    description TEXT,
    icon TEXT DEFAULT 'table',              -- Icon for UI
    color TEXT DEFAULT '#3b82f6',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,                     -- Admin user ID
    is_system INTEGER DEFAULT 0,            -- 0=user, 1=system
    record_count INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',           -- active, archived, deleted
    settings TEXT                           -- JSON metadata
);

-- TABLE 2: custom_fields (field definitions for tables)
CREATE TABLE IF NOT EXISTS custom_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    field_name TEXT NOT NULL,               -- Internal: "customer_email"
    display_name TEXT NOT NULL,             -- User-friendly: "Email Address"
    field_type TEXT NOT NULL,               -- text, email, number, etc.
    sort_order INTEGER DEFAULT 0,
    is_required INTEGER DEFAULT 0,
    is_unique INTEGER DEFAULT 0,
    default_value TEXT,
    validation_rules TEXT,                  -- JSON
    help_text TEXT,
    placeholder TEXT,
    options TEXT,                           -- JSON (for dropdown)
    settings TEXT,                          -- JSON (type-specific)
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);

-- TABLE 3: table_relationships (links between tables)
CREATE TABLE IF NOT EXISTS table_relationships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_table_id INTEGER NOT NULL,
    child_table_id INTEGER NOT NULL,
    relationship_type TEXT NOT NULL,        -- one_to_one, one_to_many, many_to_many
    parent_field TEXT NOT NULL,
    child_field TEXT NOT NULL,
    cascade_delete INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (child_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);
```

### **Verification:**
- [ ] builder.db created
- [ ] All 3 tables exist
- [ ] Indexes created
- [ ] Foreign keys working
- [ ] Can insert test data

---

## 🎨 TASK 13.2: Main Dashboard

**Time:** 1.5 hours  
**Lines:** ~350 lines  
**File:** `/admin/database-builder/index.php`

### **Dashboard Layout:**

```
┌────────────────────────────────────────────────────────────┐
│ 🗂️  Database Builder          [Tutorial] [Import] [+ New]  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  📊 YOUR TABLES (5)                                        │
│                                                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Customers│  │ Tickets  │  │ VIP List │  │ Products │  │
│  │ 127 rec  │  │ 34 rec   │  │ 3 rec    │  │ 89 rec   │  │
│  │ [Open]   │  │ [Open]   │  │ [Open]   │  │ [Open]   │  │
│  │ [Edit]   │  │ [Edit]   │  │ [Edit]   │  │ [Edit]   │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                            │
│  💡 NEW TO DATABASES? Start the 5-minute tutorial!        │
│  [▶️ Start Tutorial]                                       │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Grid of table cards
- [ ] Show record count
- [ ] Open button (view data)
- [ ] Edit button (edit structure)
- [ ] Delete button (with confirmation)
- [ ] Import CSV/Excel button
- [ ] Create new table button
- [ ] Tutorial link

### **Verification:**
- [ ] Dashboard loads
- [ ] Shows all tables
- [ ] Buttons functional
- [ ] Record counts accurate
- [ ] Theme colors apply

---

## 🔧 TASK 13.3: Table Designer Interface

**Time:** 2 hours  
**Lines:** ~500 lines  
**File:** `/admin/database-builder/designer.php`

### **Designer UI:**

```
┌────────────────────────────────────────────────────────────┐
│ ⬅️ Back     Table: "customers" - Edit Structure            │
├────────────────────────────────────────────────────────────┤
│                                                            │
│ BASIC INFO                                                  │
│ Display Name: [Customer Records_____________________]      │
│ Description:  [Store customer contact info___________]     │
│ Icon: [👥] Color: [🎨 #3b82f6]                            │
│                                                            │
│ ─────────────────────────────────────────────────────────  │
│                                                            │
│ FIELDS (5)                                [+ Add Field]     │
│                                                            │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ ☰ Name (Text) - Required                            │   │
│ │   [Edit] [Delete] ↕                                  │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ ☰ Email (Email) - Required, Unique                  │   │
│ │   [Edit] [Delete] ↕                                  │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ ☰ Phone (Phone)                                      │   │
│ │   [Edit] [Delete] ↕                                  │   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ [Save Structure] [Preview Table] [Cancel]                  │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Edit table metadata (name, description, icon, color)
- [ ] List all fields
- [ ] Drag to reorder fields
- [ ] Add new field button
- [ ] Edit field button
- [ ] Delete field button
- [ ] Save structure button
- [ ] Preview table button

### **Verification:**
- [ ] Can edit table name
- [ ] Can change icon/color
- [ ] Can reorder fields (drag-drop)
- [ ] All buttons work
- [ ] Saves to database

---

## 📝 TASK 13.4: Field Editor Modal

**Time:** 2 hours  
**Lines:** ~600 lines  
**File:** `/admin/database-builder/field-editor.php`

### **Support 15 Field Types:**

1. **TEXT** - Single line text
2. **TEXTAREA** - Multi-line text
3. **NUMBER** - Integer or decimal
4. **CURRENCY** - Money values
5. **DATE/TIME** - Dates and timestamps
6. **EMAIL** - Email addresses
7. **PHONE** - Phone numbers
8. **URL** - Website addresses
9. **DROPDOWN** - Select one from list
10. **CHECKBOX** - Yes/No
11. **RADIO** - Choose one option
12. **FILE** - File upload
13. **RATING** - Star ratings
14. **COLOR** - Color picker
15. **SIGNATURE** - Electronic signature

### **Field Editor UI:**

```
┌────────────────────────────────────────────┐
│ 📝 Add Field                                │
├────────────────────────────────────────────┤
│                                            │
│ Field Type: [Email ▼]                      │
│                                            │
│ Display Name: [Email Address___________]   │
│                                            │
│ Internal Name: customer_email (auto)       │
│                                            │
│ ☑️ Required field                          │
│ ☑️ Must be unique                          │
│                                            │
│ Placeholder: [email@example.com________]   │
│                                            │
│ Help Text: [Customer's primary email___]   │
│                                            │
│ Default Value: [_______________________]   │
│                                            │
│ VALIDATION RULES                            │
│ ☑️ RFC5322 email format                    │
│ ☑️ Auto-lowercase                          │
│ ☐ DNS check                                │
│                                            │
│ [Save Field] [Cancel]                       │
│                                            │
└────────────────────────────────────────────┘
```

### **Features:**
- [ ] Field type dropdown (15 types)
- [ ] Display name input
- [ ] Auto-generate internal name
- [ ] Required checkbox
- [ ] Unique checkbox
- [ ] Placeholder input
- [ ] Help text input
- [ ] Default value input
- [ ] Type-specific options (e.g., dropdown options)
- [ ] Validation rules
- [ ] Save/cancel buttons

### **Verification:**
- [ ] All 15 field types work
- [ ] Validation rules apply
- [ ] Options save correctly
- [ ] Can edit existing fields
- [ ] Can delete fields

---

## 🔗 TASK 13.5: Relationship Builder

**Time:** 1.5 hours  
**Lines:** ~400 lines  
**File:** `/admin/database-builder/relationships.php`

### **Relationship Types:**

1. **ONE-TO-ONE** - Customer has one profile
2. **ONE-TO-MANY** - Customer has many tickets
3. **MANY-TO-MANY** - Customers and products (needs junction table)

### **Relationship UI:**

```
┌────────────────────────────────────────────┐
│ 🔗 Create Relationship                      │
├────────────────────────────────────────────┤
│                                            │
│ Parent Table: [customers ▼]                │
│                                            │
│ Child Table:  [tickets ▼]                  │
│                                            │
│ Relationship Type:                          │
│ ⚪ ONE-TO-ONE                              │
│ 🔘 ONE-TO-MANY                             │
│ ⚪ MANY-TO-MANY                            │
│                                            │
│ Parent Field: [id ▼]                        │
│ Child Field:  [customer_id ▼]              │
│                                            │
│ ☑️ Delete children when parent deleted     │
│                                            │
│ [Create Relationship] [Cancel]              │
│                                            │
└────────────────────────────────────────────┘
```

### **Features:**
- [ ] Visual diagram of relationships
- [ ] Add relationship modal
- [ ] Choose relationship type
- [ ] Map parent/child fields
- [ ] Cascade delete option
- [ ] Edit/delete relationships
- [ ] Validation (prevent circular references)

### **Verification:**
- [ ] Can create relationships
- [ ] Visual diagram updates
- [ ] Foreign keys created in database
- [ ] Cascade delete works
- [ ] Can delete relationships

---

## 📊 TASK 13.6: Data Management Interface

**Time:** 2 hours  
**Lines:** ~600 lines  
**File:** `/admin/database-builder/data.php`

### **Spreadsheet-Like View:**

```
┌────────────────────────────────────────────────────────────┐
│ 📊 customers (127 records)      [+ Add] [Import] [Export]  │
├────────────────────────────────────────────────────────────┤
│ 🔍 [Search...] Status: [All ▼] Plan: [All ▼]              │
│                                                            │
│ ┌──┬────┬─────────────┬──────────────────┬──────────┬────┐│
│ │☑│ ID │ Name        │ Email            │ Phone    │Plan││
│ ├──┼────┼─────────────┼──────────────────┼──────────┼────┤│
│ │☐│  1 │ John Smith  │ john@example.com │ 555-1234 │Pro ││
│ │☐│  2 │ Sarah Jones │ sarah@ex.com     │ 555-5678 │VIP ││
│ │☐│  3 │ Mike Davis  │ mike@example.com │ 555-9012 │Pro ││
│ └──┴────┴─────────────┴──────────────────┴──────────┴────┘│
│                                                            │
│ Selected: 0    [Showing 1-50 of 127]    [◄] [1] [2] [►]   │
│                                                            │
│ BULK ACTIONS: [✏️ Edit] [🗑️ Delete] [📤 Export]           │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### **Features:**
- [ ] Spreadsheet-like grid view
- [ ] Inline editing (click cell to edit)
- [ ] Add new record button
- [ ] Search/filter records
- [ ] Sort by column (click header)
- [ ] Pagination
- [ ] Bulk select (checkboxes)
- [ ] Bulk actions (edit, delete, export)
- [ ] Export to CSV/Excel
- [ ] Import from CSV/Excel

### **Verification:**
- [ ] Can view all records
- [ ] Inline editing works
- [ ] Search/filter works
- [ ] Sorting works
- [ ] Can add records
- [ ] Can delete records
- [ ] Export works
- [ ] Import works

---

## 🔌 TASK 13.7: API Endpoints

**Time:** 1.5 hours  
**Lines:** ~500 lines  
**Files:** 6 API files

### **Create API Endpoints:**

**1. /api/builder/tables.php** (~150 lines)
- GET - List all tables
- GET /:id - Get table details
- POST - Create new table
- PUT /:id - Update table
- DELETE /:id - Delete table

**2. /api/builder/fields.php** (~150 lines)
- GET - List fields for table
- GET /:id - Get field details
- POST - Create new field
- PUT /:id - Update field
- DELETE /:id - Delete field
- POST /reorder - Reorder fields

**3. /api/builder/relationships.php** (~100 lines)
- GET - List all relationships
- POST - Create relationship
- DELETE /:id - Delete relationship

**4. /api/builder/data.php** (~150 lines)
- GET /:table - List records
- GET /:table/:id - Get record
- POST /:table - Create record
- PUT /:table/:id - Update record
- DELETE /:table/:id - Delete record
- POST /:table/bulk - Bulk operations

**5. /api/builder/import.php** (~50 lines)
- POST - Import CSV/Excel file
- Validate data
- Insert records
- Return results

**6. /api/builder/export.php** (~50 lines)
- GET /:table - Export table as CSV/Excel
- Support filters
- Return file download

### **Verification:**
- [ ] All endpoints respond
- [ ] CRUD operations work
- [ ] Validation works
- [ ] Error handling works
- [ ] Returns JSON properly

---

## 📚 TASK 13.8: Import/Export Functionality

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/admin/database-builder/import-export.php`

### **CSV Import:**
- [ ] File upload interface
- [ ] Parse CSV file
- [ ] Match columns to fields
- [ ] Validate data
- [ ] Show preview before import
- [ ] Insert records
- [ ] Show results (success/failures)

### **CSV Export:**
- [ ] Export all records
- [ ] Export selected records
- [ ] Export with filters
- [ ] Choose columns to include
- [ ] Download as CSV file

### **Excel Support:**
- [ ] Import .xlsx files
- [ ] Export .xlsx files
- [ ] Preserve formatting

### **Verification:**
- [ ] Can import CSV
- [ ] Can import Excel
- [ ] Can export CSV
- [ ] Can export Excel
- [ ] Data integrity maintained

---

## 🧪 TESTING CHECKLIST

### **Table Creation:**
- [ ] Can create new table
- [ ] Can add fields
- [ ] Can edit table structure
- [ ] Can delete table
- [ ] Table appears in dashboard

### **Field Management:**
- [ ] All 15 field types work
- [ ] Validation rules apply
- [ ] Required fields enforced
- [ ] Unique fields enforced
- [ ] Default values work

### **Relationships:**
- [ ] Can create one-to-one
- [ ] Can create one-to-many
- [ ] Can create many-to-many
- [ ] Foreign keys created
- [ ] Cascade delete works

### **Data Management:**
- [ ] Can add records
- [ ] Can edit records
- [ ] Can delete records
- [ ] Inline editing works
- [ ] Bulk operations work

### **Import/Export:**
- [ ] CSV import works
- [ ] Excel import works
- [ ] CSV export works
- [ ] Excel export works

### **Performance:**
- [ ] Tables with 1000+ records load fast
- [ ] Search is instant
- [ ] Pagination works smoothly
- [ ] No memory issues

---

## 🎨 DESIGN REQUIREMENTS

### **Use Database-Driven Themes:**
- [ ] Load colors from themes.db
- [ ] Apply theme to all UI elements
- [ ] No hardcoded colors

### **Responsive Design:**
- [ ] Works on desktop
- [ ] Works on tablet
- [ ] Works on mobile (limited)

### **User-Friendly:**
- [ ] Clear labels
- [ ] Help text/tooltips
- [ ] Confirmation dialogs
- [ ] Success/error messages
- [ ] Loading indicators

---

## 📦 FILE STRUCTURE

```
/admin/database-builder/
├── index.php (dashboard)
├── designer.php (table designer)
├── field-editor.php (field modal)
├── relationships.php (relationship builder)
├── data.php (data management)
├── import-export.php (import/export UI)
├── setup-builder.php (database setup)
├── api/
│   ├── tables.php
│   ├── fields.php
│   ├── relationships.php
│   ├── data.php
│   ├── import.php
│   └── export.php
├── user-tables/ (user-created .db files)
│   ├── customers.db
│   ├── tickets.db
│   └── ...
├── assets/
│   ├── css/builder.css
│   ├── js/builder.js
│   └── js/drag-drop.js
└── databases/
    └── builder.db
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] All files uploaded to server
- [ ] builder.db created and writable
- [ ] user-tables/ directory created and writable
- [ ] API endpoints accessible
- [ ] File permissions correct (755 directories, 644 files)
- [ ] Database files writable (666)
- [ ] Test on production server
- [ ] No errors in error_log

---

## 📊 SUMMARY

**Total Tasks:** 8 major tasks  
**Total Files:** 15+ files  
**Total Lines:** ~3,000 lines  
**Total Time:** 10-12 hours  

**Dependencies:**
- Part 1 (Database infrastructure) ✅
- Part 4 (Admin authentication) ✅
- Part 7 (Theme system) ✅

---

**END OF PART 13 CHECKLIST - DATABASE BUILDER**

---

## 🔄 CRITICAL UPDATES - JANUARY 20, 2026

**USER DECISION:** Database Builder = **DataForge** (FileMaker Pro Alternative)

**Key Requirements:**
1. Full database management tool
2. Visual table designer
3. Create/update/change ANY database
4. Template library with multiple styles
5. Template categories: Marketing, Email, VPN, Forms
6. Template styles: Basic, Formal, Executive

---

### **DATAFORGE SPECIFICATIONS:**

**What DataForge Does:**
- Create custom databases visually
- Design tables with drag-and-drop
- CRUD interface for all data
- Template library (100+ templates)
- Export/import databases
- FileMaker Pro-style functionality

**Template System:**

**Categories:**
1. **Marketing** (50+ templates)
   - Social media posts
   - Email campaigns
   - Ad copy
   - Press releases
   - Blog posts

2. **Email** (30+ templates)
   - Onboarding emails
   - Billing emails
   - Support emails
   - Retention emails
   - VIP emails

3. **VPN** (20+ templates)
   - WireGuard configs
   - Server setups
   - Port forwarding rules
   - Parental controls

4. **Forms** (58+ templates)
   - Contact forms
   - Support tickets
   - Survey forms
   - Order forms
   - Registration forms

**Style Variants (for each template):**
- **Basic** → Simple, clean, minimal
- **Formal** → Professional, structured
- **Executive** → Premium, polished

**Example:**
- Marketing → Social Post → Christmas Sale
  - Basic style (plain text)
  - Formal style (professional layout)
  - Executive style (premium graphics)

---

### **UPDATED TASK 13.1: Create DataForge Visual Designer**

**File:** `/database-builder/designer.php`
**Time:** 8-10 hours (increased)
**Lines:** ~1200 lines

**Interface Sections:**

**1. Table Designer:**
- Drag-and-drop field creator
- Field types: TEXT, INTEGER, REAL, BLOB, BOOLEAN, DATE, JSON
- Field properties: Name, Type, Required, Default, Unique
- Relationship builder (foreign keys)
- Index creator
- Save table schema

**2. Data Manager:**
- CRUD interface for records
- Grid view (spreadsheet-like)
- Form view (detailed)
- Filter/search
- Export to CSV/JSON
- Import from CSV/JSON

**3. Template Library:**
- Category browser (Marketing, Email, VPN, Forms)
- Style selector (Basic, Formal, Executive)
- Preview modal
- Insert template
- Customize template
- Save custom templates

**Example Template Structure:**
```json
{
  "id": "marketing_social_christmas",
  "category": "marketing",
  "subcategory": "social_media",
  "name": "Christmas Sale Post",
  "description": "Holiday sale announcement for social media",
  "styles": {
    "basic": {
      "content": "🎄 Christmas Sale! Get 50% off VPN plans...",
      "format": "text"
    },
    "formal": {
      "content": "Season's Greetings! We're pleased to announce...",
      "format": "text"
    },
    "executive": {
      "content": "Exclusive Holiday Offer for Our Valued Customers...",
      "format": "html",
      "template": "<div class='premium'>...</div>"
    }
  },
  "variables": ["discount_percent", "sale_end_date", "product_name"],
  "tags": ["holiday", "christmas", "sale", "social"]
}
```

**Database Schema for Templates:**
```sql
CREATE TABLE dataforge_templates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  template_key TEXT UNIQUE NOT NULL,
  category TEXT NOT NULL,
  subcategory TEXT,
  name TEXT NOT NULL,
  description TEXT,
  styles TEXT, -- JSON with basic/formal/executive
  variables TEXT, -- JSON array
  tags TEXT, -- JSON array
  preview_image TEXT,
  usage_count INTEGER DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

---

### **UPDATED TASK 13.2: Create Template Library**

**File:** `/database-builder/templates.php`
**Time:** 6-8 hours
**Lines:** ~800 lines

**Interface:**
- Category tabs (Marketing, Email, VPN, Forms)
- Template grid with previews
- Style selector dropdown
- Search/filter
- Preview modal
- Insert button
- Customize button

**Pre-Built Templates to Create (150+ total):**

**Marketing Templates (50):**
- Social media posts (Facebook, Twitter, LinkedIn, Instagram)
- Email campaigns (newsletters, promotions, announcements)
- Ad copy (Google Ads, Facebook Ads)
- Press releases
- Blog post templates

**Email Templates (30):**
- Onboarding (welcome, setup, follow-up)
- Billing (payment success, failed, reminder)
- Support (ticket received, resolved, satisfaction)
- Retention (cancellation survey, win-back)
- VIP (welcome package, premium features)

**VPN Templates (20):**
- WireGuard configs (device-specific)
- Server setup scripts
- Port forwarding rules
- Parental control schedules
- Network scanner configs

**Form Templates (58):**
- Contact forms
- Support tickets
- Survey forms
- Order forms
- Registration forms
- Feedback forms
- Quote requests
- Appointment booking

**Each template has 3 styles:**
- Basic (plain text, minimal)
- Formal (structured, professional)
- Executive (premium, polished)

---

### **UPDATED TASK 13.3: Template Style System**

**Purpose:** Allow users to choose between Basic, Formal, Executive for any template

**Implementation:**
```php
// Example: Load template with style
$template = $db->query("
  SELECT * FROM dataforge_templates 
  WHERE template_key = ?
", ['marketing_social_christmas'])->fetch();

$styles = json_decode($template['styles'], true);

// User selects style
$selectedStyle = $_POST['style']; // 'basic', 'formal', or 'executive'
$content = $styles[$selectedStyle]['content'];

// Replace variables
$variables = json_decode($template['variables'], true);
foreach ($variables as $var) {
  $content = str_replace('{' . $var . '}', $_POST[$var], $content);
}

// Output
echo $content;
```

**Style Characteristics:**

**Basic:**
- Plain text
- Minimal formatting
- Direct/simple language
- No graphics
- Fast to use

**Formal:**
- Professional structure
- Business-appropriate
- Headers/sections
- Simple graphics
- Corporate tone

**Executive:**
- Premium presentation
- Polished design
- High-end graphics
- Sophisticated language
- Impressive visuals

---

### **UPDATED Part 13 Summary:**

**Original:** Database Builder (simple)
**Updated:** DataForge - FileMaker Pro Alternative

**Features:**
- Visual table designer
- CRUD interface
- Template library (150+ templates)
- 3 style variants (Basic, Formal, Executive)
- Export/import
- Relationship builder
- Index management

**Time Estimate:**
- Original: 6-8 hours
- Updated: 15-20 hours (full FileMaker alternative)

**Files:**
- /database-builder/designer.php
- /database-builder/data-manager.php
- /database-builder/templates.php
- /database-builder/api/*.php (10+ API endpoints)
- /databases/dataforge.db (separate database)

---


---

## 📚 TASK 13.9: CREATE TEMPLATE LIBRARY (150+ TEMPLATES)

**Time:** 4 hours  
**Lines:** ~1,000 lines  
**Files:** Multiple template JSON files

**CRITICAL USER DECISION:**
DataForge must include 150+ pre-built templates across 4 categories, each with 3 style variants (Basic, Formal, Executive).

**Template Categories:**

### **Category 1: Marketing Templates (50 templates)**

**Social Media Posts (10):**
1. Facebook Product Launch
2. Twitter Announcement
3. LinkedIn Company Update
4. Instagram Story Promo
5. TikTok Video Script
6. Pinterest Pin Description
7. YouTube Video Description
8. Reddit Post Format
9. Discord Community Update
10. Threads Engagement Post

**Email Campaigns (10):**
1. Newsletter Monthly
2. Product Announcement
3. Sale/Promotion Alert
4. Event Invitation
5. Survey Request
6. Testimonial Request
7. Re-engagement Campaign
8. Abandoned Cart Recovery
9. Birthday/Anniversary
10. Welcome Series

**Ad Copy (10):**
1. Google Search Ad
2. Facebook Ad
3. Instagram Ad
4. LinkedIn Sponsored Content
5. Twitter Promoted Tweet
6. YouTube Pre-Roll
7. Display Banner Text
8. Native Advertising
9. Retargeting Ad
10. Local Service Ad

**Press Releases (10):**
1. Product Launch
2. Company Milestone
3. Partnership Announcement
4. Executive Appointment
5. Award Recognition
6. Event Coverage
7. Crisis Response
8. Financial Results
9. Merger/Acquisition
10. Charity Initiative

**Blog Posts (10):**
1. How-To Guide
2. Listicle Article
3. Case Study
4. Industry News
5. Product Review
6. Company Culture
7. Expert Interview
8. Trend Analysis
9. Tutorial Series
10. FAQ Compilation

---

### **Category 2: Email Templates (30 templates)**

**Customer Onboarding (5):**
1. Welcome Email - New Customer
2. Account Setup Guide
3. First Purchase Thank You
4. Product Tutorial Series
5. 30-Day Check-in

**Billing & Payments (5):**
1. Payment Receipt
2. Payment Failed Notification
3. Subscription Renewal Reminder
4. Refund Processed
5. Payment Method Update

**Support Communications (5):**
1. Ticket Received Confirmation
2. Ticket Resolved Notification
3. Satisfaction Survey
4. Technical Support Follow-up
5. Knowledge Base Recommendation

**Retention & Re-engagement (5):**
1. Inactive User Re-engagement
2. Cancellation Feedback Request
3. Win-Back Offer
4. Loyalty Reward
5. Upgrade Opportunity

**Transactional Emails (5):**
1. Order Confirmation
2. Shipping Notification
3. Delivery Confirmation
4. Return Authorization
5. Account Password Reset

**Internal Communications (5):**
1. Team Meeting Invitation
2. Project Status Update
3. Policy Change Notification
4. Employee Recognition
5. Department Newsletter

---

### **Category 3: VPN Business Templates (20 templates)**

**Device Configuration (5):**
1. WireGuard Config Generator
2. Port Forwarding Rules
3. Parental Control Schedule
4. Gaming Console Setup
5. Camera RTSP URLs

**Server Management (5):**
1. Server Status Report
2. Bandwidth Usage Log
3. Connection History
4. IP Assignment Tracker
5. Maintenance Schedule

**Customer Management (5):**
1. User Account Details
2. Subscription Tracking
3. VIP User Registry
4. Trial Account Monitor
5. Payment History

**Technical Documentation (5):**
1. Setup Instructions
2. Troubleshooting Guide
3. API Documentation
4. Security Audit Log
5. Change Log

---

### **Category 4: Form Templates (58 templates)**

**Contact & Inquiry (10):**
1. Basic Contact Form
2. Quote Request
3. Partnership Inquiry
4. Media Contact
5. Job Application
6. Speaker Request
7. Sponsorship Inquiry
8. Feedback Form
9. Complaint Form
10. General Inquiry

**Support & Service (10):**
1. Technical Support Ticket
2. Billing Issue Report
3. Bug Report
4. Feature Request
5. Account Recovery
6. Service Cancellation
7. Refund Request
8. Change Request
9. Escalation Form
10. Priority Support

**Registration & Signup (10):**
1. User Registration
2. Event Registration
3. Newsletter Signup
4. Beta Program Signup
5. Waitlist Registration
6. Membership Application
7. Vendor Registration
8. Partner Application
9. Volunteer Signup
10. Course Enrollment

**Surveys & Feedback (10):**
1. Customer Satisfaction (NPS)
2. Post-Purchase Survey
3. Exit Survey
4. Employee Satisfaction
5. Product Feedback
6. Service Quality
7. Website Feedback
8. Event Feedback
9. Training Evaluation
10. Market Research

**Business Operations (10):**
1. Leave Request
2. Expense Report
3. Purchase Requisition
4. Time Off Request
5. Travel Authorization
6. Equipment Request
7. IT Support Ticket
8. Facilities Request
9. Vendor Invoice
10. Project Proposal

**Legal & Compliance (8):**
1. Privacy Policy Consent
2. Terms of Service Acceptance
3. Cookie Consent
4. GDPR Data Request
5. Copyright Claim
6. Legal Hold Notice
7. Confidentiality Agreement
8. Age Verification

---

## 🎨 TASK 13.10: Implement Style Variants (Basic, Formal, Executive)

**Time:** 2 hours  
**Lines:** ~500 lines  
**File:** `/admin/database-builder/templates/style-variants.php`

**Each template has 3 style variants:**

### **Basic Style**
- Simple layout
- Minimal formatting
- Plain text emphasis
- Basic colors
- Standard fonts
- Clean, no-frills
- Fast to read
- Mobile-friendly
- **Use case:** Internal notes, quick updates, casual communication

**Example - Basic Welcome Email:**
```
Subject: Welcome to TrueVault VPN

Hi [Name],

Thanks for signing up! Your account is ready.

Your login details:
- Email: [Email]
- Password: [Temporary Password]

Click here to get started: [Link]

Questions? Reply to this email.

Thanks,
The TrueVault Team
```

### **Formal Style**
- Professional layout
- Structured formatting
- Organized sections
- Business colors
- Professional fonts
- Headers/footers
- Logo placement
- **Use case:** Client communications, official correspondence, business documents

**Example - Formal Welcome Email:**
```
Subject: Welcome to TrueVault VPN - Your Account is Active

Dear [Title] [Last Name],

Thank you for choosing TrueVault VPN as your digital security partner.

Account Information:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Email Address: [Email]
Account Type: [Plan Name]
Activation Date: [Date]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Next Steps:
1. Set your secure password
2. Download configuration files
3. Connect your devices

Should you require any assistance, our support team is available at:
admin@the-truth-publishing.com

Best regards,

The TrueVault VPN Team
Connection Point Systems Inc.
```

### **Executive Style**
- Premium design
- Rich formatting
- Visual hierarchy
- Luxury colors (gold, deep blue)
- Designer fonts
- Graphics/icons
- Signature blocks
- Brand imagery
- **Use case:** VIP clients, executive communications, high-value accounts

**Example - Executive Welcome Email:**
```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🛡️  TRUEVAULT VPN - EXECUTIVE ACCESS  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

Dear [Title] [Last Name],

Welcome to an exclusive tier of digital protection.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ Your Executive Account Details

   Account Tier: EXECUTIVE
   Membership ID: #[ID]
   Activation: [Date]
   
   Dedicated Support: Priority 24/7
   Personal Account Manager: [Name]
   Direct Line: [Phone]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Your Exclusive Benefits:

▸ Dedicated VPN Server
▸ Unlimited Devices
▸ Priority Routing
▸ White-Glove Support
▸ Custom Configuration

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Your personal account manager will contact you
within 24 hours to ensure seamless onboarding.

Sincerely,

[Account Manager Name]
Executive Account Services
TrueVault VPN | Connection Point Systems Inc.

┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
```

---

## 📂 TASK 13.11: Template File Structure

**Organization:**

```
/databases/templates/
├── marketing/
│   ├── social_media/
│   │   ├── facebook_launch_basic.json
│   │   ├── facebook_launch_formal.json
│   │   ├── facebook_launch_executive.json
│   │   └── ... (all 10 social templates x 3 styles = 30 files)
│   ├── email_campaigns/
│   │   └── ... (10 templates x 3 styles = 30 files)
│   ├── ad_copy/
│   │   └── ... (10 templates x 3 styles = 30 files)
│   ├── press_releases/
│   │   └── ... (10 templates x 3 styles = 30 files)
│   └── blog_posts/
│       └── ... (10 templates x 3 styles = 30 files)
│
├── email/
│   ├── onboarding/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── billing/
│   ├── support/
│   ├── retention/
│   ├── transactional/
│   └── internal/
│
├── vpn/
│   ├── device_config/
│   ├── server_management/
│   ├── customer_management/
│   └── documentation/
│
└── forms/
    ├── contact/
    ├── support/
    ├── registration/
    ├── surveys/
    ├── business_ops/
    └── legal/
```

**Template JSON Format:**
```json
{
  "template_id": "welcome_email_basic",
  "display_name": "Welcome Email",
  "category": "email",
  "subcategory": "onboarding",
  "style": "basic",
  "description": "Simple welcome email for new customers",
  "variables": [
    {"name": "first_name", "type": "text", "required": true},
    {"name": "email", "type": "email", "required": true},
    {"name": "temp_password", "type": "text", "required": false},
    {"name": "login_link", "type": "url", "required": true}
  ],
  "subject": "Welcome to TrueVault VPN",
  "content": "Hi {first_name}...",
  "preview_text": "Thanks for signing up! Your account is ready.",
  "tags": ["welcome", "onboarding", "new customer"]
}
```

---

## 🔍 TASK 13.12: Template Selection Interface

**Time:** 1.5 hours  
**Lines:** ~400 lines  
**File:** `/admin/database-builder/template-selector.php`

**Template browser with:**
- Category tabs (Marketing, Email, VPN, Forms)
- Subcategory filters
- Style variant toggle (Basic, Formal, Executive)
- Search by keyword
- Preview modal
- "Use Template" button
- Variables auto-population

**Verification:**
- [ ] All 150+ templates created
- [ ] Each has 3 style variants
- [ ] JSON format correct
- [ ] Template selector works
- [ ] Can search templates
- [ ] Can filter by category
- [ ] Can preview templates
- [ ] Variables populate correctly

---

## 📊 UPDATED TIME ESTIMATE

**Part 13 Total:** 20-25 hours (increased from 10-12 hours)

**Breakdown:**
- Tasks 13.1-13.8: Original database builder (10-12 hrs)
- Task 13.9: Create 150+ templates (4 hrs)
- Task 13.10: Style variants (2 hrs)
- Task 13.11: Template files (2 hrs)
- Task 13.12: Template selector (1.5 hrs)
- Testing & refinement (2-3 hrs)

**Total Lines:** ~6,000 lines (increased from ~3,000)

**Total Templates:** 158 base templates x 3 styles = **474 template files**

---

## 🎯 CRITICAL SUCCESS FACTORS

✅ 150+ templates (NOT just basic builder!)  
✅ 3 style variants each (Basic, Formal, Executive)  
✅ 4 categories (Marketing, Email, VPN, Forms)  
✅ Visual template selector  
✅ Search & filter functionality  
✅ Variable auto-population  
✅ Export/import templates  
✅ FileMaker Pro alternative  

**THIS IS HOW PART 13 MUST BE BUILT!**

---

**END OF PART 13 UPDATES**

