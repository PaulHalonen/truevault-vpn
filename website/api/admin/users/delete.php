<?php
/**
 * TrueVault VPN - Admin Delete User API
 * 
 * PURPOSE: Delete user account (admin only)
 * METHOD: POST
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * REQUEST BODY:
 * {
 *   "user_id": 5
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "User deleted successfully"
 * }
 * 
 * NOTE: Also deletes all user devices
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../../configs/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    // Check admin access
    if (!in_array($userTier, ['admin', 'vip'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin or VIP access required.'
        ]);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: user_id'
        ]);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    
    // Prevent self-deletion
    if ($userId === $user['user_id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete your own account'
        ]);
        exit;
    }
    
    // Get database connections
    $db = Database::getInstance();
    $usersConn = $db->getConnection('users');
    $devicesConn = $db->getConnection('devices');
    
    // Check if user exists
    $stmt = $usersConn->prepare("SELECT user_id, email FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }
    
    // Delete user's devices first
    $stmt = $devicesConn->prepare("DELETE FROM devices WHERE user_id = ?");
    $stmt->execute([$userId]);
    $deletedDevices = $stmt->rowCount();
    
    // Delete user
    $stmt = $usersConn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully',
        'deleted_devices' => $deletedDevices
    ]);
    
    // Log deletion
    error_log("Admin deleted user: {$targetUser['email']} (ID: $userId) by {$user['email']}");
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Delete User Error: ' . $e->getMessage());
}
