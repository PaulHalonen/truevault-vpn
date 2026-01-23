<?php
/**
 * TrueVault VPN - Content Database Setup
 * Part 12 - Task 12.1
 * Creates database tables for landing pages, settings, and navigation
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><title>TrueVault - Content DB Setup</title>
<style>body{font-family:system-ui;background:#0f0f1a;color:#fff;padding:40px;max-width:900px;margin:0 auto;}
h1{color:#00d9ff;}h2{color:#00ff88;margin-top:30px;}.success{color:#00ff88;}.error{color:#ff5050;}
pre{background:#1a1a2e;padding:15px;border-radius:8px;overflow-x:auto;font-size:13px;}</style></head><body>";

echo "<h1>🚀 TrueVault Content Database Setup</h1>";

$dbPath = __DIR__ . '/content.db';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Database connected: $dbPath</p>";

    // ==================== TABLE 1: pages ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_key TEXT UNIQUE NOT NULL,
            page_title TEXT NOT NULL,
            meta_title TEXT,
            meta_description TEXT,
            meta_keywords TEXT,
            hero_title TEXT,
            hero_subtitle TEXT,
            hero_cta_text TEXT,
            hero_cta_url TEXT,
            sections TEXT,
            custom_css TEXT,
            custom_js TEXT,
            is_published INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'pages' created</p>";

    // ==================== TABLE 2: settings ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type TEXT DEFAULT 'text',
            category TEXT,
            label TEXT,
            description TEXT,
            sort_order INTEGER DEFAULT 0,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'settings' created</p>";

    // ==================== TABLE 3: navigation ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS navigation (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            location TEXT NOT NULL,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            icon TEXT,
            parent_id INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            open_new_tab INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'navigation' created</p>";

    // ==================== TABLE 4: pricing_plans ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS pricing_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plan_key TEXT UNIQUE NOT NULL,
            plan_name TEXT NOT NULL,
            price_monthly REAL,
            price_yearly REAL,
            description TEXT,
            features TEXT,
            is_popular INTEGER DEFAULT 0,
            cta_text TEXT DEFAULT 'Get Started',
            cta_url TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'pricing_plans' created</p>";

    // ==================== TABLE 5: features ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS features (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            feature_key TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            icon TEXT,
            image_url TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'features' created</p>";

    // ==================== TABLE 6: faqs ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS faqs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            category TEXT DEFAULT 'general',
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'faqs' created</p>";

    // ==================== TABLE 7: testimonials ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            author_name TEXT NOT NULL,
            author_title TEXT,
            author_image TEXT,
            content TEXT NOT NULL,
            rating INTEGER DEFAULT 5,
            is_featured INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'testimonials' created</p>";

    // ==================== TABLE 8: contact_submissions ====================
    $db->exec("
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            subject TEXT,
            message TEXT NOT NULL,
            ip_address TEXT,
            is_read INTEGER DEFAULT 0,
            is_replied INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Table 'contact_submissions' created</p>";

    echo "<h2>📝 Seeding Default Data...</h2>";

    // ==================== SEED: Settings ====================
    $settings = [
        // Branding
        ['site_name', 'TrueVault VPN', 'text', 'branding', 'Site Name', 'The name of your VPN service', 1],
        ['site_tagline', 'Your Complete Digital Fortress', 'text', 'branding', 'Tagline', 'Short tagline shown in header', 2],
        ['site_logo', '/assets/images/logo.png', 'image', 'branding', 'Logo', 'Main site logo (recommended 200x50)', 3],
        ['site_favicon', '/assets/images/favicon.ico', 'image', 'branding', 'Favicon', '16x16 or 32x32 icon', 4],
        ['footer_text', '© 2026 TrueVault VPN. All rights reserved.', 'text', 'branding', 'Footer Text', 'Copyright text in footer', 5],
        
        // Contact
        ['contact_email', 'support@truevault.com', 'text', 'contact', 'Support Email', 'Main support email', 1],
        ['contact_phone', '', 'text', 'contact', 'Phone Number', 'Optional phone number', 2],
        ['contact_address', '', 'textarea', 'contact', 'Address', 'Physical address (optional)', 3],
        
        // Social
        ['social_twitter', '', 'text', 'social', 'Twitter/X URL', 'Twitter profile URL', 1],
        ['social_facebook', '', 'text', 'social', 'Facebook URL', 'Facebook page URL', 2],
        ['social_instagram', '', 'text', 'social', 'Instagram URL', 'Instagram profile URL', 3],
        ['social_youtube', '', 'text', 'social', 'YouTube URL', 'YouTube channel URL', 4],
        
        // SEO
        ['seo_title_suffix', ' | TrueVault VPN', 'text', 'seo', 'Title Suffix', 'Added to all page titles', 1],
        ['seo_default_description', 'TrueVault VPN - Secure, private, and fast VPN service with personal certificates and smart identity routing.', 'textarea', 'seo', 'Default Description', 'Default meta description', 2],
        ['seo_default_keywords', 'VPN, secure VPN, private VPN, certificate VPN, identity protection', 'textarea', 'seo', 'Default Keywords', 'Default meta keywords', 3],
        
        // CTA
        ['cta_primary_text', 'Start Free Trial', 'text', 'cta', 'Primary CTA Text', 'Main call-to-action button text', 1],
        ['cta_primary_url', '/register.php', 'text', 'cta', 'Primary CTA URL', 'Where the main CTA button goes', 2],
        ['cta_secondary_text', 'View Pricing', 'text', 'cta', 'Secondary CTA Text', 'Secondary button text', 3],
        ['cta_secondary_url', '/pricing.php', 'text', 'cta', 'Secondary CTA URL', 'Where the secondary button goes', 4],
        
        // Features
        ['hero_stats_encryption', '256-bit', 'text', 'hero', 'Encryption Level', 'Shown in hero stats', 1],
        ['hero_stats_policy', 'Zero', 'text', 'hero', 'Log Policy', 'Shown in hero stats', 2],
        ['hero_stats_servers', '50+', 'text', 'hero', 'Server Count', 'Number of servers/countries', 3],
        ['hero_stats_devices', '∞', 'text', 'hero', 'Device Limit', 'Devices per account', 4],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_type, category, label, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($settings as $s) { $stmt->execute($s); }
    echo "<p class='success'>✅ Settings seeded (" . count($settings) . " entries)</p>";

    // ==================== SEED: Navigation ====================
    $navItems = [
        // Header
        ['header', 'Features', '/features.php', '⚡', 0, 1, 0],
        ['header', 'Pricing', '/pricing.php', '💰', 0, 2, 0],
        ['header', 'About', '/about.php', '📖', 0, 3, 0],
        ['header', 'Contact', '/contact.php', '📧', 0, 4, 0],
        
        // Footer - Company
        ['footer_company', 'About Us', '/about.php', '', 0, 1, 0],
        ['footer_company', 'Contact', '/contact.php', '', 0, 2, 0],
        ['footer_company', 'Blog', '/blog.php', '', 0, 3, 0],
        
        // Footer - Legal
        ['footer_legal', 'Privacy Policy', '/privacy.php', '', 0, 1, 0],
        ['footer_legal', 'Terms of Service', '/terms.php', '', 0, 2, 0],
        ['footer_legal', 'Refund Policy', '/refund.php', '', 0, 3, 0],
        
        // Footer - Support
        ['footer_support', 'Help Center', '/help.php', '', 0, 1, 0],
        ['footer_support', 'Downloads', '/downloads.php', '', 0, 2, 0],
        ['footer_support', 'Server Status', '/status.php', '', 0, 3, 0],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO navigation (location, label, url, icon, parent_id, sort_order, open_new_tab) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($navItems as $n) { $stmt->execute($n); }
    echo "<p class='success'>✅ Navigation seeded (" . count($navItems) . " entries)</p>";

    // ==================== SEED: Pages ====================
    $pages = [
        [
            'homepage', 'Home', 'TrueVault VPN - Your Complete Digital Fortress',
            'Secure your digital life with TrueVault VPN. Personal certificates, smart identity routing, and military-grade encryption.',
            'VPN, secure, private, certificates',
            'Your Complete Digital Fortress',
            'The world\'s first all-in-one privacy platform combining persistent digital identities, encrypted mesh networking, decentralized routing, and personal certificate authority — all owned and controlled by YOU.',
            'Start Your Free Trial', '/register.php'
        ],
        [
            'pricing', 'Pricing', 'Pricing Plans - TrueVault VPN',
            'Choose the perfect TrueVault VPN plan for your needs. Personal, Family, and Business plans available.',
            'VPN pricing, VPN plans, VPN subscription',
            'Choose Your Plan',
            'Simple, transparent pricing with no hidden fees. All plans include our core security features.',
            'Start Free Trial', '/register.php'
        ],
        [
            'features', 'Features', 'Features - TrueVault VPN',
            'Explore TrueVault VPN features: Smart Identity Router, Mesh Networking, Personal Certificates, and more.',
            'VPN features, smart routing, mesh network, certificates',
            'Revolutionary Features',
            'TrueVault isn\'t just another VPN. It\'s a complete digital identity and privacy platform.',
            'See All Features', '#features'
        ],
        [
            'about', 'About', 'About Us - TrueVault VPN',
            'Learn about TrueVault VPN, our mission, and our commitment to your privacy.',
            'about VPN, VPN company, privacy mission',
            'About TrueVault',
            'We believe everyone deserves true digital privacy. That\'s why we built TrueVault.',
            'Join Us', '/register.php'
        ],
        [
            'contact', 'Contact', 'Contact Us - TrueVault VPN',
            'Get in touch with TrueVault VPN support. We\'re here to help.',
            'contact VPN, VPN support, help',
            'Get In Touch',
            'Have questions? We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.',
            'Send Message', '#contact-form'
        ],
        [
            'privacy', 'Privacy Policy', 'Privacy Policy - TrueVault VPN',
            'TrueVault VPN Privacy Policy. Learn how we protect your data.',
            'privacy policy, data protection, VPN privacy',
            'Privacy Policy',
            'Your privacy is our priority. Read our complete privacy policy below.',
            '', ''
        ],
        [
            'terms', 'Terms of Service', 'Terms of Service - TrueVault VPN',
            'TrueVault VPN Terms of Service. Read our terms and conditions.',
            'terms of service, terms and conditions, VPN terms',
            'Terms of Service',
            'Please read these terms carefully before using TrueVault VPN.',
            '', ''
        ],
        [
            'refund', 'Refund Policy', 'Refund Policy - TrueVault VPN',
            'TrueVault VPN Refund Policy. 30-day money-back guarantee.',
            'refund policy, money back, VPN refund',
            'Refund Policy',
            'We offer a 30-day money-back guarantee. No questions asked.',
            '', ''
        ],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO pages (page_key, page_title, meta_title, meta_description, meta_keywords, hero_title, hero_subtitle, hero_cta_text, hero_cta_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($pages as $p) { $stmt->execute($p); }
    echo "<p class='success'>✅ Pages seeded (" . count($pages) . " entries)</p>";

    // ==================== SEED: Pricing Plans ====================
    $plans = [
        ['personal', 'Personal', 9.99, 99.99, 'Perfect for individual users', 
         '["5 Devices","Personal Certificates","3 Regional Identities","Smart Routing","24/7 Support"]',
         0, 'Get Started', '/register.php?plan=personal', 1],
        ['family', 'Family', 14.99, 149.99, 'Protect your whole family',
         '["Unlimited Devices","Full Certificate Suite","All Regional Identities","Mesh Networking (6 users)","Priority Support","Bandwidth Rewards"]',
         1, 'Most Popular', '/register.php?plan=family', 2],
        ['business', 'Business', 29.99, 299.99, 'For teams and organizations',
         '["Unlimited Everything","Enterprise Certificates","Team Mesh (25 users)","Admin Dashboard","API Access","Dedicated Support"]',
         0, 'Contact Sales', '/contact.php?type=business', 3],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO pricing_plans (plan_key, plan_name, price_monthly, price_yearly, description, features, is_popular, cta_text, cta_url, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($plans as $p) { $stmt->execute($p); }
    echo "<p class='success'>✅ Pricing plans seeded (" . count($plans) . " entries)</p>";

    // ==================== SEED: Features ====================
    $features = [
        ['smart_identity', 'Smart Identity Router', 'Maintain persistent "digital identities" for different regions. Your Canadian banking identity stays consistent — same IP, same fingerprint, same behavior. Banks never flag you as suspicious VPN traffic again.', '🎭', '', 1],
        ['mesh_network', 'Family Mesh Network', 'Connect all your devices and trusted family members as if you\'re on the same local network — regardless of where you are physically. Remote tech support without complicated port forwarding.', '🏠', '', 2],
        ['decentralized', 'Decentralized Network', 'Traffic routes through thousands of residential nodes worldwide. No central servers to subpoena or shut down. Contribute bandwidth, earn credits. True peer-to-peer privacy.', '🌐', '', 3],
        ['ai_routing', 'AI-Powered Routing', 'Our smart system learns your habits. Banking? Trusted route, minimal hops. Browsing news? Privacy-optimized multi-hop. Gaming? Lowest latency path. All automatic.', '🤖', '', 4],
        ['certificates', 'Personal Certificate Authority', 'You own your encryption. We generate YOUR personal certificate infrastructure — not even we can decrypt your traffic. True end-to-end encryption where you hold all the keys.', '🔐', '', 5],
        ['invisible', 'Invisible Mode', 'Advanced traffic obfuscation makes your VPN traffic look like normal HTTPS. Bypass corporate firewalls, government blocks, and VPN detection systems. Stay connected anywhere.', '👻', '', 6],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO features (feature_key, title, description, icon, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($features as $f) { $stmt->execute($f); }
    echo "<p class='success'>✅ Features seeded (" . count($features) . " entries)</p>";

    // ==================== SEED: FAQs ====================
    $faqs = [
        ['What makes TrueVault different from other VPNs?', 'TrueVault isn\'t just a VPN — it\'s a complete digital identity platform. We offer personal certificates, persistent regional identities, mesh networking, and decentralized routing that no other VPN provides.', 'general', 1],
        ['How does the personal certificate system work?', 'When you sign up, we generate a unique cryptographic certificate infrastructure just for you. This means your traffic is encrypted with keys that only you control — not even TrueVault can decrypt it.', 'technical', 2],
        ['Can I share my account with family?', 'Yes! Our Family and Business plans support multiple users with mesh networking. Each family member gets their own identity while being able to securely connect to shared resources.', 'billing', 3],
        ['What is your refund policy?', 'We offer a 30-day money-back guarantee, no questions asked. If you\'re not completely satisfied, contact support for a full refund.', 'billing', 4],
        ['Do you keep any logs?', 'Absolutely not. We have a strict zero-log policy. We don\'t track your browsing, don\'t store connection logs, and don\'t monitor your traffic. Your privacy is our priority.', 'privacy', 5],
        ['What devices are supported?', 'TrueVault works on Windows, Mac, Linux, iOS, Android, and routers. Our Family plan supports unlimited devices.', 'technical', 6],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO faqs (question, answer, category, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($faqs as $f) { $stmt->execute($f); }
    echo "<p class='success'>✅ FAQs seeded (" . count($faqs) . " entries)</p>";

    // ==================== SEED: Testimonials ====================
    $testimonials = [
        ['Sarah M.', 'Software Engineer', '', 'TrueVault\'s certificate system is brilliant. As a security professional, I finally trust a VPN with my data. The mesh networking lets me access my home server from anywhere.', 5, 1],
        ['James K.', 'Digital Nomad', '', 'I\'ve tried dozens of VPNs while traveling. TrueVault is the only one that consistently bypasses restrictions in China and the UAE. The invisible mode actually works!', 5, 1],
        ['The Martinez Family', 'Family Plan Users', '', 'Setting up parental controls was so easy. The kids can\'t bypass it, and we can monitor everything from one dashboard. Worth every penny for peace of mind.', 5, 1],
    ];

    $stmt = $db->prepare("INSERT OR IGNORE INTO testimonials (author_name, author_title, author_image, content, rating, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($testimonials as $t) { $stmt->execute($t); }
    echo "<p class='success'>✅ Testimonials seeded (" . count($testimonials) . " entries)</p>";

    echo "<h2>✅ All Done!</h2>";
    echo "<p>Database setup complete. You can now use the landing pages.</p>";
    echo "<p><a href='/index.php' style='color:#00d9ff;'>→ View Homepage</a></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
