<?php
/**
 * TrueVault VPN - User Update API
 * Update user profile information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
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

$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDatabase('users');
    
    $updates = [];
    $params = [];
    
    // Allowed fields to update
    if (isset($input['first_name'])) {
        $updates[] = "first_name = ?";
        $params[] = $input['first_name'];
    }
    if (isset($input['last_name'])) {
        $updates[] = "last_name = ?";
        $params[] = $input['last_name'];
    }
    
    // Settings/preferences
    if (isset($input['auto_connect'])) {
        $updates[] = "auto_connect = ?";
        $params[] = $input['auto_connect'] ? 1 : 0;
    }
    if (isset($input['kill_switch'])) {
        $updates[] = "kill_switch = ?";
        $params[] = $input['kill_switch'] ? 1 : 0;
    }
    if (isset($input['email_notifications'])) {
        $updates[] = "email_notifications = ?";
        $params[] = $input['email_notifications'] ? 1 : 0;
    }
    if (isset($input['two_factor'])) {
        $updates[] = "two_factor = ?";
        $params[] = $input['two_factor'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }
    
    $params[] = $user['id'];
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Fetch updated user data
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, plan, is_vip, auto_connect, kill_switch, email_notifications, two_factor FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => $updatedUser,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
