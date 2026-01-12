<?php
/**
 * TrueVault VPN - VPN Connect
 * POST /api/vpn/connect.php
 * 
 * Returns WireGuard configuration for connecting to a server
 * Keys are generated on the VPN server via API call
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Require authentication
$user = Auth::requireAuth();

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'server_id' => 'required|integer',
    'device_id' => 'integer',
    'identity_id' => 'integer'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$serverId = (int) $input['server_id'];
$deviceId = $input['device_id'] ?? null;
$identityId = $input['identity_id'] ?? null;

try {
    // Get server
    $serversDb = DatabaseManager::getInstance()->servers();
    $stmt = $serversDb->prepare("SELECT * FROM vpn_servers WHERE id = ?");
    $stmt->execute([$serverId]);
    $server = $stmt->fetch();
    
    if (!$server) {
        Response::notFound('Server not found');
    }
    
    // Check server status
    if ($server['status'] === 'offline') {
        Response::error('Server is currently offline', 503);
    }
    
    if ($server['status'] === 'maintenance') {
        Response::error('Server is under maintenance', 503);
    }
    
    // Check VIP restriction
    if ($server['is_vip_only']) {
        if (!Auth::isVipUser()) {
            Response::forbidden('This server is reserved for VIP users');
        }
        // Check if this VIP server belongs to this user
        if ($server['vip_user_email'] !== $user['email']) {
            Response::forbidden('This VIP server is not assigned to your account');
        }
    }
    
    // Check server capacity
    if ($server['current_connections'] >= $server['max_connections']) {
        Response::error('Server is at capacity. Please try another server.', 503);
    }
    
    // Generate keys on the VPN server via API
    // In production, this would make an actual API call to the VPN server
    $keyPair = generateKeysOnServer($server);
    
    if (!$keyPair) {
        Response::serverError('Failed to generate VPN keys');
    }
    
    // Assign IP address (simplified - in production would be managed by server)
    $assignedIp = assignClientIP($server);
    
    // Get identity if specified
    $identity = null;
    if ($identityId) {
        $identitiesDb = DatabaseManager::getInstance()->identities();
        $stmt = $identitiesDb->prepare("SELECT * FROM regional_identities WHERE id = ? AND user_id = ?");
        $stmt->execute([$identityId, $user['id']]);
        $identity = $stmt->fetch();
    }
    
    // Record connection
    $connectionsDb = DatabaseManager::getInstance()->connections();
    $stmt = $connectionsDb->prepare("
        INSERT INTO active_connections (user_id, server_id, device_id, client_ip, assigned_ip, public_key, identity_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $serverId,
        $deviceId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $assignedIp,
        $keyPair['public_key'],
        $identityId
    ]);
    
    $connectionId = $connectionsDb->lastInsertId();
    
    // Update server connection count
    $stmt = $serversDb->prepare("UPDATE vpn_servers SET current_connections = current_connections + 1 WHERE id = ?");
    $stmt->execute([$serverId]);
    
    // Generate WireGuard configuration
    $config = generateWireGuardConfig($server, $keyPair, $assignedIp);
    
    Logger::info('VPN connection initiated', [
        'user_id' => $user['id'],
        'server_id' => $serverId,
        'connection_id' => $connectionId
    ]);
    
    Response::success([
        'connection_id' => $connectionId,
        'server' => [
            'name' => $server['server_name'],
            'region' => $server['region'],
            'endpoint' => $server['ip_address'] . ':' . $server['wireguard_port']
        ],
        'config' => $config,
        'assigned_ip' => $assignedIp,
        'identity' => $identity ? [
            'region' => $identity['region_code'],
            'timezone' => $identity['timezone']
        ] : null
    ], 'Connection configuration generated');
    
} catch (Exception $e) {
    Logger::error('VPN connect failed: ' . $e->getMessage());
    Response::serverError('Failed to connect to VPN');
}

/**
 * Generate keys on the VPN server
 * In production, this makes an API call to the actual VPN server
 */
function generateKeysOnServer($server) {
    // In production, this would be:
    // $response = callServerAPI($server['ip_address'], $server['api_port'], '/generate-keys', $server['api_secret']);
    
    // For now, generate locally (placeholder)
    // IMPORTANT: Real implementation must generate on the VPN server!
    $privateKey = base64_encode(random_bytes(32));
    $publicKey = base64_encode(hash('sha256', base64_decode($privateKey), true));
    
    return [
        'private_key' => $privateKey,
        'public_key' => $publicKey
    ];
}

/**
 * Assign a client IP address
 */
function assignClientIP($server) {
    // In production, this would be managed by the VPN server
    // For now, generate a random IP in the 10.x.x.x range
    return '10.0.' . rand(1, 254) . '.' . rand(2, 254);
}

/**
 * Generate WireGuard configuration file content
 */
function generateWireGuardConfig($server, $keyPair, $clientIp) {
    $dns = $server['dns_servers'] ?? '1.1.1.1, 1.0.0.1';
    $allowedIps = $server['allowed_ips'] ?? '0.0.0.0/0, ::/0';
    $endpoint = $server['ip_address'] . ':' . $server['wireguard_port'];
    
    return "[Interface]
PrivateKey = {$keyPair['private_key']}
Address = {$clientIp}/32
DNS = {$dns}

[Peer]
PublicKey = {$server['public_key']}
AllowedIPs = {$allowedIps}
Endpoint = {$endpoint}
PersistentKeepalive = 25";
}
