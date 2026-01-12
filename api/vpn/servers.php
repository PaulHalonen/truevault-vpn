<?php
/**
 * TrueVault VPN - VPN Servers List
 * GET /api/vpn/servers.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow GET
Response::requireMethod('GET');

// Require authentication
$user = Auth::requireAuth();

try {
    $db = DatabaseManager::getInstance()->servers();
    
    // Get server ID if specific server requested
    $serverId = $_GET['id'] ?? null;
    
    if ($serverId) {
        // Get single server
        $stmt = $db->prepare("SELECT * FROM vpn_servers WHERE id = ?");
        $stmt->execute([$serverId]);
        $server = $stmt->fetch();
        
        if (!$server) {
            Response::notFound('Server not found');
        }
        
        // Check if VIP-only server
        if ($server['is_vip_only'] && !Auth::isVipUser()) {
            Response::forbidden('This server is reserved for VIP users');
        }
        
        // Don't expose VIP user email
        unset($server['vip_user_email']);
        unset($server['api_secret']);
        
        Response::success(['server' => $server]);
    } else {
        // Get all servers
        $isVip = Auth::isVipUser();
        $userEmail = $user['email'];
        
        if ($isVip) {
            // VIP users see all servers
            $stmt = $db->query("
                SELECT * FROM vpn_servers 
                WHERE status != 'offline'
                ORDER BY region, server_name
            ");
        } else {
            // Regular users don't see VIP-only servers
            $stmt = $db->query("
                SELECT * FROM vpn_servers 
                WHERE status != 'offline' 
                AND is_vip_only = 0
                ORDER BY region, server_name
            ");
        }
        
        $servers = $stmt->fetchAll();
        
        // Clean up sensitive data and add user-specific info
        foreach ($servers as &$server) {
            // Check if this is the user's VIP server
            $server['is_user_vip_server'] = ($server['vip_user_email'] === $userEmail);
            
            // Remove sensitive data
            unset($server['vip_user_email']);
            unset($server['api_secret']);
            
            // Calculate status indicator
            $server['status_color'] = match($server['status']) {
                'active' => 'green',
                'maintenance' => 'yellow',
                'full' => 'orange',
                default => 'red'
            };
            
            // Calculate load color
            $load = (int) $server['load_percent'];
            $server['load_color'] = match(true) {
                $load < 50 => 'green',
                $load < 80 => 'yellow',
                default => 'red'
            };
        }
        
        // Group by region
        $grouped = [];
        foreach ($servers as $server) {
            $region = $server['region'];
            if (!isset($grouped[$region])) {
                $grouped[$region] = [];
            }
            $grouped[$region][] = $server;
        }
        
        Response::success([
            'servers' => $servers,
            'grouped' => $grouped,
            'count' => count($servers)
        ]);
    }
    
} catch (Exception $e) {
    Logger::error('Server list failed: ' . $e->getMessage());
    Response::serverError('Failed to get servers');
}
