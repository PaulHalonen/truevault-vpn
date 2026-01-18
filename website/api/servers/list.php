<?php
/**
 * TrueVault VPN - Servers List API
 * 
 * PURPOSE: Return list of available VPN servers
 * METHOD: GET
 * ENDPOINT: /api/servers/list.php
 * AUTHENTICATION: Required (JWT)
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "servers": [
 *     {
 *       "id": 1,
 *       "name": "USA (Dallas)",
 *       "country": "usa",
 *       "region": "Dallas",
 *       "endpoint": "66.241.124.4:51820",
 *       "status": "online",
 *       "load": 45
 *     }
 *   ]
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration and dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

try {
    // ============================================
    // AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    
    // ============================================
    // QUERY SERVERS DATABASE
    // ============================================
    
    $db = Database::getInstance();
    $conn = $db->getConnection('servers');
    
    // Query all servers with their current status
    $query = "
        SELECT 
            id,
            name,
            country,
            region,
            endpoint,
            status,
            current_load,
            max_users,
            is_dedicated,
            dedicated_user_email
        FROM servers
        WHERE status IN ('online', 'maintenance')
        ORDER BY 
            CASE 
                WHEN status = 'online' THEN 0
                WHEN status = 'maintenance' THEN 1
                ELSE 2
            END,
            current_load ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // FORMAT RESPONSE
    // ============================================
    
    $formattedServers = [];
    
    foreach ($servers as $server) {
        // Calculate load percentage
        $loadPercentage = 0;
        if ($server['max_users'] > 0) {
            $loadPercentage = round(($server['current_load'] / $server['max_users']) * 100);
        }
        
        // Check if dedicated server is for current user
        $isAvailableToUser = true;
        if ($server['is_dedicated'] == 1) {
            // Dedicated servers only available to specific user
            if ($server['dedicated_user_email'] !== $user['email']) {
                // Skip this server - not for this user
                continue;
            }
        }
        
        // Format server data
        $formattedServers[] = [
            'id' => (int)$server['id'],
            'name' => $server['name'],
            'country' => strtolower($server['country']),
            'region' => $server['region'],
            'endpoint' => $server['endpoint'],
            'status' => $server['status'],
            'load' => $loadPercentage,
            'is_dedicated' => (bool)$server['is_dedicated']
        ];
    }
    
    // ============================================
    // RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'servers' => $formattedServers,
        'count' => count($formattedServers)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Servers List API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load servers',
        'message' => $e->getMessage()
    ]);
}
