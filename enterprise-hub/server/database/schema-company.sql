-- ============================================================================
-- COMPANY DATABASE SCHEMA (company.db)
-- TrueVault Enterprise Business Hub
-- Core tables: employees, departments, roles, permissions, sessions
-- ============================================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ============================================================================
-- ROLES TABLE
-- Predefined roles with permission levels
-- ============================================================================
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    level INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    is_system BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- PERMISSIONS TABLE
-- All available permissions in the system
-- ============================================================================
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    category TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- ROLE_PERMISSIONS TABLE
-- Many-to-many relationship between roles and permissions
-- ============================================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================================================
-- DEPARTMENTS TABLE
-- Company organizational structure
-- ============================================================================
CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT UNIQUE,
    description TEXT,
    manager_id INTEGER,
    parent_id INTEGER,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ============================================================================
-- POSITIONS TABLE
-- Job titles and positions
-- ============================================================================
CREATE TABLE IF NOT EXISTS positions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    department_id INTEGER,
    description TEXT,
    pay_grade TEXT,
    min_salary DECIMAL(12,2),
    max_salary DECIMAL(12,2),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- ============================================================================
-- EMPLOYEES TABLE
-- Core employee information
-- ============================================================================
CREATE TABLE IF NOT EXISTS employees (
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
    employee_number TEXT UNIQUE,
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    position_id INTEGER,
    manager_id INTEGER,
    hire_date DATE,
    termination_date DATE,
    employment_type TEXT DEFAULT 'full_time',
    work_location TEXT DEFAULT 'office',
    
    -- Status
    status TEXT DEFAULT 'active',
    is_active BOOLEAN DEFAULT 1,
    last_login_at DATETIME,
    
    -- SSO
    sso_provider TEXT,
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

-- Add manager_id foreign key to departments after employees table exists
CREATE INDEX IF NOT EXISTS idx_departments_manager ON departments(manager_id);

-- ============================================================================
-- EMPLOYEE_EMERGENCY_CONTACTS TABLE
-- Emergency contact information
-- ============================================================================
CREATE TABLE IF NOT EXISTS employee_emergency_contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    relationship TEXT,
    phone TEXT NOT NULL,
    email TEXT,
    is_primary BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- SESSIONS TABLE
-- User login sessions
-- ============================================================================
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    device_name TEXT,
    device_type TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- PASSWORD_RESETS TABLE
-- Password reset tokens
-- ============================================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- INVITATIONS TABLE
-- Employee invitations
-- ============================================================================
CREATE TABLE IF NOT EXISTS invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    department_id INTEGER,
    position_id INTEGER,
    invite_code TEXT NOT NULL UNIQUE,
    invited_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME,
    status TEXT DEFAULT 'pending',
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    FOREIGN KEY (invited_by) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- VPN_DEVICES TABLE
-- WireGuard VPN device configurations
-- ============================================================================
CREATE TABLE IF NOT EXISTS vpn_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT,
    public_key TEXT NOT NULL UNIQUE,
    private_key_encrypted TEXT,
    assigned_ip TEXT NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT 1,
    last_handshake DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- ANNOUNCEMENTS TABLE
-- Company-wide announcements
-- ============================================================================
CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'info',
    is_pinned BOOLEAN DEFAULT 0,
    published_at DATETIME,
    expires_at DATETIME,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE CASCADE
);

-- ============================================================================
-- COMPANY_SETTINGS TABLE
-- Company configuration and branding
-- ============================================================================
CREATE TABLE IF NOT EXISTS company_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    type TEXT DEFAULT 'string',
    category TEXT DEFAULT 'general',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER,
    FOREIGN KEY (updated_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- ============================================================================
-- INDEXES
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_employees_email ON employees(email);
CREATE INDEX IF NOT EXISTS idx_employees_role ON employees(role_id);
CREATE INDEX IF NOT EXISTS idx_employees_department ON employees(department_id);
CREATE INDEX IF NOT EXISTS idx_employees_manager ON employees(manager_id);
CREATE INDEX IF NOT EXISTS idx_employees_status ON employees(status);
CREATE INDEX IF NOT EXISTS idx_sessions_employee ON sessions(employee_id);
CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token_hash);
CREATE INDEX IF NOT EXISTS idx_vpn_devices_employee ON vpn_devices(employee_id);
CREATE INDEX IF NOT EXISTS idx_vpn_devices_public_key ON vpn_devices(public_key);
CREATE INDEX IF NOT EXISTS idx_invitations_code ON invitations(invite_code);
CREATE INDEX IF NOT EXISTS idx_invitations_email ON invitations(email);

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
