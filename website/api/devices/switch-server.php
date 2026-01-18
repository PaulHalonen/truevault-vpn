<?php
/**
 * TrueVault VPN - Switch Server API
 * 
 * PURPOSE: Switch device to a different VPN server
 * USE CASE: User switches from Canada for banking to USA for Netflix
 * METHOD: POST
 * ENDPOINT: /api/devices/switch-server.php
 * AUTHENTICATION: Required (JWT)
 * 
 * INPUT (JSON):
 * {
 *   "device_id": "dev_abc123",
 *   "server_id": 2
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "Switched to USA (Dallas)",
 *   "config": "[Interface]\nPrivateKey=...\n[Peer]\n..."
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // ============================================
    // AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    $userId = $user['user_id'];
    $userEmail = $user['email'];
    
    // ============================================
    // GET AND VALIDATE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $deviceId = $data['device_id'] ?? '';
    $newServerId = (int)($data['server_id'] ?? 0);
    
    if (empty($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    if ($newServerId <= 0) {
        throw new Exception('Valid server ID is required');
    }
    
    // ============================================
    // VERIFY DEVICE BELONGS TO USER
    // ============================================
    
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    $serversConn = $db->getConnection('servers');
    
    $stmt = $devicesConn->prepare(
        "SELECT id, device_name, public_key, ipv4_address, current_server_id
        FROM devices 
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
    
    // Check if already on this server
    if ($device['current_server_id'] == $newServerId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Device is already connected to this server'
        ]);
        exit;
    }
    
    // ============================================
    // VERIFY NEW SERVER EXISTS AND IS AVAILABLE
    // ============================================
    
    $stmt = $serversConn->prepare(
        "SELECT id, name, country, region, endpoint, public_key,
                is_dedicated, dedicated_user_email, status
        FROM servers 
        WHERE id = ?"
    );
    $stmt->execute([$newServerId]);
    $newServer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$newServer) {
        throw new Exception('Target server not found');
    }
    
    if ($newServer['status'] !== 'online') {
        throw new Exception('Target server is not currently available');
    }
    
    // Check if dedicated server belongs to this user
    if ($newServer['is_dedicated'] == 1) {
        if ($newServer['dedicated_user_email'] !== $userEmail) {
            throw new Exception('This server is not available for your account');
        }
    }
    
    // ============================================
    // UPDATE DEVICE SERVER
    // ============================================
    
    $stmt = $devicesConn->prepare(
        "UPDATE devices 
        SET current_server_id = ?,
            last_handshake = NULL
        WHERE device_id = ? AND user_id = ?"
    );
    $stmt->execute([$newServerId, $deviceId, $userId]);
    
    // ============================================
    // GENERATE NEW CONFIG FOR NEW SERVER
    // ============================================
    
    // Get private key from stored public key (we need to regenerate config)
    // Note: In production, private key should be retrieved from secure storage
    // For now, we'll use the existing public key and generate new config
    
    $configContent = "[Interface]\n";
    $configContent .= "# Private key was provided during initial setup\n";
    $configContent .= "# Keep your existing PrivateKey line\n";
    $configContent .= "Address = " . $device['ipv4_address'] . "/32\n";
    $configContent .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $configContent .= "\n";
    $configContent .= "[Peer]\n";
    $configContent .= "PublicKey = " . $newServer['public_key'] . "\n";
    $configContent .= "Endpoint = " . $newServer['endpoint'] . "\n";
    $configContent .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $configContent .= "PersistentKeepalive = 25\n";
    
    // ============================================
    // RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Switched to ' . $newServer['name'],
        'device' => [
            'name' => $device['device_name'],
            'ipv4_address' => $device['ipv4_address']
        ],
        'server' => [
            'id' => $newServer['id'],
            'name' => $newServer['name'],
            'country' => $newServer['country'],
            'region' => $newServer['region']
        ],
        'config' => $configContent,
        'note' => 'Download new config or update your [Peer] section with new server details'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Switch Server API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
