<?php
/**
 * TrueVault VPN - Switch Server
 * POST /api/devices/switch.php
 * 
 * Switch a device to a different server
 * Generates new keys, removes old peer, adds new peer
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
if (empty($input['device_id'])) {
    Response::error('Device ID required', 400);
}

if (empty($input['new_server_id'])) {
    Response::error('New server ID required', 400);
}

if (empty($input['new_public_key'])) {
    Response::error('New public key required', 400);
}

$deviceId = (int)$input['device_id'];
$newServerId = (int)$input['new_server_id'];
$newPublicKey = trim($input['new_public_key']);

// Get device
$device = Database::queryOne('users',
    "SELECT * FROM user_devices WHERE id = ? AND user_id = ?",
    [$deviceId, $user['id']]
);

if (!$device) {
    Response::error('Device not found', 404);
}

// Check if VIP
$isVIP = VIPManager::isVIP($user['email']);
$vipDetails = $isVIP ? VIPManager::getVIPDetails($user['email']) : null;

// VIP user MUST use dedicated server
if ($isVIP && $vipDetails && $vipDetails['dedicated_server_id']) {
    $newServerId = $vipDetails['dedicated_server_id'];
    Logger::log('vip', "VIP user {$user['email']} forced to dedicated server {$newServerId}");
}

// Get old server
$oldServer = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ?",
    [$device['current_server_id']]
);

// Get new server
$newServer = Database::queryOne('servers',
    "SELECT * FROM vpn_servers WHERE id = ? AND status = 'active'",
    [$newServerId]
);

if (!$newServer) {
    Response::error('Invalid or inactive server', 400);
}

// Check if non-VIP trying to access VIP server
if (!$isVIP && $newServer['server_type'] === 'vip_dedicated') {
    Response::error('This server is reserved for VIP users', 403);
}

// Check if switching to same server
if ($device['current_server_id'] == $newServerId) {
    Response::error('Device is already on this server', 400);
}

// Get next available IP on new server
$newAssignedIp = getNextAvailableIp($newServerId);

if (!$newAssignedIp) {
    Response::error('No available IP addresses on the new server', 503);
}

// Remove peer from old server
if ($oldServer) {
    $removeResult = removePeerFromServer($oldServer, $device['public_key']);
    if (!$removeResult['success']) {
        Logger::log('warning', "Failed to remove peer from old server {$oldServer['id']}: " . $removeResult['error']);
        // Continue anyway - we'll add to new server
    }
}

// Add peer to new server
$addResult = addPeerToServer($newServer, $newPublicKey, $newAssignedIp, $user['id']);

if (!$addResult['success']) {
    Logger::log('error', "Failed to add peer to new server {$newServerId}: " . $addResult['error']);
    Response::error('Failed to configure new VPN server: ' . $addResult['error'], 500);
}

// Update database
try {
    Database::beginTransaction('users');
    
    // Update device
    Database::execute('users',
        "UPDATE user_devices SET public_key = ?, current_server_id = ?, assigned_ip = ?, last_connected = datetime('now') WHERE id = ?",
        [$newPublicKey, $newServerId, $newAssignedIp, $deviceId]
    );
    
    // Remove old peer entry
    Database::execute('vpn',
        "DELETE FROM user_peers WHERE user_id = ? AND device_name = ? AND server_id = ?",
        [$user['id'], $device['device_name'], $device['current_server_id']]
    );
    
    // Add new peer entry
    Database::execute('vpn',
        "INSERT INTO user_peers (user_id, server_id, device_name, public_key, assigned_ip, is_active, created_at) 
         VALUES (?, ?, ?, ?, ?, 1, datetime('now'))",
        [$user['id'], $newServerId, $device['device_name'], $newPublicKey, $newAssignedIp]
    );
    
    Database::commit('users');
    
    Logger::log('device', "Device {$device['device_name']} switched from server {$device['current_server_id']} to {$newServerId}");
    
    // Return new config data
    Response::success([
        'device_id' => $deviceId,
        'device_name' => $device['device_name'],
        'assigned_ip' => $newAssignedIp,
        'server' => [
            'id' => $newServer['id'],
            'name' => $newServer['display_name'] ?? $newServer['name'],
            'ip' => $newServer['ip_address'],
            'port' => $newServer['wireguard_port'],
            'public_key' => $newServer['public_key']
        ],
        'dns' => ['1.1.1.1', '8.8.8.8']
    ], 'Server switched successfully');
    
} catch (Exception $e) {
    Database::rollback('users');
    Logger::log('error', "Database error switching server: " . $e->getMessage());
    Response::serverError('Failed to update device: ' . $e->getMessage());
}

// Helper functions

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

function removePeerFromServer($server, $publicKey) {
    $apiUrl = "http://{$server['ip_address']}:{$server['api_port']}/remove_peer";
    
    $data = [
        'public_key' => $publicKey
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
