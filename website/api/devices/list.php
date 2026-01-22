<?php
/**
 * List User Devices API - SQLITE3 VERSION
 * 
 * PURPOSE: Return all devices for authenticated user
 * METHOD: GET
 * ENDPOINT: /api/devices/list.php
 * REQUIRES: Bearer token
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate user
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    $userTier = $payload['tier'];
    
    // Get device limits by tier
    $maxDevices = ['standard' => 3, 'pro' => 5, 'vip' => 999, 'admin' => 999];
    $limit = $maxDevices[$userTier] ?? 3;
    
    // Get devices from database
    $devicesDb = Database::getInstance('devices');
    
    $stmt = $devicesDb->prepare("
        SELECT d.id, d.device_name, d.device_type, d.ipv4_address, d.status,
               d.last_handshake, d.data_sent_bytes, d.data_received_bytes,
               d.current_server_id, d.created_at
        FROM devices d
        WHERE d.user_id = :user_id
        ORDER BY d.created_at DESC
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $devices = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Get server info
        $serversDb = Database::getInstance('servers');
        $serverStmt = $serversDb->prepare("SELECT name, location FROM servers WHERE id = :id");
        $serverStmt->bindValue(':id', $row['current_server_id'], SQLITE3_INTEGER);
        $serverResult = $serverStmt->execute();
        $server = $serverResult->fetchArray(SQLITE3_ASSOC);
        
        $devices[] = [
            'id' => (int)$row['id'],
            'name' => $row['device_name'],
            'type' => $row['device_type'],
            'ip_address' => $row['ipv4_address'],
            'status' => $row['status'],
            'server' => $server ? $server['name'] : 'Unknown',
            'server_location' => $server ? $server['location'] : 'Unknown',
            'last_handshake' => $row['last_handshake'],
            'data_sent' => (int)$row['data_sent_bytes'],
            'data_received' => (int)$row['data_received_bytes'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'devices' => $devices,
        'count' => count($devices),
        'limit' => $limit,
        'remaining' => $limit - count($devices)
    ]);
    
} catch (Exception $e) {
    logError('List devices failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to list devices']);
}
