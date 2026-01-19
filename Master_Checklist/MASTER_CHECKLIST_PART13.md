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
