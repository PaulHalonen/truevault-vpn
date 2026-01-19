<?php
/**
 * TrueVault VPN - Delete Device API
 * 
 * PURPOSE: Remove a device from user's account
 * METHOD: DELETE (or POST with _method=DELETE)
 * ENDPOINT: /api/devices/delete.php
 * AUTHENTICATION: Required (JWT)
 * 
 * INPUT (JSON):
 * {
 *   "device_id": "dev_abc123"
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Device removed successfully"
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
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow POST or DELETE
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST or DELETE.'
    ]);
    exit;
}

try {
    // ============================================
    // AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    $userId = $user['user_id'];
    
    // ============================================
    // GET AND VALIDATE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $deviceId = $data['device_id'] ?? '';
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    // ============================================
    // VERIFY DEVICE BELONGS TO USER
    // ============================================
    
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    
    $stmt = $devicesConn->prepare(
        "SELECT id, device_name FROM devices 
        WHERE device_id = ? AND user_id = ?"
    );
    $stmt->execute([$deviceId, $userId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Device not found or does not belong to you'
        ]);
        exit;
    }
    
    // ============================================
    // DELETE DEVICE
    // ============================================
    
    $stmt = $devicesConn->prepare(
        "DELETE FROM devices WHERE device_id = ? AND user_id = ?"
    );
    $stmt->execute([$deviceId, $userId]);
    
    // Log activity
    require_once __DIR__ . '/../../includes/Integration.php';
    Integration::logActivity($userId, 'device_deleted', "Device '{$device['device_name']}' deleted");
    
    // ============================================
    // RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Device "' . $device['device_name'] . '" removed successfully'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Delete Device API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
