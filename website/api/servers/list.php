<?php
/**
 * TrueVault VPN - List Servers API
 * 
 * Returns available VPN servers for user
 * Handles VIP vs public server assignment
 * 
 * @method GET
 * @auth Required (JWT)
 * @returns JSON array of servers
 */

define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/ServerManager.php';

// Check authentication
$user = Auth::getUserFromToken();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Check if user is VIP
    $isVIP = isset($user['is_vip']) && $user['is_vip'] == 1;
    
    // Get servers for this user
    if ($isVIP) {
        // VIP gets all servers including dedicated
        $servers = ServerManager::getAllServers(true);
        
        // Filter to show only their dedicated server + public servers
        $filteredServers = array_filter($servers, function($server) use ($user) {
            if ($server['access_level'] === 'vip') {
                return $server['vip_email'] === $user['email'];
            }
            return true;
        });
        $servers = array_values($filteredServers);
    } else {
        // Regular users only see public servers
        $servers = ServerManager::getAllServers(false);
    }
    
    // Format server data for client
    $result = [];
    foreach ($servers as $server) {
        $stats = ServerManager::getServerStats($server['id']);
        
        $result[] = [
            'id' => $server['id'],
            'name' => $server['name'],
            'location' => $server['location'],
            'country_code' => $server['country_code'],
            'ip_address' => $server['ip_address'],
            'port' => $server['port'],
            'endpoint' => $server['endpoint'],
            'streaming_optimized' => $server['streaming_optimized'] == 1,
            'port_forwarding' => $server['port_forwarding'] == 1,
            'is_vip' => $server['access_level'] === 'vip',
            'health_status' => $server['health_status'] ?? 'unknown',
            'load_percentage' => $stats['load_percentage'],
            'uptime' => $server['uptime_percentage'] ?? 99.9,
            'current_users' => $server['current_users'],
            'max_users' => $server['max_users']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'servers' => $result,
        'count' => count($result),
        'user_is_vip' => $isVIP
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve servers',
        'details' => $e->getMessage()
    ]);
}
?>
