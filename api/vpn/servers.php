<?php
/**
 * TrueVault VPN - Get Available Servers
 * GET /api/vpn/servers.php
 * 
 * Returns list of servers available to the user based on their plan/VIP status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

// All server info
$ALL_SERVERS = [
    [
        'id' => 1,
        'name' => 'NY',
        'full_name' => 'New York',
        'location' => 'New York, USA',
        'country' => 'US',
        'ip' => '66.94.103.91',
        'port' => 51820,
        'type' => 'shared',
        'bandwidth' => 'unlimited',
        'allowed' => ['gaming', 'streaming', 'cameras', 'torrents'],
        'instructions' => 'RECOMMENDED FOR HOME USE - Gaming, cameras, streaming, torrents all allowed.',
        'icon' => '🗽',
        'vip_only' => false,
        'filename' => 'TrueVaultNY.conf'
    ],
    [
        'id' => 2,
        'name' => 'STL',
        'full_name' => 'St. Louis (VIP)',
        'location' => 'St. Louis, USA',
        'country' => 'US',
        'ip' => '144.126.133.253',
        'port' => 51820,
        'type' => 'dedicated',
        'bandwidth' => 'unlimited',
        'allowed' => ['everything'],
        'instructions' => 'YOUR PRIVATE SERVER - Only you can connect.',
        'icon' => '👑',
        'vip_only' => true,
        'vip_email' => 'seige235@yahoo.com',
        'filename' => 'TrueVaultSTL.conf'
    ],
    [
        'id' => 3,
        'name' => 'TX',
        'full_name' => 'Dallas',
        'location' => 'Dallas, USA',
        'country' => 'US',
        'ip' => '66.241.124.4',
        'port' => 51820,
        'type' => 'shared',
        'bandwidth' => 'limited',
        'allowed' => ['streaming'],
        'not_allowed' => ['gaming', 'torrents', 'cameras'],
        'instructions' => 'STREAMING ONLY - Netflix works, NO gaming/torrents/cameras.',
        'icon' => '🤠',
        'vip_only' => false,
        'filename' => 'TrueVaultTX.conf'
    ],
    [
        'id' => 4,
        'name' => 'CAN',
        'full_name' => 'Toronto',
        'location' => 'Toronto, Canada',
        'country' => 'CA',
        'ip' => '66.241.125.247',
        'port' => 51820,
        'type' => 'shared',
        'bandwidth' => 'limited',
        'allowed' => ['streaming'],
        'not_allowed' => ['gaming', 'torrents', 'cameras'],
        'instructions' => 'CANADIAN STREAMING - Canadian Netflix, NO gaming/torrents/cameras.',
        'icon' => '🍁',
        'vip_only' => false,
        'filename' => 'TrueVaultCAN.conf'
    ]
];

// Require authentication
$user = Auth::requireAuth();

Response::requireMethod('GET');

try {
    $isVIP = VIPManager::isVIP($user['email']);
    $vipDetails = $isVIP ? VIPManager::getVIPDetails($user['email']) : null;
    
    $availableServers = [];
    
    foreach ($ALL_SERVERS as $server) {
        // Check VIP-only servers
        if ($server['vip_only']) {
            // Only show to the specific VIP user
            if (isset($server['vip_email']) && strtolower($user['email']) === strtolower($server['vip_email'])) {
                $server['your_server'] = true;
                $availableServers[] = $server;
            }
            continue;
        }
        
        // Regular servers available to everyone with subscription
        $availableServers[] = $server;
    }
    
    // Get connection status for each server
    foreach ($availableServers as &$server) {
        $peer = Database::queryOne('vpn',
            "SELECT status, assigned_ip FROM user_peers WHERE user_id = ? AND server_id = ? ORDER BY created_at DESC LIMIT 1",
            [$user['id'], $server['id']]
        );
        
        $server['connection_status'] = $peer ? $peer['status'] : 'not_configured';
        $server['assigned_ip'] = $peer ? $peer['assigned_ip'] : null;
    }
    
    Response::success([
        'servers' => $availableServers,
        'is_vip' => $isVIP,
        'vip_tier' => $vipDetails ? $vipDetails['tier'] : null,
        'total_servers' => count($availableServers)
    ]);
    
} catch (Exception $e) {
    Response::serverError('Failed to get servers: ' . $e->getMessage());
}
