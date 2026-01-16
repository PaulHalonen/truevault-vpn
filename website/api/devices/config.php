<?php
/**
 * TrueVault VPN - Device Config API
 * 
 * GET ?device_id=X - Get WireGuard config for device
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$userId = $payload['user_id'];
$deviceId = $_GET['device_id'] ?? null;

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Device ID required']);
    exit;
}

$devicesDb = Database::getInstance('devices');
$serversDb = Database::getInstance('servers');
$mainDb = Database::getInstance('main');

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

// Get server info
$server = $serversDb->queryOne(
    "SELECT * FROM servers WHERE id = ?",
    [$device['current_server_id']]
);

if (!$server) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server configuration error']);
    exit;
}

// Get DNS setting
$dns = $mainDb->queryValue("SELECT value FROM settings WHERE key = 'vpn_dns'") ?? '1.1.1.1, 8.8.8.8';

// Build config data
$config = [
    'device_id' => $device['device_id'],
    'device_name' => $device['device_name'],
    'client_ip' => $device['assigned_ip'],
    'dns' => $dns,
    'server_id' => $server['id'],
    'server_name' => $server['display_name'],
    'server_location' => $server['location'],
    'server_public_key' => $server['public_key'],
    'endpoint' => $server['ip_address'] . ':' . ($server['port'] ?? 51820),
    'allowed_ips' => '0.0.0.0/0, ::/0'
];

echo json_encode([
    'success' => true,
    'config' => $config
]);
