<?php
/**
 * Port Forwarding - List Rules API
 * 
 * METHOD: GET
 * ENDPOINT: /api/port-forwarding/list.php
 * REQUIRES: Bearer token
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
    
    $stmt = $pfDb->prepare("
        SELECT r.id, r.rule_name, r.external_port, r.internal_port, r.protocol, 
               r.target_ip, r.enabled, r.created_at,
               d.device_name, d.device_type
        FROM port_forwarding_rules r
        LEFT JOIN discovered_devices d ON r.device_id = d.id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $rules = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rules[] = [
            'id' => (int)$row['id'],
            'name' => $row['rule_name'],
            'external_port' => (int)$row['external_port'],
            'internal_port' => (int)$row['internal_port'],
            'protocol' => $row['protocol'],
            'target_ip' => $row['target_ip'],
            'enabled' => (bool)$row['enabled'],
            'device_name' => $row['device_name'],
            'device_type' => $row['device_type'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'rules' => $rules, 'count' => count($rules)]);
    
} catch (Exception $e) {
    logError('List port forwarding rules failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to list rules']);
}
