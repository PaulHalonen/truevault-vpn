<?php
/**
 * TrueVault VPN - Content Functions
 * Part 12 - Database-driven content helpers
 * ALL landing page content comes from database
 * NO HARDCODING - Everything from content.db
 * 
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

/**
 * Get content database connection (SQLite3)
 */
function getContentDB() {
    static $db = null;
    if ($db === null) {
        $dbPath = __DIR__ . '/../databases/content.db';
        if (!file_exists($dbPath)) {
            die("Database not found. Please run /setup.php first.");
        }
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
    }
    return $db;
}

/**
 * Helper: Fetch all rows as associative array (SQLite3)
 */
function fetchAllAssoc($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Get a single setting value
 */
function getSetting($key, $default = '') {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['setting_value'] : $default;
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
        $stmt->bindValue(1, $category, SQLITE3_TEXT);
        $result = $stmt->execute();
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
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
        $result = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
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
        $stmt->bindValue(1, $location, SQLITE3_TEXT);
        $result = $stmt->execute();
        return fetchAllAssoc($result);
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
        $stmt->bindValue(1, $pageKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: [];
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
        $result = $db->query("SELECT * FROM pricing_plans WHERE is_active = 1 ORDER BY sort_order");
        $plans = fetchAllAssoc($result);
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
        $result = $db->query("SELECT * FROM plan_comparison WHERE is_active = 1 ORDER BY sort_order");
        return fetchAllAssoc($result);
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
            $stmt->bindValue(1, $category, SQLITE3_TEXT);
            $result = $stmt->execute();
        } else {
            $result = $db->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order");
        }
        return fetchAllAssoc($result);
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
        $result = $db->query("SELECT * FROM feature_comparison WHERE is_active = 1 ORDER BY sort_order");
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get trust badges for hero section
 */
function getTrustBadges($pageKey = 'all') {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM trust_badges WHERE is_active = 1 AND (page_key = ? OR page_key = 'all') ORDER BY sort_order");
        $stmt->bindValue(1, $pageKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        return fetchAllAssoc($result);
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
        $result = $db->query("SELECT * FROM how_it_works WHERE is_active = 1 ORDER BY step_number");
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get testimonials, optionally only featured ones
 */
function getTestimonials($featuredOnly = false) {
    try {
        $db = getContentDB();
        if ($featuredOnly) {
            $result = $db->query("SELECT * FROM testimonials WHERE is_active = 1 AND is_featured = 1");
        } else {
            $result = $db->query("SELECT * FROM testimonials WHERE is_active = 1");
        }
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get FAQs, optionally by category
 */
function getFAQs($category = null) {
    try {
        $db = getContentDB();
        if ($category) {
            $stmt = $db->prepare("SELECT * FROM faqs WHERE is_active = 1 AND category = ? ORDER BY sort_order");
            $stmt->bindValue(1, $category, SQLITE3_TEXT);
            $result = $stmt->execute();
        } else {
            $result = $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY category, sort_order");
        }
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get page sections by page key
 */
function getPageSections($pageKey) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = ? AND is_active = 1 ORDER BY sort_order");
        $stmt->bindValue(1, $pageKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        $sections = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $sections[$row['section_key']] = $row;
        }
        return $sections;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a specific page section
 */
function getPageSection($pageKey, $sectionKey) {
    try {
        $db = getContentDB();
        $stmt = $db->prepare("SELECT * FROM page_sections WHERE page_key = ? AND section_key = ? AND is_active = 1");
        $stmt->bindValue(1, $pageKey, SQLITE3_TEXT);
        $stmt->bindValue(2, $sectionKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all competitors data (FROM SECTION 26)
 */
function getCompetitors() {
    try {
        $db = getContentDB();
        $result = $db->query("SELECT * FROM competitors WHERE is_active = 1 ORDER BY sort_order");
        return fetchAllAssoc($result);
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
        $result = $db->query("SELECT * FROM competitor_comparison WHERE is_active = 1 ORDER BY sort_order");
        return fetchAllAssoc($result);
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
        $stmt->bindValue(1, $pageKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get use cases recommendations
 */
function getUseCases($recommendUs = null) {
    try {
        $db = getContentDB();
        if ($recommendUs !== null) {
            $stmt = $db->prepare("SELECT * FROM use_cases WHERE is_active = 1 AND recommend_us = ? ORDER BY sort_order");
            $stmt->bindValue(1, $recommendUs, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } else {
            $result = $db->query("SELECT * FROM use_cases WHERE is_active = 1 ORDER BY sort_order");
        }
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get honest assessment (pros/cons)
 */
function getHonestAssessment($type = null) {
    try {
        $db = getContentDB();
        if ($type) {
            $stmt = $db->prepare("SELECT * FROM honest_assessment WHERE type = ? AND is_active = 1 ORDER BY sort_order");
            $stmt->bindValue(1, $type, SQLITE3_TEXT);
            $result = $stmt->execute();
        } else {
            $result = $db->query("SELECT * FROM honest_assessment WHERE is_active = 1 ORDER BY type, sort_order");
        }
        return fetchAllAssoc($result);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Replace template variables in text
 * Example: {days} becomes 30, {percent} becomes 17
 */
function replaceTemplateVars($text, $settings = null) {
    if (!$settings) {
        $settings = getAllSettings();
    }
    
    $replacements = [
        '{days}' => $settings['feature_refund_days'] ?? '30',
        '{percent}' => $settings['feature_yearly_discount'] ?? '17',
        '{trial_days}' => $settings['feature_trial_days'] ?? '7',
        '{company}' => $settings['company_name'] ?? 'TrueVault VPN',
        '{support_email}' => $settings['support_email'] ?? 'support@truevault.com',
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}
