<?php
/**
 * TrueVault VPN - Add Device v2
 * POST /api/devices/add.php
 * 
 * 2-Click Workflow with browser-side key generation
 * Private keys never touch the server
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

Response::requireMethod('POST');

$input = Response::getJsonInput();

// Validate input
if (empty($input['device_name'])) {
    Response::error('Device name required', 400);
}

if (empty($input['public_key'])) {
    Response::error('Public key required', 400);
}

if (empty($input['server_id'])) {
    Response::error('Server ID required', 400);
}

$deviceName = trim($input['device_name']);
$publicKey = trim($input['public_key']);
$serverId = (int)$input['server_id'];
$deviceType = $input['device_type'] ?? 'unknown';

// Check if VIP
$isVIP = VIPManager::isVIP($user['email']);
$vipDetails = $isVIP ? VIPManager::getVIPDetails($user['email']) : null;

// VIP user MUST use dedicated server
if ($isVIP && $vipDetails && $vipDetails['dedicated_server_id']) {
    $serverId = $vipDetails['dedicated_server_id'];
    Logger::log('vip', "VIP user {$user['email']} forced to dedicated server {$serverId}");
}

// Check device limit (VIP = unlimited)
if (!$isVIP) {
    $maxDevices = getMaxDevices($user['plan_type']);
    
    $deviceCount = Database::queryOne('users',
        "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1",
        [$user['id']]
    );
    
    if ($deviceCount && $deviceCount['count'] >= $maxDevices) {
        Response::error("Device limit reached. Upgrade your plan to add more devices.", 403);
    }
}

// Check if device name already exists for this user
$existing = Database::queryOne('users',
    "SELECT id FROM user_devices WHERE user_id = ? AND device_name = ?",
    [$user['id'], $deviceName]
);

if ($existing) {
    Response::error('A device with this name already exists', 409);
}

// Check if public key already exists
$existingKey = Database::queryOne('users',
    "SELECT id FROM user_devices WHERE public_key = ?",
    [$publicKey]
);

if ($existingKey) {
    Response::error('This public key is already in use', 409);
}

// Get server details
$server = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ? AND status = 'active'",
    [$serverId]
);

if (!$server) {
    Response::error('Invalid or inactive server', 400);
}

// Check if non-VIP trying to access VIP server
if (!$isVIP && $server['server_type'] === 'vip_dedicated') {
    Response::error('This server is reserved for VIP users', 403);
}

// Get next available IP for this server
$assignedIp = getNextAvailableIp($serverId);

if (!$assignedIp) {
    Response::error('No available IP addresses on this server', 503);
}

// Call server's peer API to add the peer
$peerResult = addPeerToServer($server, $publicKey, $assignedIp, $user['id']);

if (!$peerResult['success']) {
    Logger::log('error', "Failed to add peer to server {$serverId}: " . $peerResult['error']);
    Response::error('Failed to configure VPN server: ' . $peerResult['error'], 500);
}

// Add device to database
try {
    Database::beginTransaction('users');
    
    // Insert device
    Database::execute('users',
        "INSERT INTO user_devices (user_id, device_name, device_type, public_key, current_server_id, assigned_ip, is_active, created_at, last_connected) 
         VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))",
        [$user['id'], $deviceName, $deviceType, $publicKey, $serverId, $assignedIp]
    );
    
    $deviceId = Database::lastInsertId('users');
    
    // Add to user_peers in vpn.db
    Database::execute('vpn',
        "INSERT INTO user_peers (user_id, server_id, device_name, public_key, assigned_ip, is_active, created_at) 
         VALUES (?, ?, ?, ?, ?, 1, datetime('now'))",
        [$user['id'], $serverId, $deviceName, $publicKey, $assignedIp]
    );
    
    Database::commit('users');
    
    Logger::log('device', "Device added: {$deviceName} for user {$user['email']} on server {$serverId}");
    
    // Return config data for client-side config generation
    Response::success([
        'device_id' => $deviceId,
        'device_name' => $deviceName,
        'assigned_ip' => $assignedIp,
        'server' => [
            'id' => $server['id'],
            'name' => $server['display_name'] ?? $server['name'],
            'ip' => $server['ip_address'],
            'port' => $server['wireguard_port'],
            'public_key' => $server['public_key']
        ],
        'dns' => ['1.1.1.1', '8.8.8.8']
    ], 'Device added successfully');
    
} catch (Exception $e) {
    Database::rollback('users');
    Logger::log('error', "Database error adding device: " . $e->getMessage());
    Response::serverError('Failed to save device: ' . $e->getMessage());
}

// Helper functions

function getMaxDevices($planType) {
    $limits = [
        'trial' => 1,
        'free' => 1,
        'personal' => 3,
        'family' => 999,
        'business' => 999
    ];
    return $limits[$planType] ?? 1;
}

function getNextAvailableIp($serverId) {
    // Get all assigned IPs for this server
    $assignedIps = Database::query('vpn',
        "SELECT assigned_ip FROM user_peers WHERE server_id = ? AND is_active = 1",
        [$serverId]
    );
    
    $usedIps = array_column($assignedIps, 'assigned_ip');
    
    // Generate IPs from 10.0.0.2 to 10.0.0.254
    for ($i = 2; $i <= 254; $i++) {
        $ip = "10.0.0.{$i}";
        if (!in_array($ip, $usedIps)) {
            return $ip;
        }
    }
    
    return null;
}

function addPeerToServer($server, $publicKey, $assignedIp, $userId) {
    $apiUrl = "http://{$server['ip_address']}:{$server['api_port']}/add_peer";
    
    $data = [
        'public_key' => $publicKey,
        'allowed_ips' => "{$assignedIp}/32",
        'user_id' => $userId
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . (getenv('VPN_API_KEY') ?: 'truevault-api-key-2026')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => "Connection failed: {$error}"];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "Server returned HTTP {$httpCode}"];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['success'])) {
        return ['success' => false, 'error' => 'Invalid server response'];
    }
    
    return $result;
}
