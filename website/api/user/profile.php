<?php
/**
 * TrueVault VPN - User Profile API
 * 
 * GET  - Get user profile
 * PUT  - Update user profile
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
$devicesDb = Database::getInstance('devices');
$billingDb = Database::getInstance('billing');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get user profile
        $user = $usersDb->queryOne(
            "SELECT id, email, first_name, last_name, status, plan, created_at, updated_at FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Check VIP status
        $vip = $mainDb->queryOne(
            "SELECT max_devices, dedicated_server_id FROM vip_users WHERE email = ?",
            [$user['email']]
        );
        
        $user['is_vip'] = $vip ? true : false;
        
        // Determine max devices
        if ($vip) {
            $user['max_devices'] = $vip['max_devices'] ?? 999;
            $user['dedicated_server_id'] = $vip['dedicated_server_id'];
        } else {
            // Get from plan
            $planDevices = [
                'personal' => 3,
                'family' => 6,
                'dedicated' => 999
            ];
            $user['max_devices'] = $planDevices[$user['plan']] ?? 3;
        }
        
        // Get device count
        $user['device_count'] = $devicesDb->queryValue(
            "SELECT COUNT(*) FROM devices WHERE user_id = ?",
            [$userId]
        );
        
        // Get subscription info
        $subscription = $billingDb->queryOne(
            "SELECT plan, status, billing_interval, current_period_end FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        $user['subscription'] = $subscription;
        
        echo json_encode(['success' => true, 'user' => $user]);
        break;
        
    case 'PUT':
        // Update profile
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Allowed fields to update
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
