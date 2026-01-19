<?php
/**
 * TrueVault VPN - Insert Global Site Settings
 * 
 * Inserts 25+ essential site settings
 * All customizable via admin panel
 * ZERO hardcoded values in frontend
 * 
 * Categories:
 * - General (site title, contact info)
 * - Branding (logo, theme)
 * - SEO (meta tags, analytics)
 * - Social (social media links)
 * - Features (maintenance mode, registration)
 * - Pricing (display prices)
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Database path
$dbPath = __DIR__ . '/../databases/themes.db';

if (!file_exists($dbPath)) {
    die("ERROR: themes.db not found! Run setup-themes-database.php first.");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>TrueVault VPN - Insert Site Settings</h1>\n";
    echo "<pre>\n";
    
    // ============================================
    // SITE SETTINGS
    // ============================================
    
    $settings = [
        // GENERAL
        [
            'key' => 'site_title',
            'value' => 'TrueVault VPN',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Website title shown in browser tabs'
        ],
        [
            'key' => 'site_tagline',
            'value' => 'Your Complete Digital Fortress',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Short description of your service'
        ],
        [
            'key' => 'site_logo',
            'value' => '/assets/logo.png',
            'type' => 'image',
            'category' => 'general',
            'description' => 'Path to site logo image'
        ],
        [
            'key' => 'site_favicon',
            'value' => '/assets/favicon.ico',
            'type' => 'image',
            'category' => 'general',
            'description' => 'Path to favicon'
        ],
        [
            'key' => 'contact_email',
            'value' => 'support@vpn.the-truth-publishing.com',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Public contact email'
        ],
        [
            'key' => 'support_email',
            'value' => 'support@vpn.the-truth-publishing.com',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Support email for tickets'
        ],
        [
            'key' => 'admin_email',
            'value' => 'admin@vpn.the-truth-publishing.com',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Admin notification email'
        ],
        [
            'key' => 'company_name',
            'value' => 'TrueVault VPN',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Company/business name'
        ],
        [
            'key' => 'company_address',
            'value' => '',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Company address (optional)'
        ],
        [
            'key' => 'support_phone',
            'value' => '',
            'type' => 'text',
            'category' => 'general',
            'description' => 'Support phone number (optional)'
        ],
        
        // BRANDING
        [
            'key' => 'active_theme_id',
            'value' => '3',
            'type' => 'number',
            'category' => 'branding',
            'description' => 'ID of currently active theme'
        ],
        [
            'key' => 'enable_seasonal_themes',
            'value' => '1',
            'type' => 'boolean',
            'category' => 'branding',
            'description' => 'Automatically switch themes based on season'
        ],
        [
            'key' => 'logo_width',
            'value' => '180',
            'type' => 'number',
            'category' => 'branding',
            'description' => 'Logo width in pixels'
        ],
        [
            'key' => 'logo_height',
            'value' => '50',
            'type' => 'number',
            'category' => 'branding',
            'description' => 'Logo height in pixels'
        ],
        
        // SEO
        [
            'key' => 'meta_description',
            'value' => 'Advanced VPN with parental controls, port forwarding, camera dashboard, and 2-click device setup. Zero logs, military-grade encryption.',
            'type' => 'text',
            'category' => 'seo',
            'description' => 'Default meta description for SEO'
        ],
        [
            'key' => 'meta_keywords',
            'value' => 'VPN, privacy, security, parental controls, port forwarding, camera dashboard, zero logs',
            'type' => 'text',
            'category' => 'seo',
            'description' => 'Default meta keywords'
        ],
        [
            'key' => 'google_analytics_id',
            'value' => '',
            'type' => 'text',
            'category' => 'seo',
            'description' => 'Google Analytics tracking ID (optional)'
        ],
        [
            'key' => 'google_site_verification',
            'value' => '',
            'type' => 'text',
            'category' => 'seo',
            'description' => 'Google Search Console verification code'
        ],
        
        // SOCIAL MEDIA
        [
            'key' => 'facebook_url',
            'value' => '',
            'type' => 'text',
            'category' => 'social',
            'description' => 'Facebook page URL (optional)'
        ],
        [
            'key' => 'twitter_url',
            'value' => '',
            'type' => 'text',
            'category' => 'social',
            'description' => 'Twitter/X profile URL (optional)'
        ],
        [
            'key' => 'linkedin_url',
            'value' => '',
            'type' => 'text',
            'category' => 'social',
            'description' => 'LinkedIn page URL (optional)'
        ],
        [
            'key' => 'youtube_url',
            'value' => '',
            'type' => 'text',
            'category' => 'social',
            'description' => 'YouTube channel URL (optional)'
        ],
        
        // FEATURES
        [
            'key' => 'maintenance_mode',
            'value' => '0',
            'type' => 'boolean',
            'category' => 'features',
            'description' => 'Put site in maintenance mode'
        ],
        [
            'key' => 'enable_registration',
            'value' => '1',
            'type' => 'boolean',
            'category' => 'features',
            'description' => 'Allow new user registrations'
        ],
        [
            'key' => 'enable_free_trial',
            'value' => '1',
            'type' => 'boolean',
            'category' => 'features',
            'description' => 'Offer 7-day free trial'
        ],
        [
            'key' => 'trial_days',
            'value' => '7',
            'type' => 'number',
            'category' => 'features',
            'description' => 'Number of free trial days'
        ],
        
        // PRICING (for display purposes)
        [
            'key' => 'price_standard',
            'value' => '9.99',
            'type' => 'number',
            'category' => 'pricing',
            'description' => 'Standard plan price per month'
        ],
        [
            'key' => 'price_pro',
            'value' => '14.99',
            'type' => 'number',
            'category' => 'pricing',
            'description' => 'Pro plan price per month'
        ],
        [
            'key' => 'currency_symbol',
            'value' => '$',
            'type' => 'text',
            'category' => 'pricing',
            'description' => 'Currency symbol to display'
        ],
        [
            'key' => 'currency_code',
            'value' => 'USD',
            'type' => 'text',
            'category' => 'pricing',
            'description' => 'Currency code (USD, CAD, EUR, etc.)'
        ],
    ];
    
    // ============================================
    // INSERT SETTINGS
    // ============================================
    
    echo "Inserting site settings...\n\n";
    
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($settings as $setting) {
        // Check if setting exists
        $stmt = $db->prepare("SELECT id, setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$setting['key']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE site_settings 
                SET setting_value = ?, setting_type = ?, category = ?, description = ?, updated_at = CURRENT_TIMESTAMP
                WHERE setting_key = ?
            ");
            $stmt->execute([
                $setting['value'],
                $setting['type'],
                $setting['category'],
                $setting['description'],
                $setting['key']
            ]);
            echo "  ↻ {$setting['key']} - Updated\n";
            $updatedCount++;
        } else {
            // Insert new
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value, setting_type, category, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $setting['key'],
                $setting['value'],
                $setting['type'],
                $setting['category'],
                $setting['description']
            ]);
            echo "  ✓ {$setting['key']} - Inserted\n";
            $insertedCount++;
        }
    }
    
    // ============================================
    // VERIFICATION
    // ============================================
    
    echo "\n";
    echo "Verification by category:\n";
    
    $categories = ['general', 'branding', 'seo', 'social', 'features', 'pricing'];
    
    foreach ($categories as $category) {
        $count = $db->query("SELECT COUNT(*) FROM site_settings WHERE category = '$category'")->fetchColumn();
        echo "  $category: $count settings\n";
    }
    
    $totalSettings = $db->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    
    echo "\n";
    echo "========================================\n";
    echo "SITE SETTINGS INSERTED SUCCESSFULLY!\n";
    echo "========================================\n";
    echo "New settings: $insertedCount\n";
    echo "Updated settings: $updatedCount\n";
    echo "Total settings: $totalSettings\n";
    echo "\n";
    echo "Test in your code:\n";
    echo "Content::get('site_title')\n";
    echo "Content::get('contact_email')\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Create Theme.php helper class\n";
    echo "2. Create Content.php helper class\n";
    echo "3. Update Database.php to include themes.db\n";
    echo "4. Test settings in frontend\n";
    echo "\n</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "ERROR: Failed to insert site settings!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "</pre>";
    exit(1);
}
?>
