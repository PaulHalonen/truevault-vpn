# SECTION 23: ENTERPRISE PORTAL (VPN DASHBOARD INTEGRATION)
## Enterprise License Management System
**Status:** 📋 UPDATED - Portal Only (Not Full Build)  
**Created:** January 17, 2026  
**Last Updated:** January 20, 2026 - User Decision #4 Applied

---

## ⚠️ CRITICAL USER DECISION UPDATE

**CHANGED:** Enterprise portal is ONLY integrated into VPN dashboard  
**CHANGED:** Full enterprise build (HR, DataForge, WebSocket, React PWA) is a SEPARATE codebase  
**NEW:** Focus on license sales, delivery, and activation tracking  
**NEW:** 2-3 hours implementation (NOT 8-10 hours)  

---

## TABLE OF CONTENTS

1. [Overview](#1-overview)
2. [What This IS](#2-what-this-is)
3. [What This IS NOT](#3-what-this-is-not)
4. [Database Schema](#4-database-schema)
5. [License Key System](#5-license-key-system)
6. [Purchase Flow](#6-purchase-flow)
7. [Admin Management](#7-admin-management)
8. [Implementation](#8-implementation)

---

## 1. OVERVIEW

### 1.1 What Is Enterprise Portal?

The Enterprise Portal is a **lightweight integration** within the TrueVault VPN dashboard that allows customers to:

1. **Purchase** enterprise licenses via PayPal
2. **Receive** license keys automatically via email
3. **Download** enterprise software package (.zip or installer)
4. **Activate** their license keys
5. **Track** license status and expiration

**This is NOT the full enterprise product.** This is just the sales/delivery/licensing system.

### 1.2 Why Portal Only?

**User Decision #4 Rationale:**
- Full enterprise build (HR, DataForge, WebSocket, React PWA) is a **SEPARATE codebase**
- The VPN dashboard just needs to **SELL and DELIVER** the enterprise product
- Keep implementation simple: 2-3 hours instead of 8-10 hours
- License management only - no actual enterprise features in VPN dashboard
- Enterprise software runs independently after download

### 1.3 Pricing

- **Enterprise License:** $79.97/month per company
- **Includes:** 5 user seats
- **Additional Seats:** $8/month each
- **License Type:** Subscription-based, auto-renews monthly
- **Payment:** PayPal integration

---

## 2. WHAT THIS IS

### 2.1 Features Included in Portal

✅ **License Purchase Page**
- Product description
- Pricing table
- PayPal buy button
- Subscription management

✅ **License Key Generation**
- Automatic key creation on purchase
- Format: `TVPN-XXXX-XXXX-XXXX-XXXX`
- Unique per customer
- Stored in database

✅ **Email Delivery**
- Instant license key email
- Download link included
- Activation instructions
- Support contact info

✅ **Download Portal**
- Download enterprise software package
- Version history
- Installation instructions
- System requirements

✅ **License Activation Tracking**
- Track which licenses are activated
- Monitor activation date/time
- Record activation machine info
- Prevent multiple activations (optional)

✅ **Admin License Management**
- View all purchased licenses
- Manually create/revoke licenses
- View activation status
- Export license report

---

## 3. WHAT THIS IS NOT

❌ **NOT Included:**
- HR management system
- DataForge database builder
- WebSocket real-time sync
- React PWA application
- 7-tier role system
- Company-dedicated servers
- Employee management
- Time-off tracking
- Performance reviews
- Custom database creation
- Team collaboration features

**Why Not?**
These features belong in the SEPARATE enterprise product codebase, not the VPN dashboard.

---

---

## 2. ARCHITECTURE

### 2.1 Deployment Model

```
┌─────────────────────────────────────────────────────────────────┐
│                    TRUEVAULT ENTERPRISE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    WireGuard VPN    ┌────────────────────┐   │
│  │              │◄──────────────────►│                    │   │
│  │   Employee   │                     │  Company Server    │   │
│  │   Browser    │    HTTPS/WSS        │  (Contabo VPS)     │   │
│  │              │◄──────────────────►│                    │   │
│  └──────────────┘                     │  - Node.js API     │   │
│                                        │  - SQLite DBs      │   │
│  ┌──────────────┐                     │  - WireGuard       │   │
│  │              │◄──────────────────►│  - Web Dashboard   │   │
│  │   Employee   │    VPN + HTTPS      │                    │   │
│  │   (Remote)   │                     └────────────────────┘   │
│  │              │                                               │
│  └──────────────┘                                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Access Flow

1. Employee installs WireGuard app (any device)
2. Scans QR code from company dashboard
3. Connects to company VPN
4. Opens `https://dashboard.acme.truevault.app`
5. All traffic secured through company server

### 2.3 Company Server Specifications

**Auto-Provisioned on Signup:**
- **Provider:** Contabo Cloud VPS 10 SSD
- **Specs:** 4 vCPU, 6GB RAM, 150GB SSD, 32TB bandwidth
- **IP:** Dedicated IPv4
- **Software:**
  - Ubuntu 24.04 LTS
  - Node.js 20 LTS
  - WireGuard VPN server
  - SQLite databases
  - Nginx reverse proxy
  - Let's Encrypt SSL
- **Cost:** $6.75/month
- **Subdomain:** `{company-slug}.truevault.app`

### 2.4 Data Architecture

```
Company Server
├── /opt/truevault/
│   ├── server/           # Node.js backend
│   ├── frontend/         # Built React app
│   └── data/
│       ├── company.db    # Employees, roles, sessions
│       ├── hr.db         # Salary, time-off, reviews
│       ├── dataforge.db  # Custom tables/records
│       └── audit.db      # Activity logs
```

**Key Principle:** Single source of truth. No local databases. All data on company server. Real-time sync via WebSocket.

---

## 3. TECHNOLOGY STACK

### 3.1 Backend

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Runtime | Node.js | 20 LTS | JavaScript runtime |
| Framework | Express.js | 4.18 | HTTP server |
| Database | better-sqlite3 | 9.4 | SQLite driver |
| Auth | jsonwebtoken | 9.0 | JWT tokens |
| Password | bcrypt | 5.1 | Password hashing |
| Real-time | Socket.io | 4.7 | WebSocket server |
| Validation | Zod | 3.22 | Schema validation |
| Uploads | Multer | 1.4 | File uploads |
| Logging | Pino | 8.x | Structured logging |
| Security | Helmet | 7.1 | HTTP headers |

### 3.2 Frontend

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Framework | React | 18 | UI library |
| Build | Vite | 5.x | Dev server & bundler |
| Routing | React Router | 6 | Client-side routing |
| Styling | Tailwind CSS | 3.4 | Utility CSS |
| Components | shadcn/ui | latest | UI component library |
| Icons | Lucide React | latest | Icon library |
| Charts | Recharts | 2.x | Data visualization |
| Forms | React Hook Form | 7.x | Form management |
| Validation | Zod | 3.22 | Schema validation |
| State | Zustand | 4.x | Global state |
| Data | TanStack Query | 5.x | Server state |
| Real-time | Socket.io-client | 4.7 | WebSocket client |
| PWA | Vite PWA Plugin | latest | Service worker |

### 3.3 Infrastructure

| Component | Technology | Purpose |
|-----------|------------|---------|
| VPS | Contabo Cloud VPS | Company servers |
| VPN | WireGuard | Secure tunnel |
| Proxy | Nginx | Reverse proxy + SSL |
| SSL | Let's Encrypt | HTTPS certificates |
| DNS | Cloudflare | DNS management |

---

## 4. DATABASE SCHEMAS

### 4.1 company.db - Core Company Data

#### Table: roles
```sql
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,           -- 'owner', 'admin', etc.
    display_name TEXT NOT NULL,          -- 'Owner', 'Administrator'
    level INTEGER NOT NULL DEFAULT 0,    -- Permission level (10-100)
    description TEXT,
    is_system BOOLEAN DEFAULT 1,         -- Cannot be deleted
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed Data:
-- (1, 'readonly', 'Read Only', 10, 'View-only access', 1)
-- (2, 'employee', 'Employee', 20, 'Standard employee', 1)
-- (3, 'manager', 'Manager', 40, 'Team manager', 1)
-- (4, 'hr_staff', 'HR Staff', 50, 'HR team member', 1)
-- (5, 'hr_admin', 'HR Administrator', 70, 'Full HR access', 1)
-- (6, 'admin', 'Administrator', 80, 'System administrator', 1)
-- (7, 'owner', 'Owner', 100, 'Company owner', 1)
```

#### Table: permissions
```sql
CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,           -- 'employees.view'
    category TEXT NOT NULL,              -- 'employees', 'hr', 'admin'
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories: employees, hr, vpn, dataforge, admin, system
-- See Section 5 for complete permission list
```

#### Table: role_permissions
```sql
CREATE TABLE role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

#### Table: departments
```sql
CREATE TABLE departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 'Engineering'
    code TEXT UNIQUE,                    -- 'ENG'
    description TEXT,
    manager_id INTEGER,                  -- FK to employees
    parent_id INTEGER,                   -- FK to departments (hierarchy)
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
);
```

#### Table: positions
```sql
CREATE TABLE positions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,                 -- 'Senior Developer'
    department_id INTEGER,
    description TEXT,
    pay_grade TEXT,                      -- 'L4', 'L5'
    min_salary DECIMAL(12,2),
    max_salary DECIMAL(12,2),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);
```

#### Table: employees
```sql
CREATE TABLE employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Authentication
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT,
    must_change_password BOOLEAN DEFAULT 1,
    password_changed_at DATETIME,
    
    -- Personal Info
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    preferred_name TEXT,
    avatar_url TEXT,
    phone TEXT,
    personal_email TEXT,
    date_of_birth DATE,
    
    -- Employment
    employee_number TEXT UNIQUE,         -- 'EMP-0001'
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    position_id INTEGER,
    manager_id INTEGER,                  -- FK to employees
    hire_date DATE,
    termination_date DATE,
    employment_type TEXT DEFAULT 'full_time',  -- full_time, part_time, contractor
    work_location TEXT DEFAULT 'office',       -- office, remote, hybrid
    
    -- Status
    status TEXT DEFAULT 'active',        -- active, inactive, terminated, on_leave
    is_active BOOLEAN DEFAULT 1,
    last_login_at DATETIME,
    
    -- SSO
    sso_provider TEXT,                   -- 'google', 'microsoft'
    sso_id TEXT,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,
    
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_employees_role ON employees(role_id);
CREATE INDEX idx_employees_department ON employees(department_id);
CREATE INDEX idx_employees_manager ON employees(manager_id);
CREATE INDEX idx_employees_status ON employees(status);
```

#### Table: employee_emergency_contacts
```sql
CREATE TABLE employee_emergency_contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    relationship TEXT,                   -- 'spouse', 'parent', 'sibling'
    phone TEXT NOT NULL,
    email TEXT,
    is_primary BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

#### Table: sessions
```sql
CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    device_name TEXT,                    -- 'Chrome on Windows'
    device_type TEXT,                    -- 'desktop', 'mobile', 'tablet'
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_sessions_employee ON sessions(employee_id);
CREATE INDEX idx_sessions_token ON sessions(token_hash);
```

#### Table: password_resets
```sql
CREATE TABLE password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,        -- 1 hour from creation
    used_at DATETIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

#### Table: invitations
```sql
CREATE TABLE invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    position_id INTEGER,
    invite_code TEXT NOT NULL UNIQUE,    -- Random 32-char string
    invited_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,        -- 7 days from creation
    accepted_at DATETIME,
    status TEXT DEFAULT 'pending',       -- pending, accepted, expired, cancelled
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    FOREIGN KEY (invited_by) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_invitations_code ON invitations(invite_code);
CREATE INDEX idx_invitations_email ON invitations(email);
```

#### Table: vpn_devices
```sql
CREATE TABLE vpn_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,           -- 'iPhone 15 Pro'
    device_type TEXT,                    -- 'mobile', 'desktop', 'laptop'
    public_key TEXT NOT NULL UNIQUE,     -- WireGuard public key
    private_key_encrypted TEXT,          -- Optional, encrypted
    assigned_ip TEXT NOT NULL UNIQUE,    -- '10.0.0.5'
    is_active BOOLEAN DEFAULT 1,
    last_handshake DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_vpn_devices_employee ON vpn_devices(employee_id);
CREATE INDEX idx_vpn_devices_public_key ON vpn_devices(public_key);
```

#### Table: announcements
```sql
CREATE TABLE announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'info',            -- info, warning, urgent, success
    is_pinned BOOLEAN DEFAULT 0,
    published_at DATETIME,
    expires_at DATETIME,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE CASCADE
);
```

#### Table: company_settings
```sql
CREATE TABLE company_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    type TEXT DEFAULT 'string',          -- string, number, boolean, json
    category TEXT DEFAULT 'general',     -- general, branding, security, vpn
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER,
    FOREIGN KEY (updated_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Default settings:
-- company_name, company_logo, primary_color, timezone,
-- password_min_length, session_timeout, vpn_network_cidr, etc.
```

#### Table: notifications
```sql
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    type TEXT NOT NULL,                  -- 'time_off_approved', 'announcement', etc.
    title TEXT NOT NULL,
    message TEXT,
    link TEXT,                           -- Optional deep link
    is_read BOOLEAN DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_employee ON notifications(employee_id);
CREATE INDEX idx_notifications_unread ON notifications(employee_id, is_read);
```

---

### 4.2 hr.db - Human Resources Data

#### Table: compensation
```sql
CREATE TABLE compensation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,        -- FK to company.db employees
    salary_amount DECIMAL(12,2) NOT NULL,
    salary_currency TEXT DEFAULT 'USD',
    salary_frequency TEXT DEFAULT 'annual',  -- annual, monthly, hourly
    effective_date DATE NOT NULL,
    end_date DATE,
    change_reason TEXT,                  -- 'promotion', 'annual_review', 'adjustment'
    approved_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER
);

CREATE INDEX idx_compensation_employee ON compensation(employee_id);
CREATE INDEX idx_compensation_effective ON compensation(effective_date);
```

#### Table: time_off_types
```sql
CREATE TABLE time_off_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,           -- 'Vacation'
    code TEXT NOT NULL UNIQUE,           -- 'VAC'
    description TEXT,
    color TEXT DEFAULT '#3B82F6',        -- For calendar display
    default_days_per_year DECIMAL(5,2) DEFAULT 0,
    accrual_type TEXT DEFAULT 'annual',  -- annual, monthly, none
    requires_approval BOOLEAN DEFAULT 1,
    can_carry_over BOOLEAN DEFAULT 0,
    max_carry_over_days DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Default types:
-- (1, 'Vacation', 'VAC', 'Paid time off', '#10B981', 15, 'annual', 1, 1, 5)
-- (2, 'Sick Leave', 'SICK', 'Illness or medical', '#EF4444', 10, 'annual', 0, 0, 0)
-- (3, 'Personal', 'PERS', 'Personal time', '#8B5CF6', 3, 'annual', 1, 0, 0)
-- (4, 'Bereavement', 'BRV', 'Family death', '#6B7280', 5, 'none', 0, 0, 0)
-- (5, 'Jury Duty', 'JURY', 'Court service', '#F59E0B', 0, 'none', 0, 0, 0)
```

#### Table: time_off_balances
```sql
CREATE TABLE time_off_balances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    time_off_type_id INTEGER NOT NULL,
    year INTEGER NOT NULL,
    entitled_days DECIMAL(5,2) DEFAULT 0,
    used_days DECIMAL(5,2) DEFAULT 0,
    pending_days DECIMAL(5,2) DEFAULT 0,
    carried_over_days DECIMAL(5,2) DEFAULT 0,
    adjusted_days DECIMAL(5,2) DEFAULT 0,
    adjustment_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(employee_id, time_off_type_id, year),
    FOREIGN KEY (time_off_type_id) REFERENCES time_off_types(id)
);

CREATE INDEX idx_time_off_balances_employee ON time_off_balances(employee_id);
CREATE INDEX idx_time_off_balances_year ON time_off_balances(year);
```

#### Table: time_off_requests
```sql
CREATE TABLE time_off_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    time_off_type_id INTEGER NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5,2) NOT NULL,
    half_day_start BOOLEAN DEFAULT 0,    -- Morning only on start
    half_day_end BOOLEAN DEFAULT 0,      -- Afternoon only on end
    reason TEXT,
    status TEXT DEFAULT 'pending',       -- pending, approved, denied, cancelled
    reviewed_by INTEGER,
    reviewed_at DATETIME,
    review_notes TEXT,
    cancelled_at DATETIME,
    cancellation_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (time_off_type_id) REFERENCES time_off_types(id)
);

CREATE INDEX idx_time_off_requests_employee ON time_off_requests(employee_id);
CREATE INDEX idx_time_off_requests_status ON time_off_requests(status);
CREATE INDEX idx_time_off_requests_dates ON time_off_requests(start_date, end_date);
```

#### Table: documents
```sql
CREATE TABLE documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    name TEXT NOT NULL,                  -- 'W-4 Form'
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type TEXT,
    category TEXT DEFAULT 'general',     -- tax, identification, contract, etc.
    description TEXT,
    is_confidential BOOLEAN DEFAULT 0,
    expiration_date DATE,
    reminder_sent BOOLEAN DEFAULT 0,
    uploaded_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_documents_employee ON documents(employee_id);
CREATE INDEX idx_documents_category ON documents(category);
CREATE INDEX idx_documents_expiration ON documents(expiration_date);
```

#### Table: review_cycles
```sql
CREATE TABLE review_cycles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 'Q4 2025 Review'
    type TEXT DEFAULT 'annual',          -- annual, quarterly, probation
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    self_assessment_due DATE,
    manager_assessment_due DATE,
    status TEXT DEFAULT 'draft',         -- draft, active, completed
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

#### Table: reviews
```sql
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    reviewer_id INTEGER NOT NULL,
    review_type TEXT DEFAULT 'manager',  -- manager, self, peer
    
    -- Self Assessment
    self_assessment TEXT,
    self_rating INTEGER,                 -- 1-5
    self_submitted_at DATETIME,
    
    -- Manager Assessment
    manager_assessment TEXT,
    manager_rating INTEGER,              -- 1-5
    manager_submitted_at DATETIME,
    
    -- Final
    overall_rating INTEGER,              -- 1-5
    strengths TEXT,
    areas_for_improvement TEXT,
    goals_for_next_period TEXT,
    comments TEXT,
    
    -- Status
    status TEXT DEFAULT 'draft',         -- draft, in_progress, completed
    completed_at DATETIME,
    acknowledged_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(cycle_id, employee_id, review_type),
    FOREIGN KEY (cycle_id) REFERENCES review_cycles(id) ON DELETE CASCADE
);

CREATE INDEX idx_reviews_cycle ON reviews(cycle_id);
CREATE INDEX idx_reviews_employee ON reviews(employee_id);
```

#### Table: hr_notes
```sql
CREATE TABLE hr_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    note TEXT NOT NULL,
    category TEXT DEFAULT 'general',     -- general, disciplinary, commendation
    is_confidential BOOLEAN DEFAULT 1,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_hr_notes_employee ON hr_notes(employee_id);
```

#### Table: holidays
```sql
CREATE TABLE holidays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 'Christmas Day'
    date DATE NOT NULL,
    is_paid BOOLEAN DEFAULT 1,
    is_recurring BOOLEAN DEFAULT 0,      -- Same date every year
    year INTEGER,                        -- NULL if recurring
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(date, name)
);
```

---

### 4.3 dataforge.db - Custom Database Builder

#### Table: tables
```sql
CREATE TABLE tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 'Customer List'
    slug TEXT NOT NULL UNIQUE,           -- 'customer_list'
    description TEXT,
    icon TEXT DEFAULT 'table',           -- Lucide icon name
    color TEXT DEFAULT '#3B82F6',
    created_by INTEGER NOT NULL,
    is_system BOOLEAN DEFAULT 0,         -- Cannot be deleted
    is_active BOOLEAN DEFAULT 1,
    record_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tables_slug ON tables(slug);
CREATE INDEX idx_tables_created_by ON tables(created_by);
```

#### Table: fields
```sql
CREATE TABLE fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    name TEXT NOT NULL,                  -- 'Company Name'
    key TEXT NOT NULL,                   -- 'company_name'
    type TEXT NOT NULL,                  -- See field types below
    description TEXT,
    is_required BOOLEAN DEFAULT 0,
    is_unique BOOLEAN DEFAULT 0,
    is_searchable BOOLEAN DEFAULT 1,
    default_value TEXT,
    options TEXT,                        -- JSON for select/multiselect
    validation TEXT,                     -- JSON validation rules
    sort_order INTEGER DEFAULT 0,
    width INTEGER DEFAULT 200,           -- Column width in pixels
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(table_id, key),
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE INDEX idx_fields_table ON fields(table_id);

-- Field Types:
-- text, textarea, number, decimal, currency, percent,
-- date, datetime, time, checkbox, select, multiselect,
-- email, phone, url, file, image, user, lookup, formula
```

#### Table: records
```sql
CREATE TABLE records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    data TEXT NOT NULL,                  -- JSON object with field values
    created_by INTEGER NOT NULL,
    updated_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE INDEX idx_records_table ON records(table_id);
CREATE INDEX idx_records_created_by ON records(created_by);
```

#### Table: table_permissions
```sql
CREATE TABLE table_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    employee_id INTEGER,                 -- NULL means role-based
    role_id INTEGER,                     -- NULL means user-specific
    permission_level TEXT NOT NULL,      -- 'view', 'edit', 'full'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE INDEX idx_table_permissions_table ON table_permissions(table_id);
CREATE INDEX idx_table_permissions_employee ON table_permissions(employee_id);
CREATE INDEX idx_table_permissions_role ON table_permissions(role_id);
```

#### Table: views
```sql
CREATE TABLE views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    name TEXT NOT NULL,                  -- 'Active Customers'
    type TEXT DEFAULT 'grid',            -- grid, kanban, calendar, gallery
    filters TEXT,                        -- JSON filter conditions
    sorts TEXT,                          -- JSON sort rules
    visible_fields TEXT,                 -- JSON array of field IDs
    group_by INTEGER,                    -- Field ID to group by
    is_default BOOLEAN DEFAULT 0,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE INDEX idx_views_table ON views(table_id);
```

#### Table: templates
```sql
CREATE TABLE templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,                  -- 'CRM - Customers'
    category TEXT NOT NULL,              -- 'crm', 'project', 'inventory', etc.
    description TEXT,
    icon TEXT,
    schema TEXT NOT NULL,                -- JSON with table/field definitions
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 50+ pre-built templates for common use cases
```

---

### 4.4 audit.db - Activity Logging

#### Table: audit_log
```sql
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER,                 -- NULL for system actions
    action TEXT NOT NULL,                -- 'create', 'update', 'delete', 'login', etc.
    resource_type TEXT NOT NULL,         -- 'employee', 'time_off_request', etc.
    resource_id INTEGER,
    old_values TEXT,                     -- JSON of previous values
    new_values TEXT,                     -- JSON of new values
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_log_employee ON audit_log(employee_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_resource ON audit_log(resource_type, resource_id);
CREATE INDEX idx_audit_log_created ON audit_log(created_at);
```

#### Table: login_attempts
```sql
CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    success BOOLEAN NOT NULL,
    failure_reason TEXT,                 -- 'invalid_password', 'account_locked', etc.
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_login_attempts_email ON login_attempts(email);
CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address);
CREATE INDEX idx_login_attempts_created ON login_attempts(created_at);
```

---

## 5. ROLE & PERMISSION SYSTEM

### 5.1 Role Hierarchy

| Role | Level | Description | Access |
|------|-------|-------------|--------|
| OWNER | 100 | Company owner | Everything |
| ADMIN | 80 | IT/System admin | All except billing, ownership |
| HR_ADMIN | 70 | HR department head | All HR including salary |
| HR_STAFF | 50 | HR team member | HR except salary |
| MANAGER | 40 | Team lead | Direct reports only |
| EMPLOYEE | 20 | Standard user | Self-service only |
| READONLY | 10 | View-only | Read access only |

### 5.2 Permission Categories

#### Employees (employees.*)
```
employees.view         - View employee directory
employees.view.details - View employee profiles
employees.create       - Create new employees
employees.update       - Update employee info
employees.update.own   - Update own profile
employees.deactivate   - Deactivate employees
employees.delete       - Delete employees (soft)
```

#### HR (hr.*)
```
hr.compensation.view   - View salary data
hr.compensation.manage - Modify salary data
hr.timeoff.view        - View all time-off
hr.timeoff.manage      - Approve/deny time-off
hr.timeoff.request     - Submit time-off (own)
hr.documents.view      - View HR documents
hr.documents.manage    - Upload/delete documents
hr.reviews.view        - View all reviews
hr.reviews.manage      - Create/manage review cycles
hr.notes.view          - View HR notes
hr.notes.create        - Create HR notes
```

#### VPN (vpn.*)
```
vpn.devices.view       - View own devices
vpn.devices.manage     - Add/remove own devices
vpn.devices.admin      - Manage all devices
vpn.config.view        - View VPN config
vpn.config.manage      - Modify VPN settings
```

#### DataForge (dataforge.*)
```
dataforge.tables.view  - View tables
dataforge.tables.create - Create tables
dataforge.tables.manage - Modify table structure
dataforge.tables.delete - Delete tables
dataforge.records.view  - View records (per-table)
dataforge.records.create - Create records
dataforge.records.update - Update records
dataforge.records.delete - Delete records
```

#### Admin (admin.*)
```
admin.users.view       - View all users
admin.users.manage     - Manage user accounts
admin.roles.view       - View roles
admin.roles.manage     - Modify roles/permissions
admin.settings.view    - View company settings
admin.settings.manage  - Modify settings
admin.audit.view       - View audit logs
admin.invitations.manage - Send/manage invitations
admin.announcements.manage - Create announcements
```

#### System (system.*)
```
system.backup.create   - Create backups
system.backup.restore  - Restore backups
system.api.access      - Access API endpoints
system.impersonate     - Login as another user
```

### 5.3 Default Role Permissions

#### OWNER (all permissions)
All permissions in all categories.

#### ADMIN
```
employees.*, hr.timeoff.view, hr.documents.view,
vpn.*, dataforge.*, admin.* (except roles.manage),
system.backup.*, system.api.access
```

#### HR_ADMIN
```
employees.view, employees.view.details, employees.create, employees.update,
hr.*, vpn.devices.view, dataforge.tables.view, dataforge.records.*
```

#### HR_STAFF
```
employees.view, employees.view.details,
hr.* (except hr.compensation.*), vpn.devices.view
```

#### MANAGER
```
employees.view, employees.view.details (direct reports),
hr.timeoff.view (direct reports), hr.timeoff.manage (direct reports),
hr.reviews.view (direct reports), vpn.devices.view,
dataforge.tables.view, dataforge.records.view
```

#### EMPLOYEE
```
employees.view, employees.update.own,
hr.timeoff.request, hr.timeoff.view (own),
hr.documents.view (own), hr.reviews.view (own),
vpn.devices.view, vpn.devices.manage,
dataforge.tables.view (permitted), dataforge.records.* (permitted)
```

#### READONLY
```
employees.view, hr.timeoff.view (own),
vpn.devices.view, dataforge.tables.view (permitted)
```

---

## 6. API ENDPOINTS

### 6.1 Authentication

#### POST /api/auth/login
```
Request:
{
  "email": "john@company.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": 1,
    "email": "john@company.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": "employee",
    "permissions": ["employees.view", "hr.timeoff.request", ...]
  }
}
```

#### POST /api/auth/logout
```
Headers: Authorization: Bearer {token}
Response: { "success": true }
```

#### GET /api/auth/me
```
Headers: Authorization: Bearer {token}
Response: { "user": {...} }
```

#### POST /api/auth/password/change
```
Request:
{
  "current_password": "oldpass",
  "new_password": "newpass123",
  "confirm_password": "newpass123"
}
```

#### POST /api/auth/password/reset-request
```
Request: { "email": "john@company.com" }
Response: { "success": true, "message": "Reset email sent" }
```

#### POST /api/auth/password/reset
```
Request:
{
  "token": "reset-token-here",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

#### POST /api/auth/refresh
```
Headers: Authorization: Bearer {token}
Response: { "token": "new-token..." }
```

#### GET /api/auth/sessions
```
Response: [
  {
    "id": 1,
    "device_name": "Chrome on Windows",
    "ip_address": "192.168.1.1",
    "created_at": "2025-01-17T10:00:00Z",
    "last_used_at": "2025-01-17T14:30:00Z",
    "is_current": true
  }
]
```

#### DELETE /api/auth/sessions/:id
Revoke a specific session.

---

### 6.2 Employees

#### GET /api/employees
```
Query: ?search=john&department=1&status=active&page=1&limit=20
Response:
{
  "data": [...],
  "pagination": { "page": 1, "limit": 20, "total": 45 }
}
```

#### GET /api/employees/:id
```
Response: { employee object with full details }
```

#### POST /api/employees (HR+)
```
Request:
{
  "email": "new@company.com",
  "first_name": "Jane",
  "last_name": "Smith",
  "role_id": 2,
  "department_id": 1,
  "position_id": 3,
  "hire_date": "2025-01-15"
}
```

#### PATCH /api/employees/:id
```
Request: { fields to update }
```

#### POST /api/employees/:id/deactivate (HR+)
```
Request: { "termination_date": "2025-01-31", "reason": "Resignation" }
```

#### POST /api/employees/:id/emergency-contacts
```
Request:
{
  "name": "Jane Doe",
  "relationship": "spouse",
  "phone": "555-1234",
  "is_primary": true
}
```

---

### 6.3 Time-Off

#### GET /api/timeoff/types
```
Response: [ array of time-off types ]
```

#### GET /api/timeoff/balances
```
Query: ?year=2025
Response: [ balances for current user by type ]
```

#### GET /api/timeoff/requests
```
Query: ?status=pending&year=2025
Response: [ user's time-off requests ]
```

#### POST /api/timeoff/requests
```
Request:
{
  "time_off_type_id": 1,
  "start_date": "2025-02-01",
  "end_date": "2025-02-05",
  "reason": "Family vacation"
}
```

#### POST /api/timeoff/requests/:id/cancel
```
Request: { "reason": "Plans changed" }
```

#### POST /api/timeoff/requests/:id/review (Manager+)
```
Request:
{
  "status": "approved",
  "notes": "Enjoy your vacation!"
}
```

#### GET /api/timeoff/team (Manager+)
```
Response: [ time-off requests for direct reports ]
```

#### GET /api/timeoff/calendar
```
Query: ?month=2025-02
Response: [ calendar events for team ]
```

---

### 6.4 VPN Devices

#### GET /api/vpn/devices
```
Response: [ user's VPN devices ]
```

#### POST /api/vpn/devices
```
Request:
{
  "device_name": "iPhone 15 Pro",
  "device_type": "mobile"
}

Response:
{
  "device": {...},
  "config": "WireGuard config string",
  "qr_code": "base64 QR image"
}
```

#### GET /api/vpn/devices/:id/config
```
Query: ?format=file|qr
Response: Config file or QR code
```

#### DELETE /api/vpn/devices/:id
Revoke device.

#### GET /api/vpn/admin/devices (Admin+)
```
Response: [ all VPN devices ]
```

#### POST /api/vpn/admin/devices/:id/revoke (Admin+)
Admin revoke any device.

---

### 6.5 Admin

#### GET /api/admin/stats
```
Response:
{
  "total_employees": 45,
  "active_employees": 42,
  "pending_timeoff": 3,
  "vpn_devices": 87,
  "announcements": 2
}
```

#### GET /api/admin/users
```
Query: ?search=&role=&status=&page=1
Response: [ paginated user list ]
```

#### PATCH /api/admin/users/:id/role
```
Request: { "role_id": 3 }
```

#### GET /api/admin/audit-logs
```
Query: ?action=&resource_type=&employee_id=&from=&to=&page=1
Response: [ paginated audit logs ]
```

#### POST /api/admin/invitations
```
Request:
{
  "email": "newuser@company.com",
  "role_id": 2,
  "department_id": 1
}
```

#### GET /api/admin/invitations
```
Response: [ pending invitations ]
```

#### GET /api/admin/roles
```
Response: [ all roles with permissions ]
```

#### GET /api/admin/departments
```
Response: [ all departments ]
```

#### POST /api/admin/departments
```
Request:
{
  "name": "Marketing",
  "code": "MKT",
  "manager_id": 5
}
```

#### GET /api/admin/settings
```
Response: { all company settings }
```

#### PATCH /api/admin/settings
```
Request: { "key": "value", ... }
```

#### POST /api/admin/announcements
```
Request:
{
  "title": "Office Closure",
  "content": "Office will be closed on...",
  "type": "info",
  "expires_at": "2025-02-01"
}
```

---

### 6.6 DataForge

#### GET /api/dataforge/tables
```
Response: [ tables user has access to ]
```

#### GET /api/dataforge/tables/:slug
```
Response: { table with fields }
```

#### POST /api/dataforge/tables
```
Request:
{
  "name": "Customer List",
  "description": "All customers",
  "icon": "users",
  "fields": [
    { "name": "Company Name", "key": "company_name", "type": "text", "is_required": true },
    { "name": "Contact Email", "key": "email", "type": "email" },
    { "name": "Status", "key": "status", "type": "select", "options": ["Active", "Inactive"] }
  ]
}
```

#### PATCH /api/dataforge/tables/:slug
Update table metadata or fields.

#### DELETE /api/dataforge/tables/:slug
Soft delete table.

#### GET /api/dataforge/tables/:slug/records
```
Query: ?search=&filter=&sort=&page=1
Response: { "data": [...], "pagination": {...} }
```

#### POST /api/dataforge/tables/:slug/records
```
Request: { field_key: value, ... }
```

#### PATCH /api/dataforge/tables/:slug/records/:id
```
Request: { fields to update }
```

#### DELETE /api/dataforge/tables/:slug/records/:id
Delete record.

#### GET /api/dataforge/templates
```
Response: [ available templates ]
```

#### POST /api/dataforge/templates/:id/use
Create table from template.

---

## 7. AUTHENTICATION FLOW

### 7.1 Login Flow

```
1. User enters email + password
2. POST /api/auth/login
3. Server:
   a. Find employee by email
   b. Verify password with bcrypt
   c. Check account status (active, not locked)
   d. Create session record
   e. Generate JWT token (7-day expiry)
   f. Log login attempt (success)
   g. Return token + user data
4. Client stores token in localStorage
5. Client sets Authorization header for all requests
6. WebSocket connects with token
```

### 7.2 JWT Token Structure

```javascript
{
  "sub": 1,                    // Employee ID
  "email": "john@company.com",
  "role": "employee",
  "role_level": 20,
  "session_id": 123,
  "iat": 1705500000,
  "exp": 1706104800           // 7 days
}
```

### 7.3 Request Authentication

```javascript
// Every API request:
1. Extract token from Authorization: Bearer {token}
2. Verify JWT signature
3. Check token not expired
4. Load session from database
5. Verify session is active
6. Load employee + role + permissions
7. Attach to request: req.user, req.permissions
8. Continue to route handler
```

### 7.4 Permission Checking

```javascript
// Middleware: requirePermission('employees.view')
1. Check req.permissions includes 'employees.view'
2. If yes: continue
3. If no: return 403 Forbidden
```

---

## 8. WEBSOCKET EVENTS

### 8.1 Connection

```javascript
// Client connects with token
const socket = io('wss://dashboard.company.truevault.app', {
  auth: { token: 'jwt-token-here' }
});

// Server authenticates and joins rooms
socket.join(`user:${userId}`);
socket.join(`role:${roleName}`);
socket.join(`department:${departmentId}`);
```

### 8.2 Events

#### Server → Client

```javascript
// Announcement broadcast
socket.emit('announcement', {
  id: 1,
  title: 'Office Update',
  content: '...',
  type: 'info'
});

// Time-off status update
socket.emit('timeoff:updated', {
  id: 5,
  status: 'approved',
  reviewed_by: 'Jane Manager'
});

// Employee directory update
socket.emit('employee:updated', {
  id: 10,
  changes: ['department', 'position']
});

// DataForge record update
socket.emit('dataforge:record:updated', {
  table_slug: 'customers',
  record_id: 42,
  updated_by: 'John Doe'
});

// Notification
socket.emit('notification', {
  id: 100,
  type: 'timeoff_approved',
  title: 'Time-off Approved',
  message: 'Your vacation request was approved'
});

// VPN device status
socket.emit('vpn:device:status', {
  device_id: 5,
  online: true,
  last_handshake: '2025-01-17T10:00:00Z'
});
```

#### Client → Server

```javascript
// Join DataForge table room
socket.emit('dataforge:join', { table_slug: 'customers' });

// Leave DataForge table room
socket.emit('dataforge:leave', { table_slug: 'customers' });

// Mark notification read
socket.emit('notification:read', { id: 100 });
```

---

## 9. FRONTEND COMPONENTS

### 9.1 Page Structure

```
/                     → Redirect to /dashboard
/login                → Login page
/accept-invite/:code  → Accept invitation page

/dashboard            → Main dashboard (role-based content)

/my                   → Employee self-service
  /my/profile         → View/edit own profile
  /my/timeoff         → Request time-off, view balances
  /my/devices         → Manage VPN devices
  /my/notifications   → View notifications

/directory            → Employee directory
/directory/:id        → Employee profile view

/hr                   → HR section (HR_STAFF+)
  /hr/employees       → Employee management
  /hr/employees/:id   → Employee detail
  /hr/timeoff         → Time-off management
  /hr/documents       → Document management
  /hr/reviews         → Performance reviews

/manager              → Manager section (MANAGER+)
  /manager/team       → Direct reports
  /manager/timeoff    → Team time-off approvals
  /manager/reviews    → Team reviews

/admin                → Admin section (ADMIN+)
  /admin/users        → User management
  /admin/roles        → Role management
  /admin/departments  → Department management
  /admin/settings     → Company settings
  /admin/audit        → Audit logs
  /admin/vpn          → VPN management

/owner                → Owner section (OWNER only)
  /owner/billing      → Subscription management
  /owner/transfer     → Transfer ownership

/dataforge            → DataForge (all users, per-table permissions)
  /dataforge          → Table list
  /dataforge/:slug    → Table view/edit
```

### 9.2 Component Library

Built with shadcn/ui components:

```
Layout
├── Sidebar           - Navigation sidebar
├── Header            - Top bar with user menu
├── Breadcrumb        - Page navigation
└── Footer            - Page footer

Data Display
├── DataTable         - Sortable/filterable table
├── Card              - Info cards
├── Badge             - Status badges
├── Avatar            - User avatars
├── Stat              - Statistic display
└── Timeline          - Activity timeline

Forms
├── Input             - Text input
├── Select            - Dropdown select
├── DatePicker        - Date selection
├── DateRangePicker   - Date range
├── Checkbox          - Boolean input
├── RadioGroup        - Single select
├── Textarea          - Multi-line text
├── FileUpload        - File upload
└── Form              - Form wrapper

Feedback
├── Alert             - Alert messages
├── Toast             - Toast notifications
├── Dialog            - Modal dialogs
├── Sheet             - Slide-out panels
├── Skeleton          - Loading skeletons
└── Spinner           - Loading spinner

Navigation
├── Tabs              - Tab navigation
├── Dropdown          - Dropdown menu
├── Command           - Command palette
└── Pagination        - Page navigation
```

---

## 10. DATAFORGE BUILDER

### 10.1 Field Types

| Type | Description | Options |
|------|-------------|---------|
| text | Single line text | maxLength, minLength |
| textarea | Multi-line text | maxLength, rows |
| number | Integer | min, max |
| decimal | Decimal number | min, max, precision |
| currency | Money | currency, min, max |
| percent | Percentage | min, max |
| date | Date only | min, max |
| datetime | Date and time | min, max |
| time | Time only | - |
| checkbox | Boolean | - |
| select | Single select | options[] |
| multiselect | Multi select | options[] |
| email | Email address | - |
| phone | Phone number | format |
| url | Web URL | - |
| file | File attachment | maxSize, allowedTypes |
| image | Image file | maxSize, maxWidth, maxHeight |
| user | Employee reference | - |
| lookup | Reference to another table | tableId, displayField |
| formula | Calculated field | expression |

### 10.2 Pre-Built Templates (50+)

#### CRM
- Customers
- Contacts
- Leads
- Deals/Opportunities
- Activities

#### Project Management
- Projects
- Tasks
- Milestones
- Time Tracking
- Resources

#### Inventory
- Products
- Inventory
- Suppliers
- Purchase Orders
- Stock Movements

#### HR (Additional)
- Job Postings
- Applicants
- Interview Schedules
- Training Records
- Certifications

#### Finance
- Expenses
- Invoices
- Budgets
- Assets
- Vendors

#### Operations
- Equipment
- Maintenance Logs
- Locations
- Shipping
- Quality Control

---

## 11. VPN INTEGRATION

### 11.1 WireGuard Configuration

#### Server Config
```ini
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = {SERVER_PRIVATE_KEY}
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peers added dynamically per employee device
[Peer]
# Employee: John Doe - iPhone
PublicKey = {DEVICE_PUBLIC_KEY}
AllowedIPs = 10.0.0.5/32
```

#### Client Config (Generated)
```ini
[Interface]
PrivateKey = {DEVICE_PRIVATE_KEY}
Address = 10.0.0.5/32
DNS = 10.0.0.1

[Peer]
PublicKey = {SERVER_PUBLIC_KEY}
Endpoint = vpn.company.truevault.app:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25
```

### 11.2 IP Assignment

- Server: 10.0.0.1
- DHCP Range: 10.0.0.2 - 10.0.0.254
- Maximum devices: 253
- 5 devices per employee (default)

### 11.3 Device Management

1. Employee creates device via dashboard
2. Server generates key pair (or accepts provided public key)
3. Assigns next available IP
4. Adds peer to WireGuard server
5. Returns config file + QR code
6. Employee imports to WireGuard app

---

## 12. FILE STRUCTURE

### 12.1 Server

```
enterprise-hub/
├── server/
│   ├── package.json
│   ├── .env.example
│   ├── src/
│   │   ├── index.js              # Entry point
│   │   ├── config/
│   │   │   ├── database.js       # DB connections
│   │   │   └── env.js            # Environment config
│   │   ├── middleware/
│   │   │   ├── auth.js           # JWT auth
│   │   │   ├── permissions.js    # Permission checking
│   │   │   ├── errorHandler.js   # Error handling
│   │   │   └── audit.js          # Audit logging
│   │   ├── routes/
│   │   │   ├── auth.js           # /api/auth/*
│   │   │   ├── employees.js      # /api/employees/*
│   │   │   ├── timeoff.js        # /api/timeoff/*
│   │   │   ├── vpn.js            # /api/vpn/*
│   │   │   ├── admin.js          # /api/admin/*
│   │   │   └── dataforge.js      # /api/dataforge/*
│   │   ├── services/
│   │   │   ├── auth.service.js
│   │   │   ├── employee.service.js
│   │   │   ├── timeoff.service.js
│   │   │   ├── vpn.service.js
│   │   │   └── dataforge.service.js
│   │   ├── utils/
│   │   │   ├── jwt.js
│   │   │   ├── password.js
│   │   │   └── validators.js
│   │   └── websocket/
│   │       └── handler.js        # WebSocket events
│   └── database/
│       ├── schema-company.sql
│       ├── schema-hr.sql
│       ├── schema-dataforge.sql
│       ├── schema-audit.sql
│       └── seed-data.sql
```

### 12.2 Frontend

```
├── frontend/
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.js
│   ├── index.html
│   └── src/
│       ├── main.jsx
│       ├── App.jsx
│       ├── components/
│       │   ├── ui/               # shadcn/ui components
│       │   ├── layout/
│       │   │   ├── Sidebar.jsx
│       │   │   ├── Header.jsx
│       │   │   └── Layout.jsx
│       │   ├── forms/
│       │   │   ├── LoginForm.jsx
│       │   │   ├── EmployeeForm.jsx
│       │   │   └── TimeOffForm.jsx
│       │   └── shared/
│       │       ├── DataTable.jsx
│       │       ├── PermissionGate.jsx
│       │       └── Loading.jsx
│       ├── pages/
│       │   ├── Login.jsx
│       │   ├── Dashboard.jsx
│       │   ├── my/
│       │   ├── hr/
│       │   ├── manager/
│       │   ├── admin/
│       │   ├── owner/
│       │   └── dataforge/
│       ├── hooks/
│       │   ├── useAuth.js
│       │   ├── usePermissions.js
│       │   └── useWebSocket.js
│       ├── store/
│       │   └── authStore.js
│       ├── api/
│       │   └── client.js
│       └── lib/
│           └── utils.js
```

---

## APPENDIX A: SEED DATA

### Roles
```sql
INSERT INTO roles (name, display_name, level, description, is_system) VALUES
('readonly', 'Read Only', 10, 'View-only access to permitted areas', 1),
('employee', 'Employee', 20, 'Standard employee with self-service access', 1),
('manager', 'Manager', 40, 'Team manager with direct report access', 1),
('hr_staff', 'HR Staff', 50, 'HR team member without salary access', 1),
('hr_admin', 'HR Administrator', 70, 'Full HR access including compensation', 1),
('admin', 'Administrator', 80, 'System administrator', 1),
('owner', 'Owner', 100, 'Company owner with full access', 1);
```

### Permissions (50+)
```sql
INSERT INTO permissions (name, category, description) VALUES
-- Employees
('employees.view', 'employees', 'View employee directory'),
('employees.view.details', 'employees', 'View detailed employee profiles'),
('employees.create', 'employees', 'Create new employees'),
('employees.update', 'employees', 'Update employee information'),
('employees.update.own', 'employees', 'Update own profile'),
('employees.deactivate', 'employees', 'Deactivate employee accounts'),
('employees.delete', 'employees', 'Delete employees'),

-- HR
('hr.compensation.view', 'hr', 'View salary and compensation'),
('hr.compensation.manage', 'hr', 'Modify compensation records'),
('hr.timeoff.view', 'hr', 'View all time-off requests'),
('hr.timeoff.manage', 'hr', 'Approve or deny time-off'),
('hr.timeoff.request', 'hr', 'Submit own time-off requests'),
('hr.documents.view', 'hr', 'View employee documents'),
('hr.documents.manage', 'hr', 'Upload and manage documents'),
('hr.reviews.view', 'hr', 'View performance reviews'),
('hr.reviews.manage', 'hr', 'Create and manage review cycles'),
('hr.notes.view', 'hr', 'View HR notes'),
('hr.notes.create', 'hr', 'Create HR notes'),

-- VPN
('vpn.devices.view', 'vpn', 'View own VPN devices'),
('vpn.devices.manage', 'vpn', 'Add and remove own devices'),
('vpn.devices.admin', 'vpn', 'Manage all VPN devices'),
('vpn.config.view', 'vpn', 'View VPN configuration'),
('vpn.config.manage', 'vpn', 'Modify VPN settings'),

-- DataForge
('dataforge.tables.view', 'dataforge', 'View DataForge tables'),
('dataforge.tables.create', 'dataforge', 'Create new tables'),
('dataforge.tables.manage', 'dataforge', 'Modify table structure'),
('dataforge.tables.delete', 'dataforge', 'Delete tables'),
('dataforge.records.view', 'dataforge', 'View records'),
('dataforge.records.create', 'dataforge', 'Create records'),
('dataforge.records.update', 'dataforge', 'Update records'),
('dataforge.records.delete', 'dataforge', 'Delete records'),

-- Admin
('admin.users.view', 'admin', 'View all users'),
('admin.users.manage', 'admin', 'Manage user accounts'),
('admin.roles.view', 'admin', 'View roles'),
('admin.roles.manage', 'admin', 'Modify roles and permissions'),
('admin.settings.view', 'admin', 'View company settings'),
('admin.settings.manage', 'admin', 'Modify company settings'),
('admin.audit.view', 'admin', 'View audit logs'),
('admin.invitations.manage', 'admin', 'Send and manage invitations'),
('admin.announcements.manage', 'admin', 'Create and manage announcements'),

-- System
('system.backup.create', 'system', 'Create system backups'),
('system.backup.restore', 'system', 'Restore from backups'),
('system.api.access', 'system', 'Access API endpoints'),
('system.impersonate', 'system', 'Impersonate other users');
```

### Time-Off Types
```sql
INSERT INTO time_off_types (name, code, description, color, default_days_per_year, accrual_type, requires_approval, can_carry_over, max_carry_over_days) VALUES
('Vacation', 'VAC', 'Paid time off for vacation', '#10B981', 15, 'annual', 1, 1, 5),
('Sick Leave', 'SICK', 'Time off for illness or medical appointments', '#EF4444', 10, 'annual', 0, 0, 0),
('Personal', 'PERS', 'Personal time off', '#8B5CF6', 3, 'annual', 1, 0, 0),
('Bereavement', 'BRV', 'Time off for family death', '#6B7280', 5, 'none', 0, 0, 0),
('Jury Duty', 'JURY', 'Time off for court service', '#F59E0B', 0, 'none', 0, 0, 0),
('Parental Leave', 'PAR', 'Maternity/paternity leave', '#EC4899', 0, 'none', 1, 0, 0);
```

---

## APPENDIX B: ENVIRONMENT VARIABLES

```bash
# Server
PORT=3000
HOST=0.0.0.0
NODE_ENV=production

# JWT
JWT_SECRET=your-256-bit-secret-key-here
JWT_EXPIRES_IN=7d

# Databases
DB_COMPANY=./data/company.db
DB_HR=./data/hr.db
DB_DATAFORGE=./data/dataforge.db
DB_AUDIT=./data/audit.db

# VPN
VPN_SERVER_ENDPOINT=vpn.company.truevault.app:51820
VPN_SERVER_PUBLIC_KEY=server-public-key
VPN_NETWORK_CIDR=10.0.0.0/24
VPN_SERVER_PRIVATE_KEY=server-private-key

# Email (optional)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=noreply@company.com
SMTP_PASS=smtp-password
SMTP_FROM=noreply@company.com

# SSO (optional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=

# Frontend
FRONTEND_URL=https://dashboard.company.truevault.app

# Logging
LOG_LEVEL=info
```

---

---

## 13. EMPLOYEE EFFECTIVENESS FEATURES

### 13.1 Zero Friction Design Principles

Every feature must answer: "Does this save the employee time?"

**PRINCIPLE 1: ONE-CLICK ACTIONS**
If it takes more than ONE click, redesign it.
- ❌ BEFORE: Click menu → Click Time-Off → Click New → Fill form → Submit
- ✅ AFTER: Click "🏖️" button → Date picker → Done!

**PRINCIPLE 2: SMART DEFAULTS**
Pre-fill everything the system already knows.
- ❌ BEFORE: Employee fills: Name, Email, Department, Manager...
- ✅ AFTER: System knows all this. Just pick the date!

**PRINCIPLE 3: PROACTIVE INFORMATION**
Show info BEFORE they ask for it.
- ❌ BEFORE: Employee wonders "Do I have enough PTO?"
- ✅ AFTER: Dashboard shows: "12 days available" prominently

**PRINCIPLE 4: CONTEXTUAL ACTIONS**
Show the right actions at the right time.
- ❌ BEFORE: Generic dashboard, employee hunts for what they need
- ✅ AFTER: "3 tasks due today" with direct links to complete them

**PRINCIPLE 5: MOBILE FIRST**
Everything must work perfectly on a phone.
- ❌ BEFORE: "Please use desktop for this feature"
- ✅ AFTER: Tap, swipe, done. Same on every device.

### 13.2 Smart Dashboard Widgets

**WIDGET 1: TODAY'S FOCUS**
- Shows tasks due today with one-click complete buttons
- Shows overdue tasks with escalation indicators
- Direct action buttons for each item

**WIDGET 2: MY TIME-OFF AT A GLANCE**
- PTO balance with visual progress bar
- Sick leave balance
- Upcoming approved time-off
- Quick "Request Time-Off" button

**WIDGET 3: QUICK ACTIONS**
- Add Task button
- Request PTO button
- My Tasks button
- Team Directory button
- Find Person button

**WIDGET 4: COMPANY UPDATES**
- Recent announcements
- Birthday celebrations
- Company news
- "View All Announcements" link

**WIDGET 5: MY TEAM (Managers Only)**
- Team member online/offline status
- Location indicators (Office/Remote/Meeting)
- PTO status for team members
- Pending approvals count with "Review Now" button

### 13.3 One-Tap Workflows

**Time-Off Request (Simplified)**
1. Select type (dropdown with smart default)
2. Pick dates on calendar (visual selection)
3. Auto-shows: Summary, balance after, manager to notify
4. Optional notes field
5. Team conflict detection
6. Submit → Instant confirmation

**Task Completion**
- Quick Complete: Single tap [Complete ✓] button
- Complete with Notes: Long-press → Options menu
- Swipe right to complete
- Options: Snooze, Change due date, Delete

### 13.4 Universal Search (Cmd+K)

**Find Anything Instantly:**
- Keyboard shortcut: Cmd+K (Mac) / Ctrl+K (Windows)
- Mobile: Tap search icon
- Shows recent searches
- Shows quick actions

**Search Results Include:**
- Employees (name, email, extension)
- Customers (company, contact info)
- Tasks (title, due date, assignee)
- Projects
- Documents

### 13.5 Smart Notification System

**Priority Levels:**

🔴 **URGENT** (Immediate push + sound)
- Time-off denied
- High priority task assigned
- System security alert
- Manager needs response now

🟡 **IMPORTANT** (Push notification)
- Time-off approved
- New task assigned
- Approval waiting for you
- Task due tomorrow

🟢 **INFO** (In-app only)
- Company announcement
- Birthday celebration
- Someone mentioned you
- Weekly digest available

**Smart Bundling:**
Instead of 10 separate notifications:
- ❌ "John commented" "Mike commented" "Sarah commented"...
- ✅ "3 new comments on Q1 Project" [View All]

**Quiet Hours:**
- Weekdays: 8 PM - 8 AM (no push)
- Weekends: All day (configurable)
- Exception: 🔴 Urgent still gets through

### 13.6 Integrated Calendar View

**Features:**
- Week/Month toggle
- Color-coded events:
  - 🏖️ Time-Off
  - 📋 Task Due
  - 🗓️ Meeting
  - 🎂 Birthday
- Upcoming events list
- Click to view/edit details

### 13.7 Personal Stats & Insights

**Productivity Metrics:**
- Tasks completed this month
- On-time rate percentage
- Weekly trend chart
- Comparison to last month

**Time-Off Usage:**
- PTO days used vs total
- Sick days used vs total
- Visual progress bars
- Tips: "You have 8 PTO days left. Plan before Dec 31!"

### 13.8 Mobile-Optimized Workflows

**Mobile Dashboard:**
- Thumb-friendly action buttons
- VPN connection status
- Quick actions row
- Collapsible task list
- Time-off balance summary
- Bottom navigation bar

**Swipe Gestures:**

| Element | Swipe Left | Swipe Right |
|---------|------------|-------------|
| Task | Edit/Delete | Complete ✓ |
| Notification | Dismiss | Mark as Read |
| Time-Off Request (Manager) | Deny | Approve |

### 13.9 Time Savings Summary

| Activity | Before | After | Daily Savings |
|----------|--------|-------|---------------|
| Task Management | 15 min/day | 2 min/day | 13 min |
| Finding Information | 20 min/day | 30 sec/day | 19.5 min |
| Time-Off Requests | 10 min | 1 min | 9 min |
| Status Updates | 15 min/day | 0 min | 15 min (auto) |
| Report Generation | 30 min/week | 0 min | Auto-generated |

**TOTAL DAILY SAVINGS: ~45 minutes per employee**
**MONTHLY SAVINGS: 15+ hours per employee**

---

**END OF SECTION 23 TECHNICAL SPECIFICATION**

*This document contains complete technical specifications for the Enterprise Business Hub feature of TrueVault VPN. All database schemas, API endpoints, authentication flows, and component structures are defined here.*
## 4. DATABASE SCHEMA

### 4.1 Table 1: licenses

```sql
CREATE TABLE IF NOT EXISTS licenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_key TEXT UNIQUE NOT NULL,           -- TVPN-XXXX-XXXX-XXXX-XXXX
    customer_email TEXT NOT NULL,
    customer_name TEXT,
    company_name TEXT,
    
    -- License Details
    seats_included INTEGER DEFAULT 5,
    additional_seats INTEGER DEFAULT 0,
    total_seats INTEGER GENERATED ALWAYS AS (seats_included + additional_seats),
    
    -- Pricing
    base_price REAL DEFAULT 79.97,
    additional_seat_price REAL DEFAULT 8.00,
    total_price REAL,
    
    -- Status
    status TEXT DEFAULT 'pending',              -- pending, active, suspended, expired, cancelled
    purchase_date TEXT DEFAULT CURRENT_TIMESTAMP,
    activation_date TEXT,
    expiration_date TEXT,
    auto_renew INTEGER DEFAULT 1,
    
    -- PayPal
    paypal_subscription_id TEXT,
    paypal_transaction_id TEXT,
    
    -- Activation Tracking
    is_activated INTEGER DEFAULT 0,
    activated_by_machine TEXT,                  -- Machine name/ID
    activation_ip TEXT,
    
    -- Download Tracking
    download_count INTEGER DEFAULT 0,
    last_download_date TEXT,
    
    -- Metadata
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);
```

### 4.2 Table 2: license_activations

```sql
CREATE TABLE IF NOT EXISTS license_activations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    license_id INTEGER NOT NULL,
    
    -- Activation Details
    activation_date TEXT DEFAULT CURRENT_TIMESTAMP,
    machine_name TEXT,
    machine_id TEXT,                            -- Hardware ID or fingerprint
    ip_address TEXT,
    user_agent TEXT,
    
    -- Status
    is_active INTEGER DEFAULT 1,                -- 0 = deactivated, 1 = active
    deactivation_date TEXT,
    deactivation_reason TEXT,
    
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
);
```

**Indexes:**
```sql
CREATE INDEX idx_license_key ON licenses(license_key);
CREATE INDEX idx_customer_email ON licenses(customer_email);
CREATE INDEX idx_license_status ON licenses(status);
CREATE INDEX idx_license_activation ON license_activations(license_id, is_active);
```

---

## 5. LICENSE KEY SYSTEM

### 5.1 License Key Format

**Format:** `TVPN-XXXX-XXXX-XXXX-XXXX`

**Structure:**
- Prefix: `TVPN-` (TrueVault Enterprise)
- 4 segments of 4 characters each
- Characters: A-Z, 0-9 (excluding ambiguous: 0, O, I, 1)
- Total: 20 characters (including dashes)

**Example:** `TVPN-A3K7-9FMQ-2BXR-4G8T`

### 5.2 Key Generation Algorithm

```javascript
function generateLicenseKey() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No 0,O,I,1
    const segments = 4;
    const segmentLength = 4;
    
    let key = 'TVPN-';
    
    for (let i = 0; i < segments; i++) {
        if (i > 0) key += '-';
        
        for (let j = 0; j < segmentLength; j++) {
            const randomIndex = Math.floor(Math.random() * chars.length);
            key += chars[randomIndex];
        }
    }
    
    return key;
}

// Usage
const licenseKey = generateLicenseKey();
// Returns: TVPN-A3K7-9FMQ-2BXR-4G8T
```

### 5.3 Key Validation

```javascript
function validateLicenseKey(key) {
    // Format validation
    const pattern = /^TVPN-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/;
    
    if (!pattern.test(key)) {
        return { valid: false, error: 'Invalid format' };
    }
    
    // Database lookup
    const license = db.query('SELECT * FROM licenses WHERE license_key = ?', [key]);
    
    if (!license) {
        return { valid: false, error: 'License not found' };
    }
    
    if (license.status !== 'active') {
        return { valid: false, error: `License ${license.status}` };
    }
    
    if (new Date(license.expiration_date) < new Date()) {
        return { valid: false, error: 'License expired' };
    }
    
    return { valid: true, license: license };
}
```

---

## 6. PURCHASE FLOW

### 6.1 Customer Journey

```
Customer visits /enterprise page
    ↓
Reads product description & pricing
    ↓
Clicks "Buy Now" (PayPal button)
    ↓
PayPal checkout (subscription)
    ↓
Payment successful → PayPal webhook fires
    ↓
System generates license key (TVPN-XXXX-XXXX-XXXX-XXXX)
    ↓
Stores license in database
    ↓
Sends email with:
  - License key
  - Download link
  - Activation instructions
    ↓
Customer downloads software package
    ↓
Customer installs & activates with license key
    ↓
License marked as "activated" in database
```

### 6.2 Purchase Page (/enterprise)

**File:** `/enterprise/index.php`

```php
<?php
require_once '../configs/config.php';
require_once '../includes/Database.php';
require_once '../includes/Content.php';

$content = new Content();
?>
<!DOCTYPE html>
<html>
<head>
    <title>TrueVault Enterprise - Business VPN Solution</title>
</head>
<body>

<div class="hero">
    <h1>TrueVault Enterprise</h1>
    <p>Complete Business VPN & Management Platform</p>
</div>

<div class="features">
    <h2>What's Included</h2>
    <div class="feature-grid">
        <div class="feature">
            <h3>🔒 Secure VPN</h3>
            <p>WireGuard-based corporate VPN for your entire team</p>
        </div>
        <div class="feature">
            <h3>👥 5 User Seats</h3>
            <p>Includes 5 employees, add more at $8/month each</p>
        </div>
        <div class="feature">
            <h3>📊 Management Dashboard</h3>
            <p>Web-based admin panel for team management</p>
        </div>
        <div class="feature">
            <h3>🗄️ DataForge Builder</h3>
            <p>Custom database creation tool (FileMaker alternative)</p>
        </div>
        <div class="feature">
            <h3>📧 Priority Support</h3>
            <p>24/7 support with faster response times</p>
        </div>
        <div class="feature">
            <h3>🔄 Automatic Updates</h3>
            <p>Always get the latest features and security patches</p>
        </div>
    </div>
</div>

<div class="pricing">
    <h2>Simple Pricing</h2>
    <div class="pricing-card">
        <h3>Enterprise License</h3>
        <div class="price">$79.97<span>/month</span></div>
        <ul>
            <li>✓ 5 User Seats Included</li>
            <li>✓ WireGuard VPN Access</li>
            <li>✓ Management Dashboard</li>
            <li>✓ DataForge Database Builder</li>
            <li>✓ Priority Support</li>
            <li>✓ Automatic Updates</li>
        </ul>
        
        <!-- PayPal Subscribe Button -->
        <div id="paypal-button-container"></div>
        
        <p class="pricing-note">
            Additional seats: $8/month each<br>
            Cancel anytime, no contracts
        </p>
    </div>
</div>

<div class="how-it-works">
    <h2>How It Works</h2>
    <ol>
        <li>Click "Subscribe" and complete PayPal checkout</li>
        <li>Receive license key via email instantly</li>
        <li>Download enterprise software package</li>
        <li>Install on your server or local machine</li>
        <li>Activate with your license key</li>
        <li>Add your team members and start working</li>
    </ol>
</div>

<div class="faq">
    <h2>Frequently Asked Questions</h2>
    <details>
        <summary>What happens after I purchase?</summary>
        <p>You'll instantly receive an email with your license key and download link. Installation takes about 10 minutes.</p>
    </details>
    <details>
        <summary>Can I cancel anytime?</summary>
        <p>Yes! Cancel your subscription anytime from your PayPal account. Your license remains active until the end of your billing period.</p>
    </details>
    <details>
        <summary>How many devices can each user connect?</summary>
        <p>Each user can connect unlimited devices (phone, laptop, tablet, etc.)</p>
    </details>
    <details>
        <summary>Do you offer a trial?</summary>
        <p>We offer a 30-day money-back guarantee. Try it risk-free!</p>
    </details>
</div>

<!-- PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&vault=true&intent=subscription"></script>

<script>
paypal.Buttons({
    style: {
        shape: 'rect',
        color: 'gold',
        layout: 'vertical',
        label: 'subscribe'
    },
    createSubscription: function(data, actions) {
        return actions.subscription.create({
            plan_id: '<?= PAYPAL_ENTERPRISE_PLAN_ID ?>' // Set in config
        });
    },
    onApprove: function(data, actions) {
        // Send subscription ID to our server
        fetch('/api/enterprise/activate-subscription.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                subscription_id: data.subscriptionID,
                order_id: data.orderID
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/enterprise/thank-you.php?license=' + data.license_key;
            }
        });
    }
}).render('#paypal-button-container');
</script>

