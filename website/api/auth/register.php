<?php
/**
 * TrueVault VPN - Register API
 * 
 * POST - Register new user
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit;
}

$email = strtolower(trim($input['email']));
$password = $input['password'];

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Validate password
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
    exit;
}

$usersDb = Database::getInstance('users');
$mainDb = Database::getInstance('main');
$logsDb = Database::getInstance('logs');

// Check if email exists
$existing = $usersDb->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Email already registered']);
    exit;
}

// Check if VIP
$vip = $mainDb->queryOne("SELECT * FROM vip_users WHERE email = ?", [$email]);
$isVip = !empty($vip);

// Determine initial plan
$plan = $isVip ? 'vip' : 'trial';
$status = $isVip ? 'active' : 'trial';

// Create user
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

$usersDb->insert('users', [
    'email' => $email,
    'password' => $passwordHash,
    'plan' => $plan,
    'status' => $status,
    'created_at' => $now,
    'updated_at' => $now,
    'last_login' => $now
]);

$userId = $usersDb->lastInsertId();

// Generate token
Auth::init(JWT_SECRET);
$token = Auth::generateToken([
    'user_id' => $userId,
    'email' => $email,
    'plan' => $plan,
    'is_vip' => $isVip
]);

// Log registration
$logsDb->insert('activity_logs', [
    'user_id' => $userId,
    'action' => 'user_registered',
    'details' => json_encode([
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'is_vip' => $isVip,
        'plan' => $plan
    ]),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'created_at' => $now
]);

echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => $userId,
        'email' => $email,
        'plan' => $plan,
        'is_vip' => $isVip
    ],
    'message' => $isVip ? 'VIP account activated!' : 'Account created successfully'
]);
