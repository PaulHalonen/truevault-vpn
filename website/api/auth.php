<?php
/**
 * TrueVault VPN - Authentication API
 * 
 * Endpoints:
 *   POST ?action=register     - Create new account
 *   POST ?action=login        - Login and get token
 *   POST ?action=logout       - Invalidate token
 *   POST ?action=forgot       - Request password reset
 *   POST ?action=reset        - Reset password with token
 *   GET  ?action=me           - Get current user info
 *   GET  ?action=verify       - Verify token is valid
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Required for config.php security check
define('TRUEVAULT_INIT', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once dirname(__DIR__) . '/configs/config.php';
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/Auth.php';

// Initialize Auth with secret
Auth::init(JWT_SECRET);

/**
 * Send JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Get JSON input
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Get Bearer token from Authorization header
 */
function getBearerToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Require authentication
 */
function requireAuth() {
    $token = getBearerToken();
    
    if (!$token) {
        jsonResponse(['success' => false, 'error' => 'No token provided'], 401);
    }
    
    $result = Auth::verifyToken($token);
    
    if (!$result['valid']) {
        jsonResponse(['success' => false, 'error' => $result['error']], 401);
    }
    
    // Verify session is still active
    if (!Auth::verifySession($token)) {
        jsonResponse(['success' => false, 'error' => 'Session expired or invalidated'], 401);
    }
    
    return $result['payload'];
}

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Route actions
switch ($action) {
    
    // ==========================================
    // REGISTER
    // ==========================================
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $input = getJsonInput();
        
        // Validate required fields
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $firstName = trim($input['first_name'] ?? '');
        $lastName = trim($input['last_name'] ?? '');
        
        if (empty($email) || empty($password)) {
            jsonResponse(['success' => false, 'error' => 'Email and password are required'], 400);
        }
        
        // Register user
        $result = Auth::register($email, $password, $firstName, $lastName);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'user' => [
                    'id' => $result['user_id'],
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'account_type' => $result['account_type'],
                    'status' => $result['status']
                ],
                'token' => $result['token'],
                // VIP status is SECRET - don't expose directly
                // But the account_type will be 'vip' which client can use
            ], 201);
        } else {
            jsonResponse(['success' => false, 'error' => $result['message']], 400);
        }
        break;
    
    // ==========================================
    // LOGIN
    // ==========================================
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $input = getJsonInput();
        
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            jsonResponse(['success' => false, 'error' => 'Email and password are required'], 400);
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $result = Auth::login($email, $password, $ipAddress, $userAgent);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'user' => [
                    'id' => $result['user_id'],
                    'email' => $result['email'],
                    'first_name' => $result['first_name'],
                    'last_name' => $result['last_name'],
                    'account_type' => $result['account_type'],
                    'plan' => $result['plan'],
                    'status' => $result['status'],
                    'max_devices' => $result['max_devices']
                ],
                'token' => $result['token']
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => $result['message']], 401);
        }
        break;
    
    // ==========================================
    // LOGOUT
    // ==========================================
    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $token = getBearerToken();
        
        if (!$token) {
            jsonResponse(['success' => false, 'error' => 'No token provided'], 400);
        }
        
        $result = Auth::logout($token);
        jsonResponse($result);
        break;
    
    // ==========================================
    // FORGOT PASSWORD
    // ==========================================
    case 'forgot':
    case 'forgot-password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $input = getJsonInput();
        $email = trim($input['email'] ?? '');
        
        if (empty($email)) {
            jsonResponse(['success' => false, 'error' => 'Email is required'], 400);
        }
        
        $result = Auth::requestPasswordReset($email);
        
        // Always return success to not reveal if email exists
        jsonResponse([
            'success' => true,
            'message' => 'If the email exists, a reset link has been sent.'
        ]);
        break;
    
    // ==========================================
    // RESET PASSWORD
    // ==========================================
    case 'reset':
    case 'reset-password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $input = getJsonInput();
        $token = $input['token'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($token) || empty($password)) {
            jsonResponse(['success' => false, 'error' => 'Token and password are required'], 400);
        }
        
        $result = Auth::resetPassword($token, $password);
        
        if ($result['success']) {
            jsonResponse($result);
        } else {
            jsonResponse($result, 400);
        }
        break;
    
    // ==========================================
    // GET CURRENT USER (ME)
    // ==========================================
    case 'me':
    case 'profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $tokenData = requireAuth();
        
        // Get fresh user data
        $user = Auth::getUserById($tokenData['user_id']);
        
        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'User not found'], 404);
        }
        
        // Don't expose sensitive fields
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'account_type' => $user['account_type'],
                'plan' => $user['plan'],
                'status' => $user['status'],
                'max_devices' => $user['max_devices'],
                'email_verified' => (bool)$user['email_verified'],
                'trial_ends_at' => $user['trial_ends_at'],
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login']
            ]
        ]);
        break;
    
    // ==========================================
    // UPDATE PROFILE
    // ==========================================
    case 'update':
    case 'update-profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $tokenData = requireAuth();
        $input = getJsonInput();
        
        $result = Auth::updateProfile($tokenData['user_id'], $input);
        
        if ($result['success']) {
            // Get updated user
            $user = Auth::getUserById($tokenData['user_id']);
            jsonResponse([
                'success' => true,
                'message' => $result['message'],
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]
            ]);
        } else {
            jsonResponse($result, 400);
        }
        break;
    
    // ==========================================
    // VERIFY TOKEN
    // ==========================================
    case 'verify':
    case 'verify-token':
        $token = getBearerToken();
        
        if (!$token) {
            jsonResponse(['success' => false, 'valid' => false, 'error' => 'No token provided'], 400);
        }
        
        $result = Auth::verifyToken($token);
        
        if ($result['valid'] && Auth::verifySession($token)) {
            jsonResponse([
                'success' => true,
                'valid' => true,
                'user_id' => $result['payload']['user_id'],
                'email' => $result['payload']['email'],
                'expires' => date('Y-m-d H:i:s', $result['payload']['exp'])
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'valid' => false,
                'error' => $result['error'] ?? 'Session invalid'
            ], 401);
        }
        break;
    
    // ==========================================
    // CHANGE PASSWORD (while logged in)
    // ==========================================
    case 'change-password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        
        $tokenData = requireAuth();
        $input = getJsonInput();
        
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(['success' => false, 'error' => 'Current and new passwords are required'], 400);
        }
        
        if (strlen($newPassword) < 8) {
            jsonResponse(['success' => false, 'error' => 'New password must be at least 8 characters'], 400);
        }
        
        // Get user and verify current password
        $user = Auth::getUserById($tokenData['user_id']);
        
        if (!Auth::verifyPassword($currentPassword, $user['password_hash'])) {
            jsonResponse(['success' => false, 'error' => 'Current password is incorrect'], 400);
        }
        
        // Update password
        try {
            $db = Database::getInstance('users');
            $newHash = Auth::hashPassword($newPassword);
            $db->update('users', [
                'password_hash' => $newHash,
                'updated_at' => date('Y-m-d H:i:s')
            ], "id = {$user['id']}");
            
            jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Failed to change password'], 500);
        }
        break;
    
    // ==========================================
    // DEFAULT - UNKNOWN ACTION
    // ==========================================
    default:
        jsonResponse([
            'success' => false,
            'error' => 'Unknown action',
            'available_actions' => [
                'register' => 'POST - Create new account',
                'login' => 'POST - Login and get token',
                'logout' => 'POST - Invalidate token',
                'forgot' => 'POST - Request password reset',
                'reset' => 'POST - Reset password with token',
                'me' => 'GET - Get current user info',
                'verify' => 'GET - Verify token is valid',
                'update' => 'POST - Update profile',
                'change-password' => 'POST - Change password'
            ]
        ], 400);
}
