<?php
/**
 * TrueVault VPN - Content Functions
 * Part 12 - Database-driven content helpers
 * ALL landing page content comes from database
 */

/**
 * Get content database connection
 */
function getContentDB() {
    static $db = null;
    if ($db === null) {
        $dbPath = __DIR__ . '/../databases/content.db';
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get pricing plans
 */
function getPricingPlans() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM pricing_plans WHERE is_active = 1 ORDER BY sort_order");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode features JSON
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true) ?: [];
        }
        return $plans;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get features
 */
function getFeatures() {
    try {
        $db = getContentDB();
        $stmt = $db->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order");
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
            $stmt = $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order");
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
 * Save contact form submission
 */
function saveContactSubmission($name, $email, $subject, $message) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message, $_SERVER['REMOTE_ADDR'] ?? '']);
        return true;
    } catch (Exception $e) {
        return false;
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
 * Default theme fallback
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
?>
