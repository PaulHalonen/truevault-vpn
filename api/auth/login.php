<?php
/**
 * TrueVault VPN - User Login
 * POST /api/auth/login.php
 * 
 * VIP users are automatically detected and given special privileges
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

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
    // Check if VIP user (even before they have an account)
    $isVip = VIPManager::isVIP($email);
    $vipDetails = $isVip ? VIPManager::getVIPDetails($email) : null;
    
    // Get user
    $user = Auth::getUserByEmail($email);
    
    if (!$user) {
        Response::error('Invalid email or password', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        Response::error('Invalid email or password', 401);
    }
    
    // Check account status (VIPs bypass suspended status)
    if (!$isVip) {
        if ($user['status'] === 'suspended') {
            Response::error('Account is suspended. Please contact support.', 403);
        }
        
        if ($user['status'] === 'cancelled') {
            Response::error('Account has been cancelled.', 403);
        }
    }
    
    // Activate VIP if first login
    if ($isVip && empty($vipDetails['activated_at'])) {
        VIPManager::activateVIP($email, $user['id'], $user['first_name'], $user['last_name']);
    }
    
    // Generate tokens
    $token = JWTManager::generateToken($user['id'], $user['email'], false);
    $refreshToken = JWTManager::generateRefreshToken($user['id']);
    
    // Update last login
    Auth::updateLastLogin($user['id']);
    
    // Get subscription info (VIPs get special subscription)
    $subscription = null;
    if ($isVip) {
        $vipLimits = VIPManager::getVIPLimits($email);
        $subscription = [
            'plan_type' => $vipLimits['tier'],
            'status' => 'active',
            'max_devices' => $vipLimits['max_devices'],
            'max_cameras' => $vipLimits['max_cameras'],
            'is_vip' => true,
            'vip_badge' => $vipLimits['badge'],
            'bypass_payment' => true,
            'dedicated_server_id' => $vipLimits['dedicated_server_id'],
            'dedicated_server_ip' => $vipLimits['dedicated_server_ip']
        ];
    } else {
        $subscription = Auth::getUserSubscription($user['id']);
        if ($subscription) {
            $planLimits = PlanLimits::getPlan($subscription['plan_type'] ?? 'basic');
            $subscription['max_devices'] = $planLimits['max_devices'];
            $subscription['max_cameras'] = $planLimits['max_cameras'];
        }
    }
    
    // Sanitize user data
    $userData = Auth::sanitizeUser($user);
    
    // Add VIP info to user data
    if ($isVip) {
        $userData['is_vip'] = true;
        $userData['vip_tier'] = $vipDetails['tier'];
        $userData['vip_badge'] = $vipDetails['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP';
    }
    
    // Get available servers for this user
    $availableServers = ServerRules::getAvailableServers($email);
    
    Response::success([
        'user' => $userData,
        'subscription' => $subscription,
        'servers' => $availableServers,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 60 * 60 * 24 * 7 // 7 days
    ], 'Login successful');
    
} catch (Exception $e) {
    Response::serverError('Login failed: ' . $e->getMessage());
}
