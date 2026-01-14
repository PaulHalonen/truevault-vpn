<?php
/**
 * TrueVault VPN - Quick Connect API
 * 2-Click Device Setup - Automated VPN Provisioning
 * 
 * This endpoint automates the entire VPN connection process:
 * 1. Validates device limits
 * 2. Validates server access
 * 3. Generates/retrieves WireGuard keys
 * 4. Provisions peer on server
 * 5. Generates config file
 * 6. Registers device
 * 
 * User Experience: Click "Connect" → 3 seconds later → Download config
 */

require_once '../config/database.php';
require_once '../helpers/auth.php';
require_once '../helpers/response.php';
require_once '../helpers/peer-manager.php';

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

$deviceName = trim($data['device_name'] ?? '');
$serverId = (int)($data['server_id'] ?? 0);

// Validate input
if (empty($deviceName)) {
    Response::error('Device name is required', 400);
}

if (strlen($deviceName) > 50) {
    Response::error('Device name too long (max 50 characters)', 400);
}

if ($serverId <= 0) {
    Response::error('Invalid server ID', 400);
}

try {
    // ============================================
    // STEP 1: VALIDATE DEVICE LIMITS
    // ============================================
    
    $deviceCount = Database::queryOne('devices',
        "SELECT COUNT(*) as count FROM user_devices 
         WHERE user_id = ? AND status = 'active'",
        [$userId]
    );
    
    $currentDevices = $deviceCount ? (int)$deviceCount['count'] : 0;
    
    // Get subscription limits
    $subscription = Database::queryOne('subscriptions',
        "SELECT max_devices, plan_type, status FROM subscriptions 
         WHERE user_id = ? AND status = 'active'
         ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    if (!$subscription) {
        Response::error('No active subscription found', 403);
    }
    
    $maxDevices = (int)$subscription['max_devices'];
    
    if ($currentDevices >= $maxDevices) {
        Response::error(
            "Device limit reached ({$currentDevices}/{$maxDevices}). Remove a device or upgrade your plan.",
            403
        );
    }
    
    // ============================================
    // STEP 2: VALIDATE SERVER ACCESS
    // ============================================
    
    $server = Database::queryOne('vpn',
        "SELECT * FROM vpn_servers WHERE id = ?",
        [$serverId]
    );
    
    if (!$server) {
        Response::error('Server not found', 404);
    }
    
    if ($server['status'] !== 'online') {
        Response::error('Server is currently offline. Please try a different server.', 503);
    }
    
    // Check VIP-only servers
    if (!empty($server['vip_only'])) {
        $allowedEmail = strtolower(trim($server['vip_only']));
        $userEmail = strtolower(trim($user['email']));
        
        if ($allowedEmail !== $userEmail && $user['email'] !== 'paulhalonen@gmail.com') {
            Response::error('This server is reserved for VIP members only', 403);
        }
    }
    
    // Check plan restrictions (Basic/Family can't use certain servers for cameras)
    // This check is done at camera registration, not device registration
    
    // ============================================
    // STEP 3: GET OR CREATE WIREGUARD KEYS
    // ============================================
    
    $userKey = Database::queryOne('certificates',
        "SELECT * FROM user_certificates 
         WHERE user_id = ? AND type = 'wireguard' AND status = 'active'
         LIMIT 1",
        [$userId]
    );
    
    if (!$userKey) {
        // Generate new WireGuard key pair
        // Using simple method (production would use wg genkey/pubkey)
        $privateKey = base64_encode(random_bytes(32));
        $publicKey = base64_encode(random_bytes(32)); // Simplified for now
        
        $certId = Database::execute('certificates',
            "INSERT INTO user_certificates 
             (user_id, name, type, public_key, private_key, status, created_at)
             VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
            [$userId, $publicKey, $privateKey]
        );
        
        $userKey = [
            'id' => $certId,
            'public_key' => $publicKey,
            'private_key' => $privateKey
        ];
    }
    
    // ============================================
    // STEP 4: CHECK IF PEER ALREADY EXISTS
    // ============================================
    
    $existingPeer = Database::queryOne('vpn',
        "SELECT * FROM user_peers 
         WHERE user_id = ? AND server_id = ? AND status = 'active'",
        [$userId, $serverId]
    );
    
    $assignedIp = null;
    
    if (!$existingPeer) {
        // ============================================
        // STEP 5: PROVISION PEER ON SERVER
        // ============================================
        
        // Calculate IP address (user_id % 250) + 2
        $networkPrefix = $server['network'];
        $lastOctet = ($userId % 250) + 2;
        $assignedIp = "{$networkPrefix}.{$lastOctet}";
        
        // Call server Peer API to add peer
        $serverIp = $server['ip'];
        $serverApiPort = $server['api_port'] ?? 8080;
        $apiUrl = "http://{$serverIp}:{$serverApiPort}/peers/add";
        
        $peerData = [
            'public_key' => $userKey['public_key'],
            'user_id' => $userId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($peerData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer TrueVault2026SecretKey'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            Response::error('Failed to provision VPN access on server. Please try again.', 500);
        }
        
        $serverResponse = json_decode($response, true);
        
        if (!$serverResponse || !$serverResponse['success']) {
            $errorMsg = $serverResponse['error'] ?? 'Unknown server error';
            Response::error("Server provisioning failed: {$errorMsg}", 500);
        }
        
        // Use server-assigned IP (should match our calculation)
        if (!empty($serverResponse['allowed_ip'])) {
            $assignedIp = $serverResponse['allowed_ip'];
        }
        
        // ============================================
        // STEP 6: STORE PEER RECORD
        // ============================================
        
        Database::execute('vpn',
            "INSERT INTO user_peers 
             (user_id, server_id, public_key, assigned_ip, status, created_at)
             VALUES (?, ?, ?, ?, 'active', datetime('now'))",
            [$userId, $serverId, $userKey['public_key'], $assignedIp]
        );
    } else {
        $assignedIp = $existingPeer['assigned_ip'];
    }
    
    // ============================================
    // STEP 7: REGISTER DEVICE
    // ============================================
    
    $deviceId = 'dev_' . bin2hex(random_bytes(8));
    
    Database::execute('devices',
        "INSERT INTO user_devices 
         (device_id, user_id, name, type, server_id, status, created_at)
         VALUES (?, ?, ?, 'vpn_device', ?, 'active', datetime('now'))",
        [$deviceId, $userId, $deviceName, $serverId]
    );
    
    // ============================================
    // STEP 8: GENERATE WIREGUARD CONFIG
    // ============================================
    
    $configFilename = $server['config_filename'];
    $serverPublicKey = $server['public_key'];
    $serverEndpoint = "{$server['ip']}:{$server['port']}";
    
    $config = <<<CONF
[Interface]
# TrueVault VPN - {$server['name']}
# Config: {$configFilename}
# User: {$user['email']}
# Device: {$deviceName}
# Generated: [DATE_GENERATED]

PrivateKey = {$userKey['private_key']}
Address = {$assignedIp}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
# TrueVault {$server['name']} Server
PublicKey = {$serverPublicKey}
Endpoint = {$serverEndpoint}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
CONF;
    
    $config = str_replace('[DATE_GENERATED]', date('Y-m-d H:i:s'), $config);
    
    // ============================================
    // STEP 9: LOG ACTIVITY
    // ============================================
    
    Database::execute('logs',
        "INSERT INTO activity_log 
         (user_id, action, details, created_at)
         VALUES (?, 'device_connected', ?, datetime('now'))",
        [$userId, json_encode([
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'server_id' => $serverId,
            'server_name' => $server['name'],
            'assigned_ip' => $assignedIp
        ])]
    );
    
    // ============================================
    // STEP 10: RETURN SUCCESS
    // ============================================
    
    Response::success([
        'device_id' => $deviceId,
        'server_name' => $server['name'],
        'server_location' => $server['location'],
        'assigned_ip' => $assignedIp,
        'config' => $config,
        'config_filename' => $configFilename,
        'instructions' => [
            'windows' => 'Install WireGuard from wireguard.com, click "Import tunnel(s) from file", select ' . $configFilename . ', then click "Activate"',
            'mac' => 'Install WireGuard from App Store, drag ' . $configFilename . ' into the app window, then toggle the switch to connect',
            'ios' => 'Install WireGuard from App Store, tap "+", choose "Create from file or archive", select ' . $configFilename . ' or scan QR code, then toggle to connect',
            'android' => 'Install WireGuard from Play Store, tap "+", choose "Import from file", select ' . $configFilename . ' or tap "Scan from QR code", then toggle to activate',
            'linux' => 'Copy ' . $configFilename . ' to /etc/wireguard/, then run: sudo wg-quick up ' . basename($configFilename, '.conf')
        ],
        'remaining_slots' => $maxDevices - ($currentDevices + 1)
    ]);
    
} catch (Exception $e) {
    error_log("Quick Connect Error: " . $e->getMessage());
    Response::error('An error occurred during setup. Please try again.', 500);
}
