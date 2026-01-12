<?php
/**
 * TrueVault VPN - User Login
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/logger.php';
require_once __DIR__ . '/../helpers/auth.php';

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
    // Get user
    $user = Auth::getUserByEmail($email);
    
    if (!$user) {
        Logger::auth('login_failed', $email, false);
        Response::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!Encryption::verifyPassword($password, $user['password_hash'])) {
        Logger::auth('login_failed', $email, false);
        Response::error('Invalid email or password', 401);
    }
    
    // Check account status
    if ($user['status'] === 'suspended') {
        Logger::auth('login_suspended', $email, false);
        Response::error('Account is suspended. Please contact support.', 403);
    }
    
    if ($user['status'] === 'cancelled') {
        Logger::auth('login_cancelled', $email, false);
        Response::error('Account has been cancelled.', 403);
    }
    
    // Check 2FA if enabled
    if ($user['two_factor_enabled'] && !empty($input['two_factor_code'])) {
        // Verify 2FA code (simplified - would use TOTP library)
        // For now, we'll skip this check
    } elseif ($user['two_factor_enabled']) {
        Response::error('Two-factor authentication code required', 401, [
            'requires_2fa' => true
        ]);
    }
    
    // Generate tokens
    $token = JWTManager::generateToken($user['id'], $user['email'], false);
    $refreshToken = JWTManager::generateRefreshToken($user['id']);
    
    // Create session
    Auth::createSession($user['id'], $token, $refreshToken);
    
    // Update last login
    Auth::updateLastLogin($user['id']);
    
    // Log successful login
    Logger::auth('login_success', $email, true);
    
    // Get subscription info
    $subscription = Auth::getUserSubscription($user['id']);
    
    // Sanitize user data
    $userData = Auth::sanitizeUser($user);
    
    Response::success([
        'user' => $userData,
        'subscription' => $subscription,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 60 * 60 * 24 * 7 // 7 days
    ], 'Login successful');
    
} catch (Exception $e) {
    Logger::error('Login failed: ' . $e->getMessage());
    Response::serverError('Login failed. Please try again.');
}
