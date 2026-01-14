<?php
/**
 * TrueVault VPN - Remove Device API
 * Remove a registered device
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verify user token
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$deviceId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = $input['id'] ?? $deviceId;
}

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Device ID is required']);
    exit;
}

try {
    $db = getDatabase('devices');
    
    // Verify device belongs to user
    $stmt = $db->prepare("SELECT id FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$deviceId, $user['id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }
    
    // Remove device
    $stmt = $db->prepare("DELETE FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$deviceId, $user['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Device removed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
