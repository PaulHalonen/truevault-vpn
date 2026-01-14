<?php
/**
 * TrueVault VPN - Server Switch API
 * Switch device from one server to another
 * 
 * Process:
 * 1. Validates device ownership
 * 2. Validates new server access
 * 3. Removes peer from old server
 * 4. Adds peer to new server
 * 5. Generates new config file
 * 6. Updates device record
 * 
 * User downloads new config and imports to WireGuard app
 */

require_once '../config/database.php';
require_once '../helpers/auth.php';
require_once '../helpers/response.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require authentication
Auth::require();
$user = Auth::getUser();
$userId = $user['id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    Response::error('Invalid request data', 400);
}

$deviceId = trim($data['device_id'] ?? '');
$oldServerId = (int)($data['old_server_id'] ?? 0);
$newServerId = (int)($data['new_server_id'] ?? 0);

// Validate input
if (empty($deviceId)) {
    Response::error('Device ID is required', 400);
}

if ($oldServerId <= 0 || $newServerId <= 0) {
    Response::error('Invalid server ID', 400);
}

if ($oldServerId === $newServerId) {
    Response::error('New server must be different from current server', 400);
}

try {
    // ============================================
    // STEP 1: VERIFY DEVICE OWNERSHIP
    // ============================================
    
    $device = Database::queryOne('devices',
        "SELECT * FROM user_devices 
         WHERE device_id = ? AND user_id = ?",
        [$deviceId, $userId]
    );
    
    if (!$device) {
        Response::error('Device not found', 404);
    }
    
    if ($device['status'] !== 'active') {
        Response::error('Device is not active', 400);
    }
    
    // ============================================
    // STEP 2: VALIDATE OLD SERVER
    // ============================================
    
    $oldServer = Database::queryOne('vpn',
        "SELECT * FROM vpn_servers WHERE id = ?",
        [$oldServerId]
    );
    
    if (!$oldServer) {
        Response::error('Old server not found', 404);
    }
    
    // ============================================
    // STEP 3: VALIDATE NEW SERVER
    // ============================================
    
    $newServer = Database::queryOne('vpn',
        "SELECT * FROM vpn_servers WHERE id = ?",
        [$newServerId]
    );
    
    if (!$newServer) {
        Response::error('New server not found', 404);
    }
    
    if ($newServer['status'] !== 'online') {
        Response::error('New server is currently offline. Please try a different server.', 503);
    }
    
    // Check VIP-only servers
    if (!empty($newServer['vip_only'])) {
        $allowedEmail = strtolower(trim($newServer['vip_only']));
        $userEmail = strtolower(trim($user['email']));
        
        if ($allowedEmail !== $userEmail && $user['email'] !== 'paulhalonen@gmail.com') {
            Response::error('This server is reserved for VIP members only', 403);
        }
    }
    
    // ============================================
    // STEP 4: GET USER'S WIREGUARD KEY
    // ============================================
    
    $userKey = Database::queryOne('certificates',
        "SELECT * FROM user_certificates 
         WHERE user_id = ? AND type = 'wireguard' AND status = 'active'
         LIMIT 1",
        [$userId]
    );
    
    if (!$userKey) {
        Response::error('WireGuard key not found. Please reconnect device.', 404);
    }
    
    // ============================================
    // STEP 5: REMOVE PEER FROM OLD SERVER
    // ============================================
    
    $oldServerIp = $oldServer['ip'];
    $oldServerApiPort = $oldServer['api_port'] ?? 8080;
    $removeUrl = "http://{$oldServerIp}:{$oldServerApiPort}/peers/remove";
    
    $removeData = [
        'public_key' => $userKey['public_key']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $removeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($removeData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer TrueVault2026SecretKey'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $removeResponse = curl_exec($ch);
    $removeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Update old peer record (even if server call failed)
    Database::execute('vpn',
        "UPDATE user_peers 
         SET status = 'removed', removed_at = datetime('now')
         WHERE user_id = ? AND server_id = ?",
        [$userId, $oldServerId]
    );
    
    // Log removal (even if failed, we still moved on)
    if ($removeHttpCode !== 200) {
        error_log("Failed to remove peer from server {$oldServerId}: HTTP {$removeHttpCode}");
    }
    
    // ============================================
    // STEP 6: ADD PEER TO NEW SERVER
    // ============================================
    
    // Calculate new IP address
    $networkPrefix = $newServer['network'];
    $lastOctet = ($userId % 250) + 2;
    $newAssignedIp = "{$networkPrefix}.{$lastOctet}";
    
    $newServerIp = $newServer['ip'];
    $newServerApiPort = $newServer['api_port'] ?? 8080;
    $addUrl = "http://{$newServerIp}:{$newServerApiPort}/peers/add";
    
    $addData = [
        'public_key' => $userKey['public_key'],
        'user_id' => $userId
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $addUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($addData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer TrueVault2026SecretKey'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $addResponse = curl_exec($ch);
    $addHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($addHttpCode !== 200) {
        Response::error('Failed to add peer to new server. Please try again.', 500);
    }
    
    $serverResponse = json_decode($addResponse, true);
    
    if (!$serverResponse || !$serverResponse['success']) {
        $errorMsg = $serverResponse['error'] ?? 'Unknown server error';
        Response::error("Server provisioning failed: {$errorMsg}", 500);
    }
    
    // Use server-assigned IP if different
    if (!empty($serverResponse['allowed_ip'])) {
        $newAssignedIp = $serverResponse['allowed_ip'];
    }
    
    // ============================================
    // STEP 7: STORE NEW PEER RECORD
    // ============================================
    
    Database::execute('vpn',
        "INSERT INTO user_peers 
         (user_id, server_id, public_key, assigned_ip, status, created_at)
         VALUES (?, ?, ?, ?, 'active', datetime('now'))",
        [$userId, $newServerId, $userKey['public_key'], $newAssignedIp]
    );
    
    // ============================================
    // STEP 8: UPDATE DEVICE RECORD
    // ============================================
    
    Database::execute('devices',
        "UPDATE user_devices 
         SET server_id = ?, updated_at = datetime('now')
         WHERE device_id = ?",
        [$newServerId, $deviceId]
    );
    
    // ============================================
    // STEP 9: GENERATE NEW CONFIG
    // ============================================
    
    $configFilename = $newServer['config_filename'];
    $serverPublicKey = $newServer['public_key'];
    $serverEndpoint = "{$newServer['ip']}:{$newServer['port']}";
    
    $config = <<<CONF
[Interface]
# TrueVault VPN - {$newServer['name']}
# Config: {$configFilename}
# User: {$user['email']}
# Device: {$device['name']}
# Generated: [DATE_GENERATED]
# Switched from: {$oldServer['name']}

PrivateKey = {$userKey['private_key']}
Address = {$newAssignedIp}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
# TrueVault {$newServer['name']} Server
PublicKey = {$serverPublicKey}
Endpoint = {$serverEndpoint}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
CONF;
    
    $config = str_replace('[DATE_GENERATED]', date('Y-m-d H:i:s'), $config);
    
    // ============================================
    // STEP 10: LOG ACTIVITY
    // ============================================
    
    Database::execute('logs',
        "INSERT INTO activity_log 
         (user_id, action, details, created_at)
         VALUES (?, 'server_switch', ?, datetime('now'))",
        [$userId, json_encode([
            'device_id' => $deviceId,
            'device_name' => $device['name'],
            'from_server_id' => $oldServerId,
            'from_server_name' => $oldServer['name'],
            'to_server_id' => $newServerId,
            'to_server_name' => $newServer['name'],
            'new_ip' => $newAssignedIp
        ])]
    );
    
    // ============================================
    // STEP 11: RETURN SUCCESS
    // ============================================
    
    Response::success([
        'device_id' => $deviceId,
        'device_name' => $device['name'],
        'old_server' => [
            'id' => $oldServerId,
            'name' => $oldServer['name']
        ],
        'new_server' => [
            'id' => $newServerId,
            'name' => $newServer['name'],
            'location' => $newServer['location']
        ],
        'assigned_ip' => $newAssignedIp,
        'config' => $config,
        'config_filename' => $configFilename,
        'message' => 'Server switched successfully. Download and import the new config file in your WireGuard app.',
        'instructions' => [
            'windows' => 'In WireGuard, remove the old tunnel, then click "Import tunnel(s) from file" and select ' . $configFilename,
            'mac' => 'In WireGuard, delete the old tunnel, then drag ' . $configFilename . ' into the app window',
            'ios' => 'In WireGuard, delete the old tunnel, then tap "+" and import ' . $configFilename,
            'android' => 'In WireGuard, delete the old tunnel, then tap "+" and import ' . $configFilename,
            'linux' => 'Run: sudo wg-quick down <old>, then sudo cp ' . $configFilename . ' /etc/wireguard/ && sudo wg-quick up ' . basename($configFilename, '.conf')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Server Switch Error: " . $e->getMessage());
    Response::error('An error occurred during server switch. Please try again.', 500);
}
