<?php
/**
 * TrueVault VPN - Device Management API
 * GET /api/devices/list.php - List user's devices
 * POST /api/devices/register.php - Register new device
 * POST /api/devices/remove.php - Remove device
 * POST /api/devices/swap.php - Swap device (for Family+ plans)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

class DeviceManager {
    
    /**
     * Get device limits for a user
     */
    public static function getLimits($userId) {
        $user = Auth::getUserById($userId);
        if (!$user) return null;
        
        // Check VIP first
        if (VIPManager::isVIP($user['email'])) {
            $vipDetails = VIPManager::getVIPDetails($user['email']);
            return [
                'max_devices' => $vipDetails['max_devices'],
                'max_cameras' => $vipDetails['max_cameras'],
                'can_swap' => true,
                'is_vip' => true
            ];
        }
        
        // Get from subscription
        $sub = BillingManager::getCurrentSubscription($userId);
        
        if (!$sub) {
            // Free tier - no devices
            return [
                'max_devices' => 0,
                'max_cameras' => 0,
                'can_swap' => false,
                'is_vip' => false
            ];
        }
        
        // Plans that can swap devices
        $canSwap = in_array($sub['plan_type'], ['family', 'dedicated', 'vip_basic', 'vip_dedicated']);
        
        return [
            'max_devices' => $sub['max_devices'] ?? 3,
            'max_cameras' => $sub['max_cameras'] ?? 1,
            'can_swap' => $canSwap,
            'is_vip' => false
        ];
    }
    
    /**
     * Get user's registered devices
     */
    public static function getDevices($userId) {
        return Database::queryAll('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    /**
     * Get user's registered cameras
     */
    public static function getCameras($userId) {
        return Database::queryAll('devices',
            "SELECT * FROM user_cameras WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    /**
     * Register a new device
     */
    public static function registerDevice($userId, $deviceData) {
        $limits = self::getLimits($userId);
        
        if (!$limits || $limits['max_devices'] <= 0) {
            return ['success' => false, 'error' => 'No active subscription'];
        }
        
        // Count current devices
        $currentCount = Database::queryOne('devices',
            "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND status = 'active'",
            [$userId]
        )['count'];
        
        if ($currentCount >= $limits['max_devices']) {
            if ($limits['can_swap']) {
                return ['success' => false, 'error' => 'Device limit reached. Use swap to replace a device.', 'can_swap' => true];
            }
            return ['success' => false, 'error' => 'Device limit reached. Upgrade your plan for more devices.'];
        }
        
        // Generate device ID
        $deviceId = 'dev_' . bin2hex(random_bytes(8));
        
        Database::execute('devices',
            "INSERT INTO user_devices (user_id, device_id, name, type, mac_address, ip_address, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'active', datetime('now'))",
            [
                $userId,
                $deviceId,
                $deviceData['name'] ?? 'Unnamed Device',
                $deviceData['type'] ?? 'unknown',
                $deviceData['mac_address'] ?? null,
                $deviceData['ip_address'] ?? null
            ]
        );
        
        return [
            'success' => true,
            'device_id' => $deviceId,
            'remaining' => $limits['max_devices'] - $currentCount - 1
        ];
    }
    
    /**
     * Register a camera
     */
    public static function registerCamera($userId, $cameraData) {
        $limits = self::getLimits($userId);
        $user = Auth::getUserById($userId);
        
        if (!$limits || $limits['max_cameras'] <= 0) {
            return ['success' => false, 'error' => 'No camera slots available'];
        }
        
        // Count current cameras
        $currentCount = Database::queryOne('devices',
            "SELECT COUNT(*) as count FROM user_cameras WHERE user_id = ? AND status = 'active'",
            [$userId]
        )['count'];
        
        if ($currentCount >= $limits['max_cameras']) {
            return ['success' => false, 'error' => 'Camera limit reached. Upgrade your plan for more cameras.'];
        }
        
        // Check server restriction
        $serverId = $cameraData['server_id'] ?? 1;
        $sub = BillingManager::getCurrentSubscription($userId);
        
        // Basic/Family plans can only use cameras on NY server
        if ($sub && in_array($sub['plan_type'], ['basic', 'family'])) {
            if ($serverId != 1) {
                return ['success' => false, 'error' => 'Your plan only allows cameras on the NY server. Upgrade to Dedicated for any server.'];
            }
        }
        
        // Generate camera ID
        $cameraId = 'cam_' . bin2hex(random_bytes(8));
        
        Database::execute('devices',
            "INSERT INTO user_cameras (user_id, camera_id, name, type, ip_address, port, server_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', datetime('now'))",
            [
                $userId,
                $cameraId,
                $cameraData['name'] ?? 'IP Camera',
                $cameraData['type'] ?? 'generic',
                $cameraData['ip_address'],
                $cameraData['port'] ?? 554,
                $serverId
            ]
        );
        
        return [
            'success' => true,
            'camera_id' => $cameraId,
            'remaining' => $limits['max_cameras'] - $currentCount - 1
        ];
    }
    
    /**
     * Remove a device
     */
    public static function removeDevice($userId, $deviceId) {
        $device = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND device_id = ? AND status = 'active'",
            [$userId, $deviceId]
        );
        
        if (!$device) {
            return ['success' => false, 'error' => 'Device not found'];
        }
        
        Database::execute('devices',
            "UPDATE user_devices SET status = 'removed', removed_at = datetime('now') WHERE device_id = ?",
            [$deviceId]
        );
        
        return ['success' => true];
    }
    
    /**
     * Swap a device (replace one with another)
     */
    public static function swapDevice($userId, $oldDeviceId, $newDeviceData) {
        $limits = self::getLimits($userId);
        
        if (!$limits['can_swap']) {
            return ['success' => false, 'error' => 'Your plan does not support device swapping. Upgrade to Family or higher.'];
        }
        
        // Verify old device exists
        $oldDevice = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE user_id = ? AND device_id = ? AND status = 'active'",
            [$userId, $oldDeviceId]
        );
        
        if (!$oldDevice) {
            return ['success' => false, 'error' => 'Device to swap not found'];
        }
        
        // Remove old device
        Database::execute('devices',
            "UPDATE user_devices SET status = 'swapped', removed_at = datetime('now') WHERE device_id = ?",
            [$oldDeviceId]
        );
        
        // Register new device (bypass limit check since we just freed a slot)
        $deviceId = 'dev_' . bin2hex(random_bytes(8));
        
        Database::execute('devices',
            "INSERT INTO user_devices (user_id, device_id, name, type, mac_address, ip_address, swapped_from, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active', datetime('now'))",
            [
                $userId,
                $deviceId,
                $newDeviceData['name'] ?? 'Swapped Device',
                $newDeviceData['type'] ?? 'unknown',
                $newDeviceData['mac_address'] ?? null,
                $newDeviceData['ip_address'] ?? null,
                $oldDeviceId
            ]
        );
        
        // Log swap
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, created_at)
             VALUES (?, 'device_swap', ?, datetime('now'))",
            [$userId, json_encode(['old' => $oldDeviceId, 'new' => $deviceId])]
        );
        
        return [
            'success' => true,
            'new_device_id' => $deviceId,
            'message' => 'Device swapped successfully'
        ];
    }
}
