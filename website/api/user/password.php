<?php
/**
 * TrueVault VPN - Change Password API
 * 
 * POST - Change user password
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (!$currentPassword || !$newPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current and new password required']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
    exit;
}

$userId = $payload['user_id'];
$usersDb = Database::getInstance('users');

// Get current user
$user = $usersDb->queryOne("SELECT password FROM users WHERE id = ?", [$userId]);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Verify current password
if (!password_verify($currentPassword, $user['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// Update password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$usersDb->update('users', [
    'password' => $hashedPassword,
    'updated_at' => date('Y-m-d H:i:s')
], 'id = ?', [$userId]);

// Log activity
$logsDb = Database::getInstance('logs');
$logsDb->insert('activity_logs', [
    'user_id' => $userId,
    'action' => 'password_changed',
    'details' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'created_at' => date('Y-m-d H:i:s')
]);

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
