<?php
/**
 * TrueVault VPN - Delete Device API
 * 
 * PURPOSE: Remove a device from user's account
 * METHOD: DELETE or POST with _method=DELETE
 * ENDPOINT: /api/devices/delete.php?id=123
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
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow DELETE or POST (with _method override)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['_method']) && strtoupper($input['_method']) === 'DELETE') {
        $method = 'DELETE';
    }
}

if ($method !== 'DELETE' && $method !== 'POST') {
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
    
    // Get device ID from query string or body
    $deviceId = $_GET['id'] ?? null;
    if (!$deviceId && isset($input['device_id'])) {
        $deviceId = $input['device_id'];
    }
    
    if (!$deviceId || !is_numeric($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    $deviceId = (int)$deviceId;
    
    // Get device and verify ownership
    $devicesDb = Database::getInstance('devices');
    
    $device = $devicesDb->queryOne(
        "SELECT id, device_name, current_server_id, status FROM devices 
         WHERE id = {$deviceId} AND user_id = {$userId}"
    );
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found or not owned by you']);
        exit;
    }
    
    // Soft delete - set status to 'deleted' instead of removing
    $devicesDb->update('devices', [
        'status' => 'deleted',
        'updated_at' => date('Y-m-d H:i:s')
    ], "id = {$deviceId}");
    
    // Update server client count
    if ($device['status'] === 'active') {
        $serversDb = Database::getInstance('servers');
        $serversDb->exec("UPDATE servers SET current_clients = MAX(0, current_clients - 1) WHERE id = {$device['current_server_id']}");
    }
    
    // Log deletion
    try {
        $logsDb = Database::getInstance('logs');
        $logsDb->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'device_deleted',
            'entity_type' => 'device',
            'entity_id' => $deviceId,
            'details' => json_encode(['device_name' => $device['device_name']]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Log failure not critical
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Device '{$device['device_name']}' has been removed"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
