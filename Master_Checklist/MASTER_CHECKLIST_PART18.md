# MASTER CHECKLIST - PART 18: ENTERPRISE BUSINESS HUB

**Created:** January 18, 2026 - 11:35 PM CST  
**Blueprint:** SECTION_23_ENTERPRISE_BUSINESS_HUB.md (2,163 lines)  
**Status:** ⏳ NOT STARTED  
**Priority:** 🟢 LOW - Advanced corporate feature  
**Estimated Time:** 8-10 hours  
**Estimated Lines:** ~2,000 lines  

---

## 📋 OVERVIEW

Build Enterprise Business Hub - Corporate VPN + HR Management + DataForge for companies.

**Core Principle:** *"Transform TrueVault from consumer VPN to complete business platform"*

**What This Includes:**
- Corporate VPN (separate from consumer service)
- HR Management System (employees, departments, time-off, reviews)
- DataForge Custom Database Builder (FileMaker alternative)
- 7-Tier Role-Based Access Control
- Real-Time Sync (WebSocket)
- React PWA Frontend

**Pricing:**
- **Corporate Plan:** $79.97/month (includes 5 seats)
- **Additional Seats:** $8/month each
- **Profit Margin:** 91.5% ($73.22 per company)

---

## 🎯 KEY FEATURES

✅ Company-dedicated VPS (each company gets own server)  
✅ Corporate WireGuard VPN  
✅ HR Management (employees, departments, positions)  
✅ Time-off management  
✅ Performance reviews  
✅ DataForge (custom database builder)  
✅ 7-tier role system (Read-Only → Owner)  
✅ Real-time updates (WebSocket)  
✅ React PWA (web-first, no desktop app needed)  
✅ 2-minute onboarding  

---

## 💾 TASK 18.1: Create Database Schema (company.db)

**Time:** 1 hour  
**Lines:** ~300 lines  
**File:** `/enterprise/setup-company-db.php`

### **Create company.db with 12 tables:**

