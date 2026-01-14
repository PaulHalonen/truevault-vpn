<?php
/**
 * TrueVault VPN - WireGuard Config Generator
 * GET /api/vpn/config.php?server_id=X
 * 
 * Generates downloadable WireGuard configuration files
 * Names: TrueVaultNY.conf, TrueVaultTX.conf, TrueVaultCAN.conf, TrueVaultSTL.conf
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();

if (empty($_GET['server_id'])) {
    Response::error('Server ID required', 400);
}

$serverId = (int) $_GET['server_id'];

// Server configurations with real public keys
$servers = [
    1 => [
        'name' => 'NY',
        'full_name' => 'New York',
        'ip' => '66.94.103.91',
        'port' => 51820,
        'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
        'network' => '10.0.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'vip_only' => false
    ],
    2 => [
        'name' => 'STL',
        'full_name' => 'St. Louis (VIP)',
        'ip' => '144.126.133.253',
        'port' => 51820,
        'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
        'network' => '10.0.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'vip_only' => true,
        'vip_email' => 'seige235@yahoo.com'
    ],
    3 => [
        'name' => 'TX',
        'full_name' => 'Dallas',
        'ip' => '66.241.124.4',
        'port' => 51820,
        'public_key' => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
        'network' => '10.10.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'vip_only' => false
    ],
    4 => [
        'name' => 'CAN',
        'full_name' => 'Toronto',
        'ip' => '66.241.125.247',
        'port' => 51820,
        'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
        'network' => '10.10.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'vip_only' => false
    ]
];

if (!isset($servers[$serverId])) {
    Response::error('Invalid server', 404);
}

$server = $servers[$serverId];

// Check VIP access
if ($server['vip_only']) {
    if (strtolower($user['email']) !== strtolower($server['vip_email'])) {
        Response::error('This is a VIP-only server', 403);
    }
}

// Check subscription
$subscription = Database::queryOne('billing',
    "SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'",
    [$user['id']]
);

// Allow VIPs without checking subscription
if (!$subscription && !VIPManager::isVIP($user['email'])) {
    Response::error('Active subscription required', 403);
}

// Get or create user's WireGuard keys
$userKey = Database::queryOne('certificates',
    "SELECT * FROM user_certificates WHERE user_id = ? AND type = 'wireguard' AND status = 'active'",
    [$user['id']]
);

if (!$userKey) {
    // Generate new keypair
    $privateKey = generateWireGuardPrivateKey();
    $publicKey = generateWireGuardPublicKey($privateKey);
    
    Database::execute('certificates',
        "INSERT INTO user_certificates (user_id, name, type, public_key, private_key, status, created_at)
         VALUES (?, 'WireGuard Key', 'wireguard', ?, ?, 'active', datetime('now'))",
        [$user['id'], $publicKey, $privateKey]
    );
    
    $userKey = [
        'private_key' => $privateKey,
        'public_key' => $publicKey
    ];
}

// Get assigned IP for this server
$peer = Database::queryOne('vpn',
    "SELECT assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
    [$user['id'], $serverId]
);

if ($peer && $peer['assigned_ip']) {
    $assignedIp = $peer['assigned_ip'];
} else {
    // Assign new IP
    $assignedIp = assignVpnIp($user['id'], $serverId, $server['network']);
    
    // Record peer (will be provisioned when they connect)
    Database::execute('vpn',
        "INSERT OR REPLACE INTO user_peers (user_id, server_id, public_key, assigned_ip, status, created_at)
         VALUES (?, ?, ?, ?, 'pending', datetime('now'))",
        [$user['id'], $serverId, $userKey['public_key'], $assignedIp]
    );
}

// Generate config
$configName = "TrueVault{$server['name']}";
$config = generateConfig($server, $userKey, $assignedIp, $configName);

// Check if download requested
if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"{$configName}.conf\"");
    header('Content-Length: ' . strlen($config));
    echo $config;
    exit;
}

// Return as JSON
Response::success([
    'server' => [
        'id' => $serverId,
        'name' => $server['name'],
        'full_name' => $server['full_name'],
        'ip' => $server['ip']
    ],
    'config_name' => "{$configName}.conf",
    'config' => $config,
    'assigned_ip' => $assignedIp,
    'public_key' => $userKey['public_key'],
    'download_url' => "/api/vpn/config.php?server_id={$serverId}&download=1"
], 'Configuration generated');

/**
 * Generate WireGuard configuration file
 */
function generateConfig($server, $userKey, $assignedIp, $configName) {
    $config = <<<CONFIG
[Interface]
# {$configName} - TrueVault VPN
# Server: {$server['full_name']}
# Generated: {$_SERVER['REQUEST_TIME']}
PrivateKey = {$userKey['private_key']}
Address = {$assignedIp}/32
DNS = {$server['dns']}

[Peer]
# TrueVault {$server['full_name']} Server
PublicKey = {$server['public_key']}
Endpoint = {$server['ip']}:{$server['port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
CONFIG;

    return $config;
}

/**
 * Assign VPN IP address
 */
function assignVpnIp($userId, $serverId, $network) {
    // Check existing assignments on this server
    $existing = Database::queryAll('vpn',
        "SELECT assigned_ip FROM user_peers WHERE server_id = ? AND assigned_ip LIKE ?",
        [$serverId, "{$network}.%"]
    );
    
    $usedIps = [];
    foreach ($existing as $row) {
        $parts = explode('.', $row['assigned_ip']);
        if (count($parts) === 4) {
            $usedIps[] = (int) $parts[3];
        }
    }
    
    // Find next available (2-254)
    for ($i = 2; $i <= 254; $i++) {
        if (!in_array($i, $usedIps)) {
            return "{$network}.{$i}";
        }
    }
    
    // Fallback based on user ID
    $lastOctet = ($userId % 253) + 2;
    return "{$network}.{$lastOctet}";
}

/**
 * Generate WireGuard private key
 */
function generateWireGuardPrivateKey() {
    $bytes = random_bytes(32);
    $bytes[0] = chr(ord($bytes[0]) & 248);
    $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
    return base64_encode($bytes);
}

/**
 * Generate WireGuard public key from private key
 */
function generateWireGuardPublicKey($privateKey) {
    if (function_exists('sodium_crypto_scalarmult_base')) {
        $privateBytes = base64_decode($privateKey);
        $publicBytes = sodium_crypto_scalarmult_base($privateBytes);
        return base64_encode($publicBytes);
    }
    
    // Fallback
    $hash = hash('sha256', base64_decode($privateKey), true);
    return base64_encode($hash);
}
