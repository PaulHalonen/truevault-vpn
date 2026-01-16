<?php
/**
 * TrueVault VPN - Admin Authentication API
 * 
 * POST ?action=login   - Admin login
 * POST ?action=logout  - Admin logout
 * GET  ?action=verify  - Verify admin token
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Initialize Auth
Auth::init(JWT_SECRET);

$action = $_GET['action'] ?? '';

// Admin credentials (stored in main.db settings)
$mainDb = Database::getInstance('main');

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email and password required']);
            exit;
        }
        
        // Check if admin email
        $adminEmail = $mainDb->queryValue(
            "SELECT setting_value FROM settings WHERE setting_key = 'admin_email'"
        );
        
        // For now, use a simple admin check
        // In production, you'd have an admins table with hashed passwords
        $vipUser = $mainDb->queryOne(
            "SELECT * FROM vip_users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        // Check if user is admin (paulhalonen@gmail.com)
        if (!$vipUser || $email !== $adminEmail) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        
        // Simple password check (in production, use proper hashing)
        // Default admin password is: TrueVault2026!
        $adminPasswordHash = '$2y$10$YourHashedPasswordHere'; // placeholder
        
        // For initial setup, accept a default password
        $defaultPassword = 'TrueVault2026!';
        if ($password !== $defaultPassword) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
            exit;
        }
        
        // Generate admin token
        $token = Auth::generateToken([
            'user_id' => $vipUser['id'],
            'email' => $email,
            'role' => 'admin',
            'type' => 'admin'
        ], 86400 * 7); // 7 days
        
        // Log admin login
        $logsDb = Database::getInstance('logs');
        $logsDb->insert('activity_logs', [
            'user_id' => $vipUser['id'],
            'action' => 'admin_login',
            'details' => json_encode(['email' => $email]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'admin' => [
                'id' => $vipUser['id'],
                'email' => $email,
                'role' => 'admin'
            ]
        ]);
        break;
        
    case 'verify':
        $token = null;
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No token provided']);
            exit;
        }
        
        $payload = Auth::verifyToken($token);
        if (!$payload || ($payload['type'] ?? '') !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid admin token']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'admin' => [
                'id' => $payload['user_id'],
                'email' => $payload['email'],
                'role' => $payload['role']
            ]
        ]);
        break;
        
    case 'logout':
        // Just return success - client should clear token
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
