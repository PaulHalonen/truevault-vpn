<?php
/**
 * TrueVault VPN - Server List API
 * GET /api/servers/list.php
 * 
 * Returns available VPN servers from database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

// Only GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    // Check if user is VIP
    $isVIP = VIPManager::isVIP($user['email']);
    $vipDetails = $isVIP ? VIPManager::getVIPDetails($user['email']) : null;
    
    // Get all active servers from database
    $allServers = Database::query('servers',
        "SELECT * FROM vpn_servers WHERE status = 'active' ORDER BY id ASC"
    );
    
    $availableServers = [];
    $recommendedServerId = 1; // Default to NY
    
    foreach ($allServers as $server) {
        // Check if this is a VIP-only server
        $isVIPOnly = ($server['server_type'] === 'vip_dedicated');
        $isYourDedicated = false;
        
        if ($isVIPOnly) {
            // Only show VIP server to the assigned VIP user
            if (!$isVIP) {
                continue; // Skip this server for non-VIP users
            }
            
            // Check if this is YOUR dedicated server
            $vipEmail = strtolower($server['vip_user_email'] ?? '');
            $userEmail = strtolower($user['email']);
            
            if ($vipEmail && $vipEmail !== $userEmail) {
                continue; // This VIP server belongs to someone else
            }
            
            $isYourDedicated = true;
            $recommendedServerId = (int)$server['id']; // VIP server is recommended for VIP user
        }
        
        // Parse rules JSON
        $rulesAllowed = json_decode($server['rules_allowed'] ?? '[]', true) ?: [];
        $rulesNotAllowed = json_decode($server['rules_not_allowed'] ?? '[]', true) ?: [];
        
        $serverData = [
            'id' => (int)$server['id'],
            'name' => $server['display_name'],
            'display_name' => $server['display_name'],
            'country' => $server['country'],
            'country_code' => $server['country_code'],
            'flag' => $server['country_flag'],
            'country_flag' => $server['country_flag'],
            'ip_address' => $server['ip_address'],
            'port' => (int)$server['wireguard_port'],
            'public_key' => $server['public_key'],
            'status' => $server['status'],
            'load' => (int)$server['cpu_load'],
            'cpu_load' => (int)$server['cpu_load'],
            'latency' => (int)$server['latency_ms'],
            'latency_ms' => (int)$server['latency_ms'],
            'bandwidth_type' => $server['bandwidth_type'],
            'server_type' => $server['server_type'],
            'is_vip_only' => $isVIPOnly,
            'is_your_dedicated' => $isYourDedicated,
            'max_connections' => (int)$server['max_connections'],
            'current_connections' => (int)$server['current_connections'],
            'rules' => [
                'title' => $server['rules_title'],
                'description' => $server['rules_description'],
                'allowed' => $rulesAllowed,
                'not_allowed' => $rulesNotAllowed
            ]
        ];
        
        $availableServers[] = $serverData;
    }
    
    // Sort: VIP dedicated first (for VIP users), then by ID
    usort($availableServers, function($a, $b) {
        if ($a['is_your_dedicated'] && !$b['is_your_dedicated']) return -1;
        if (!$a['is_your_dedicated'] && $b['is_your_dedicated']) return 1;
        return $a['id'] - $b['id'];
    });
    
    Response::success([
        'servers' => $availableServers,
        'server_count' => count($availableServers),
        'recommended_server_id' => $recommendedServerId,
        'user' => [
            'email' => $user['email'],
            'is_vip' => $isVIP,
            'vip_tier' => $vipDetails['tier'] ?? null,
            'has_dedicated' => $vipDetails ? ($vipDetails['dedicated_server'] ?? false) : false
        ]
    ]);
    
} catch (Exception $e) {
    Response::serverError('Failed to load servers: ' . $e->getMessage());
}
