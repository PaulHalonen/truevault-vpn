<?php
/**
 * TrueVault VPN - User Registration
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

Response::requireMethod('POST');

$input = Response::getJsonInput();

// Validate input
$required = ['email', 'password', 'first_name', 'last_name'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        Response::error("$field is required", 400);
    }
}

$email = strtolower(trim($input['email']));
$password = $input['password'];
$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Validate password length
if (strlen($password) < 8) {
    Response::error('Password must be at least 8 characters', 400);
}

// Check if email exists
$existing = Database::queryOne('users', "SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    Response::error('Email already registered', 409);
}

// Create user
$uuid = Auth::generateUUID();
$passwordHash = Auth::hashPassword($password);
$isVip = VIPManager::isVIP($email);

Database::execute('users',
    "INSERT INTO users (uuid, email, password_hash, first_name, last_name, status, is_vip, created_at)
     VALUES (?, ?, ?, ?, ?, 'active', ?, datetime('now'))",
    [$uuid, $email, $passwordHash, $firstName, $lastName, $isVip ? 1 : 0]
);

$userId = Database::lastInsertId('users');

// Generate token
$token = Auth::generateToken($userId, $email);

// If VIP, auto-create subscription
if ($isVip) {
    $vipPlan = VIPManager::getVIPPlan($email);
    Database::execute('billing',
        "INSERT INTO subscriptions (user_id, plan_type, status, start_date, end_date, created_at)
         VALUES (?, ?, 'active', datetime('now'), datetime('now', '+100 years'), datetime('now'))",
        [$userId, $vipPlan]
    );
}

Response::success([
    'token' => $token,
    'user' => [
        'id' => $userId,
        'uuid' => $uuid,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'is_vip' => $isVip
    ]
], 'Registration successful', 201);