</body>
</html>
```

### 6.3 License Generation on Purchase

**File:** `/api/enterprise/activate-subscription.php`

```php
<?php
require_once '../../configs/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Email.php';

$data = json_decode(file_get_contents('php://input'), true);

$subscriptionId = $data['subscription_id'];
$orderId = $data['order_id'];

// Verify PayPal subscription
$paypal = verifyPayPalSubscription($subscriptionId);

if (!$paypal['verified']) {
    echo json_encode(['success' => false, 'error' => 'Verification failed']);
    exit;
}

// Generate license key
$licenseKey = generateLicenseKey();

// Get customer details from PayPal
$customerEmail = $paypal['subscriber']['email_address'];
$customerName = $paypal['subscriber']['name']['given_name'] . ' ' . $paypal['subscriber']['name']['surname'];

// Store in database
$db = new Database('admin');
$db->insert('licenses', [
    'license_key' => $licenseKey,
    'customer_email' => $customerEmail,
    'customer_name' => $customerName,
    'seats_included' => 5,
    'additional_seats' => 0,
    'base_price' => 79.97,
    'total_price' => 79.97,
    'status' => 'active',
    'purchase_date' => date('Y-m-d H:i:s'),
    'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
    'auto_renew' => 1,
    'paypal_subscription_id' => $subscriptionId
]);

