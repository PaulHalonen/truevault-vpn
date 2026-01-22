<?php
/**
 * List Port Forwarding Rules - SQLITE3 VERSION
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    $pfDb = Database::getInstance('port_forwards');
    $devicesDb = Database::getInstance('devices');
    
    $stmt = $pfDb->prepare("
        SELECT * FROM port_forwarding_rules 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $rules = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Get device name if linked
        $deviceName = null;
        if ($row['device_id']) {
            $dStmt = $devicesDb->prepare("SELECT device_name FROM devices WHERE id = :id");
            $dStmt->bindValue(':id', $row['device_id'], SQLITE3_INTEGER);
            $dResult = $dStmt->execute();
            $device = $dResult->fetchArray(SQLITE3_ASSOC);
            $deviceName = $device['device_name'] ?? null;
        }
        
        $rules[] = [
            'id' => (int)$row['id'],
            'rule_name' => $row['rule_name'],
            'device_id' => $row['device_id'] ? (int)$row['device_id'] : null,
            'device_name' => $deviceName,
            'external_port' => (int)$row['external_port'],
            'internal_port' => (int)$row['internal_port'],
            'protocol' => $row['protocol'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'rules' => $rules, 'count' => count($rules)]);
    
} catch (Exception $e) {
    logError('List port rules failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to list rules']);
}
