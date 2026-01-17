-- ============================================================================
-- AUDIT DATABASE SCHEMA (audit.db)
-- TrueVault Enterprise Business Hub
-- Security and compliance: audit logs, notifications, tasks
-- ============================================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ============================================================================
-- AUDIT_LOGS TABLE
-- All user actions for compliance
-- ============================================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Who
    employee_id INTEGER,
    employee_email TEXT,
    employee_name TEXT,
    
    -- What
    action TEXT NOT NULL,
    resource_type TEXT NOT NULL,
    resource_id TEXT,
    resource_name TEXT,
    
    -- Details
    old_values TEXT,
    new_values TEXT,
    description TEXT,
    
    -- Where
    ip_address TEXT,
    user_agent TEXT,
    session_id INTEGER,
    
    -- When
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Severity
    severity TEXT DEFAULT 'info'
);

-- ============================================================================
-- NOTIFICATIONS TABLE
-- User notifications
-- ============================================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    
    -- Content
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    type TEXT DEFAULT 'info',
    priority TEXT DEFAULT 'normal',
    
    -- Link
    action_url TEXT,
    action_label TEXT,
    
    -- Related entity
    resource_type TEXT,
    resource_id INTEGER,
    
    -- Status
    is_read BOOLEAN DEFAULT 0,
    read_at DATETIME,
    is_dismissed BOOLEAN DEFAULT 0,
    dismissed_at DATETIME,
    
    -- Push
    push_sent BOOLEAN DEFAULT 0,
    push_sent_at DATETIME,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME
);

-- ============================================================================
-- TASKS TABLE
-- Employee tasks and to-dos
-- ============================================================================
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Content
    title TEXT NOT NULL,
    description TEXT,
    
    -- Assignment
    assigned_to INTEGER NOT NULL,
    assigned_by INTEGER,
    
    -- Dates
    due_date DATE,
    due_time TIME,
    reminder_date DATETIME,
    
    -- Status
    priority TEXT DEFAULT 'medium',
    status TEXT DEFAULT 'pending',
    completed_at DATETIME,
    
    -- Related entity
    resource_type TEXT,
    resource_id INTEGER,
    
    -- Recurring
    is_recurring BOOLEAN DEFAULT 0,
    recurrence_pattern TEXT,
    parent_task_id INTEGER,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- ============================================================================
-- EMAIL_LOGS TABLE
-- Track sent emails
-- ============================================================================
CREATE TABLE IF NOT EXISTS email_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Recipients
    to_email TEXT NOT NULL,
    to_name TEXT,
    cc_emails TEXT,
    bcc_emails TEXT,
    
    -- Content
    subject TEXT NOT NULL,
    template TEXT,
    body_preview TEXT,
    
    -- Status
    status TEXT DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    
    -- Related
    employee_id INTEGER,
    resource_type TEXT,
    resource_id INTEGER,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- SCHEDULED_JOBS TABLE
-- Background job scheduling
-- ============================================================================
CREATE TABLE IF NOT EXISTS scheduled_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Job info
    name TEXT NOT NULL,
    job_type TEXT NOT NULL,
    
    -- Schedule
    cron_expression TEXT,
    next_run_at DATETIME,
    last_run_at DATETIME,
    
    -- Payload
    payload TEXT,
    
    -- Status
    is_active BOOLEAN DEFAULT 1,
    is_running BOOLEAN DEFAULT 0,
    last_status TEXT,
    last_error TEXT,
    run_count INTEGER DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- ACTIVITY_FEED TABLE
-- Recent activity for dashboards
-- ============================================================================
CREATE TABLE IF NOT EXISTS activity_feed (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    -- Who
    employee_id INTEGER,
    employee_name TEXT,
    
    -- What
    activity_type TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    
    -- Related
    resource_type TEXT,
    resource_id INTEGER,
    resource_url TEXT,
    
    -- Visibility
    is_public BOOLEAN DEFAULT 1,
    visible_to TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- LOGIN_ATTEMPTS TABLE
-- Track failed login attempts for security
-- ============================================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    email TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    
    success BOOLEAN DEFAULT 0,
    failure_reason TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- SECURITY_EVENTS TABLE
-- Security-related events
-- ============================================================================
CREATE TABLE IF NOT EXISTS security_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    event_type TEXT NOT NULL,
    severity TEXT NOT NULL,
    
    employee_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    
    description TEXT NOT NULL,
    details TEXT,
    
    is_resolved BOOLEAN DEFAULT 0,
    resolved_at DATETIME,
    resolved_by INTEGER,
    resolution_notes TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- INDEXES
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_audit_employee ON audit_logs(employee_id);
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_resource ON audit_logs(resource_type, resource_id);
CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_employee ON notifications(employee_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read ON notifications(is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications(created_at);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_due ON tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_email_logs_status ON email_logs(status);
CREATE INDEX IF NOT EXISTS idx_email_logs_employee ON email_logs(employee_id);
CREATE INDEX IF NOT EXISTS idx_activity_employee ON activity_feed(employee_id);
CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_feed(created_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip_address);
CREATE INDEX IF NOT EXISTS idx_security_events_type ON security_events(event_type);
CREATE INDEX IF NOT EXISTS idx_security_events_created ON security_events(created_at);

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
