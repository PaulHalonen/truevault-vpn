<?php
/**
 * TrueVault VPN - User Devices
 * GET/POST/DELETE /api/users/devices.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();

$method = Response::getMethod();

try {
    $db = DatabaseManager::getInstance()->users();
    
    switch ($method) {
        case 'GET':
            // List user's devices
            $stmt = $db->prepare("
                SELECT id, device_uuid, device_name, device_type, os_type, last_connected, last_ip, is_active, created_at
                FROM user_devices 
                WHERE user_id = ?
                ORDER BY last_connected DESC
            ");
            $stmt->execute([$user['id']]);
            $devices = $stmt->fetchAll();
            
            Response::success([
                'devices' => $devices,
                'count' => count($devices),
                'limit' => (int) $user['device_limit']
            ]);
            break;
            
        case 'POST':
            // Register new device
            $input = Response::getJsonInput();
            
            $validator = Validator::make($input, [
                'device_name' => 'required|min:1|max:100',
                'device_type' => 'required|in:desktop,laptop,mobile,tablet,router,other',
                'os_type' => 'max:50'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->errors());
            }
            
            // Check device limit
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$user['id']]);
            $count = $stmt->fetch()['count'];
            
            if ($count >= $user['device_limit']) {
                Response::error("Device limit reached ({$user['device_limit']} devices). Upgrade your plan for more devices.", 403);
            }
            
            // Generate device UUID
            $deviceUuid = Encryption::generateUUID();
            
            // Insert device
            $stmt = $db->prepare("
                INSERT INTO user_devices (user_id, device_uuid, device_name, device_type, os_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                $deviceUuid,
                trim($input['device_name']),
                $input['device_type'],
                $input['os_type'] ?? null
            ]);
            
            $deviceId = $db->lastInsertId();
            
            // Get the device
            $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ?");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch();
            
            // Remove sensitive data
            unset($device['private_key_encrypted']);
            
            Logger::info('Device registered', ['user_id' => $user['id'], 'device_id' => $deviceId]);
            
            Response::created([
                'device' => $device,
                'count' => $count + 1,
                'limit' => (int) $user['device_limit']
            ], 'Device registered successfully');
            break;
            
        case 'DELETE':
            // Remove device
            $deviceId = $_GET['id'] ?? null;
            
            if (!$deviceId) {
                Response::error('Device ID required', 400);
            }
            
            // Verify device belongs to user
            $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$deviceId, $user['id']]);
            $device = $stmt->fetch();
            
            if (!$device) {
                Response::notFound('Device not found');
            }
            
            // Delete device
            $stmt = $db->prepare("DELETE FROM user_devices WHERE id = ?");
            $stmt->execute([$deviceId]);
            
            Logger::info('Device removed', ['user_id' => $user['id'], 'device_id' => $deviceId]);
            
            Response::success(null, 'Device removed successfully');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Device operation failed: ' . $e->getMessage());
    Response::serverError('Failed to process device request');
}