```sql
-- TABLE 1: roles (7-tier system)
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,              -- 'owner', 'admin', 'manager', etc.
    display_name TEXT NOT NULL,
    level INTEGER NOT NULL DEFAULT 0,       -- 10=readonly, 100=owner
    description TEXT,
    is_system INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Seed 7 roles:
-- (1, 'readonly', 'Read Only', 10)
-- (2, 'employee', 'Employee', 20)
-- (3, 'manager', 'Manager', 40)
-- (4, 'hr_staff', 'HR Staff', 50)
-- (5, 'hr_admin', 'HR Admin', 70)
-- (6, 'admin', 'Administrator', 80)
-- (7, 'owner', 'Owner', 100)

-- TABLE 2: departments
CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT UNIQUE,                       -- 'ENG', 'SALES', 'HR'
    description TEXT,
    manager_id INTEGER,                     -- FK to employees
    parent_id INTEGER,                      -- FK to departments (hierarchy)
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 3: positions
CREATE TABLE IF NOT EXISTS positions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,                    -- 'Senior Developer'
    department_id INTEGER,
    description TEXT,
    pay_grade TEXT,
    min_salary REAL,
    max_salary REAL,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- TABLE 4: employees
CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    employee_number TEXT UNIQUE,            -- 'EMP-0001'
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    position_id INTEGER,
    manager_id INTEGER,
    hire_date TEXT,
    status TEXT DEFAULT 'active',           -- active, inactive, terminated
    phone TEXT,
    avatar_url TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (manager_id) REFERENCES employees(id)
);

-- TABLE 5: time_off_requests
CREATE TABLE IF NOT EXISTS time_off_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    type TEXT NOT NULL,                     -- vacation, sick, personal
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    days_count REAL NOT NULL,
    reason TEXT,
    status TEXT DEFAULT 'pending',          -- pending, approved, denied
    approved_by INTEGER,
    approved_at TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id)
);

-- TABLE 6: performance_reviews
CREATE TABLE IF NOT EXISTS performance_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    reviewer_id INTEGER NOT NULL,
    review_period TEXT NOT NULL,            -- '2026-Q1'
    overall_rating INTEGER,                 -- 1-5
    performance_rating INTEGER,
    attendance_rating INTEGER,
    teamwork_rating INTEGER,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals TEXT,
    notes TEXT,
    status TEXT DEFAULT 'draft',            -- draft, submitted, acknowledged
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    submitted_at TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (reviewer_id) REFERENCES employees(id)
);

-- TABLE 7: vpn_devices (company VPN devices)
CREATE TABLE IF NOT EXISTS vpn_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT,                       -- laptop, desktop, mobile
    wireguard_public_key TEXT NOT NULL,
    internal_ip TEXT NOT NULL,              -- 10.0.0.x
    is_active INTEGER DEFAULT 1,
    last_connected_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- TABLE 8: dataforge_tables (custom tables created)
CREATE TABLE IF NOT EXISTS dataforge_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    description TEXT,
    created_by INTEGER NOT NULL,
    fields TEXT,                            -- JSON: field definitions
    permissions TEXT,                       -- JSON: role-based access
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employees(id)
);

-- TABLE 9: sessions (authentication)
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    device_name TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT NOT NULL,
    last_activity_at TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- TABLE 10: audit_log (all actions)
CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER,
    action_type TEXT NOT NULL,
    resource_type TEXT,
    resource_id INTEGER,
    details TEXT,                           -- JSON
    ip_address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- TABLE 11: company_settings
CREATE TABLE IF NOT EXISTS company_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name TEXT NOT NULL,
    company_logo_url TEXT,
    primary_color TEXT DEFAULT '#3b82f6',
    vpn_server_ip TEXT,                     -- Dedicated Contabo VPS IP
    max_employees INTEGER DEFAULT 5,
    subscription_status TEXT DEFAULT 'active',
    billing_email TEXT,
    billing_cycle TEXT DEFAULT 'monthly',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 12: notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    type TEXT NOT NULL,                     -- time_off_approved, review_ready
    title TEXT NOT NULL,
    message TEXT,
    is_read INTEGER DEFAULT 0,
    link_url TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

### **Verification:**
- [ ] company.db created
- [ ] All 12 tables exist
- [ ] 7 roles seeded
- [ ] Can insert test data

---

## 🏢 TASK 18.2: Company Provisioning System

**Time:** 1.5 hours  
**Lines:** ~300 lines  
**File:** `/enterprise/provision-company.php`

### **Auto-Provision on Signup:**

When company signs up for Corporate plan:

1. **Create Contabo VPS**
   - API call to Contabo
   - Deploy Ubuntu + WireGuard
   - Configure firewall
   - Install dashboard

2. **Create Company Database**
   - Generate company.db
   - Seed initial data
   - Create first admin user

3. **Generate VPN Configs**
   - Create WireGuard server keys
   - Assign internal IP range (10.0.0.0/24)
   - Generate QR codes for mobile

4. **Setup Subdomain**
   - Create DNS record: company-name.truevault.app
   - Install SSL certificate
   - Configure Nginx reverse proxy

### **Provisioning API:**

```php
class CompanyProvisioner {
    public function provisionNewCompany($companyData) {
        // 1. Create Contabo VPS via API
        $server = $this->contaboAPI->createVPS([
            'plan' => 'Cloud VPS 10',
            'region' => 'US-central',
            'os' => 'ubuntu-24.04'
        ]);
        
        // 2. Wait for server ready
        $this->waitForServer($server['ip']);
        
        // 3. Deploy WireGuard + Dashboard
        $this->deployToServer($server['ip'], [
            'wireguard' => true,
            'dashboard' => true,
            'company_name' => $companyData['name']
        ]);
        
        // 4. Create company database
        $this->createCompanyDB($companyData);
        
        // 5. Setup DNS
        $this->cloudflareAPI->createDNS([
            'name' => $companyData['subdomain'],
            'type' => 'A',
            'content' => $server['ip']
        ]);
        
        // 6. Generate SSL
        $this->generateSSL($companyData['subdomain']);
        
        return [
            'company_id' => $companyId,
            'dashboard_url' => "https://{$companyData['subdomain']}.truevault.app",
            'vpn_ip' => $server['ip'],
            'admin_email' => $companyData['admin_email'],
            'status' => 'ready'
        ];
    }
}
```

### **Verification:**
- [ ] Can provision new company
- [ ] Contabo VPS created
- [ ] WireGuard installed
- [ ] Dashboard accessible
- [ ] DNS working
- [ ] SSL certificate valid

---

## 👥 TASK 18.3: HR Management System

**Time:** 2 hours  
**Lines:** ~400 lines  
**Files:** Multiple HR modules

### **Employee Management:**

**Features:**
- [ ] Add/edit/deactivate employees
- [ ] Assign roles (7-tier system)
- [ ] Assign departments
- [ ] Assign managers (hierarchy)
- [ ] Upload avatar
- [ ] View employee directory
- [ ] Org chart visualization

### **Time-Off Management:**

**Features:**
- [ ] Request time off
- [ ] Approve/deny requests (managers only)
- [ ] Calendar view of absences
- [ ] Balance tracking
- [ ] Automatic email notifications

### **Performance Reviews:**

**Features:**
- [ ] Create review templates
- [ ] Assign reviews (managers)
- [ ] Submit reviews
- [ ] Employee acknowledgment
- [ ] Review history
- [ ] Rating analytics

### **API Endpoints:**

```
POST /api/employees - Create employee
GET  /api/employees - List all employees
GET  /api/employees/:id - Get employee details
PUT  /api/employees/:id - Update employee
DELETE /api/employees/:id - Deactivate employee

