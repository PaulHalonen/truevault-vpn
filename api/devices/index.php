<?php
/**
 * TrueVault VPN - Device Management
 * Handles device limits, swapping, and camera assignments
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$user = Auth::requireAuth();

$action = $_GET['action'] ?? 'list';

class DeviceManager {
    
    /**
     * Get user's device limits based on plan
     */
    public static function getUserLimits($userId) {
        $user = Auth::getUserById($userId);
        
        // Check VIP
        if (VIPManager::isVIP($user['email'])) {
            return VIPManager::getVIPLimits($user['email']);
        }
        
        // Get from subscription
        $subscription = BillingManager::getCurrentSubscription($userId);
        
        if (!$subscription) {
            return ['max_devices' => 0, 'max_cameras' => 0, 'camera_server' => null];
        }
        
        $planLimits = [
            'basic' => ['max_devices' => 3, 'max_cameras' => 1, 'camera_server' => 'ny'],
            'family' => ['max_devices' => 5, 'max_cameras' => 2, 'camera_server' => 'ny'],
            'dedicated' => ['max_devices' => 999, 'max_cameras' => 12, 'camera_server' => 'any'],
            'vip_basic' => ['max_devices' => 8, 'max_cameras' => 2, 'camera_server' => 'ny'],
            'vip_dedicated' => ['max_devices' => 999, 'max_cameras' => 12, 'camera_server' => 'any']
        ];
        
        return $planLimits[$subscription['plan_type']] ?? ['max_devices' => 0, 'max_cameras' => 0];
    }
    
    /**
     * Get user's current devices
     */
    public static function getDevices($userId) {
        return Database::queryAll('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND status = 'active' ORDER BY is_camera DESC, last_seen DESC",
            [$userId]
        );
    }
    
    /**
     * Get current device/camera counts
     */
    public static function getCounts($userId) {
        $devices = self::getDevices($userId);
        
        $deviceCount = 0;
        $cameraCount = 0;
        
        foreach ($devices as $device) {
            if ($device['is_camera']) {
                $cameraCount++;
            } else {
                $deviceCount++;
            }
        }
        
        return [
            'devices' => $deviceCount,
            'cameras' => $cameraCount,
            'total' => count($devices)
        ];
    }
    
    /**
     * Register a new device
     */
    public static function registerDevice($userId, $deviceData) {
        $limits = self::getUserLimits($userId);
        $counts = self::getCounts($userId);
        
        $isCamera = $deviceData['is_camera'] ?? false;
        
        // Check limits
        if ($isCamera) {
            if ($counts['cameras'] >= $limits['max_cameras']) {
                return [
                    'success' => false,
                    'error' => "Camera limit reached ({$limits['max_cameras']}). Remove a camera or upgrade your plan.",
                    'limit' => $limits['max_cameras'],
                    'current' => $counts['cameras']
                ];
            }
        } else {
            if ($counts['devices'] >= $limits['max_devices']) {
                return [
                    'success' => false,
                    'error' => "Device limit reached ({$limits['max_devices']}). Remove a device or upgrade your plan.",
                    'limit' => $limits['max_devices'],
                    'current' => $counts['devices']
                ];
            }
        }
        
        // Check for existing device by MAC
        $existing = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND mac_address = ?",
            [$userId, $deviceData['mac_address'] ?? null]
        );
        
        if ($existing) {
            // Update existing device
            Database::execute('devices',
                "UPDATE user_devices SET 
                    name = COALESCE(?, name),
                    device_type = COALESCE(?, device_type),
                    status = 'active',
                    last_seen = datetime('now')
                 WHERE id = ?",
                [$deviceData['name'] ?? null, $deviceData['device_type'] ?? null, $existing['id']]
            );
            
            return [
                'success' => true,
                'device_id' => $existing['id'],
                'message' => 'Device updated'
            ];
        }
        
        // Create new device
        Database::execute('devices',
            "INSERT INTO user_devices (user_id, name, device_type, mac_address, ip_address, is_camera, vendor, status, created_at, last_seen)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', datetime('now'), datetime('now'))",
            [
                $userId,
                $deviceData['name'] ?? 'Unknown Device',
                $deviceData['device_type'] ?? 'unknown',
                $deviceData['mac_address'] ?? null,
                $deviceData['ip_address'] ?? null,
                $isCamera ? 1 : 0,
                $deviceData['vendor'] ?? null
            ]
        );
        
        $deviceId = Database::getConnection('devices')->lastInsertId();
        
        return [
            'success' => true,
            'device_id' => $deviceId,
            'message' => 'Device registered'
        ];
    }
    
    /**
     * Remove a device
     */
    public static function removeDevice($userId, $deviceId) {
        // Verify ownership
        $device = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE id = ? AND user_id = ?",
            [$deviceId, $userId]
        );
        
        if (!$device) {
            return ['success' => false, 'error' => 'Device not found'];
        }
        
        // Soft delete
        Database::execute('devices',
            "UPDATE user_devices SET status = 'removed', removed_at = datetime('now') WHERE id = ?",
            [$deviceId]
        );
        
        return ['success' => true, 'message' => 'Device removed'];
    }
    
    /**
     * Swap device (replace old with new)
     */
    public static function swapDevice($userId, $oldDeviceId, $newDeviceData) {
        // Remove old device
        $removeResult = self::removeDevice($userId, $oldDeviceId);
        
        if (!$removeResult['success']) {
            return $removeResult;
        }
        
        // Register new device
        return self::registerDevice($userId, $newDeviceData);
    }
    
    /**
     * Mark device as camera
     */
    public static function setAsCamera($userId, $deviceId, $isCamera = true) {
        // Verify ownership
        $device = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE id = ? AND user_id = ?",
            [$deviceId, $userId]
        );
        
        if (!$device) {
            return ['success' => false, 'error' => 'Device not found'];
        }
        
        // Check camera limits if marking as camera
        if ($isCamera) {
            $limits = self::getUserLimits($userId);
            $counts = self::getCounts($userId);
            
            if ($counts['cameras'] >= $limits['max_cameras']) {
                return [
                    'success' => false,
                    'error' => "Camera limit reached ({$limits['max_cameras']})"
                ];
            }
        }
        
        Database::execute('devices',
            "UPDATE user_devices SET is_camera = ? WHERE id = ?",
            [$isCamera ? 1 : 0, $deviceId]
        );
        
        return ['success' => true, 'message' => $isCamera ? 'Marked as camera' : 'Marked as device'];
    }
    
    /**
     * Get camera-specific info
     */
    public static function getCameras($userId) {
        $limits = self::getUserLimits($userId);
        
        $cameras = Database::queryAll('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND is_camera = 1 AND status = 'active'",
            [$userId]
        );
        
        return [
            'cameras' => $cameras,
            'count' => count($cameras),
            'limit' => $limits['max_cameras'],
            'allowed_servers' => $limits['camera_server'] === 'any' ? ['NY', 'TX', 'CAN', 'STL'] : ['NY']
        ];
    }
}

