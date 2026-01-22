<?php
/**
 * Test VPN Server API Connectivity
 * 
 * Calls the VPN server's health endpoint to verify connectivity
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $serverId = (int)($_GET['server_id'] ?? 0);
    
    if (!$serverId) {
        throw new Exception('Server ID required');
    }
    
    // Get server info
    $serversDb = Database::getInstance('servers');
    $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
    $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $server = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$server) {
        throw new Exception('Server not found');
    }
    
    // Call VPN server health endpoint
    $apiPort = $server['api_port'] ?? 8443;
    $url = "http://{$server['ip_address']}:{$apiPort}/api/health";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $startTime = microtime(true);
    $response = @file_get_contents($url, false, $context);
    $latency = round((microtime(true) - $startTime) * 1000);
    
    if ($response === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to connect to VPN server',
            'server_id' => $serverId,
            'server_name' => $server['name']
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid response from server',
            'raw_response' => substr($response, 0, 200)
        ]);
        exit;
    }
    
    // Get peer count if available
    $peerCount = null;
    $serverInfoUrl = "http://{$server['ip_address']}:{$apiPort}/api/server-info";
    $infoResponse = @file_get_contents($serverInfoUrl, false, $context);
    if ($infoResponse) {
        $infoData = json_decode($infoResponse, true);
        // Server info doesn't include peer count, would need list-peers with auth
    }
    
    echo json_encode([
        'success' => true,
        'server_id' => $serverId,
        'server_name' => $server['name'],
        'status' => $data['status'] ?? 'unknown',
        'latency_ms' => $latency,
        'response' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
