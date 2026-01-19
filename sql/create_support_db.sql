-- Support Ticket System Database Schema
-- Created: January 19, 2026

-- Support tickets table (already exists in main.db, enhancing here)
CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    ticket_number TEXT NOT NULL UNIQUE,
    subject TEXT NOT NULL,
    category TEXT DEFAULT 'general',         -- billing, technical, account, complaint, general
    priority TEXT DEFAULT 'normal',          -- low, normal, high, urgent
    status TEXT DEFAULT 'open',              -- open, in_progress, waiting, resolved, closed
    assigned_to INTEGER,                     -- Admin user ID
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    resolved_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Ticket messages/replies
CREATE TABLE IF NOT EXISTS ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,                         -- NULL if from admin
    admin_id INTEGER,                        -- NULL if from user
    message TEXT NOT NULL,
    is_internal INTEGER DEFAULT 0,           -- Internal notes not visible to users
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Ticket attachments
CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    message_id INTEGER,
    filename TEXT NOT NULL,
    filepath TEXT NOT NULL,
    filesize INTEGER,
    mime_type TEXT,
    uploaded_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES ticket_messages(id) ON DELETE CASCADE
);

-- Knowledge base articles
CREATE TABLE IF NOT EXISTS knowledge_base (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category TEXT NOT NULL,
    content TEXT NOT NULL,
    tags TEXT,                               -- JSON array
    views INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Canned responses (quick replies)
CREATE TABLE IF NOT EXISTS canned_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category TEXT,
    content TEXT NOT NULL,
    shortcut TEXT,                           -- Quick access code
    usage_count INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Ticket satisfaction ratings
CREATE TABLE IF NOT EXISTS ticket_ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,                 -- 1-5 stars
    feedback TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_tickets_user ON support_tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_tickets_priority ON support_tickets(priority);
CREATE INDEX IF NOT EXISTS idx_tickets_assigned ON support_tickets(assigned_to);
CREATE INDEX IF NOT EXISTS idx_tickets_created ON support_tickets(created_at);
CREATE INDEX IF NOT EXISTS idx_messages_ticket ON ticket_messages(ticket_id);
CREATE INDEX IF NOT EXISTS idx_attachments_ticket ON ticket_attachments(ticket_id);
CREATE INDEX IF NOT EXISTS idx_kb_category ON knowledge_base(category);
CREATE INDEX IF NOT EXISTS idx_kb_published ON knowledge_base(is_published);

-- Sample knowledge base articles
INSERT OR IGNORE INTO knowledge_base (title, category, content, tags) VALUES
('How to Connect to VPN', 'getting_started', 
'<h2>Connecting to TrueVault VPN</h2>
<ol>
<li>Download the TrueVault VPN client</li>
<li>Install and open the application</li>
<li>Enter your login credentials</li>
<li>Select a server location</li>
<li>Click Connect</li>
</ol>
<p>You should see a green "Connected" status within seconds.</p>',
'["vpn", "connection", "setup", "tutorial"]'),

('Troubleshooting Connection Issues', 'troubleshooting',
'<h2>Can''t Connect to VPN?</h2>
<p>Try these steps:</p>
<ul>
<li>Check your internet connection</li>
<li>Restart the VPN application</li>
<li>Try a different server location</li>
<li>Disable firewall temporarily</li>
<li>Reinstall the VPN client</li>
</ul>
<p>Still having issues? Contact our support team.</p>',
'["troubleshooting", "connection", "errors"]'),

('Billing and Subscription', 'billing',
'<h2>Managing Your Subscription</h2>
<p>You can manage your subscription from your account dashboard:</p>
<ul>
<li>View payment history</li>
<li>Update payment method</li>
<li>Upgrade/downgrade plan</li>
<li>Cancel subscription</li>
</ul>
<p>All changes take effect immediately.</p>',
'["billing", "subscription", "payment"]');

-- Sample canned responses
INSERT OR IGNORE INTO canned_responses (title, category, content, shortcut) VALUES
('Welcome Response', 'general', 
'Thank you for contacting TrueVault VPN support! We''ve received your ticket and will respond within 24 hours. In the meantime, check our knowledge base for instant answers.',
'welcome'),

('Connection Issue', 'technical',
'I''m sorry you''re experiencing connection issues. Let''s troubleshoot this together:

1. Please restart the VPN application
2. Try connecting to a different server
3. Check if your firewall is blocking the connection
4. Ensure you''re using the latest version

Please let me know the results of these steps.',
'conn_issue'),

('Billing Question', 'billing',
'Thank you for your billing inquiry. I''d be happy to help with your account. Could you please provide more details about:

- Which subscription plan you''re on
- The specific billing question or concern

I''ll review your account and get back to you shortly.',
'billing'),

('Password Reset', 'account',
'I can help you reset your password. For security reasons, please use the "Forgot Password" link on our login page. You''ll receive a reset email within minutes.

If you don''t receive the email, please check your spam folder or let me know and I can assist further.',
'pwd_reset');