// Handle requests
switch ($action) {
    
    case 'list':
        $devices = DeviceManager::getDevices($user['id']);
        $limits = DeviceManager::getUserLimits($user['id']);
        $counts = DeviceManager::getCounts($user['id']);
        
        Response::success([
            'devices' => $devices,
            'counts' => $counts,
            'limits' => $limits
        ]);
        break;
        
    case 'register':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        $result = DeviceManager::registerDevice($user['id'], $input);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400, $result);
        }
        break;
        
    case 'remove':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['device_id'])) {
            Response::error('Device ID required', 400);
        }
        
        $result = DeviceManager::removeDevice($user['id'], $input['device_id']);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    case 'swap':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['old_device_id'])) {
            Response::error('Old device ID required', 400);
        }
        
        $result = DeviceManager::swapDevice($user['id'], $input['old_device_id'], $input['new_device'] ?? []);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    case 'set_camera':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['device_id'])) {
            Response::error('Device ID required', 400);
        }
        
        $result = DeviceManager::setAsCamera($user['id'], $input['device_id'], $input['is_camera'] ?? true);
        
        if ($result['success']) {
            Response::success($result);
        } else {
            Response::error($result['error'], 400);
        }
        break;
        
    case 'cameras':
        $result = DeviceManager::getCameras($user['id']);
        Response::success($result);
        break;
        
    case 'limits':
        $limits = DeviceManager::getUserLimits($user['id']);
        $counts = DeviceManager::getCounts($user['id']);
        
        Response::success([
            'limits' => $limits,
            'counts' => $counts,
            'remaining' => [
                'devices' => max(0, $limits['max_devices'] - $counts['devices']),
                'cameras' => max(0, $limits['max_cameras'] - $counts['cameras'])
            ]
        ]);
        break;
        
    default:
        Response::error('Invalid action', 400);
}
