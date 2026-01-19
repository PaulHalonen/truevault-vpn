<?php
/**
 * TrueVault VPN - Content Helper Class
 * 
 * Manages global site settings
 * All settings stored in themes.db (site_settings table)
 * 
 * USAGE:
 * $title = Content::get('site_title');
 * Content::set('site_title', 'New Title');
 * $settings = Content::getByCategory('general');
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access forbidden');
}

class Content {
    /**
     * Cache for settings (stored in memory)
     * Reduces database queries
     */
    private static $cache = [];
    
    /**
     * Get site setting value
     * 
     * @param string $key Setting key
     * @param mixed $fallback Fallback value if not found
     * @return mixed Setting value
     */
    public static function get($key, $fallback = '') {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT setting_value, setting_type FROM site_settings 
                WHERE setting_key = ?
            ");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                self::$cache[$key] = $fallback;
                return $fallback;
            }
            
            // Cast to appropriate type
            $value = self::castValue($result['setting_value'], $result['setting_type']);
            
            // Cache it
            self::$cache[$key] = $value;
            
            return $value;
            
        } catch (Exception $e) {
            error_log("Content::get() error: " . $e->getMessage());
            return $fallback;
        }
    }
    
    /**
     * Set site setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type (text, number, boolean, json, image)
     * @param string $category Category (general, branding, seo, social, features, pricing)
     * @param string $description Optional description
     * @return bool Success status
     */
    public static function set($key, $value, $type = 'text', $category = 'general', $description = '') {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            // Convert value to string for storage
            $stringValue = self::valueToString($value, $type);
            
            // Check if setting exists
            $stmt = $themesConn->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing
                $stmt = $themesConn->prepare("
                    UPDATE site_settings 
                    SET setting_value = ?, setting_type = ?, category = ?, description = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE setting_key = ?
                ");
                $stmt->execute([$stringValue, $type, $category, $description, $key]);
            } else {
                // Insert new
                $stmt = $themesConn->prepare("
                    INSERT INTO site_settings (setting_key, setting_value, setting_type, category, description)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$key, $stringValue, $type, $category, $description]);
            }
            
            // Update cache
            self::$cache[$key] = $value;
            
            return true;
            
        } catch (Exception $e) {
            error_log("Content::set() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get multiple settings at once
     * 
     * @param array $keys Array of setting keys
     * @return array Associative array of key => value
     */
    public static function getMany($keys) {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = self::get($key);
        }
        
        return $result;
    }
    
    /**
     * Check if setting exists
     * 
     * @param string $key Setting key
     * @return bool True if exists
     */
    public static function exists($key) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            error_log("Content::exists() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete setting
     * 
     * @param string $key Setting key
     * @return bool Success status
     */
    public static function delete($key) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("DELETE FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            // Remove from cache
            unset(self::$cache[$key]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Content::delete() error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all settings by category
     * 
     * @param string $category Category name
     * @return array Array of settings
     */
    public static function getByCategory($category) {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->prepare("
                SELECT setting_key, setting_value, setting_type, description 
                FROM site_settings 
                WHERE category = ?
                ORDER BY setting_key ASC
            ");
            $stmt->execute([$category]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($rows as $row) {
                $result[$row['setting_key']] = [
                    'value' => self::castValue($row['setting_value'], $row['setting_type']),
                    'type' => $row['setting_type'],
                    'description' => $row['description']
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Content::getByCategory() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all settings
     * 
     * @return array Array of all settings
     */
    public static function getAll() {
        try {
            require_once __DIR__ . '/Database.php';
            $db = Database::getInstance();
            $themesConn = $db->getConnection('themes');
            
            $stmt = $themesConn->query("
                SELECT setting_key, setting_value, setting_type, category, description 
                FROM site_settings 
                ORDER BY category ASC, setting_key ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($rows as $row) {
                $result[$row['setting_key']] = [
                    'value' => self::castValue($row['setting_value'], $row['setting_type']),
                    'type' => $row['setting_type'],
                    'category' => $row['category'],
                    'description' => $row['description']
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Content::getAll() error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear cache
     */
    public static function clearCache() {
        self::$cache = [];
    }
    
    /**
     * Cast value to appropriate type based on setting_type
     * 
     * @param string $value String value from database
     * @param string $type Setting type
     * @return mixed Casted value
     */
    private static function castValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'number':
                return is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $value;
            case 'json':
                return json_decode($value, true);
            case 'text':
            case 'image':
            default:
                return $value;
        }
    }
    
    /**
     * Convert value to string for storage
     * 
     * @param mixed $value Value to store
     * @param string $type Setting type
     * @return string String representation
     */
    private static function valueToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'number':
                return (string)$value;
            case 'json':
                return json_encode($value);
            case 'text':
            case 'image':
            default:
                return (string)$value;
        }
    }
}
?>
