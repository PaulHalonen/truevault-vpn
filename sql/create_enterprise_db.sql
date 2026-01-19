-- Enterprise Business Hub Database Schema
-- Created: January 19, 2026

-- Clients/Companies
CREATE TABLE IF NOT EXISTS enterprise_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name TEXT NOT NULL,
    contact_name TEXT,
    contact_email TEXT,
    contact_phone TEXT,
    industry TEXT,
    company_size TEXT,                    -- small, medium, large, enterprise
    billing_address TEXT,
    tax_id TEXT,
    payment_terms INTEGER DEFAULT 30,     -- Net days
    hourly_rate REAL DEFAULT 150.00,
    status TEXT DEFAULT 'active',         -- active, inactive, suspended
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Projects
CREATE TABLE IF NOT EXISTS enterprise_projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    project_name TEXT NOT NULL,
    description TEXT,
    project_type TEXT,                    -- fixed_price, hourly, retainer
    budget REAL,
    hourly_rate REAL,
    start_date TEXT,
    end_date TEXT,
    status TEXT DEFAULT 'active',         -- active, on_hold, completed, cancelled
    priority TEXT DEFAULT 'medium',       -- low, medium, high, urgent
    completion_percent INTEGER DEFAULT 0,
    assigned_team TEXT,                   -- JSON array of team member IDs
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES enterprise_clients(id) ON DELETE CASCADE
);

-- Tasks
CREATE TABLE IF NOT EXISTS enterprise_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    task_name TEXT NOT NULL,
    description TEXT,
    assigned_to INTEGER,
    status TEXT DEFAULT 'todo',           -- todo, in_progress, review, completed
    priority TEXT DEFAULT 'medium',
    estimated_hours REAL,
    actual_hours REAL DEFAULT 0,
    due_date TEXT,
    completed_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES enterprise_projects(id) ON DELETE CASCADE
);

-- Time Tracking
CREATE TABLE IF NOT EXISTS enterprise_time_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    task_id INTEGER,
    team_member_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    hours REAL NOT NULL,
    billable INTEGER DEFAULT 1,
    hourly_rate REAL,
    entry_date TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES enterprise_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES enterprise_tasks(id) ON DELETE SET NULL
);

-- Invoices
CREATE TABLE IF NOT EXISTS enterprise_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT NOT NULL UNIQUE,
    client_id INTEGER NOT NULL,
    project_id INTEGER,
    invoice_date TEXT NOT NULL,
    due_date TEXT NOT NULL,
    subtotal REAL NOT NULL DEFAULT 0,
    tax_rate REAL DEFAULT 0,
    tax_amount REAL DEFAULT 0,
    total_amount REAL NOT NULL DEFAULT 0,
    status TEXT DEFAULT 'draft',          -- draft, sent, paid, overdue, cancelled
    payment_date TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES enterprise_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES enterprise_projects(id) ON DELETE SET NULL
);

-- Invoice Line Items
CREATE TABLE IF NOT EXISTS enterprise_invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    quantity REAL DEFAULT 1,
    unit_price REAL NOT NULL,
    amount REAL NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES enterprise_invoices(id) ON DELETE CASCADE
);

-- Documents
CREATE TABLE IF NOT EXISTS enterprise_documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    project_id INTEGER,
    document_name TEXT NOT NULL,
    document_type TEXT,                   -- contract, proposal, report, invoice, other
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type TEXT,
    version INTEGER DEFAULT 1,
    uploaded_by INTEGER,
    is_confidential INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES enterprise_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES enterprise_projects(id) ON DELETE CASCADE
);

-- Contracts
CREATE TABLE IF NOT EXISTS enterprise_contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    contract_number TEXT NOT NULL UNIQUE,
    contract_type TEXT,                   -- service_agreement, nda, msa, sow
    start_date TEXT NOT NULL,
    end_date TEXT,
    value REAL,
    status TEXT DEFAULT 'draft',          -- draft, pending, active, expired, terminated
    signed_date TEXT,
    document_id INTEGER,
    auto_renew INTEGER DEFAULT 0,
    renewal_notice_days INTEGER DEFAULT 30,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES enterprise_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES enterprise_documents(id) ON DELETE SET NULL
);

-- Team Members
CREATE TABLE IF NOT EXISTS enterprise_team (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    role TEXT,                            -- developer, designer, manager, admin
    hourly_rate REAL DEFAULT 100.00,
    employment_type TEXT DEFAULT 'full_time', -- full_time, part_time, contractor
    start_date TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Client Communications
CREATE TABLE IF NOT EXISTS enterprise_communications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    project_id INTEGER,
    comm_type TEXT DEFAULT 'email',       -- email, call, meeting, chat
    subject TEXT,
    message TEXT,
    sent_by INTEGER,
    sent_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES enterprise_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES enterprise_projects(id) ON DELETE SET NULL
);

-- Resources
CREATE TABLE IF NOT EXISTS enterprise_resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resource_name TEXT NOT NULL,
    resource_type TEXT,                   -- software, hardware, subscription, license
    cost_per_month REAL,
    renewal_date TEXT,
    assigned_to_project INTEGER,
    status TEXT DEFAULT 'active',
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to_project) REFERENCES enterprise_projects(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_clients_status ON enterprise_clients(status);
CREATE INDEX IF NOT EXISTS idx_projects_client ON enterprise_projects(client_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON enterprise_projects(status);
CREATE INDEX IF NOT EXISTS idx_tasks_project ON enterprise_tasks(project_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON enterprise_tasks(status);
CREATE INDEX IF NOT EXISTS idx_time_project ON enterprise_time_entries(project_id);
CREATE INDEX IF NOT EXISTS idx_time_member ON enterprise_time_entries(team_member_id);
CREATE INDEX IF NOT EXISTS idx_invoices_client ON enterprise_invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON enterprise_invoices(status);
CREATE INDEX IF NOT EXISTS idx_documents_client ON enterprise_documents(client_id);
CREATE INDEX IF NOT EXISTS idx_contracts_client ON enterprise_contracts(client_id);

-- Insert sample data
INSERT OR IGNORE INTO enterprise_clients (company_name, contact_name, contact_email, industry, company_size, hourly_rate) VALUES
('Acme Corporation', 'John Smith', 'john@acme.com', 'Technology', 'large', 175.00),
('TechStart Inc', 'Sarah Johnson', 'sarah@techstart.io', 'Software', 'medium', 150.00),
('Global Finance Ltd', 'Michael Chen', 'michael@globalfinance.com', 'Finance', 'enterprise', 200.00);

INSERT OR IGNORE INTO enterprise_team (full_name, email, role, hourly_rate, employment_type) VALUES
('Alice Developer', 'alice@example.com', 'developer', 125.00, 'full_time'),
('Bob Designer', 'bob@example.com', 'designer', 100.00, 'full_time'),
('Carol Manager', 'carol@example.com', 'manager', 150.00, 'full_time');

INSERT OR IGNORE INTO enterprise_projects (client_id, project_name, description, project_type, budget, start_date, status) VALUES
(1, 'VPN Infrastructure Upgrade', 'Modernize VPN infrastructure for enterprise deployment', 'fixed_price', 50000.00, '2026-01-01', 'active'),
(2, 'Mobile App Development', 'Build iOS and Android VPN apps', 'hourly', NULL, '2026-01-15', 'active'),
(3, 'Security Audit', 'Comprehensive security assessment', 'fixed_price', 25000.00, '2025-12-01', 'completed');
