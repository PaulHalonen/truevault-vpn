-- Admin Panel Database Schema
-- Created: January 19, 2026

-- Admin users table (already exists in main.db, but adding here for reference)
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT DEFAULT 'admin',              -- admin, superadmin
    is_active INTEGER DEFAULT 1,
    last_login TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Admin sessions
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    session_token TEXT NOT NULL UNIQUE,
    ip_address TEXT,
    user_agent TEXT,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- Activity log for audit trail
CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER,
    action TEXT NOT NULL,                   -- login, logout, user_created, payment_processed, etc.
    entity_type TEXT,                       -- user, device, payment, ticket
    entity_id INTEGER,
    details TEXT,                           -- JSON with additional info
    ip_address TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'text',      -- text, number, boolean, json
    category TEXT DEFAULT 'general',        -- general, payment, email, vpn
    description TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_admin_sessions_token ON admin_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_admin_sessions_admin ON admin_sessions(admin_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_admin ON activity_log(admin_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_system_settings_category ON system_settings(category);

-- Default admin user (password: admin123 - CHANGE THIS!)
INSERT OR IGNORE INTO admin_users (id, email, password, name, role) VALUES
(1, 'admin@vpn.the-truth-publishing.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'superadmin');

-- Default system settings
INSERT OR IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES
('site_name', 'TrueVault VPN', 'text', 'general', 'Website name'),
('site_url', 'https://vpn.the-truth-publishing.com', 'text', 'general', 'Base URL'),
('admin_email', 'admin@vpn.the-truth-publishing.com', 'text', 'general', 'Admin email address'),
('maintenance_mode', '0', 'boolean', 'general', 'Maintenance mode enabled'),
('paypal_client_id', 'ActD2XQKe8EkUNI8eZakmhR8964d2kAdh7rcpbkm2rbr8rrtEOoOdmoj50FtXmy1XLYzALL5ogvxcagk', 'text', 'payment', 'PayPal Client ID'),
('paypal_secret', 'EIc2idTcm_YjKf4pNxXpRr_vBt0Ebb3FCp71H2fTI3T9NAi_iAvlrwYbEaidmP23IynWSqfP6nkAXwGN', 'text', 'payment', 'PayPal Secret Key'),
('paypal_mode', 'live', 'text', 'payment', 'PayPal mode (sandbox/live)'),
('trial_days', '7', 'number', 'payment', 'Free trial period in days'),
('personal_price_usd', '9.97', 'number', 'payment', 'Personal plan monthly price USD'),
('family_price_usd', '14.97', 'number', 'payment', 'Family plan monthly price USD'),
('dedicated_price_usd', '39.97', 'number', 'payment', 'Dedicated plan monthly price USD'),
('personal_price_cad', '13.47', 'number', 'payment', 'Personal plan monthly price CAD'),
('family_price_cad', '20.21', 'number', 'payment', 'Family plan monthly price CAD'),
('dedicated_price_cad', '53.96', 'number', 'payment', 'Dedicated plan monthly price CAD'),
('smtp_host', '', 'text', 'email', 'SMTP server host'),
('smtp_port', '587', 'number', 'email', 'SMTP server port'),
('smtp_username', '', 'text', 'email', 'SMTP username'),
('smtp_password', '', 'text', 'email', 'SMTP password'),
('smtp_from_email', 'noreply@vpn.the-truth-publishing.com', 'text', 'email', 'From email address'),
('smtp_from_name', 'TrueVault VPN', 'text', 'email', 'From name'),
('max_devices_personal', '3', 'number', 'vpn', 'Max devices for Personal plan'),
('max_devices_family', '5', 'number', 'vpn', 'Max devices for Family plan'),
('max_devices_dedicated', '999', 'number', 'vpn', 'Max devices for Dedicated plan'),
('vip_user_email', 'seige235@yahoo.com', 'text', 'vpn', 'VIP user email (free dedicated)'),
('contabo_server1_ip', '66.94.103.91', 'text', 'vpn', 'Contabo Server 1 IP (Shared - Bandwidth Limited)'),
('contabo_server2_ip', '144.126.133.253', 'text', 'vpn', 'Contabo Server 2 IP (VIP Dedicated)'),
('flyio_server3_ip', '66.241.124.4', 'text', 'vpn', 'Fly.io Server 3 IP (Dallas - Shared)'),
('flyio_server4_ip', '66.241.125.247', 'text', 'vpn', 'Fly.io Server 4 IP (Toronto - Shared)');
