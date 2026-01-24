<?php
/**
 * TrueVault VPN - Database Setup
 * CORRECT DATA FROM MASTER BLUEPRINT & CHECKLIST
 * 
 * DATA SOURCES:
 * - SECTION_01_SYSTEM_OVERVIEW.md: Core features, business model
 * - SECTION_25_PLAN_RESTRICTIONS.md: Device limits, server access rules
 * - SECTION_26_PRICING_COMPARISON.md: Competitor analysis
 * - MASTER_CHECKLIST_PART12.md: Pricing, settings, navigation
 * - User Instructions: Pricing $9.97/$99.97, $14.97/$140.97, $39.97/$399.97
 * 
 * ALL TEXT FROM DATABASE - NO HARDCODING ANYWHERE
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
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    // Helper function for prepared statements with arrays (SQLite3 style)
    function executeWithParams($db, $sql, $params) {
        $stmt = $db->prepare($sql);
        foreach ($params as $i => $value) {
            $stmt->bindValue($i + 1, $value);
        }
        return $stmt->execute();
    }
    
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
    // TABLE: page_sections (for dynamic page content)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS page_sections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_key TEXT NOT NULL,
        section_key TEXT NOT NULL,
        title TEXT,
        subtitle TEXT,
        content TEXT,
        icon TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        UNIQUE(page_key, section_key)
    )");
    echo "<p>✅ page_sections table</p>";
    
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
    // TABLE: competitors (FROM SECTION 26)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS competitors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        competitor_key TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        real_monthly_cost REAL,
        min_users INTEGER,
        advertised_price TEXT,
        price_calculation TEXT,
        has_dedicated_server INTEGER DEFAULT 0,
        has_port_forwarding INTEGER DEFAULT 0,
        has_parental_controls INTEGER DEFAULT 0,
        has_camera_dashboard INTEGER DEFAULT 0,
        has_network_scanner INTEGER DEFAULT 0,
        has_2click_setup INTEGER DEFAULT 0,
        has_own_keys INTEGER DEFAULT 0,
        best_for TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ competitors table</p>";
    
    // ============================================
    // TABLE: competitor_comparison (comparison table rows)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS competitor_comparison (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feature_name TEXT NOT NULL,
        truevault_value TEXT,
        goodaccess_value TEXT,
        nordlayer_value TEXT,
        perimeter81_value TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ competitor_comparison table</p>";
    
    // ============================================
    // TABLE: unique_features (features only we offer)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS unique_features (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        icon TEXT,
        title TEXT NOT NULL,
        description TEXT,
        page_key TEXT DEFAULT 'pricing-comparison',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ unique_features table</p>";
    
    // ============================================
    // TABLE: use_cases (who should choose what)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS use_cases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        icon TEXT,
        title TEXT NOT NULL,
        recommendation TEXT,
        description TEXT,
        recommend_us INTEGER DEFAULT 1,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ use_cases table</p>";
    
    // ============================================
    // TABLE: honest_assessment (pros/cons)
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS honest_assessment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL,
        icon TEXT,
        text TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ honest_assessment table</p>";
    
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
    // TABLE: trust_badges
    // ============================================
    $db->exec("CREATE TABLE IF NOT EXISTS trust_badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        text TEXT NOT NULL,
        icon TEXT DEFAULT '✓',
        page_key TEXT DEFAULT 'all',
        sort_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1
    )");
    echo "<p>✅ trust_badges table</p>";
    
    // ============================================
    // TABLE: how_it_works
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
        // BRANDING
        ['site_title', 'TrueVault VPN', 'text', 'branding', 'Site Title', 1],
        ['site_tagline', 'Your Complete Digital Fortress', 'text', 'branding', 'Tagline', 2],
        ['site_logo', '/assets/images/logo.png', 'image', 'branding', 'Logo', 3],
        ['site_favicon', '/assets/images/favicon.ico', 'image', 'branding', 'Favicon', 4],
        ['company_name', 'Connection Point Systems Inc', 'text', 'branding', 'Company Name', 5],
        
        // CTA BUTTONS
        ['cta_primary_text', 'Start Free Trial', 'text', 'cta', 'Primary Button Text', 1],
        ['cta_secondary_text', 'View Pricing', 'text', 'cta', 'Secondary Button Text', 2],
        ['cta_login_text', 'Sign In', 'text', 'cta', 'Login Button Text', 3],
        
        // SUPPORT
        ['support_email', 'admin@the-truth-publishing.com', 'text', 'general', 'Support Email', 1],
        ['notification_email', 'paulhalonen@gmail.com', 'text', 'general', 'Notification Email', 2],
        
        // SEO
        ['seo_default_title', 'TrueVault VPN - Complete Digital Privacy', 'text', 'seo', 'Default Meta Title', 1],
        ['seo_default_description', 'Military-grade VPN with port forwarding, parental controls, and camera management', 'textarea', 'seo', 'Default Meta Description', 2],
        
        // FEATURES
        ['feature_trial_days', '7', 'number', 'features', 'Free Trial Days', 1],
        ['feature_refund_days', '30', 'number', 'features', 'Refund Period Days', 2],
        ['feature_yearly_discount', '17', 'number', 'features', 'Yearly Discount Percent', 3],
        
        // PRICING USD (from user: Personal $9.97/$99.97, Family $14.97/$140.97, Dedicated $39.97/$399.97)
        ['price_personal_monthly_usd', '9.97', 'number', 'pricing', 'Personal Monthly USD', 1],
        ['price_personal_yearly_usd', '99.97', 'number', 'pricing', 'Personal Yearly USD', 2],
        ['price_family_monthly_usd', '14.97', 'number', 'pricing', 'Family Monthly USD', 3],
        ['price_family_yearly_usd', '140.97', 'number', 'pricing', 'Family Yearly USD', 4],
        ['price_dedicated_monthly_usd', '39.97', 'number', 'pricing', 'Dedicated Monthly USD', 5],
        ['price_dedicated_yearly_usd', '399.97', 'number', 'pricing', 'Dedicated Yearly USD', 6],
        
        // PRICING CAD (~35% markup)
        ['price_personal_monthly_cad', '13.47', 'number', 'pricing', 'Personal Monthly CAD', 7],
        ['price_personal_yearly_cad', '134.97', 'number', 'pricing', 'Personal Yearly CAD', 8],
        ['price_family_monthly_cad', '20.21', 'number', 'pricing', 'Family Monthly CAD', 9],
        ['price_family_yearly_cad', '190.31', 'number', 'pricing', 'Family Yearly CAD', 10],
        ['price_dedicated_monthly_cad', '53.96', 'number', 'pricing', 'Dedicated Monthly CAD', 11],
        ['price_dedicated_yearly_cad', '539.97', 'number', 'pricing', 'Dedicated Yearly CAD', 12],
        
        // Hero stats (FROM BLUEPRINT SECTION 1)
        ['hero_stats_encryption', '256-bit', 'text', 'hero', 'Encryption Badge', 1],
        ['hero_stats_encryption_label', 'Military-Grade Encryption', 'text', 'hero', 'Encryption Label', 2],
        ['hero_stats_policy', 'Zero', 'text', 'hero', 'Log Policy Badge', 3],
        ['hero_stats_policy_label', 'Log Policy', 'text', 'hero', 'Policy Label', 4],
        ['hero_stats_servers', '4', 'text', 'hero', 'Server Count', 5],
        ['hero_stats_servers_label', 'Server Locations', 'text', 'hero', 'Servers Label', 6],
        ['hero_stats_setup', '2-Click', 'text', 'hero', 'Setup Badge', 7],
        ['hero_stats_setup_label', 'Device Setup', 'text', 'hero', 'Setup Label', 8],
        
        // UI Text (for pricing page)
        ['ui_save_badge_text', 'Save {percent}%', 'text', 'ui', 'Yearly Save Badge', 1],
        ['ui_popular_badge', 'Most Popular', 'text', 'ui', 'Popular Plan Badge', 2],
        ['ui_compare_plans', 'Compare Plans', 'text', 'ui', 'Compare Plans Title', 3],
        ['ui_billing_faq', 'Billing FAQ', 'text', 'ui', 'Billing FAQ Title', 4],
        ['ui_guarantee_title', '{days}-Day Money-Back Guarantee', 'text', 'ui', 'Guarantee Title', 5],
        ['ui_guarantee_text', 'Try TrueVault VPN risk-free. If you\'re not completely satisfied within {days} days, we\'ll refund 100% of your payment. No questions asked.', 'textarea', 'ui', 'Guarantee Text', 6],
        ['ui_refund_btn_text', 'Learn About Our Refund Policy', 'text', 'ui', 'Refund Button Text', 7],
    ];
    
    foreach ($settings as $s) {
        executeWithParams($db, "INSERT INTO settings (setting_key, setting_value, setting_type, category, label, sort_order) VALUES (?, ?, ?, ?, ?, ?)", $s);
    }
    echo "<p>✅ Settings seeded (" . count($settings) . " items)</p>";
    
    // ===========================================
    // NAVIGATION
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
    
    foreach ($navItems as $nav) {
        executeWithParams($db, "INSERT INTO navigation (location, label, url, sort_order) VALUES (?, ?, ?, ?)", $nav);
    }
    echo "<p>✅ Navigation seeded (" . count($navItems) . " items)</p>";

    
    // ===========================================
    // PAGES
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
         'Business VPN Pricing: The Hidden Costs', 'Business VPNs advertise "$7/user" but require 5-10 minimum users. We tell the truth so you can make smart choices.',
         'Start Free Trial', '/register.php?plan=dedicated'],
    ];
    
    foreach ($pages as $p) {
        executeWithParams($db, "INSERT INTO pages (page_key, page_title, meta_title, meta_description, hero_title, hero_subtitle, hero_cta_text, hero_cta_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $p);
    }
    echo "<p>✅ Pages seeded (" . count($pages) . " items)</p>";
    
    // ===========================================
    // PAGE SECTIONS (for pricing-comparison page)
    // All text content from database - NO hardcoding
    // ===========================================
    $pageSections = [
        // Pricing Comparison Page Sections
        ['pricing-comparison', 'trap_title', 'The "$7/user" Trap', null, null, '⚠️', 1],
        ['pricing-comparison', 'trap_intro', null, null, 'Business VPNs like GoodAccess, NordLayer, and Perimeter 81 advertise low per-user pricing. What they don\'t tell you: they require 5-10 minimum users.', null, 2],
        ['pricing-comparison', 'trap_competitor_title', 'Competitor Pricing', 'Example: "Only $7/user/month!"', null, '❌', 3],
        ['pricing-comparison', 'trap_competitor_calc', null, null, '$7/user × 5 minimum = $35/mo\n+ Platform fee: $20/mo\n+ Dedicated server: $50/mo\n─────────────────────\nACTUAL COST: $105/mo', null, 4],
        ['pricing-comparison', 'trap_competitor_note', null, null, 'Even if you\'re just ONE person, you pay for 5+ users!', null, 5],
        ['pricing-comparison', 'trap_truevault_title', 'TrueVault Pricing', 'Flat rate, no minimums', null, '✅', 6],
        ['pricing-comparison', 'trap_truevault_calc', null, null, 'Dedicated Server Plan: $39.97/mo\nMinimum users: NONE\nHidden fees: NONE\n─────────────────────\nACTUAL COST: $39.97/mo', null, 7],
        ['pricing-comparison', 'trap_truevault_note', null, null, 'Pay only for what you need. Period.', null, 8],
        ['pricing-comparison', 'comparison_title', 'True Cost Comparison', 'What you\'ll actually pay for dedicated VPN services as a single admin or small team', null, null, 9],
        ['pricing-comparison', 'realcost_title', 'The Real Monthly Cost', 'What you\'ll actually pay for dedicated VPN services (for 1 user/small team)', null, null, 10],
        ['pricing-comparison', 'unique_title', 'Features Only TrueVault Offers', 'No other VPN gives you these features - not at any price', null, null, 11],
        ['pricing-comparison', 'choose_title', 'Who Should Choose What?', null, null, null, 12],
        ['pricing-comparison', 'honest_title', 'Honest Assessment', 'We\'re transparent about where we excel and where others might be better', null, null, 13],
        ['pricing-comparison', 'cta_title', 'Ready for Dedicated VPN Without Minimum Users?', null, null, null, 14],
        ['pricing-comparison', 'cta_subtitle', null, null, 'Get your own dedicated server at $39.97/month. No hidden fees.', null, 15],
        ['pricing-comparison', 'badge_no_minimum', 'No minimum users required', null, null, '✓', 16],
        ['pricing-comparison', 'badge_dedicated', 'Dedicated server included', null, null, '✓', 17],
        ['pricing-comparison', 'badge_guarantee', '30-day money-back guarantee', null, null, '✓', 18],
    ];
    
    foreach ($pageSections as $ps) {
        executeWithParams($db, "INSERT INTO page_sections (page_key, section_key, title, subtitle, content, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)", $ps);
    }
    echo "<p>✅ Page sections seeded (" . count($pageSections) . " items)</p>";

    
    // ===========================================
    // COMPETITORS (FROM SECTION 26)
    // ===========================================
    $competitors = [
        ['truevault', 'TrueVault', 39.97, 0, '$39.97/mo', 'Dedicated server INCLUDED\nNo minimum users', 1, 1, 1, 1, 1, 1, 1, 'Individuals, Families, Small Teams', 1],
        ['goodaccess', 'GoodAccess', 74.00, 5, '$10/user', '$10/user × 5 min + $20 platform\n+$50 for dedicated server', 0, 0, 0, 0, 0, 0, 0, 'Mid-size teams', 2],
        ['nordlayer', 'NordLayer', 95.00, 5, '$7/user + $40/yr', '$7/user × 5 min + $40/yr\n+$40/yr per dedicated IP', 0, 0, 0, 0, 0, 0, 0, 'Enterprise', 3],
        ['perimeter81', 'Perimeter 81', 80.00, 10, '$8/user', '$8/user × 10 minimum\nNo dedicated server option', 0, 0, 0, 0, 0, 0, 0, 'Enterprise', 4],
    ];
    
    foreach ($competitors as $c) {
        executeWithParams($db, "INSERT INTO competitors (competitor_key, name, real_monthly_cost, min_users, advertised_price, price_calculation, has_dedicated_server, has_port_forwarding, has_parental_controls, has_camera_dashboard, has_network_scanner, has_2click_setup, has_own_keys, best_for, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $c);
    }
    echo "<p>✅ Competitors seeded (" . count($competitors) . " items)</p>";
    
    // ===========================================
    // COMPETITOR COMPARISON TABLE ROWS
    // ===========================================
    $compComparison = [
        ['Real Monthly Cost (1 Admin)', '$39.97', '$74.00', '$95.00', '$80.00', 1],
        ['Minimum Users Required', 'None', '5 users', '5 users', '10 users', 2],
        ['Dedicated Server', '✓ Included', '+$50/mo', '✗ Not available', '✗ Enterprise only', 3],
        ['2-Click Setup', '✓', '✗', '✗', '✗', 4],
        ['You Own Your Keys', '✓', '✗', '✗', '✗', 5],
        ['Port Forwarding', '✓', '✗', '✗', '✗', 6],
        ['Parental Controls', '✓', '✗', '✗', '✗', 7],
        ['Camera Dashboard', '✓', '✗', '✗', '✗', 8],
        ['Network Scanner', '✓', '✗', '✗', '✗', 9],
        ['Best For', 'Individuals, Families, Small Teams', 'Mid-size teams', 'Enterprise', 'Enterprise', 10],
    ];
    
    foreach ($compComparison as $cc) {
        executeWithParams($db, "INSERT INTO competitor_comparison (feature_name, truevault_value, goodaccess_value, nordlayer_value, perimeter81_value, sort_order) VALUES (?, ?, ?, ?, ?, ?)", $cc);
    }
    echo "<p>✅ Competitor comparison seeded (" . count($compComparison) . " items)</p>";
    
    // ===========================================
    // UNIQUE FEATURES (features only TrueVault offers)
    // ===========================================
    $uniqueFeatures = [
        ['🔌', '2-Click Port Forwarding', 'Port open for gaming, Plex, Minecraft server hosting. No router config needed. Works instantly.', 'pricing-comparison', 1],
        ['👨‍👩‍👧‍👦', 'Built-in Parental Controls', 'Block sites by category, set daily screen time limits, control access by schedule. All per-device.', 'pricing-comparison', 2],
        ['📷', 'Camera Dashboard', 'View Ring/Wyze/Hikvision cameras remotely without cloud subscription fees. No monthly Ring/Nest fees.', 'pricing-comparison', 3],
        ['🔍', 'Network Scanner', 'Auto-discovers home devices - cameras, printers, consoles. One-click sync to VPN for port forwarding.', 'pricing-comparison', 4],
    ];
    
    foreach ($uniqueFeatures as $uf) {
        executeWithParams($db, "INSERT INTO unique_features (icon, title, description, page_key, sort_order) VALUES (?, ?, ?, ?, ?)", $uf);
    }
    echo "<p>✅ Unique features seeded (" . count($uniqueFeatures) . " items)</p>";
    
    // ===========================================
    // USE CASES (who should choose what)
    // ===========================================
    $useCases = [
        ['👤', 'Individuals/Solopreneurs', 'Choose TrueVault Dedicated', 'At $39.97/mo, you get your own dedicated server and unlimited features. No paying for 5+ users you don\'t have.', 1, 1],
        ['🏠', 'Families & Home Users', 'Choose TrueVault', 'Parental controls, camera dashboard, gaming controls. No enterprise VPN offers these family-friendly features.', 1, 2],
        ['🎮', 'Gamers', 'Choose TrueVault', 'Only VPN with 2-click port forwarding for open NAT type. Xbox, PlayStation, PC gaming all supported.', 1, 3],
        ['📷', 'IP Camera Users', 'Choose TrueVault', 'View cameras remotely without Ring/Nest monthly fees. Works with any camera brand.', 1, 4],
        ['🏢', 'Teams 10+ People', 'Consider GoodAccess or NordLayer', 'At scale, per-user pricing becomes economical. If you need SSO, compliance certs, enterprise features.', 0, 5],
        ['🔐', 'Enterprise Security', 'Consider Perimeter 81 or Tailscale', 'For zero-trust architecture, SOC2 compliance, dedicated IT deployment requirements.', 0, 6],
    ];
    
    foreach ($useCases as $uc) {
        executeWithParams($db, "INSERT INTO use_cases (icon, title, recommendation, description, recommend_us, sort_order) VALUES (?, ?, ?, ?, ?, ?)", $uc);
    }
    echo "<p>✅ Use cases seeded (" . count($useCases) . " items)</p>";
    
    // ===========================================
    // HONEST ASSESSMENT (pros/cons)
    // ===========================================
    $honestAssessment = [
        ['advantage', '✓', 'No minimum users - Pay only for what you need', 1],
        ['advantage', '✓', 'Actual dedicated server (not just dedicated IP)', 2],
        ['advantage', '✓', 'Port forwarding - Only we offer this', 3],
        ['advantage', '✓', 'Parental controls built-in', 4],
        ['advantage', '✓', 'Camera dashboard - No cloud fees', 5],
        ['advantage', '✓', 'Simple 2-click setup - No IT needed', 6],
        ['limitation', '⚠', 'Large teams (10+) - Per-user pricing cheaper at scale', 1],
        ['limitation', '⚠', 'Compliance needs - SOC2, HIPAA, GDPR certs', 2],
        ['limitation', '⚠', 'SSO/Identity - Enterprise identity management', 3],
        ['limitation', '⚠', 'Global servers - We have 4 regions, they have 50+', 4],
        ['limitation', '⚠', 'Team management - Role-based access, provisioning', 5],
    ];
    
    foreach ($honestAssessment as $ha) {
        executeWithParams($db, "INSERT INTO honest_assessment (type, icon, text, sort_order) VALUES (?, ?, ?, ?)", $ha);
    }
    echo "<p>✅ Honest assessment seeded (" . count($honestAssessment) . " items)</p>";

    
    // ===========================================
    // PRICING PLANS (FROM USER + SECTION 25)
    // ===========================================
    $plans = [
        ['personal', 'Personal', 'Perfect for individual use', 9.97, 99.97, 13.47, 134.97, 
         '["3 VPN Devices","All Server Locations","Port Forwarding (2 devices)","Network Scanner","1 Camera","Parental Controls","24/7 Email Support"]',
         'Get Started', '/register.php?plan=personal', 0, 1],
        ['family', 'Family', 'Great for families', 14.97, 140.97, 20.21, 190.31,
         '["5 VPN Devices","All Server Locations","Port Forwarding (5 devices)","Network Scanner","2 Cameras","Parental Controls","Gaming Controls","Priority Support"]',
         'Get Started', '/register.php?plan=family', 1, 2],
        ['dedicated', 'Dedicated', 'For power users', 39.97, 399.97, 53.96, 539.97,
         '["99 VPN Devices","All Server Locations","Unlimited Port Forwarding","Network Scanner","Unlimited Cameras","Parental Controls","Gaming Controls","Dedicated Server","Priority Support"]',
         'Get Started', '/register.php?plan=dedicated', 0, 3],
    ];
    
    foreach ($plans as $p) {
        executeWithParams($db, "INSERT INTO pricing_plans (plan_key, plan_name, description, price_monthly_usd, price_yearly_usd, price_monthly_cad, price_yearly_cad, features, cta_text, cta_url, is_popular, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $p);
    }
    echo "<p>✅ Pricing plans seeded (" . count($plans) . " items)</p>";
    
    // ===========================================
    // PLAN COMPARISON (FROM SECTION 25 - EXACT LIMITS)
    // ===========================================
    $planComparison = [
        ['VPN Devices', '3', '5', '99', 1],
        ['Home Network Devices', '3', '5', '99', 2],
        ['Cameras', '1', '2', 'Unlimited', 3],
        ['Port Forwarding Devices', '2', '5', 'Unlimited', 4],
        ['Server Locations', '4', '4', '4 + Dedicated', 5],
        ['Parental Controls', '✓', '✓', '✓', 6],
        ['Gaming Controls', '✗', '✓', '✓', 7],
        ['Network Scanner', '✓', '✓', '✓', 8],
        ['Camera Dashboard', '✓', '✓', '✓', 9],
        ['Port Forwarding', 'NY Contabo Only', 'NY Contabo Only', 'All Servers', 10],
        ['Dedicated Server', '✗', '✗', '✓', 11],
        ['Support', 'Email', 'Priority', 'Priority', 12],
    ];
    
    foreach ($planComparison as $pc) {
        executeWithParams($db, "INSERT INTO plan_comparison (feature_name, personal_value, family_value, dedicated_value, sort_order) VALUES (?, ?, ?, ?, ?)", $pc);
    }
    echo "<p>✅ Plan comparison seeded (" . count($planComparison) . " items)</p>";
    
    // ===========================================
    // FEATURES (FROM BLUEPRINT SECTION 1)
    // ===========================================
    $features = [
        ['🛡️', 'Military-Grade Encryption', 'WireGuard protocol with 256-bit encryption. State-of-the-art security that\'s been audited by researchers.', 'core', 1],
        ['⚡', '2-Click Device Setup', 'No confusing configs. Browser generates your keys. Scan QR code. Done in under 2 minutes.', 'core', 2],
        ['🌍', '4 Server Locations', 'New York, St. Louis, Dallas, and Toronto. All using fast WireGuard protocol.', 'core', 3],
        ['📋', 'Zero-Logs Policy', 'We don\'t track, store, or share your browsing. Only your email for account management.', 'core', 4],
        ['👨‍👩‍👧‍👦', 'Parental Controls', 'Block sites by category, set screen time limits, control access by schedule. All per-device.', 'unique', 5],
        ['📷', 'Camera Dashboard', 'View your IP cameras remotely without paying Ring/Nest monthly fees. Works with any brand.', 'unique', 6],
        ['🔌', 'Port Forwarding', 'One-click port forwarding for gaming, Plex, cameras. No router config needed.', 'unique', 7],
        ['🔍', 'Network Scanner', 'Auto-discovers all devices on your home network. Cameras, printers, consoles, and more.', 'unique', 8],
        ['🎮', 'Gaming Controls', 'Set specific hours when gaming consoles can access the internet. Perfect for kids.', 'unique', 9],
        ['📱', 'Multi-Device Support', '3, 5, or 99 devices depending on plan. One account, all your devices.', 'plan', 10],
        ['🖥️', 'Dedicated Server Option', 'Get your own private server. Not shared with anyone. Ultimate privacy.', 'plan', 11],
        ['💬', '24/7 Support', 'Email support with fast response times. We\'re here when you need us.', 'plan', 12],
    ];
    
    foreach ($features as $f) {
        executeWithParams($db, "INSERT INTO features (icon, title, description, category, sort_order) VALUES (?, ?, ?, ?, ?)", $f);
    }
    echo "<p>✅ Features seeded (" . count($features) . " items)</p>";
    
    // ===========================================
    // TRUST BADGES
    // ===========================================
    $trustBadges = [
        ['7-Day Free Trial', '✓', 'all', 1],
        ['No Credit Card Required', '✓', 'all', 2],
        ['30-Day Money Back', '✓', 'all', 3],
        ['256-bit Encryption', '🔒', 'all', 4],
        ['Dedicated server included', '✓', 'pricing-comparison', 5],
        ['No minimum users', '✓', 'pricing-comparison', 6],
    ];
    
    foreach ($trustBadges as $tb) {
        executeWithParams($db, "INSERT INTO trust_badges (text, icon, page_key, sort_order) VALUES (?, ?, ?, ?)", $tb);
    }
    echo "<p>✅ Trust badges seeded (" . count($trustBadges) . " items)</p>";

    
    // ===========================================
    // HOW IT WORKS
    // ===========================================
    $steps = [
        [1, 'Choose Your Plan', 'Select Personal, Family, or Dedicated based on your needs. Start with a 7-day free trial.', '1️⃣', 1],
        [2, 'Download Config', 'We generate your WireGuard config instantly. Browser creates keys - we never see them.', '2️⃣', 2],
        [3, 'Scan & Connect', 'Open WireGuard app, scan QR code, tap connect. Done in under 2 minutes.', '3️⃣', 3],
    ];
    
    foreach ($steps as $s) {
        executeWithParams($db, "INSERT INTO how_it_works (step_number, title, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)", $s);
    }
    echo "<p>✅ How it works seeded (" . count($steps) . " items)</p>";
    
    // ===========================================
    // FEATURE COMPARISON (TrueVault vs Traditional VPN)
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
    
    foreach ($vsComparison as $v) {
        executeWithParams($db, "INSERT INTO feature_comparison (feature_name, traditional_vpn, truevault, sort_order) VALUES (?, ?, ?, ?)", $v);
    }
    echo "<p>✅ Feature comparison seeded (" . count($vsComparison) . " items)</p>";
    
    // ===========================================
    // TESTIMONIALS
    // ===========================================
    $testimonials = [
        ['Sarah M.', 'Small Business Owner', 'Finally a VPN that works with my IP cameras! I can check on my shop from anywhere without paying Ring monthly fees. The camera dashboard is a game-changer.', 5, 1],
        ['James K.', 'Parent of 3', 'The parental controls are exactly what I needed. I can set screen time limits and block inappropriate content on all my kids devices. Setup took 2 minutes.', 5, 1],
        ['Mike R.', 'IT Professional', 'As someone who knows networking, I am impressed by how simple they made WireGuard configuration. 2-click setup is no joke — it actually works.', 5, 1],
        ['Lisa T.', 'Work From Home Mom', 'I love that I can manage my kids gaming time while I work. The gaming controls let me set specific hours for Xbox and PlayStation.', 5, 0],
        ['Dave P.', 'Gamer', 'Port forwarding was always a nightmare with my old VPN. Now I just click one button and my NAT type is open. Perfect for online gaming.', 5, 0],
    ];
    
    foreach ($testimonials as $t) {
        executeWithParams($db, "INSERT INTO testimonials (name, role, content, rating, is_featured) VALUES (?, ?, ?, ?, ?)", $t);
    }
    echo "<p>✅ Testimonials seeded (" . count($testimonials) . " items)</p>";
    
    // ===========================================
    // FAQS
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
    
    foreach ($faqs as $faq) {
        executeWithParams($db, "INSERT INTO faqs (question, answer, category, sort_order) VALUES (?, ?, ?, ?)", $faq);
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
    echo "<li><strong>Competitors:</strong> SECTION_26_PRICING_COMPARISON.md - GoodAccess, NordLayer, Perimeter 81</li>";
    echo "</ul>";
    echo "<p><a href='/'>Go to Homepage</a> | <a href='/pricing.php'>View Pricing</a> | <a href='/pricing-comparison.php'>View Comparison</a></p>";
    echo "<p style='color:red; font-size:1.2em'><strong>⚠️ DELETE THIS setup.php FILE FOR SECURITY!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
