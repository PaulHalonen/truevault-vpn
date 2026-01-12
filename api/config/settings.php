<?php
/**
 * TrueVault VPN - Settings Manager
 * Database-driven configuration including theme settings
 * 
 * CRITICAL: All styling comes from this class - NO HARDCODING!
 */

require_once __DIR__ . '/database.php';

class Settings {
    private static $instance = null;
    private $cache = [];
    private $themeCache = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get a setting value
     */
    public function get($key, $default = null) {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        // Settings could be stored in a dedicated settings table
        // For now, return default
        return $default;
    }
    
    /**
     * Set a setting value
     */
    public function set($key, $value) {
        $this->cache[$key] = $value;
        // In a full implementation, save to database
    }
    
    /**
     * Get the active theme
     */
    public function getActiveTheme() {
        if ($this->themeCache !== null) {
            return $this->themeCache;
        }
        
        try {
            $db = DatabaseManager::getInstance()->themes();
            $stmt = $db->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
            $theme = $stmt->fetch();
            
            if ($theme) {
                $this->themeCache = $theme;
                return $theme;
            }
        } catch (Exception $e) {
            // Database might not exist yet
        }
        
        return null;
    }
    
    /**
     * Get all theme settings organized by category
     */
    public function getThemeSettings($themeId = null) {
        if ($themeId === null) {
            $theme = $this->getActiveTheme();
            if (!$theme) return [];
            $themeId = $theme['id'];
        }
        
        try {
            $db = DatabaseManager::getInstance()->themes();
            $stmt = $db->prepare("
                SELECT setting_category, setting_key, setting_value, setting_type 
                FROM theme_settings 
                WHERE theme_id = ?
                ORDER BY setting_category, sort_order
            ");
            $stmt->execute([$themeId]);
            $settings = $stmt->fetchAll();
            
            // Organize by category
            $organized = [];
            foreach ($settings as $setting) {
                $category = $setting['setting_category'];
                if (!isset($organized[$category])) {
                    $organized[$category] = [];
                }
                $organized[$category][$setting['setting_key']] = $setting['setting_value'];
            }
            
            return $organized;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get theme colors
     */
    public function getColors() {
        $settings = $this->getThemeSettings();
        return $settings['colors'] ?? $this->getDefaultColors();
    }
    
    /**
     * Get typography settings
     */
    public function getTypography() {
        $settings = $this->getThemeSettings();
        return $settings['typography'] ?? $this->getDefaultTypography();
    }
    
    /**
     * Get button styles
     */
    public function getButtons() {
        $settings = $this->getThemeSettings();
        return $settings['buttons'] ?? $this->getDefaultButtons();
    }
    
    /**
     * Get layout settings
     */
    public function getLayout() {
        $settings = $this->getThemeSettings();
        return $settings['layout'] ?? $this->getDefaultLayout();
    }
    
    /**
     * Get card styles
     */
    public function getCards() {
        $settings = $this->getThemeSettings();
        return $settings['cards'] ?? [];
    }
    
    /**
     * Get form styles
     */
    public function getForms() {
        $settings = $this->getThemeSettings();
        return $settings['forms'] ?? [];
    }
    
    /**
     * Get badge styles
     */
    public function getBadges() {
        $settings = $this->getThemeSettings();
        return $settings['badges'] ?? [];
    }
    
    /**
     * Get gradient definitions
     */
    public function getGradients() {
        $settings = $this->getThemeSettings();
        return $settings['gradients'] ?? [];
    }
    
    /**
     * Generate CSS variables from theme settings
     * This is called to create dynamic CSS that reflects database values
     */
    public function generateCSSVariables() {
        $settings = $this->getThemeSettings();
        $css = ":root {\n";
        
        foreach ($settings as $category => $values) {
            $css .= "  /* $category */\n";
            foreach ($values as $key => $value) {
                $varName = "--{$category}-{$key}";
                $varName = str_replace('_', '-', $varName);
                $css .= "  $varName: $value;\n";
            }
            $css .= "\n";
        }
        
        $css .= "}\n";
        return $css;
    }
    
    /**
     * Get all theme settings as JSON (for JavaScript)
     */
    public function getThemeJSON() {
        return json_encode($this->getThemeSettings());
    }
    
    /**
     * Update a theme setting
     */
    public function updateThemeSetting($themeId, $category, $key, $value) {
        try {
            $db = DatabaseManager::getInstance()->themes();
            $stmt = $db->prepare("
                UPDATE theme_settings 
                SET setting_value = ?
                WHERE theme_id = ? AND setting_category = ? AND setting_key = ?
            ");
            $stmt->execute([$value, $themeId, $category, $key]);
            
            // Clear cache
            $this->themeCache = null;
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update multiple theme settings at once
     */
    public function updateThemeSettings($themeId, $settings) {
        try {
            $db = DatabaseManager::getInstance()->themes();
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE theme_settings 
                SET setting_value = ?
                WHERE theme_id = ? AND setting_category = ? AND setting_key = ?
            ");
            
            foreach ($settings as $category => $values) {
                foreach ($values as $key => $value) {
                    $stmt->execute([$value, $themeId, $category, $key]);
                }
            }
            
            $db->commit();
            $this->themeCache = null;
            
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    
    // Default values (only used if database is not available)
    
    private function getDefaultColors() {
        return [
            'primary' => '#00d9ff',
            'secondary' => '#00ff88',
            'accent' => '#ff6b6b',
            'background' => '#0f0f1a',
            'text' => '#ffffff',
            'text_muted' => '#888888',
            'success' => '#00ff88',
            'warning' => '#ffbb00',
            'error' => '#ff5050'
        ];
    }
    
    private function getDefaultTypography() {
        return [
            'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'font_size_base' => '16px',
            'line_height' => '1.5'
        ];
    }
    
    private function getDefaultButtons() {
        return [
            'border_radius' => '8px',
            'padding' => '10px 20px'
        ];
    }
    
    private function getDefaultLayout() {
        return [
            'max_width' => '1200px',
            'sidebar_width' => '250px',
            'spacing_unit' => '8px'
        ];
    }
}

// Helper function
function settings() {
    return Settings::getInstance();
}
