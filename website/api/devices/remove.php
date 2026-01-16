<?php
/**
 * TrueVault VPN - Device Remove API
 * 
 * POST - Remove a device
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

$userId = $payload['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$deviceId = $input['device_id'] ?? null;

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Device ID required']);
    exit;
}

$devicesDb = Database::getInstance('devices');
$serversDb = Database::getInstance('servers');

// Get device (verify ownership)
$device = $devicesDb->queryOne(
    "SELECT * FROM devices WHERE device_id = ? AND user_id = ?",
    [$deviceId, $userId]
);

if (!$device) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Device not found']);
    exit;
}

// Release IP back to pool
$devicesDb->update('ip_pool', [
    'is_available' => 1,
    'device_id' => null,
    'assigned_at' => null
], 'ip_address = ?', [$device['assigned_ip']]);

// Decrease server client count
$serversDb->query(
    "UPDATE servers SET current_clients = MAX(0, current_clients - 1) WHERE id = ?",
    [$device['current_server_id']]
);

// Delete device configs if any
$devicesDb->query("DELETE FROM device_configs WHERE device_id = ?", [$deviceId]);

// Delete device
$devicesDb->query("DELETE FROM devices WHERE device_id = ?", [$deviceId]);

echo json_encode([
    'success' => true,
    'message' => 'Device removed successfully'
]);