POST /api/time-off - Request time off
GET  /api/time-off - List all requests
PUT  /api/time-off/:id/approve - Approve request
PUT  /api/time-off/:id/deny - Deny request

POST /api/reviews - Create review
GET  /api/reviews - List reviews
GET  /api/reviews/:id - Get review details
PUT  /api/reviews/:id - Update review
```

### **Verification:**
- [ ] Can add employees
- [ ] Roles enforce permissions
- [ ] Time-off requests work
- [ ] Reviews work
- [ ] Org chart displays

---

## 🔐 TASK 18.4: 7-Tier Role System

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/enterprise/roles-permissions.php`

### **Role Hierarchy:**

```
LEVEL 100 - OWNER
├─ Full system access
├─ Manage company settings
├─ Manage billing
├─ Create/delete any data
└─ Cannot be deleted

LEVEL 80 - ADMINISTRATOR
├─ Manage employees
├─ Manage departments
├─ View all data
├─ Cannot change billing
└─ Cannot delete owner

LEVEL 70 - HR ADMINISTRATOR
├─ Full HR access
├─ Approve time-off
├─ Conduct reviews
└─ Cannot access admin settings

LEVEL 50 - HR STAFF
├─ View HR data
├─ Submit reviews
└─ Limited editing

LEVEL 40 - MANAGER
├─ Manage team members
├─ Approve team time-off
├─ Conduct team reviews
└─ View team data only

LEVEL 20 - EMPLOYEE
├─ View own data
├─ Request time off
├─ View own reviews
└─ Submit expense reports

LEVEL 10 - READ ONLY
├─ View public data only
├─ No editing
└─ No sensitive data
```

### **Permission Check Function:**

```php
function hasPermission($employeeId, $permission) {
    // Get employee role level
    $level = getEmployeeRoleLevel($employeeId);
    
    // Check permission requirements
    $required = PERMISSIONS[$permission];
    
    return $level >= $required;
}

// Usage:
if (hasPermission($userId, 'employees.delete')) {
    // Allow deletion
} else {
    // Deny with 403 Forbidden
}
```

### **Verification:**
- [ ] All 7 roles exist
- [ ] Permission checks work
- [ ] Lower roles cannot access higher features
- [ ] Owner cannot be deleted

---

## 🗄️ TASK 18.5: DataForge (Custom Database Builder)

**Time:** 1.5 hours  
**Lines:** ~300 lines  
**File:** `/enterprise/dataforge.php`

### **DataForge Features:**

**Similar to Part 13 (Database Builder) but simpler:**
- [ ] Create custom tables
- [ ] Add fields (10 types: text, number, date, dropdown, etc.)
- [ ] Add records
- [ ] Edit records
- [ ] Role-based access (who can view/edit)
- [ ] Export to CSV

### **Key Differences from Part 13:**
- Simplified (fewer field types)
- Built into enterprise dashboard
- Role-based permissions integrated
- Real-time sync via WebSocket

### **Verification:**
- [ ] Can create tables
- [ ] Can add fields
- [ ] Can add records
- [ ] Permissions work
- [ ] Export works

---

## ⚡ TASK 18.6: Real-Time Sync (WebSocket)

**Time:** 1 hour  
**Lines:** ~200 lines  
**File:** `/enterprise/websocket-server.js`

### **WebSocket Events:**

```javascript
// Server pushes updates to all connected clients
io.on('connection', (socket) => {
    // Employee added
    socket.on('employee:added', (data) => {
        socket.broadcast.emit('employee:added', data);
    });
    
    // Time-off approved
    socket.on('timeoff:approved', (data) => {
        socket.to(`user_${data.employee_id}`).emit('notification', {
            type: 'timeoff_approved',
            message: 'Your time-off request was approved!'
        });
    });
    
    // Real-time notifications
    socket.on('notification:new', (data) => {
        socket.to(`user_${data.recipient_id}`).emit('notification', data);
    });
});
```

### **Client Integration:**

```javascript
// React hook for WebSocket
const useWebSocket = () => {
    useEffect(() => {
        const socket = io('wss://company.truevault.app');
        
        socket.on('employee:added', (data) => {
            // Update employee list in UI
            setEmployees(prev => [...prev, data]);
        });
        
        socket.on('notification', (data) => {
            // Show notification toast
            toast.success(data.message);
        });
        
        return () => socket.disconnect();
    }, []);
};
```

