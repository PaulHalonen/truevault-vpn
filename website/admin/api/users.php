<?php
/**
 * TrueVault VPN - Admin Users API
 * 
 * GET              - List all users
 * GET ?id=X        - Get single user
 * POST             - Create user
 * PUT ?id=X        - Update user
 * DELETE ?id=X     - Delete user
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify admin token
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
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$usersDb = Database::getInstance('users');
$devicesDb = Database::getInstance('devices');
$billingDb = Database::getInstance('billing');
$mainDb = Database::getInstance('main');

$userId = $_GET['id'] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($userId) {
            // Get single user with details
            $user = $usersDb->queryOne(
                "SELECT id, email, first_name, last_name, account_type, plan, status, max_devices, 
                        created_at, last_login, email_verified, trial_ends_at 
                 FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'User not found']);
                exit;
            }
            
            // Get user's devices
            $devices = $devicesDb->queryAll(
                "SELECT id, device_name, device_type, assigned_ip, status, is_online, last_seen, created_at 
                 FROM devices WHERE user_id = ?",
                [$userId]
            );
            
            // Get user's subscription
            $subscription = $billingDb->queryOne(
                "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );
            
            // Get payment history
            $payments = $billingDb->queryAll(
                "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
                [$userId]
            );
            
            // Check if VIP
            $vip = $mainDb->queryOne(
                "SELECT * FROM vip_users WHERE email = ? AND is_active = 1",
                [$user['email']]
            );
            
            $user['is_vip'] = $vip ? true : false;
            $user['vip_details'] = $vip;
            $user['devices'] = $devices;
            $user['device_count'] = count($devices);
            $user['subscription'] = $subscription;
            $user['payments'] = $payments;
            
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            // List all users with pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            // Build query
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            if ($status) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $total = $usersDb->queryValue(
                "SELECT COUNT(*) FROM users {$whereClause}",
                $params
            );
            
            // Get users
            $users = $usersDb->queryAll(
                "SELECT id, email, first_name, last_name, account_type, plan, status, max_devices, 
                        created_at, last_login, email_verified 
                 FROM users {$whereClause} 
                 ORDER BY created_at DESC 
                 LIMIT {$limit} OFFSET {$offset}",
                $params
            );
            
            // Add device counts
            foreach ($users as &$user) {
                $user['device_count'] = $devicesDb->queryValue(
                    "SELECT COUNT(*) FROM devices WHERE user_id = ?",
                    [$user['id']]
                ) ?? 0;
            }
            
            echo json_encode([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // Create new user
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        $plan = $input['plan'] ?? 'personal';
        $status = $input['status'] ?? 'active';
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Valid email required']);
            exit;
        }
        
        if (!$password || strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
            exit;
        }
        
        // Check if email exists
        $existing = $usersDb->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already registered']);
            exit;
        }
        
        // Get max devices for plan
        $maxDevices = ['personal' => 3, 'family' => 6, 'dedicated' => 999][$plan] ?? 3;
        
        $newUserId = $usersDb->insert('users', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'plan' => $plan,
            'status' => $status,
            'max_devices' => $maxDevices,
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'user_id' => $newUserId,
            'message' => 'User created successfully'
        ]);
        break;
        
    case 'PUT':
        // Update user
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Fields that can be updated
        $allowedFields = ['first_name', 'last_name', 'plan', 'status', 'max_devices', 'account_type'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        // Handle password update
        if (!empty($input['password'])) {
            if (strlen($input['password']) < 8) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
                exit;
            }
            $updateData['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        $usersDb->update('users', $updateData, 'id = ?', [$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        break;
        
    case 'DELETE':
        // Delete user
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }
        
        // Get user email first
        $user = $usersDb->queryOne("SELECT email FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        
        // Don't allow deleting admin
        $adminEmail = $mainDb->queryValue(
            "SELECT setting_value FROM settings WHERE setting_key = 'admin_email'"
        );
        if ($user['email'] === $adminEmail) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Cannot delete admin user']);
            exit;
        }
        
        // Delete user's devices first
        $devicesDb->query("DELETE FROM devices WHERE user_id = ?", [$userId]);
        
        // Delete user's billing data
        $billingDb->query("DELETE FROM payments WHERE user_id = ?", [$userId]);
        $billingDb->query("DELETE FROM invoices WHERE user_id = ?", [$userId]);
        $billingDb->query("DELETE FROM subscriptions WHERE user_id = ?", [$userId]);
        
        // Delete user
        $usersDb->query("DELETE FROM users WHERE id = ?", [$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
