<?php
/**
 * TrueVault VPN - Admin Theme API
 * GET/POST /api/admin/theme.php
 * 
 * Manages theme settings in the database
 * ALL VISUAL STYLES STORED IN DATABASE - NO HARDCODING
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require admin authentication
$token = JWTManager::getBearerToken();
if (!$token) {
    Response::unauthorized('No token provided');
}

$payload = JWTManager::validateToken($token);
if (!$payload || !isset($payload['is_admin']) || !$payload['is_admin']) {
    Response::forbidden('Admin access required');
}

$method = Response::getMethod();

try {
    $themesDb = DatabaseManager::getInstance()->themes();
    
    switch ($method) {
        case 'GET':
            // Get all theme settings
            $stmt = $themesDb->prepare("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
            $stmt->execute();
            $theme = $stmt->fetch();
            
            if (!$theme) {
                Response::success(['theme' => null, 'settings' => []]);
            }
            
            // Get all settings for this theme
            $stmt = $themesDb->prepare("
                SELECT setting_category, setting_key, setting_value, setting_type
                FROM theme_settings 
                WHERE theme_id = ?
                ORDER BY setting_category, sort_order
            ");
            $stmt->execute([$theme['id']]);
            $settingsRows = $stmt->fetchAll();
            
            // Group settings by category
            $settings = [];
            foreach ($settingsRows as $row) {
                $category = $row['setting_category'];
                $key = $row['setting_key'];
                $value = $row['setting_value'];
                
                if (!isset($settings[$category])) {
                    $settings[$category] = [];
                }
                $settings[$category][$key] = $value;
            }
            
            Response::success([
                'theme' => [
                    'id' => $theme['id'],
                    'name' => $theme['theme_name'],
                    'slug' => $theme['theme_slug']
                ],
                'settings' => $settings
            ]);
            break;
            
        case 'POST':
            // Update theme settings
            $input = Response::getJsonInput();
            
            if (empty($input['settings'])) {
                Response::error('No settings provided', 400);
            }
            
            // Get active theme
            $stmt = $themesDb->prepare("SELECT id FROM themes WHERE is_active = 1 LIMIT 1");
            $stmt->execute();
            $theme = $stmt->fetch();
            
            if (!$theme) {
                // Create default theme if none exists
                $stmt = $themesDb->prepare("
                    INSERT INTO themes (theme_name, theme_slug, is_active, is_default)
                    VALUES ('Default', 'default', 1, 1)
                ");
                $stmt->execute();
                $themeId = $themesDb->lastInsertId();
            } else {
                $themeId = $theme['id'];
            }
            
            // Update settings
            $themesDb->beginTransaction();
            
            try {
                foreach ($input['settings'] as $category => $categorySettings) {
                    foreach ($categorySettings as $key => $value) {
                        // Upsert setting
                        $stmt = $themesDb->prepare("
                            INSERT INTO theme_settings (theme_id, setting_category, setting_key, setting_value)
                            VALUES (?, ?, ?, ?)
                            ON CONFLICT(theme_id, setting_category, setting_key) 
                            DO UPDATE SET setting_value = excluded.setting_value, updated_at = datetime('now')
                        ");
                        $stmt->execute([$themeId, $category, $key, $value]);
                    }
                }
                
                // Update theme modified timestamp
                $stmt = $themesDb->prepare("UPDATE themes SET updated_at = datetime('now') WHERE id = ?");
                $stmt->execute([$themeId]);
                
                $themesDb->commit();
                
                // Clear any cached theme data
                // In production, you might want to invalidate Redis/Memcached here
                
                Logger::info('Theme settings updated', [
                    'admin_id' => $payload['sub'],
                    'theme_id' => $themeId
                ]);
                
                Response::success(['saved' => true], 'Theme settings saved successfully');
                
            } catch (Exception $e) {
                $themesDb->rollBack();
                throw $e;
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Admin theme error: ' . $e->getMessage());
    Response::serverError('Failed to process theme request');
}
