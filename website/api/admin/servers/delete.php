<?php
/**
 * TrueVault VPN - Admin Delete Server API
 * 
 * PURPOSE: Delete VPN server (admin only)
 * METHOD: POST
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * REQUEST BODY:
 * {
 *   "server_id": 1
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Server deleted successfully",
 *   "moved_devices": 5
 * }
 * 
 * NOTE: Devices using this server are moved to another available server
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
require_once __DIR__ . '/../../../configs/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    // Check admin access
    if (!in_array($userTier, ['admin', 'vip'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin or VIP access required.'
        ]);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['server_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: server_id'
        ]);
        exit;
    }
    
    $serverId = (int)$input['server_id'];
    
    // Get database connections
    $db = Database::getInstance();
    $serversConn = $db->getConnection('servers');
    $devicesConn = $db->getConnection('devices');
    
    // Check if server exists
    $stmt = $serversConn->prepare("SELECT server_id, name FROM servers WHERE server_id = ?");
    $stmt->execute([$serverId]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Server not found'
        ]);
        exit;
    }
    
    // Find devices using this server
    $stmt = $devicesConn->prepare("
        SELECT device_id, user_id
        FROM devices
        WHERE current_server_id = ?
    ");
    $stmt->execute([$serverId]);
    $affectedDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Find alternative server (first available online server)
    $stmt = $serversConn->prepare("
        SELECT server_id
        FROM servers
        WHERE server_id != ? AND status = 'online'
        LIMIT 1
    ");
    $stmt->execute([$serverId]);
    $altServer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (count($affectedDevices) > 0 && !$altServer) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete server: No alternative server available for ' . count($affectedDevices) . ' devices'
        ]);
        exit;
    }
    
    // Move devices to alternative server
    $movedDevices = 0;
    if (count($affectedDevices) > 0 && $altServer) {
        $stmt = $devicesConn->prepare("
            UPDATE devices
            SET current_server_id = ?,
                last_handshake = NULL
            WHERE current_server_id = ?
        ");
        $stmt->execute([$altServer['server_id'], $serverId]);
        $movedDevices = $stmt->rowCount();
    }
    
    // Delete server
    $stmt = $serversConn->prepare("DELETE FROM servers WHERE server_id = ?");
    $stmt->execute([$serverId]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Server deleted successfully',
        'moved_devices' => $movedDevices
    ]);
    
    // Log deletion
    error_log("Admin deleted server: {$server['name']} (ID: $serverId) by {$user['email']}. Moved $movedDevices devices.");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Delete Server Error: ' . $e->getMessage());
}
