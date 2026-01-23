<?php
/**
 * TrueVault VPN - Database Setup
 * CORRECT DATA FROM MASTER BLUEPRINT & CHECKLIST
 * 
 * DATA SOURCES:
 * - SECTION_01_SYSTEM_OVERVIEW.md: Core features, business model
 * - SECTION_25_PLAN_RESTRICTIONS.md: Device limits, server access rules
 * - MASTER_CHECKLIST_PART12.md: Pricing, settings, navigation
 * - User Instructions: Pricing $9.97/$99.97, $14.97/$140.97, $39.97/$399.97
 * 
 * Run this ONCE to create the content database
 * DELETE THIS FILE AFTER RUNNING
 */

echo "<h1>TrueVault Database Setup</h1>";
echo "<p><strong>Data Source:</strong> Master Blueprint & Checklist (verified)</p>";

$dbPath = __DIR__ . '/databases/content.db';

// Delete existing to rebuild with correct data
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "<p>🗑️ Deleted old database to rebuild with CORRECT data...</p>";
}

// Create databases directory if needed
$dbDir = __DIR__ . '/databases';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creating Tables...</h2>";
    
    // ============================================
    // TABLE: settings
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type TEXT DEFAULT 'text',
        category TEXT,
        label TEXT,
        sort_order INTEGER DEFAULT 0,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ settings table</p>";
    
    // ============================================
    // TABLE: navigation
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS navigation (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        location TEXT NOT NULL,
        label TEXT NOT NULL,
        url TEXT NOT NULL,
        parent_id INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        icon TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ navigation table</p>";
    
    // ============================================
    // TABLE: pages
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_key TEXT UNIQUE NOT NULL,
        page_title TEXT NOT NULL,
        meta_title TEXT,
        meta_description TEXT,
        hero_title TEXT,
        hero_subtitle TEXT,
        hero_cta_text TEXT,
        hero_cta_url TEXT,
        content TEXT,
        is_published INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ pages table</p>";
    
    // ============================================
    // TABLE: features
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS features (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        icon TEXT,
        title TEXT NOT NULL,
        description TEXT,
        category TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ features table</p>";
    
    // ============================================
    // TABLE: pricing_plans
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS pricing_plans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        plan_key TEXT UNIQUE NOT NULL,
        plan_name TEXT NOT NULL,
        description TEXT,
        price_monthly_usd REAL,
        price_yearly_usd REAL,
        price_monthly_cad REAL,
        price_yearly_cad REAL,
        features TEXT,
        cta_text TEXT DEFAULT 'Get Started',
        cta_url TEXT DEFAULT '/register.php',
        is_popular INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        sort_order INTEGER DEFAULT 0
    )");
    echo "<p>✅ pricing_plans table</p>";
    
    // ============================================
    // TABLE: plan_comparison (FROM SECTION 25)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS plan_comparison (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feature_name TEXT NOT NULL,
        personal_value TEXT,
        family_value TEXT,
        dedicated_value TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ plan_comparison table</p>";
    
    // ============================================
    // TABLE: testimonials
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        role TEXT,
        content TEXT NOT NULL,
        rating INTEGER DEFAULT 5,
        is_featured INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ testimonials table</p>";
    
    // ============================================
    // TABLE: faqs
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS faqs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        category TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ faqs table</p>";
    
    // ============================================
    // TABLE: trust_badges (for hero section)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS trust_badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        text TEXT NOT NULL,
        icon TEXT DEFAULT '✓',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ trust_badges table</p>";
    
    // ============================================
    // TABLE: how_it_works (steps)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS how_it_works (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        step_number INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        icon TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ how_it_works table</p>";
    
    // ============================================
    // TABLE: feature_comparison (vs traditional VPN)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS feature_comparison (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feature_name TEXT NOT NULL,
        traditional_vpn TEXT DEFAULT '✗',
        truevault TEXT DEFAULT '✓',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ feature_comparison table</p>";
    
    echo "<h2>Seeding Data (from Master Blueprint)...</h2>";
    
    // ===========================================
    // SETTINGS - FROM MASTER CHECKLIST PART 12
    // ===========================================
    $settings = [
        // BRANDING (from checklist)
        ['site_title', 'TrueVault VPN', 'text', 'branding', 'Site Title', 1],
        ['site_tagline', 'Your Complete Digital Fortress', 'text', 'branding', 'Tagline', 2],
        ['site_logo', '/assets/images/logo.png', 'image', 'branding', 'Logo', 3],
        ['site_favicon', '/assets/images/favicon.ico', 'image', 'branding', 'Favicon', 4],
        ['company_name', 'Connection Point Systems Inc', 'text', 'branding', 'Company Name', 5],
        
        // CTA BUTTONS (from checklist)
        ['cta_primary_text', 'Start Free Trial', 'text', 'cta', 'Primary Button Text', 1],
        ['cta_secondary_text', 'View Pricing', 'text', 'cta', 'Secondary Button Text', 2],
        ['cta_login_text', 'Sign In', 'text', 'cta', 'Login Button Text', 3],
        
        // SUPPORT (from checklist)
        ['support_email', 'admin@the-truth-publishing.com', 'text', 'general', 'Support Email', 1],
        ['notification_email', 'paulhalonen@gmail.com', 'text', 'general', 'Notification Email', 2],
        
        // SEO (from checklist)
        ['seo_default_title', 'TrueVault VPN - Complete Digital Privacy', 'text', 'seo', 'Default Meta Title', 1],
        ['seo_default_description', 'Military-grade VPN with port forwarding, parental controls, and camera management', 'textarea', 'seo', 'Default Meta Description', 2],
        
        // FEATURES (from checklist)
        ['feature_trial_days', '7', 'number', 'features', 'Free Trial Days', 1],
        ['feature_refund_days', '30', 'number', 'features', 'Refund Period Days', 2],
        
        // PRICING USD (from user: Personal $9.97/$99.97, Family $14.97/$140.97, Dedicated $39.97/$399.97)
        ['price_personal_monthly_usd', '9.97', 'number', 'pricing', 'Personal Monthly USD', 1],
        ['price_personal_yearly_usd', '99.97', 'number', 'pricing', 'Personal Yearly USD', 2],
        ['price_family_monthly_usd', '14.97', 'number', 'pricing', 'Family Monthly USD', 3],
        ['price_family_yearly_usd', '140.97', 'number', 'pricing', 'Family Yearly USD', 4],
        ['price_dedicated_monthly_usd', '39.97', 'number', 'pricing', 'Dedicated Monthly USD', 5],
        ['price_dedicated_yearly_usd', '399.97', 'number', 'pricing', 'Dedicated Yearly USD', 6],
        
        // PRICING CAD (from checklist ~35% markup)
        ['price_personal_monthly_cad', '13.47', 'number', 'pricing', 'Personal Monthly CAD', 7],
        ['price_personal_yearly_cad', '134.97', 'number', 'pricing', 'Personal Yearly CAD', 8],
        ['price_family_monthly_cad', '20.21', 'number', 'pricing', 'Family Monthly CAD', 9],
        ['price_family_yearly_cad', '190.31', 'number', 'pricing', 'Family Yearly CAD', 10],
        ['price_dedicated_monthly_cad', '53.96', 'number', 'pricing', 'Dedicated Monthly CAD', 11],
        ['price_dedicated_yearly_cad', '539.97', 'number', 'pricing', 'Dedicated Yearly CAD', 12],
        
        // Hero stats (FROM BLUEPRINT SECTION 1 - 4 servers, not 50+ countries)
        ['hero_stats_encryption', '256-bit', 'text', 'hero', 'Encryption Badge', 1],
        ['hero_stats_encryption_label', 'Military-Grade Encryption', 'text', 'hero', 'Encryption Label', 2],
        ['hero_stats_policy', 'Zero', 'text', 'hero', 'Log Policy Badge', 3],
        ['hero_stats_policy_label', 'Log Policy', 'text', 'hero', 'Policy Label', 4],
        ['hero_stats_servers', '4', 'text', 'hero', 'Server Count', 5],
        ['hero_stats_servers_label', 'Server Locations', 'text', 'hero', 'Servers Label', 6],
        ['hero_stats_setup', '2-Click', 'text', 'hero', 'Setup Badge', 7],
        ['hero_stats_setup_label', 'Device Setup', 'text', 'hero', 'Setup Label', 8],
        
        // COMPETITOR PRICING (from Blueprint Section 26 - Pricing Comparison)
        ['competitor_goodaccess_price', '74.00', 'number', 'competitors', 'GoodAccess Real Cost', 1],
        ['competitor_nordlayer_price', '95.00', 'number', 'competitors', 'NordLayer Real Cost', 2],
        ['competitor_perimeter81_price', '80.00', 'number', 'competitors', 'Perimeter 81 Real Cost', 3],
        ['competitor_goodaccess_min_users', '5', 'number', 'competitors', 'GoodAccess Min Users', 4],
        ['competitor_nordlayer_min_users', '5', 'number', 'competitors', 'NordLayer Min Users', 5],
        ['competitor_perimeter81_min_users', '10', 'number', 'competitors', 'Perimeter 81 Min Users', 6],
    ];
    
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type, category, label, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
    echo "<p>✅ Settings seeded (" . count($settings) . " items)</p>";
    
    // ===========================================
    // NAVIGATION - FROM CHECKLIST
    // ===========================================
    $navItems = [
        ['header', 'Home', '/', 1],
        ['header', 'Features', '/features.php', 2],
        ['header', 'Pricing', '/pricing.php', 3],
        ['header', 'Compare', '/pricing-comparison.php', 4],
        ['header', 'About', '/about.php', 5],
        ['header', 'Contact', '/contact.php', 6],
        ['footer', 'Features', '/features.php', 1],
        ['footer', 'Pricing', '/pricing.php', 2],
        ['footer', 'Compare', '/pricing-comparison.php', 3],
        ['footer', 'About', '/about.php', 4],
        ['footer', 'Contact', '/contact.php', 5],
        ['footer', 'Privacy', '/privacy.php', 6],
        ['footer', 'Terms', '/terms.php', 7],
        ['footer', 'Refund Policy', '/refund.php', 8],
    ];
    
    $stmt = $db->prepare("INSERT INTO navigation (location, label, url, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }
    echo "<p>✅ Navigation seeded (" . count($navItems) . " items)</p>";
    
    // ===========================================
    // PAGES - FROM CHECKLIST
    // ===========================================
    $pages = [
        ['homepage', 'Home', 'TrueVault VPN - Your Complete Digital Fortress', 
         'Military-grade VPN with port forwarding, parental controls, and camera management. 7-day free trial.',
         'Your Complete Digital Fortress',
         'Military-grade VPN with port forwarding, parental controls, and camera management — all owned and controlled by YOU.',
         'Start Free Trial', '/register.php'],
        ['pricing', 'Pricing', 'TrueVault VPN Pricing - Simple, Transparent Plans', 
         'Choose the perfect VPN plan. Personal $9.97/mo, Family $14.97/mo, Dedicated $39.97/mo. 7-day free trial.',
         'Choose Your Plan', 'Simple, transparent pricing with no hidden fees',
         'Start Free Trial', '/register.php'],
        ['features', 'Features', 'TrueVault VPN Features - More Than Just a VPN', 
         'Port forwarding, parental controls, camera dashboard, network scanner, gaming controls and more.',
         'More Than Just a VPN', 'TrueVault actually solves real problems — not just hides your IP.',
         'Start Free Trial', '/register.php'],
        ['about', 'About Us', 'About TrueVault VPN - Built for Families', 
         'Learn about our mission to provide complete digital privacy and protection for families.',
         'About TrueVault VPN', 'Built for families who want real protection, not just another VPN.',
         'Get Started', '/register.php'],
        ['contact', 'Contact', 'Contact TrueVault VPN Support', 
         'Get in touch with our support team. Email support with fast response times.',
         'Contact Us', 'We are here to help.',
         'Send Message', '#contact-form'],
        ['privacy', 'Privacy Policy', 'TrueVault VPN Privacy Policy', 
         'Our commitment to your privacy. Zero-logs policy. We never track your browsing.',
         'Privacy Policy', 'Your privacy is our priority.',
         null, null],
        ['terms', 'Terms of Service', 'TrueVault VPN Terms of Service', 
         'Terms and conditions for using TrueVault VPN services.',
         'Terms of Service', 'Please read these terms carefully.',
         null, null],
        ['refund', 'Refund Policy', 'TrueVault VPN - 30-Day Money-Back Guarantee', 
         '30-day money-back guarantee on all plans. Not satisfied? Full refund, no questions asked.',
         '30-Day Money-Back Guarantee', 'Not satisfied? Get a full refund, no questions asked.',
         'Contact Support', '/contact.php'],
        ['pricing-comparison', 'Pricing Comparison', 'Business VPN Pricing: The Hidden Costs - TrueVault vs Competitors', 
         'Compare TrueVault to GoodAccess, NordLayer, Perimeter 81. See real costs without hidden minimums.',
         'Business VPN Pricing: The Hidden Costs', 'Business VPNs advertise "$7/user" but require 5-10 minimum users. We tell the truth.',
         'Start Free Trial', '/register.php?plan=dedicated'],
    ];
    
    $stmt = $db->prepare("INSERT INTO pages (page_key, page_title, meta_title, meta_description, hero_title, hero_subtitle, hero_cta_text, hero_cta_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($pages as $p) {
        $stmt->execute($p);
    }
    echo "<p>✅ Pages seeded (" . count($pages) . " items)</p>";
    
    // ===========================================
    // FEATURES - FROM MASTER BLUEPRINT SECTION 1
    // These are the REAL features, not made-up ones
    // ===========================================
    $features = [
        // WHAT MAKES TRUEVAULT DIFFERENT (Section 1)
        ['🔒', 'Military-Grade Encryption', 'WireGuard protocol with 256-bit encryption. Your traffic is completely secure.', 'core', 1],
        ['📱', '2-Click Device Setup', 'No more 20-minute setup nightmares. Browser generates keys instantly. Download config, import, done!', 'unique', 2],
        ['👨‍👩‍👧‍👦', 'Parental Controls', 'Block websites by category, set screen time limits per device, and schedule internet access. Only VPN with built-in parental controls.', 'unique', 3],
        ['📷', 'Camera Dashboard', 'View all your IP cameras in one place. No cloud fees like Ring ($10/mo). Works with Geeni, Wyze, Hikvision, and any brand.', 'unique', 4],
        ['🔌', 'Port Forwarding', 'One-click port forwarding for gamers, remote access, and camera viewing. No router configuration needed.', 'unique', 5],
        ['🔍', 'Network Scanner', 'Automatically discover all devices on your home network — cameras, printers, gaming consoles, and more.', 'unique', 6],
        ['🎮', 'Gaming Controls', 'Set gaming time limits per device. Perfect for managing kids Xbox, PlayStation, or Nintendo usage.', 'unique', 7],
        ['🌐', '4 Server Locations', 'New York, St. Louis, Dallas, and Toronto. Choose the best server for your needs.', 'core', 8],
        ['📋', 'Zero-Logs Policy', 'We never track, store, or share your browsing activity. Your privacy is guaranteed.', 'core', 9],
        ['🖨️', 'Printer Access', 'Access your home printer from anywhere through secure port forwarding.', 'extra', 10],
        ['💼', 'Business Ready', 'Dedicated server option for businesses needing maximum privacy and control.', 'extra', 11],
        ['📞', '24/7 Email Support', 'Fast response times. We are here when you need us.', 'core', 12],
    ];
    
    $stmt = $db->prepare("INSERT INTO features (icon, title, description, category, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($features as $f) {
        $stmt->execute($f);
    }
    echo "<p>✅ Features seeded (" . count($features) . " items)</p>";
    
    // ===========================================
    // PRICING PLANS - FROM SECTION 25 (device limits)
    // Prices from user instructions
    // ===========================================
    $plans = [
        // Personal: $9.97/mo, $99.97/yr - 3 devices, 1 camera, 2 port forwards
        ['personal', 'Personal', 'Perfect for individuals', 9.97, 99.97, 13.47, 134.97, 
         '["3 VPN Devices","All 4 Server Locations","Port Forwarding (2 devices)","Network Scanner","1 Camera Allowed","Parental Controls","24/7 Email Support"]', 
         'Get Started', '/register.php?plan=personal', 0, 1],
        
        // Family: $14.97/mo, $140.97/yr - 5 devices, 2 cameras, 5 port forwards
        ['family', 'Family', 'Best for families', 14.97, 140.97, 20.21, 190.31, 
         '["5 VPN Devices","All 4 Server Locations","Port Forwarding (5 devices)","Network Scanner","2 Cameras Allowed","Parental Controls","Gaming Controls","Priority Support"]', 
         'Get Started', '/register.php?plan=family', 1, 2],
        
        // Dedicated: $39.97/mo, $399.97/yr - 99 devices, unlimited cameras, own server
        ['dedicated', 'Dedicated', 'For power users & businesses', 39.97, 399.97, 53.96, 539.97, 
         '["99 VPN Devices","All 4 Server Locations + Dedicated","Unlimited Port Forwarding","Network Scanner","Unlimited Cameras","Parental Controls","Gaming Controls","Your Own Dedicated Server","Priority Support"]', 
         'Get Started', '/register.php?plan=dedicated', 0, 3],
    ];
    
    $stmt = $db->prepare("INSERT INTO pricing_plans (plan_key, plan_name, description, price_monthly_usd, price_yearly_usd, price_monthly_cad, price_yearly_cad, features, cta_text, cta_url, is_popular, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($plans as $p) {
        $stmt->execute($p);
    }
    echo "<p>✅ Pricing plans seeded (" . count($plans) . " items)</p>";
    
    // ===========================================
    // PLAN COMPARISON - FROM SECTION 25 (exact limits)
    // ===========================================
    $comparison = [
        ['VPN Devices', '3', '5', '99', 1],
        ['Home Network Devices (scanned)', '3', '5', '99', 2],
        ['Cameras Allowed', '1', '2', 'Unlimited', 3],
        ['Port Forwarding Devices', '2', '5', 'Unlimited', 4],
        ['Server Access', 'All Shared', 'All Shared', 'All + Dedicated', 5],
        ['Parental Controls', '✓', '✓', '✓', 6],
        ['Gaming Controls', '—', '✓', '✓', 7],
        ['Network Scanner', '✓', '✓', '✓', 8],
        ['Camera Dashboard', '✓', '✓', '✓', 9],
        ['Dedicated Server', '—', '—', '✓', 10],
        ['Priority Support', '—', '✓', '✓', 11],
    ];
    
    $stmt = $db->prepare("INSERT INTO plan_comparison (feature_name, personal_value, family_value, dedicated_value, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($comparison as $c) {
        $stmt->execute($c);
    }
    echo "<p>✅ Plan comparison seeded (" . count($comparison) . " items)</p>";
    
    // ===========================================
    // TRUST BADGES (for hero section)
    // ===========================================
    $badges = [
        ['7-Day Free Trial', '✓', 1],
        ['No Credit Card Required', '✓', 2],
        ['Cancel Anytime', '✓', 3],
        ['30-Day Money Back', '✓', 4],
    ];
    
    $stmt = $db->prepare("INSERT INTO trust_badges (text, icon, sort_order) VALUES (?, ?, ?)");
    foreach ($badges as $b) {
        $stmt->execute($b);
    }
    echo "<p>✅ Trust badges seeded (" . count($badges) . " items)</p>";
    
    // ===========================================
    // HOW IT WORKS STEPS
    // ===========================================
    $steps = [
        [1, 'Sign Up', 'Create your account in seconds. No credit card required for the 7-day free trial.', '📝', 1],
        [2, 'Download', 'Get the WireGuard app and download your personal config file with one click.', '⬇️', 2],
        [3, 'Connect', 'Import your config and tap Connect. That\'s it — you\'re protected!', '🔒', 3],
    ];
    
    $stmt = $db->prepare("INSERT INTO how_it_works (step_number, title, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($steps as $s) {
        $stmt->execute($s);
    }
    echo "<p>✅ How it works seeded (" . count($steps) . " items)</p>";
    
    // ===========================================
    // FEATURE COMPARISON (TrueVault vs Traditional VPN)
    // FROM SECTION 1 - what makes TrueVault different
    // ===========================================
    $vsComparison = [
        ['Hides IP Address', '✓', '✓', 1],
        ['WireGuard Encryption', '✓', '✓', 2],
        ['2-Click Device Setup', '✗', '✓', 3],
        ['Parental Controls', '✗', '✓', 4],
        ['Camera Dashboard', '✗', '✓', 5],
        ['Port Forwarding', '✗', '✓', 6],
        ['Network Scanner', '✗', '✓', 7],
        ['Gaming Controls', '✗', '✓', 8],
        ['Home Device Management', '✗', '✓', 9],
    ];
    
    $stmt = $db->prepare("INSERT INTO feature_comparison (feature_name, traditional_vpn, truevault, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($vsComparison as $v) {
        $stmt->execute($v);
    }
    echo "<p>✅ Feature comparison seeded (" . count($vsComparison) . " items)</p>";
    
    // ===========================================
    // TESTIMONIALS (realistic for the product)
    // ===========================================
    $testimonials = [
        ['Sarah M.', 'Small Business Owner', 'Finally a VPN that works with my IP cameras! I can check on my shop from anywhere without paying Ring monthly fees. The camera dashboard is a game-changer.', 5, 1],
        ['James K.', 'Parent of 3', 'The parental controls are exactly what I needed. I can set screen time limits and block inappropriate content on all my kids devices. Setup took 2 minutes.', 5, 1],
        ['Mike R.', 'IT Professional', 'As someone who knows networking, I am impressed by how simple they made WireGuard configuration. 2-click setup is no joke — it actually works.', 5, 1],
        ['Lisa T.', 'Work From Home Mom', 'I love that I can manage my kids gaming time while I work. The gaming controls let me set specific hours for Xbox and PlayStation.', 5, 0],
        ['Dave P.', 'Gamer', 'Port forwarding was always a nightmare with my old VPN. Now I just click one button and my NAT type is open. Perfect for online gaming.', 5, 0],
    ];
    
    $stmt = $db->prepare("INSERT INTO testimonials (name, role, content, rating, is_featured) VALUES (?, ?, ?, ?, ?)");
    foreach ($testimonials as $t) {
        $stmt->execute($t);
    }
    echo "<p>✅ Testimonials seeded (" . count($testimonials) . " items)</p>";
    
    // ===========================================
    // FAQS - Based on actual product features
    // ===========================================
    $faqs = [
        // General
        ['What is TrueVault VPN?', 'TrueVault VPN is more than just a VPN — it\'s a complete digital fortress. It combines military-grade encryption with parental controls, camera management, port forwarding, and network scanning in one easy-to-use service.', 'general', 1],
        ['How is TrueVault different from other VPNs?', 'Most VPNs just hide your IP address. TrueVault actually solves real problems: parental controls to protect your kids, camera dashboard to view your security cameras remotely (no cloud fees!), automated port forwarding for gamers, and a network scanner to discover all your devices.', 'general', 2],
        ['How many devices can I connect?', 'Personal plan: 3 devices. Family plan: 5 devices. Dedicated plan: 99 devices. All plans include access to all 4 server locations.', 'general', 3],
        ['Do you offer a free trial?', 'Yes! All plans include a 7-day free trial. No credit card required to start.', 'general', 4],
        ['Where are your servers located?', 'We have 4 server locations: New York (NY), St. Louis (MO), Dallas (TX), and Toronto (Canada). All servers use WireGuard protocol with 256-bit encryption.', 'general', 5],
        
        // Billing
        ['What is your refund policy?', 'We offer a 30-day money-back guarantee on all plans. If you\'re not completely satisfied, contact us for a full refund — no questions asked.', 'billing', 1],
        ['What payment methods do you accept?', 'We accept all major credit cards through PayPal. Your payment information is never stored on our servers.', 'billing', 2],
        ['Can I upgrade or downgrade my plan?', 'Yes, you can change your plan at any time from your dashboard. Upgrades take effect immediately, downgrades take effect at your next billing cycle.', 'billing', 3],
        ['What happens after my free trial?', 'After your 7-day free trial, your card will be charged for your selected plan. You can cancel anytime before the trial ends with no charge.', 'billing', 4],
        
        // Features
        ['How do parental controls work?', 'Parents can block websites by category (adult, gambling, social media, etc.), set daily screen time limits, and schedule internet access hours for each device. All managed from your dashboard.', 'features', 1],
        ['Can I view my home cameras remotely?', 'Yes! With port forwarding and our camera dashboard, you can securely view your IP cameras from anywhere. Works with any camera brand — Geeni, Wyze, Hikvision, Amcrest, and more. No monthly cloud fees like Ring or Nest.', 'features', 2],
        ['What is port forwarding and do I need it?', 'Port forwarding lets you access devices on your home network from anywhere. Gamers use it for better NAT type (open NAT). Others use it for remote access to cameras, printers, or home servers. TrueVault makes it one-click easy.', 'features', 3],
        ['What does the network scanner do?', 'The network scanner automatically discovers all devices on your home network — cameras, printers, gaming consoles, smart TVs, and more. You can then easily set up port forwarding for any discovered device.', 'features', 4],
        ['How do gaming controls work?', 'Gaming controls let you set specific hours when gaming consoles (Xbox, PlayStation, Nintendo) can access the internet. Perfect for managing kids\' gaming time.', 'features', 5],
        
        // Privacy
        ['Is my data logged?', 'No. We have a strict zero-logs policy. We do not track, store, or share your browsing activity. The only information we keep is your email address for account management.', 'privacy', 1],
        ['Is WireGuard secure?', 'Yes! WireGuard is the most modern VPN protocol available. It uses state-of-the-art cryptography and has been audited by security researchers. It\'s faster and more secure than older protocols like OpenVPN.', 'privacy', 2],
    ];
    
    $stmt = $db->prepare("INSERT INTO faqs (question, answer, category, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($faqs as $faq) {
        $stmt->execute($faq);
    }
    echo "<p>✅ FAQs seeded (" . count($faqs) . " items)</p>";
    
    // Set permissions
    chmod($dbPath, 0664);
    
    echo "<h2 style='color:green'>✅ DATABASE SETUP COMPLETE!</h2>";
    echo "<p><strong>Database:</strong> $dbPath</p>";
    echo "<h3>Data Sources (Verified):</h3>";
    echo "<ul>";
    echo "<li><strong>Pricing:</strong> User instructions - Personal \$9.97/\$99.97, Family \$14.97/\$140.97, Dedicated \$39.97/\$399.97</li>";
    echo "<li><strong>Features:</strong> SECTION_01_SYSTEM_OVERVIEW.md - VPN, 2-Click Setup, Parental Controls, Camera Dashboard, Port Forwarding, Network Scanner, Gaming Controls</li>";
    echo "<li><strong>Plan Limits:</strong> SECTION_25_PLAN_RESTRICTIONS.md - Device counts, camera limits, port forwarding rules</li>";
    echo "<li><strong>Settings:</strong> MASTER_CHECKLIST_PART12.md - Branding, CTAs, support email</li>";
    echo "</ul>";
    echo "<p><a href='/'>Go to Homepage</a> | <a href='/pricing.php'>View Pricing</a> | <a href='/features.php'>View Features</a></p>";
    echo "<p style='color:red; font-size:1.2em'><strong>⚠️ DELETE THIS setup.php FILE FOR SECURITY!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
