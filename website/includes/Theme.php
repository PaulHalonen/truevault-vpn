<?php
/**
 * TrueVault VPN - Theme Helper Class
 * 
 * Manages themes, colors, and seasonal switching
 * All theme data stored in themes.db
 * Caching for performance
 * 
 * USAGE:
 * $theme = Theme::getActiveTheme();
 * $color = Theme::getColor('primary');
 * Theme::switchTheme(5);
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Theme {
    /**
     * Cache for theme data (stored in PHP session)
     * Reduces database queries
     */
    private static $cache = null;
    private static $cacheExpiry = 3600; // 1 hour
    
    /**
     * Get currently active theme
     * 
     * @return array Theme data with all properties
     */
    public static function getActiveTheme() {
        // Check cache first
        if (self::$cache !== null && isset(self::$cache['theme'])) {
            if (time() < self::$cache['expiry']) {
                return self::$cache['theme'];
            }
        }
        
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT * FROM themes WHERE is_active = 1 LIMIT 1
            ");
            $stmt->execute();
            $theme = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$theme) {
                // Fallback to default_dark
                $stmt = $themesConn->prepare("
                    SELECT * FROM themes WHERE name = 'default_dark' LIMIT 1
                ");
                $stmt->execute();
                $theme = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Cache it
            self::$cache = [
                'theme' => $theme,
                'expiry' => time() + self::$cacheExpiry
            ];
            
            return $theme;
            
        } catch (Exception $e) {
            error_log("Theme::getActiveTheme() error: " . $e->getMessage());
            return self::getDefaultTheme();
        }
    }
    
    /**
     * Get color value by key
     * 
     * @param string $key Color key (primary, secondary, accent, etc.)
     * @param string $fallback Fallback color if not found
     * @return string Hex color code
     */
    public static function getColor($key, $fallback = '#667eea') {
        try {
            $theme = self::getActiveTheme();
            
            if (!$theme) {
                return $fallback;
            }
            
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT color_value FROM theme_colors 
                WHERE theme_id = ? AND color_key = ?
            ");
            $stmt->execute([$theme['id'], $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['color_value'] : $fallback;
            
        } catch (Exception $e) {
            error_log("Theme::getColor() error: " . $e->getMessage());
            return $fallback;
        }
    }
    
    /**
     * Get all colors for active theme
     * 
     * @return array Associative array of color_key => color_value
     */
    public static function getAllColors() {
        try {
            $theme = self::getActiveTheme();
            
            if (!$theme) {
                return self::getDefaultColors();
            }
            
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT color_key, color_value FROM theme_colors 
                WHERE theme_id = ?
            ");
            $stmt->execute([$theme['id']]);
            $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($colors as $color) {
                $result[$color['color_key']] = $color['color_value'];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Theme::getAllColors() error: " . $e->getMessage());
            return self::getDefaultColors();
        }
    }
    
    /**
     * Get theme setting value
     * 
     * @param string $key Setting key
     * @param string $fallback Fallback value
     * @return string Setting value
     */
    public static function getSetting($key, $fallback = '') {
        try {
            $theme = self::getActiveTheme();
            
            if (!$theme) {
                return $fallback;
            }
            
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT setting_value FROM theme_settings 
                WHERE theme_id = ? AND setting_key = ?
            ");
            $stmt->execute([$theme['id'], $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['setting_value'] : $fallback;
            
        } catch (Exception $e) {
            error_log("Theme::getSetting() error: " . $e->getMessage());
            return $fallback;
        }
    }
    
    /**
     * Switch to different theme
     * 
     * @param int $themeId Theme ID to activate
     * @return bool Success status
     */
    public static function switchTheme($themeId) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            // Verify theme exists
            $stmt = $themesConn->prepare("SELECT id FROM themes WHERE id = ?");
            $stmt->execute([$themeId]);
            if (!$stmt->fetch()) {
                return false;
            }
            
            // Deactivate all themes
            $themesConn->exec("UPDATE themes SET is_active = 0");
            
            // Activate selected theme
            $stmt = $themesConn->prepare("UPDATE themes SET is_active = 1 WHERE id = ?");
            $stmt->execute([$themeId]);
            
            // Update site setting
            $stmt = $themesConn->prepare("
                UPDATE site_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP
                WHERE setting_key = 'active_theme_id'
            ");
            $stmt->execute([$themeId]);
            
            // Clear cache
            self::clearCache();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Theme::switchTheme() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List all available themes
     * 
     * @param string|null $style Filter by style (light, medium, dark)
     * @param bool|null $seasonal Filter by seasonal status
     * @return array Array of themes
     */
    public static function listThemes($style = null, $seasonal = null) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $sql = "SELECT * FROM themes WHERE 1=1";
            $params = [];
            
            if ($style !== null) {
                $sql .= " AND style = ?";
                $params[] = $style;
            }
            
            if ($seasonal !== null) {
                $sql .= " AND is_seasonal = ?";
                $params[] = $seasonal ? 1 : 0;
            }
            
            $sql .= " ORDER BY is_seasonal ASC, style ASC, name ASC";
            
            $stmt = $themesConn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Theme::listThemes() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Automatically switch to seasonal theme
     * Called by cron job daily
     * 
     * @return bool Success status
     */
    public static function autoSwitchSeasonal() {
        try {
            // Check if seasonal themes enabled
            require_once __DIR__ . '/Content.php';
            $enabled = Content::get('enable_seasonal_themes', '0');
            
            if ($enabled != '1') {
                return false; // Seasonal switching disabled
            }
            
            // Get current season
            $currentSeason = self::getCurrentSeason();
            
            // Get active theme
            $activeTheme = self::getActiveTheme();
            
            // Check if we need to switch
            if ($activeTheme['is_seasonal'] && $activeTheme['season'] == $currentSeason) {
                return false; // Already on correct seasonal theme
            }
            
            // Find seasonal theme matching current style and season
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT id FROM themes 
                WHERE is_seasonal = 1 AND season = ? AND style = ?
                LIMIT 1
            ");
            $stmt->execute([$currentSeason, $activeTheme['style']]);
            $newTheme = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$newTheme) {
                return false; // No matching seasonal theme found
            }
            
            // Switch to seasonal theme
            return self::switchTheme($newTheme['id']);
            
        } catch (Exception $e) {
            error_log("Theme::autoSwitchSeasonal() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Determine current season based on date
     * 
     * @return string Season name (winter, spring, summer, fall)
     */
    public static function getCurrentSeason() {
        $month = (int)date('n'); // 1-12
        $day = (int)date('j'); // 1-31
        
        // Winter: Dec 1 - Feb 28/29
        if ($month == 12 || $month == 1 || $month == 2) {
            return 'winter';
        }
        
        // Spring: Mar 1 - May 31
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        }
        
        // Summer: Jun 1 - Aug 31
        if ($month >= 6 && $month <= 8) {
            return 'summer';
        }
        
        // Fall: Sep 1 - Nov 30
        return 'fall';
    }
    
    /**
     * Clear theme cache
     * Called after theme switch
     */
    public static function clearCache() {
        self::$cache = null;
    }
    
    /**
     * Get default theme (fallback)
     * 
     * @return array Default theme data
     */
    private static function getDefaultTheme() {
        return [
            'id' => 3,
            'name' => 'default_dark',
            'display_name' => 'Dark Mode',
            'description' => 'Deep blue/purple gradient background',
            'style' => 'dark',
            'is_active' => 1,
            'is_seasonal' => 0,
            'season' => null
        ];
    }
    
    /**
     * Get default colors (fallback)
     * 
     * @return array Default color palette
     */
    private static function getDefaultColors() {
        return [
            'primary' => '#667eea',
            'secondary' => '#764ba2',
            'accent' => '#00d9ff',
            'background' => '#0f0f1a',
            'surface' => '#1a1a2e',
            'text_primary' => '#ffffff',
            'text_secondary' => '#94a3b8',
            'success' => '#10b981',
            'warning' => '#fbbf24',
            'error' => '#f87171',
            'info' => '#60a5fa'
        ];
    }
}
?>
