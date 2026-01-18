<?php
/**
 * TrueVault VPN - List User Devices API
 * 
 * PURPOSE: Return list of user's registered devices
 * METHOD: GET
 * ENDPOINT: /api/devices/list.php
 * AUTHENTICATION: Required (JWT)
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "devices": [
 *     {
 *       "id": 1,
 *       "device_id": "dev_abc123",
 *       "name": "iPhone",
 *       "type": "mobile",
 *       "ipv4_address": "10.8.0.2",
 *       "server": "USA (Dallas)",
 *       "status": "active",
 *       "created_at": "2026-01-18 14:05:00",
 *       "last_handshake": "2026-01-18 15:30:00"
 *     }
 *   ],
 *   "count": 1,
 *   "limit": 3
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration and dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

try {
    // ============================================
    // AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    $userId = $user['user_id'];
    $userTier = $user['tier'] ?? 'standard';
    
    // ============================================
    // QUERY USER'S DEVICES
    // ============================================
    
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    $serversConn = $db->getConnection('servers');
    
    // Get all devices for this user
    $stmt = $devicesConn->prepare(
        "SELECT 
            id,
            device_id,
            device_name,
            device_type,
            ipv4_address,
            current_server_id,
            status,
            created_at,
            last_handshake
        FROM devices
        WHERE user_id = ?
        ORDER BY created_at DESC"
    );
    $stmt->execute([$userId]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // ENRICH WITH SERVER INFORMATION
    // ============================================
    
    $formattedDevices = [];
    
    foreach ($devices as $device) {
        // Get server info
        $serverStmt = $serversConn->prepare(
            "SELECT name, country, region FROM servers WHERE id = ?"
        );
        $serverStmt->execute([$device['current_server_id']]);
        $server = $serverStmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate connection status
        $isConnected = false;
        if ($device['last_handshake']) {
            $lastHandshake = strtotime($device['last_handshake']);
            $now = time();
            // Consider connected if handshake within last 5 minutes
            $isConnected = ($now - $lastHandshake) < 300;
        }
        
        // Format device data
        $formattedDevices[] = [
            'id' => (int)$device['id'],
            'device_id' => $device['device_id'],
            'name' => $device['device_name'],
            'type' => $device['device_type'],
            'ipv4_address' => $device['ipv4_address'],
            'server' => $server['name'] ?? 'Unknown',
            'server_country' => $server['country'] ?? 'unknown',
            'server_region' => $server['region'] ?? 'unknown',
            'status' => $device['status'],
            'is_connected' => $isConnected,
            'created_at' => $device['created_at'],
            'last_handshake' => $device['last_handshake']
        ];
    }
    
    // ============================================
    // GET DEVICE LIMIT FOR USER'S TIER
    // ============================================
    
    $deviceLimits = [
        'standard' => 3,
        'pro' => 5,
        'vip' => 999,
        'admin' => 999
    ];
    
    $maxDevices = $deviceLimits[$userTier] ?? 3;
    
    // ============================================
    // RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'devices' => $formattedDevices,
        'count' => count($formattedDevices),
        'limit' => $maxDevices,
        'tier' => $userTier
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Devices List API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load devices',
        'message' => $e->getMessage()
    ]);
}
