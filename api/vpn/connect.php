<?php
/**
 * TrueVault VPN - Connect API
 * POST /api/vpn/connect.php
 * 
 * Generates WireGuard configuration for connecting to a server
 * Returns text config (no QR codes - users must copy/paste)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

// Require authentication
$user = Auth::requireAuth();

// Only POST
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['server_id'])) {
    Response::error('Server ID is required', 400);
}

$serverId = (int) $input['server_id'];

try {
    // Get server
    $server = Database::queryOne('vpn', "SELECT * FROM vpn_servers WHERE id = ? AND status = 'online'", [$serverId]);
    
    if (!$server) {
        Response::error('Server not found or offline', 404);
    }
    
    // Check VIP server access
    if ($server['is_vip'] == 1) {
        $vipEmail = $server['vip_user_email'];
        if (strtolower($user['email']) !== strtolower($vipEmail)) {
            Response::error('This is a VIP-only server', 403);
        }
    }
    
    // Check subscription status (VIPs bypass)
    if (!VIPManager::isVIP($user['email'])) {
        $subscription = BillingManager::getCurrentSubscription($user['id']);
        if (!$subscription || $subscription['status'] !== 'active') {
            Response::error('Active subscription required', 403);
        }
    }
    
    // Check device limits
    $deviceCount = Database::queryOne('devices', 
        "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND status = 'active'",
        [$user['id']]
    );
    
    $limits = VIPManager::isVIP($user['email']) 
        ? VIPManager::getVIPLimits($user['email'])
        : ['max_devices' => $subscription['max_devices'] ?? 3];
    
    // Generate or retrieve user's keys
    $userKey = PeerManager::getOrCreateUserKey($user['id']);
    
    // Check if already has peer on this server
    $existingPeer = Database::queryOne('vpn',
        "SELECT * FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
        [$user['id'], $serverId]
    );
    
    $assignedIp = null;
    
    if ($existingPeer) {
        $assignedIp = $existingPeer['assigned_ip'];
    } else {
        // Add peer to server via API
        $result = addPeerToServer($server, $userKey['public_key'], $user['id']);
        
        if ($result['success']) {
            $assignedIp = $result['allowed_ip'];
            
            // Store peer record
            Database::execute('vpn',
                "INSERT INTO user_peers (user_id, server_id, public_key, assigned_ip, status, created_at)
                 VALUES (?, ?, ?, ?, 'active', datetime('now'))",
                [$user['id'], $serverId, $userKey['public_key'], $assignedIp]
            );
        } else {
            // Fallback - assign IP locally
            $assignedIp = assignLocalIp($user['id'], $server['network_prefix']);
        }
    }
    
    // Generate config file name
    $configName = generateConfigName($server);
    
    // Generate WireGuard config
    $config = generateWireGuardConfig($server, $userKey, $assignedIp, $configName);
    
    // Log the connection
    Database::execute('vpn',
        "INSERT INTO vpn_connections (user_id, server_id, status, assigned_ip, connected_at) 
         VALUES (?, ?, 'active', ?, datetime('now'))",
        [$user['id'], $serverId, $assignedIp]
    );
    
    // Log activity
    Database::execute('logs',
        "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
         VALUES (?, 'vpn_connect', ?, ?, datetime('now'))",
        [$user['id'], json_encode(['server' => $server['name'], 'assigned_ip' => $assignedIp]), $_SERVER['REMOTE_ADDR'] ?? null]
    );
    
    Response::success([
        'server' => [
            'id' => $server['id'],
            'name' => $server['name'],
            'location' => $server['location'],
            'ip' => $server['ip_address'],
            'instructions' => $server['instructions'],
            'allowed_uses' => explode(',', $server['allowed_uses']),
            'bandwidth_limit' => $server['bandwidth_limit']
        ],
        'assigned_ip' => $assignedIp,
        'config_name' => $configName,
        'config' => $config,
        'public_key' => $userKey['public_key'],
        'instructions' => [
            '1. Copy the configuration text below',
            '2. Open WireGuard on your device',
            '3. Click "Add Tunnel" → "Add empty tunnel" (or import from text)',
            '4. Paste the configuration',
            '5. Save as "' . $configName . '"',
            '6. Activate the tunnel'
        ]
    ], 'Configuration generated');

} catch (Exception $e) {
    Response::serverError('Failed to connect: ' . $e->getMessage());
}

/**
 * Generate config file name based on server
 */
function generateConfigName($server) {
    $names = [
        'New York' => 'TrueVaultNY',
        'St. Louis VIP' => 'TrueVaultSTL',
        'Dallas' => 'TrueVaultTX',
        'Toronto' => 'TrueVaultCAN'
    ];
    
    return ($names[$server['name']] ?? 'TrueVault' . $server['id']) . '.conf';
}

/**
 * Add peer to VPN server via API
 */
function addPeerToServer($server, $publicKey, $userId) {
    $url = "http://{$server['ip_address']}:{$server['api_port']}/peers/add";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer TrueVault2026SecretKey'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'public_key' => $publicKey,
        'user_id' => $userId
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 || $httpCode === 201) {
        return json_decode($response, true) ?: ['success' => false];
    }
    
    return ['success' => false, 'error' => 'Server unreachable'];
}

/**
 * Assign IP locally as fallback
 */
function assignLocalIp($userId, $networkPrefix) {
    $lastOctet = ($userId % 250) + 2;
    return "{$networkPrefix}.{$lastOctet}";
}

/**
 * Generate WireGuard configuration file content
 */
function generateWireGuardConfig($server, $userKey, $assignedIp, $configName) {
    $config = "[Interface]
# TrueVault VPN - {$server['name']}
# Config: {$configName}
# Generated: " . date('Y-m-d H:i:s') . "
# Location: {$server['location']}
PrivateKey = {$userKey['private_key']}
Address = {$assignedIp}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
# TrueVault {$server['name']} Server
PublicKey = {$server['public_key']}
Endpoint = {$server['ip_address']}:{$server['port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
";
    
    return $config;
}
