<?php
/**
 * TrueVault VPN - VIP Servers API
 * GET /api/vip/servers.php - Get available servers for VIP
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

Response::requireMethod('GET');

// Require authentication
$user = Auth::requireAuth();

try {
    $email = $user['email'];
    
    // Check VIP status
    $vipDetails = VIPManager::getVIPDetails($email);
    
    if (!$vipDetails) {
        Response::error('VIP access required', 403);
    }
    
    // Get available servers
    $servers = VIPManager::getAvailableServers($email);
    
    // Get upgrade options
    $upgradeOptions = VIPManager::getUpgradeOptions($email);
    
    Response::success([
        'vip' => $vipDetails,
        'servers' => $servers,
        'upgrade' => $upgradeOptions,
        'server_rules' => [
            'ny_server' => [
                'name' => 'New York (US-East)',
                'ip' => '66.94.103.91',
                'recommended_for' => ['Home devices', 'Xbox/Gaming', 'Torrents', 'High bandwidth streaming'],
                'allowed' => ['torrents', 'xbox', 'gaming', 'streaming', 'netflix'],
                'notes' => '✅ USE FOR HOME DEVICES - Full access, unlimited bandwidth'
            ],
            'dallas_server' => [
                'name' => 'Dallas (US-Central)',
                'ip' => '66.241.124.4',
                'recommended_for' => ['Netflix', 'Light streaming', 'Browsing'],
                'allowed' => ['streaming', 'netflix', 'browsing'],
                'blocked' => ['torrents', 'xbox', 'gaming'],
                'notes' => '⚠️ LIMITED BANDWIDTH - Netflix OK, NO torrents/Xbox'
            ],
            'canada_server' => [
                'name' => 'Toronto (Canada)',
                'ip' => '66.241.125.247',
                'recommended_for' => ['Netflix Canada', 'Canadian content', 'Light streaming'],
                'allowed' => ['streaming', 'netflix', 'browsing'],
                'blocked' => ['torrents', 'xbox', 'gaming'],
                'notes' => '⚠️ LIMITED BANDWIDTH - Netflix OK, NO torrents/Xbox'
            ]
        ],
        'usage_guide' => [
            'home_devices' => 'Connect home devices to NY server for best performance',
            'xbox_gaming' => 'ONLY use NY server for Xbox and gaming',
            'netflix_us' => 'Use NY or Dallas for US Netflix',
            'netflix_canada' => 'Use Canada server for Canadian Netflix library',
            'torrents' => 'ONLY use NY server for torrents - other servers will block',
            'high_bandwidth' => 'Use NY or your dedicated server for high bandwidth needs'
        ]
    ]);
    
} catch (Exception $e) {
    Response::serverError('Failed to get VIP servers: ' . $e->getMessage());
}
