<?php
/**
 * TrueVault VPN - User Profile API
 * 
 * GET  - Get user profile
 * PUT  - Update profile (first_name, last_name)
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$userId = $payload['user_id'];
$usersDb = Database::getInstance('users');
$mainDb = Database::getInstance('main');
$billingDb = Database::getInstance('billing');
$devicesDb = Database::getInstance('devices');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get user profile
        $user = $usersDb->queryOne(
            "SELECT id, email, first_name, last_name, plan, status, created_at, last_login 
             FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Check VIP status
        $vip = $mainDb->queryOne(
            "SELECT max_devices FROM vip_users WHERE email = ?",
            [$user['email']]
        );
        
        $isVip = !empty($vip);
        
        // Get device count
        $deviceCount = $devicesDb->queryValue(
            "SELECT COUNT(*) FROM devices WHERE user_id = ?",
            [$userId]
        );
        
        // Determine max devices based on plan or VIP
        if ($isVip) {
            $maxDevices = $vip['max_devices'] ?? 999;
        } else {
            $planDevices = [
                'personal' => 3,
                'family' => 6,
                'dedicated' => 999,
                'trial' => 1
            ];
            $maxDevices = $planDevices[$user['plan']] ?? 3;
        }
        
        // Get active subscription
        $subscription = $billingDb->queryOne(
            "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'plan' => $isVip ? 'vip' : $user['plan'],
                'status' => $user['status'],
                'is_vip' => $isVip,
                'device_count' => (int)$deviceCount,
                'max_devices' => $maxDevices,
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login'],
                'subscription' => $subscription
            ]
        ]);
        break;
        
    case 'PUT':
        // Update profile
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        if (isset($input['first_name'])) {
            $updateData['first_name'] = trim($input['first_name']);
        }
        if (isset($input['last_name'])) {
            $updateData['last_name'] = trim($input['last_name']);
        }
        
        $usersDb->update('users', $updateData, 'id = ?', [$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
