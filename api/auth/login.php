<?php
/**
 * TrueVault VPN - User Login
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['email']) || empty($input['password'])) {
    Response::error('Email and password required', 400);
}

$email = strtolower(trim($input['email']));
$password = $input['password'];

// Find user
$user = Database::queryOne('users',
    "SELECT * FROM users WHERE email = ?",
    [$email]
);

if (!$user) {
    Response::error('Invalid credentials', 401);
}

// Verify password
if (!Auth::verifyPassword($password, $user['password_hash'])) {
    Response::error('Invalid credentials', 401);
}

// Check status
if ($user['status'] !== 'active') {
    Response::error('Account is ' . $user['status'], 403);
}

// Update last login
Database::execute('users',
    "UPDATE users SET last_login = datetime('now') WHERE id = ?",
    [$user['id']]
);

// Generate token
$token = Auth::generateToken($user['id'], $user['email']);

// Get subscription
$subscription = Database::queryOne('billing',
    "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
    [$user['id']]
);

Response::success([
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'uuid' => $user['uuid'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'is_vip' => VIPManager::isVIP($user['email']),
        'vip_type' => VIPManager::getVIPType($user['email'])
    ],
    'subscription' => $subscription
], 'Login successful');
