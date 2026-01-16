<?php
/**
 * TrueVault VPN - Delete Port Forwarding Rule API
 * POST /api/port-forwarding/delete-rule.php
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

if (empty($data['rule_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing rule_id']);
    exit;
}

$ruleId = (int)$data['rule_id'];

// Connect to database
$db = new Database();

// Verify rule belongs to user
$stmt = $db->portForwards->prepare("
    SELECT id FROM port_forwarding_rules
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$ruleId, $userId]);
$rule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rule) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Rule not found']);
    exit;
}

// Delete the rule
try {
    $stmt = $db->portForwards->prepare("
        DELETE FROM port_forwarding_rules
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$ruleId, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Port forwarding rule deleted successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
