# TRUEVAULT VPN - COMPLETE MASTER BLUEPRINT
## The Definitive Technical Specification
**Version:** 1.0 FINAL  
**Created:** January 14, 2026  
**Last Updated:** January 14, 2026 5:45 PM CST  
**Total Lines:** ~20,000+  
**Status:** COMPLETE - Ready for Development

---

# TABLE OF CONTENTS

## Part 1: Foundation
1. [Executive Summary](#section-1-executive-summary)
2. [Database Architecture](#section-2-database-architecture) ⭐ UPDATED
3. [File Structure](#section-3-file-structure)
4. [Server Configuration](#section-4-server-configuration)

## Part 2: Core Features
5. [Authentication System](#section-5-authentication-system)
6. [Device Management](#section-6-device-management)
7. [Billing & Payments](#section-7-billing-payments)
8. [Admin Control Panel](#section-8-admin-control-panel) ⭐ UPDATED

## Part 3: Advanced Features
9. [Parental Controls](#section-9-parental-controls)
10. [Camera Dashboard](#section-10-camera-dashboard)
11. [Network Scanner](#section-11-network-scanner)
12. [QoS System](#section-12-qos-system)
13. [Port Forwarding](#section-13-port-forwarding)
14. [VIP System](#section-14-vip-system)
15. [Security & Error Handling](#section-15-security-error-handling)

## Part 4: NEW - Business Automation Suite
16. [Database Builder](#section-16-database-builder) ⭐ NEW
17. [Form Library & Builder](#section-17-form-library-builder) ⭐ NEW
18. [Marketing Automation](#section-18-marketing-automation) ⭐ NEW
19. [Tutorial System](#section-19-tutorial-system) ⭐ NEW

## Part 5: Appendices
- [API Reference](#api-reference)
- [Theme Variables](#theme-variables)
- [Transfer Checklist](#transfer-checklist)

---

# SECTION 1: EXECUTIVE SUMMARY

## 1.1 Project Overview

**TrueVault VPN** is a revolutionary VPN service designed for:
- **2-Click Maximum** - Any user action takes 2 clicks or less
- **One-Man Operation** - Fully automated, minimal daily maintenance
- **Business Transferability** - New owner can take over in 30 minutes
- **Database-Driven** - NO hardcoded values, everything editable via admin

## 1.2 Unique Selling Points

**Features NO Other VPN Offers:**
| Feature | TrueVault | NordVPN | ExpressVPN |
|---------|-----------|---------|------------|
| Parental Controls | ✅ | ❌ | ❌ |
| Camera Dashboard | ✅ | ❌ | ❌ |
| Network Scanner | ✅ | ❌ | ❌ |
| QoS Gaming Mode | ✅ | ❌ | ❌ |
| Smart Port Forward | ✅ | ❌ | ❌ |
| 2-Click Setup | ✅ | ❌ | ❌ |
| Built-in Marketing | ✅ | ❌ | ❌ |
| Database Builder | ✅ | ❌ | ❌ |
| 50+ Form Templates | ✅ | ❌ | ❌ |

## 1.3 Target Markets

1. **Families** - Parental controls, screen time, content filtering
2. **Gamers** - QoS prioritization, port forwarding, low latency
3. **Security-Conscious** - Camera dashboard, device scanning
4. **Small Businesses** - VPN + CRM + Marketing automation

## 1.4 Pricing Plans (Database-Driven)

| Plan | Monthly | Features |
|------|---------|----------|
| Personal | $9.99 | 3 devices, basic features |
| Family | $14.99 | 10 devices, parental controls |
| Business | $29.99 | Unlimited devices, API access |

## 1.5 Core Principles

1. **2-CLICK MAXIMUM** - No feature requires more than 2 clicks
2. **NO MOCKUPS** - Every UI element connects to real data
3. **DATABASE-DRIVEN** - All config, themes, content from SQLite
4. **ONE-MAN OPERATION** - 5 min/day maximum admin work
5. **PORTABLE** - Easy transfer to new owner in 30 minutes
6. **ZERO AD BUDGET** - Marketing runs on FREE platforms only

---

# SECTION 2: DATABASE ARCHITECTURE

## 2.1 Database Overview

**CRITICAL:** All databases are SQLite, stored separately (NOT clumped).

**Location:** `/vpn.the-truth-publishing.com/databases/`

### Database Directory Structure
```
/databases/
├── core/
│   ├── users.db          # User accounts & devices
│   └── admin.db          # Admin users
├── vpn/
│   ├── servers.db        # VPN server configuration
│   └── peers.db          # WireGuard peer connections
├── billing/
│   └── billing.db        # Subscriptions & payments
├── cms/
│   ├── themes.db         # Visual theming (ALL colors, fonts)
│   └── pages.db          # Page content
├── features/
│   ├── parental.db       # Parental control settings
│   ├── cameras.db        # Camera configurations
│   ├── scanner.db        # Network scanner results
│   └── qos.db            # QoS profiles
├── builder/              # NEW - Database Builder
│   ├── builder.db        # User-created tables
│   └── forms.db          # Form templates & submissions
├── marketing/            # NEW - Marketing Automation
│   ├── campaigns.db      # Email & social campaigns
│   └── calendar.db       # 365-day marketing calendar
├── tutorials/            # NEW - Tutorial System
│   └── tutorials.db      # Tutorial progress tracking
└── logs/
    └── logs.db           # System logs
```

## 2.2 Core Database: users.db

```sql
-- =================================================================
-- DATABASE: users.db
-- PURPOSE: Core user accounts, authentication, devices
-- =================================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    phone TEXT,
    timezone TEXT DEFAULT 'America/Chicago',
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'suspended', 'cancelled', 'trial')),
    is_vip INTEGER DEFAULT 0,
    vip_type TEXT, -- 'owner', 'dedicated', 'standard'
    plan_type TEXT DEFAULT 'personal' CHECK(plan_type IN ('personal', 'family', 'business', 'trial')),
    trial_ends_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    login_count INTEGER DEFAULT 0
);

-- User devices (VPN connections)
CREATE TABLE IF NOT EXISTS user_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_uuid TEXT UNIQUE NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT DEFAULT 'unknown' CHECK(device_type IN ('phone', 'tablet', 'laptop', 'desktop', 'router', 'other', 'unknown')),
    os_type TEXT, -- 'ios', 'android', 'windows', 'mac', 'linux'
    public_key TEXT UNIQUE NOT NULL,
    private_key_encrypted TEXT, -- For backup/restore
    server_id INTEGER,
    assigned_ip TEXT,
    is_active INTEGER DEFAULT 1,
    last_handshake DATETIME,
    bytes_sent INTEGER DEFAULT 0,
    bytes_received INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_connected DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, device_name)
);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- User sessions (for multi-device login)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT UNIQUE NOT NULL,
    device_info TEXT, -- JSON: browser, OS, IP
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_uuid ON users(uuid);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_devices_user ON user_devices(user_id);
CREATE INDEX IF NOT EXISTS idx_devices_public_key ON user_devices(public_key);
CREATE INDEX IF NOT EXISTS idx_devices_server ON user_devices(server_id);
```

## 2.3 VPN Database: servers.db

```sql
-- =================================================================
-- DATABASE: servers.db
-- PURPOSE: VPN server configuration and status
-- =================================================================

-- VPN Servers (THE source of truth)
CREATE TABLE IF NOT EXISTS vpn_servers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,              -- Internal: truevault-ny
    display_name TEXT NOT NULL,             -- Display: New York, USA
    country TEXT NOT NULL,                  -- USA
    country_code TEXT NOT NULL,             -- US
    country_flag TEXT NOT NULL,             -- 🇺🇸
    ip_address TEXT NOT NULL,               -- 66.94.103.91
    wireguard_port INTEGER DEFAULT 51820,
    api_port INTEGER DEFAULT 8080,
    public_key TEXT,                        -- Server's WireGuard public key
    dns_primary TEXT DEFAULT '1.1.1.1',
    dns_secondary TEXT DEFAULT '8.8.8.8',
    subnet TEXT DEFAULT '10.0.0.0/24',      -- IP range for this server
    server_type TEXT DEFAULT 'shared' CHECK(server_type IN ('shared', 'vip_dedicated', 'streaming', 'gaming')),
    vip_user_email TEXT,                    -- Only for vip_dedicated type
    max_connections INTEGER DEFAULT 50,
    current_connections INTEGER DEFAULT 0,
    bandwidth_limit_mbps INTEGER,           -- NULL = unlimited
    cpu_load INTEGER DEFAULT 0,
    memory_percent INTEGER DEFAULT 0,
    latency_ms INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    is_maintenance INTEGER DEFAULT 0,
    maintenance_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Server features (what each server supports)
CREATE TABLE IF NOT EXISTS server_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    feature_name TEXT NOT NULL,             -- 'streaming', 'gaming', 'p2p', 'double_vpn'
    is_enabled INTEGER DEFAULT 1,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id) ON DELETE CASCADE,
    UNIQUE(server_id, feature_name)
);

-- Server health monitoring
CREATE TABLE IF NOT EXISTS server_health (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    server_id INTEGER NOT NULL,
    cpu_percent INTEGER,
    memory_percent INTEGER,
    disk_percent INTEGER,
    connections INTEGER,
    bandwidth_in_mbps REAL,
    bandwidth_out_mbps REAL,
    latency_ms INTEGER,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES vpn_servers(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_servers_type ON vpn_servers(server_type);
CREATE INDEX IF NOT EXISTS idx_servers_active ON vpn_servers(is_active);
CREATE INDEX IF NOT EXISTS idx_health_server ON server_health(server_id);
CREATE INDEX IF NOT EXISTS idx_health_time ON server_health(recorded_at);
```

## 2.4 Billing Database: billing.db

```sql
-- =================================================================
-- DATABASE: billing.db
-- PURPOSE: Subscriptions, payments, invoices
-- =================================================================

-- Subscription plans (database-driven pricing)
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_code TEXT UNIQUE NOT NULL,         -- 'personal', 'family', 'business'
    display_name TEXT NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2),
    max_devices INTEGER NOT NULL,
    features_json TEXT,                     -- JSON array of feature keys
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User subscriptions
CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    paypal_subscription_id TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'past_due', 'cancelled', 'expired', 'trial')),
    billing_cycle TEXT DEFAULT 'monthly' CHECK(billing_cycle IN ('monthly', 'yearly')),
    current_period_start DATETIME,
    current_period_end DATETIME,
    trial_start DATETIME,
    trial_end DATETIME,
    cancelled_at DATETIME,
    cancellation_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Payment history
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    paypal_transaction_id TEXT,
    amount DECIMAL(10,2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'completed' CHECK(status IN ('completed', 'pending', 'failed', 'refunded')),
    payment_method TEXT DEFAULT 'paypal',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT UNIQUE NOT NULL,    -- INV-2026-00001
    user_id INTEGER NOT NULL,
    subscription_id INTEGER,
    payment_id INTEGER,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'paid' CHECK(status IN ('draft', 'sent', 'paid', 'void')),
    due_date DATE,
    paid_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- Insert default plans
INSERT OR IGNORE INTO plans (plan_code, display_name, description, price_monthly, price_yearly, max_devices, features_json, sort_order) VALUES
('personal', 'Personal', 'Perfect for individuals', 9.99, 99.99, 3, '["vpn","support"]', 1),
('family', 'Family', 'Protect your whole family', 14.99, 149.99, 10, '["vpn","support","parental","cameras"]', 2),
('business', 'Business', 'For teams and businesses', 29.99, 299.99, -1, '["vpn","support","parental","cameras","api","priority"]', 3);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_subscriptions_user ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_user ON invoices(user_id);
```

## 2.5 Theme Database: themes.db

```sql
-- =================================================================
-- DATABASE: themes.db
-- PURPOSE: ALL visual styling (NO hardcoded CSS values)
-- =================================================================

-- Theme definitions
CREATE TABLE IF NOT EXISTS themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_code TEXT UNIQUE NOT NULL,        -- 'dark', 'light', 'christmas'
    display_name TEXT NOT NULL,
    description TEXT,
    is_default INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    season TEXT,                            -- 'winter', 'summer', 'halloween'
    active_from DATE,                       -- For seasonal themes
    active_to DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Theme variables (CSS custom properties)
CREATE TABLE IF NOT EXISTS theme_variables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    theme_id INTEGER NOT NULL,
    variable_name TEXT NOT NULL,            -- '--primary-color'
    variable_value TEXT NOT NULL,           -- '#00d9ff'
    category TEXT DEFAULT 'colors',         -- 'colors', 'fonts', 'spacing', 'borders'
    description TEXT,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE(theme_id, variable_name)
);

-- Insert default dark theme
INSERT OR IGNORE INTO themes (theme_code, display_name, description, is_default, is_active) VALUES
('dark', 'Dark Mode', 'Default dark theme with cyan accents', 1, 1),
('light', 'Light Mode', 'Clean light theme for daytime use', 0, 1),
('christmas', 'Christmas', 'Holiday theme with red and green', 0, 1);

-- Insert default theme variables (Dark Theme)
INSERT OR IGNORE INTO theme_variables (theme_id, variable_name, variable_value, category, description) VALUES
-- Dark Theme Colors
(1, '--bg-primary', '#0f0f1a', 'colors', 'Main background'),
(1, '--bg-secondary', '#1a1a2e', 'colors', 'Secondary background'),
(1, '--bg-card', 'rgba(255,255,255,0.04)', 'colors', 'Card background'),
(1, '--text-primary', '#ffffff', 'colors', 'Primary text'),
(1, '--text-secondary', '#888888', 'colors', 'Secondary text'),
(1, '--accent-primary', '#00d9ff', 'colors', 'Primary accent (cyan)'),
(1, '--accent-secondary', '#00ff88', 'colors', 'Secondary accent (green)'),
(1, '--accent-gradient', 'linear-gradient(90deg, #00d9ff, #00ff88)', 'colors', 'Accent gradient'),
(1, '--error', '#ff6464', 'colors', 'Error color'),
(1, '--warning', '#ffaa00', 'colors', 'Warning color'),
(1, '--success', '#00ff88', 'colors', 'Success color'),
(1, '--border-color', 'rgba(255,255,255,0.08)', 'colors', 'Border color'),
-- Dark Theme Fonts
(1, '--font-primary', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', 'fonts', 'Primary font stack'),
(1, '--font-mono', '"Fira Code", "Courier New", monospace', 'fonts', 'Monospace font'),
(1, '--font-size-base', '16px', 'fonts', 'Base font size'),
(1, '--font-size-sm', '14px', 'fonts', 'Small font size'),
(1, '--font-size-lg', '18px', 'fonts', 'Large font size'),
(1, '--font-size-xl', '24px', 'fonts', 'Extra large font size'),
-- Dark Theme Spacing
(1, '--spacing-xs', '4px', 'spacing', 'Extra small spacing'),
(1, '--spacing-sm', '8px', 'spacing', 'Small spacing'),
(1, '--spacing-md', '16px', 'spacing', 'Medium spacing'),
(1, '--spacing-lg', '24px', 'spacing', 'Large spacing'),
(1, '--spacing-xl', '32px', 'spacing', 'Extra large spacing'),
-- Dark Theme Borders
(1, '--border-radius-sm', '4px', 'borders', 'Small border radius'),
(1, '--border-radius-md', '8px', 'borders', 'Medium border radius'),
(1, '--border-radius-lg', '14px', 'borders', 'Large border radius'),
(1, '--border-radius-full', '9999px', 'borders', 'Full border radius (pills)');
```



## 2.6 NEW Database: builder.db (Database Builder)

```sql
-- =================================================================
-- DATABASE: builder.db
-- PURPOSE: Visual database builder for non-technical users
-- =================================================================

-- User-created custom tables
CREATE TABLE IF NOT EXISTS user_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,        -- Internal name (alphanumeric)
    display_name TEXT NOT NULL,             -- User-friendly name
    description TEXT,
    icon TEXT DEFAULT '📋',                 -- Emoji icon for the table
    is_system INTEGER DEFAULT 0,            -- 1 = system table, can't delete
    row_count INTEGER DEFAULT 0,            -- Cached row count
    created_by INTEGER,                     -- admin user id
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Fields in user-created tables
CREATE TABLE IF NOT EXISTS user_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    field_name TEXT NOT NULL,               -- Internal name
    display_name TEXT NOT NULL,             -- Label shown to users
    field_type TEXT NOT NULL CHECK(field_type IN (
        'text', 'textarea', 'number', 'decimal', 'date', 'datetime', 'time',
        'dropdown', 'checkbox', 'radio', 'file', 'image',
        'email', 'phone', 'url', 'currency', 'rating', 'color', 'signature'
    )),
    is_required INTEGER DEFAULT 0,
    is_unique INTEGER DEFAULT 0,
    default_value TEXT,
    placeholder TEXT,                       -- Placeholder text for input
    help_text TEXT,                         -- Tooltip/help text
    validation_rules TEXT,                  -- JSON: {"min": 0, "max": 100, "pattern": "..."}
    dropdown_options TEXT,                  -- JSON array for dropdowns: ["Option1", "Option2"]
    file_types TEXT,                        -- For file uploads: "pdf,doc,docx"
    max_file_size INTEGER,                  -- Max file size in KB
    field_order INTEGER DEFAULT 0,          -- Display order
    is_searchable INTEGER DEFAULT 0,        -- Include in search
    is_filterable INTEGER DEFAULT 0,        -- Show in filter options
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES user_tables(id) ON DELETE CASCADE,
    UNIQUE(table_id, field_name)
);

-- Table relationships (visual relationship builder)
CREATE TABLE IF NOT EXISTS table_relationships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_table_id INTEGER NOT NULL,
    to_table_id INTEGER NOT NULL,
    relationship_type TEXT NOT NULL CHECK(relationship_type IN (
        'one_to_one', 'one_to_many', 'many_to_many'
    )),
    from_field TEXT NOT NULL,               -- Foreign key field in from_table
    to_field TEXT NOT NULL,                 -- Primary key field in to_table (usually 'id')
    on_delete TEXT DEFAULT 'CASCADE',       -- CASCADE, SET NULL, RESTRICT
    display_name TEXT,                      -- "Customer Orders", "Order Items"
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_table_id) REFERENCES user_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (to_table_id) REFERENCES user_tables(id) ON DELETE CASCADE
);

-- Data import/export history
CREATE TABLE IF NOT EXISTS data_transfers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    transfer_type TEXT NOT NULL CHECK(transfer_type IN ('import', 'export')),
    file_name TEXT NOT NULL,
    file_format TEXT NOT NULL,              -- 'csv', 'xlsx', 'json'
    row_count INTEGER,
    status TEXT DEFAULT 'completed',
    error_message TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES user_tables(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_user_fields_table ON user_fields(table_id);
CREATE INDEX IF NOT EXISTS idx_relationships_from ON table_relationships(from_table_id);
CREATE INDEX IF NOT EXISTS idx_relationships_to ON table_relationships(to_table_id);
```

## 2.7 NEW Database: forms.db (Form Library & Builder)

```sql
-- =================================================================
-- DATABASE: forms.db
-- PURPOSE: 50+ pre-built form templates and custom forms
-- =================================================================

-- Form template categories
CREATE TABLE IF NOT EXISTS form_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_code TEXT UNIQUE NOT NULL,
    display_name TEXT NOT NULL,
    description TEXT,
    icon TEXT DEFAULT '📝',
    sort_order INTEGER DEFAULT 0
);

-- Pre-built form templates (50+ templates)
CREATE TABLE IF NOT EXISTS form_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_code TEXT UNIQUE NOT NULL,     -- 'customer_registration', 'support_ticket'
    template_name TEXT NOT NULL,
    category_id INTEGER NOT NULL,
    description TEXT,
    fields_json TEXT NOT NULL,              -- JSON array of field definitions
    default_style TEXT DEFAULT 'business',  -- 'casual', 'business', 'corporate'
    is_active INTEGER DEFAULT 1,
    usage_count INTEGER DEFAULT 0,          -- Track popularity
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES form_categories(id)
);

-- User-created forms (from templates or scratch)
CREATE TABLE IF NOT EXISTS forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_uuid TEXT UNIQUE NOT NULL,
    form_name TEXT NOT NULL,
    form_slug TEXT NOT NULL UNIQUE,         -- URL-friendly: 'contact-us'
    template_id INTEGER,                    -- NULL if created from scratch
    style TEXT NOT NULL DEFAULT 'business' CHECK(style IN ('casual', 'business', 'corporate')),
    fields_json TEXT NOT NULL,              -- JSON array of field definitions
    settings_json TEXT,                     -- JSON: success_message, redirect_url, etc.
    notification_email TEXT,                -- Send submissions here
    confirmation_email INTEGER DEFAULT 0,   -- Send confirmation to submitter
    save_to_table_id INTEGER,              -- Save to custom database table
    is_active INTEGER DEFAULT 1,
    is_public INTEGER DEFAULT 1,            -- Can be accessed without login
    view_count INTEGER DEFAULT 0,
    submission_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES form_templates(id),
    FOREIGN KEY (save_to_table_id) REFERENCES user_tables(id)
);

-- Form submissions
CREATE TABLE IF NOT EXISTS form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_uuid TEXT UNIQUE NOT NULL,
    form_id INTEGER NOT NULL,
    submission_data TEXT NOT NULL,          -- JSON of all field values
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT,
    referrer_url TEXT,
    status TEXT DEFAULT 'new' CHECK(status IN ('new', 'read', 'replied', 'archived', 'spam')),
    assigned_to INTEGER,                    -- Admin user ID
    notes TEXT,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- Form style presets
CREATE TABLE IF NOT EXISTS form_styles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    style_code TEXT UNIQUE NOT NULL,        -- 'casual', 'business', 'corporate'
    display_name TEXT NOT NULL,
    colors_json TEXT NOT NULL,              -- JSON: {primary, secondary, accent, bg, text}
    fonts_json TEXT NOT NULL,               -- JSON: {heading, body, button}
    border_radius TEXT NOT NULL,            -- '12px' for casual, '6px' business, '0' corporate
    tone_examples TEXT,                     -- Example text for each tone
    description TEXT,
    is_active INTEGER DEFAULT 1
);

-- Insert form categories
INSERT OR IGNORE INTO form_categories (category_code, display_name, description, icon, sort_order) VALUES
('customer', 'Customer Management', 'Forms for managing customer interactions', '👤', 1),
('sales', 'Sales & Billing', 'Quote requests, orders, invoices', '💰', 2),
('support', 'Support & Service', 'Tickets, bug reports, feature requests', '🎧', 3),
('marketing', 'Marketing & Leads', 'Lead capture, newsletters, surveys', '📢', 4),
('hr', 'HR & Operations', 'Job applications, time off, expenses', '📋', 5),
('vpn', 'VPN-Specific', 'VPN account setup, server requests', '🔒', 6);

-- Insert form styles
INSERT OR IGNORE INTO form_styles (style_code, display_name, colors_json, fonts_json, border_radius, tone_examples, description) VALUES
('casual', 'Casual', 
 '{"primary":"#FF6B6B","secondary":"#4ECDC4","accent":"#FFE66D","bg":"#FFFFFF","text":"#333333"}',
 '{"heading":"Poppins, sans-serif","body":"Nunito, sans-serif","button":"Poppins, sans-serif"}',
 '12px',
 'Hey there! 👋 Let''s get started!',
 'Friendly, approachable design with rounded corners and bright colors'),
('business', 'Business',
 '{"primary":"#4A90E2","secondary":"#5CB85C","accent":"#F0AD4E","bg":"#FFFFFF","text":"#333333"}',
 '{"heading":"Inter, sans-serif","body":"Open Sans, sans-serif","button":"Inter, sans-serif"}',
 '6px',
 'Please complete the form below.',
 'Professional, clean design with subtle colors'),
('corporate', 'Corporate',
 '{"primary":"#1A1A2E","secondary":"#D4AF37","accent":"#16213E","bg":"#FFFFFF","text":"#1A1A2E"}',
 '{"heading":"Merriweather, serif","body":"Playfair Display, serif","button":"Inter, sans-serif"}',
 '0px',
 'Kindly provide the requested information.',
 'Premium, formal design with elegant typography');

-- Insert ALL 55 form templates
INSERT OR IGNORE INTO form_templates (template_code, template_name, category_id, description, fields_json) VALUES
-- Customer Management (10 forms)
('customer_registration', 'Customer Registration', 1, 'New customer signup form', 
 '[{"name":"first_name","type":"text","label":"First Name","required":true},{"name":"last_name","type":"text","label":"Last Name","required":true},{"name":"email","type":"email","label":"Email Address","required":true},{"name":"phone","type":"phone","label":"Phone Number","required":false},{"name":"company","type":"text","label":"Company Name","required":false},{"name":"how_heard","type":"dropdown","label":"How did you hear about us?","options":["Google","Social Media","Friend","Advertisement","Other"]}]'),
('customer_profile_update', 'Customer Profile Update', 1, 'Update customer information', 
 '[{"name":"first_name","type":"text","label":"First Name","required":true},{"name":"last_name","type":"text","label":"Last Name","required":true},{"name":"email","type":"email","label":"Email Address","required":true},{"name":"phone","type":"phone","label":"Phone Number"},{"name":"address","type":"textarea","label":"Mailing Address"},{"name":"preferences","type":"checkbox","label":"Communication Preferences","options":["Email","SMS","Phone"]}]'),
('customer_feedback', 'Customer Feedback', 1, 'Collect customer feedback', 
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"rating","type":"rating","label":"Overall Satisfaction","required":true},{"name":"what_liked","type":"textarea","label":"What did you like?"},{"name":"improvements","type":"textarea","label":"What could we improve?"},{"name":"recommend","type":"radio","label":"Would you recommend us?","options":["Definitely","Probably","Not Sure","Probably Not"]}]'),
('customer_satisfaction_survey', 'Customer Satisfaction Survey', 1, 'Detailed satisfaction survey',
 '[{"name":"overall_rating","type":"rating","label":"Overall Experience","required":true},{"name":"product_quality","type":"rating","label":"Product Quality"},{"name":"customer_service","type":"rating","label":"Customer Service"},{"name":"value_for_money","type":"rating","label":"Value for Money"},{"name":"comments","type":"textarea","label":"Additional Comments"}]'),
('customer_complaint', 'Customer Complaint', 1, 'Handle customer complaints',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"order_number","type":"text","label":"Order/Account Number"},{"name":"complaint_type","type":"dropdown","label":"Type of Complaint","options":["Product Quality","Service Issue","Billing Problem","Delivery Issue","Other"],"required":true},{"name":"description","type":"textarea","label":"Please describe the issue in detail","required":true},{"name":"resolution","type":"textarea","label":"What resolution would you like?"}]'),
('rma_request', 'RMA Request', 1, 'Return Merchandise Authorization',
 '[{"name":"name","type":"text","label":"Customer Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"order_number","type":"text","label":"Order Number","required":true},{"name":"product_name","type":"text","label":"Product Name","required":true},{"name":"reason","type":"dropdown","label":"Return Reason","options":["Defective","Wrong Item","Not as Described","Changed Mind","Other"],"required":true},{"name":"description","type":"textarea","label":"Please describe the issue"},{"name":"photos","type":"file","label":"Upload Photos (if applicable)","file_types":"jpg,png,gif"}]'),
('product_return', 'Product Return', 1, 'Standard product return form',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone Number"},{"name":"order_number","type":"text","label":"Order Number","required":true},{"name":"items","type":"textarea","label":"Items to Return","required":true},{"name":"reason","type":"dropdown","label":"Return Reason","options":["Wrong Size","Defective","Not as Expected","No Longer Needed","Other"],"required":true},{"name":"condition","type":"radio","label":"Item Condition","options":["Unopened","Opened - Unused","Used"]}]'),
('warranty_claim', 'Warranty Claim', 1, 'Submit warranty claims',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"product_name","type":"text","label":"Product Name","required":true},{"name":"serial_number","type":"text","label":"Serial Number"},{"name":"purchase_date","type":"date","label":"Purchase Date","required":true},{"name":"issue_description","type":"textarea","label":"Describe the Issue","required":true},{"name":"proof_of_purchase","type":"file","label":"Upload Proof of Purchase","file_types":"pdf,jpg,png"}]'),
('service_request', 'Service Request', 1, 'Request professional services',
 '[{"name":"name","type":"text","label":"Contact Name","required":true},{"name":"company","type":"text","label":"Company Name"},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone Number","required":true},{"name":"service_type","type":"dropdown","label":"Service Needed","options":["Installation","Repair","Maintenance","Consultation","Training","Other"],"required":true},{"name":"preferred_date","type":"date","label":"Preferred Date"},{"name":"description","type":"textarea","label":"Service Details","required":true}]'),
('account_closure', 'Account Closure', 1, 'Request account deletion',
 '[{"name":"name","type":"text","label":"Account Holder Name","required":true},{"name":"email","type":"email","label":"Email Address","required":true},{"name":"account_id","type":"text","label":"Account ID"},{"name":"reason","type":"dropdown","label":"Reason for Closing","options":["No Longer Needed","Found Alternative","Too Expensive","Poor Service","Other"],"required":true},{"name":"feedback","type":"textarea","label":"Additional Feedback"},{"name":"confirm","type":"checkbox","label":"I understand this action is permanent","required":true}]'),

-- Sales & Billing (10 forms)
('quote_request', 'Quote Request', 2, 'Request a price quote',
 '[{"name":"company","type":"text","label":"Company Name","required":true},{"name":"contact_name","type":"text","label":"Contact Person","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone Number"},{"name":"products","type":"textarea","label":"Products/Services Needed","required":true},{"name":"quantity","type":"number","label":"Estimated Quantity"},{"name":"budget","type":"dropdown","label":"Budget Range","options":["Under $1,000","$1,000 - $5,000","$5,000 - $10,000","$10,000+"]},{"name":"timeline","type":"text","label":"When do you need this?"}]'),
('order_form', 'Order Form', 2, 'Place an order',
 '[{"name":"customer_name","type":"text","label":"Customer Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"billing_address","type":"textarea","label":"Billing Address","required":true},{"name":"shipping_address","type":"textarea","label":"Shipping Address"},{"name":"products","type":"textarea","label":"Products to Order","required":true},{"name":"special_instructions","type":"textarea","label":"Special Instructions"}]'),
('invoice_template', 'Invoice Template', 2, 'Generate invoices',
 '[{"name":"invoice_number","type":"text","label":"Invoice #","required":true},{"name":"invoice_date","type":"date","label":"Invoice Date","required":true},{"name":"due_date","type":"date","label":"Due Date","required":true},{"name":"client_name","type":"text","label":"Bill To","required":true},{"name":"items","type":"textarea","label":"Line Items"},{"name":"subtotal","type":"currency","label":"Subtotal"},{"name":"tax","type":"currency","label":"Tax"},{"name":"total","type":"currency","label":"Total Due","required":true}]'),
('payment_form', 'Payment Form', 2, 'Collect payments',
 '[{"name":"name","type":"text","label":"Cardholder Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"amount","type":"currency","label":"Payment Amount","required":true},{"name":"description","type":"text","label":"Payment For"},{"name":"billing_address","type":"textarea","label":"Billing Address"}]'),
('refund_request', 'Refund Request', 2, 'Request a refund',
 '[{"name":"name","type":"text","label":"Customer Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"order_number","type":"text","label":"Order Number","required":true},{"name":"amount","type":"currency","label":"Refund Amount Requested"},{"name":"reason","type":"dropdown","label":"Refund Reason","options":["Product Defective","Service Not Rendered","Duplicate Charge","Cancelled Order","Other"],"required":true},{"name":"details","type":"textarea","label":"Additional Details"}]'),
('credit_application', 'Credit Application', 2, 'Apply for credit/financing',
 '[{"name":"company_name","type":"text","label":"Company Name","required":true},{"name":"contact_name","type":"text","label":"Contact Person","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"address","type":"textarea","label":"Business Address","required":true},{"name":"years_in_business","type":"number","label":"Years in Business"},{"name":"credit_requested","type":"currency","label":"Credit Amount Requested"},{"name":"bank_reference","type":"text","label":"Bank Reference"},{"name":"trade_references","type":"textarea","label":"Trade References (3 minimum)"}]'),
('purchase_order', 'Purchase Order', 2, 'Create purchase orders',
 '[{"name":"po_number","type":"text","label":"PO Number","required":true},{"name":"date","type":"date","label":"Date","required":true},{"name":"vendor","type":"text","label":"Vendor Name","required":true},{"name":"ship_to","type":"textarea","label":"Ship To Address","required":true},{"name":"items","type":"textarea","label":"Items Ordered","required":true},{"name":"total","type":"currency","label":"Total Amount"},{"name":"payment_terms","type":"dropdown","label":"Payment Terms","options":["Net 30","Net 60","Due on Receipt","50% Upfront"]}]'),
('contract_agreement', 'Contract Agreement', 2, 'Service contract form',
 '[{"name":"client_name","type":"text","label":"Client Name","required":true},{"name":"client_email","type":"email","label":"Client Email","required":true},{"name":"service_description","type":"textarea","label":"Services to be Provided","required":true},{"name":"start_date","type":"date","label":"Contract Start Date","required":true},{"name":"end_date","type":"date","label":"Contract End Date"},{"name":"total_value","type":"currency","label":"Total Contract Value"},{"name":"payment_schedule","type":"textarea","label":"Payment Schedule"},{"name":"signature","type":"signature","label":"Client Signature","required":true},{"name":"date_signed","type":"date","label":"Date Signed","required":true}]'),
('subscription_change', 'Subscription Change', 2, 'Modify subscription plans',
 '[{"name":"name","type":"text","label":"Account Holder","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"current_plan","type":"text","label":"Current Plan"},{"name":"new_plan","type":"dropdown","label":"New Plan","options":["Personal","Family","Business"],"required":true},{"name":"effective_date","type":"date","label":"Effective Date"},{"name":"reason","type":"textarea","label":"Reason for Change"}]'),
('cancellation_form', 'Cancellation Form', 2, 'Cancel subscription/service',
 '[{"name":"name","type":"text","label":"Account Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"account_number","type":"text","label":"Account Number"},{"name":"cancellation_date","type":"date","label":"Cancellation Date","required":true},{"name":"reason","type":"dropdown","label":"Cancellation Reason","options":["Too Expensive","Not Using","Found Alternative","Poor Service","Other"],"required":true},{"name":"feedback","type":"textarea","label":"How can we improve?"},{"name":"confirm","type":"checkbox","label":"I confirm I want to cancel","required":true}]'),

-- Support & Service (10 forms)
('support_ticket', 'Support Ticket', 3, 'Submit support requests',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"subject","type":"text","label":"Subject","required":true},{"name":"category","type":"dropdown","label":"Category","options":["Technical Issue","Billing Question","Account Help","Feature Request","Other"],"required":true},{"name":"priority","type":"dropdown","label":"Priority","options":["Low","Medium","High","Urgent"]},{"name":"description","type":"textarea","label":"Describe your issue","required":true},{"name":"attachments","type":"file","label":"Attachments","file_types":"jpg,png,pdf,zip"}]'),
('bug_report', 'Bug Report', 3, 'Report software bugs',
 '[{"name":"reporter","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"bug_title","type":"text","label":"Bug Title","required":true},{"name":"severity","type":"dropdown","label":"Severity","options":["Critical","Major","Minor","Cosmetic"],"required":true},{"name":"steps_to_reproduce","type":"textarea","label":"Steps to Reproduce","required":true},{"name":"expected_behavior","type":"textarea","label":"Expected Behavior"},{"name":"actual_behavior","type":"textarea","label":"Actual Behavior","required":true},{"name":"screenshots","type":"file","label":"Screenshots","file_types":"jpg,png,gif"},{"name":"browser_os","type":"text","label":"Browser/OS"}]'),
('feature_request', 'Feature Request', 3, 'Request new features',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"feature_title","type":"text","label":"Feature Title","required":true},{"name":"description","type":"textarea","label":"Describe the feature","required":true},{"name":"use_case","type":"textarea","label":"How would you use this?"},{"name":"priority","type":"dropdown","label":"How important is this?","options":["Nice to have","Important","Critical"]}]'),
('technical_support', 'Technical Support', 3, 'Get technical help',
 '[{"name":"name","type":"text","label":"Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"product","type":"dropdown","label":"Product","options":["VPN App","Camera Dashboard","Network Scanner","Other"],"required":true},{"name":"os","type":"dropdown","label":"Operating System","options":["Windows","Mac","iOS","Android","Linux","Other"]},{"name":"issue","type":"textarea","label":"Describe the technical issue","required":true},{"name":"error_message","type":"textarea","label":"Error Message (if any)"},{"name":"screenshots","type":"file","label":"Screenshots"}]'),
('installation_request', 'Installation Request', 3, 'Request installation assistance',
 '[{"name":"name","type":"text","label":"Contact Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"address","type":"textarea","label":"Installation Address","required":true},{"name":"product","type":"text","label":"Product to Install","required":true},{"name":"preferred_date","type":"date","label":"Preferred Date"},{"name":"preferred_time","type":"dropdown","label":"Preferred Time","options":["Morning (8-12)","Afternoon (12-5)","Evening (5-8)"]},{"name":"notes","type":"textarea","label":"Special Instructions"}]'),
('training_request', 'Training Request', 3, 'Request product training',
 '[{"name":"name","type":"text","label":"Contact Name","required":true},{"name":"company","type":"text","label":"Company Name"},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"topic","type":"dropdown","label":"Training Topic","options":["Basic Setup","Advanced Features","Admin Console","API Integration","Custom"],"required":true},{"name":"participants","type":"number","label":"Number of Participants"},{"name":"preferred_date","type":"date","label":"Preferred Date"},{"name":"format","type":"radio","label":"Training Format","options":["Online","In-Person","Recorded Video"]}]'),
('consultation_booking', 'Consultation Booking', 3, 'Book a consultation',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"company","type":"text","label":"Company Name"},{"name":"topic","type":"dropdown","label":"Consultation Topic","options":["Product Demo","Technical Setup","Business Solutions","Custom Integration","Other"],"required":true},{"name":"preferred_date","type":"date","label":"Preferred Date","required":true},{"name":"preferred_time","type":"dropdown","label":"Preferred Time","options":["9:00 AM","10:00 AM","11:00 AM","1:00 PM","2:00 PM","3:00 PM","4:00 PM"]},{"name":"questions","type":"textarea","label":"Questions/Topics to Discuss"}]'),
('appointment_scheduler', 'Appointment Scheduler', 3, 'Schedule appointments',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"appointment_type","type":"dropdown","label":"Appointment Type","options":["Initial Consultation","Follow-up","Technical Support","Training Session"],"required":true},{"name":"date","type":"date","label":"Preferred Date","required":true},{"name":"time","type":"dropdown","label":"Preferred Time","options":["9:00 AM","10:00 AM","11:00 AM","1:00 PM","2:00 PM","3:00 PM","4:00 PM"],"required":true},{"name":"notes","type":"textarea","label":"Notes"}]'),
('callback_request', 'Callback Request', 3, 'Request a phone callback',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"phone","type":"phone","label":"Phone Number","required":true},{"name":"email","type":"email","label":"Email"},{"name":"reason","type":"dropdown","label":"Callback Reason","options":["Sales Question","Support Issue","Billing Question","General Inquiry"],"required":true},{"name":"best_time","type":"dropdown","label":"Best Time to Call","options":["Morning (9-12)","Afternoon (12-5)","Evening (5-8)","Anytime"]},{"name":"notes","type":"textarea","label":"Brief Description of Your Question"}]'),
('chat_transcript', 'Live Chat Transcript', 3, 'Save chat conversations',
 '[{"name":"customer_name","type":"text","label":"Customer Name"},{"name":"customer_email","type":"email","label":"Customer Email"},{"name":"agent_name","type":"text","label":"Agent Name"},{"name":"chat_start","type":"datetime","label":"Chat Started"},{"name":"chat_end","type":"datetime","label":"Chat Ended"},{"name":"transcript","type":"textarea","label":"Chat Transcript"},{"name":"resolution","type":"dropdown","label":"Resolution Status","options":["Resolved","Escalated","Pending","Unresolved"]},{"name":"rating","type":"rating","label":"Customer Rating"}]'),

-- Marketing & Leads (10 forms)
('newsletter_signup', 'Newsletter Signup', 4, 'Email newsletter subscription',
 '[{"name":"email","type":"email","label":"Email Address","required":true},{"name":"first_name","type":"text","label":"First Name"},{"name":"interests","type":"checkbox","label":"Interests","options":["Product Updates","Tips & Tutorials","Industry News","Special Offers"]}]'),
('lead_capture', 'Lead Capture', 4, 'Capture sales leads',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Business Email","required":true},{"name":"phone","type":"phone","label":"Phone Number"},{"name":"company","type":"text","label":"Company Name","required":true},{"name":"job_title","type":"text","label":"Job Title"},{"name":"company_size","type":"dropdown","label":"Company Size","options":["1-10","11-50","51-200","201-500","500+"]},{"name":"interest","type":"dropdown","label":"Interest Area","options":["Personal VPN","Family Plan","Business Solution","Enterprise"],"required":true}]'),
('gated_content', 'Download/Gated Content', 4, 'Access gated resources',
 '[{"name":"email","type":"email","label":"Work Email","required":true},{"name":"name","type":"text","label":"Full Name","required":true},{"name":"company","type":"text","label":"Company"},{"name":"job_title","type":"text","label":"Job Title"},{"name":"consent","type":"checkbox","label":"I agree to receive marketing communications","required":true}]'),
('webinar_registration', 'Webinar Registration', 4, 'Register for webinars',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"company","type":"text","label":"Company Name"},{"name":"job_title","type":"text","label":"Job Title"},{"name":"questions","type":"textarea","label":"Questions for the presenter"},{"name":"reminder","type":"checkbox","label":"Send me a reminder before the webinar"}]'),
('event_registration', 'Event Registration', 4, 'Register for events',
 '[{"name":"name","type":"text","label":"Attendee Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"company","type":"text","label":"Company/Organization"},{"name":"dietary","type":"dropdown","label":"Dietary Restrictions","options":["None","Vegetarian","Vegan","Gluten-Free","Other"]},{"name":"t_shirt","type":"dropdown","label":"T-Shirt Size","options":["S","M","L","XL","XXL"]},{"name":"sessions","type":"checkbox","label":"Sessions to Attend","options":["Session A","Session B","Session C","All Sessions"]}]'),
('contest_entry', 'Contest Entry', 4, 'Enter contests/giveaways',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone Number"},{"name":"age_confirm","type":"checkbox","label":"I am 18 years or older","required":true},{"name":"rules_accept","type":"checkbox","label":"I accept the contest rules","required":true},{"name":"marketing_consent","type":"checkbox","label":"I consent to receive marketing emails"}]'),
('survey_form', 'Survey Form', 4, 'Multiple choice surveys',
 '[{"name":"q1","type":"radio","label":"How satisfied are you with our service?","options":["Very Satisfied","Satisfied","Neutral","Dissatisfied","Very Dissatisfied"],"required":true},{"name":"q2","type":"checkbox","label":"What features do you use most?","options":["VPN","Parental Controls","Camera Dashboard","Port Forwarding"]},{"name":"q3","type":"rating","label":"Rate your overall experience"},{"name":"q4","type":"textarea","label":"Any additional comments?"}]'),
('poll_form', 'Quick Poll', 4, 'Simple voting polls',
 '[{"name":"vote","type":"radio","label":"Cast your vote","required":true},{"name":"email","type":"email","label":"Email (optional, for results)"}]'),
('quiz_form', 'Quiz Form', 4, 'Scored quizzes',
 '[{"name":"name","type":"text","label":"Your Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"q1","type":"radio","label":"Question 1: What is...?","options":["A","B","C","D"],"required":true},{"name":"q2","type":"radio","label":"Question 2: Which...?","options":["A","B","C","D"],"required":true},{"name":"q3","type":"radio","label":"Question 3: How...?","options":["A","B","C","D"],"required":true}]'),
('referral_form', 'Referral Form', 4, 'Customer referral program',
 '[{"name":"your_name","type":"text","label":"Your Name","required":true},{"name":"your_email","type":"email","label":"Your Email","required":true},{"name":"friend_name","type":"text","label":"Friend''s Name","required":true},{"name":"friend_email","type":"email","label":"Friend''s Email","required":true},{"name":"message","type":"textarea","label":"Personal Message (optional)"}]'),

-- HR & Operations (10 forms)
('job_application', 'Job Application', 5, 'Employment applications',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"position","type":"dropdown","label":"Position Applied For","required":true},{"name":"resume","type":"file","label":"Resume/CV","file_types":"pdf,doc,docx","required":true},{"name":"cover_letter","type":"file","label":"Cover Letter","file_types":"pdf,doc,docx"},{"name":"linkedin","type":"url","label":"LinkedIn Profile"},{"name":"salary_expectation","type":"text","label":"Salary Expectation"},{"name":"start_date","type":"date","label":"Available Start Date"},{"name":"why_interested","type":"textarea","label":"Why are you interested in this position?"}]'),
('employee_onboarding', 'Employee Onboarding', 5, 'New hire paperwork',
 '[{"name":"full_name","type":"text","label":"Legal Full Name","required":true},{"name":"email","type":"email","label":"Personal Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"address","type":"textarea","label":"Home Address","required":true},{"name":"emergency_contact","type":"text","label":"Emergency Contact Name","required":true},{"name":"emergency_phone","type":"phone","label":"Emergency Contact Phone","required":true},{"name":"start_date","type":"date","label":"Start Date","required":true},{"name":"bank_name","type":"text","label":"Bank Name (for direct deposit)"},{"name":"shirt_size","type":"dropdown","label":"T-Shirt Size","options":["S","M","L","XL","XXL"]}]'),
('time_off_request', 'Time Off Request', 5, 'Request vacation/sick leave',
 '[{"name":"employee_name","type":"text","label":"Employee Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"leave_type","type":"dropdown","label":"Type of Leave","options":["Vacation","Sick Leave","Personal Day","Bereavement","Other"],"required":true},{"name":"start_date","type":"date","label":"Start Date","required":true},{"name":"end_date","type":"date","label":"End Date","required":true},{"name":"reason","type":"textarea","label":"Reason (optional)"},{"name":"coverage","type":"text","label":"Coverage Arranged By"}]'),
('expense_report', 'Expense Report', 5, 'Submit expense reimbursements',
 '[{"name":"employee_name","type":"text","label":"Employee Name","required":true},{"name":"department","type":"text","label":"Department"},{"name":"expense_date","type":"date","label":"Expense Date","required":true},{"name":"category","type":"dropdown","label":"Expense Category","options":["Travel","Meals","Office Supplies","Equipment","Software","Other"],"required":true},{"name":"amount","type":"currency","label":"Amount","required":true},{"name":"description","type":"textarea","label":"Description","required":true},{"name":"receipt","type":"file","label":"Receipt","file_types":"jpg,png,pdf","required":true}]'),
('vendor_application', 'Vendor Application', 5, 'Become a vendor/supplier',
 '[{"name":"company_name","type":"text","label":"Company Name","required":true},{"name":"contact_name","type":"text","label":"Contact Person","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"address","type":"textarea","label":"Business Address","required":true},{"name":"products_services","type":"textarea","label":"Products/Services Offered","required":true},{"name":"website","type":"url","label":"Website"},{"name":"years_in_business","type":"number","label":"Years in Business"},{"name":"references","type":"textarea","label":"Business References"}]'),
('partner_application', 'Partner Application', 5, 'Become a partner/reseller',
 '[{"name":"company_name","type":"text","label":"Company Name","required":true},{"name":"contact_name","type":"text","label":"Primary Contact","required":true},{"name":"email","type":"email","label":"Business Email","required":true},{"name":"phone","type":"phone","label":"Phone","required":true},{"name":"website","type":"url","label":"Company Website","required":true},{"name":"business_type","type":"dropdown","label":"Business Type","options":["Reseller","Consultant","Integrator","MSP","Other"],"required":true},{"name":"customer_base","type":"textarea","label":"Describe Your Customer Base"},{"name":"why_partner","type":"textarea","label":"Why do you want to partner with us?"}]'),
('nda_agreement', 'NDA Agreement', 5, 'Non-disclosure agreement',
 '[{"name":"party_name","type":"text","label":"Party Name/Company","required":true},{"name":"contact_name","type":"text","label":"Contact Person","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"address","type":"textarea","label":"Address","required":true},{"name":"purpose","type":"textarea","label":"Purpose of NDA","required":true},{"name":"signature","type":"signature","label":"Signature","required":true},{"name":"date","type":"date","label":"Date","required":true}]'),
('contact_update', 'Contact Information Update', 5, 'Update employee/contact info',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"phone","type":"phone","label":"Phone"},{"name":"address","type":"textarea","label":"Address"},{"name":"emergency_contact","type":"text","label":"Emergency Contact"},{"name":"emergency_phone","type":"phone","label":"Emergency Contact Phone"}]'),
('change_request', 'Change Request', 5, 'Request system/process changes',
 '[{"name":"requester","type":"text","label":"Requested By","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"department","type":"text","label":"Department"},{"name":"change_type","type":"dropdown","label":"Type of Change","options":["Process","System","Policy","Other"],"required":true},{"name":"current_state","type":"textarea","label":"Current State","required":true},{"name":"proposed_change","type":"textarea","label":"Proposed Change","required":true},{"name":"reason","type":"textarea","label":"Reason for Change","required":true},{"name":"impact","type":"textarea","label":"Expected Impact"},{"name":"priority","type":"dropdown","label":"Priority","options":["Low","Medium","High","Critical"]}]'),
('incident_report', 'Incident Report', 5, 'Report workplace incidents',
 '[{"name":"reporter_name","type":"text","label":"Reporter Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"incident_date","type":"datetime","label":"Date & Time of Incident","required":true},{"name":"location","type":"text","label":"Location","required":true},{"name":"incident_type","type":"dropdown","label":"Type of Incident","options":["Safety","Security","Equipment","Environmental","Other"],"required":true},{"name":"description","type":"textarea","label":"Incident Description","required":true},{"name":"injuries","type":"radio","label":"Were there any injuries?","options":["Yes","No"]},{"name":"witnesses","type":"textarea","label":"Witness Names"},{"name":"action_taken","type":"textarea","label":"Immediate Action Taken"}]'),

-- VPN-Specific (5 forms)
('vpn_account_setup', 'VPN Account Setup', 6, 'New VPN account request',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"plan","type":"dropdown","label":"Plan","options":["Personal ($9.99)","Family ($14.99)","Business ($29.99)"],"required":true},{"name":"devices","type":"number","label":"Number of Devices"},{"name":"primary_use","type":"dropdown","label":"Primary Use Case","options":["Privacy","Security","Streaming","Gaming","Business","Other"]}]'),
('server_change_request', 'Server Change Request', 6, 'Request server change',
 '[{"name":"email","type":"email","label":"Account Email","required":true},{"name":"current_server","type":"text","label":"Current Server"},{"name":"requested_server","type":"dropdown","label":"Requested Server","options":["New York","St. Louis (VIP)","Dallas","Toronto"],"required":true},{"name":"reason","type":"textarea","label":"Reason for Change"}]'),
('port_forwarding_request', 'Port Forwarding Request', 6, 'Request port forwarding',
 '[{"name":"email","type":"email","label":"Account Email","required":true},{"name":"internal_ip","type":"text","label":"Internal IP Address","required":true},{"name":"internal_port","type":"number","label":"Internal Port","required":true},{"name":"external_port","type":"number","label":"External Port (leave blank for auto)"},{"name":"protocol","type":"dropdown","label":"Protocol","options":["TCP","UDP","Both"],"required":true},{"name":"purpose","type":"dropdown","label":"Purpose","options":["Gaming","Camera/DVR","Web Server","Remote Desktop","Other"],"required":true},{"name":"description","type":"textarea","label":"Description"}]'),
('vip_access_request', 'VIP Access Request', 6, 'Request VIP status',
 '[{"name":"name","type":"text","label":"Full Name","required":true},{"name":"email","type":"email","label":"Email","required":true},{"name":"reason","type":"textarea","label":"Why are you requesting VIP access?","required":true},{"name":"referral","type":"text","label":"Referred by (if applicable)"}]'),
('network_scanner_report', 'Network Scanner Report', 6, 'Submit scanner results',
 '[{"name":"email","type":"email","label":"Account Email","required":true},{"name":"devices_found","type":"number","label":"Devices Found"},{"name":"cameras_found","type":"number","label":"Cameras Found"},{"name":"issues","type":"textarea","label":"Any Issues Encountered?"},{"name":"device_list","type":"textarea","label":"Device List (JSON)"}]');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_templates_category ON form_templates(category_id);
CREATE INDEX IF NOT EXISTS idx_forms_slug ON forms(form_slug);
CREATE INDEX IF NOT EXISTS idx_forms_active ON forms(is_active);
CREATE INDEX IF NOT EXISTS idx_submissions_form ON form_submissions(form_id);
CREATE INDEX IF NOT EXISTS idx_submissions_status ON form_submissions(status);
CREATE INDEX IF NOT EXISTS idx_submissions_date ON form_submissions(submitted_at);
```



## 2.8 NEW Database: campaigns.db (Marketing Automation)

```sql
-- =================================================================
-- DATABASE: campaigns.db  
-- PURPOSE: Email campaigns, social media, marketing automation
-- =================================================================

-- Email campaigns
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_uuid TEXT UNIQUE NOT NULL,
    campaign_name TEXT NOT NULL,
    template_id INTEGER,
    style TEXT NOT NULL DEFAULT 'business' CHECK(style IN ('casual', 'business', 'corporate')),
    subject_line TEXT NOT NULL,
    preview_text TEXT,
    from_name TEXT NOT NULL,
    from_email TEXT NOT NULL,
    reply_to TEXT,
    html_content TEXT NOT NULL,
    text_content TEXT,                      -- Plain text version
    segment_id INTEGER,                     -- Target customer segment
    status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled')),
    scheduled_at DATETIME,
    sent_at DATETIME,
    send_count INTEGER DEFAULT 0,
    open_count INTEGER DEFAULT 0,
    click_count INTEGER DEFAULT 0,
    unsubscribe_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (segment_id) REFERENCES customer_segments(id)
);

-- Customer segments for targeting
CREATE TABLE IF NOT EXISTS customer_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    segment_name TEXT NOT NULL UNIQUE,
    description TEXT,
    filter_rules TEXT NOT NULL,             -- JSON: {"status":"active","plan":"family"}
    customer_count INTEGER DEFAULT 0,
    is_dynamic INTEGER DEFAULT 1,           -- Auto-update count
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Email tracking (per recipient)
CREATE TABLE IF NOT EXISTS email_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    sent_at DATETIME,
    delivered_at DATETIME,
    opened_at DATETIME,
    open_count INTEGER DEFAULT 0,
    clicked_at DATETIME,
    click_count INTEGER DEFAULT 0,
    bounced_at DATETIME,
    bounce_type TEXT,                       -- 'hard', 'soft'
    unsubscribed_at DATETIME,
    complained_at DATETIME,                 -- Spam complaint
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE
);

-- Link tracking
CREATE TABLE IF NOT EXISTS link_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    original_url TEXT NOT NULL,
    tracking_code TEXT UNIQUE NOT NULL,     -- Short unique code
    click_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE
);

-- Individual link clicks
CREATE TABLE IF NOT EXISTS link_clicks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tracking_id INTEGER NOT NULL,
    customer_id INTEGER,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address TEXT,
    user_agent TEXT,
    referrer TEXT,
    FOREIGN KEY (tracking_id) REFERENCES link_tracking(id) ON DELETE CASCADE
);

-- Landing pages
CREATE TABLE IF NOT EXISTS landing_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_uuid TEXT UNIQUE NOT NULL,
    page_name TEXT NOT NULL,
    page_slug TEXT UNIQUE NOT NULL,         -- URL path
    template_id INTEGER,
    style TEXT NOT NULL DEFAULT 'business',
    html_content TEXT NOT NULL,
    meta_title TEXT,
    meta_description TEXT,
    is_active INTEGER DEFAULT 1,
    view_count INTEGER DEFAULT 0,
    conversion_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Email templates (reusable)
CREATE TABLE IF NOT EXISTS email_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_code TEXT UNIQUE NOT NULL,     -- 'welcome_basic', 'payment_receipt'
    template_name TEXT NOT NULL,
    category TEXT NOT NULL,                 -- 'welcome', 'transactional', 'promotional'
    style TEXT DEFAULT 'business',
    subject_template TEXT NOT NULL,
    html_template TEXT NOT NULL,
    text_template TEXT,
    variables TEXT,                         -- JSON: list of available variables
    is_active INTEGER DEFAULT 1,
    usage_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default customer segments
INSERT OR IGNORE INTO customer_segments (segment_name, description, filter_rules) VALUES
('all', 'All Customers', '{}'),
('active', 'Active Subscribers', '{"status":"active"}'),
('trial', 'Trial Users', '{"status":"trial"}'),
('inactive', 'Inactive (90+ days)', '{"last_login":"<90days"}'),
('personal', 'Personal Plan', '{"plan":"personal"}'),
('family', 'Family Plan', '{"plan":"family"}'),
('business', 'Business Plan', '{"plan":"business"}'),
('vip', 'VIP Customers', '{"is_vip":1}'),
('new_30', 'New Last 30 Days', '{"created":"<30days"}'),
('high_value', 'High Value (>$100)', '{"total_spent":">100"}');

-- Insert default email templates
INSERT OR IGNORE INTO email_templates (template_code, template_name, category, subject_template, html_template, variables) VALUES
('welcome_basic', 'Welcome Email (Basic)', 'welcome', 'Welcome to TrueVault VPN!', '<h1>Welcome, {first_name}!</h1><p>Thank you for joining TrueVault VPN.</p>', '["first_name","email","plan_name"]'),
('welcome_formal', 'Welcome Email (Formal)', 'welcome', 'Welcome to TrueVault VPN', '<h1>Welcome to TrueVault VPN</h1><p>Dear {first_name},</p><p>Thank you for choosing TrueVault VPN.</p>', '["first_name","email","plan_name"]'),
('payment_receipt', 'Payment Receipt', 'transactional', 'Receipt for your TrueVault VPN payment', '<h1>Payment Received</h1><p>Amount: ${amount}</p><p>Transaction ID: {transaction_id}</p>', '["first_name","amount","transaction_id","date"]'),
('subscription_renewal', 'Subscription Renewal', 'transactional', 'Your TrueVault VPN subscription has been renewed', '<h1>Subscription Renewed</h1><p>Your {plan_name} plan has been renewed.</p>', '["first_name","plan_name","amount","next_billing"]'),
('payment_failed', 'Payment Failed', 'transactional', 'Action Required: Payment Failed', '<h1>Payment Issue</h1><p>We were unable to process your payment.</p>', '["first_name","amount","retry_date"]'),
('promo_discount', 'Promotional Discount', 'promotional', 'Special Offer: Save {discount}% on TrueVault VPN!', '<h1>Limited Time Offer!</h1><p>Save {discount}% with code {coupon_code}</p>', '["first_name","discount","coupon_code","expiry_date"]');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_campaigns_status ON email_campaigns(status);
CREATE INDEX IF NOT EXISTS idx_campaigns_scheduled ON email_campaigns(scheduled_at);
CREATE INDEX IF NOT EXISTS idx_tracking_campaign ON email_tracking(campaign_id);
CREATE INDEX IF NOT EXISTS idx_tracking_customer ON email_tracking(customer_id);
CREATE INDEX IF NOT EXISTS idx_links_campaign ON link_tracking(campaign_id);
CREATE INDEX IF NOT EXISTS idx_clicks_tracking ON link_clicks(tracking_id);
```

## 2.9 NEW Database: calendar.db (365-Day Marketing Calendar)

```sql
-- =================================================================
-- DATABASE: calendar.db
-- PURPOSE: Pre-scheduled 365-day marketing automation
-- =================================================================

-- Marketing platforms (50+ FREE platforms)
CREATE TABLE IF NOT EXISTS marketing_platforms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_code TEXT UNIQUE NOT NULL,     -- 'facebook', 'twitter', 'openpr'
    platform_name TEXT NOT NULL,
    platform_type TEXT NOT NULL,            -- 'social', 'press', 'directory', 'classified'
    platform_url TEXT NOT NULL,
    api_available INTEGER DEFAULT 0,
    post_frequency TEXT,                    -- 'daily', 'weekly', 'monthly'
    max_post_length INTEGER,
    supports_images INTEGER DEFAULT 0,
    supports_links INTEGER DEFAULT 1,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Platform credentials
CREATE TABLE IF NOT EXISTS platform_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_id INTEGER NOT NULL,
    api_key TEXT,
    api_secret TEXT,
    access_token TEXT,
    refresh_token TEXT,
    page_id TEXT,                           -- Facebook page ID, etc.
    expires_at DATETIME,
    is_connected INTEGER DEFAULT 0,
    last_used DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES marketing_platforms(id) ON DELETE CASCADE
);

-- Scheduled posts (365-day calendar)
CREATE TABLE IF NOT EXISTS scheduled_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_uuid TEXT UNIQUE NOT NULL,
    platform_id INTEGER NOT NULL,
    post_date DATE NOT NULL,
    post_time TIME NOT NULL,
    post_type TEXT NOT NULL,                -- 'promotional', 'educational', 'testimonial', 'tip'
    content_template TEXT NOT NULL,
    content_rendered TEXT,                  -- With variables replaced
    image_url TEXT,
    link_url TEXT,
    tracking_link TEXT,                     -- With UTM parameters
    current_price DECIMAL(10,2),            -- Price at time of posting
    hashtags TEXT,                          -- JSON array
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'posted', 'failed', 'skipped', 'cancelled')),
    posted_at DATETIME,
    post_id_external TEXT,                  -- ID from platform
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    views INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES marketing_platforms(id)
);

-- Holiday/seasonal campaigns
CREATE TABLE IF NOT EXISTS holiday_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_code TEXT UNIQUE NOT NULL,     -- 'new_year_2026', 'black_friday_2026'
    campaign_name TEXT NOT NULL,
    holiday_name TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    theme TEXT,                             -- 'fresh_start', 'protect_family'
    discount_percent INTEGER,
    special_price DECIMAL(10,2),
    posts_count INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Marketing performance tracking
CREATE TABLE IF NOT EXISTS marketing_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    date DATE NOT NULL,
    views INTEGER DEFAULT 0,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,           -- For paid ads (always 0 for free)
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES scheduled_posts(id) ON DELETE CASCADE
);

-- Price schedule (for dynamic pricing)
CREATE TABLE IF NOT EXISTS price_schedule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    personal_price DECIMAL(10,2) NOT NULL,
    family_price DECIMAL(10,2) NOT NULL,
    business_price DECIMAL(10,2) NOT NULL,
    promo_code TEXT,
    promo_message TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert ALL 50+ marketing platforms
INSERT OR IGNORE INTO marketing_platforms (platform_code, platform_name, platform_type, platform_url, api_available, post_frequency, supports_images, notes) VALUES
-- Social Media (10)
('facebook', 'Facebook', 'social', 'https://www.facebook.com/business', 1, 'daily', 1, 'Business page required'),
('twitter', 'Twitter/X', 'social', 'https://twitter.com', 1, 'daily', 1, 'API v2 required'),
('linkedin', 'LinkedIn', 'social', 'https://www.linkedin.com/company/setup', 1, 'weekly', 1, 'Company page required'),
('pinterest', 'Pinterest', 'social', 'https://business.pinterest.com', 1, 'daily', 1, 'Visual content focus'),
('instagram', 'Instagram', 'social', 'https://business.instagram.com', 0, 'daily', 1, 'Meta approval needed'),
('tiktok', 'TikTok', 'social', 'https://www.tiktok.com/business', 0, 'weekly', 1, 'Video content'),
('youtube', 'YouTube', 'social', 'https://www.youtube.com/create_channel', 1, 'weekly', 1, 'Video uploads'),
('reddit', 'Reddit', 'social', 'https://www.reddit.com', 1, 'weekly', 0, 'r/VPN r/privacy communities'),
('quora', 'Quora', 'social', 'https://www.quora.com', 0, 'weekly', 0, 'Answer questions'),
('medium', 'Medium', 'social', 'https://medium.com', 1, 'monthly', 1, 'Long-form articles'),

-- Press Release Sites (10)
('pr_247', '24-7 Press Release', 'press', 'https://www.24-7pressrelease.com', 0, 'weekly', 0, 'Free tier available'),
('prcom', 'PR.com', 'press', 'https://www.pr.com', 0, 'weekly', 0, '30,000+ journalists'),
('openpr', 'OpenPR', 'press', 'https://www.openpr.com', 0, 'weekly', 0, 'Unlimited free releases'),
('prlog', 'PRLog', 'press', 'https://www.prlog.org', 0, 'weekly', 0, 'Google News indexed'),
('freepr', 'Free Press Release', 'press', 'https://www.free-press-release.com', 0, 'weekly', 0, 'Basic distribution'),
('pr1888', '1888 Press Release', 'press', 'https://www.1888pressrelease.com', 0, 'weekly', 0, 'Free plan'),
('prfree', 'PRFree', 'press', 'https://www.prfree.com', 0, 'weekly', 0, 'Global distribution'),
('onlinepr', 'Online PR News', 'press', 'https://www.onlineprnews.com', 0, 'weekly', 0, 'Search engine focus'),
('prpoint', 'Press Release Point', 'press', 'https://www.pressreleasepoint.com', 0, 'weekly', 0, 'News aggregators'),
('inewswire', 'I-Newswire', 'press', 'https://www.i-newswire.com', 0, 'weekly', 0, 'Global reach'),

-- Classified Sites (5)
('craigslist', 'Craigslist', 'classified', 'https://www.craigslist.org', 0, 'weekly', 0, '700+ cities'),
('gumtree', 'Gumtree', 'classified', 'https://www.gumtree.com', 0, 'weekly', 0, 'UK/Australia'),
('oodle', 'Oodle', 'classified', 'https://www.oodle.com', 0, 'weekly', 0, 'Facebook sync'),
('classifiedads', 'ClassifiedAds', 'classified', 'https://www.classifiedads.com', 0, 'weekly', 0, 'Unlimited free'),
('locanto', 'Locanto', 'classified', 'https://www.locanto.com', 0, 'weekly', 0, '60+ countries'),

-- Business Directories (10)
('google_business', 'Google Business Profile', 'directory', 'https://business.google.com', 1, 'weekly', 1, 'Essential for local SEO'),
('yelp', 'Yelp', 'directory', 'https://biz.yelp.com', 0, 'monthly', 1, 'Reviews important'),
('yellowpages', 'Yellow Pages', 'directory', 'https://www.yellowpages.com', 0, 'monthly', 0, 'Basic listing free'),
('bing_places', 'Bing Places', 'directory', 'https://www.bingplaces.com', 0, 'monthly', 0, 'Bing search visibility'),
('apple_maps', 'Apple Maps Connect', 'directory', 'https://mapsconnect.apple.com', 0, 'monthly', 0, 'iOS users'),
('manta', 'Manta', 'directory', 'https://www.manta.com', 0, 'monthly', 0, 'B2B directory'),
('merchantcircle', 'Merchant Circle', 'directory', 'https://www.merchantcircle.com', 0, 'monthly', 0, 'Local business'),
('hotfrog', 'Hotfrog', 'directory', 'https://www.hotfrog.com', 0, 'monthly', 0, 'International'),
('cylex', 'Cylex', 'directory', 'https://www.cylex.us.com', 0, 'monthly', 0, 'Local listings'),
('tupalo', 'Tupalo', 'directory', 'https://tupalo.com', 0, 'monthly', 0, 'Location-based'),

-- Tech Directories (5)
('producthunt', 'Product Hunt', 'directory', 'https://www.producthunt.com', 0, 'once', 1, 'Launch platform'),
('alternativeto', 'AlternativeTo', 'directory', 'https://alternativeto.net', 0, 'once', 0, 'List as NordVPN alternative'),
('capterra', 'Capterra', 'directory', 'https://www.capterra.com', 0, 'once', 0, 'Software directory'),
('g2', 'G2', 'directory', 'https://www.g2.com', 0, 'once', 0, 'B2B software reviews'),
('saashub', 'SaaSHub', 'directory', 'https://www.saashub.com', 0, 'once', 0, 'SaaS alternatives'),

-- Forums (5)
('webhostingtalk', 'WebHostingTalk', 'forum', 'https://www.webhostingtalk.com', 0, 'weekly', 0, 'VPN/Security section'),
('warriorforum', 'Warrior Forum', 'forum', 'https://www.warriorforum.com', 0, 'weekly', 0, 'Marketing community'),
('blackhatworld', 'BlackHatWorld', 'forum', 'https://www.blackhatworld.com', 0, 'weekly', 0, 'Tools section'),
('digitalpoint', 'Digital Point', 'forum', 'https://www.digitalpoint.com', 0, 'weekly', 0, 'Business forum'),
('v7n', 'V7N Forum', 'forum', 'https://www.v7n.com', 0, 'weekly', 0, 'Web development'),

-- Content Platforms (4)
('linkedin_articles', 'LinkedIn Articles', 'content', 'https://www.linkedin.com/pulse', 0, 'monthly', 1, 'Professional audience'),
('blogger', 'Blogger', 'content', 'https://www.blogger.com', 0, 'monthly', 1, 'Google-owned'),
('wordpress', 'WordPress.com', 'content', 'https://wordpress.com', 0, 'monthly', 1, 'Free tier'),
('substack', 'Substack', 'content', 'https://substack.com', 0, 'monthly', 1, 'Newsletter platform'),

-- Deal Sites (3)
('retailmenot', 'RetailMeNot', 'deals', 'https://www.retailmenot.com/submit', 0, 'monthly', 0, 'Coupon submission'),
('slickdeals', 'Slickdeals', 'deals', 'https://slickdeals.net', 0, 'monthly', 0, 'Community-voted'),
('dealnews', 'DealNews', 'deals', 'https://www.dealnews.com', 0, 'monthly', 0, 'Deal aggregator');

-- Insert holiday campaigns for 2026
INSERT OR IGNORE INTO holiday_campaigns (campaign_code, campaign_name, holiday_name, start_date, end_date, theme, discount_percent, special_price) VALUES
('new_year_2026', 'New Year 2026', 'New Year', '2026-01-01', '2026-01-07', 'Fresh Start, New Security', 0, 9.99),
('valentines_2026', 'Valentine 2026', 'Valentine''s Day', '2026-02-10', '2026-02-17', 'Protect What You Love', 20, 7.99),
('stpatricks_2026', 'St Patrick 2026', 'St. Patrick''s Day', '2026-03-14', '2026-03-20', 'Get Lucky with Privacy', 10, 8.99),
('easter_2026', 'Easter 2026', 'Easter', '2026-04-01', '2026-04-06', 'Spring Clean Your Digital Life', 20, 7.99),
('memorial_2026', 'Memorial Day 2026', 'Memorial Day', '2026-05-22', '2026-05-28', 'American-Made Security', 10, 8.99),
('july4_2026', '4th of July 2026', 'Independence Day', '2026-06-28', '2026-07-06', 'Independence & Freedom', 20, 7.99),
('backtoschool_2026', 'Back to School 2026', 'Back to School', '2026-08-15', '2026-09-05', 'Protect Kids Online', 0, 9.99),
('labor_2026', 'Labor Day 2026', 'Labor Day', '2026-08-31', '2026-09-08', 'Work From Home Security', 10, 8.99),
('halloween_2026', 'Halloween 2026', 'Halloween', '2026-10-25', '2026-11-01', 'Scary Internet Threats', 0, 9.99),
('thanksgiving_2026', 'Thanksgiving 2026', 'Thanksgiving', '2026-11-23', '2026-11-27', 'Gratitude Sale', 30, 6.99),
('blackfriday_2026', 'Black Friday 2026', 'Black Friday', '2026-11-27', '2026-12-02', 'Biggest Sale of the Year', 50, 4.99),
('christmas_2026', 'Christmas 2026', 'Christmas', '2026-12-15', '2026-12-31', 'Gift of Privacy', 50, 4.99);

-- Insert default price schedule
INSERT OR IGNORE INTO price_schedule (start_date, end_date, personal_price, family_price, business_price, promo_message) VALUES
('2026-01-01', '2026-02-09', 9.99, 14.99, 29.99, 'Standard pricing'),
('2026-02-10', '2026-02-17', 7.99, 12.99, 24.99, 'Valentine''s Day Special!'),
('2026-02-18', '2026-11-26', 9.99, 14.99, 29.99, 'Standard pricing'),
('2026-11-27', '2026-12-02', 4.99, 9.99, 19.99, 'Black Friday - 50% OFF!'),
('2026-12-03', '2026-12-14', 9.99, 14.99, 29.99, 'Standard pricing'),
('2026-12-15', '2026-12-31', 4.99, 9.99, 19.99, 'Holiday Special - 50% OFF!');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_posts_date ON scheduled_posts(post_date);
CREATE INDEX IF NOT EXISTS idx_posts_platform ON scheduled_posts(platform_id);
CREATE INDEX IF NOT EXISTS idx_posts_status ON scheduled_posts(status);
CREATE INDEX IF NOT EXISTS idx_performance_post ON marketing_performance(post_id);
CREATE INDEX IF NOT EXISTS idx_performance_date ON marketing_performance(date);
```

## 2.10 NEW Database: tutorials.db (Tutorial System)

```sql
-- =================================================================
-- DATABASE: tutorials.db
-- PURPOSE: Interactive tutorial system for non-technical users
-- =================================================================

-- Tutorial categories
CREATE TABLE IF NOT EXISTS tutorial_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_code TEXT UNIQUE NOT NULL,
    category_name TEXT NOT NULL,
    description TEXT,
    icon TEXT DEFAULT '📚',
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1
);

-- Tutorial lessons
CREATE TABLE IF NOT EXISTS tutorials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tutorial_code TEXT UNIQUE NOT NULL,
    tutorial_name TEXT NOT NULL,
    category_id INTEGER NOT NULL,
    description TEXT,
    difficulty TEXT DEFAULT 'beginner' CHECK(difficulty IN ('beginner', 'intermediate', 'advanced')),
    estimated_minutes INTEGER DEFAULT 5,
    steps_json TEXT NOT NULL,               -- JSON array of step objects
    video_url TEXT,
    prerequisites TEXT,                     -- JSON array of tutorial_codes
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    completed_count INTEGER DEFAULT 0,
    avg_rating DECIMAL(3,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES tutorial_categories(id)
);

-- User tutorial progress
CREATE TABLE IF NOT EXISTS user_tutorial_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tutorial_id INTEGER NOT NULL,
    current_step INTEGER DEFAULT 0,
    completed INTEGER DEFAULT 0,
    score INTEGER,                          -- For quizzes
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    UNIQUE(user_id, tutorial_id)
);

-- Tutorial ratings & feedback
CREATE TABLE IF NOT EXISTS tutorial_feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tutorial_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER CHECK(rating BETWEEN 1 AND 5),
    helpful INTEGER,                        -- 1 = yes, 0 = no
    feedback_text TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    UNIQUE(tutorial_id, user_id)
);

-- Help tooltips (context-sensitive)
CREATE TABLE IF NOT EXISTS help_tooltips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    element_selector TEXT UNIQUE NOT NULL,  -- CSS selector or ID
    page_context TEXT,                      -- Which page
    tooltip_title TEXT NOT NULL,
    tooltip_content TEXT NOT NULL,
    learn_more_link TEXT,                   -- Link to full tutorial
    is_active INTEGER DEFAULT 1
);

-- Insert tutorial categories
INSERT OR IGNORE INTO tutorial_categories (category_code, category_name, description, icon, sort_order) VALUES
('getting_started', 'Getting Started', 'Essential first steps for new users', '🚀', 1),
('database_builder', 'Database Builder', 'Learn to create custom databases', '🗄️', 2),
('form_builder', 'Form Builder', 'Create and customize forms', '📝', 3),
('marketing', 'Marketing Automation', 'Master the marketing calendar', '📢', 4),
('advanced', 'Advanced Features', 'Power user features', '⚡', 5);

-- Insert ALL 35 tutorials
INSERT OR IGNORE INTO tutorials (tutorial_code, tutorial_name, category_id, description, difficulty, estimated_minutes, steps_json) VALUES
-- Getting Started (5 tutorials)
('gs_01_databases', 'Understanding Databases', 1, 'What is a database? Learn the basics in plain English.', 'beginner', 5,
 '[{"step":1,"title":"What is a Database?","content":"Think of a database like a super-organized spreadsheet...","action":"read"},{"step":2,"title":"What is a Table?","content":"A table is like one sheet in a spreadsheet...","action":"read"},{"step":3,"title":"What is a Field?","content":"Fields are like column headers...","action":"read"},{"step":4,"title":"Quick Quiz","content":"Let''s check your understanding!","action":"quiz","questions":[{"q":"A database is like a...","options":["Spreadsheet","Word document","Picture"],"correct":0}]}]'),
('gs_02_first_table', 'Your First Table', 1, 'Create a simple contacts table step-by-step.', 'beginner', 10,
 '[{"step":1,"title":"Click New Table","content":"Find the New Table button and click it.","action":"click","target":"#new-table-btn"},{"step":2,"title":"Name Your Table","content":"Type ''contacts'' as the table name.","action":"input","target":"#table-name"},{"step":3,"title":"Add Name Field","content":"Click Add Field, select Text, name it ''name''.","action":"guided"},{"step":4,"title":"Add Email Field","content":"Add another field for email addresses.","action":"guided"},{"step":5,"title":"Save","content":"Click Save to create your table!","action":"click","target":"#save-btn"}]'),
('gs_03_first_form', 'Your First Form', 1, 'Launch a contact form in under 5 minutes.', 'beginner', 5,
 '[{"step":1,"title":"Open Form Library","content":"Go to Forms > Form Library","action":"navigate"},{"step":2,"title":"Choose Template","content":"Find ''Contact Us'' and click Use Template","action":"select"},{"step":3,"title":"Pick Style","content":"Choose Casual, Business, or Corporate","action":"select"},{"step":4,"title":"Activate Form","content":"Click Activate to make it live!","action":"click"}]'),
('gs_04_first_campaign', 'Your First Email Campaign', 1, 'Send your first marketing email.', 'beginner', 15,
 '[{"step":1,"title":"Create Campaign","content":"Go to Marketing > New Campaign","action":"navigate"},{"step":2,"title":"Choose Template","content":"Pick a promotional email template","action":"select"},{"step":3,"title":"Edit Content","content":"Customize the message for your audience","action":"edit"},{"step":4,"title":"Select Recipients","content":"Choose which customers to send to","action":"select"},{"step":5,"title":"Schedule or Send","content":"Pick a time or send now!","action":"click"}]'),
('gs_05_tracking', 'Understanding Analytics', 1, 'Learn to read your marketing performance.', 'beginner', 10,
 '[{"step":1,"title":"What is an ''Open''?","content":"When someone views your email, that''s an open.","action":"read"},{"step":2,"title":"What is a ''Click''?","content":"When someone clicks a link in your email.","action":"read"},{"step":3,"title":"Conversion Rate","content":"Percentage of people who took action.","action":"read"},{"step":4,"title":"Reading Reports","content":"Let''s look at a real report together.","action":"guided"}]'),

-- Database Builder (10 tutorials)
('db_01_field_types', 'Field Types Explained', 2, 'All 15 field types and when to use them.', 'beginner', 15,
 '[{"step":1,"title":"Text Fields","content":"For names, titles, short answers.","action":"read"},{"step":2,"title":"Number Fields","content":"For quantities, ages, amounts.","action":"read"},{"step":3,"title":"Date Fields","content":"For birthdays, deadlines, events.","action":"read"},{"step":4,"title":"Dropdown Fields","content":"For choosing from a list.","action":"read"},{"step":5,"title":"Practice","content":"Let''s add each type to a test table.","action":"guided"}]'),
('db_02_validation', 'Adding Validation Rules', 2, 'Ensure data quality with validation.', 'intermediate', 10,
 '[{"step":1,"title":"Why Validate?","content":"Prevent bad data from entering your database.","action":"read"},{"step":2,"title":"Required Fields","content":"Make fields mandatory.","action":"guided"},{"step":3,"title":"Min/Max Length","content":"Limit text length.","action":"guided"},{"step":4,"title":"Email Validation","content":"Ensure valid email format.","action":"guided"}]'),
('db_03_relationships', 'Creating Relationships', 2, 'Connect tables together.', 'intermediate', 15,
 '[{"step":1,"title":"What are Relationships?","content":"Linking related data between tables.","action":"read"},{"step":2,"title":"One-to-Many","content":"One customer, many orders.","action":"read"},{"step":3,"title":"Visual Builder","content":"Draw lines between tables.","action":"guided"},{"step":4,"title":"Practice","content":"Link customers to orders.","action":"guided"}]'),
('db_04_import_data', 'Importing Data from Excel', 2, 'Bring in existing data from spreadsheets.', 'beginner', 10,
 '[{"step":1,"title":"Prepare Your File","content":"Save as CSV or keep as XLSX.","action":"read"},{"step":2,"title":"Click Import","content":"Find the Import button.","action":"click"},{"step":3,"title":"Map Columns","content":"Match spreadsheet columns to database fields.","action":"guided"},{"step":4,"title":"Review & Import","content":"Preview and confirm the import.","action":"guided"}]'),
('db_05_export_data', 'Exporting Reports', 2, 'Get your data out for analysis.', 'beginner', 5,
 '[{"step":1,"title":"Select Data","content":"Choose what to export.","action":"guided"},{"step":2,"title":"Choose Format","content":"CSV for spreadsheets, JSON for developers.","action":"select"},{"step":3,"title":"Download","content":"Click Export to download.","action":"click"}]'),
('db_06_search_filter', 'Searching and Filtering', 2, 'Find exactly what you need.', 'beginner', 10,
 '[{"step":1,"title":"Quick Search","content":"Type to search across all fields.","action":"guided"},{"step":2,"title":"Advanced Filters","content":"Filter by specific criteria.","action":"guided"},{"step":3,"title":"Save Filters","content":"Save frequently used searches.","action":"guided"}]'),
('db_07_bulk_operations', 'Bulk Operations', 2, 'Update or delete many records at once.', 'intermediate', 10,
 '[{"step":1,"title":"Select Multiple","content":"Use checkboxes to select records.","action":"guided"},{"step":2,"title":"Bulk Edit","content":"Change a field for all selected.","action":"guided"},{"step":3,"title":"Bulk Delete","content":"Remove multiple records safely.","action":"guided"}]'),
('db_08_backup', 'Backing Up Your Data', 2, 'Protect your valuable data.', 'beginner', 5,
 '[{"step":1,"title":"Why Backup?","content":"Accidents happen. Always have a backup.","action":"read"},{"step":2,"title":"Create Backup","content":"Click Backup in Settings.","action":"click"},{"step":3,"title":"Download","content":"Save the backup file safely.","action":"guided"}]'),
('db_09_restore', 'Restoring from Backup', 2, 'Recover data when needed.', 'intermediate', 10,
 '[{"step":1,"title":"Upload Backup","content":"Go to Settings > Restore.","action":"navigate"},{"step":2,"title":"Select File","content":"Choose your backup file.","action":"guided"},{"step":3,"title":"Confirm Restore","content":"Review and confirm the restoration.","action":"guided"}]'),
('db_10_best_practices', 'Database Best Practices', 2, 'Pro tips for organizing data.', 'advanced', 15,
 '[{"step":1,"title":"Naming Conventions","content":"Use clear, consistent names.","action":"read"},{"step":2,"title":"Data Normalization","content":"Avoid duplicate data.","action":"read"},{"step":3,"title":"Index Important Fields","content":"Speed up searches.","action":"read"},{"step":4,"title":"Regular Maintenance","content":"Keep your database healthy.","action":"read"}]'),

-- Form Builder (10 tutorials)
('fb_01_choose_template', 'Choosing the Right Template', 3, 'Find the perfect starting point.', 'beginner', 5,
 '[{"step":1,"title":"Browse Categories","content":"Forms organized by purpose.","action":"navigate"},{"step":2,"title":"Preview Templates","content":"See how they look before choosing.","action":"guided"},{"step":3,"title":"Use Template","content":"Start with a template and customize.","action":"click"}]'),
('fb_02_customize_fields', 'Customizing Form Fields', 3, 'Make the form your own.', 'beginner', 10,
 '[{"step":1,"title":"Add Fields","content":"Drag fields from the library.","action":"guided"},{"step":2,"title":"Edit Properties","content":"Change labels, help text, requirements.","action":"guided"},{"step":3,"title":"Reorder Fields","content":"Drag to rearrange.","action":"guided"},{"step":4,"title":"Remove Fields","content":"Delete what you don''t need.","action":"guided"}]'),
('fb_03_conditional_logic', 'Adding Conditional Logic', 3, 'Show/hide fields based on answers.', 'intermediate', 15,
 '[{"step":1,"title":"What is Conditional Logic?","content":"Make forms dynamic and smart.","action":"read"},{"step":2,"title":"Add Condition","content":"Click the logic icon on a field.","action":"click"},{"step":3,"title":"Set Rules","content":"Define when to show/hide.","action":"guided"},{"step":4,"title":"Test It","content":"Preview to see it in action.","action":"guided"}]'),
('fb_04_multipage', 'Creating Multi-Page Forms', 3, 'Break long forms into steps.', 'intermediate', 10,
 '[{"step":1,"title":"Add Page Break","content":"Split form into sections.","action":"guided"},{"step":2,"title":"Page Navigation","content":"Add progress indicators.","action":"guided"},{"step":3,"title":"Save Progress","content":"Let users resume later.","action":"guided"}]'),
('fb_05_notifications', 'Setting Up Email Notifications', 3, 'Get notified when forms are submitted.', 'beginner', 10,
 '[{"step":1,"title":"Open Settings","content":"Go to Form Settings.","action":"navigate"},{"step":2,"title":"Add Notification","content":"Enter email address.","action":"input"},{"step":3,"title":"Customize Message","content":"Change the notification content.","action":"guided"},{"step":4,"title":"Test It","content":"Submit a test entry.","action":"guided"}]'),
('fb_06_billing_connect', 'Connecting Forms to Billing', 3, 'Accept payments through forms.', 'intermediate', 15,
 '[{"step":1,"title":"Add Payment Field","content":"Drag the payment field onto your form.","action":"guided"},{"step":2,"title":"Connect PayPal","content":"Link your PayPal account.","action":"guided"},{"step":3,"title":"Set Pricing","content":"Configure the amount.","action":"guided"},{"step":4,"title":"Test Payment","content":"Do a test transaction.","action":"guided"}]'),
('fb_07_embed', 'Embedding Forms on Your Website', 3, 'Put forms on any webpage.', 'beginner', 5,
 '[{"step":1,"title":"Get Embed Code","content":"Click Share > Embed.","action":"click"},{"step":2,"title":"Copy Code","content":"Copy the HTML snippet.","action":"guided"},{"step":3,"title":"Paste on Website","content":"Add to your webpage.","action":"guided"}]'),
('fb_08_submissions', 'Analyzing Form Submissions', 3, 'Review and manage responses.', 'beginner', 10,
 '[{"step":1,"title":"View Submissions","content":"Go to Forms > Submissions.","action":"navigate"},{"step":2,"title":"Filter Results","content":"Find specific entries.","action":"guided"},{"step":3,"title":"Export Data","content":"Download for analysis.","action":"guided"},{"step":4,"title":"Respond to Submissions","content":"Mark as handled, add notes.","action":"guided"}]'),
('fb_09_design_tips', 'Form Design Best Practices', 3, 'Create forms people want to fill out.', 'intermediate', 10,
 '[{"step":1,"title":"Keep It Short","content":"Only ask what you need.","action":"read"},{"step":2,"title":"Logical Order","content":"Flow naturally from simple to complex.","action":"read"},{"step":3,"title":"Mobile-Friendly","content":"Test on phones!","action":"read"},{"step":4,"title":"Clear Labels","content":"Users should never be confused.","action":"read"}]'),
('fb_10_security', 'Form Security & Privacy', 3, 'Protect sensitive information.', 'advanced', 10,
 '[{"step":1,"title":"SSL Encryption","content":"All forms use HTTPS automatically.","action":"read"},{"step":2,"title":"Data Retention","content":"How long to keep submissions.","action":"guided"},{"step":3,"title":"GDPR Compliance","content":"Privacy requirements explained.","action":"read"},{"step":4,"title":"Spam Prevention","content":"Block bot submissions.","action":"guided"}]'),

-- Marketing (10 tutorials)
('mk_01_first_campaign', 'Building Your First Campaign', 4, 'Send a marketing email today.', 'beginner', 15,
 '[{"step":1,"title":"Start Campaign","content":"Click New Campaign.","action":"click"},{"step":2,"title":"Choose Template","content":"Pick from 30+ templates.","action":"select"},{"step":3,"title":"Edit Content","content":"Replace placeholder text.","action":"edit"},{"step":4,"title":"Select Recipients","content":"Choose your audience.","action":"select"},{"step":5,"title":"Send","content":"Review and send!","action":"click"}]'),
('mk_02_segments', 'Creating Customer Segments', 4, 'Target the right people.', 'intermediate', 10,
 '[{"step":1,"title":"What are Segments?","content":"Groups of customers with something in common.","action":"read"},{"step":2,"title":"Create Segment","content":"Define your criteria.","action":"guided"},{"step":3,"title":"Use in Campaign","content":"Target your segment.","action":"guided"}]'),
('mk_03_email_design', 'Email Design Best Practices', 4, 'Create emails people want to read.', 'intermediate', 15,
 '[{"step":1,"title":"Subject Lines","content":"The most important part!","action":"read"},{"step":2,"title":"Preview Text","content":"The snippet people see first.","action":"read"},{"step":3,"title":"Layout","content":"Keep it scannable.","action":"read"},{"step":4,"title":"Call to Action","content":"Make it obvious what to do.","action":"read"}]'),
('mk_04_subject_lines', 'Writing Compelling Subject Lines', 4, 'Get your emails opened.', 'beginner', 10,
 '[{"step":1,"title":"Keep It Short","content":"Under 50 characters ideal.","action":"read"},{"step":2,"title":"Create Urgency","content":"Limited time offers work.","action":"read"},{"step":3,"title":"Be Specific","content":"Tell them what''s inside.","action":"read"},{"step":4,"title":"A/B Test","content":"Try two versions!","action":"guided"}]'),
('mk_05_timing', 'Timing Your Campaigns', 4, 'Send at the perfect moment.', 'intermediate', 10,
 '[{"step":1,"title":"Best Days","content":"Tuesday-Thursday generally best.","action":"read"},{"step":2,"title":"Best Times","content":"Morning (9-11am) or afternoon (1-3pm).","action":"read"},{"step":3,"title":"Time Zones","content":"Send based on recipient''s time zone.","action":"guided"},{"step":4,"title":"Testing","content":"Find what works for YOUR audience.","action":"guided"}]'),
('mk_06_analytics', 'Reading Campaign Analytics', 4, 'Understand your results.', 'beginner', 15,
 '[{"step":1,"title":"Open Rate","content":"Percentage who opened email.","action":"read"},{"step":2,"title":"Click Rate","content":"Percentage who clicked a link.","action":"read"},{"step":3,"title":"Conversion Rate","content":"Percentage who took action.","action":"read"},{"step":4,"title":"Unsubscribe Rate","content":"Keep this below 0.5%.","action":"read"},{"step":5,"title":"Benchmarks","content":"How do you compare?","action":"read"}]'),
('mk_07_ab_testing', 'A/B Testing Explained', 4, 'Find what works best.', 'advanced', 15,
 '[{"step":1,"title":"What is A/B Testing?","content":"Compare two versions to find the winner.","action":"read"},{"step":2,"title":"Create Test","content":"Set up an A/B test.","action":"guided"},{"step":3,"title":"Wait for Results","content":"Let it run until statistically significant.","action":"read"},{"step":4,"title":"Analyze Winners","content":"Understand why one won.","action":"guided"}]'),
('mk_08_landing_pages', 'Building Landing Pages', 4, 'Create pages that convert.', 'intermediate', 20,
 '[{"step":1,"title":"What''s a Landing Page?","content":"A focused page for one goal.","action":"read"},{"step":2,"title":"Choose Template","content":"Start with a proven design.","action":"select"},{"step":3,"title":"Add Content","content":"Headline, benefits, call to action.","action":"edit"},{"step":4,"title":"Add Form","content":"Capture leads or sales.","action":"guided"},{"step":5,"title":"Publish","content":"Make it live!","action":"click"}]'),
('mk_09_automation', 'Creating Automated Sequences', 4, 'Set up emails that send themselves.', 'advanced', 20,
 '[{"step":1,"title":"What is Automation?","content":"Emails triggered by actions.","action":"read"},{"step":2,"title":"Welcome Sequence","content":"Greet new customers automatically.","action":"guided"},{"step":3,"title":"Set Triggers","content":"Define what starts the sequence.","action":"guided"},{"step":4,"title":"Add Delays","content":"Time between emails.","action":"guided"},{"step":5,"title":"Activate","content":"Turn on the automation.","action":"click"}]'),
('mk_10_calendar', 'Using the Marketing Calendar', 4, 'Manage your 365-day plan.', 'beginner', 10,
 '[{"step":1,"title":"Calendar View","content":"See all scheduled posts.","action":"navigate"},{"step":2,"title":"Edit Posts","content":"Click to modify any post.","action":"guided"},{"step":3,"title":"Change Prices","content":"Update pricing easily.","action":"guided"},{"step":4,"title":"Activate Calendar","content":"Start automated posting!","action":"click"}]');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_tutorials_category ON tutorials(category_id);
CREATE INDEX IF NOT EXISTS idx_tutorials_difficulty ON tutorials(difficulty);
CREATE INDEX IF NOT EXISTS idx_progress_user ON user_tutorial_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_progress_tutorial ON user_tutorial_progress(tutorial_id);
CREATE INDEX IF NOT EXISTS idx_tooltips_page ON help_tooltips(page_context);
```

---



# SECTION 16: DATABASE BUILDER

**NEW FEATURE - Never existed before in any VPN service**

The Database Builder is a visual, drag-and-drop interface that lets NON-TECHNICAL users create custom databases without writing any code. Think FileMaker Pro, but simpler and built into your VPN admin console.

## 16.1 Overview

**Purpose:** Allow the business owner (or buyer) to create custom databases for tracking anything they need - customer lists, inventory, projects, etc.

**Target User:** Someone who has never used a database before (like Kah-Len)

**Key Principles:**
- No SQL knowledge required
- Visual drag-and-drop interface  
- Interactive tutorials guide every step
- Instant results (changes apply immediately)
- Database-driven (no hardcoded anything)

## 16.2 Visual Table Designer Interface

### Main Interface Layout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ DATABASE BUILDER                                    [? Help] [👤 Admin ▼]   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ [+ New Table]  [📥 Import Data]  [📤 Export Data]  [🔗 Relationships]      │
│                                                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  YOUR TABLES                    │           TABLE: contacts                 │
│  ─────────────────             │           ─────────────────────────────    │
│                                │                                            │
│  📋 contacts (125 records)     │  [+ Add Field]  [⚙ Settings]  [🗑 Delete] │
│  📋 orders (47 records)        │                                            │
│  📋 products (18 records)      │  ┌──────────────────────────────────────┐ │
│  📋 support_tickets (89)       │  │ ○ id (Auto Number)        [🔒 System]│ │
│                                │  │   Primary key, auto-increment        │ │
│  [+ New Table]                 │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  ┌──────────────────────────────────────┐ │
│                                │  │ ○ name (Text)               [✏][🗑] │ │
│                                │  │   Required: Yes, Max: 100 chars      │ │
│                                │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  ┌──────────────────────────────────────┐ │
│                                │  │ ○ email (Email)             [✏][🗑] │ │
│                                │  │   Required: Yes, Unique: Yes         │ │
│                                │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  ┌──────────────────────────────────────┐ │
│                                │  │ ○ phone (Phone)             [✏][🗑] │ │
│                                │  │   Required: No, Format: US           │ │
│                                │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  ┌──────────────────────────────────────┐ │
│                                │  │ ○ status (Dropdown)         [✏][🗑] │ │
│                                │  │   Options: Active, Inactive, Lead    │ │
│                                │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  ┌──────────────────────────────────────┐ │
│                                │  │ ○ created_at (Date/Time)    [🔒 Sys] │ │
│                                │  │   Auto-set on creation               │ │
│                                │  └──────────────────────────────────────┘ │
│                                │                                            │
│                                │  [Preview Data]  [Save Changes]           │
│                                │                                            │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Add Field Modal

```
╔══════════════════════════════════════════════════════════════════════════╗
║                           ADD NEW FIELD                                   ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  FIELD TYPE                                                              ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    ║
║  │  📝 Text    │  │  📝 Text    │  │  🔢 Number  │  │  📅 Date    │    ║
║  │  (single)   │  │  Area       │  │             │  │             │    ║
║  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    ║
║                                                                          ║
║  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    ║
║  │  ▼ Dropdown │  │  ☑ Checkbox │  │  ⊙ Radio   │  │  📎 File    │    ║
║  │             │  │             │  │             │  │  Upload     │    ║
║  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    ║
║                                                                          ║
║  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    ║
║  │  📧 Email   │  │  📱 Phone   │  │  🔗 URL     │  │  💰 Currency│    ║
║  │             │  │             │  │             │  │             │    ║
║  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    ║
║                                                                          ║
║  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                      ║
║  │  ⭐ Rating  │  │  🎨 Color   │  │  ✍ Signature│                      ║
║  │  (1-5)      │  │  Picker     │  │             │                      ║
║  └─────────────┘  └─────────────┘  └─────────────┘                      ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  FIELD PROPERTIES                                                        ║
║                                                                          ║
║  Field Name (internal): [first_name         ]                           ║
║  Display Label:         [First Name         ]                           ║
║                                                                          ║
║  ☑ Required Field                                                        ║
║  ☐ Unique Values Only                                                    ║
║                                                                          ║
║  Default Value:         [                   ]                           ║
║  Help Text:             [Enter customer's first name]                   ║
║                                                                          ║
║  VALIDATION (optional):                                                  ║
║  Min Length: [0  ]    Max Length: [100 ]                                ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║                              [Cancel]  [Add Field]                       ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

## 16.3 Field Types Reference

| Field Type | Icon | Use Case | Validation Options |
|------------|------|----------|-------------------|
| Text | 📝 | Names, titles, short answers | Min/max length, pattern |
| Text Area | 📝 | Descriptions, notes, long text | Min/max length |
| Number | 🔢 | Quantities, ages, counts | Min/max value, decimals |
| Decimal | 🔢 | Precise numbers | Min/max value, decimal places |
| Date | 📅 | Birthdays, deadlines | Min/max date |
| Date/Time | 📅 | Appointments, timestamps | Min/max datetime |
| Time | 🕐 | Hours, duration | - |
| Dropdown | ▼ | Select from list | Required, options list |
| Checkbox | ☑ | Yes/No, multiple select | Required |
| Radio | ⊙ | Choose one from list | Required, options list |
| File Upload | 📎 | Documents, images | File types, max size |
| Image | 🖼 | Photos, logos | File types, max size, dimensions |
| Email | 📧 | Contact emails | Auto-validates format |
| Phone | 📱 | Phone numbers | Format (US, Intl) |
| URL | 🔗 | Websites, links | Auto-validates format |
| Currency | 💰 | Prices, payments | Currency symbol, decimals |
| Rating | ⭐ | 1-5 star ratings | Min/max stars |
| Color | 🎨 | Color picker | - |
| Signature | ✍ | Digital signatures | - |

## 16.4 Visual Relationship Builder

### Relationship Diagram Interface

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ RELATIONSHIP BUILDER                                        [? Help]        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Drag tables to position. Click and drag between tables to create links.   │
│                                                                             │
│  ┌─────────────────┐                      ┌─────────────────┐              │
│  │   customers     │                      │    orders       │              │
│  ├─────────────────┤                      ├─────────────────┤              │
│  │ 🔑 id          │──────────────────────│ customer_id     │              │
│  │ name           │    One-to-Many       │ 🔑 id          │              │
│  │ email          │                      │ order_date      │              │
│  │ phone          │                      │ total           │              │
│  │ status         │                      │ status          │              │
│  └─────────────────┘                      └────────┬────────┘              │
│                                                    │                        │
│                                                    │ One-to-Many            │
│                                                    │                        │
│                                           ┌────────┴────────┐              │
│                                           │   order_items   │              │
│                                           ├─────────────────┤              │
│                                           │ 🔑 id          │              │
│                                           │ order_id        │              │
│                                           │ product_id      │──────┐       │
│                                           │ quantity        │      │       │
│                                           │ price           │      │       │
│                                           └─────────────────┘      │       │
│                                                                    │       │
│  ┌─────────────────┐                                              │       │
│  │   products      │◄─────────────────────────────────────────────┘       │
│  ├─────────────────┤                                                       │
│  │ 🔑 id          │                                                       │
│  │ name           │                                                       │
│  │ price          │                                                       │
│  │ stock          │                                                       │
│  └─────────────────┘                                                       │
│                                                                             │
│  RELATIONSHIPS:                                                             │
│  • customers → orders (One-to-Many)                                        │
│  • orders → order_items (One-to-Many)                                      │
│  • products → order_items (One-to-Many)                                    │
│                                                                             │
│                                    [Save Layout]  [Export Diagram]         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Create Relationship Modal

```
╔══════════════════════════════════════════════════════════════════════════╗
║                        CREATE RELATIONSHIP                                ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  FROM TABLE                        TO TABLE                              ║
║  [orders              ▼]    →     [customers          ▼]                ║
║                                                                          ║
║  RELATIONSHIP TYPE                                                       ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ⊙ One-to-One                                                           ║
║     Each order belongs to exactly one customer.                          ║
║     Each customer has exactly one order.                                 ║
║                                                                          ║
║  ⦿ One-to-Many (Most Common)                                            ║
║     Each order belongs to one customer.                                  ║
║     Each customer can have MANY orders.                                  ║
║                                                                          ║
║  ⊙ Many-to-Many                                                         ║
║     Many orders can relate to many customers.                            ║
║     (Creates a join table automatically)                                 ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  LINK FIELDS                                                             ║
║                                                                          ║
║  From Field: [customer_id    ▼]  → To Field: [id             ▼]        ║
║                                                                          ║
║  ON DELETE:                                                              ║
║  [CASCADE ▼] (When customer deleted, delete their orders too)           ║
║                                                                          ║
║  Display Name: [Customer Orders          ]                               ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║                           [Cancel]  [Create Relationship]                ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

## 16.5 Data Management Grid

### Spreadsheet-Style Data View

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ DATA: contacts                              [🔍 Search...              ]   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ [+ Add Record] [📥 Import] [📤 Export] [🗑 Delete Selected] [⚙ Columns]   │
│                                                                             │
│ Filters: [Status: All ▼] [Created: All Time ▼] [+ Add Filter]             │
│                                                                             │
├────┬──────────────────┬─────────────────────────┬────────────┬─────────────┤
│ ☐  │ Name             │ Email                   │ Phone      │ Status      │
├────┼──────────────────┼─────────────────────────┼────────────┼─────────────┤
│ ☐  │ John Smith       │ john@example.com        │ 555-0101   │ ● Active    │
│ ☐  │ Jane Doe         │ jane@example.com        │ 555-0102   │ ● Active    │
│ ☐  │ Bob Wilson       │ bob@example.com         │ 555-0103   │ ○ Inactive  │
│ ☐  │ Alice Brown      │ alice@company.com       │ 555-0104   │ ◐ Lead      │
│ ☐  │ Charlie Davis    │ charlie@business.com    │ 555-0105   │ ● Active    │
│ ☐  │ Diana Miller     │ diana@startup.io        │ 555-0106   │ ● Active    │
│ ☐  │ Edward Taylor    │ ed@agency.com           │ 555-0107   │ ○ Inactive  │
│ ☐  │ Fiona Garcia     │ fiona@design.co         │ 555-0108   │ ● Active    │
│ ☐  │ George Lee       │ george@tech.net         │ 555-0109   │ ◐ Lead      │
│ ☐  │ Helen Wang       │ helen@corp.com          │ 555-0110   │ ● Active    │
├────┴──────────────────┴─────────────────────────┴────────────┴─────────────┤
│                                                                             │
│ Showing 1-10 of 125 records                    [◄ Prev] [1] [2] [3] [Next ►]│
│                                                                             │
│ Selected: 0 records                            [Bulk Edit] [Bulk Delete]   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Inline Edit Record

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ EDIT RECORD #1 - John Smith                                    [✕ Close]   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Name *                           Email *                                   │
│  ┌────────────────────────────┐  ┌────────────────────────────────────┐   │
│  │ John Smith                 │  │ john@example.com                   │   │
│  └────────────────────────────┘  └────────────────────────────────────┘   │
│                                                                             │
│  Phone                            Status                                    │
│  ┌────────────────────────────┐  ┌──────────────────────────┐             │
│  │ 555-0101                   │  │ Active                 ▼ │             │
│  └────────────────────────────┘  └──────────────────────────┘             │
│                                                                             │
│  Notes                                                                      │
│  ┌────────────────────────────────────────────────────────────────────┐   │
│  │ Long-time customer. Prefers email contact.                         │   │
│  │                                                                     │   │
│  └────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Created: Jan 15, 2026 10:30 AM              Modified: Jan 20, 2026 2:15 PM│
│                                                                             │
│                                      [Delete Record]  [Cancel]  [Save]     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 16.6 Import/Export Functionality

### Import Data Wizard

```
╔══════════════════════════════════════════════════════════════════════════╗
║                         IMPORT DATA                                       ║
║                         Step 2 of 3: Map Columns                          ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  File: customers.xlsx (250 rows)                                         ║
║                                                                          ║
║  Map your spreadsheet columns to database fields:                        ║
║                                                                          ║
║  SPREADSHEET COLUMN          →    DATABASE FIELD                         ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  "Customer Name"             →    [name              ▼]  ✓ Mapped       ║
║  "Email Address"             →    [email             ▼]  ✓ Mapped       ║
║  "Phone Number"              →    [phone             ▼]  ✓ Mapped       ║
║  "Customer Status"           →    [status            ▼]  ✓ Mapped       ║
║  "Notes"                     →    [-- Skip Column -- ▼]  ⚠ Skipped     ║
║  "Old ID"                    →    [-- Skip Column -- ▼]  ⚠ Skipped     ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  PREVIEW (First 5 rows):                                                 ║
║  ┌─────────────────┬─────────────────────┬────────────┬───────────┐     ║
║  │ name            │ email               │ phone      │ status    │     ║
║  ├─────────────────┼─────────────────────┼────────────┼───────────┤     ║
║  │ John Smith      │ john@example.com    │ 555-0101   │ Active    │     ║
║  │ Jane Doe        │ jane@example.com    │ 555-0102   │ Active    │     ║
║  │ Bob Wilson      │ bob@example.com     │ 555-0103   │ Inactive  │     ║
║  │ Alice Brown     │ alice@company.com   │ 555-0104   │ Lead      │     ║
║  │ Charlie Davis   │ charlie@business... │ 555-0105   │ Active    │     ║
║  └─────────────────┴─────────────────────┴────────────┴───────────┘     ║
║                                                                          ║
║  ☐ First row contains headers (skip during import)                       ║
║  ☐ Update existing records if email matches                              ║
║  ⦿ Add as new records only                                              ║
║                                                                          ║
║                        [◄ Back]  [Cancel]  [Import 250 Records ►]        ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

## 16.7 API Endpoints

```
BASE URL: /api/database-builder/

# Tables
GET    /tables                    - List all user tables
POST   /tables                    - Create new table
GET    /tables/{id}               - Get table details
PUT    /tables/{id}               - Update table
DELETE /tables/{id}               - Delete table

# Fields
GET    /tables/{id}/fields        - List fields in table
POST   /tables/{id}/fields        - Add field to table
PUT    /tables/{id}/fields/{fid}  - Update field
DELETE /tables/{id}/fields/{fid}  - Delete field
POST   /tables/{id}/fields/reorder - Reorder fields

# Relationships
GET    /relationships             - List all relationships
POST   /relationships             - Create relationship
DELETE /relationships/{id}        - Delete relationship

# Data
GET    /tables/{id}/data          - Get records (paginated)
POST   /tables/{id}/data          - Create record
PUT    /tables/{id}/data/{rid}    - Update record
DELETE /tables/{id}/data/{rid}    - Delete record
POST   /tables/{id}/data/bulk     - Bulk operations

# Import/Export
POST   /tables/{id}/import        - Import data (CSV/Excel)
GET    /tables/{id}/export        - Export data
GET    /tables/{id}/export/{format} - Export as CSV/JSON/Excel
```

---



# SECTION 17: FORM LIBRARY & BUILDER

**55+ PRE-BUILT FORMS - Ready to use in seconds!**

The Form Library provides 55+ professionally designed forms across 6 categories, each available in 3 styles (Casual, Business, Corporate). Users can launch a form in under 2 minutes without any design or coding skills.

## 17.1 Form Library Overview

### Form Categories & Count

| Category | Forms | Description |
|----------|-------|-------------|
| Customer Management | 10 | Registration, feedback, complaints, returns |
| Sales & Billing | 10 | Quotes, orders, invoices, refunds |
| Support & Service | 10 | Tickets, bugs, appointments, callbacks |
| Marketing & Leads | 10 | Newsletter, leads, surveys, referrals |
| HR & Operations | 10 | Job applications, expenses, incidents |
| VPN-Specific | 5 | VPN setup, server requests, port forwarding |
| **TOTAL** | **55** | Ready to use immediately |

## 17.2 Complete Form List

### CUSTOMER MANAGEMENT (10 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 1 | Customer Registration | 6 | New customer signup |
| 2 | Customer Profile Update | 6 | Update customer info |
| 3 | Customer Feedback | 6 | Collect general feedback |
| 4 | Customer Satisfaction Survey | 5 | Detailed satisfaction rating |
| 5 | Customer Complaint | 6 | Handle complaints formally |
| 6 | RMA Request | 7 | Return merchandise authorization |
| 7 | Product Return | 7 | Standard product returns |
| 8 | Warranty Claim | 7 | Submit warranty claims |
| 9 | Service Request | 7 | Request professional services |
| 10 | Account Closure | 6 | Request account deletion |

### SALES & BILLING (10 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 11 | Quote Request | 8 | Request price quotes |
| 12 | Order Form | 7 | Place product orders |
| 13 | Invoice Template | 8 | Generate invoices |
| 14 | Payment Form | 5 | Collect payments |
| 15 | Refund Request | 6 | Request refunds |
| 16 | Credit Application | 9 | Apply for credit/financing |
| 17 | Purchase Order | 7 | Create purchase orders |
| 18 | Contract Agreement | 9 | Service contract form |
| 19 | Subscription Change | 6 | Modify subscription plans |
| 20 | Cancellation Form | 7 | Cancel subscription/service |

### SUPPORT & SERVICE (10 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 21 | Support Ticket | 7 | Submit support requests |
| 22 | Bug Report | 9 | Report software bugs |
| 23 | Feature Request | 6 | Request new features |
| 24 | Technical Support | 8 | Get technical help |
| 25 | Installation Request | 7 | Request installation assistance |
| 26 | Training Request | 8 | Request product training |
| 27 | Consultation Booking | 8 | Book a consultation |
| 28 | Appointment Scheduler | 6 | Schedule appointments |
| 29 | Callback Request | 5 | Request phone callback |
| 30 | Chat Transcript | 9 | Save chat conversations |

### MARKETING & LEADS (10 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 31 | Newsletter Signup | 3 | Email newsletter subscription |
| 32 | Lead Capture | 7 | Capture sales leads |
| 33 | Gated Content | 5 | Access gated resources |
| 34 | Webinar Registration | 6 | Register for webinars |
| 35 | Event Registration | 7 | Register for events |
| 36 | Contest Entry | 6 | Enter contests/giveaways |
| 37 | Survey Form | 4 | Multiple choice surveys |
| 38 | Quick Poll | 2 | Simple voting polls |
| 39 | Quiz Form | 5 | Scored quizzes |
| 40 | Referral Form | 5 | Customer referral program |

### HR & OPERATIONS (10 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 41 | Job Application | 10 | Employment applications |
| 42 | Employee Onboarding | 9 | New hire paperwork |
| 43 | Time Off Request | 7 | Request vacation/sick leave |
| 44 | Expense Report | 7 | Submit expense reimbursements |
| 45 | Vendor Application | 9 | Become a vendor/supplier |
| 46 | Partner Application | 8 | Become a partner/reseller |
| 47 | NDA Agreement | 7 | Non-disclosure agreement |
| 48 | Contact Update | 6 | Update contact info |
| 49 | Change Request | 9 | Request system/process changes |
| 50 | Incident Report | 9 | Report workplace incidents |

### VPN-SPECIFIC (5 Forms)

| # | Form Name | Fields | Purpose |
|---|-----------|--------|---------|
| 51 | VPN Account Setup | 5 | New VPN account request |
| 52 | Server Change Request | 4 | Request server change |
| 53 | Port Forwarding Request | 6 | Request port forwarding |
| 54 | VIP Access Request | 4 | Request VIP status |
| 55 | Network Scanner Report | 5 | Submit scanner results |

## 17.3 Three Style System

Every form is available in THREE distinct styles, each designed for different audiences and brand personalities.

### STYLE 1: CASUAL

**Best For:** Consumer products, fun brands, informal businesses, startups

**Visual Characteristics:**
- **Colors:** Bright, playful (coral #FF6B6B, teal #4ECDC4, yellow #FFE66D)
- **Fonts:** Poppins (headings), Nunito (body) - rounded, friendly
- **Buttons:** Rounded corners (12px), colorful gradients
- **Icons:** Playful, cartoonish, emoji-friendly
- **Borders:** Thick, rounded, colorful
- **Shadows:** Soft, large drop shadows

**Tone Examples:**
- Heading: "Hey there! 👋 Let's get started!"
- Labels: "What should we call you?"
- Buttons: "Submit! 🎉"
- Success: "Awesome! We got your message! 🙌"
- Error: "Oops! Something's not quite right 😅"

**CSS Variables:**
```css
--casual-primary: #FF6B6B;
--casual-secondary: #4ECDC4;
--casual-accent: #FFE66D;
--casual-bg: #FFFFFF;
--casual-text: #333333;
--casual-border-radius: 12px;
--casual-font-heading: 'Poppins', sans-serif;
--casual-font-body: 'Nunito', sans-serif;
```

### STYLE 2: BUSINESS

**Best For:** B2B companies, professional services, established businesses

**Visual Characteristics:**
- **Colors:** Professional (blue #4A90E2, green #5CB85C, gray #333333)
- **Fonts:** Inter (headings), Open Sans (body) - clean, modern
- **Buttons:** Slightly rounded (6px), solid colors
- **Icons:** Simple line icons, minimalist
- **Borders:** Thin, subtle, gray
- **Shadows:** Subtle, tight drop shadows

**Tone Examples:**
- Heading: "Contact Us"
- Labels: "Full Name"
- Buttons: "Submit"
- Success: "Your submission has been received."
- Error: "Please correct the highlighted fields."

**CSS Variables:**
```css
--business-primary: #4A90E2;
--business-secondary: #5CB85C;
--business-accent: #F0AD4E;
--business-bg: #FFFFFF;
--business-text: #333333;
--business-border-radius: 6px;
--business-font-heading: 'Inter', sans-serif;
--business-font-body: 'Open Sans', sans-serif;
```

### STYLE 3: CORPORATE

**Best For:** Enterprise clients, luxury brands, formal industries, law firms, finance

**Visual Characteristics:**
- **Colors:** Premium (navy #1A1A2E, gold #D4AF37, black #000000)
- **Fonts:** Merriweather (headings), Playfair Display (body) - elegant serif
- **Buttons:** Sharp corners (0px), minimalist, often outlined
- **Icons:** Minimal, sophisticated, or none
- **Borders:** Thin, elegant lines
- **Shadows:** Very subtle or none

**Tone Examples:**
- Heading: "Contact Information"
- Labels: "Full Name"
- Buttons: "Submit Request"
- Success: "Your inquiry has been received. We will respond within one business day."
- Error: "Kindly review and correct the indicated fields."

**CSS Variables:**
```css
--corporate-primary: #1A1A2E;
--corporate-secondary: #D4AF37;
--corporate-accent: #16213E;
--corporate-bg: #FFFFFF;
--corporate-text: #1A1A2E;
--corporate-border-radius: 0px;
--corporate-font-heading: 'Merriweather', serif;
--corporate-font-body: 'Playfair Display', serif;
```

## 17.4 Form Builder Interface

### Main Form Builder

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ FORM BUILDER: Contact Us                          [Preview] [Save] [Publish]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  FIELD LIBRARY            │  FORM CANVAS                                    │
│  ─────────────────       │  ─────────────────────────────────────────────   │
│                          │                                                  │
│  BASIC FIELDS            │  Form Style: [Business ▼]                       │
│  ┌──────────────┐        │                                                  │
│  │ 📝 Text      │        │  ┌─────────────────────────────────────────────┐│
│  └──────────────┘        │  │              CONTACT US                      ││
│  ┌──────────────┐        │  │  We'd love to hear from you!                 ││
│  │ 📝 Text Area │        │  └─────────────────────────────────────────────┘│
│  └──────────────┘        │                                                  │
│  ┌──────────────┐        │  ┌─────────────────────────────────────────────┐│
│  │ 🔢 Number    │        │  │ Full Name *                                  ││
│  └──────────────┘        │  │ ┌─────────────────────────────────────────┐ ││
│  ┌──────────────┐        │  │ │                                         │ ││
│  │ 📧 Email     │        │  │ └─────────────────────────────────────────┘ ││
│  └──────────────┘        │  │                               [✏][↕][🗑]    ││
│  ┌──────────────┐        │  └─────────────────────────────────────────────┘│
│  │ 📱 Phone     │        │                                                  │
│  └──────────────┘        │  ┌─────────────────────────────────────────────┐│
│  ┌──────────────┐        │  │ Email Address *                              ││
│  │ 📅 Date      │        │  │ ┌─────────────────────────────────────────┐ ││
│  └──────────────┘        │  │ │                                         │ ││
│                          │  │ └─────────────────────────────────────────┘ ││
│  ADVANCED FIELDS         │  │                               [✏][↕][🗑]    ││
│  ┌──────────────┐        │  └─────────────────────────────────────────────┘│
│  │ ▼ Dropdown   │        │                                                  │
│  └──────────────┘        │  ┌─────────────────────────────────────────────┐│
│  ┌──────────────┐        │  │ Message *                                    ││
│  │ ☑ Checkbox   │        │  │ ┌─────────────────────────────────────────┐ ││
│  └──────────────┘        │  │ │                                         │ ││
│  ┌──────────────┐        │  │ │                                         │ ││
│  │ ⊙ Radio      │        │  │ │                                         │ ││
│  └──────────────┘        │  │ └─────────────────────────────────────────┘ ││
│  ┌──────────────┐        │  │                               [✏][↕][🗑]    ││
│  │ 📎 File      │        │  └─────────────────────────────────────────────┘│
│  └──────────────┘        │                                                  │
│  ┌──────────────┐        │  ┌─────────────────────────────────────────────┐│
│  │ ⭐ Rating    │        │  │           [ SEND MESSAGE ]                   ││
│  └──────────────┘        │  └─────────────────────────────────────────────┘│
│  ┌──────────────┐        │                                                  │
│  │ ✍ Signature  │        │                                                  │
│  └──────────────┘        │                                                  │
│                          │                                                  │
│  LAYOUT                  │                                                  │
│  ┌──────────────┐        │                                                  │
│  │ ═ Section    │        │                                                  │
│  └──────────────┘        │                                                  │
│  ┌──────────────┐        │                                                  │
│  │ ─ Divider    │        │                                                  │
│  └──────────────┘        │                                                  │
│  ┌──────────────┐        │                                                  │
│  │ ⊟ Page Break │        │                                                  │
│  └──────────────┘        │                                                  │
│                          │                                                  │
└──────────────────────────┴──────────────────────────────────────────────────┘
```

### Field Properties Panel

```
╔══════════════════════════════════════════════════════════════════════════╗
║                       FIELD PROPERTIES                           [✕]    ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  BASIC                                                                   ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Field Label:        [Full Name                    ]                    ║
║  Placeholder:        [Enter your full name         ]                    ║
║  Help Text:          [First and last name          ]                    ║
║                                                                          ║
║  ☑ Required Field                                                        ║
║  ☐ Hide Label                                                            ║
║                                                                          ║
║  VALIDATION                                                              ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Min Length:  [2  ]        Max Length:  [100 ]                          ║
║                                                                          ║
║  Custom Pattern:    [                              ]                    ║
║  Pattern Error:     [Please enter a valid name     ]                    ║
║                                                                          ║
║  APPEARANCE                                                              ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Width:      [Full ▼]    (Full, Half, Third, Quarter)                   ║
║  CSS Class:  [                                     ]                    ║
║                                                                          ║
║  CONDITIONAL LOGIC                                                       ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ☐ Enable conditional logic                                              ║
║                                                                          ║
║  Show this field when:                                                   ║
║  [Field: customer_type ▼] [equals ▼] [Business ▼]                       ║
║                                                                          ║
║  [+ Add Condition]                                                       ║
║                                                                          ║
║                                                   [Cancel]  [Apply]     ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

### Form Settings Panel

```
╔══════════════════════════════════════════════════════════════════════════╗
║                        FORM SETTINGS                            [✕]     ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  GENERAL                                                                 ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Form Name:       [Contact Us                           ]               ║
║  Form URL:        vpn.the-truth-publishing.com/form/[contact-us  ]      ║
║  Description:     [General contact form                 ]               ║
║                                                                          ║
║  ☑ Active (accepting submissions)                                        ║
║  ☐ Require login to submit                                               ║
║                                                                          ║
║  AFTER SUBMISSION                                                        ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Success Action:  ⦿ Show Message  ○ Redirect to URL                     ║
║                                                                          ║
║  Success Message: [Thank you! We'll be in touch soon.   ]               ║
║  Redirect URL:    [                                      ]               ║
║                                                                          ║
║  NOTIFICATIONS                                                           ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ☑ Send email notification to admin                                      ║
║     Email: [admin@truevault.com                         ]               ║
║                                                                          ║
║  ☑ Send confirmation email to submitter                                  ║
║     Subject: [Thanks for contacting us!                 ]               ║
║                                                                          ║
║  DATA STORAGE                                                            ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ☑ Save submissions to database                                          ║
║  ☐ Also save to custom table: [-- Select Table -- ▼]                    ║
║                                                                          ║
║  SPAM PROTECTION                                                         ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  ☑ Honeypot field (invisible anti-spam)                                  ║
║  ☐ reCAPTCHA (requires API key)                                          ║
║  ☐ Rate limiting (max 3 per hour per IP)                                 ║
║                                                                          ║
║                                              [Cancel]  [Save Settings]  ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

## 17.5 Form Submissions Management

### Submissions List View

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ SUBMISSIONS: Contact Us Form                                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ [📤 Export All] [🗑 Delete Selected]  Filter: [All ▼] [This Week ▼]        │
│                                                                             │
│ 🔍 Search: [                                    ]                          │
│                                                                             │
├────┬───────────────────┬────────────────────────┬──────────────┬───────────┤
│ ☐  │ Submitted         │ Name                   │ Email        │ Status    │
├────┼───────────────────┼────────────────────────┼──────────────┼───────────┤
│ ☐  │ Jan 20, 2:45 PM   │ John Smith             │ john@ex.com  │ ● New     │
│ ☐  │ Jan 20, 11:30 AM  │ Jane Doe               │ jane@co.com  │ ● New     │
│ ☐  │ Jan 19, 4:15 PM   │ Bob Wilson             │ bob@biz.com  │ ○ Read    │
│ ☐  │ Jan 19, 10:00 AM  │ Alice Brown            │ alice@io.com │ ✓ Replied │
│ ☐  │ Jan 18, 3:30 PM   │ Charlie Davis          │ cd@tech.net  │ ○ Read    │
├────┴───────────────────┴────────────────────────┴──────────────┴───────────┤
│                                                                             │
│ Showing 1-5 of 47 submissions                      [◄ Prev] [1] [2] [Next ►]│
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Single Submission View

```
╔══════════════════════════════════════════════════════════════════════════╗
║ SUBMISSION #124                                                  [✕]    ║
╠══════════════════════════════════════════════════════════════════════════╣
║                                                                          ║
║  Submitted: January 20, 2026 at 2:45 PM                                  ║
║  Form: Contact Us                                                        ║
║  Status: [New     ▼]                                                     ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  Full Name:       John Smith                                             ║
║  Email:           john@example.com                                       ║
║  Phone:           555-123-4567                                           ║
║  Subject:         Question about VPN plans                               ║
║                                                                          ║
║  Message:                                                                ║
║  ┌────────────────────────────────────────────────────────────────┐     ║
║  │ Hi, I'm interested in your family plan but I have a few       │     ║
║  │ questions about the parental controls feature. Can you tell   │     ║
║  │ me more about how it works and what content can be blocked?   │     ║
║  │                                                                │     ║
║  │ Thanks,                                                        │     ║
║  │ John                                                           │     ║
║  └────────────────────────────────────────────────────────────────┘     ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  METADATA                                                                ║
║  IP Address: 192.168.1.100                                               ║
║  Browser: Chrome 120 on Windows 11                                       ║
║  Referrer: https://google.com                                            ║
║                                                                          ║
║  ─────────────────────────────────────────────────────────────────────   ║
║                                                                          ║
║  INTERNAL NOTES                                                          ║
║  ┌────────────────────────────────────────────────────────────────┐     ║
║  │ Add notes about this submission...                             │     ║
║  └────────────────────────────────────────────────────────────────┘     ║
║                                                                          ║
║                    [Delete]  [Mark as Spam]  [Reply by Email]           ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
```

## 17.6 API Endpoints

```
BASE URL: /api/forms/

# Form Templates (read-only)
GET    /templates                  - List all 55 templates
GET    /templates/{code}           - Get single template
GET    /templates/category/{cat}   - Get templates by category

# User Forms
GET    /forms                      - List user's forms
POST   /forms                      - Create new form
GET    /forms/{id}                 - Get form details
PUT    /forms/{id}                 - Update form
DELETE /forms/{id}                 - Delete form
POST   /forms/{id}/duplicate       - Duplicate form

# Form Fields
GET    /forms/{id}/fields          - Get form fields
PUT    /forms/{id}/fields          - Update form fields (full replace)
POST   /forms/{id}/fields/reorder  - Reorder fields

# Public Form Submission
POST   /submit/{slug}              - Submit form (public)
GET    /form/{slug}                - Get form for rendering (public)

# Submissions
GET    /forms/{id}/submissions     - List submissions (paginated)
GET    /submissions/{id}           - Get single submission
PUT    /submissions/{id}           - Update submission status/notes
DELETE /submissions/{id}           - Delete submission
POST   /submissions/bulk-export    - Export selected submissions

# Form Styles
GET    /styles                     - List all styles (casual, business, corporate)
GET    /styles/{code}              - Get style details with CSS vars
```

---

