<?php
/**
 * TrueVault VPN - Content Functions
 * Part 12 - Database-driven content helpers
 * ALL landing page content comes from database
 * NO HARDCODING - Everything from content.db
 */

/**
 * Get content database connection
 */
function getContentDB() {
    static $db = null;
    if ($db === null) {
        $dbPath = __DIR__ . '/../databases/content.db';
        if (!file_exists($dbPath)) {
            die("Database not found. Please run /setup.php first.");
        }
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

/**
 * Get a single setting value
 */
function getSetting($key, $default = '') {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Get multiple settings by category
 */
function getSettingsByCategory($category) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE category = ? ORDER BY sort_order");
        $stmt->execute([$category]);
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all settings as key-value array
 */
function getAllSettings() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get navigation items by location
 */
function getNavigation($location) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM navigation WHERE location = ? AND is_active = 1 ORDER BY sort_order");
        $stmt->execute([$location]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get page data by key
 */
function getPage($pageKey) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM pages WHERE page_key = ? AND is_published = 1");
        $stmt->execute([$pageKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get pricing plans with decoded features
 */
function getPricingPlans() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM pricing_plans WHERE is_active = 1 ORDER BY sort_order");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true) ?: [];
            // Add shorthand for templates
            $plan['price_monthly'] = $plan['price_monthly_usd'];
            $plan['price_yearly'] = $plan['price_yearly_usd'];
        }
        return $plans;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get plan comparison table data (FROM SECTION 25)
 */
function getPlanComparison() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM plan_comparison WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get features, optionally filtered by category
 */
function getFeatures($category = null) {
    try {
        $db = getContentDB();
        if ($category) {
            $stmt = $db->prepare("SELECT * FROM features WHERE is_active = 1 AND category = ? ORDER BY sort_order");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get feature comparison (TrueVault vs Traditional VPN)
 */
function getFeatureComparison() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM feature_comparison WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get trust badges for hero section
 */
function getTrustBadges() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM trust_badges WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get how it works steps
 */
function getHowItWorks() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM how_it_works WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get FAQs by category (or all)
 */
function getFAQs($category = null) {
    try {
        $db = getContentDB();
        if ($category) {
            $stmt = $db->prepare("SELECT * FROM faqs WHERE is_active = 1 AND category = ? ORDER BY sort_order");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY category, sort_order");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get testimonials
 */
function getTestimonials($featuredOnly = false) {
    try {
        $db = getContentDB();
        $sql = "SELECT * FROM testimonials WHERE is_active = 1";
        if ($featuredOnly) $sql .= " AND is_featured = 1";
        $sql .= " ORDER BY id DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get theme from Part 8 themes database
 */
function getActiveTheme() {
    try {
        $themesDb = __DIR__ . '/../databases/themes.db';
        if (!file_exists($themesDb)) return getDefaultTheme();
        
        $db = new PDO("sqlite:$themesDb");
        $stmt = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
        $theme = $stmt->fetch(PDO::FETCH_ASSOC);
        return $theme ?: getDefaultTheme();
    } catch (Exception $e) {
        return getDefaultTheme();
    }
}

/**
 * Default theme fallback (database-driven when themes.db exists)
 */
function getDefaultTheme() {
    return [
        'primary_color' => '#00d9ff',
        'secondary_color' => '#00ff88',
        'background_color' => '#0f0f1a',
        'card_bg_color' => '#1a1a2e',
        'text_primary' => '#ffffff',
        'text_secondary' => '#a0a0a0',
        'accent_color' => '#ff6b6b',
        'font_family' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
    ];
}

/**
 * Generate CSS variables from theme
 */
function getThemeCSS() {
    $theme = getActiveTheme();
    return "
        :root {
            --primary: {$theme['primary_color']};
            --secondary: {$theme['secondary_color']};
            --background: {$theme['background_color']};
            --card-bg: {$theme['card_bg_color']};
            --text-primary: {$theme['text_primary']};
            --text-secondary: {$theme['text_secondary']};
            --accent: {$theme['accent_color']};
            --font-family: {$theme['font_family']};
        }
    ";
}

/**
 * Render stars for rating
 */
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '⭐' : '☆';
    }
    return $stars;
}

/**
 * Safe HTML escape
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format price with currency
 */
function formatPrice($amount, $currency = 'USD') {
    $symbol = $currency === 'CAD' ? 'C$' : '$';
    return $symbol . number_format($amount, 2);
}

// ===========================================
// NEW FUNCTIONS FOR PRICING COMPARISON PAGE
// FROM SECTION 26 - All content from database
// ===========================================

/**
 * Get page sections by page_key
 */
function getPageSections($pageKey) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = ? AND is_active = 1 ORDER BY sort_order");
        $stmt->execute([$pageKey]);
        $sections = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sections[$row['section_key']] = $row;
        }
        return $sections;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a single page section
 */
function getPageSection($pageKey, $sectionKey) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = ? AND section_key = ?");
        $stmt->execute([$pageKey, $sectionKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get competitors data
 */
function getCompetitors() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM competitors WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get single competitor by key
 */
function getCompetitor($key) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM competitors WHERE competitor_key = ?");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get competitor comparison table rows
 */
function getCompetitorComparison() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM competitor_comparison WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get unique features (features only TrueVault offers)
 */
function getUniqueFeatures($pageKey = 'pricing-comparison') {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM unique_features WHERE page_key = ? AND is_active = 1 ORDER BY sort_order");
        $stmt->execute([$pageKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get use cases (who should choose what)
 */
function getUseCases() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM use_cases WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get honest assessment items by type
 */
function getHonestAssessment($type = null) {
    try {
        $db = getContentDB();
        if ($type) {
            $stmt = $db->prepare("SELECT * FROM honest_assessment WHERE type = ? AND is_active = 1 ORDER BY sort_order");
            $stmt->execute([$type]);
        } else {
            $stmt = $db->query("SELECT * FROM honest_assessment WHERE is_active = 1 ORDER BY type, sort_order");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get trust badges filtered by page
 */
function getTrustBadgesByPage($pageKey = 'all') {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM trust_badges WHERE (page_key = ? OR page_key = 'all') AND is_active = 1 ORDER BY sort_order");
        $stmt->execute([$pageKey]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Replace template variables in text
 * e.g., {days} becomes the actual value
 */
function replaceVars($text, $vars = []) {
    foreach ($vars as $key => $value) {
        $text = str_replace('{' . $key . '}', $value, $text);
    }
    return $text;
}
?>
