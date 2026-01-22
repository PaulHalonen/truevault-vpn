<?php
/**
 * List Available VPN Servers API
 * 
 * VISIBILITY RULES:
 * 1. Dedicated servers (dedicated_user_email set) - ONLY visible to that email
 * 2. Shared servers - visible to all authenticated users
 * 
 * St. Louis (144.126.133.253) is DEDICATED to seige235@yahoo.com ONLY!
 * No other users (including VIPs) can see it.
 * 
 * @created January 22, 2026
 * @version 2.1.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    $payload = JWT::requireAuth();
    $userEmail = strtolower(trim($payload['email']));
    $userTier = $payload['tier'];
    
    $serversDb = Database::getInstance('servers');
    
    // Get all active servers
    $result = $serversDb->query("
        SELECT id, name, location, country_code, ip_address, 
               streaming_optimized, current_clients, max_clients, load_percentage,
               port_forwarding_allowed, high_bandwidth_allowed,
               dedicated_user_email
        FROM servers 
        WHERE status = 'active'
        ORDER BY name
    ");
    
    $servers = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $dedicatedEmail = $row['dedicated_user_email'] 
            ? strtolower(trim($row['dedicated_user_email'])) 
            : null;
        
        // DEDICATED SERVER CHECK
        // If server has dedicated_user_email, ONLY that user can see it
        if ($dedicatedEmail && $userEmail !== $dedicatedEmail) {
            continue; // Skip - user is not the dedicated owner
        }
        
        // Remove internal fields before returning
        unset($row['dedicated_user_email']);
        
        // Add load status
        $load = $row['load_percentage'] ?? 0;
        $row['load_status'] = $load < 30 ? 'low' : ($load < 70 ? 'medium' : 'high');
        
        // Add feature flags for UI
        $row['can_port_forward'] = (bool)$row['port_forwarding_allowed'];
        $row['can_gaming'] = (bool)$row['high_bandwidth_allowed'];
        $row['can_torrent'] = (bool)$row['high_bandwidth_allowed'];
        
        $servers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'servers' => $servers,
        'count' => count($servers)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
