-- Business Automation Workflow System Database Schema
-- Created: January 19, 2026

-- Workflow definitions
CREATE TABLE IF NOT EXISTS workflows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_name TEXT NOT NULL,
    description TEXT,
    trigger_type TEXT NOT NULL,          -- manual, event, scheduled, webhook
    trigger_config TEXT,                  -- JSON configuration for trigger
    is_active INTEGER DEFAULT 1,
    is_template INTEGER DEFAULT 0,        -- Template workflows
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Workflow steps/actions
CREATE TABLE IF NOT EXISTS workflow_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER NOT NULL,
    step_number INTEGER NOT NULL,
    step_type TEXT NOT NULL,              -- email, delay, condition, action, api_call
    step_config TEXT NOT NULL,            -- JSON configuration
    delay_minutes INTEGER DEFAULT 0,      -- Delay before executing this step
    condition_rules TEXT,                 -- JSON conditions to evaluate
    on_success_step INTEGER,              -- Next step on success
    on_failure_step INTEGER,              -- Next step on failure
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

-- Workflow executions
CREATE TABLE IF NOT EXISTS workflow_executions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workflow_id INTEGER NOT NULL,
    trigger_data TEXT,                    -- JSON data that triggered workflow
    status TEXT DEFAULT 'running',        -- running, completed, failed, paused
    current_step INTEGER,
    started_at TEXT DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT,
    error_message TEXT,
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE
);

-- Workflow execution logs
CREATE TABLE IF NOT EXISTS workflow_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    execution_id INTEGER NOT NULL,
    step_id INTEGER,
    log_level TEXT DEFAULT 'info',        -- info, warning, error, success
    message TEXT NOT NULL,
    data TEXT,                            -- JSON additional data
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE
);

-- Scheduled tasks (for delayed workflow steps)
CREATE TABLE IF NOT EXISTS scheduled_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    execution_id INTEGER NOT NULL,
    step_id INTEGER NOT NULL,
    scheduled_time TEXT NOT NULL,
    status TEXT DEFAULT 'pending',        -- pending, processing, completed, failed
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_workflows_active ON workflows(is_active);
CREATE INDEX IF NOT EXISTS idx_workflows_trigger ON workflows(trigger_type);
CREATE INDEX IF NOT EXISTS idx_steps_workflow ON workflow_steps(workflow_id);
CREATE INDEX IF NOT EXISTS idx_executions_workflow ON workflow_executions(workflow_id);
CREATE INDEX IF NOT EXISTS idx_executions_status ON workflow_executions(status);
CREATE INDEX IF NOT EXISTS idx_logs_execution ON workflow_logs(execution_id);
CREATE INDEX IF NOT EXISTS idx_tasks_scheduled ON scheduled_tasks(scheduled_time);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON scheduled_tasks(status);

-- Insert workflow templates
INSERT OR IGNORE INTO workflows (workflow_name, description, trigger_type, is_template, is_active) VALUES

('Customer Onboarding', 'Automated welcome sequence for new customers', 'event', 1, 1),
('Payment Success', 'Actions to take when payment is successful', 'event', 1, 1),
('Payment Failed', 'Retry and notification sequence for failed payments', 'event', 1, 1),
('Trial Expiration Reminder', 'Remind users before trial expires', 'scheduled', 1, 1),
('Subscription Renewal', 'Monthly subscription processing', 'scheduled', 1, 1),
('Support Ticket Auto-Response', 'Automated ticket acknowledgment', 'event', 1, 1),
('Customer Churn Prevention', 'Engage inactive customers', 'scheduled', 1, 1),
('Upsell Campaign', 'Promote upgrades to existing customers', 'scheduled', 1, 1);

-- Customer Onboarding workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
-- Workflow 1: Customer Onboarding
(1, 1, 'email', '{"template":"welcome_email","subject":"Welcome to TrueVault VPN!","to":"{{customer.email}}"}', 0),
(1, 2, 'action', '{"action":"create_customer_folder","path":"/customers/{{customer.id}}"}', 0),
(1, 3, 'delay', '{"duration_minutes":60}', 60),
(1, 4, 'email', '{"template":"setup_guide","subject":"Get Started with TrueVault VPN","to":"{{customer.email}}"}', 60),
(1, 5, 'delay', '{"duration_minutes":1440}', 1440),
(1, 6, 'email', '{"template":"tips_tricks","subject":"Pro Tips for TrueVault VPN","to":"{{customer.email}}"}', 1440);

