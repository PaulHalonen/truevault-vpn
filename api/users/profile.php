<?php
/**
 * TrueVault VPN - User Profile
 * GET/PUT /api/users/profile.php
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

switch ($method) {
    case 'GET':
        try {
            // Get subscription info
            $subscription = Auth::getUserSubscription($user['id']);
            
            // Get device count
            $deviceResult = Database::queryOne('devices', 
                "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1", 
                [$user['id']]
            );
            $deviceCount = $deviceResult['count'] ?? 0;
            
            // Get camera count
            $cameraResult = Database::queryOne('cameras', 
                "SELECT COUNT(*) as count FROM discovered_cameras WHERE user_id = ?", 
                [$user['id']]
            );
            $cameraCount = $cameraResult['count'] ?? 0;
            
            // Sanitize user data
            $userData = Auth::sanitizeUser($user);
            
            Response::success([
                'user' => $userData,
                'subscription' => $subscription,
                'stats' => [
                    'device_count' => (int) $deviceCount,
                    'device_limit' => (int) ($user['device_limit'] ?? 3),
                    'camera_count' => (int) $cameraCount,
                    'mesh_user_limit' => (int) ($user['mesh_user_limit'] ?? 0)
                ]
            ]);
        } catch (Exception $e) {
            Logger::error('Profile fetch failed: ' . $e->getMessage());
            Response::serverError('Failed to get profile');
        }
        break;
        
    case 'PUT':
        // Update profile
        $input = Response::getJsonInput();
        
        $validator = Validator::make($input, [
            'first_name' => 'min:1|max:50',
            'last_name' => 'min:1|max:50'
        ]);
        
        if ($validator->fails()) {
            Response::validationError($validator->errors());
        }
        
        $updates = [];
        $params = [];
        
        if (isset($input['first_name'])) {
            $updates[] = 'first_name = ?';
            $params[] = trim($input['first_name']);
        }
        
        if (isset($input['last_name'])) {
            $updates[] = 'last_name = ?';
            $params[] = trim($input['last_name']);
        }
        
        if (empty($updates)) {
            Response::error('No fields to update', 400);
        }
        
        $updates[] = "updated_at = datetime('now')";
        $params[] = $user['id'];
        
        try {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            Database::execute('users', $sql, $params);
            
            // Get updated user
            $updatedUser = Database::queryOne('users', 
                "SELECT * FROM users WHERE id = ?", 
                [$user['id']]
            );
            $userData = Auth::sanitizeUser($updatedUser);
            
            Logger::info('Profile updated', ['user_id' => $user['id']]);
            
            Response::success(['user' => $userData], 'Profile updated successfully');
            
        } catch (Exception $e) {
            Logger::error('Profile update failed: ' . $e->getMessage());
            Response::serverError('Failed to update profile');
        }
        break;
        
    default:
        Response::error('Method not allowed', 405);
}
