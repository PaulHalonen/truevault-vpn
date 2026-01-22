<?php
/**
 * Delete Port Forwarding Rule - SQLITE3 VERSION
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $ruleId = (int)($input['rule_id'] ?? $_GET['rule_id'] ?? 0);
    
    if (!$ruleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Rule ID required']);
        exit;
    }
    
    $pfDb = Database::getInstance('port_forwards');
    
    // Verify ownership
    $stmt = $pfDb->prepare("SELECT id, rule_name FROM port_forwarding_rules WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $ruleId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rule = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$rule) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Rule not found']);
        exit;
    }
    
    // Delete
    $stmt = $pfDb->prepare("DELETE FROM port_forwarding_rules WHERE id = :id");
    $stmt->bindValue(':id', $ruleId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Log
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (:user_id, 'port_rule_deleted', 'port_rule', :rule_id, :details, :ip, datetime('now'))");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':rule_id', $ruleId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['rule_name' => $rule['rule_name']]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Rule deleted']);
    
} catch (Exception $e) {
    logError('Delete port rule failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete rule']);
}
