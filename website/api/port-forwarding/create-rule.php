<?php
/**
 * TrueVault VPN - Create Port Forwarding Rule API
 * POST /api/port-forwarding/create-rule.php
 */

header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Auth.php';

// Check authentication
$auth = new Auth();
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $auth->getUserId();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Validate required fields
$required = ['device_name', 'internal_ip', 'external_port', 'internal_port', 'protocol'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

// Validate ports
$externalPort = (int)$data['external_port'];
$internalPort = (int)$data['internal_port'];

if ($externalPort < 1024 || $externalPort > 65535) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'External port must be between 1024-65535']);
    exit;
}

if ($internalPort < 1 || $internalPort > 65535) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Internal port must be between 1-65535']);
    exit;
}

// Validate protocol
$validProtocols = ['tcp', 'udp', 'both'];
if (!in_array($data['protocol'], $validProtocols)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid protocol']);
    exit;
}

// Connect to database
$db = new Database();

// Check for port conflicts
$stmt = $db->portForwards->prepare("
    SELECT COUNT(*) as count
    FROM port_forwarding_rules
    WHERE user_id = ? AND external_port = ? AND status = 'active'
");
$stmt->execute([$userId, $externalPort]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'External port already in use']);
    exit;
}

// Insert port forwarding rule
try {
    $stmt = $db->portForwards->prepare("
        INSERT INTO port_forwarding_rules 
        (user_id, device_name, internal_ip, external_port, internal_port, protocol, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', datetime('now'))
    ");
    
    $stmt->execute([
        $userId,
        $data['device_name'],
        $data['internal_ip'],
        $externalPort,
        $internalPort,
        $data['protocol']
    ]);
    
    $ruleId = $db->portForwards->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Port forwarding rule created successfully',
        'rule_id' => $ruleId
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