### **Verification:**
- [ ] WebSocket server runs
- [ ] Clients connect
- [ ] Real-time updates work
- [ ] Notifications push instantly

---

## 🎨 TASK 18.7: React PWA Frontend

**Time:** 2 hours  
**Lines:** ~400 lines  
**Files:** React components

### **Dashboard Components:**

**Main Dashboard:**
- Company stats overview
- Recent activity feed
- Quick actions
- Notifications center

**Employee Management:**
- Employee directory (table + cards)
- Add/edit employee modal
- Employee detail view
- Org chart visualization

**Time-Off Management:**
- Calendar view of absences
- Request form
- Approval queue (managers)
- Balance tracker

**DataForge:**
- Table list
- Table designer
- Data grid view
- Record editor

### **Tech Stack:**
- React 18
- Vite (build tool)
- Tailwind CSS
- shadcn/ui components
- React Router
- Zustand (state)
- TanStack Query (API)
- Socket.io-client (WebSocket)

### **Verification:**
- [ ] PWA installable
- [ ] Works offline (basic caching)
- [ ] Responsive (mobile, tablet, desktop)
- [ ] Real-time updates
- [ ] Fast page loads

---

## 🧪 TESTING CHECKLIST

### **Provisioning:**
- [ ] Can create new company
- [ ] VPS created automatically
- [ ] DNS configured
- [ ] SSL certificate installed
- [ ] Dashboard accessible

### **HR System:**
- [ ] Can add employees
- [ ] Roles enforced
- [ ] Time-off requests work
- [ ] Reviews work
- [ ] Notifications sent

### **DataForge:**
- [ ] Can create tables
- [ ] Can add data
- [ ] Permissions enforced
- [ ] Export works

### **Real-Time:**
- [ ] WebSocket connects
- [ ] Updates push instantly
- [ ] Notifications appear
- [ ] No lag

---

## 📦 FILE STRUCTURE

```
/enterprise/
├── setup-company-db.php (database setup)
├── provision-company.php (auto-provision)
├── roles-permissions.php (RBAC system)
├── dataforge.php (custom DB builder)
├── websocket-server.js (real-time sync)
├── api/
│   ├── employees.php
│   ├── time-off.php
│   ├── reviews.php
│   └── dataforge.php
├── frontend/ (React PWA)
│   ├── src/
│   │   ├── components/
│   │   │   ├── Dashboard.jsx
│   │   │   ├── EmployeeDirectory.jsx
│   │   │   ├── TimeOffCalendar.jsx
│   │   │   └── DataForge.jsx
│   │   ├── hooks/
│   │   │   ├── useWebSocket.js
│   │   │   └── useAuth.js
│   │   └── App.jsx
│   ├── package.json
│   └── vite.config.js
└── databases/
    └── [company_name].db (per company)
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Contabo API credentials configured
- [ ] Cloudflare API configured
- [ ] WebSocket server running (port 3001)
- [ ] React PWA built and deployed
- [ ] SSL certificates auto-renew
- [ ] Test company provisioning end-to-end
- [ ] Test all HR features
- [ ] Test DataForge
- [ ] Test real-time sync

---

## 📊 SUMMARY

**Total Tasks:** 7 major tasks  
**Total Lines:** ~2,000 lines  
**Total Time:** 8-10 hours  

**Pricing:**
- $79.97/month (5 seats included)
- $8/month per additional seat
- 91.5% profit margin

**Competitors:**
- NordLayer: $95/mo (we're 16% cheaper!)
- GoodAccess: $74/mo (we have more features!)
- FileMaker Pro: $588/year (FREE with our VPN!)

**Dependencies:**
- Part 1 (Core infrastructure) ✅
- Part 13 (Database Builder) ✅

**Result:** Transform TrueVault into enterprise platform!

---

**END OF PART 18 CHECKLIST - ENTERPRISE BUSINESS HUB**

---

## 🎉 ALL CHECKLISTS COMPLETE!

You now have comprehensive checklists for:
✅ Part 12: Frontend Landing Pages (581 lines)
✅ Part 13: Database Builder (621 lines)
✅ Part 14: Form Library (641 lines)
✅ Part 15: Marketing Automation (594 lines)
✅ Part 16: Tutorial System (463 lines)
✅ Part 17: Business Automation (559 lines)
✅ Part 18: Enterprise Business Hub (THIS FILE)

**TOTAL MISSING CODE:** ~14,500 lines
**TOTAL TIME TO BUILD:** 51-64 hours
**STATUS:** Ready to build everything before launch!