-- Payment Success workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(2, 1, 'action', '{"action":"update_customer_status","status":"active"}', 0),
(2, 2, 'action', '{"action":"generate_invoice","customer_id":"{{customer.id}}"}', 0),
(2, 3, 'email', '{"template":"payment_received","subject":"Payment Confirmed","to":"{{customer.email}}"}', 0),
(2, 4, 'action', '{"action":"log_transaction","type":"payment_success"}', 0);

-- Payment Failed workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(3, 1, 'action', '{"action":"update_customer_status","status":"payment_failed"}', 0),
(3, 2, 'email', '{"template":"payment_failed_notice","subject":"Payment Issue - Action Required","to":"{{customer.email}}"}', 0),
(3, 3, 'delay', '{"duration_minutes":4320}', 4320),
(3, 4, 'condition', '{"check":"payment_status","equals":"failed"}', 4320),
(3, 5, 'email', '{"template":"payment_retry_reminder","subject":"Reminder: Update Payment Method","to":"{{customer.email}}"}', 4320),
(3, 6, 'delay', '{"duration_minutes":10080}', 10080),
(3, 7, 'action', '{"action":"suspend_service","customer_id":"{{customer.id}}"}', 10080),
(3, 8, 'email', '{"template":"service_suspended","subject":"Service Suspended","to":"{{customer.email}}"}', 10080);

-- Trial Expiration Reminder workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(4, 1, 'condition', '{"check":"days_until_expiry","operator":"<=","value":3}', 0),
(4, 2, 'email', '{"template":"trial_ending_soon","subject":"Your Trial Ends Soon","to":"{{customer.email}}"}', 0),
(4, 3, 'delay', '{"duration_minutes":1440}', 1440),
(4, 4, 'condition', '{"check":"subscription_status","equals":"trial"}', 1440),
(4, 5, 'email', '{"template":"last_chance_trial","subject":"Last Day of Your Free Trial","to":"{{customer.email}}"}', 1440);

-- Support Ticket Auto-Response workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(6, 1, 'email', '{"template":"ticket_received","subject":"Support Ticket #{{ticket.number}} Received","to":"{{ticket.email}}"}', 0),
(6, 2, 'action', '{"action":"check_knowledge_base","ticket_id":"{{ticket.id}}"}', 0),
(6, 3, 'condition', '{"check":"kb_solution_found","equals":true}', 0),
(6, 4, 'email', '{"template":"kb_solution","subject":"Possible Solution for Ticket #{{ticket.number}}","to":"{{ticket.email}}"}', 0),
(6, 5, 'delay', '{"duration_minutes":1440}', 1440),
(6, 6, 'condition', '{"check":"ticket_status","equals":"open"}', 1440),
(6, 7, 'action', '{"action":"escalate_ticket","priority":"high"}', 1440);

-- Customer Churn Prevention workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(7, 1, 'condition', '{"check":"last_connection_days","operator":">","value":30}', 0),
(7, 2, 'email', '{"template":"we_miss_you","subject":"We Miss You!","to":"{{customer.email}}"}', 0),
(7, 3, 'delay', '{"duration_minutes":10080}', 10080),
(7, 4, 'condition', '{"check":"last_connection_days","operator":">","value":45}', 10080),
(7, 5, 'email', '{"template":"special_offer","subject":"Exclusive Offer Inside","to":"{{customer.email}}"}', 10080);

-- Upsell Campaign workflow steps
INSERT OR IGNORE INTO workflow_steps (workflow_id, step_number, step_type, step_config, delay_minutes) VALUES
(8, 1, 'condition', '{"check":"current_plan","equals":"personal"}', 0),
(8, 2, 'condition', '{"check":"customer_lifetime_days","operator":">=","value":90}', 0),
(8, 3, 'email', '{"template":"upgrade_offer","subject":"Upgrade & Save 20%","to":"{{customer.email}}"}', 0),
(8, 4, 'delay', '{"duration_minutes":10080}', 10080),
(8, 5, 'condition', '{"check":"current_plan","equals":"personal"}', 10080),
(8, 6, 'email', '{"template":"upgrade_reminder","subject":"Limited Time: Family Plan Discount","to":"{{customer.email}}"}', 10080);
