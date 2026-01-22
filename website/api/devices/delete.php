<?php
/**
 * Delete Device API - SERVER-SIDE PEER REMOVAL
 * 
 * PURPOSE: Remove device and its WireGuard peer from VPN server
 * METHOD: DELETE or POST
 * ENDPOINT: /api/devices/delete.php
 * REQUIRES: Bearer token
 * 
 * ARCHITECTURE:
 * 1. User requests delete
 * 2. Web server calls VPN server API to remove peer
 * 3. VPN server removes peer from WireGuard
 * 4. Web server deletes from database
 * 
 * @created January 2026
 * @version 2.0.0 - Now removes peer from VPN server
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

/**
 * Call VPN Server API to remove peer
 */
function callVpnServerApi($serverIp, $apiPort, $apiSecret, $endpoint, $data = []) {
    $url = "http://{$serverIp}:{$apiPort}{$endpoint}";
    
    $options = [
        'http' => [
            'method' => empty($data) ? 'GET' : 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiSecret
            ],
            'content' => empty($data) ? '' : json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to connect to VPN server'];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid response from VPN server'];
    }
    
    return $decoded;
}

try {
    // Authenticate user
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    // Get device ID from input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $deviceId = (int)($data['device_id'] ?? $_GET['device_id'] ?? 0);
    
    if (!$deviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Device ID required']);
        exit;
    }
    
    // Get device and verify ownership
    $devicesDb = Database::getInstance('devices');
    
    $stmt = $devicesDb->prepare("
        SELECT id, device_name, current_server_id, ipv4_address, public_key
        FROM devices 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $device = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found or not owned by you']);
        exit;
    }
    
    $serverId = $device['current_server_id'];
    $deviceName = $device['device_name'];
    $publicKey = $device['public_key'];
    
    // ============================================
    // STEP 1: REMOVE PEER FROM VPN SERVER
    // ============================================
    
    if ($serverId && $publicKey) {
        // Get server info
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $server = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($server) {
            // Get API secret
            $adminDb = Database::getInstance('admin');
            $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
            $stmt->bindValue(':key', 'server_api_secret_' . $server['id'], SQLITE3_TEXT);
            $result = $stmt->execute();
            $secretRow = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!$secretRow) {
                $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'vpn_server_api_secret'");
                $result = $stmt->execute();
                $secretRow = $result->fetchArray(SQLITE3_ASSOC);
            }
            
            $apiSecret = $secretRow['setting_value'] ?? 'TRUEVAULT_API_SECRET_2026';
            $apiPort = $server['api_port'] ?? 8443;
            
            // Call VPN server to remove peer
            $vpnResponse = callVpnServerApi(
                $server['ip_address'],
                $apiPort,
                $apiSecret,
                '/api/remove-peer',
                ['public_key' => $publicKey]
            );
            
            // Log if removal failed (but continue with database deletion)
            if (!isset($vpnResponse['success']) || !$vpnResponse['success']) {
                $logsDb = Database::getInstance('logs');
                $stmt = $logsDb->prepare("
                    INSERT INTO error_log (error_type, message, context, created_at)
                    VALUES ('vpn_api', :msg, :ctx, datetime('now'))
                ");
                $stmt->bindValue(':msg', 'Failed to remove peer from VPN server: ' . ($vpnResponse['error'] ?? 'Unknown'), SQLITE3_TEXT);
                $stmt->bindValue(':ctx', json_encode([
                    'server' => $server['name'],
                    'device_id' => $deviceId,
                    'public_key' => substr($publicKey, 0, 20) . '...'
                ]), SQLITE3_TEXT);
                $stmt->execute();
            }
        }
    }
    
    // ============================================
    // STEP 2: DELETE FROM DATABASE
    // ============================================
    
    // Delete device configs first (foreign key constraint)
    $stmt = $devicesDb->prepare("DELETE FROM device_configs WHERE device_id = :id");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Delete device
    $stmt = $devicesDb->prepare("DELETE FROM devices WHERE id = :id");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Decrement server client count
    if ($serverId) {
        $serversDb = Database::getInstance('servers');
        $stmt = $serversDb->prepare("UPDATE servers SET current_clients = MAX(0, current_clients - 1) WHERE id = :id");
        $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // ============================================
    // STEP 3: LOG EVENT
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'device_deleted', 'device', :device_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode([
        'device_name' => $deviceName,
        'method' => 'vpn_server_api'
    ]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => "Device '{$deviceName}' deleted successfully"
    ]);
    
} catch (Exception $e) {
    logError('Delete device failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete device']);
}
