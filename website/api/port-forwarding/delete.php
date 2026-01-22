<?php
/**
 * Port Forwarding - Delete Rule API
 * 
 * METHOD: POST/DELETE
 * ENDPOINT: /api/port-forwarding/delete.php
 * REQUIRES: Bearer token
 * 
 * REQUEST: { "rule_id": 123 }
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $ruleId = (int)($data['rule_id'] ?? $_GET['rule_id'] ?? 0);
    
    if (!$ruleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Rule ID required']);
        exit;
    }
    
    $pfDb = Database::getInstance('port_forwards');
    
    // Verify ownership
    $stmt = $pfDb->prepare("SELECT rule_name FROM port_forwarding_rules WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $ruleId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rule = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$rule) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rule not found']);
        exit;
    }
    
    // Delete rule
    $stmt = $pfDb->prepare("DELETE FROM port_forwarding_rules WHERE id = :id");
    $stmt->bindValue(':id', $ruleId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => "Rule '{$rule['rule_name']}' deleted"
    ]);
    
} catch (Exception $e) {
    logError('Delete port forwarding rule failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete rule']);
}
