<?php
/**
 * Server Health Check API
 * 
 * Calls the VPN server's health endpoint and returns status
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$serverId = (int)($_GET['server_id'] ?? 0);

if (!$serverId) {
    echo json_encode(['success' => false, 'error' => 'Server ID required']);
    exit;
}

try {
    $serversDb = Database::getInstance('servers');
    $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
    $stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $server = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$server) {
        echo json_encode(['success' => false, 'error' => 'Server not found']);
        exit;
    }
    
    // Call the server's health endpoint
    $url = "http://{$server['ip_address']}:{$server['api_port']}/api/health";
    
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
        // Server didn't respond
        echo json_encode([
            'success' => true,
            'server_id' => $serverId,
            'server_name' => $server['name'],
            'status' => 'offline',
            'latency_ms' => null,
            'message' => 'Server did not respond'
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['status'])) {
        echo json_encode([
            'success' => true,
            'server_id' => $serverId,
            'server_name' => $server['name'],
            'status' => $data['status'] === 'ok' ? 'online' : $data['status'],
            'latency_ms' => $latency,
            'server_response' => $data
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'server_id' => $serverId,
            'server_name' => $server['name'],
            'status' => 'degraded',
            'latency_ms' => $latency,
            'message' => 'Unexpected response from server'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
