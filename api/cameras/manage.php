<?php
/**
 * TrueVault VPN - Camera Management
 * Handles IP camera registration with server restrictions
 * 
 * Camera Rules:
 * - Basic/Family: Cameras ONLY on NY server
 * - Dedicated: Cameras on any server
 * - VIP: Based on tier
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$user = Auth::requireAuth();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    
    case 'list':
        Response::requireMethod('GET');
        
        $cameras = Database::queryAll('cameras',
            "SELECT c.*, s.name as server_name, s.location as server_location
             FROM user_cameras c
             LEFT JOIN vpn_servers s ON s.id = c.server_id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC",
            [$user['id']]
        );
        
        $limits = getCameraLimits($user);
        $activeCount = count(array_filter($cameras, fn($c) => $c['status'] === 'active'));
        
        Response::success([
            'cameras' => $cameras,
            'limits' => [
                'max_cameras' => $limits['max_cameras'],
                'active_cameras' => $activeCount,
                'allowed_servers' => $limits['allowed_servers'],
                'can_add_more' => $activeCount < $limits['max_cameras']
            ]
        ]);
        break;
    
    case 'register':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        // Validate
        if (empty($input['camera_name']) || empty($input['local_ip'])) {
            Response::error('Camera name and local IP required', 400);
        }
        
        // Check limits
        $limits = getCameraLimits($user);
        $activeCount = Database::queryOne('cameras',
            "SELECT COUNT(*) as cnt FROM user_cameras WHERE user_id = ? AND status = 'active'",
            [$user['id']]
        );
        
        if (($activeCount['cnt'] ?? 0) >= $limits['max_cameras']) {
            Response::error("Camera limit reached ({$limits['max_cameras']}). Remove a camera or upgrade your plan.", 403);
        }
        
        // Determine server
        $serverId = $input['server_id'] ?? null;
        
        if (!$serverId) {
            // Default to NY (server 1)
            $serverId = 1;
        }
        
        // Check server access
        if (!in_array($serverId, $limits['allowed_servers'])) {
            $serverNames = [1 => 'NY', 2 => 'STL', 3 => 'TX', 4 => 'CAN'];
            Response::error("Your plan only allows cameras on: " . implode(', ', array_map(fn($id) => $serverNames[$id] ?? $id, $limits['allowed_servers'])), 403);
        }
        
        // Register camera
        $cameraId = 'cam_' . bin2hex(random_bytes(6));
        
        // Detect camera type from vendor
        $vendor = $input['vendor'] ?? 'Unknown';
        $cameraType = detectCameraType($vendor);
        
        Database::execute('cameras',
            "INSERT INTO user_cameras (user_id, camera_id, camera_name, local_ip, camera_type, vendor, server_id, external_port, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', datetime('now'))",
            [$user['id'], $cameraId, $input['camera_name'], $input['local_ip'], $cameraType, $vendor, $serverId, null]
        );
        
        // Create port forwarding rule
        $portResult = createPortForwarding($user['id'], $cameraId, $input['local_ip'], $serverId);
        
        Response::success([
            'camera_id' => $cameraId,
            'server_id' => $serverId,
            'port_forwarding' => $portResult,
            'message' => 'Camera registered successfully'
        ]);
        break;
    
    case 'remove':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['camera_id'])) {
            Response::error('Camera ID required', 400);
        }
        
        // Verify ownership
        $camera = Database::queryOne('cameras',
            "SELECT * FROM user_cameras WHERE camera_id = ? AND user_id = ?",
            [$input['camera_id'], $user['id']]
        );
        
        if (!$camera) {
            Response::error('Camera not found', 404);
        }
        
        // Remove port forwarding
        Database::execute('port_forwarding',
            "UPDATE port_forwards SET status = 'removed' WHERE camera_id = ?",
            [$input['camera_id']]
        );
        
        // Remove camera
        Database::execute('cameras',
            "UPDATE user_cameras SET status = 'removed', removed_at = datetime('now') WHERE camera_id = ?",
            [$input['camera_id']]
        );
        
        Response::success(['message' => 'Camera removed']);
        break;
    
    case 'get_stream_url':
        Response::requireMethod('GET');
        
        $cameraId = $_GET['camera_id'] ?? '';
        
        if (!$cameraId) {
            Response::error('Camera ID required', 400);
        }
        
        // Get camera with port forwarding
        $camera = Database::queryOne('cameras',
            "SELECT c.*, pf.external_port, s.ip_address as server_ip
             FROM user_cameras c
             LEFT JOIN port_forwards pf ON pf.camera_id = c.camera_id AND pf.status = 'active'
             LEFT JOIN vpn_servers s ON s.id = c.server_id
             WHERE c.camera_id = ? AND c.user_id = ?",
            [$cameraId, $user['id']]
        );
        
        if (!$camera) {
            Response::error('Camera not found', 404);
        }
        
        // Generate stream URL
        $streamUrl = null;
        if ($camera['external_port'] && $camera['server_ip']) {
            $streamUrl = "rtsp://{$camera['server_ip']}:{$camera['external_port']}";
        }
        
        Response::success([
            'camera' => $camera,
            'stream_url' => $streamUrl,
            'local_url' => "rtsp://{$camera['local_ip']}:554"
        ]);
        break;
    
    default:
        Response::error('Unknown action', 400);
}

/**
 * Get camera limits for user
 */
