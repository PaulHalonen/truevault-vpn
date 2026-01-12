<?php
/**
 * TrueVault VPN - Admin Login
 * POST /api/auth/admin-login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/logger.php';

Response::requireMethod('POST');

$input = Response::getJsonInput();

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
    
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        Logger::security('Admin login failed - not found', ['email' => $email]);
        Response::error('Invalid credentials', 401);
    }
    
    if (!Encryption::verifyPassword($password, $admin['password_hash'])) {
        Logger::security('Admin login failed - invalid password', ['email' => $email]);
        Response::error('Invalid credentials', 401);
    }
    
    if (!$admin['is_active']) {
        Response::error('Admin account is disabled', 403);
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE admin_users SET last_login = datetime('now') WHERE id = ?");
    $stmt->execute([$admin['id']]);
    
    // Generate admin token
    $token = JWTManager::generateToken($admin['id'], $admin['email'], true, [
        'role_id' => $admin['role_id']
    ]);
    
    Logger::info("Admin login: $email", ['admin_id' => $admin['id']]);
    
    Response::success([
        'admin' => [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'first_name' => $admin['first_name'],
            'last_name' => $admin['last_name'],
            'role_id' => $admin['role_id']
        ],
        'token' => $token,
        'expires_in' => 60 * 60 * 24 * 7
    ], 'Admin login successful');
    
} catch (Exception $e) {
    Logger::error("Admin login error: " . $e->getMessage());
    Response::serverError('Login failed');
}
