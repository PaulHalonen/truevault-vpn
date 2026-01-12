<?php
/**
 * TrueVault VPN - User Registration
 * POST /api/auth/register.php
 * 
 * Auto-detects VIP users by email and grants them free access
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/vip.php';

// Only allow POST
Response::requireMethod('POST');

// Get input
$input = Response::getJsonInput();

// Validate input
if (empty($input['email']) || empty($input['password'])) {
    Response::error('Email and password are required', 400);
}

if (empty($input['first_name'])) {
    Response::error('First name is required', 400);
}

$email = strtolower(trim($input['email']));
$password = $input['password'];
$firstName = trim($input['first_name']);
$lastName = trim($input['last_name'] ?? '');

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Validate password length
if (strlen($password) < 6) {
    Response::error('Password must be at least 6 characters', 400);
}

try {
    // Check if email exists
    $existing = Database::queryOne('users', "SELECT id FROM users WHERE email = ?", [$email]);
    
    if ($existing) {
        Response::error('Email already registered', 400);
    }
    
    // Check if this is a VIP email (secret - auto-detected)
    $vipDetails = VIPManager::getVIPDetails($email);
    $isVIP = $vipDetails !== null;
    
    // Set plan based on VIP status
    $planType = $isVIP ? $vipDetails['tier'] : 'personal';
    $status = 'active'; // VIPs are always active
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $result = Database::execute('users', 
        "INSERT INTO users (email, password, first_name, last_name, plan_type, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, datetime('now'))",
        [$email, $passwordHash, $firstName, $lastName, $planType, $status]
    );
    
    $userId = $result['lastInsertId'];
    
    // If VIP, activate and update VIP record with their name
    if ($isVIP) {
        VIPManager::activateVIP($email);
    }
    
    // Get user
    $user = Database::queryOne('users', "SELECT * FROM users WHERE id = ?", [$userId]);
    
    // Generate tokens
    $token = JWTManager::generateToken($userId, $email, false);
    $refreshToken = JWTManager::generateRefreshToken($userId);
    
    // Sanitize user data
    unset($user['password']);
    
    // Build subscription info
    $subscription = null;
    if ($isVIP) {
        $subscription = [
            'plan_type' => $vipDetails['tier'],
            'plan_name' => $vipDetails['tier_name'],
            'status' => 'active',
            'is_vip' => true,
            'vip_tier' => $vipDetails['tier'],
            'free_forever' => true,
            'bypass_payment' => true,
            'max_devices' => $vipDetails['max_devices'],
            'max_cameras' => $vipDetails['max_cameras'],
            'has_dedicated_server' => $vipDetails['has_dedicated_server'],
            'dedicated_server_ip' => $vipDetails['dedicated_server_ip']
        ];
        
        // Add VIP badge to user data
        $user['is_vip'] = true;
        $user['vip_tier'] = $vipDetails['tier'];
        $user['vip_badge'] = $vipDetails['tier'] === 'vip_dedicated' ? '👑 VIP' : '⭐ VIP';
    }
    
    Response::created([
        'user' => $user,
        'subscription' => $subscription,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 60 * 60 * 24 * 7
    ], $isVIP ? 'Welcome VIP! Your account has been activated with free lifetime access.' : 'Registration successful');
    
} catch (Exception $e) {
    Response::serverError('Registration failed: ' . $e->getMessage());
}
