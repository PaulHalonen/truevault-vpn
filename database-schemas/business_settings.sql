-- BUSINESS SETTINGS TABLE FOR TRANSFERABILITY
-- Database: vpn.db
-- Purpose: Store ALL business configuration in database (nothing hardcoded)
-- This enables complete business transfer in 30 minutes via GUI

CREATE TABLE IF NOT EXISTS business_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type TEXT CHECK(setting_type IN ('text', 'email', 'password', 'url', 'boolean', 'number')),
    is_encrypted BOOLEAN DEFAULT 0,
    category TEXT CHECK(category IN ('general', 'payment', 'email', 'server', 'transfer')),
    display_name TEXT NOT NULL,
    description TEXT,
    requires_verification BOOLEAN DEFAULT 0,
    verification_status TEXT CHECK(verification_status IN ('pending', 'verified', 'failed', NULL)),
    last_verified DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by TEXT
);

-- Default business settings (Current Owner: Kah-Len)

-- GENERAL
INSERT INTO business_settings VALUES
(1, 'business_name', 'TrueVault VPN', 'text', 0, 'general', 'Business Name', 'Name shown to customers', 0, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(2, 'owner_name', 'Kah-Len Halonen', 'text', 0, 'general', 'Owner Name', 'Current business owner', 0, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(3, 'business_domain', 'vpn.the-truth-publishing.com', 'url', 0, 'general', 'Business Domain', 'Website domain', 1, NULL, NULL, datetime('now'), datetime('now'), 'system');

-- PAYMENT
INSERT INTO business_settings VALUES
(4, 'paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'text', 0, 'payment', 'PayPal Client ID', 'PayPal API Client ID', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(5, 'paypal_secret', 'ENCRYPTED', 'password', 1, 'payment', 'PayPal Secret Key', 'PayPal API Secret', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(6, 'paypal_webhook_id', '46924926WL757580D', 'text', 0, 'payment', 'PayPal Webhook ID', 'Webhook identifier', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(7, 'paypal_account_email', 'paulhalonen@gmail.com', 'email', 0, 'payment', 'PayPal Account Email', 'Business account email', 0, NULL, NULL, datetime('now'), datetime('now'), 'system');

-- EMAIL
INSERT INTO business_settings VALUES
(8, 'customer_email', 'admin@the-truth-publishing.com', 'email', 0, 'email', 'Customer Email', 'Email for customer communications', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(9, 'customer_email_password', 'ENCRYPTED', 'password', 1, 'email', 'Email Password', 'SMTP/IMAP password', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(10, 'smtp_server', 'the-truth-publishing.com', 'text', 0, 'email', 'SMTP Server', 'Outgoing mail server', 1, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(11, 'smtp_port', '465', 'number', 0, 'email', 'SMTP Port', 'SMTP port', 0, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(12, 'email_from_name', 'TrueVault VPN Team', 'text', 0, 'email', 'From Name', 'Sender name', 0, NULL, NULL, datetime('now'), datetime('now'), 'system');

-- SERVER
INSERT INTO business_settings VALUES
(13, 'server_provider_email', 'paulhalonen@gmail.com', 'email', 0, 'server', 'Server Provider Email', 'Contabo/Fly.io notifications', 0, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(14, 'server_root_password', 'ENCRYPTED', 'password', 1, 'server', 'Server Root Password', 'Standard root password', 0, NULL, NULL, datetime('now'), datetime('now'), 'system');

-- TRANSFER
INSERT INTO business_settings VALUES
(15, 'transfer_mode_active', '0', 'boolean', 0, 'transfer', 'Transfer Mode', 'Is transfer in progress', 0, NULL, NULL, datetime('now'), datetime('now'), 'system'),
(16, 'setup_complete', '1', 'boolean', 0, 'transfer', 'Setup Complete', 'Initial setup done', 0, NULL, NULL, datetime('now'), datetime('now'), 'system');

-- Audit log
CREATE TABLE IF NOT EXISTS business_settings_audit (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    change_reason TEXT
);

-- Trigger for change logging
CREATE TRIGGER IF NOT EXISTS log_settings_changes
AFTER UPDATE ON business_settings
FOR EACH ROW
BEGIN
    INSERT INTO business_settings_audit (setting_key, old_value, new_value, changed_by)
    VALUES (NEW.setting_key, OLD.setting_value, NEW.setting_value, NEW.updated_by);
END;

-- Indexes
CREATE INDEX IF NOT EXISTS idx_settings_key ON business_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_settings_category ON business_settings(category);
