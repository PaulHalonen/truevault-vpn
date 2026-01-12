<?php
/**
 * TrueVault VPN - Admin Login
 * POST /api/admin/login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'email' => 'required|email',
    'password' => 'required'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$email = strtolower(trim($input['email']));
$password = $input['password'];

try {
    $db = DatabaseManager::getInstance()->admin();
    
    // Get admin user
    $stmt = $db->prepare("
        SELECT au.*, ar.role_name, ar.role_slug 
        FROM admin_users au
        LEFT JOIN admin_roles ar ON au.role_id = ar.id
        WHERE au.email = ?
    ");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        Logger::auth('admin_login_failed', $email, false);
        Response::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!Encryption::verifyPassword($password, $admin['password_hash'])) {
        Logger::auth('admin_login_failed', $email, false);
        Response::error('Invalid email or password', 401);
    }
    
    // Check if active
    if (!$admin['is_active']) {
        Logger::auth('admin_login_inactive', $email, false);
        Response::error('Admin account is not active', 403);
    }
    
    // Generate admin token
    $token = JWTManager::generateToken($admin['id'], $admin['email'], true, [
        'role' => $admin['role_slug'],
        'admin_id' => $admin['id']
    ]);
    
    // Update last login
    $stmt = $db->prepare("UPDATE admin_users SET last_login = datetime('now') WHERE id = ?");
    $stmt->execute([$admin['id']]);
    
    // Log activity
    $stmt = $db->prepare("
        INSERT INTO admin_activity_log (admin_id, action, details, ip_address)
        VALUES (?, 'login', 'Admin logged in', ?)
    ");
    $stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'] ?? null]);
    
    Logger::auth('admin_login_success', $email, true);
    
    // Clean response
    unset($admin['password_hash']);
    
    Response::success([
        'admin' => $admin,
        'token' => $token,
        'expires_in' => 60 * 60 * 24 * 7
    ], 'Admin login successful');
    
} catch (Exception $e) {
    Logger::error('Admin login failed: ' . $e->getMessage());
    Response::serverError('Login failed');
}
