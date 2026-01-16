<?php
/**
 * TrueVault VPN - Delete Account API
 * 
 * DELETE - Delete user account and all associated data
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = $payload['user_id'];

$usersDb = Database::getInstance('users');
$devicesDb = Database::getInstance('devices');
$billingDb = Database::getInstance('billing');
$supportDb = Database::getInstance('support');
$logsDb = Database::getInstance('logs');

// Get user info for logging
$user = $usersDb->queryOne("SELECT email FROM users WHERE id = ?", [$userId]);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

try {
    // Get all device IPs to release
    $devices = $devicesDb->queryAll(
        "SELECT assigned_ip FROM devices WHERE user_id = ?",
        [$userId]
    );
    
    // Release IPs back to pool
    foreach ($devices as $device) {
        $devicesDb->update('ip_pool', 
            ['is_available' => 1, 'device_id' => null, 'assigned_at' => null],
            'ip_address = ?',
            [$device['assigned_ip']]
        );
    }
    
    // Delete device configs
    $devicesDb->query(
        "DELETE FROM device_configs WHERE device_id IN (SELECT device_id FROM devices WHERE user_id = ?)",
        [$userId]
    );
    
    // Delete devices
    $devicesDb->query("DELETE FROM devices WHERE user_id = ?", [$userId]);
    
    // Delete billing records
    $billingDb->query("DELETE FROM subscriptions WHERE user_id = ?", [$userId]);
    $billingDb->query("DELETE FROM invoices WHERE user_id = ?", [$userId]);
    $billingDb->query("DELETE FROM payments WHERE user_id = ?", [$userId]);
    $billingDb->query("DELETE FROM orders WHERE user_id = ?", [$userId]);
    
    // Delete support tickets
    $supportDb->query("DELETE FROM tickets WHERE user_id = ?", [$userId]);
    
    // Log the deletion
    $logsDb->insert('activity_logs', [
        'user_id' => null,
        'action' => 'account_deleted',
        'details' => json_encode([
            'deleted_user_id' => $userId,
            'email' => $user['email'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Finally delete the user
    $usersDb->query("DELETE FROM users WHERE id = ?", [$userId]);
    
    echo json_encode(['success' => true, 'message' => 'Account deleted']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete account']);
}