function getCameraLimits($user) {
    // Check VIP
    if (VIPManager::isVIP($user['email'])) {
        $vipLimits = VIPManager::getVIPLimits($user['email']);
        $vipDetails = VIPManager::getVIPDetails($user['email']);
        
        // VIP dedicated gets all servers
        if ($vipDetails['tier'] === 'vip_dedicated') {
            return [
                'max_cameras' => $vipLimits['max_cameras'],
                'allowed_servers' => [1, 2, 3, 4] // All servers
            ];
        }
        
        // VIP basic - NY only
        return [
            'max_cameras' => $vipLimits['max_cameras'],
            'allowed_servers' => [1] // NY only
        ];
    }
    
    // Get subscription
    $subscription = BillingManager::getCurrentSubscription($user['id']);
    
    if ($subscription) {
        // Dedicated plan gets all servers
        if ($subscription['plan_type'] === 'dedicated') {
            return [
                'max_cameras' => $subscription['max_cameras'] ?? 12,
                'allowed_servers' => [1, 3, 4] // All shared servers (not VIP)
            ];
        }
        
        // Basic/Family - NY only
        return [
            'max_cameras' => $subscription['max_cameras'] ?? 1,
            'allowed_servers' => [1] // NY only
        ];
    }
    
    // No subscription
    return ['max_cameras' => 0, 'allowed_servers' => []];
}

/**
 * Detect camera type from vendor
 */
function detectCameraType($vendor) {
    $vendor = strtolower($vendor);
    
    $typeMap = [
        'geeni' => 'tuya',
        'tuya' => 'tuya',
        'wyze' => 'wyze',
        'hikvision' => 'onvif',
        'dahua' => 'onvif',
        'amcrest' => 'onvif',
        'reolink' => 'onvif',
        'ring' => 'ring',
        'nest' => 'nest'
    ];
    
    foreach ($typeMap as $key => $type) {
        if (strpos($vendor, $key) !== false) {
            return $type;
        }
    }
    
    return 'generic';
}

/**
 * Create port forwarding rule for camera
 */
function createPortForwarding($userId, $cameraId, $localIp, $serverId) {
    // Find available external port (start from 10000)
    $lastPort = Database::queryOne('port_forwarding',
        "SELECT MAX(external_port) as max_port FROM port_forwards WHERE server_id = ?",
        [$serverId]
    );
    
    $externalPort = max(10000, ($lastPort['max_port'] ?? 9999) + 1);
    
    // RTSP default port is 554
    $internalPort = 554;
    
    Database::execute('port_forwarding',
        "INSERT INTO port_forwards (user_id, camera_id, server_id, internal_ip, internal_port, external_port, protocol, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 'tcp', 'active', datetime('now'))",
        [$userId, $cameraId, $serverId, $localIp, $internalPort, $externalPort]
    );
    
    return [
        'external_port' => $externalPort,
        'internal_port' => $internalPort,
        'protocol' => 'tcp'
    ];
}
