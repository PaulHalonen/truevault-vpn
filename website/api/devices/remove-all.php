<?php
/**
 * TrueVault VPN - Remove All Devices API
 * 
 * POST - Remove all devices for user
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

$devicesDb = Database::getInstance('devices');
$serversDb = Database::getInstance('servers');

// Get all user's devices
$devices = $devicesDb->queryAll("SELECT * FROM devices WHERE user_id = ?", [$userId]);

$count = count($devices);

foreach ($devices as $device) {
    // Release IP
    $devicesDb->update('ip_pool', [
        'is_available' => 1,
        'device_id' => null,
        'assigned_at' => null
    ], 'ip_address = ?', [$device['assigned_ip']]);
    
    // Decrease server count
    $serversDb->query(
        "UPDATE servers SET current_clients = MAX(0, current_clients - 1) WHERE id = ?",
        [$device['current_server_id']]
    );
    
    // Delete configs
    $devicesDb->query("DELETE FROM device_configs WHERE device_id = ?", [$device['device_id']]);
}

// Delete all devices
$devicesDb->query("DELETE FROM devices WHERE user_id = ?", [$userId]);

echo json_encode([
    'success' => true,
    'message' => "Removed {$count} device(s)"
]);
