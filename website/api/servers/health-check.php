<?php
/**
 * TrueVault VPN - Server Health Check API
 * 
 * Performs health check on server
 * Updates health status in database
 * 
 * @method POST
 * @auth Admin only
 */

define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/ServerManager.php';

// Check admin authentication
$user = Auth::getUserFromToken();

if (!$user || !Auth::isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$serverId = intval($input['server_id'] ?? 0);

if ($serverId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid server ID']);
    exit;
}

try {
    $server = ServerManager::getServerById($serverId);
    
    if (!$server) {
        http_response_code(404);
        echo json_encode(['error' => 'Server not found']);
        exit;
    }
    
    // Perform health check (ping + port check)
    $startTime = microtime(true);
    
    // Check if server responds on WireGuard port
    $socket = @fsockopen($server['ip_address'], $server['port'], $errno, $errstr, 2);
    
    $responseTime = round((microtime(true) - $startTime) * 1000); // ms
    
    if ($socket) {
        fclose($socket);
        $status = 'online';
        $details = "Server responding on port {$server['port']}";
    } else {
        $status = 'offline';
        $details = "Failed to connect: $errstr (Error: $errno)";
    }
    
    // Update health status
    ServerManager::updateHealthStatus($serverId, $status, $responseTime, $details);
    
    echo json_encode([
        'success' => true,
        'server_id' => $serverId,
        'status' => $status,
        'response_time' => $responseTime,
        'details' => $details,
        'checked_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Health check failed',
        'details' => $e->getMessage()
    ]);
}
?>
