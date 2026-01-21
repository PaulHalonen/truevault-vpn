# SECTION 16: DATABASE BUILDER SYSTEM

**Created:** January 14, 2026  
**Status:** Complete Specification  
**Priority:** HIGH - New Feature for TruthVault VPN  
**Complexity:** MEDIUM-HIGH  

---

## 📋 TABLE OF CONTENTS

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [Visual Table Designer](#visual-table-designer)
5. [Field Types & Properties](#field-types-properties)
6. [Relationship Builder](#relationship-builder)
7. [Data Management](#data-management)
8. [User Interface](#user-interface)
9. [API Endpoints](#api-endpoints)
10. [Security & Permissions](#security-permissions)
11. [Import/Export](#import-export)
12. [Tutorial System](#tutorial-system)
13. [Implementation Guide](#implementation-guide)

---

## 🎯 OVERVIEW

### **Purpose**
A complete visual database builder designed for NON-TECHNICAL USERS who have never used databases before. Think FileMaker Pro meets Airtable - but specifically for TruthVault VPN's needs.

### **Why This Matters**
Kah-Len (the owner) needs to:
- Manage customer data
- Track support tickets
- Store port forwarding configurations
- Maintain VIP lists
- Log network scans
- All WITHOUT writing SQL or understanding database concepts!

### **Core Principle**
**"If you can use Excel, you can build databases"**

### **Key Features**
✅ Drag-and-drop field creation  
✅ Visual relationship designer  
✅ 15+ field types with validation  
✅ Spreadsheet-like data editing  
✅ CSV/Excel import/export  
✅ Built-in tutorials (5-minute lessons)  
✅ No coding required  

---

## 🏗️ SYSTEM ARCHITECTURE

### **Technology Stack**

**Backend:**
- Language: PHP 8.2+
- Database: SQLite (builder.db)
- File Storage: JSON metadata
- Path: `/home/eybn38fwc55z/public_html/vpn.the-truth-publishing.com/admin/database-builder/`

**Frontend:**
- HTML5 + CSS3 (database-driven themes)
- JavaScript (vanilla - no frameworks)
- Drag-and-drop: HTML5 native API
- AJAX: Fetch API for real-time updates

**Storage Structure:**
```
/admin/database-builder/
├── index.php (main dashboard)
├── api/
│   ├── tables.php (CRUD for table definitions)
│   ├── fields.php (CRUD for field definitions)
│   ├── relationships.php (manage table relationships)
│   ├── data.php (CRUD for actual data)
│   ├── import.php (CSV/Excel import)
│   └── export.php (CSV/Excel export)
├── builder.db (SQLite - stores all metadata)
├── user-tables/ (directory for user-created table data)
│   ├── customers.db
│   ├── tickets.db
│   └── [table_name].db
├── assets/
│   ├── css/builder.css (database-driven)
│   ├── js/builder.js
│   └── js/drag-drop.js
└── tutorials/
    ├── lesson1.json
    ├── lesson2.json
    └── ...
```

### **Data Flow**

```
USER ACTION
    ↓
[Visual Interface] (drag field, click save)
    ↓
[JavaScript Handler] (validate, build JSON)
    ↓
[AJAX Request] → [PHP API Endpoint]
    ↓
[SQLite Operations] (save metadata to builder.db)
    ↓
[Response JSON] → [Update UI]
    ↓
[Real-Time Preview] (show changes immediately)
```

### **Design Principles**

1. **Forgiving Interface**
   - Can't break things by clicking around
   - Undo/Redo on all operations
   - Confirm before deleting

2. **Instant Feedback**
   - Real-time preview of tables
   - Live validation messages
   - Visual success/error indicators

3. **Progressive Disclosure**
   - Start simple (just add fields)
   - Advanced features hidden until needed
   - Tutorials guide to complex features

4. **Database-Driven Everything**
   - Table definitions stored in builder.db
   - User data stored in separate .db files
   - Themes/colors from admin settings

---

## 💾 DATABASE SCHEMA

### **builder.db Structure**

**This database stores METADATA about user-created tables, not actual data!**

```sql
-- Main database file: builder.db
-- Location: /admin/database-builder/builder.db

-- ========================================
-- TABLE 1: Custom Tables Registry
-- ========================================
CREATE TABLE IF NOT EXISTS custom_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,        -- Internal name: "customers"
    display_name TEXT NOT NULL,             -- User-friendly: "Customer Records"
    description TEXT,                       -- Optional description
    icon TEXT DEFAULT 'table',              -- Icon name for UI
    color TEXT DEFAULT '#3b82f6',           -- Theme color
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,                     -- Admin user ID
    is_system INTEGER DEFAULT 0,            -- 0=user created, 1=system table
    record_count INTEGER DEFAULT 0,         -- Cached count
    status TEXT DEFAULT 'active',           -- active, archived, deleted
    settings TEXT                           -- JSON: permissions, views, etc.
);

-- Index for fast lookups
CREATE INDEX idx_table_name ON custom_tables(table_name);
CREATE INDEX idx_status ON custom_tables(status);

-- ========================================
-- TABLE 2: Field Definitions
-- ========================================
CREATE TABLE IF NOT EXISTS custom_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,              -- FK to custom_tables
    field_name TEXT NOT NULL,               -- Internal: "customer_email"
    display_name TEXT NOT NULL,             -- User-friendly: "Email Address"
    field_type TEXT NOT NULL,               -- text, email, number, date, etc.
    sort_order INTEGER DEFAULT 0,           -- Display order in forms
    is_required INTEGER DEFAULT 0,          -- 1=required, 0=optional
    is_unique INTEGER DEFAULT 0,            -- 1=must be unique
    default_value TEXT,                     -- Default value for new records
    validation_rules TEXT,                  -- JSON: regex, min/max, etc.
    help_text TEXT,                         -- Tooltip text
    placeholder TEXT,                       -- Input placeholder
    options TEXT,                           -- JSON: for dropdown/radio
    settings TEXT,                          -- JSON: type-specific settings
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_table_fields ON custom_fields(table_id);
CREATE INDEX idx_field_name ON custom_fields(field_name);

-- ========================================
-- TABLE 3: Table Relationships
-- ========================================
CREATE TABLE IF NOT EXISTS table_relationships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_table_id INTEGER NOT NULL,       -- FK to custom_tables
    child_table_id INTEGER NOT NULL,        -- FK to custom_tables
    relationship_type TEXT NOT NULL,        -- one_to_one, one_to_many, many_to_many
    parent_field TEXT NOT NULL,             -- Field name in parent table
    child_field TEXT NOT NULL,              -- Field name in child table
    cascade_delete INTEGER DEFAULT 0,       -- Delete children when parent deleted
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (child_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);

-- Prevent duplicate relationships
CREATE UNIQUE INDEX idx_relationship 
ON table_relationships(parent_table_id, child_table_id, parent_field, child_field);
```

### **User Table Structure (Example)**

**Each user-created table gets its own .db file!**

```sql
-- Example: /admin/database-builder/user-tables/customers.db
-- This is created dynamically when user creates "customers" table

CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- User-defined fields (based on custom_fields table)
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    signup_date TEXT,
    plan_name TEXT,
    status TEXT DEFAULT 'active',
    
    -- System fields (always added)
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,                     -- Admin user who created record
    modified_by INTEGER                     -- Last admin who modified
);

-- Indexes created automatically based on field settings
CREATE INDEX idx_customers_email ON customers(email);
CREATE INDEX idx_customers_status ON customers(status);
```

### **Why Separate Database Files?**

1. **Portability**: Each table is independent
2. **Performance**: No giant single database
3. **Backup**: Can backup individual tables
4. **Transfer**: Easy to move to new owner
5. **Isolation**: Corruption in one table doesn't affect others

---

## 🎨 VISUAL TABLE DESIGNER

### **Main Dashboard**

```
┌────────────────────────────────────────────────────────────────────┐
│ 🗂️  Database Builder                    [Tutorial] [Import] [New]  │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  📊 YOUR TABLES (5)                                                │
│                                                                    │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐│
│  │ 👥 Customers     │  │ 🎫 Tickets       │  │ 🔐 VIP List      ││
│  │ 127 records      │  │ 34 records       │  │ 3 records        ││
│  │ [Open] [Edit]    │  │ [Open] [Edit]    │  │ [Open] [Edit]    ││
│  └──────────────────┘  └──────────────────┘  └──────────────────┘│
│                                                                    │
│  ┌──────────────────┐  ┌──────────────────┐                       │
│  │ 🌐 Port Fwd      │  │ 📡 Scans         │                       │
│  │ 89 records       │  │ 12 records       │                       │
│  │ [Open] [Edit]    │  │ [Open] [Edit]    │                       │
│  └──────────────────┘  └──────────────────┘                       │
│                                                                    │
│  💡 NEW TO DATABASES? Start the 5-minute tutorial!                │
│  [▶️ Start Tutorial]                                               │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

### **Table Designer Interface**

```
┌────────────────────────────────────────────────────────────────────┐
│ ⬅️ Back to Dashboard    Table: "customers" - Edit Structure        │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  📝 BASIC INFO                                                     │
│  ┌────────────────────────────────────────────────────────────────┐│
│  │ Display Name: [Customer Records________________]              ││
│  │ Icon: [👥] Color: [🎨 Blue]                                   ││
│  │ Description: [Store customer contact and account info_______] ││
│  └────────────────────────────────────────────────────────────────┘│
│                                                                    │
│  🔧 FIELDS (6 fields)                    [+ Add Field]             │
│  ┌────────────────────────────────────────────────────────────────┐│
│  │                                                                ││
│  │  1️⃣ [=] Name                                          [↑][↓][x]││
│  │     Type: Text  •  Required  •  Max 100 chars                 ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  │  2️⃣ [=] Email Address                                 [↑][↓][x]││
│  │     Type: Email  •  Required  •  Unique                       ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  │  3️⃣ [=] Phone Number                                  [↑][↓][x]││
│  │     Type: Phone  •  Optional  •  US Format                    ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  │  4️⃣ [=] Signup Date                                   [↑][↓][x]││
│  │     Type: Date  •  Required  •  Default: Today                ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  │  5️⃣ [=] Plan Name                                     [↑][↓][x]││
│  │     Type: Dropdown  •  Options: Basic, Pro, VIP               ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  │  6️⃣ [=] Status                                        [↑][↓][x]││
│  │     Type: Dropdown  •  Options: Active, Suspended, Canceled   ││
│  │     [✏️ Edit Properties]                                       ││
│  │                                                                ││
│  └────────────────────────────────────────────────────────────────┘│
│                                                                    │
│  [Preview Table] [Save Changes] [Cancel]                           │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

### **Add Field Dialog**

```
┌────────────────────────────────────────────────┐
│ ➕ Add New Field                                │
├────────────────────────────────────────────────┤
│                                                │
│ STEP 1: Choose Field Type                      │
│                                                │
│ 📝 TEXT                                        │
│ Single line of text (names, titles, etc.)      │
│                                                │
│ 📋 TEXT AREA                                   │
│ Multiple lines (descriptions, notes)           │
│                                                │
│ 🔢 NUMBER                                      │
│ Integers or decimals                           │
│                                                │
│ 📅 DATE/TIME                                   │
│ Calendar picker                                │
│                                                │
│ 📧 EMAIL                                       │
│ Email with validation                          │
│                                                │
│ 📱 PHONE                                       │
│ Phone number with formatting                   │
│                                                │
│ 🔗 URL                                         │
│ Website address                                │
│                                                │
│ 📋 DROPDOWN                                    │
│ Choose from predefined list                    │
│                                                │
│ ☑️ CHECKBOX                                   │
│ Yes/No or True/False                           │
│                                                │
│ 🔘 RADIO BUTTONS                               │
│ Choose one from multiple options               │
│                                                │
│ 💰 CURRENCY                                    │
│ Money with $ formatting                        │
│                                                │
│ ⭐ RATING                                      │
│ Star rating (1-5)                              │
│                                                │
│ 🎨 COLOR PICKER                                │
│ Choose a color                                 │
│                                                │
│ 📎 FILE UPLOAD                                 │
│ Upload documents, images                       │
│                                                │
│ ✍️ SIGNATURE                                  │
│ Draw signature                                 │
│                                                │
│ [Cancel]                                       │
└────────────────────────────────────────────────┘
```

### **Field Properties Editor**

```
┌────────────────────────────────────────────────┐
│ ✏️ Edit Field: "Email Address"                 │
├────────────────────────────────────────────────┤
│                                                │
│ 📋 BASIC SETTINGS                              │
│ ┌────────────────────────────────────────────┐ │
│ │ Display Name: [Email Address______________]│ │
│ │ Internal Name: email (auto-generated)      │ │
│ │ Field Type: Email (✅ validated)           │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ ⚙️ VALIDATION                                  │
│ ┌────────────────────────────────────────────┐ │
│ │ ☑️ Required Field                          │ │
│ │ ☑️ Must Be Unique                          │ │
│ │ ☐ Read Only                                │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ 💬 USER INTERFACE                              │
│ ┌────────────────────────────────────────────┐ │
│ │ Placeholder: [Enter your email_____________]│ │
│ │ Help Text: [We'll never share your email__]│ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ 📊 DEFAULT VALUE                               │
│ ┌────────────────────────────────────────────┐ │
│ │ Default: [_________________________________]│ │
│ │ (Leave blank for none)                     │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ [Save] [Cancel]                                │
└────────────────────────────────────────────────┘
```

---

## 🔧 FIELD TYPES & PROPERTIES

### **Complete Field Type Reference**

#### **1. TEXT (Single Line)**

**Use For:** Names, titles, short descriptions

**Properties:**
```json
{
  "type": "text",
  "min_length": 0,
  "max_length": 255,
  "pattern": "regex pattern",
  "placeholder": "Enter text here",
  "default_value": "",
  "allow_html": false,
  "trim_whitespace": true
}
```

**Validation:**
- Min/max length
- Regular expression pattern
- Required/optional
- Unique constraint

**Example Use Cases:**
- Customer Name
- Product Title
- Address Line 1
- Company Name

---

#### **2. TEXT AREA (Multiple Lines)**

**Use For:** Long descriptions, notes, comments

**Properties:**
```json
{
  "type": "textarea",
  "min_length": 0,
  "max_length": 5000,
  "rows": 5,
  "cols": 50,
  "placeholder": "Enter details here",
  "allow_html": false,
  "rich_text": false
}
```

**Validation:**
- Character count limits
- Optional HTML stripping
- Line break handling

**Example Use Cases:**
- Support Ticket Description
- Product Description
- Customer Notes
- Complaint Details

---

#### **3. NUMBER (Integer or Decimal)**

**Use For:** Quantities, prices, IDs

**Properties:**
```json
{
  "type": "number",
  "number_type": "integer|decimal",
  "min_value": null,
  "max_value": null,
  "decimal_places": 2,
  "step": 1,
  "placeholder": "0",
  "default_value": null,
  "thousands_separator": true
}
```

**Validation:**
- Min/max value range
- Integer vs decimal
- Decimal places
- Positive/negative

**Example Use Cases:**
- Customer ID
- Quantity Ordered
- Port Number
- Age

---

#### **4. CURRENCY**

**Use For:** Money values

**Properties:**
```json
{
  "type": "currency",
  "currency_code": "USD",
  "symbol": "$",
  "decimal_places": 2,
  "min_value": 0,
  "max_value": null,
  "thousands_separator": true,
  "symbol_position": "before"
}
```

**Display Format:**
- $1,234.56 (US)
- €1.234,56 (EU)
- £1,234.56 (UK)

**Example Use Cases:**
- Product Price
- Invoice Amount
- Refund Amount
- Account Balance

---

#### **5. DATE/TIME**

**Use For:** Dates, timestamps, schedules

**Properties:**
```json
{
  "type": "datetime",
  "datetime_type": "date|time|datetime",
  "format": "YYYY-MM-DD",
  "min_date": null,
  "max_date": null,
  "default_value": "today|now|custom",
  "include_time": false,
  "timezone": "America/Chicago"
}
```

**Display Formats:**
- Date: 2026-01-14
- Time: 14:30:00
- DateTime: 2026-01-14 14:30:00

**Example Use Cases:**
- Signup Date
- Last Login
- Ticket Created At
- Appointment Time

---

#### **6. EMAIL**

**Use For:** Email addresses

**Properties:**
```json
{
  "type": "email",
  "validation": "RFC5322",
  "allow_multiple": false,
  "separator": ",",
  "dns_check": false,
  "lowercase": true,
  "placeholder": "email@example.com"
}
```

**Validation:**
- RFC5322 email format
- Optional DNS check
- Duplicate detection
- Auto-lowercase

**Example Use Cases:**
- Customer Email
- Support Email
- Billing Email
- CC Recipients

---

#### **7. PHONE**

**Use For:** Phone numbers

**Properties:**
```json
{
  "type": "phone",
  "format": "US|international",
  "country_code": "default",
  "allow_extension": true,
  "auto_format": true,
  "validation": "E.164",
  "placeholder": "(555) 123-4567"
}
```

**Display Formats:**
- US: (555) 123-4567
- International: +1-555-123-4567
- Extension: (555) 123-4567 x890

**Example Use Cases:**
- Customer Phone
- Emergency Contact
- Business Phone
- Mobile Number

---

#### **8. URL**

**Use For:** Website addresses

**Properties:**
```json
{
  "type": "url",
  "require_protocol": true,
  "allowed_protocols": ["http", "https"],
  "auto_add_protocol": true,
  "check_exists": false,
  "placeholder": "https://example.com"
}
```

**Validation:**
- Valid URL format
- Protocol required (http/https)
- Optional: Check if URL exists
- Auto-add https:// if missing

**Example Use Cases:**
- Company Website
- Profile Picture URL
- Documentation Link
- Social Media Profile

---

#### **9. DROPDOWN (Select One)**

**Use For:** Predefined choice lists

**Properties:**
```json
{
  "type": "dropdown",
  "options": [
    {"value": "basic", "label": "Basic Plan"},
    {"value": "pro", "label": "Pro Plan"},
    {"value": "vip", "label": "VIP Plan"}
  ],
  "allow_custom": false,
  "default_value": null,
  "placeholder": "-- Select --",
  "searchable": true
}
```

**Features:**
- Predefined options
- Optional search/filter
- Custom value entry (optional)
- Multi-select variation available

**Example Use Cases:**
- Plan Selection
- Status (Active/Suspended/Canceled)
- Priority (Low/Medium/High)
- Country Selection

---

#### **10. CHECKBOX (Yes/No)**

**Use For:** Boolean values, agreements

**Properties:**
```json
{
  "type": "checkbox",
  "label": "I agree to terms",
  "checked_value": 1,
  "unchecked_value": 0,
  "default_value": 0,
  "inline_label": true
}
```

**Validation:**
- Required to be checked (for agreements)
- Custom checked/unchecked values
- Visual styling

**Example Use Cases:**
- Terms Accepted
- Email Opt-In
- Active/Inactive Status
- Feature Enabled

---

#### **11. RADIO BUTTONS (Choose One)**

**Use For:** Exclusive choices

**Properties:**
```json
{
  "type": "radio",
  "options": [
    {"value": "male", "label": "Male"},
    {"value": "female", "label": "Female"},
    {"value": "other", "label": "Other"}
  ],
  "default_value": null,
  "layout": "vertical|horizontal|inline"
}
```

**Features:**
- Only one selection allowed
- Visual button style
- Layout options

**Example Use Cases:**
- Gender Selection
- Shipping Method
- Payment Type
- Communication Preference

---

#### **12. FILE UPLOAD**

**Use For:** Documents, images, attachments

**Properties:**
```json
{
  "type": "file",
  "allowed_types": [".pdf", ".jpg", ".png", ".docx"],
  "max_size_mb": 10,
  "max_files": 5,
  "storage_path": "/uploads/",
  "generate_thumbnail": true,
  "virus_scan": false
}
```

**Features:**
- Multiple file upload
- File type restrictions
- Size limits
- Thumbnail generation (images)
- Virus scanning (optional)

**Example Use Cases:**
- Profile Picture
- Document Upload
- Invoice Attachment
- Support Ticket Screenshot

---

#### **13. RATING (Stars)**

**Use For:** Ratings, scores

**Properties:**
```json
{
  "type": "rating",
  "max_stars": 5,
  "allow_half": false,
  "default_value": 0,
  "icon": "star",
  "color": "#FFD700",
  "size": "medium"
}
```

**Display:**
- ⭐⭐⭐⭐⭐ (5 stars)
- ⭐⭐⭐☆☆ (3 of 5)
- ⭐⭐⭐⭐½ (4.5 stars - if half allowed)

**Example Use Cases:**
- Customer Satisfaction
- Product Rating
- Service Quality
- Support Response Rating

---

#### **14. COLOR PICKER**

**Use For:** Color selection

**Properties:**
```json
{
  "type": "color",
  "format": "hex|rgb|hsl",
  "allow_alpha": true,
  "default_value": "#3b82f6",
  "show_swatches": true,
  "preset_colors": ["#ff0000", "#00ff00", "#0000ff"]
}
```

**Features:**
- Visual color picker
- Multiple formats (hex/rgb/hsl)
- Alpha channel (transparency)
- Preset color swatches

**Example Use Cases:**
- Brand Color
- Theme Color
- Label Color
- Highlight Color

---

#### **15. SIGNATURE**

**Use For:** Electronic signatures

**Properties:**
```json
{
  "type": "signature",
  "canvas_width": 400,
  "canvas_height": 200,
  "pen_color": "#000000",
  "background_color": "#ffffff",
  "save_format": "png|svg",
  "required": true
}
```

**Features:**
- Draw with mouse/touch
- Clear and redraw
- Save as image
- Timestamped

**Example Use Cases:**
- Contract Signature
- Terms Agreement
- Delivery Confirmation
- Authorization Signature

---

## 🔗 RELATIONSHIP BUILDER

### **Visual Relationship Designer**

```
┌────────────────────────────────────────────────────────────────────┐
│ 🔗 Table Relationships                                              │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  VISUAL DIAGRAM                                                    │
│                                                                    │
│  ┌──────────────┐                    ┌──────────────┐             │
│  │  customers   │                    │   tickets    │             │
│  ├──────────────┤                    ├──────────────┤             │
│  │ id (PK)      │─────────────────►  │ id (PK)      │             │
│  │ name         │  ONE TO MANY       │ customer_id  │             │
│  │ email        │                    │ subject      │             │
│  │ phone        │                    │ status       │             │
│  └──────────────┘                    └──────────────┘             │
│                                                                    │
│  ┌──────────────┐                    ┌──────────────┐             │
│  │  customers   │                    │   invoices   │             │
│  ├──────────────┤                    ├──────────────┤             │
│  │ id (PK)      │─────────────────►  │ id (PK)      │             │
│  │ name         │  ONE TO MANY       │ customer_id  │             │
│  │ email        │                    │ amount       │             │
│  └──────────────┘                    │ due_date     │             │
│                                      └──────────────┘             │
│                                                                    │
│  [+ Add Relationship]                                              │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

### **Add Relationship Dialog**

```
┌────────────────────────────────────────────────┐
│ 🔗 Create Relationship                          │
├────────────────────────────────────────────────┤
│                                                │
│ STEP 1: Select Parent Table                    │
│ ┌────────────────────────────────────────────┐ │
│ │ [customers ▼]                              │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ STEP 2: Select Child Table                     │
│ ┌────────────────────────────────────────────┐ │
│ │ [tickets ▼]                                │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ STEP 3: Choose Relationship Type                │
│                                                │
│ ⚪ ONE-TO-ONE                                  │
│    Each customer has exactly one profile       │
│                                                │
│ 🔘 ONE-TO-MANY                                 │
│    Each customer can have many tickets         │
│                                                │
│ ⚪ MANY-TO-MANY                                │
│    Customers and products (needs link table)   │
│                                                │
│ STEP 4: Field Mapping                          │
│ ┌────────────────────────────────────────────┐ │
│ │ Parent Field: [id ▼]                       │ │
│ │ Child Field:  [customer_id ▼]              │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ ☑️ Delete child records when parent deleted   │
│                                                │
│ [Create Relationship] [Cancel]                 │
└────────────────────────────────────────────────┘
```

### **Relationship Types Explained**

#### **ONE-TO-ONE**
```
Customer ←→ Customer Profile
├─ Each customer has ONE profile
└─ Each profile belongs to ONE customer

Example: 
customers.id = customer_profiles.customer_id
```

#### **ONE-TO-MANY**
```
Customer ←→ Tickets
├─ Each customer has MANY tickets
└─ Each ticket belongs to ONE customer

Example:
customers.id = tickets.customer_id
```

#### **MANY-TO-MANY** (Requires Junction Table)
```
Customers ←→ Products
├─ Each customer can buy MANY products
├─ Each product can be bought by MANY customers
└─ Requires: customer_products (junction table)

Example:
customers.id ←→ customer_products.customer_id
products.id ←→ customer_products.product_id
```

---

## 📊 DATA MANAGEMENT

### **Spreadsheet-Like Data View**

```
┌────────────────────────────────────────────────────────────────────┐
│ 📊 customers (127 records)                   [+ Add] [Import] [☰]  │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│ 🔍 Search: [________________]  Status: [All ▼]  Plan: [All ▼]     │
│                                                                    │
│ ┌────┬──────────────┬────────────────────┬──────────────┬────────┐│
│ │[☑] │ ID           │ Name               │ Email        │ Plan   ││
│ ├────┼──────────────┼────────────────────┼──────────────┼────────┤│
│ │[ ] │ 1            │ John Smith         │ john@ex.com  │ Pro    ││
│ │[ ] │ 2            │ Sarah Johnson      │ sarah@ex.com │ VIP    ││
│ │[ ] │ 3            │ Mike Davis         │ mike@ex.com  │ Basic  ││
│ │[ ] │ 4            │ Emily Wilson       │ emily@ex.com │ Pro    ││
│ │[ ] │ 5            │ David Brown        │ david@ex.com │ Basic  ││
│ │... │ ...          │ ...                │ ...          │ ...    ││
│ └────┴──────────────┴────────────────────┴──────────────┴────────┘│
│                                                                    │
│ Selected: 0 records                           [Showing 1-50 of 127]│
│ [◄ Previous] [Next ►]                                              │
│                                                                    │
│ BULK ACTIONS:                                                      │
│ [✏️ Edit Selected] [🗑️ Delete Selected] [📤 Export Selected]      │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

### **Edit Record Form**

```
┌────────────────────────────────────────────────┐
│ ✏️ Edit Customer #127                           │
├────────────────────────────────────────────────┤
│                                                │
│ Name                                            │
│ ┌────────────────────────────────────────────┐ │
│ │ John Smith                                 │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ Email Address                                   │
│ ┌────────────────────────────────────────────┐ │
│ │ john@example.com                           │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ Phone Number                                    │
│ ┌────────────────────────────────────────────┐ │
│ │ (555) 123-4567                             │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ Signup Date                                     │
│ ┌────────────────────────────────────────────┐ │
│ │ 2026-01-01 [📅]                            │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ Plan Name                                       │
│ ┌────────────────────────────────────────────┐ │
│ │ Pro ▼                                      │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ Status                                          │
│ ┌────────────────────────────────────────────┐ │
│ │ Active ▼                                   │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ [Save Changes] [Cancel] [Delete Record]         │
│                                                │
└────────────────────────────────────────────────┘
```

### **Quick Inline Editing**

- Click any cell to edit
- Tab to move to next field
- Enter to save
- Esc to cancel
- Auto-save after 2 seconds of no typing

### **Filtering & Sorting**

**Available Filters:**
- Text search (searches all text fields)
- Date range picker
- Dropdown filters (for dropdown fields)
- Checkbox filters (for yes/no fields)
- Number range (min/max)

**Sorting:**
- Click column header to sort
- Click again to reverse
- Shift+click for multi-column sort

### **Bulk Operations**

**Select Multiple Records:**
- Click checkboxes
- Shift+click for range
- Select All checkbox

**Bulk Actions:**
1. **Edit Selected** - Change same field on all
2. **Delete Selected** - Remove multiple records
3. **Export Selected** - Download as CSV/Excel
4. **Duplicate Selected** - Create copies
5. **Change Status** - Update status field

---

## 🔒 SECURITY & PERMISSIONS

### **Access Control**

**Admin Levels:**
```
SUPER ADMIN (kahlen@truthvault.com)
├─ Full access to all tables
├─ Can create/edit/delete tables
├─ Can manage relationships
└─ Can manage other admins

ADMIN
├─ Full access to assigned tables
├─ Can add/edit/delete records
├─ Cannot modify table structure
└─ Cannot delete tables

VIEWER
├─ Read-only access
├─ Can view records
├─ Can export data
└─ Cannot edit anything
```

### **Table-Level Permissions**

```sql
-- Each table can have specific permissions
{
  "table_id": 5,
  "permissions": {
    "owner": "kahlen@truthvault.com",
    "admins": ["admin@truthvault.com"],
    "viewers": ["support@truthvault.com"],
    "public_view": false,
    "public_add": false
  }
}
```

### **Field-Level Security**

```sql
-- Sensitive fields can be hidden from certain users
{
  "field_id": 12,
  "security": {
    "visible_to": ["super_admin", "admin"],
    "editable_by": ["super_admin"],
    "encrypted": true
  }
}
```

### **Audit Trail**

**Every change is logged:**
```sql
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL,
    record_id INTEGER NOT NULL,
    action TEXT NOT NULL,              -- create, update, delete
    field_name TEXT,                   -- Which field changed
    old_value TEXT,                    -- Previous value
    new_value TEXT,                    -- New value
    changed_by INTEGER,                -- Admin user ID
    changed_at TEXT DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,                   -- User's IP
    user_agent TEXT                    -- Browser info
);
```

**View Audit Trail:**
```
┌────────────────────────────────────────────────┐
│ 📜 Audit Log: customers #127                    │
├────────────────────────────────────────────────┤
│                                                │
│ Jan 14, 2026 2:30 PM - kahlen@truthvault.com   │
│ ├─ Changed "status" from "Active" to "VIP"    │
│ └─ IP: 192.168.1.100                           │
│                                                │
│ Jan 10, 2026 9:15 AM - admin@truthvault.com    │
│ ├─ Changed "email" from old@ex.com             │
│ └─ IP: 192.168.1.50                            │
│                                                │
│ Jan 1, 2026 8:00 AM - system                   │
│ └─ Record created                              │
│                                                │
└────────────────────────────────────────────────┘
```

---

## 📥 IMPORT/EXPORT

### **CSV/Excel Import**

```
┌────────────────────────────────────────────────┐
│ 📥 Import Data to "customers"                   │
├────────────────────────────────────────────────┤
│                                                │
│ STEP 1: Upload File                            │
│ ┌────────────────────────────────────────────┐ │
│ │                                            │ │
│ │     [📁 Choose File] or Drag & Drop        │ │
│ │                                            │ │
│ │     Accepted: .csv, .xlsx, .xls            │ │
│ │     Max size: 10 MB                        │ │
│ │                                            │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ STEP 2: Map Columns (Auto-Detected)            │
│ ┌────────────────────────────────────────────┐ │
│ │ CSV Column       →  Database Field         │ │
│ ├────────────────────────────────────────────┤ │
│ │ Name             →  [name ▼]               │ │
│ │ Email            →  [email ▼]              │ │
│ │ Phone            →  [phone ▼]              │ │
│ │ SignupDate       →  [signup_date ▼]        │ │
│ │ Plan             →  [plan_name ▼]          │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ STEP 3: Import Options                         │
│ ☑️ Skip first row (headers)                   │
│ ☑️ Update existing records (match by email)   │
│ ☐ Delete records not in file                  │
│                                                │
│ PREVIEW (First 5 rows):                        │
│ ┌────────────────────────────────────────────┐ │
│ │ John Smith | john@ex.com | (555) 123-4567  │ │
│ │ Sarah Davis | sarah@ex.com | (555) 234-5678││ │
│ │ ...                                        │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ ✅ Ready to import 150 records                 │
│                                                │
│ [Import Now] [Cancel]                          │
│                                                │
└────────────────────────────────────────────────┘
```

### **Export Options**

```
┌────────────────────────────────────────────────┐
│ 📤 Export "customers" Data                      │
├────────────────────────────────────────────────┤
│                                                │
│ FORMAT:                                         │
│ 🔘 CSV (Excel-compatible)                      │
│ ⚪ Excel (.xlsx)                               │
│ ⚪ JSON (for API use)                          │
│                                                │
│ FIELDS TO EXPORT:                               │
│ ☑️ Select All                                  │
│ ☑️ ID                                          │
│ ☑️ Name                                        │
│ ☑️ Email                                       │
│ ☑️ Phone                                       │
│ ☑️ Signup Date                                 │
│ ☑️ Plan Name                                   │
│ ☑️ Status                                      │
│                                                │
│ FILTERS:                                        │
│ Status: [All ▼]                                │
│ Plan: [All ▼]                                  │
│ Date Range: [All Time ▼]                       │
│                                                │
│ OPTIONS:                                        │
│ ☑️ Include column headers                      │
│ ☐ Include audit information                    │
│ ☐ Include related tables                       │
│                                                │
│ 📊 Will export 127 records                     │
│                                                │
│ [Download Export] [Cancel]                     │
│                                                │
└────────────────────────────────────────────────┘
```

---

## 🎓 TUTORIAL SYSTEM

### **5-Minute Quick Start**

**Tutorial Flow:**

```
LESSON 1: Create Your First Table (60 seconds)
├─ Click "New Table"
├─ Name it "test_contacts"
├─ Click "Add Field" → Choose "Text" → Name: "name"
├─ Click "Add Field" → Choose "Email" → Name: "email"
├─ Click "Save Changes"
└─ ✅ You just created a database table!

LESSON 2: Add Some Data (60 seconds)
├─ Click "Open" on your test_contacts table
├─ Click "+ Add Record"
├─ Fill in: Name="John Doe", Email="john@test.com"
├─ Click "Save"
└─ ✅ You just added your first record!

LESSON 3: View & Edit Data (60 seconds)
├─ See your data in spreadsheet view
├─ Click any cell to edit inline
├─ Try sorting by clicking column headers
├─ Try searching in the search box
└─ ✅ You're navigating like a pro!

LESSON 4: Import Bulk Data (90 seconds)
├─ Download sample CSV file
├─ Click "Import" button
├─ Upload the CSV
├─ Map columns (auto-detected!)
├─ Click "Import Now"
└─ ✅ You just imported 100 records!

LESSON 5: Create Relationships (90 seconds)
├─ Create second table "test_notes"
├─ Add fields: "note_text", "contact_id"
├─ Go to Relationships tab
├─ Connect test_contacts.id → test_notes.contact_id
└─ ✅ You understand table relationships!
```

### **Interactive Tutorial Overlay**

```
┌────────────────────────────────────────────────┐
│ 🎓 Tutorial Mode: Active                        │
│                                                │
│ Step 3 of 5: Add Your First Field               │
│                                                │
│ ┌────────────────────────────────────────────┐ │
│ │ Now click the "[+ Add Field]" button       │ │
│ │                                            │ │
│ │              ⬇️                            │ │
│ │         [+ Add Field] ← Click here!        │ │
│ │                                            │ │
│ │ This will open the field type selector.   │ │
│ └────────────────────────────────────────────┘ │
│                                                │
│ [Skip Tutorial] [Previous] [Next]               │
└────────────────────────────────────────────────┘
```

### **Context-Sensitive Help**

**Help bubbles appear when hovering:**
```
[?] ← Hover for help

"What's a field?"
A field is like a column in a spreadsheet.
Each field stores one type of information:
- Name field = stores names
- Email field = stores emails
- Phone field = stores phone numbers
```

### **Video Tutorials (Optional)**

**Embedded YouTube tutorials:**
- Creating Your First Table (2 min)
- Understanding Relationships (3 min)
- Importing CSV Data (2 min)
- Exporting Reports (2 min)
- Advanced Filtering (3 min)

---

## 🔌 API ENDPOINTS

### **Table Management API**

**Base URL:** `https://vpn.the-truth-publishing.com/admin/database-builder/api/`

**Authentication:** Bearer token (from admin login)

---

#### **1. List All Tables**

```http
GET /api/tables.php
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "tables": [
    {
      "id": 1,
      "table_name": "customers",
      "display_name": "Customer Records",
      "icon": "👥",
      "record_count": 127,
      "created_at": "2026-01-01 10:00:00",
      "updated_at": "2026-01-14 14:30:00"
    },
    {
      "id": 2,
      "table_name": "tickets",
      "display_name": "Support Tickets",
      "icon": "🎫",
      "record_count": 34,
      "created_at": "2026-01-05 09:00:00",
      "updated_at": "2026-01-14 16:00:00"
    }
  ],
  "total": 2
}
```

---

#### **2. Create New Table**

```http
POST /api/tables.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "table_name": "contacts",
  "display_name": "Contact List",
  "description": "Store customer and lead contacts",
  "icon": "📇",
  "color": "#3b82f6"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Table created successfully",
  "table_id": 3,
  "database_file": "user-tables/contacts.db"
}
```

---

#### **3. Get Table Structure**

```http
GET /api/tables.php?id=1
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "table": {
    "id": 1,
    "table_name": "customers",
    "display_name": "Customer Records",
    "fields": [
      {
        "id": 1,
        "field_name": "name",
        "display_name": "Customer Name",
        "field_type": "text",
        "is_required": 1,
        "is_unique": 0,
        "max_length": 100
      },
      {
        "id": 2,
        "field_name": "email",
        "display_name": "Email Address",
        "field_type": "email",
        "is_required": 1,
        "is_unique": 1
      }
    ]
  }
}
```

---

#### **4. Add Field to Table**

```http
POST /api/fields.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "table_id": 1,
  "field_name": "phone",
  "display_name": "Phone Number",
  "field_type": "phone",
  "is_required": 0,
  "validation_rules": {
    "format": "US"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Field added successfully",
  "field_id": 7,
  "sql_executed": "ALTER TABLE customers ADD COLUMN phone TEXT"
}
```

---

#### **5. Get Table Data**

```http
GET /api/data.php?table=customers&limit=50&offset=0
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "table": "customers",
  "records": [
    {
      "id": 1,
      "name": "John Smith",
      "email": "john@example.com",
      "phone": "(555) 123-4567",
      "created_at": "2026-01-01 10:00:00"
    },
    {
      "id": 2,
      "name": "Sarah Johnson",
      "email": "sarah@example.com",
      "phone": "(555) 234-5678",
      "created_at": "2026-01-02 11:30:00"
    }
  ],
  "total": 127,
  "limit": 50,
  "offset": 0,
  "has_more": true
}
```

---

#### **6. Create Record**

```http
POST /api/data.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "table": "customers",
  "data": {
    "name": "Mike Davis",
    "email": "mike@example.com",
    "phone": "(555) 345-6789",
    "plan_name": "pro"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Record created successfully",
  "record_id": 128
}
```

---

#### **7. Update Record**

```http
PUT /api/data.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "table": "customers",
  "record_id": 128,
  "data": {
    "phone": "(555) 999-8888",
    "plan_name": "vip"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Record updated successfully",
  "changes": 2
}
```

---

#### **8. Delete Record**

```http
DELETE /api/data.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "table": "customers",
  "record_id": 128
}
```

**Response:**
```json
{
  "success": true,
  "message": "Record deleted successfully",
  "audit_logged": true
}
```

---

#### **9. Import CSV**

```http
POST /api/import.php
Authorization: Bearer {token}
Content-Type: multipart/form-data

table=customers
file=[CSV file data]
mapping={"Name":"name","Email":"email","Phone":"phone"}
skip_first_row=true
update_existing=true
```

**Response:**
```json
{
  "success": true,
  "message": "Import completed",
  "records_created": 150,
  "records_updated": 25,
  "records_skipped": 5,
  "errors": []
}
```

---

#### **10. Export Data**

```http
GET /api/export.php?table=customers&format=csv&fields=name,email,phone
Authorization: Bearer {token}
```

**Response:**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="customers_2026-01-14.csv"

name,email,phone
"John Smith","john@example.com","(555) 123-4567"
"Sarah Johnson","sarah@example.com","(555) 234-5678"
...
```

---

## 🎯 IMPLEMENTATION GUIDE

### **Phase 1: Core Database Builder (Week 1)**

**Days 1-2: Database Schema**
```bash
# Create builder.db with all metadata tables
php admin/database-builder/setup/create_builder_db.php

# Test table creation
# Test field definitions
# Test relationships
```

**Days 3-4: Visual Table Designer**
```bash
# Build drag-and-drop interface
# Implement field type selector
# Create property editor
# Real-time preview
```

**Days 5-7: Data Management**
```bash
# Spreadsheet-like grid view
# Inline editing
# Search and filter
# Pagination
```

### **Phase 2: Advanced Features (Week 2)**

**Days 1-2: Import/Export**
```bash
# CSV parser
# Excel reader (via PhpSpreadsheet)
# Column mapping
# Bulk operations
```

**Days 3-4: Relationships**
```bash
# Visual relationship designer
# Foreign key management
# Cascade delete options
# Relationship queries
```

**Days 5-7: Tutorial System**
```bash
# 5-minute interactive tutorial
# Context-sensitive help
# Video embeds
# Progress tracking
```

### **Phase 3: Polish & Testing (Week 3)**

**Days 1-3: Security & Permissions**
```bash
# Access control
# Audit logging
# Field-level security
# Encryption for sensitive fields
```

**Days 4-5: Performance Optimization**
```bash
# Query optimization
# Index creation
# Caching layer
# Lazy loading
```

**Days 6-7: Documentation & Launch**
```bash
# User documentation
# API documentation
# Video tutorials
# Launch!
```

---

## ✅ TESTING CHECKLIST

### **Table Creation**
- [ ] Create table with 1 field
- [ ] Create table with 15 fields (all types)
- [ ] Edit table structure
- [ ] Delete empty table
- [ ] Delete table with data (with confirmation)
- [ ] Duplicate table structure
- [ ] Archive/restore table

### **Field Management**
- [ ] Add field to existing table
- [ ] Edit field properties
- [ ] Change field type (with data migration)
- [ ] Delete field (with confirmation)
- [ ] Reorder fields (drag & drop)
- [ ] Set field as required
- [ ] Set field as unique

### **Data Operations**
- [ ] Add single record
- [ ] Edit record inline
- [ ] Edit record in form
- [ ] Delete single record
- [ ] Bulk delete records
- [ ] Search records
- [ ] Filter by field
- [ ] Sort by column
- [ ] Pagination

### **Import/Export**
- [ ] Import CSV (100 records)
- [ ] Import CSV (10,000 records)
- [ ] Import Excel .xlsx
- [ ] Import with column mapping
- [ ] Import with update existing
- [ ] Export to CSV
- [ ] Export to Excel
- [ ] Export filtered data

### **Relationships**
- [ ] Create one-to-one relationship
- [ ] Create one-to-many relationship
- [ ] Create many-to-many relationship
- [ ] View related records
- [ ] Cascade delete test
- [ ] Orphan detection

### **Security**
- [ ] Admin can access all tables
- [ ] Viewer can only read
- [ ] Audit log captures all changes
- [ ] Sensitive fields hidden from viewers
- [ ] API requires authentication
- [ ] SQL injection prevention

### **Performance**
- [ ] Load time < 2 seconds (100 records)
- [ ] Load time < 5 seconds (10,000 records)
- [ ] Search response < 1 second
- [ ] Import 1,000 records < 10 seconds
- [ ] Export 10,000 records < 15 seconds

---

## 📚 CODE EXAMPLES

### **Creating a Table Programmatically**

```php
<?php
// Create new table via API
$table_data = [
    'table_name' => 'customers',
    'display_name' => 'Customer Records',
    'description' => 'Store customer contact and account information',
    'icon' => '👥',
    'color' => '#3b82f6',
    'fields' => [
        [
            'field_name' => 'name',
            'display_name' => 'Customer Name',
            'field_type' => 'text',
            'is_required' => 1,
            'max_length' => 100
        ],
        [
            'field_name' => 'email',
            'display_name' => 'Email Address',
            'field_type' => 'email',
            'is_required' => 1,
            'is_unique' => 1
        ],
        [
            'field_name' => 'phone',
            'display_name' => 'Phone Number',
            'field_type' => 'phone',
            'is_required' => 0,
            'validation_rules' => json_encode(['format' => 'US'])
        ]
    ]
];

// API call
$ch = curl_init('https://vpn.the-truth-publishing.com/admin/database-builder/api/tables.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($table_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $auth_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Table created! ID: " . $result['table_id'];
}
?>
```

### **Adding Records in Bulk**

```php
<?php
// Bulk insert records
$records = [
    ['name' => 'John Smith', 'email' => 'john@example.com', 'phone' => '555-123-4567'],
    ['name' => 'Sarah Davis', 'email' => 'sarah@example.com', 'phone' => '555-234-5678'],
    ['name' => 'Mike Johnson', 'email' => 'mike@example.com', 'phone' => '555-345-6789']
];

foreach ($records as $record) {
    $data = [
        'table' => 'customers',
        'data' => $record
    ];
    
    $ch = curl_init('https://vpn.the-truth-publishing.com/admin/database-builder/api/data.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $auth_token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    echo "Record created: " . $record['name'] . "\n";
}
?>
```

### **Querying with Filters**

```php
<?php
// Get active customers who signed up in January
$filters = [
    'status' => 'active',
    'signup_date_from' => '2026-01-01',
    'signup_date_to' => '2026-01-31'
];

$url = 'https://vpn.the-truth-publishing.com/admin/database-builder/api/data.php?';
$url .= 'table=customers&' . http_build_query($filters);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $auth_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);

echo "Found " . $result['total'] . " customers\n";
foreach ($result['records'] as $customer) {
    echo "- " . $customer['name'] . " (" . $customer['email'] . ")\n";
}
?>
```

---

## 🎉 SUCCESS METRICS

### **User Adoption**
- 100% of admins can create a table within 5 minutes (with tutorial)
- 90% of admins use the system weekly
- Average time to create table: 2 minutes
- Average time to import data: 30 seconds

### **Performance**
- Page load time: < 2 seconds
- Search response time: < 1 second
- Import 1,000 records: < 10 seconds
- Export 10,000 records: < 15 seconds

### **Reliability**
- 99.9% uptime
- Zero data loss incidents
- All changes logged in audit trail
- Automated backups every 24 hours

---

## 📞 SUPPORT & MAINTENANCE

### **Common Issues**

**1. "Can't create table"**
- Check database file permissions
- Verify SQLite extension installed
- Check disk space
- Review error log

**2. "Import fails"**
- Check CSV format (UTF-8)
- Verify column mappings
- Check for duplicate unique fields
- Review import error log

**3. "Slow performance"**
- Check table sizes
- Verify indexes are created
- Clear cached data
- Optimize queries

### **Maintenance Tasks**

**Daily:**
- Check error logs
- Monitor disk space
- Verify backups

**Weekly:**
- Review audit logs
- Check table sizes
- Optimize databases (VACUUM)

**Monthly:**
- Archive old records
- Review user access
- Update documentation

---

## 🚀 FUTURE ENHANCEMENTS

### **Phase 4: Advanced Features (Future)**

**1. Visual Query Builder**
- Drag-and-drop query designer
- No SQL knowledge required
- Saved query templates

**2. Automated Reports**
- Schedule reports (daily/weekly/monthly)
- Email reports automatically
- Dashboard widgets

**3. API Webhooks**
- Trigger external actions on events
- Integration with Zapier/Make
- Real-time notifications

**4. Mobile App**
- View data on phone
- Quick record updates
- Offline mode

**5. Advanced Analytics**
- Charts and graphs
- Trend analysis
- Predictive insights

---

## 📋 FINAL CHECKLIST

Before considering Section 16 complete:

- [ ] All field types documented (15 types)
- [ ] Database schema created (3 tables)
- [ ] Visual designer UI designed
- [ ] Data management interface planned
- [ ] API endpoints specified (10 endpoints)
- [ ] Security model defined
- [ ] Import/export features designed
- [ ] Tutorial system outlined
- [ ] Implementation guide created
- [ ] Testing checklist provided
- [ ] Code examples included
- [ ] Future enhancements listed

---

**END OF SECTION 16: DATABASE BUILDER SYSTEM**

**Next Section:** Section 17 - Form Library & Builder
**Status:** Section 16 Complete ✅
**Lines:** ~1,500 lines
**Created:** January 14, 2026

---

## 📚 TEMPLATE LIBRARY SYSTEM (150+ TEMPLATES)

**CRITICAL USER DECISION UPDATE - January 20, 2026:**
DataForge must include 150+ pre-built templates across 4 categories, each with 3 style variants (Basic, Formal, Executive).

---

### **WHY 150+ TEMPLATES?**

**Problem:** Most users don't know what fields to include in their databases.

**Solution:** Pre-built templates for common business scenarios.

**Example:**
- User needs to track customers
- Instead of: "Uh... name? email? I guess?"
- They select: "Customer Management" template
- Gets: 15 pre-configured fields (name, email, phone, address, signup date, status, tags, notes, etc.)

---

## 🎨 TEMPLATE CATEGORIES (158 BASE TEMPLATES)

### **CATEGORY 1: MARKETING TEMPLATES (50 TEMPLATES)**

#### **Social Media Posts (10)**
1. **Facebook Product Launch**
   - Fields: Post Title, Description, Image URL, Target Audience, Schedule Date, CTA Link, Engagement Goals
   - Style Variants: Basic (plain text), Formal (structured), Executive (premium)

2. **Twitter Announcement**
   - Fields: Tweet Text (280 chars), Hashtags, Media, Schedule Time, Thread Continuation, Reply Settings

3. **LinkedIn Company Update**
   - Fields: Update Title, Long-form Content, Company Tag, Industry Tags, Document Attachments, Publication Date

4. **Instagram Story Promo**
   - Fields: Story Image/Video, Overlay Text, Swipe-up Link, Sticker Type, Duration, Highlight Category

5. **TikTok Video Script**
   - Fields: Video Title, Script Text, Hook (first 3 sec), Call-to-action, Background Music, Trending Hashtags

6. **Pinterest Pin Description**
   - Fields: Pin Title, Description, Image URL, Board Name, Keywords, Target Demographics

7. **YouTube Video Description**
   - Fields: Video Title, Description Text, Tags, Timestamps, Links Section, Call-to-subscribe

8. **Reddit Post Format**
   - Fields: Subreddit, Post Title, Body Text, Flair, Link Type (text/link/image), Community Guidelines Check

9. **Discord Community Update**
   - Fields: Channel Name, Announcement Title, Content, Mentions (@role/@everyone), Attachments, Pin Status

10. **Threads Engagement Post**
    - Fields: Thread Text, Reply Settings, Link Attachment, Quote Post, Engagement Type

#### **Email Campaigns (10)**
1. **Newsletter Monthly**
   - Fields: Newsletter Name, Month/Year, Featured Articles (5), Company Updates, Call-to-action, Footer Links

2. **Product Announcement**
   - Fields: Product Name, Launch Date, Key Features (bullet list), Pricing Info, Pre-order Link, FAQ Section

3. **Sale/Promotion Alert**
   - Fields: Sale Title, Discount Percentage, Valid Dates, Promo Code, Featured Products, Terms & Conditions

4. **Event Invitation**
   - Fields: Event Name, Date/Time, Location/Virtual Link, RSVP Deadline, Agenda, Dress Code, Parking Info

5. **Survey Request**
   - Fields: Survey Purpose, Incentive Offered, Survey Link, Est. Completion Time, Deadline, Privacy Note

6. **Testimonial Request**
   - Fields: Customer Name, Product/Service Used, Request Message, Review Platforms, Response Deadline

7. **Re-engagement Campaign**
   - Fields: Last Activity Date, Personalized Message, Special Offer, Unsubscribe Warning, Account Status

8. **Abandoned Cart Recovery**
   - Fields: Cart Contents, Total Value, Discount Offer, Cart Link, Expiration Time, Support Contact

9. **Birthday/Anniversary**
   - Fields: Customer Name, Celebration Type, Special Offer, Valid Duration, Personalization Tokens

10. **Welcome Series**
    - Fields: Email Sequence Number (1-5), Welcome Message, Getting Started Guide, Key Resources, Next Steps

#### **Ad Copy (10)**
1. **Google Search Ad**
   - Fields: Headline 1-3, Description 1-2, Display URL, Final URL, Keywords, Negative Keywords, Bid Amount

2. **Facebook Ad**
   - Fields: Primary Text, Headline, Link Description, Image/Video, Target Audience, Ad Placement, Budget

3. **Instagram Ad**
   - Fields: Visual Asset, Caption, Call-to-action Button, Destination URL, Story/Feed/Explore, Budget/Schedule

4. **LinkedIn Sponsored Content**
   - Fields: Intro Text, Headline, Image, CTA Type, Target Job Titles, Industries, Company Size, Budget

5. **Twitter Promoted Tweet**
   - Fields: Tweet Text, Media, Target Keywords, Geographic Targeting, Device Targeting, Campaign Objective

6. **YouTube Pre-Roll**
   - Fields: Video Length (6s/15s/30s), Script, End Card, Target Topics, Skip Settings, Campaign Budget

7. **Display Banner Text**
   - Fields: Banner Size, Primary Message, Secondary Text, CTA Button Text, Logo Placement, Color Scheme

8. **Native Advertising**
   - Fields: Article Headline, Teaser Text, Thumbnail Image, Sponsored Label, Target Sites, Content Type

9. **Retargeting Ad**
   - Fields: Previous Interaction Type, Personalized Message, Offer/Incentive, Pixel Tracking, Frequency Cap

10. **Local Service Ad**
    - Fields: Service Name, Service Area (zip codes), Business Hours, Contact Info, License Numbers, Reviews Link

#### **Press Releases (10)**
1. **Product Launch**
   - Fields: Product Name, Launch Date, Key Features, Target Market, Pricing, Availability, Media Contact

2. **Company Milestone**
   - Fields: Milestone Type, Achievement Date, Statistics/Numbers, Company History Context, Future Plans

3. **Partnership Announcement**
   - Fields: Partner Company, Partnership Type, Benefits, Effective Date, Combined Statement, Contact Info

4. **Executive Appointment**
   - Fields: Executive Name, New Position, Start Date, Background/Bio, Quote from Board, Previous Role

5. **Award Recognition**
   - Fields: Award Name, Awarding Organization, Achievement Category, Date Received, Significance, Quote

6. **Event Coverage**
   - Fields: Event Name/Date, Key Announcements, Speaker Highlights, Attendance Numbers, Photo Gallery Link

7. **Crisis Response**
   - Fields: Incident Summary, Company Response, Actions Taken, Timeline, Customer Support, Future Prevention

8. **Financial Results**
   - Fields: Quarter/Year, Revenue, Profit/Loss, Year-over-year Growth, Key Metrics, CEO Statement, Outlook

9. **Merger/Acquisition**
   - Fields: Companies Involved, Deal Value, Closing Date, Strategic Rationale, Impact Statement, Integration Plan

10. **Charity Initiative**
    - Fields: Cause/Organization, Donation Amount, Partnership Duration, Employee Involvement, Impact Goals

#### **Blog Posts (10)**
1. **How-To Guide**
   - Fields: Guide Title, Problem Statement, Step-by-Step Instructions, Screenshots/Images, Tips Section, Related Resources

2. **Listicle Article**
   - Fields: List Title, Number of Items, Introduction, Item Descriptions, Featured Images, Conclusion/CTA

3. **Case Study**
   - Fields: Client Name, Challenge, Solution, Implementation, Results/Metrics, Client Quote, Key Takeaways

4. **Industry News**
   - Fields: News Headline, Summary, Industry Impact, Expert Commentary, Related Articles, Update Date

5. **Product Review**
   - Fields: Product Name, Rating (1-5), Pros/Cons, Detailed Review, Comparison Table, Recommendation, Affiliate Link

6. **Company Culture**
   - Fields: Culture Topic, Employee Stories, Company Values, Team Photos, Work Environment, Career Opportunities

7. **Expert Interview**
   - Fields: Expert Name/Title, Interview Questions, Transcript/Quotes, Key Insights, Expert Bio, Contact/Follow Links

8. **Trend Analysis**
   - Fields: Trend Name, Current Data, Historical Context, Future Predictions, Impact Assessment, Action Items

9. **Tutorial Series**
   - Fields: Series Title, Part Number, Skill Level, Duration, Learning Objectives, Video/Images, Next Lesson Link

10. **FAQ Compilation**
    - Fields: Topic Area, Question-Answer Pairs (10+), Related Topics, Support Contact, Last Updated Date

---

### **CATEGORY 2: EMAIL TEMPLATES (30 TEMPLATES)**

#### **Customer Onboarding (5)**
1. **Welcome Email - New Customer**
   - Basic: Simple greeting, login link
   - Formal: Professional welcome, structured onboarding steps
   - Executive: Premium welcome package, dedicated support contact

2. **Account Setup Guide**
   - Basic: Quick setup checklist
   - Formal: Step-by-step instructions with screenshots
   - Executive: White-glove setup offering, personal assistant available

3. **First Purchase Thank You**
   - Basic: Thank you message, order confirmation
   - Formal: Detailed order summary, shipping timeline
   - Executive: Personal thank you from CEO, VIP perks

4. **Product Tutorial Series**
   - Basic: Simple feature highlights
   - Formal: Comprehensive video tutorials
   - Executive: One-on-one training session offered

5. **30-Day Check-in**
   - Basic: How's it going? Quick survey
   - Formal: Satisfaction assessment, feature adoption
   - Executive: Account manager follow-up, strategy review

#### **Billing & Payments (5)**
6. **Payment Receipt**
   - Basic: Amount paid, date, thank you
   - Formal: Detailed invoice, payment method, tax info
   - Executive: Elegant receipt, account summary, concierge billing

7. **Payment Failed Notification**
   - Basic: Payment failed, update card link
   - Formal: Detailed failure reason, resolution steps
   - Executive: Priority assistance, direct billing contact

8. **Subscription Renewal Reminder**
   - Basic: Renewal date, amount due
   - Formal: Renewal summary, plan benefits reminder
   - Executive: Personalized renewal offer, loyalty bonus

9. **Refund Processed**
   - Basic: Refund amount, processing time
   - Formal: Refund details, timeline, feedback request
   - Executive: Apology letter, retention offer included

10. **Payment Method Update**
    - Basic: Update card link
    - Formal: Security explanation, update process
    - Executive: Secure portal access, direct support

#### **Support Communications (5)**
11. **Ticket Received Confirmation**
    - Basic: Ticket number, response time
    - Formal: Detailed issue summary, SLA timeline
    - Executive: Priority routing, immediate escalation

12. **Ticket Resolved Notification**
    - Basic: Issue fixed, close ticket?
    - Formal: Resolution summary, verification request
    - Executive: Resolution documentation, satisfaction guarantee

13. **Satisfaction Survey**
    - Basic: How did we do? 1-5 stars
    - Formal: Detailed CSAT survey, improvement focus
    - Executive: Personal feedback request, direct CEO line

14. **Technical Support Follow-up**
    - Basic: Still working? Need more help?
    - Formal: Solution verification, additional resources
    - Executive: Engineering team follow-up, preventive measures

15. **Knowledge Base Recommendation**
    - Basic: Found this helpful article link
    - Formal: Curated resources, self-service portal
    - Executive: Custom documentation created, training offered

#### **Retention & Re-engagement (5)**
16. **Inactive User Re-engagement**
    - Basic: We miss you! Special offer
    - Formal: Account status update, value reminder
    - Executive: Personal outreach, custom retention package

17. **Cancellation Feedback Request**
    - Basic: Why are you leaving? Quick survey
    - Formal: Exit interview, improvement focus
    - Executive: Personal call request, last-chance offer

18. **Win-Back Offer**
    - Basic: Come back! 50% off discount
    - Formal: Tailored return offer, new features highlight
    - Executive: Custom reactivation package, premium incentives

19. **Loyalty Reward**
    - Basic: Thanks for staying! Here's a gift
    - Formal: Anniversary milestone, exclusive benefits
    - Executive: VIP loyalty program, bespoke rewards

20. **Upgrade Opportunity**
    - Basic: Unlock premium features! Upgrade now
    - Formal: Feature comparison, upgrade benefits
    - Executive: Growth consultation, enterprise offering

#### **Transactional Emails (5)**
21. **Order Confirmation**
    - Basic: Order number, total, delivery estimate
    - Formal: Detailed order breakdown, tracking info
    - Executive: Concierge delivery service, priority handling

22. **Shipping Notification**
    - Basic: Shipped! Tracking number
    - Formal: Carrier details, delivery map, updates
    - Executive: White-glove delivery, signature required

23. **Delivery Confirmation**
    - Basic: Delivered! Enjoy your purchase
    - Formal: Delivery verification, setup instructions
    - Executive: Installation offered, premium unboxing

24. **Return Authorization**
    - Basic: Return approved, shipping label
    - Formal: Return process, refund timeline
    - Executive: Prepaid return, instant replacement

25. **Account Password Reset**
    - Basic: Reset link, expires in 1 hour
    - Formal: Security verification, reset instructions
    - Executive: Secure authentication, support available

#### **Internal Communications (5)**
26. **Team Meeting Invitation**
    - Basic: Meeting time, agenda, dial-in
    - Formal: Detailed agenda, prep materials, RSVP
    - Executive: Executive briefing, confidential materials

27. **Project Status Update**
    - Basic: Quick status, blockers, next steps
    - Formal: Milestone progress, timeline, risks
    - Executive: Executive summary, strategic implications

28. **Policy Change Notification**
    - Basic: New policy effective date
    - Formal: Policy details, FAQ, training
    - Executive: Strategic rationale, compliance requirements

29. **Employee Recognition**
    - Basic: Great job on [project]!
    - Formal: Award announcement, achievement details
    - Executive: Board recognition, career advancement

30. **Department Newsletter**
    - Basic: Team updates, upcoming events
    - Formal: Comprehensive update, metrics, kudos
    - Executive: Leadership insights, strategic direction

---

### **CATEGORY 3: VPN BUSINESS TEMPLATES (20 TEMPLATES)**

#### **Device Configuration (5)**
1. **WireGuard Config Generator**
   - Fields: Device Name, Private Key, Public Key, Server IP, Allowed IPs, DNS, MTU

2. **Port Forwarding Rules**
   - Fields: Device Name, Internal IP, External Port, Internal Port, Protocol (TCP/UDP), Status

3. **Parental Control Schedule**
   - Fields: Child Name, Device, Day of Week, Start Time, End Time, Allowed Sites, Blocked Categories

4. **Gaming Console Setup**
   - Fields: Console Type (PS5/Xbox/Switch), NAT Type Desired, Port Requirements, DMZ Enabled, Bandwidth Priority

5. **Camera RTSP URLs**
   - Fields: Camera Name/Location, Brand, RTSP URL Format, Username, Password, Resolution, FPS

#### **Server Management (5)**
6. **Server Status Report**
   - Fields: Server Name, Location, IP Address, Uptime, CPU %, Memory %, Bandwidth Used, Active Users

7. **Bandwidth Usage Log**
   - Fields: Date, Server, Total Transfer, Peak Usage Time, Top Users, Protocol Breakdown, Overage Alert

8. **Connection History**
   - Fields: User Email, Device, Connect Time, Disconnect Time, Duration, Data Transferred, Exit Server

9. **IP Assignment Tracker**
   - Fields: User Email, VPN IP Assigned, Assignment Date, Expiration, Lease Status, Renewal Count

10. **Maintenance Schedule**
    - Fields: Server, Maintenance Type, Scheduled Date/Time, Duration, Impact, Notification Sent, Completed

#### **Customer Management (5)**
11. **User Account Details**
    - Fields: Email, Name, Plan Type, Status, Signup Date, Payment Method, Devices Connected, Data Usage

12. **Subscription Tracking**
    - Fields: User, Plan, Price, Billing Cycle, Next Renewal, Auto-renew Status, Payment Failures

13. **VIP User Registry**
    - Fields: VIP Email, Dedicated Server Assigned, Bandwidth Limit, Support Priority, Account Manager

14. **Trial Account Monitor**
    - Fields: Email, Trial Start, Trial End, Days Remaining, Conversion Likelihood, Engagement Score

15. **Payment History**
    - Fields: User, Date, Amount, Plan, Payment Method, Status, Invoice URL, Receipt Sent

#### **Technical Documentation (5)**
16. **Setup Instructions**
    - Fields: OS/Device Type, Step Number, Instruction Text, Screenshot URL, Expected Result, Troubleshooting

17. **Troubleshooting Guide**
    - Fields: Problem Description, Symptoms, Diagnostic Steps, Solution, Prevention, Related Issues

18. **API Documentation**
    - Fields: Endpoint URL, Method (GET/POST), Parameters, Authentication, Request Example, Response Example

19. **Security Audit Log**
    - Fields: Date/Time, Event Type, User/IP, Action Performed, Outcome, Risk Level, Admin Notified

20. **Change Log**
    - Fields: Version, Release Date, Feature Added, Bug Fixed, Breaking Changes, Migration Steps

---

### **CATEGORY 4: FORM TEMPLATES (58 TEMPLATES)**

[Continue with all 58 form templates across Contact, Support, Registration, Surveys, Business Ops, and Legal categories...]

---

## 🎨 STYLE VARIANTS (3 STYLES FOR EACH TEMPLATE)

**All 158 templates come in 3 style variants:**

### **1. BASIC STYLE**
- **Purpose:** Internal use, quick notes, casual communication
- **Characteristics:**
  - Plain text formatting
  - Minimal HTML
  - No graphics/icons
  - Standard fonts (Arial, sans-serif)
  - Simple white background
  - Basic colors (black text, blue links)
  - Mobile-responsive (simple stack layout)

**Example: Basic Welcome Email**
```
Subject: Welcome to TrueVault VPN

Hi [Name],

Thanks for signing up! Your account is ready.

Login: [Email]
Password: [Temp Password]

Get started: [Link]

Questions? Reply to this email.

Thanks,
TrueVault Team
```

### **2. FORMAL STYLE**
- **Purpose:** Client communications, official correspondence, business documents
- **Characteristics:**
  - Professional layout with sections
  - Structured formatting (tables, borders)
  - Company logo placement
  - Business fonts (Helvetica, Georgia)
  - Professional color scheme (navy, gray)
  - Header/footer with contact info
  - Responsive design (2-column layout)

**Example: Formal Welcome Email**
```
[Logo: TrueVault VPN]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Subject: Welcome to TrueVault VPN - Account Activated

Dear [Title] [Last Name],

Thank you for choosing TrueVault VPN.

YOUR ACCOUNT DETAILS
━━━━━━━━━━━━━━━━━━
Email: [Email]
Plan: [Plan Name]
Activated: [Date]

NEXT STEPS
1. Set your password
2. Download configs
3. Connect devices

Support: admin@the-truth-publishing.com

Best regards,
The TrueVault Team
Connection Point Systems Inc.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[Footer: Privacy | Terms | Support]
```

### **3. EXECUTIVE STYLE**
- **Purpose:** VIP clients, executive communications, high-value accounts
- **Characteristics:**
  - Premium design with visual hierarchy
  - Rich HTML formatting (gradients, shadows)
  - High-quality graphics/icons
  - Designer fonts (Montserrat, Playfair)
  - Luxury color palette (gold, deep blue, burgundy)
  - Signature blocks with photos
  - Brand imagery/patterns
  - Advanced responsive (3-column desktop, stack mobile)

**Example: Executive Welcome Email**
```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🛡️  TRUEVAULT EXECUTIVE ACCESS  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

[Premium header graphic]

Dear [Title] [Last Name],

Welcome to an exclusive tier of digital protection.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✓ EXECUTIVE ACCOUNT PROFILE

   Membership: EXECUTIVE TIER
   Member ID: #[ID]
   Activated: [Date]
   
   Dedicated Support: Priority 24/7
   Account Manager: [Name]
   Direct Line: [Phone]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

YOUR EXCLUSIVE BENEFITS

▸ Dedicated VPN Server
▸ Unlimited Devices
▸ Priority Network Routing
▸ White-Glove Support
▸ Custom Configuration Service

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Your personal account manager will contact you
within 24 hours for seamless onboarding.

Warm regards,

[Signature Image]
[Account Manager Name]
Executive Account Services
TrueVault VPN

┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
[Premium footer with gold accents]
```

---

## 📂 TEMPLATE FILE STRUCTURE

```
/databases/templates/
├── marketing/
│   ├── social_media/
│   │   ├── facebook_launch_basic.json
│   │   ├── facebook_launch_formal.json
│   │   ├── facebook_launch_executive.json
│   │   └── ... (10 templates x 3 styles = 30 files)
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
│   │   ├── welcome_basic.json
│   │   ├── welcome_formal.json
│   │   ├── welcome_executive.json
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── billing/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── support/
│   │   └... (5 templates x 3 styles = 15 files)
│   ├── retention/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── transactional/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   └── internal/
│       └── ... (5 templates x 3 styles = 15 files)
│
├── vpn/
│   ├── device_config/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── server_management/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   ├── customer_management/
│   │   └── ... (5 templates x 3 styles = 15 files)
│   └── documentation/
│       └── ... (5 templates x 3 styles = 15 files)
│
└── forms/
    ├── contact/ (10 templates x 3 = 30 files)
    ├── support/ (10 templates x 3 = 30 files)
    ├── registration/ (10 templates x 3 = 30 files)
    ├── surveys/ (10 templates x 3 = 30 files)
    ├── business_ops/ (10 templates x 3 = 30 files)
    └── legal/ (8 templates x 3 = 24 files)
```

**Total Template Files:** 158 base templates × 3 styles = **474 JSON files**

---

## 💾 TEMPLATE JSON FORMAT

```json
{
  "template_id": "welcome_email_basic",
  "display_name": "Welcome Email (Basic)",
  "category": "email",
  "subcategory": "onboarding",
  "style": "basic",
  "description": "Simple welcome email for new customers",
  "use_case": "Internal communications, casual tone",
  "fields": [
    {
      "field_name": "customer_name",
      "field_type": "text",
      "required": true,
      "default_value": "",
      "placeholder": "John Smith",
      "help_text": "Customer's full name"
    },
    {
      "field_name": "email",
      "field_type": "email",
      "required": true,
      "validation": "email"
    },
    {
      "field_name": "temp_password",
      "field_type": "text",
      "required": false,
      "help_text": "Optional temporary password"
    },
    {
      "field_name": "login_link",
      "field_type": "url",
      "required": true,
      "default_value": "https://vpn.the-truth-publishing.com/login"
    }
  ],
  "email_specific": {
    "subject": "Welcome to TrueVault VPN",
    "preview_text": "Thanks for signing up! Your account is ready.",
    "from_name": "TrueVault VPN",
    "from_email": "noreply@the-truth-publishing.com",
    "reply_to": "admin@the-truth-publishing.com"
  },
  "content_template": "Hi {customer_name},\n\nThanks for signing up! Your account is ready.\n\nLogin: {email}\nPassword: {temp_password}\n\nGet started: {login_link}\n\nQuestions? Reply to this email.\n\nThanks,\nTrueVault Team",
  "variables": [
    {"name": "customer_name", "type": "text"},
    {"name": "email", "type": "email"},
    {"name": "temp_password", "type": "text"},
    {"name": "login_link", "type": "url"}
  ],
  "tags": ["welcome", "onboarding", "new customer", "basic"],
  "created_at": "2026-01-20",
  "updated_at": "2026-01-20",
  "version": "1.0"
}
```

---

## 🔍 TEMPLATE SELECTOR INTERFACE

**File:** `/admin/database-builder/template-selector.php`

**Features:**
- **Category Tabs:** Marketing, Email, VPN, Forms
- **Subcategory Filters:** Dropdown within each category
- **Style Toggle:** Switch between Basic, Formal, Executive views
- **Search Bar:** Keyword search across all templates
- **Preview Modal:** Click template to see full preview
- **Use Template Button:** One-click to apply template
- **Variable Auto-Population:** Detects existing database fields

**UI Mockup:**
```
┌────────────────────────────────────────────────────────────┐
│  [Marketing] [Email] [VPN] [Forms]                         │
├────────────────────────────────────────────────────────────┤
│  Filter: [Social Media ▼]  Style: ○ Basic ● Formal ○ Exec │
│  Search: [________________________] [🔍]                    │
├────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │Facebook  │  │Twitter   │  │LinkedIn  │  │Instagram │  │
│  │Launch    │  │Announce  │  │Update    │  │Story     │  │
│  │          │  │          │  │          │  │          │  │
│  │[Preview] │  │[Preview] │  │[Preview] │  │[Preview] │  │
│  │[Use]     │  │[Use]     │  │[Use]     │  │[Use]     │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                            │
│  Showing 10 of 50 Marketing templates                     │
└────────────────────────────────────────────────────────────┘
```

---

## ⏱️ UPDATED TIME ESTIMATES

### **Database Builder (DataForge) - COMPLETE BUILD**

**Total: 20-25 hours** (increased from 10-12 hours)

**Breakdown:**
- Original database builder tasks (10-12 hrs)
- **Task: Create 158 base templates** (3 hrs)
  - Write JSON for all templates
  - Include field definitions
  - Add validation rules
- **Task: Generate style variants** (2 hrs)
  - Apply Basic style to all templates
  - Apply Formal style to all templates
  - Apply Executive style to all templates
- **Task: Template selector UI** (1.5 hrs)
  - Category tabs
  - Style toggle
  - Search functionality
  - Preview modal
- **Task: Template file generation** (2 hrs)
  - Export all 474 JSON files
  - Organize folder structure
  - Create index/manifest
- **Testing & refinement** (2-3 hrs)

**Total Template Files Created:** 474 JSON files
**Total Lines:** ~6,000 lines (increased from ~3,000)

---

## 🎯 CRITICAL SUCCESS FACTORS

✅ **150+ Templates** (158 base templates)  
✅ **4 Categories** (Marketing, Email, VPN, Forms)  
✅ **3 Style Variants Each** (Basic, Formal, Executive)  
✅ **474 Total Template Files**  
✅ **Visual Template Selector**  
✅ **Search & Filter Functionality**  
✅ **Variable Auto-Population**  
✅ **One-Click Template Application**  
✅ **FileMaker Pro Alternative** ($588/year savings!)  

---

**END OF TEMPLATE LIBRARY ADDITION TO SECTION 16**

**This addition brings Section 16 in line with the updated MASTER_CHECKLIST_PART13.md requirements.**

