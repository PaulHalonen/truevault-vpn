<?php
/**
 * Port Forwarding - Create Rule API
 * 
 * METHOD: POST
 * ENDPOINT: /api/port-forwarding/create.php
 * REQUIRES: Bearer token
 * 
 * REQUEST: { "rule_name": "Camera", "external_port": 8080, "internal_port": 80, "target_ip": "192.168.1.50", "protocol": "tcp" }
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
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate input
    $validator = new Validator();
    $validator->string($data['rule_name'] ?? '', 'rule_name', 1, 50);
    $validator->port($data['external_port'] ?? 0, 'external_port');
    $validator->port($data['internal_port'] ?? 0, 'internal_port');
    $validator->ipAddress($data['target_ip'] ?? '', 'target_ip');
    $validator->protocol($data['protocol'] ?? 'tcp', 'protocol');
    
    if ($validator->hasErrors()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validator->getFirstError()]);
        exit;
    }
    
    $ruleName = $validator->get('rule_name');
    $externalPort = $validator->get('external_port');
    $internalPort = $validator->get('internal_port');
    $targetIP = $validator->get('target_ip');
    $protocol = $validator->get('protocol');
    $deviceId = $data['device_id'] ?? null;
    
    // Validate port ranges (non-privileged)
    if ($externalPort < 1024 || $externalPort > 65535) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'External port must be between 1024 and 65535']);
        exit;
    }
    
    $pfDb = Database::getInstance('port_forwards');
    
    // Check for port conflict
    $stmt = $pfDb->prepare("SELECT id FROM port_forwarding_rules WHERE user_id = :user_id AND external_port = :port AND enabled = 1");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':port', $externalPort, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if ($result->fetchArray()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Port {$externalPort} is already in use"]);
        exit;
    }
    
    // Create rule
    $stmt = $pfDb->prepare("
        INSERT INTO port_forwarding_rules (user_id, device_id, rule_name, external_port, internal_port, protocol, target_ip, enabled, created_at)
        VALUES (:user_id, :device_id, :name, :ext_port, :int_port, :protocol, :target_ip, 1, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_id', $deviceId, $deviceId ? SQLITE3_INTEGER : SQLITE3_NULL);
    $stmt->bindValue(':name', $ruleName, SQLITE3_TEXT);
    $stmt->bindValue(':ext_port', $externalPort, SQLITE3_INTEGER);
    $stmt->bindValue(':int_port', $internalPort, SQLITE3_INTEGER);
    $stmt->bindValue(':protocol', $protocol, SQLITE3_TEXT);
    $stmt->bindValue(':target_ip', $targetIP, SQLITE3_TEXT);
    $stmt->execute();
    
    $ruleId = $pfDb->lastInsertRowID();
    
    // Log event
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'port_forward_created', 'port_forward', :rule_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':rule_id', $ruleId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode(['rule_name' => $ruleName, 'port' => $externalPort]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Port forwarding rule created',
        'rule' => [
            'id' => $ruleId,
            'name' => $ruleName,
            'external_port' => $externalPort,
            'internal_port' => $internalPort,
            'target_ip' => $targetIP,
            'protocol' => $protocol
        ]
    ]);
    
} catch (Exception $e) {
    logError('Create port forwarding rule failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create rule']);
}
