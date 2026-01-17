<?php
/**
 * TrueVault VPN - Server Status API
 * GET /api/servers/status.php
 * Returns status of all VPN servers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/Database.php';

try {
    $db = new Database('servers');
    
    // Get all active servers
    $servers = $db->query("
        SELECT id, name, location, country, ip, port, type, is_active, 
               last_check, last_status, current_users, max_users
        FROM servers 
        WHERE is_active = 1 
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    foreach ($servers as $server) {
        // Quick port check (non-blocking)
        $status = 'unknown';
        $latency = null;
        
        $start = microtime(true);
        $socket = @fsockopen($server['ip'], $server['port'] ?? 51820, $errno, $errstr, 2);
        
        if ($socket) {
            $latency = round((microtime(true) - $start) * 1000);
            $status = 'online';
            fclose($socket);
        } else {
            $status = 'offline';
        }
        
        // Update last_check in database
        $stmt = $db->prepare("UPDATE servers SET last_check = datetime('now'), last_status = ? WHERE id = ?");
        $stmt->execute([$status, $server['id']]);
        
        // Calculate load percentage
        $load = $server['max_users'] > 0 
            ? round(($server['current_users'] / $server['max_users']) * 100) 
            : 0;
        
        $result[] = [
            'id' => (int)$server['id'],
            'name' => $server['name'],
            'location' => $server['location'],
            'country' => $server['country'] ?? 'US',
            'status' => $status,
            'latency_ms' => $latency,
            'load_percent' => $load,
            'users' => (int)$server['current_users'],
            'type' => $server['type'] ?? 'shared'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'servers' => $result,
        'checked_at' => date('c')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
