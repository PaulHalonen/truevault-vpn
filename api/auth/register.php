<?php
/**
 * TrueVault VPN - User Registration
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

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
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $result = Database::execute('users', 
        "INSERT INTO users (email, password, first_name, last_name, plan_type, status, created_at) 
         VALUES (?, ?, ?, ?, 'personal', 'active', datetime('now'))",
        [$email, $passwordHash, $firstName, $lastName]
    );
    
    $userId = $result['lastInsertId'];
    
    // Get user
    $user = Database::queryOne('users', "SELECT * FROM users WHERE id = ?", [$userId]);
    
    // Generate tokens
    $token = JWTManager::generateToken($userId, $email, false);
    $refreshToken = JWTManager::generateRefreshToken($userId);
    
    // Sanitize user data
    unset($user['password']);
    
    Response::created([
        'user' => $user,
        'token' => $token,
        'refresh_token' => $refreshToken,
        'expires_in' => 60 * 60 * 24 * 7
    ], 'Registration successful');
    
} catch (Exception $e) {
    Response::serverError('Registration failed: ' . $e->getMessage());
}
