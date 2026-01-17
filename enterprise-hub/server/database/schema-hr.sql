-- ============================================================================
-- HR DATABASE SCHEMA (hr.db)
-- TrueVault Enterprise Business Hub
-- HR tables: compensation, time-off, documents, reviews
-- ============================================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ============================================================================
-- COMPENSATION TABLE
-- Salary and compensation data (HR_ADMIN access only)
-- ============================================================================
CREATE TABLE IF NOT EXISTS compensation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    salary_amount DECIMAL(12,2) NOT NULL,
    salary_currency TEXT DEFAULT 'USD',
    salary_frequency TEXT DEFAULT 'annual',
    effective_date DATE NOT NULL,
    end_date DATE,
    change_reason TEXT,
    approved_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER
);

-- ============================================================================
-- TIME_OFF_TYPES TABLE
-- Types of time off (vacation, sick, personal, etc.)
-- ============================================================================
CREATE TABLE IF NOT EXISTS time_off_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    code TEXT NOT NULL UNIQUE,
    description TEXT,
    color TEXT DEFAULT '#3B82F6',
    default_days_per_year DECIMAL(5,2) DEFAULT 0,
    accrual_type TEXT DEFAULT 'annual',
    requires_approval BOOLEAN DEFAULT 1,
    can_carry_over BOOLEAN DEFAULT 0,
    max_carry_over_days DECIMAL(5,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- TIME_OFF_BALANCES TABLE
-- Employee time-off balances by type
-- ============================================================================
CREATE TABLE IF NOT EXISTS time_off_balances (
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
    UNIQUE(employee_id, time_off_type_id, year)
);

-- ============================================================================
-- TIME_OFF_REQUESTS TABLE
-- Employee time-off requests
-- ============================================================================
CREATE TABLE IF NOT EXISTS time_off_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    time_off_type_id INTEGER NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5,2) NOT NULL,
    half_day_start BOOLEAN DEFAULT 0,
    half_day_end BOOLEAN DEFAULT 0,
    reason TEXT,
    status TEXT DEFAULT 'pending',
    reviewed_by INTEGER,
    reviewed_at DATETIME,
    review_notes TEXT,
    cancelled_at DATETIME,
    cancellation_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DOCUMENTS TABLE
-- Employee document storage
-- ============================================================================
CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type TEXT,
    category TEXT DEFAULT 'general',
    description TEXT,
    is_confidential BOOLEAN DEFAULT 0,
    expiration_date DATE,
    reminder_sent BOOLEAN DEFAULT 0,
    uploaded_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DOCUMENT_CATEGORIES TABLE
-- Document category definitions
-- ============================================================================
CREATE TABLE IF NOT EXISTS document_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    requires_expiration BOOLEAN DEFAULT 0,
    is_confidential_by_default BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- REVIEW_CYCLES TABLE
-- Performance review cycles
-- ============================================================================
CREATE TABLE IF NOT EXISTS review_cycles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT DEFAULT 'annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    self_assessment_due DATE,
    manager_assessment_due DATE,
    status TEXT DEFAULT 'draft',
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- REVIEWS TABLE
-- Individual performance reviews
-- ============================================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    reviewer_id INTEGER NOT NULL,
    review_type TEXT DEFAULT 'manager',
    
    -- Self Assessment
    self_assessment TEXT,
    self_rating INTEGER,
    self_submitted_at DATETIME,
    
    -- Manager Assessment
    manager_assessment TEXT,
    manager_rating INTEGER,
    manager_submitted_at DATETIME,
    
    -- Final
    overall_rating INTEGER,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals_for_next_period TEXT,
    comments TEXT,
    
    -- Status
    status TEXT DEFAULT 'draft',
    completed_at DATETIME,
    acknowledged_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(cycle_id, employee_id, review_type)
);

-- ============================================================================
-- ONBOARDING_CHECKLISTS TABLE
-- Onboarding checklist templates
-- ============================================================================
CREATE TABLE IF NOT EXISTS onboarding_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- ONBOARDING_TEMPLATE_ITEMS TABLE
-- Items in onboarding template
-- ============================================================================
CREATE TABLE IF NOT EXISTS onboarding_template_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT DEFAULT 'general',
    due_days INTEGER DEFAULT 0,
    assigned_to_role TEXT,
    sort_order INTEGER DEFAULT 0,
    is_required BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES onboarding_templates(id) ON DELETE CASCADE
);

-- ============================================================================
-- EMPLOYEE_ONBOARDING TABLE
-- Employee onboarding progress
-- ============================================================================
CREATE TABLE IF NOT EXISTS employee_onboarding (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    template_id INTEGER NOT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    status TEXT DEFAULT 'in_progress',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- EMPLOYEE_ONBOARDING_ITEMS TABLE
-- Individual onboarding item completion
-- ============================================================================
CREATE TABLE IF NOT EXISTS employee_onboarding_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    onboarding_id INTEGER NOT NULL,
    template_item_id INTEGER NOT NULL,
    is_completed BOOLEAN DEFAULT 0,
    completed_at DATETIME,
    completed_by INTEGER,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (onboarding_id) REFERENCES employee_onboarding(id) ON DELETE CASCADE,
    FOREIGN KEY (template_item_id) REFERENCES onboarding_template_items(id) ON DELETE CASCADE
);

-- ============================================================================
-- NOTES TABLE
-- Private notes about employees (HR only)
-- ============================================================================
CREATE TABLE IF NOT EXISTS hr_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    note TEXT NOT NULL,
    category TEXT DEFAULT 'general',
    is_confidential BOOLEAN DEFAULT 1,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- HOLIDAYS TABLE
-- Company holidays
-- ============================================================================
CREATE TABLE IF NOT EXISTS holidays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    date DATE NOT NULL,
    is_paid BOOLEAN DEFAULT 1,
    is_recurring BOOLEAN DEFAULT 0,
    year INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(date, name)
);

-- ============================================================================
-- INDEXES
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_compensation_employee ON compensation(employee_id);
CREATE INDEX IF NOT EXISTS idx_compensation_effective ON compensation(effective_date);
CREATE INDEX IF NOT EXISTS idx_time_off_balances_employee ON time_off_balances(employee_id);
CREATE INDEX IF NOT EXISTS idx_time_off_balances_year ON time_off_balances(year);
CREATE INDEX IF NOT EXISTS idx_time_off_requests_employee ON time_off_requests(employee_id);
CREATE INDEX IF NOT EXISTS idx_time_off_requests_status ON time_off_requests(status);
CREATE INDEX IF NOT EXISTS idx_time_off_requests_dates ON time_off_requests(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_documents_employee ON documents(employee_id);
CREATE INDEX IF NOT EXISTS idx_documents_category ON documents(category);
CREATE INDEX IF NOT EXISTS idx_documents_expiration ON documents(expiration_date);
CREATE INDEX IF NOT EXISTS idx_reviews_cycle ON reviews(cycle_id);
CREATE INDEX IF NOT EXISTS idx_reviews_employee ON reviews(employee_id);
CREATE INDEX IF NOT EXISTS idx_hr_notes_employee ON hr_notes(employee_id);

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
