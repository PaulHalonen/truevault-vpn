<?php
/**
 * TrueVault VPN - Servers API
 * GET /api/vpn/servers.php - List available servers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

$user = Auth::requireAuth();

// Complete server definitions with rules
$allServers = [
    1 => [
        'id' => 1,
        'name' => 'TrueVaultNY',
        'display_name' => 'New York',
        'location' => 'New York, USA',
        'country' => 'US',
        'ip' => '66.94.103.91',
        'port' => 51820,
        'public_key' => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',
        'type' => 'shared',
        'bandwidth' => 'unlimited',
        'vip_only' => false,
        'rules' => [
            'title' => 'RECOMMENDED FOR HOME USE',
            'description' => 'Use for all home devices including gaming consoles, IP cameras, and streaming.',
            'allowed' => [
                '✓ Xbox/PlayStation Gaming',
                '✓ Torrents/P2P',
                '✓ IP Cameras (all plans)',
                '✓ Netflix/Streaming',
                '✓ General browsing'
            ],
            'not_allowed' => []
        ],
        'icon' => '🗽',
        'status' => 'online'
    ],
    2 => [
        'id' => 2,
        'name' => 'TrueVaultSTL',
        'display_name' => 'St. Louis (VIP)',
        'location' => 'St. Louis, USA',
        'country' => 'US',
        'ip' => '144.126.133.253',
        'port' => 51820,
        'public_key' => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',
        'type' => 'dedicated',
        'bandwidth' => 'unlimited',
        'vip_only' => 'seige235@yahoo.com',
        'rules' => [
            'title' => 'PRIVATE DEDICATED SERVER',
            'description' => 'Exclusively for VIP user. Unlimited bandwidth, no restrictions.',
            'allowed' => [
                '✓ Everything - No restrictions',
                '✓ Unlimited bandwidth',
                '✓ Static IP address'
            ],
            'not_allowed' => []
        ],
        'icon' => '👑',
        'status' => 'online'
    ],
    3 => [
        'id' => 3,
        'name' => 'TrueVaultTX',
        'display_name' => 'Dallas',
        'location' => 'Dallas, USA',
        'country' => 'US',
        'ip' => '66.241.124.4',
        'port' => 51820,
        'public_key' => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',
        'type' => 'shared',
        'bandwidth' => 'limited',
        'vip_only' => false,
        'rules' => [
            'title' => 'STREAMING ONLY',
            'description' => 'Optimized for Netflix and streaming services. This IP is NOT flagged by streaming services.',
            'allowed' => [
                '✓ Netflix',
                '✓ Hulu',
                '✓ Disney+',
                '✓ Amazon Prime Video'
            ],
            'not_allowed' => [
                '✗ Gaming (high latency)',
                '✗ Torrents/P2P (bandwidth)',
                '✗ IP Cameras (use NY instead)',
                '✗ Heavy downloads'
            ]
        ],
        'icon' => '🤠',
        'status' => 'online'
    ],
    4 => [
        'id' => 4,
        'name' => 'TrueVaultCAN',
        'display_name' => 'Toronto',
        'location' => 'Toronto, Canada',
        'country' => 'CA',
        'ip' => '66.241.125.247',
        'port' => 51820,
        'public_key' => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk=',
        'type' => 'shared',
        'bandwidth' => 'limited',
        'vip_only' => false,
        'rules' => [
            'title' => 'CANADIAN STREAMING',
            'description' => 'Access Canadian Netflix and streaming content. This IP is NOT flagged by streaming services.',
            'allowed' => [
                '✓ Canadian Netflix',
                '✓ CBC Gem',
                '✓ Crave',
                '✓ Canadian content'
            ],
            'not_allowed' => [
                '✗ Gaming (latency)',
                '✗ Torrents/P2P',
                '✗ IP Cameras (use NY instead)',
                '✗ Heavy downloads'
            ]
        ],
        'icon' => '🍁',
        'status' => 'online'
    ]
];

// Filter servers based on user access
$availableServers = [];
$userEmail = strtolower($user['email']);

foreach ($allServers as $id => $server) {
    // Check VIP-only servers
    if ($server['vip_only']) {
        if (strtolower($server['vip_only']) === $userEmail) {
            $server['access'] = 'exclusive';
            $availableServers[] = $server;
        }
        continue;
    }
    
    $server['access'] = 'available';
    $availableServers[] = $server;
}

// Check if user is VIP
$isVip = VIPManager::isVIP($user['email']);
$vipDetails = $isVip ? VIPManager::getVIPDetails($user['email']) : null;

Response::success([
    'servers' => $availableServers,
    'user' => [
        'email' => $user['email'],
        'is_vip' => $isVip,
        'vip_tier' => $vipDetails['tier'] ?? null,
        'has_dedicated' => $vipDetails['dedicated_server'] ?? false
    ],
    'recommendations' => [
        'gaming' => 1,
        'streaming_us' => 3,
        'streaming_ca' => 4,
        'cameras' => 1,
        'general' => 1
    ]
]);
