<?php
/**
 * Delete Device API - SQLITE3 VERSION
 * 
 * PURPOSE: Remove a device from user's account
 * METHOD: DELETE or POST
 * ENDPOINT: /api/devices/delete.php
 * REQUIRES: Bearer token
 * 
 * REQUEST: { "device_id": 123 }
 * 
 * @created January 2026
 * @version 1.0.0
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
        SELECT id, device_name, current_server_id, ipv4_address 
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
    
    // Log event
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'device_deleted', 'device', :device_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['device_name' => $deviceName]), SQLITE3_TEXT);
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
