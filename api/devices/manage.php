<?php
/**
 * TrueVault VPN - Device Management
 * Handles device registration, limits, and swapping
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$user = Auth::requireAuth();

// Get action from query
$action = $_GET['action'] ?? 'list';

switch ($action) {
    
    case 'list':
        Response::requireMethod('GET');
        
        $devices = Database::queryAll('devices',
            "SELECT * FROM user_devices WHERE user_id = ? ORDER BY is_primary DESC, last_active DESC",
            [$user['id']]
        );
        
        // Get limits
        $limits = getDeviceLimits($user);
        $activeCount = count(array_filter($devices, fn($d) => $d['status'] === 'active'));
        
        Response::success([
            'devices' => $devices,
            'limits' => [
                'max_devices' => $limits['max_devices'],
                'max_cameras' => $limits['max_cameras'],
                'active_devices' => $activeCount,
                'can_add_more' => $activeCount < $limits['max_devices']
            ]
        ]);
        break;
    
    case 'register':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        // Validate input
        if (empty($input['device_name']) || empty($input['device_type'])) {
            Response::error('Device name and type required', 400);
        }
        
        // Check device limits
        $limits = getDeviceLimits($user);
        $activeCount = Database::queryOne('devices',
            "SELECT COUNT(*) as cnt FROM user_devices WHERE user_id = ? AND status = 'active'",
            [$user['id']]
        );
        
        if (($activeCount['cnt'] ?? 0) >= $limits['max_devices']) {
            Response::error("Device limit reached ({$limits['max_devices']}). Remove a device or upgrade your plan.", 403);
        }
        
        // Check if camera and camera limits
        $deviceType = strtolower($input['device_type']);
        if (in_array($deviceType, ['camera', 'ip_camera', 'ipcamera'])) {
            $cameraCount = Database::queryOne('devices',
                "SELECT COUNT(*) as cnt FROM user_devices WHERE user_id = ? AND device_type LIKE '%camera%' AND status = 'active'",
                [$user['id']]
            );
            
            if (($cameraCount['cnt'] ?? 0) >= $limits['max_cameras']) {
                Response::error("Camera limit reached ({$limits['max_cameras']}). Remove a camera or upgrade your plan.", 403);
            }
        }
        
        // Register device
        $deviceId = generateDeviceId();
        
        Database::execute('devices',
            "INSERT INTO user_devices (user_id, device_id, device_name, device_type, status, registered_at, last_active)
             VALUES (?, ?, ?, ?, 'active', datetime('now'), datetime('now'))",
            [$user['id'], $deviceId, $input['device_name'], $input['device_type']]
        );
        
        Response::success([
            'device_id' => $deviceId,
            'message' => 'Device registered successfully'
        ]);
        break;
    
    case 'remove':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['device_id'])) {
            Response::error('Device ID required', 400);
        }
        
        // Verify ownership
        $device = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE device_id = ? AND user_id = ?",
            [$input['device_id'], $user['id']]
        );
        
        if (!$device) {
            Response::error('Device not found', 404);
        }
        
        // Remove device
        Database::execute('devices',
            "UPDATE user_devices SET status = 'removed', removed_at = datetime('now') WHERE device_id = ?",
            [$input['device_id']]
        );
        
        // Also remove VPN peer if exists
        Database::execute('vpn',
            "UPDATE user_peers SET status = 'removed' WHERE user_id = ? AND status = 'active'",
            [$user['id']]
        );
        
        Response::success(['message' => 'Device removed']);
        break;
    
    case 'swap':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['old_device_id']) || empty($input['new_device_name'])) {
            Response::error('Old device ID and new device name required', 400);
        }
        
        // Verify ownership
        $oldDevice = Database::queryOne('devices',
            "SELECT * FROM user_devices WHERE device_id = ? AND user_id = ? AND status = 'active'",
            [$input['old_device_id'], $user['id']]
        );
        
        if (!$oldDevice) {
            Response::error('Device not found', 404);
        }
        
        // Check swap cooldown (once per 24 hours per device)
        $lastSwap = Database::queryOne('devices',
            "SELECT * FROM device_swaps WHERE user_id = ? AND old_device_id = ? AND swapped_at > datetime('now', '-24 hours')",
            [$user['id'], $input['old_device_id']]
        );
        
        if ($lastSwap) {
            Response::error('Device swap cooldown: wait 24 hours between swaps', 429);
        }
        
        // Deactivate old device
        Database::execute('devices',
            "UPDATE user_devices SET status = 'swapped', removed_at = datetime('now') WHERE device_id = ?",
            [$input['old_device_id']]
        );
        
        // Register new device
        $newDeviceId = generateDeviceId();
        $newType = $input['new_device_type'] ?? $oldDevice['device_type'];
        
        Database::execute('devices',
            "INSERT INTO user_devices (user_id, device_id, device_name, device_type, status, registered_at, last_active)
             VALUES (?, ?, ?, ?, 'active', datetime('now'), datetime('now'))",
            [$user['id'], $newDeviceId, $input['new_device_name'], $newType]
        );
        
        // Log swap
        Database::execute('devices',
            "INSERT INTO device_swaps (user_id, old_device_id, new_device_id, swapped_at)
             VALUES (?, ?, ?, datetime('now'))",
            [$user['id'], $input['old_device_id'], $newDeviceId]
        );
        
        Response::success([
            'old_device_id' => $input['old_device_id'],
            'new_device_id' => $newDeviceId,
            'message' => 'Device swapped successfully'
        ]);
        break;
    
    case 'set_primary':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['device_id'])) {
            Response::error('Device ID required', 400);
        }
        
        // Clear current primary
        Database::execute('devices',
            "UPDATE user_devices SET is_primary = 0 WHERE user_id = ?",
            [$user['id']]
        );
        
        // Set new primary
        Database::execute('devices',
            "UPDATE user_devices SET is_primary = 1 WHERE device_id = ? AND user_id = ?",
            [$input['device_id'], $user['id']]
        );
        
        Response::success(['message' => 'Primary device updated']);
        break;
    
    default:
        Response::error('Unknown action', 400);
}

/**
 * Get device limits for user
 */
function getDeviceLimits($user) {
    // Check VIP first
    if (VIPManager::isVIP($user['email'])) {
        return VIPManager::getVIPLimits($user['email']);
    }
    
    // Get from subscription
    $subscription = BillingManager::getCurrentSubscription($user['id']);
    
    if ($subscription) {
        return [
            'max_devices' => $subscription['max_devices'] ?? 3,
            'max_cameras' => $subscription['max_cameras'] ?? 1
        ];
    }
    
    // Default (no subscription)
    return ['max_devices' => 1, 'max_cameras' => 0];
}

/**
 * Generate unique device ID
 */
function generateDeviceId() {
    return 'dev_' . bin2hex(random_bytes(8));
}
