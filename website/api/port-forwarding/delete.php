<?php
/**
 * TrueVault VPN - Delete Port Forward Device API
 * 
 * PURPOSE: Delete a discovered device
 * METHOD: POST
 * AUTHENTICATION: JWT required
 * 
 * REQUEST BODY:
 * {
 *   "device_id": 123
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Device deleted"
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userId = $user['user_id'];
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['device_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: device_id'
        ]);
        exit;
    }
    
    $deviceId = (int)$input['device_id'];
    
    // Get database connection
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    
    // Verify device belongs to user
    $stmt = $devicesConn->prepare("
        SELECT device_id
        FROM port_forward_devices
        WHERE device_id = ? AND user_id = ?
    ");
    $stmt->execute([$deviceId, $userId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Device not found'
        ]);
        exit;
    }
    
    // Delete device
    $stmt = $devicesConn->prepare("
        DELETE FROM port_forward_devices
        WHERE device_id = ?
    ");
    $stmt->execute([$deviceId]);
    
    // Log deletion
    error_log("Port forward device $deviceId deleted by user $userId");
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Device deleted'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Delete Port Forward Device Error: ' . $e->getMessage());
}
