<?php
/**
 * TrueVault VPN - User Login
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
if (empty($input['email']) || empty($input['password'])) {
    Response::error('Email and password are required', 400);
}

$email = strtolower(trim($input['email']));
$password = $input['password'];

try {
    // Get user
    $user = Auth::getUserByEmail($email);
    
    if (!$user) {
        Response::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        Response::error('Invalid email or password', 401);
    }
    
    // Check account status
    if ($user['status'] === 'suspended') {
        Response::error('Account is suspended. Please contact support.', 403);
    }
    
    if ($user['status'] === 'cancelled') {
        Response::error('Account has been cancelled.', 403);
    }
    
    // Generate tokens
    $token = JWTManager::generateToken($user['id'], $user['email'], false);
    $refreshToken = JWTManager::generateRefreshToken($user['id']);
    
    // Update last login
    Auth::updateLastLogin($user['id']);
    
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
    Response::serverError('Login failed: ' . $e->getMessage());
}
