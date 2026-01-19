-- Tutorial System Database Schema
-- Created: January 19, 2026

-- Tutorial categories
CREATE TABLE IF NOT EXISTS tutorial_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    icon TEXT DEFAULT '📚',
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Tutorials
CREATE TABLE IF NOT EXISTS tutorials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    difficulty TEXT DEFAULT 'beginner',     -- beginner, intermediate, advanced
    duration INTEGER DEFAULT 10,            -- Estimated minutes
    thumbnail TEXT,
    video_url TEXT,
    is_featured INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES tutorial_categories(id) ON DELETE CASCADE
);

-- Tutorial lessons/steps
CREATE TABLE IF NOT EXISTS tutorial_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tutorial_id INTEGER NOT NULL,
    lesson_number INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,                  -- Markdown or HTML
    video_url TEXT,
    code_example TEXT,
    tips TEXT,                              -- JSON array of tips
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
);

-- User progress tracking
CREATE TABLE IF NOT EXISTS tutorial_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tutorial_id INTEGER NOT NULL,
    lesson_id INTEGER,
    status TEXT DEFAULT 'in_progress',      -- not_started, in_progress, completed
    progress_percent INTEGER DEFAULT 0,
    last_accessed TEXT DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES tutorial_lessons(id) ON DELETE CASCADE,
    UNIQUE(user_id, tutorial_id, lesson_id)
);

-- User bookmarks/favorites
CREATE TABLE IF NOT EXISTS tutorial_bookmarks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tutorial_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    UNIQUE(user_id, tutorial_id)
);

-- Tutorial ratings
CREATE TABLE IF NOT EXISTS tutorial_ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tutorial_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    UNIQUE(user_id, tutorial_id)
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_tutorials_category ON tutorials(category_id);
CREATE INDEX IF NOT EXISTS idx_tutorials_published ON tutorials(is_published);
CREATE INDEX IF NOT EXISTS idx_tutorials_featured ON tutorials(is_featured);
CREATE INDEX IF NOT EXISTS idx_lessons_tutorial ON tutorial_lessons(tutorial_id);
CREATE INDEX IF NOT EXISTS idx_progress_user ON tutorial_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_progress_tutorial ON tutorial_progress(tutorial_id);
CREATE INDEX IF NOT EXISTS idx_bookmarks_user ON tutorial_bookmarks(user_id);
CREATE INDEX IF NOT EXISTS idx_ratings_tutorial ON tutorial_ratings(tutorial_id);

-- Insert sample categories
INSERT OR IGNORE INTO tutorial_categories (category_name, slug, description, icon, sort_order) VALUES
('Getting Started', 'getting-started', 'Essential tutorials for new users', '🚀', 1),
('VPN Setup', 'vpn-setup', 'Configure and connect to VPN', '🔐', 2),
('Troubleshooting', 'troubleshooting', 'Fix common issues', '🔧', 3),
('Advanced Features', 'advanced', 'Power user features', '⚡', 4),
('Security & Privacy', 'security', 'Protect your privacy', '🛡️', 5),
('Account Management', 'account', 'Manage your account', '👤', 6);

-- Insert sample tutorials
INSERT OR IGNORE INTO tutorials (category_id, title, slug, description, difficulty, duration, is_featured) VALUES
(1, 'Welcome to TrueVault VPN', 'welcome', 'A complete introduction to TrueVault VPN and its features', 'beginner', 5, 1),
(1, 'Installing Your First VPN Client', 'install-client', 'Step-by-step guide to installing the VPN client on your device', 'beginner', 10, 1),
(2, 'Connecting to a VPN Server', 'connect-server', 'Learn how to connect to different VPN servers', 'beginner', 8, 1),
(2, 'Choosing the Right Server Location', 'server-location', 'Understand which server location works best for you', 'beginner', 7, 0),
(2, 'Using Multiple Devices', 'multiple-devices', 'Set up VPN on all your devices simultaneously', 'intermediate', 15, 0),
(3, 'Connection Issues', 'connection-issues', 'Resolve common connection problems', 'beginner', 12, 1),
(3, 'Slow Speed Troubleshooting', 'slow-speed', 'Diagnose and fix slow VPN speeds', 'intermediate', 10, 0),
(4, 'Split Tunneling', 'split-tunneling', 'Route only specific traffic through VPN', 'advanced', 15, 0),
(4, 'Kill Switch Configuration', 'kill-switch', 'Prevent data leaks with kill switch', 'advanced', 8, 0),
(5, 'DNS Leak Protection', 'dns-leak', 'Ensure your DNS queries are protected', 'intermediate', 10, 1),
(5, 'Understanding Encryption', 'encryption', 'Learn about VPN encryption protocols', 'intermediate', 12, 0),
(6, 'Changing Your Plan', 'change-plan', 'Upgrade or downgrade your subscription', 'beginner', 5, 0),
(6, 'Managing Payment Methods', 'payment-methods', 'Update your billing information', 'beginner', 6, 0);

