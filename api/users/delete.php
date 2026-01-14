<?php
/**
 * TrueVault VPN - User Delete API
 * Delete user account (self-service)
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

$input = json_decode(file_get_contents('php://input'), true);

// Require password confirmation for deletion
if (empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password confirmation required']);
    exit;
}

try {
    $db = getDatabase('users');
    
    // Verify password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData || !password_verify($input['password'], $userData['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
        exit;
    }
    
    // Cancel any active subscriptions (would call PayPal API in production)
    
    // Delete user data from all databases
    $userId = $user['id'];
    
    // Delete devices
    $devicesDb = getDatabase('devices');
    $stmt = $devicesDb->prepare("DELETE FROM devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete cameras
    $camerasDb = getDatabase('cameras');
    $stmt = $camerasDb->prepare("DELETE FROM cameras WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete certificates
    $certsDb = getDatabase('certificates');
    $stmt = $certsDb->prepare("DELETE FROM certificates WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete scanner data
    $scannerDb = getDatabase('scanner');
    $stmt = $scannerDb->prepare("DELETE FROM scanner_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stmt = $scannerDb->prepare("DELETE FROM scanned_devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Finally delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Account deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
