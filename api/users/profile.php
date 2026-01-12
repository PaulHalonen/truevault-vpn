<?php
/**
 * TrueVault VPN - User Profile
 * GET/PUT /api/users/profile.php
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
        // Get profile
        $subscription = Auth::getUserSubscription($user['id']);
        
        // Get device count
        $db = DatabaseManager::getInstance()->users();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user['id']]);
        $deviceCount = $stmt->fetch()['count'];
        
        // Get camera count
        $camerasDb = DatabaseManager::getInstance()->cameras();
        $stmt = $camerasDb->prepare("SELECT COUNT(*) as count FROM discovered_cameras WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $cameraCount = $stmt->fetch()['count'];
        
        // Sanitize user data
        $userData = Auth::sanitizeUser($user);
        
        Response::success([
            'user' => $userData,
            'subscription' => $subscription,
            'stats' => [
                'device_count' => (int) $deviceCount,
                'device_limit' => (int) $user['device_limit'],
                'camera_count' => (int) $cameraCount,
                'mesh_user_limit' => (int) $user['mesh_user_limit']
            ]
        ]);
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
            $db = DatabaseManager::getInstance()->users();
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Get updated user
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $updatedUser = Auth::sanitizeUser($stmt->fetch());
            
            Logger::info('Profile updated', ['user_id' => $user['id']]);
            
            Response::success(['user' => $updatedUser], 'Profile updated successfully');
            
        } catch (Exception $e) {
            Logger::error('Profile update failed: ' . $e->getMessage());
            Response::serverError('Failed to update profile');
        }
        break;
        
    default:
        Response::error('Method not allowed', 405);
}
