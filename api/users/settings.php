<?php
/**
 * TrueVault VPN - User Settings
 * GET/PUT /api/users/settings.php
 * 
 * FIXED: January 14, 2026 - Changed DatabaseManager to Database class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();

$method = Response::getMethod();

try {
    switch ($method) {
        case 'GET':
            // Get all settings
            $settings = Database::query('users', 
                "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?", 
                [$user['id']]
            );
            
            // Convert to key-value object
            $settingsObject = [];
            foreach ($settings as $setting) {
                $value = $setting['setting_value'];
                // Try to decode JSON values
                $decoded = json_decode($value, true);
                $settingsObject[$setting['setting_key']] = ($decoded !== null) ? $decoded : $value;
            }
            
            Response::success(['settings' => $settingsObject]);
            break;
            
        case 'PUT':
            // Update settings
            $input = Response::getJsonInput();
            
            if (empty($input)) {
                Response::error('No settings provided', 400);
            }
            
            // Allowed settings
            $allowedSettings = [
                'notifications_email',
                'notifications_push',
                'auto_connect',
                'kill_switch',
                'default_server',
                'default_identity',
                'theme_mode',
                'language',
                'timezone'
            ];
            
            foreach ($input as $key => $value) {
                if (!in_array($key, $allowedSettings)) {
                    continue;
                }
                
                // Encode arrays/objects to JSON
                $valueToStore = is_array($value) || is_object($value) ? json_encode($value) : $value;
                
                // Check if setting exists
                $existing = Database::queryOne('users', 
                    "SELECT id FROM user_settings WHERE user_id = ? AND setting_key = ?", 
                    [$user['id'], $key]
                );
                
                if ($existing) {
                    // Update
                    Database::execute('users', 
                        "UPDATE user_settings SET setting_value = ?, updated_at = datetime('now') WHERE user_id = ? AND setting_key = ?", 
                        [$valueToStore, $user['id'], $key]
                    );
                } else {
                    // Insert
                    Database::execute('users', 
                        "INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)", 
                        [$user['id'], $key, $valueToStore]
                    );
                }
            }
            
            // Get updated settings
            $settings = Database::query('users', 
                "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?", 
                [$user['id']]
            );
            
            $settingsObject = [];
            foreach ($settings as $setting) {
                $value = $setting['setting_value'];
                $decoded = json_decode($value, true);
                $settingsObject[$setting['setting_key']] = ($decoded !== null) ? $decoded : $value;
            }
            
            Logger::info('Settings updated', ['user_id' => $user['id']]);
            
            Response::success(['settings' => $settingsObject], 'Settings updated');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Settings operation failed: ' . $e->getMessage());
    Response::serverError('Failed to process settings');
}
