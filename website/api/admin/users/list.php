<?php
/**
 * TrueVault VPN - Admin User List API
 * 
 * PURPOSE: Returns all users for admin management
 * METHOD: GET
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "users": [
 *     {
 *       "user_id": 1,
 *       "email": "user@example.com",
 *       "first_name": "John",
 *       "last_name": "Doe",
 *       "tier": "standard",
 *       "status": "active",
 *       "created_at": "2026-01-15 10:30:00",
 *       "device_count": 2
 *     }
 *   ],
 *   "count": 10
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
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Get database connections
    $db = Database::getInstance();
    $usersConn = $db->getConnection('users');
    $devicesConn = $db->getConnection('devices');
    
    // Get all users
    $stmt = $usersConn->prepare("
        SELECT user_id, email, first_name, last_name, tier, status, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get device count for each user
    foreach ($users as &$user) {
        $stmt = $devicesConn->prepare("
            SELECT COUNT(*) as count
            FROM devices
            WHERE user_id = ?
        ");
        $stmt->execute([$user['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $user['device_count'] = (int)$result['count'];
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin User List Error: ' . $e->getMessage());
}
