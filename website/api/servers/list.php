<?php
/**
 * TrueVault VPN - List Servers API
 * 
 * PURPOSE: Get list of available servers for user
 * METHOD: GET
 * ENDPOINT: /api/servers/list.php
 * 
 * @created January 2026
 * @version 1.0.0
 */

// Init
define('TRUEVAULT_INIT', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Load dependencies
    require_once __DIR__ . '/../../configs/config.php';
    require_once __DIR__ . '/../../includes/Database.php';
    require_once __DIR__ . '/../../includes/Auth.php';
    
    // Initialize Auth
    Auth::init(JWT_SECRET);
    
    // Authenticate
    $user = Auth::requireAuth();
    $userEmail = $user['email'];
    $accountType = $user['account_type'];
    
    // Get servers based on user type
    $serversDb = Database::getInstance('servers');
    
    // VIP/Admin users see all servers
    // Standard users see non-VIP-only servers
    if ($accountType === 'vip' || $accountType === 'admin') {
        $servers = $serversDb->queryAll(
            "SELECT id, name, location, country, endpoint, status, current_clients, max_clients, vip_only, dedicated_user_email
             FROM servers 
             WHERE status = 'active'
             ORDER BY country, name"
        );
    } else {
        $servers = $serversDb->queryAll(
            "SELECT id, name, location, country, endpoint, status, current_clients, max_clients
             FROM servers 
             WHERE status = 'active' AND vip_only = 0
             ORDER BY country, name"
        );
    }
    
    // Format response
    $formatted = [];
    foreach ($servers as $server) {
        $loadPercent = $server['max_clients'] > 0 
            ? round(($server['current_clients'] / $server['max_clients']) * 100) 
            : 0;
        
        // Determine availability
        $available = true;
        $reason = '';
        
        // Check if dedicated to another user
        if (!empty($server['dedicated_user_email']) && strtolower($server['dedicated_user_email']) !== strtolower($userEmail)) {
            $available = false;
            $reason = 'Dedicated server';
        }
        
        // Check load
        if ($loadPercent >= 95) {
            $available = false;
            $reason = 'Server full';
        }
        
        $formatted[] = [
            'id' => $server['id'],
            'name' => $server['name'],
            'location' => $server['location'],
            'country' => $server['country'],
            'load' => $loadPercent,
            'load_label' => $loadPercent < 50 ? 'Low' : ($loadPercent < 80 ? 'Medium' : 'High'),
            'available' => $available,
            'reason' => $reason,
            'vip_only' => ($server['vip_only'] ?? 0) == 1,
            'is_dedicated' => !empty($server['dedicated_user_email'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'servers' => $formatted,
        'count' => count($formatted)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
