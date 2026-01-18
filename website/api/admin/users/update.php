<?php
/**
 * TrueVault VPN - Admin Update User API
 * 
 * PURPOSE: Update user details (admin only)
 * METHOD: POST
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * REQUEST BODY:
 * {
 *   "user_id": 5,
 *   "first_name": "John",
 *   "last_name": "Doe",
 *   "email": "john@example.com",
 *   "tier": "pro",
 *   "status": "active"
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "message": "User updated successfully",
 *   "user": {...}
 * }
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
require_once __DIR__ . '/../../../includes/Validator.php';

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
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    $required = ['user_id', 'first_name', 'last_name', 'email', 'tier', 'status'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Validate data
    $userId = (int)$input['user_id'];
    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $email = trim($input['email']);
    $tier = trim($input['tier']);
    $status = trim($input['status']);
    
    // Validate email
    if (!Validator::validateEmail($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        exit;
    }
    
    // Validate tier
    $validTiers = ['standard', 'pro', 'vip', 'admin'];
    if (!in_array($tier, $validTiers)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid tier. Must be: ' . implode(', ', $validTiers)
        ]);
        exit;
    }
    
    // Validate status
    $validStatuses = ['active', 'suspended', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)
        ]);
        exit;
    }
    
    // Get database connection
    $db = Database::getInstance();
    $usersConn = $db->getConnection('users');
    
    // Check if user exists
    $stmt = $usersConn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }
    
    // Check if email is already taken by another user
    $stmt = $usersConn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Email already in use by another user'
        ]);
        exit;
    }
    
    // Update user
    $stmt = $usersConn->prepare("
        UPDATE users
        SET first_name = ?,
            last_name = ?,
            email = ?,
            tier = ?,
            status = ?,
            updated_at = datetime('now')
        WHERE user_id = ?
    ");
    
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $tier,
        $status,
        $userId
    ]);
    
    // Get updated user
    $stmt = $usersConn->prepare("
        SELECT user_id, email, first_name, last_name, tier, status, created_at, updated_at
        FROM users
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => $updatedUser
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Update User Error: ' . $e->getMessage());
}
