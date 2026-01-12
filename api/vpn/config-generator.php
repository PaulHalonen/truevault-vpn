<?php
/**
 * TrueVault VPN - WireGuard Config Generator
 * Generates properly named configuration files for each server
 * 
 * Config Names:
 * - TrueVaultNY.conf  (New York - 66.94.103.91)
 * - TrueVaultTX.conf  (Dallas - 66.241.124.4)
 * - TrueVaultCAN.conf (Toronto - 66.241.125.247)
 * - TrueVaultSTL.conf (St. Louis VIP - 144.126.133.253)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$user = Auth::requireAuth();

$action = $_GET['action'] ?? 'list';

// Server configurations with real public keys
$SERVERS = [
    1 => [
        'id' => 1,
        'name' => 'New York',
        'code' => 'NY',
        'config_name' => 'TrueVaultNY',
        'ip' => '66.94.103.91',
        'port' => 51820,
        'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
        'network' => '10.0.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming', 'gaming', 'torrents', 'cameras'],
        'type' => 'shared',
        'location_flag' => '🇺🇸'
    ],
    2 => [
        'id' => 2,
        'name' => 'St. Louis',
        'code' => 'STL',
        'config_name' => 'TrueVaultSTL',
        'ip' => '144.126.133.253',
        'port' => 51820,
        'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
        'network' => '10.0.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['all'],
        'type' => 'dedicated',
        'vip_only' => 'seige235@yahoo.com',
        'location_flag' => '🇺🇸'
    ],
    3 => [
        'id' => 3,
        'name' => 'Dallas',
        'code' => 'TX',
        'config_name' => 'TrueVaultTX',
        'ip' => '66.241.124.4',
        'port' => 51820,
        'public_key' => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
        'network' => '10.10.1',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming'],
        'restricted' => ['torrents', 'gaming', 'cameras'],
        'type' => 'shared',
        'bandwidth' => 'limited',
        'location_flag' => '🇺🇸'
    ],
    4 => [
        'id' => 4,
        'name' => 'Toronto',
        'code' => 'CAN',
        'config_name' => 'TrueVaultCAN',
        'ip' => '66.241.125.247',
        'port' => 51820,
        'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
        'network' => '10.10.0',
        'dns' => '1.1.1.1, 8.8.8.8',
        'allowed' => ['streaming'],
        'restricted' => ['torrents', 'gaming', 'cameras'],
        'type' => 'shared',
        'bandwidth' => 'limited',
        'location_flag' => '🇨🇦'
    ]
];

switch ($action) {
    
    case 'list':
        // List available servers for this user
        $availableServers = [];
        
        foreach ($SERVERS as $server) {
            // Check VIP-only servers
            if (isset($server['vip_only'])) {
                if (strtolower($user['email']) !== strtolower($server['vip_only'])) {
                    continue; // Skip - not authorized
                }
            }
            
            // Don't expose private key in list
            $serverInfo = $server;
            unset($serverInfo['network']);
            
            $availableServers[] = $serverInfo;
        }
        
        Response::success(['servers' => $availableServers]);
        break;
        
    case 'generate':
        Response::requireMethod('POST');
        $input = Response::getJsonInput();
        
        if (empty($input['server_id'])) {
            Response::error('Server ID required', 400);
        }
        
        $serverId = (int) $input['server_id'];
        
        if (!isset($SERVERS[$serverId])) {
            Response::error('Invalid server', 404);
        }
        
        $server = $SERVERS[$serverId];
        
        // Check VIP access
        if (isset($server['vip_only'])) {
            if (strtolower($user['email']) !== strtolower($server['vip_only'])) {
                Response::error('This server is VIP-only', 403);
            }
        }
        
        // Check subscription (VIPs bypass)
        if (!VIPManager::isVIP($user['email'])) {
            $subscription = BillingManager::getCurrentSubscription($user['id']);
            if (!$subscription || $subscription['status'] !== 'active') {
                Response::error('Active subscription required', 402);
            }
        }
        
        // Get or create user's WireGuard keys
        $userKey = PeerManager::getOrCreateUserKey($user['id']);
        
        // Get assigned IP for this server
        $existingPeer = Database::queryOne('vpn',
            "SELECT assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
            [$user['id'], $serverId]
        );
        
        $assignedIp = $existingPeer ? $existingPeer['assigned_ip'] : null;
        
        if (!$assignedIp) {
            // Calculate IP based on user_id
            $lastOctet = ($user['id'] % 250) + 2;
            $assignedIp = "{$server['network']}.{$lastOctet}";
        }
        
        // Generate config
        $config = generateWireGuardConfig($server, $userKey, $assignedIp, $user);
        
        // Log
        Database::execute('logs',
            "INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
             VALUES (?, 'config_generated', ?, ?, datetime('now'))",
            [$user['id'], json_encode(['server' => $server['name'], 'config' => $server['config_name']]), $_SERVER['REMOTE_ADDR'] ?? null]
        );
        
        Response::success([
            'config_name' => $server['config_name'] . '.conf',
            'config' => $config,
            'server' => [
                'name' => $server['name'],
                'location' => $server['location_flag'] . ' ' . $server['name'],
                'ip' => $server['ip'],
                'type' => $server['type']
            ],
            'assigned_ip' => $assignedIp,
            'instructions' => getServerInstructions($server)
        ]);
        break;
        
    case 'download':
        // Download config as file
        Response::requireMethod('GET');
        
        $serverId = (int) ($_GET['server_id'] ?? 0);
        
        if (!isset($SERVERS[$serverId])) {
            Response::error('Invalid server', 404);
        }
        
        $server = $SERVERS[$serverId];
        
        // Check VIP access
        if (isset($server['vip_only'])) {
            if (strtolower($user['email']) !== strtolower($server['vip_only'])) {
                Response::error('This server is VIP-only', 403);
            }
        }
        
        // Get user's keys
        $userKey = PeerManager::getOrCreateUserKey($user['id']);
        
        // Get assigned IP
        $existingPeer = Database::queryOne('vpn',
            "SELECT assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ? AND status = 'active'",
            [$user['id'], $serverId]
        );
        
        $assignedIp = $existingPeer ? $existingPeer['assigned_ip'] : "{$server['network']}." . (($user['id'] % 250) + 2);
        
        // Generate config
        $config = generateWireGuardConfig($server, $userKey, $assignedIp, $user);
        
        // Send as file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $server['config_name'] . '.conf"');
        header('Content-Length: ' . strlen($config));
        echo $config;
        exit;
        
    default:
        Response::error('Invalid action', 400);
}

/**
 * Generate WireGuard configuration file
 */
