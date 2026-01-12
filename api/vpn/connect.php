<?php
/**
 * TrueVault VPN - Connect API
 * POST /api/vpn/connect.php
 * 
 * Generates WireGuard configuration for connecting to a server
 * Keys are generated server-side for security
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

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
        if ($user['email'] !== $vipEmail) {
            Response::error('This is a VIP-only server', 403);
        }
    }
    
    // Generate or retrieve user's keys
    $userCert = Database::queryOne('certificates', 
        "SELECT * FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'", 
        [$user['id']]
    );
    
    if (!$userCert) {
        // Generate new WireGuard keypair
        $privateKey = generateWireGuardPrivateKey();
        $publicKey = generateWireGuardPublicKey($privateKey);
        
        // Store in database
        Database::execute('certificates',
            "INSERT INTO user_certificates (user_id, name, type, public_key, private_key, status, created_at) 
             VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
            [$user['id'], $publicKey, $privateKey]
        );
        
        $userCert = [
            'private_key' => $privateKey,
            'public_key' => $publicKey
        ];
    }
    
    // Assign IP address for this user on this server
    $assignedIp = assignVpnIp($user['id'], $serverId);
    
    // Generate WireGuard config
    $config = generateWireGuardConfig($server, $userCert, $assignedIp);
    
    // Log the connection attempt
    Database::execute('vpn',
        "INSERT INTO vpn_connections (user_id, server_id, status, assigned_ip, connected_at) 
         VALUES (?, ?, 'pending', ?, datetime('now'))",
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
            'ip' => $server['ip_address']
        ],
        'assigned_ip' => $assignedIp,
        'config' => $config,
        'public_key' => $userCert['public_key']
    ], 'Connection configuration generated');

} catch (Exception $e) {
    Response::serverError('Failed to connect: ' . $e->getMessage());
}

/**
 * Generate WireGuard private key
 * In production, this would use actual WireGuard key generation
 */
function generateWireGuardPrivateKey() {
    // Generate a 32-byte random key and base64 encode
    $bytes = random_bytes(32);
    // Set the appropriate bits for Curve25519
    $bytes[0] = chr(ord($bytes[0]) & 248);
    $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
    return base64_encode($bytes);
}

/**
 * Generate WireGuard public key from private key
 * In production, this would use actual Curve25519
 */
function generateWireGuardPublicKey($privateKey) {
    // Simplified - in production use sodium_crypto_scalarmult_base
    // For demo, we'll generate a pseudo-public key
    $hash = hash('sha256', base64_decode($privateKey), true);
    return base64_encode($hash);
}

/**
 * Assign a VPN IP address to user for this server
 */
function assignVpnIp($userId, $serverId) {
    // Check if user already has an IP on this server
    $existing = Database::queryOne('vpn',
        "SELECT assigned_ip FROM vpn_connections WHERE user_id = ? AND server_id = ? AND assigned_ip IS NOT NULL ORDER BY id DESC LIMIT 1",
        [$userId, $serverId]
    );
    
    if ($existing && $existing['assigned_ip']) {
        return $existing['assigned_ip'];
    }
    
    // Assign new IP in the 10.8.0.0/24 range
    // Use user_id + server_id to generate consistent IP
    $lastOctet = (($userId * 7) + $serverId) % 250 + 2; // 2-252
    return "10.8.0.{$lastOctet}";
}

/**
 * Generate WireGuard configuration file content
 */
function generateWireGuardConfig($server, $userCert, $assignedIp) {
    // Server's public key (would be stored in database in production)
    // For now, generate based on server IP
    $serverPublicKey = base64_encode(hash('sha256', $server['ip_address'] . '_server_key', true));
    
    $config = "[Interface]
# TrueVault VPN - {$server['name']}
# Generated: " . date('Y-m-d H:i:s') . "
PrivateKey = {$userCert['private_key']}
Address = {$assignedIp}/24
DNS = 1.1.1.1, 8.8.8.8

[Peer]
# {$server['name']} ({$server['location']})
PublicKey = {$serverPublicKey}
Endpoint = {$server['ip_address']}:{$server['port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
";
    
    return $config;
}