-- Insert sample lessons for "Welcome to TrueVault VPN"
INSERT OR IGNORE INTO tutorial_lessons (tutorial_id, lesson_number, title, content, sort_order) VALUES
(1, 1, 'What is a VPN?', '
# What is a VPN?

A **Virtual Private Network (VPN)** creates a secure, encrypted connection between your device and the internet.

## Key Benefits:
- 🔒 **Privacy**: Hide your IP address and browsing activity
- 🛡️ **Security**: Encrypt your data on public WiFi
- 🌍 **Freedom**: Access content from anywhere
- 🚫 **Ad Blocking**: Block trackers and ads

## How It Works:
1. Your device connects to a VPN server
2. All internet traffic is encrypted
3. Websites see the VPN server''s IP, not yours
4. Your ISP can''t see what you''re doing online

Think of it as a secure tunnel for your internet traffic!
', 1),

(1, 2, 'TrueVault Features', '
# TrueVault VPN Features

## 🚀 Core Features:
- **Unlimited Bandwidth** - No data caps or throttling
- **Global Servers** - 50+ locations worldwide
- **Multi-Device** - Use on all your devices
- **24/7 Support** - We''re always here to help

## 🔐 Security:
- Military-grade AES-256 encryption
- No-logs policy
- Kill switch protection
- DNS leak protection

## ⚡ Performance:
- Lightning-fast servers
- Optimized for streaming
- Gaming-friendly
- P2P support

## 🎯 Plans:
- **Personal** - 1 device ($9.97/mo)
- **Family** - 5 devices ($14.97/mo)  
- **Dedicated** - Your own server ($39.97/mo)
', 2),

(1, 3, 'Creating Your Account', '
# Creating Your Account

## Step 1: Sign Up
1. Visit [vpn.the-truth-publishing.com](https://vpn.the-truth-publishing.com)
2. Click "Get Started" or "Sign Up"
3. Choose your plan
4. Enter your email and create a password

## Step 2: Payment
1. Enter payment information
2. Review your order
3. Complete purchase

## Step 3: Confirmation
You''ll receive:
- Welcome email with login details
- Setup instructions
- Download links for all platforms

## 💡 Pro Tips:
- Use a strong, unique password
- Save your login details securely
- Download the app before you need it
- Test your connection at home first
', 3);

-- Insert lessons for "Installing Your First VPN Client"
INSERT OR IGNORE INTO tutorial_lessons (tutorial_id, lesson_number, title, content, sort_order) VALUES
(2, 1, 'Downloading the Client', '
# Downloading the VPN Client

## Windows:
1. Log in to your dashboard
2. Click "Downloads"
3. Select "Windows Client"
4. Save the installer (TrueVaultVPN-Setup.exe)

## Mac:
1. Go to Downloads page
2. Select "Mac Client"
3. Download TrueVaultVPN.dmg

## Mobile:
- **iOS**: Search "TrueVault VPN" in App Store
- **Android**: Find us on Google Play Store

## Linux:
Available for Ubuntu, Debian, Fedora
Download the .deb or .rpm package

## ⚠️ System Requirements:
- Windows 10 or later
- macOS 10.14 or later
- iOS 13 or later
- Android 8.0 or later
', 1),

(2, 2, 'Installing on Windows', '
# Installing on Windows

## Installation Steps:
1. **Run the installer** - Double-click TrueVaultVPN-Setup.exe
2. **Allow admin access** - Click "Yes" on UAC prompt
3. **Choose install location** - Default is recommended
4. **Wait for installation** - Takes about 30 seconds
5. **Launch application** - Check "Launch TrueVault VPN"
6. **Sign in** - Use your account credentials

## First Launch:
1. Enter your email and password
2. Choose default server location (optional)
3. Enable/disable auto-connect
4. Click "Get Started"

## 💡 Troubleshooting:
- If install fails, run as Administrator
- Disable antivirus temporarily if blocked
- Ensure you have internet connection
- Contact support if issues persist
', 2),

(2, 3, 'Installing on Mac', '
# Installing on Mac

## Installation Steps:
1. **Open the .dmg file** - Double-click TrueVaultVPN.dmg
2. **Drag to Applications** - Move the app icon to Applications folder
3. **Eject the installer** - Right-click disk image and eject
4. **Launch from Applications** - Find TrueVault VPN in your apps
5. **Allow system extension** - Go to Security & Privacy settings
6. **Grant permissions** - Click "Allow" for network extension

## macOS Permissions:
The app needs to:
- Install network extension
- Access keychain (for saved passwords)
- Send notifications

## First Launch:
1. Enter your login credentials
2. Allow system extension when prompted
3. Configure preferences
4. Connect to your first server

## 🍎 Mac-Specific Tips:
- Add to Dock for quick access
- Enable "Launch at Login" in preferences
- Use menu bar icon for quick connect
', 3);