function generateWireGuardConfig($server, $userKey, $assignedIp, $user) {
    $timestamp = date('Y-m-d H:i:s');
    
    $config = <<<CONFIG
[Interface]
# TrueVault VPN - {$server['name']}
# Config: {$server['config_name']}.conf
# User: {$user['email']}
# Generated: {$timestamp}
#
# IMPORTANT: Keep this file private!
# Your private key should never be shared.

PrivateKey = {$userKey['private_key']}
Address = {$assignedIp}/24
DNS = {$server['dns']}

[Peer]
# TrueVault {$server['name']} Server
# Location: {$server['location_flag']} {$server['name']}
# Type: {$server['type']}

PublicKey = {$server['public_key']}
Endpoint = {$server['ip']}:{$server['port']}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 25
CONFIG;

    return $config;
}

/**
 * Get usage instructions for a server
 */
function getServerInstructions($server) {
    $instructions = [];
    
    switch ($server['code']) {
        case 'NY':
            $instructions = [
                'title' => '🏠 RECOMMENDED FOR HOME USE',
                'description' => 'Full-featured server for all your home devices',
                'allowed' => ['✓ Xbox/PlayStation gaming', '✓ Torrents/P2P', '✓ IP Cameras', '✓ Netflix/Streaming', '✓ All traffic'],
                'note' => 'Best for: Gaming, cameras, and general home use'
            ];
            break;
            
        case 'STL':
            $instructions = [
                'title' => '👑 YOUR PRIVATE SERVER',
                'description' => 'Dedicated server exclusively for you',
                'allowed' => ['✓ Unlimited bandwidth', '✓ All services', '✓ Port forwarding', '✓ Static IP'],
                'note' => 'Only you can connect to this server'
            ];
            break;
            
        case 'TX':
            $instructions = [
                'title' => '📺 STREAMING ONLY',
                'description' => 'Optimized for Netflix and streaming services',
                'allowed' => ['✓ Netflix (not flagged as VPN)', '✓ Hulu, Disney+, etc.', '✓ YouTube'],
                'restricted' => ['✗ NO gaming', '✗ NO torrents', '✗ NO cameras'],
                'note' => 'Limited bandwidth - streaming traffic only'
            ];
            break;
            
        case 'CAN':
            $instructions = [
                'title' => '🇨🇦 CANADIAN STREAMING',
                'description' => 'Access Canadian content libraries',
                'allowed' => ['✓ Canadian Netflix', '✓ CBC, CTV streaming', '✓ Canadian services'],
                'restricted' => ['✗ NO gaming', '✗ NO torrents', '✗ NO cameras'],
                'note' => 'Limited bandwidth - Canadian streaming only'
            ];
            break;
    }
    
    return $instructions;
}
