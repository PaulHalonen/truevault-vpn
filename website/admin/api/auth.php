<?php
/**
 * TrueVault VPN - Admin Authentication API (FIXED)
 * 
 * POST ?action=login   - Admin login
 * POST ?action=logout  - Admin logout
 * GET  ?action=verify  - Verify admin token
 * 
 * @created January 2026
 * @fixed January 17, 2026 - Proper password hash verification
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

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        
        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email and password required']);
            exit;
        }
        
        try {
            $adminDb = Database::getInstance('admin');
            
            // Look up admin user by email
            $escaped = trim($adminDb->escape($email), "'");
            $adminUser = $adminDb->queryOne(
                "SELECT * FROM admin_users WHERE LOWER(email) = LOWER('{$escaped}') AND is_active = 1"
            );
            
            if (!$adminUser) {
                // Log failed attempt
                try {
                    $logsDb = Database::getInstance('logs');
                    $logsDb->insert('activity_logs', [
                        'user_id' => null,
                        'action' => 'admin_login_failed',
                        'details' => json_encode(['email' => $email, 'reason' => 'user_not_found']),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {}
                
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
                exit;
            }
            
            // Verify password hash
            if (!password_verify($password, $adminUser['password_hash'])) {
                // Log failed attempt
                try {
                    $logsDb = Database::getInstance('logs');
                    $logsDb->insert('activity_logs', [
                        'user_id' => $adminUser['id'],
                        'action' => 'admin_login_failed',
                        'details' => json_encode(['email' => $email, 'reason' => 'wrong_password']),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {}
                
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
                exit;
            }
            
            // Generate admin token
            $token = Auth::generateToken($adminUser['id'], $email, [
                'role' => $adminUser['role'],
                'type' => 'admin',
                'name' => $adminUser['name']
            ]);
            
            // Update last login
            $adminDb->update('admin_users', [
                'last_login' => date('Y-m-d H:i:s')
            ], "id = {$adminUser['id']}");
            
            // Log successful login
            try {
                $logsDb = Database::getInstance('logs');
                $logsDb->insert('activity_logs', [
                    'user_id' => $adminUser['id'],
                    'action' => 'admin_login',
                    'details' => json_encode(['email' => $email]),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {}
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'admin' => [
                    'id' => $adminUser['id'],
                    'email' => $email,
                    'name' => $adminUser['name'],
                    'role' => $adminUser['role']
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'verify':
        $token = null;
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader);
        }
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No token provided']);
            exit;
        }
        
        $result = Auth::verifyToken($token);
        
        if (!$result['valid']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Invalid token']);
            exit;
        }
        
        $payload = $result['payload'];
        
        if (($payload['type'] ?? '') !== 'admin') {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not an admin token']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'admin' => [
                'id' => $payload['user_id'],
                'email' => $payload['email'],
                'role' => $payload['role'] ?? 'admin',
                'name' => $payload['name'] ?? 'Admin'
            ]
        ]);
        break;
        
    case 'logout':
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action. Use: login, verify, logout']);
}
