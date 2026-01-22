<?php
/**
 * Add Port Forwarding Rule - SQLITE3 VERSION
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    $ruleName = trim($input['rule_name'] ?? '');
    $deviceId = $input['device_id'] ? (int)$input['device_id'] : null;
    $externalPort = (int)($input['external_port'] ?? 0);
    $internalPort = (int)($input['internal_port'] ?? 0);
    $protocol = strtolower($input['protocol'] ?? 'tcp');
    
    if (empty($ruleName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Rule name is required']);
        exit;
    }
    
    if ($externalPort < 1 || $externalPort > 65535 || $internalPort < 1 || $internalPort > 65535) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid port number (1-65535)']);
        exit;
    }
    
    if (!in_array($protocol, ['tcp', 'udp', 'both'])) {
        $protocol = 'tcp';
    }
    
    // Check for duplicate external port
    $pfDb = Database::getInstance('port_forwards');
    $stmt = $pfDb->prepare("SELECT id FROM port_forwarding_rules WHERE user_id = :user_id AND external_port = :port AND status = 'active'");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':port', $externalPort, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if ($result->fetchArray()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'External port already in use']);
        exit;
    }
    
    // Insert rule
    $stmt = $pfDb->prepare("
        INSERT INTO port_forwarding_rules 
        (user_id, device_id, rule_name, external_port, internal_port, protocol, status, created_at, updated_at)
        VALUES (:user_id, :device_id, :rule_name, :ext_port, :int_port, :protocol, 'active', datetime('now'), datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_id', $deviceId, $deviceId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':rule_name', $ruleName, SQLITE3_TEXT);
    $stmt->bindValue(':ext_port', $externalPort, SQLITE3_INTEGER);
    $stmt->bindValue(':int_port', $internalPort, SQLITE3_INTEGER);
    $stmt->bindValue(':protocol', $protocol, SQLITE3_TEXT);
    $stmt->execute();
    
    $ruleId = $pfDb->lastInsertRowID();
    
    // Log
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (:user_id, 'port_rule_created', 'port_rule', :rule_id, :details, :ip, datetime('now'))");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':rule_id', $ruleId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['rule_name' => $ruleName, 'external_port' => $externalPort]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Port forwarding rule created',
        'rule' => [
            'id' => $ruleId,
            'rule_name' => $ruleName,
            'external_port' => $externalPort,
            'internal_port' => $internalPort,
            'protocol' => $protocol
        ]
    ]);
    
} catch (Exception $e) {
    logError('Add port rule failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add rule']);
}
