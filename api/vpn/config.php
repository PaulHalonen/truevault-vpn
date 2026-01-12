<?php
/**
 * TrueVault VPN - WireGuard Config Generator
 * GET /api/vpn/config.php?server_id=1
 * 
 * Generates proper WireGuard config files with naming:
 * - TrueVaultNY.conf
 * - TrueVaultTX.conf
 * - TrueVaultCAN.conf
 * - TrueVaultSTL.conf (VIP only)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

// Server configurations with REAL public keys
$SERVER_CONFIG = [
    1 => [
        'id' => 1,
        'name' => 'NY',
        'full_name' => 'New York',
        'ip' => '66.94.103.91',
        'port' => 51820,
        'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
        'network' => '10.0.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming', 'gaming', 'cameras', 'torrents'],
        'filename' => 'TrueVaultNY.conf'
    ],
    2 => [
        'id' => 2,
        'name' => 'STL',
        'full_name' => 'St. Louis (VIP)',
        'ip' => '144.126.133.253',
        'port' => 51820,
        'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
        'network' => '10.0.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'vip_only' => 'seige235@yahoo.com',
        'allowed' => ['everything'],
        'filename' => 'TrueVaultSTL.conf'
    ],
    3 => [
        'id' => 3,
        'name' => 'TX',
        'full_name' => 'Dallas',
        'ip' => '66.241.124.4',
        'port' => 51820,
        'public_key' => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
        'network' => '10.10.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming'],
        'bandwidth_limited' => true,
        'filename' => 'TrueVaultTX.conf'
    ],
    4 => [
        'id' => 4,
        'name' => 'CAN',
        'full_name' => 'Toronto',
        'ip' => '66.241.125.247',
        'port' => 51820,
        'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
        'network' => '10.10.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming'],
        'bandwidth_limited' => true,
        'filename' => 'TrueVaultCAN.conf'
    ]
];

// Require authentication
$user = Auth::requireAuth();

// Only GET
Response::requireMethod('GET');

$serverId = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$serverId || !isset($SERVER_CONFIG[$serverId])) {
    Response::error('Invalid server ID', 400);
}

$server = $SERVER_CONFIG[$serverId];

try {
    // Check VIP server access
    if (isset($server['vip_only'])) {
        if (strtolower($user['email']) !== strtolower($server['vip_only'])) {
            Response::error('This is a VIP-exclusive server', 403);
        }
    }
    
    // Check subscription status (skip for VIPs)
    if (!VIPManager::isVIP($user['email'])) {
        $subscription = BillingManager::getCurrentSubscription($user['id']);
        if (!$subscription || $subscription['status'] !== 'active') {
            Response::error('Active subscription required', 403);
        }
    }
    
    // Get or create user's WireGuard keypair
    $userKey = PeerManager::getOrCreateUserKey($user['id']);
    
    // Get user's assigned IP for this server
    $peerRecord = Database::queryOne('vpn',
        "SELECT assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
        [$user['id'], $serverId]
    );
    
    $assignedIp = $peerRecord['assigned_ip'] ?? null;
    
    // If no assigned IP, provision the user
    if (!$assignedIp) {
        // Calculate IP based on user_id
        $lastOctet = ($user['id'] % 250) + 2;
        $assignedIp = "{$server['network']}.{$lastOctet}";
        
        // The peer will be added when they actually connect
        // For now, just generate the config
    }
    
    // Generate WireGuard config
    $config = generateConfig($server, $userKey, $assignedIp, $user);
    
    // Log config generation
    Database::execute('logs',
        "INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
         VALUES (?, 'config_generated', ?, ?, datetime('now'))",
        [$user['id'], json_encode(['server' => $server['name']]), $_SERVER['REMOTE_ADDR'] ?? null]
    );
    
    Response::success([
        'server' => [
            'id' => $server['id'],
            'name' => $server['name'],
            'full_name' => $server['full_name'],
            'location' => $server['full_name'],
            'ip' => $server['ip'],
            'allowed' => $server['allowed'],
            'bandwidth_limited' => $server['bandwidth_limited'] ?? false
        ],
        'filename' => $server['filename'],
        'config' => $config,
        'assigned_ip' => $assignedIp,
        'instructions' => getInstructions($server)
    ], 'Config generated');
    
} catch (Exception $e) {
    Response::serverError('Failed to generate config: ' . $e->getMessage());
}

/**
 * Generate WireGuard configuration file content
 */
function generateConfig($server, $userKey, $assignedIp, $user) {
    $date = date('Y-m-d H:i:s');
    $allowed = implode(', ', $server['allowed']);
    
    $config = <<<CONFIG
# ============================================
# TrueVault VPN - {$server['full_name']}
# ============================================
# File: {$server['filename']}
# Generated: {$date}
# User: {$user['email']}
# Allowed: {$allowed}
# ============================================

[Interface]
PrivateKey = {$userKey['private_key']}
Address = {$assignedIp}/24
DNS = {$server['dns']}

[Peer]
# TrueVault {$server['name']} Server
PublicKey = {$server['public_key']}
Endpoint = {$server['ip']}:{$server['port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
CONFIG;

    return $config;
}

/**
 * Get usage instructions for server
 */
function getInstructions($server) {
    $instructions = [
        'NY' => [
            'title' => 'RECOMMENDED FOR HOME USE',
            'description' => 'Use for all home devices, gaming, cameras, streaming, and torrents.',
            'rules' => [
                '✓ Xbox/PlayStation gaming',
                '✓ IP Cameras (all plans)',
                '✓ Netflix/streaming',
                '✓ Torrents allowed',
                '✓ Unlimited bandwidth'
            ]
        ],
        'STL' => [
            'title' => 'YOUR PRIVATE DEDICATED SERVER',
            'description' => 'This server is exclusively yours. Only your devices can connect.',
            'rules' => [
                '✓ Everything allowed',
                '✓ Unlimited bandwidth',
                '✓ Port forwarding available',
                '✓ Static IP address',
                '✓ Terminal access available'
            ]
        ],
        'TX' => [
            'title' => 'STREAMING ONLY - LIMITED BANDWIDTH',
            'description' => 'Optimized for Netflix and streaming. Not for gaming or cameras.',
            'rules' => [
                '✓ Netflix/streaming (not flagged)',
                '✗ NO gaming (high latency)',
                '✗ NO torrents',
                '✗ NO IP cameras',
                '⚠ Limited bandwidth - streaming only'
            ]
        ],
        'CAN' => [
            'title' => 'CANADIAN STREAMING - LIMITED BANDWIDTH',
            'description' => 'Access Canadian Netflix and streaming content.',
            'rules' => [
                '✓ Canadian Netflix/streaming',
                '✓ Canadian IP address',
                '✗ NO gaming',
                '✗ NO torrents',
                '✗ NO IP cameras',
                '⚠ Limited bandwidth - streaming only'
            ]
        ]
    ];
    
    return $instructions[$server['name']] ?? [
        'title' => 'VPN Server',
        'description' => 'Connect to this server for VPN access.',
        'rules' => []
    ];
}
