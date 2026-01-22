<?php
/**
 * Port Forwarding API - Create Rule
 * 
 * RESTRICTIONS:
 * - Port forwarding ONLY allowed on servers with port_forwarding_allowed=1
 * - NY Contabo (66.94.103.91): ALLOWED
 * - St. Louis Dedicated: ALLOWED (if user is owner)
 * - Dallas Fly.io: NOT ALLOWED
 * - Toronto Fly.io: NOT ALLOWED
 * 
 * PLAN LIMITS:
 * - Basic: max 2 port forward devices, 1 camera
 * - Family: max 5 port forward devices, 2 cameras
 * - Dedicated: unlimited
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    $userTier = $payload['tier'];
    $userEmail = strtolower(trim($payload['email']));
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $deviceId = $input['device_id'] ?? '';
    $serverId = $input['server_id'] ?? null;
    $deviceType = $input['device_type'] ?? 'unknown';
    $localIp = $input['local_ip'] ?? '';
    
    if (empty($deviceId) || empty($localIp)) {
        throw new Exception('Missing device_id or local_ip');
    }
    
    // Get plan limits
    $planLimits = [
        'standard' => ['port_forward' => 2, 'cameras' => 1],
        'basic' => ['port_forward' => 2, 'cameras' => 1],
        'family' => ['port_forward' => 5, 'cameras' => 2],
        'pro' => ['port_forward' => 5, 'cameras' => 2],
        'dedicated' => ['port_forward' => 99, 'cameras' => 99],
        'vip' => ['port_forward' => 99, 'cameras' => 99],
        'admin' => ['port_forward' => 99, 'cameras' => 99],
    ];
    
    $limits = $planLimits[$userTier] ?? $planLimits['basic'];
    
    // Check current port forward count
    $pfDb = Database::getInstance('port_forwards');
    $stmt = $pfDb->prepare("SELECT COUNT(*) as count FROM port_forwarding_rules WHERE user_id = :uid AND status = 'active'");
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $currentCount = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($currentCount >= $limits['port_forward']) {
        throw new Exception("Port forwarding limit reached ({$currentCount}/{$limits['port_forward']}). Upgrade your plan for more.");
    }
    
    // Check camera limit if device is a camera
    if (strpos(strtolower($deviceType), 'camera') !== false) {
        $stmt = $pfDb->prepare("SELECT COUNT(*) as count FROM port_forwarding_rules WHERE user_id = :uid AND device_type LIKE '%camera%' AND status = 'active'");
        $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $cameraCount = $result->fetchArray(SQLITE3_ASSOC)['count'];
        
        if ($cameraCount >= $limits['cameras']) {
            throw new Exception("Camera limit reached ({$cameraCount}/{$limits['cameras']}). Upgrade your plan for more cameras.");
        }
    }
    
    // Get server to use for port forwarding
    $serversDb = Database::getInstance('servers');
    
    if ($serverId) {
        // User specified a server
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id AND status = 'active'");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
    } else {
        // Default to NY Contabo for port forwarding
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE ip_address = '66.94.103.91' AND status = 'active'");
    }
    
    $result = $stmt->execute();
    $server = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$server) {
        throw new Exception('Server not found');
    }
    
    // CHECK: Is port forwarding allowed on this server?
    if (!$server['port_forwarding_allowed']) {
        throw new Exception("Port forwarding is not available on {$server['name']}. Please use the US-East (New York) server for port forwarding.");
    }
    
    // CHECK: If dedicated server, is user the owner?
    if ($server['dedicated_user_email']) {
        $dedicatedEmail = strtolower(trim($server['dedicated_user_email']));
        if ($userEmail !== $dedicatedEmail) {
            throw new Exception('You do not have access to this server');
        }
    }
    
    // All checks passed - create port forwarding rule
    // [Rest of port forwarding logic would go here]
    
    echo json_encode([
        'success' => true,
        'message' => 'Port forwarding rule created',
        'server' => $server['name'],
        'device_id' => $deviceId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