// Send email with license key
$email = new Email();
$email->sendEnterpriseLicense([
    'to' => $customerEmail,
    'name' => $customerName,
    'license_key' => $licenseKey,
    'download_link' => 'https://vpn.the-truth-publishing.com/enterprise/download.php?key=' . $licenseKey
]);

echo json_encode([
    'success' => true,
    'license_key' => $licenseKey,
    'message' => 'License activated! Check your email.'
]);
```

### 6.4 Email Template

**Subject:** Your TrueVault Enterprise License

```html
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">

<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
    <h1>🎉 Welcome to TrueVault Enterprise!</h1>
</div>

<div style="padding: 30px; background: #f9f9f9;">
    <p>Hi <?= $name ?>,</p>
    
    <p>Thank you for subscribing to TrueVault Enterprise! Your license is ready.</p>
    
    <div style="background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">Your License Key</h2>
        <div style="background: #f0f0f0; padding: 15px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 18px; text-align: center; letter-spacing: 2px;">
            <?= $license_key ?>
        </div>
        <p style="font-size: 12px; color: #666; margin: 10px 0 0 0;">Keep this key safe - you'll need it to activate the software.</p>
    </div>
    
    <h3>Next Steps:</h3>
    <ol>
        <li><strong>Download</strong> the enterprise software package:
            <br><a href="<?= $download_link ?>" style="color: #667eea;">Download TrueVault Enterprise</a>
        </li>
        <li><strong>Install</strong> on your server or local machine</li>
        <li><strong>Activate</strong> with your license key</li>
        <li><strong>Add</strong> your team members</li>
    </ol>
    
    <h3>What's Included:</h3>
    <ul>
        <li>✓ 5 User Seats</li>
        <li>✓ WireGuard VPN Server</li>
        <li>✓ Management Dashboard</li>
        <li>✓ DataForge Database Builder</li>
        <li>✓ Priority Support</li>
    </ul>
    
    <p><strong>Need Help?</strong><br>
    Email us at <a href="mailto:admin@the-truth-publishing.com">admin@the-truth-publishing.com</a></p>
    
    <p>Best regards,<br>
    The TrueVault Team</p>
