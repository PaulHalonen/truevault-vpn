<?php
/**
 * TrueVault VPN - Device Registration API
 * 
 * POST - Register a new device
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

if (empty($input['device_name']) || empty($input['public_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Device name and public key required']);
    exit;
}

$usersDb = Database::getInstance('users');
$devicesDb = Database::getInstance('devices');
$mainDb = Database::getInstance('main');
$serversDb = Database::getInstance('servers');

// Get user and check device limit
$user = $usersDb->queryOne("SELECT email, plan FROM users WHERE id = ?", [$userId]);

// Check VIP
$vip = $mainDb->queryOne("SELECT max_devices FROM vip_users WHERE email = ?", [$user['email']]);
$isVip = !empty($vip);

// Determine max devices
if ($isVip) {
    $maxDevices = $vip['max_devices'] ?? 999;
} else {
    $planDevices = ['personal' => 3, 'family' => 6, 'dedicated' => 999, 'trial' => 1];
    $maxDevices = $planDevices[$user['plan']] ?? 3;
}

// Count current devices
$deviceCount = $devicesDb->queryValue("SELECT COUNT(*) FROM devices WHERE user_id = ?", [$userId]);

if ($deviceCount >= $maxDevices) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => "Device limit reached ({$maxDevices}). Upgrade your plan for more devices."
    ]);
    exit;
}

// Get an available IP from pool
$ip = $devicesDb->queryOne(
    "SELECT ip_address FROM ip_pool WHERE is_available = 1 ORDER BY RANDOM() LIMIT 1"
);

if (!$ip) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'No IPs available. Contact support.']);
    exit;
}

// Get default server (or VIP dedicated server)
if ($isVip) {
    $server = $serversDb->queryOne("SELECT id FROM servers WHERE is_vip_only = 1 LIMIT 1");
} else {
    $server = $serversDb->queryOne("SELECT id FROM servers WHERE is_vip_only = 0 AND is_active = 1 ORDER BY current_clients ASC LIMIT 1");
}

$serverId = $server['id'] ?? 1;

// Generate device ID
$deviceId = 'dev_' . bin2hex(random_bytes(8));

$now = date('Y-m-d H:i:s');

// Create device
$devicesDb->insert('devices', [
    'device_id' => $deviceId,
    'user_id' => $userId,
    'device_name' => trim($input['device_name']),
    'device_type' => $input['device_type'] ?? 'other',
    'public_key' => $input['public_key'],
    'assigned_ip' => $ip['ip_address'],
    'current_server_id' => $serverId,
    'status' => 'active',
    'is_online' => 0,
    'created_at' => $now,
    'updated_at' => $now
]);

// Mark IP as used
$devicesDb->update('ip_pool', [
    'is_available' => 0,
    'device_id' => $deviceId,
    'assigned_at' => $now
], 'ip_address = ?', [$ip['ip_address']]);

// Update server client count
$serversDb->query("UPDATE servers SET current_clients = current_clients + 1 WHERE id = ?", [$serverId]);

echo json_encode([
    'success' => true,
    'device' => [
        'device_id' => $deviceId,
        'device_name' => $input['device_name'],
        'device_type' => $input['device_type'] ?? 'other',
        'assigned_ip' => $ip['ip_address'],
        'server_id' => $serverId
    ],
    'message' => 'Device registered successfully'
]);
