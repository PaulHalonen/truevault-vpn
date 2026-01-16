<?php
/**
 * TrueVault VPN - List Devices API
 * 
 * PURPOSE: Get all devices for authenticated user
 * METHOD: GET
 * ENDPOINT: /api/devices/list.php
 * 
 * OUTPUT: Array of user's devices with status and config info
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Init
define('TRUEVAULT_INIT', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Load dependencies
    require_once __DIR__ . '/../../configs/config.php';
    require_once __DIR__ . '/../../includes/Database.php';
    require_once __DIR__ . '/../../includes/Auth.php';
    
    // Initialize Auth
    Auth::init(JWT_SECRET);
    
    // Authenticate
    $user = Auth::requireAuth();
    $userId = $user['id'];
    $maxDevices = $user['max_devices'];
    
    // Get user's devices
    $devicesDb = Database::getInstance('devices');
    $serversDb = Database::getInstance('servers');
    
    $devices = $devicesDb->queryAll(
        "SELECT 
            d.id,
            d.device_name,
            d.device_type,
            d.public_key,
            d.ipv4_address,
            d.current_server_id,
            d.status,
            d.last_handshake,
            d.total_bytes_sent,
            d.total_bytes_received,
            d.created_at,
            d.updated_at
        FROM devices d
        WHERE d.user_id = {$userId}
        ORDER BY d.created_at DESC"
    );
    
    // Get server info for each device
    $enrichedDevices = [];
    foreach ($devices as $device) {
        $server = $serversDb->queryOne("SELECT name, location, endpoint FROM servers WHERE id = {$device['current_server_id']}");
        
        $enrichedDevices[] = [
            'id' => $device['id'],
            'name' => $device['device_name'],
            'type' => $device['device_type'],
            'ip_address' => $device['ipv4_address'],
            'status' => $device['status'],
            'server' => [
                'id' => $device['current_server_id'],
                'name' => $server['name'] ?? 'Unknown',
                'location' => $server['location'] ?? 'Unknown',
                'endpoint' => $server['endpoint'] ?? null
            ],
            'stats' => [
                'last_handshake' => $device['last_handshake'],
                'bytes_sent' => (int)$device['total_bytes_sent'],
                'bytes_received' => (int)$device['total_bytes_received']
            ],
            'created_at' => $device['created_at'],
            'updated_at' => $device['updated_at']
        ];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'devices' => $enrichedDevices,
        'count' => count($enrichedDevices),
        'limit' => $maxDevices,
        'available_slots' => $maxDevices - count(array_filter($enrichedDevices, fn($d) => $d['status'] === 'active'))
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