</div>

<div style="background: #333; color: #999; padding: 20px; text-align: center; font-size: 12px;">
    <p>TrueVault Enterprise | Connection Point Systems Inc.</p>
    <p>Your subscription will automatically renew monthly at $79.97</p>
    <p><a href="<?= PAYPAL_MANAGE_URL ?>" style="color: #667eea;">Manage Subscription</a></p>
</div>

</body>
</html>
```

---

## 7. ADMIN MANAGEMENT

### 7.1 Admin License Dashboard

**File:** `/admin/enterprise-licenses.php`

**Features:**
- View all licenses (table)
- Filter by status (active, expired, cancelled)
- Search by email or company
- Manual license creation
- License revocation
- View activation history
- Export to CSV

**Table Columns:**
- License Key
- Customer Name/Email
- Company Name
- Status
- Purchase Date
- Expiration Date
- Activated (Yes/No)
- Seats (5 + 2 = 7)
- Actions (View, Revoke, Extend)

### 7.2 Manual License Creation

**Admin can create licenses manually for:**
- Free trials
- Partner agreements
- Testing
- Promotional offers

**Form Fields:**
- Customer Email (required)
- Customer Name
- Company Name
- Seats Included (default: 5)
- Duration (1 month, 3 months, 1 year, lifetime)
- Auto-generate key or specify custom key
- Notes

---

## 8. IMPLEMENTATION

### 8.1 File Structure

```
/enterprise/
├── index.php                   # Purchase page
├── thank-you.php               # Post-purchase confirmation
├── download.php                # Software download
├── activate.php                # License activation endpoint
└── assets/
    └── truevault-enterprise.zip  # Software package

