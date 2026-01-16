<?php
/**
 * TrueVault VPN - Login API
 * 
 * POST - Login user
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

$usersDb = Database::getInstance('users');
$mainDb = Database::getInstance('main');
$logsDb = Database::getInstance('logs');

// Find user
$user = $usersDb->queryOne(
    "SELECT id, email, password, status, plan FROM users WHERE email = ?",
    [$email]
);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    // Log failed attempt
    $logsDb->insert('activity_logs', [
        'user_id' => $user['id'],
        'action' => 'login_failed',
        'details' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

// Check if suspended
if ($user['status'] === 'suspended') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Account suspended. Contact support.']);
    exit;
}

// Check VIP status
$vip = $mainDb->queryOne("SELECT * FROM vip_users WHERE email = ?", [$email]);
$isVip = !empty($vip);

// Generate token
Auth::init(JWT_SECRET);
$token = Auth::generateToken([
    'user_id' => $user['id'],
    'email' => $user['email'],
    'plan' => $isVip ? 'vip' : $user['plan'],
    'is_vip' => $isVip
]);

// Update last login
$usersDb->update('users', [
    'last_login' => date('Y-m-d H:i:s'),
    'status' => $user['status'] === 'pending' ? 'active' : $user['status']
], 'id = ?', [$user['id']]);

// Log successful login
$logsDb->insert('activity_logs', [
    'user_id' => $user['id'],
    'action' => 'login_success',
    'details' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'is_vip' => $isVip]),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'created_at' => date('Y-m-d H:i:s')
]);

echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'plan' => $isVip ? 'vip' : $user['plan'],
        'is_vip' => $isVip
    ]
]);
