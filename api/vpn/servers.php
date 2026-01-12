<?php
/**
 * TrueVault VPN - Servers API
 * GET /api/vpn/servers.php - List all available VPN servers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

// Optional auth - some info available without login
$user = Auth::optionalAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Get all servers
        $servers = Database::query('vpn', "SELECT * FROM vpn_servers ORDER BY is_vip ASC, name ASC");
        
        // Filter VIP servers for non-VIP users
        $filteredServers = [];
        foreach ($servers as $server) {
            // If it's a VIP server
            if ($server['is_vip'] == 1) {
                // Only show to the VIP user it belongs to
                if ($user && $server['vip_user_email'] === $user['email']) {
                    $filteredServers[] = $server;
                }
                // Skip for other users
                continue;
            }
            
            // Regular servers are visible to all
            $filteredServers[] = $server;
        }
        
        // Add connection count for each server (simulated for now)
        foreach ($filteredServers as &$server) {
            // Get active connections count
            $connections = Database::queryOne('vpn', 
                "SELECT COUNT(*) as count FROM vpn_connections WHERE server_id = ? AND status = 'connected'",
                [$server['id']]
            );
            $server['active_connections'] = $connections ? (int)$connections['count'] : 0;
            
            // Calculate load percentage
            $maxConnections = $server['max_connections'] ?: 50;
            $server['current_load'] = min(100, round(($server['active_connections'] / $maxConnections) * 100));
        }
        
        Response::success([
            'servers' => $filteredServers,
            'count' => count($filteredServers)
        ]);
        
    } catch (Exception $e) {
        Response::serverError('Failed to load servers: ' . $e->getMessage());
    }
} else {
    Response::error('Method not allowed', 405);
}
