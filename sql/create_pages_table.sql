-- Add pages table to main.db for frontend content
CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    meta_description TEXT,
    content TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Seed homepage
INSERT OR IGNORE INTO pages (slug, title, meta_description) VALUES 
('home', 'TrueVault VPN - Secure Privacy Protection', 'Protect your privacy with TrueVault VPN. Simple, secure, and affordable.');