/api/enterprise/
├── activate-subscription.php   # PayPal webhook handler
├── validate-license.php        # License validation API
├── download-software.php       # Authenticated download
└── check-activation.php        # Activation status

/admin/
├── enterprise-licenses.php     # License management dashboard
└── create-license.php          # Manual license creation
```

### 8.2 Implementation Checklist

**Phase 1: Database (20 min)**
- [ ] Create licenses table
- [ ] Create license_activations table
- [ ] Add indexes
- [ ] Test inserts/queries

**Phase 2: License Key System (30 min)**
- [ ] Implement generateLicenseKey() function
- [ ] Implement validateLicenseKey() function
- [ ] Test key generation (100 keys)
- [ ] Test validation logic

**Phase 3: Purchase Page (45 min)**
- [ ] Create /enterprise/index.php
- [ ] Add product description
- [ ] Add pricing table
- [ ] Integrate PayPal button
- [ ] Test subscription flow

**Phase 4: PayPal Integration (30 min)**
- [ ] Create activate-subscription.php
- [ ] Verify PayPal webhook
- [ ] Generate license on payment
- [ ] Store in database
- [ ] Test end-to-end purchase

**Phase 5: Email Delivery (20 min)**
- [ ] Create email template
- [ ] Send license key email
- [ ] Include download link
- [ ] Test email delivery

**Phase 6: Download Portal (10 min)**
- [ ] Create download.php
- [ ] Require license key
- [ ] Serve software package
- [ ] Track download count

**Phase 7: Admin Dashboard (30 min)**
- [ ] Create admin page
- [ ] List all licenses
- [ ] Add filters/search
- [ ] Manual license creation form
- [ ] Revoke license function

**Phase 8: Testing (15 min)**
- [ ] Test full purchase flow
- [ ] Test email delivery
- [ ] Test download
- [ ] Test admin functions
- [ ] Test license validation

**Total: 2-3 hours**

---

## 9. SUMMARY

### 9.1 What We Built

✅ **Enterprise Portal in VPN Dashboard**
- Purchase page with PayPal integration
- Automatic license key generation
- Email delivery system
- Download portal
- Admin license management

### 9.2 What We Did NOT Build

❌ **Full Enterprise Product** (separate codebase)
- HR management system
- DataForge database builder
- WebSocket real-time features
- React PWA application
- Team collaboration tools

### 9.3 Time Investment

- **Original Estimate:** 8-10 hours (full build)
- **Updated Estimate:** 2-3 hours (portal only)
- **Time Saved:** 6 hours

### 9.4 Business Value

**Revenue Potential:**
- $79.97/month per enterprise customer
- 91% profit margin ($73/month profit)
- Automated sales & delivery
- Minimal support overhead

**Customer Experience:**
- Instant license delivery
- Simple 6-step activation
- No manual intervention needed
- Professional email templates

---

**END OF SECTION 23: ENTERPRISE PORTAL**

**Implementation:** See MASTER_CHECKLIST_PART18.md for step-by-step build instructions

**Next Section:** Marketing & customer acquisition for enterprise offering

