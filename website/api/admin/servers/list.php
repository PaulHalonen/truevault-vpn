<?php
/**
 * TrueVault VPN - Admin Server List API
 * 
 * PURPOSE: Returns all servers for admin management
 * METHOD: GET
 * AUTHENTICATION: JWT (admin or vip tier required)
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "servers": [
 *     {
 *       "server_id": 1,
 *       "name": "USA (Dallas)",
 *       "country": "United States",
 *       "country_code": "US",
 *       "region": "Texas",
 *       "endpoint": "66.241.124.4:51820",
 *       "provider": "fly.io",
 *       "public_key": "...",
 *       "max_users": 50,
 *       "current_load": 12,
 *       "status": "online",
 *       "is_dedicated": 0,
 *       "created_at": "2026-01-15 10:00:00"
 *     }
 *   ],
 *   "count": 4
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../../configs/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    // Check admin access
    if (!in_array($userTier, ['admin', 'vip'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied. Admin or VIP access required.'
        ]);
        exit;
    }
    
    // Get database connection
    $db = Database::getInstance();
    $serversConn = $db->getConnection('servers');
    $devicesConn = $db->getConnection('devices');
    
    // Get all servers
    $stmt = $serversConn->prepare("
        SELECT server_id, name, country, country_code, region, endpoint, 
               provider, public_key, max_users, status, is_dedicated, 
               ip_pool_start, ip_pool_end, ip_pool_current, created_at
        FROM servers
        ORDER BY name ASC
    ");
    $stmt->execute();
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current load for each server
    foreach ($servers as &$server) {
        $stmt = $devicesConn->prepare("
            SELECT COUNT(*) as count
            FROM devices
            WHERE current_server_id = ? AND status = 'active'
        ");
        $stmt->execute([$server['server_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $server['current_load'] = (int)$result['count'];
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'servers' => $servers,
        'count' => count($servers)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Admin Server List Error: ' . $e->getMessage());
}
