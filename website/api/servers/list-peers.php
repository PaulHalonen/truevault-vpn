<?php
/**
 * List Peers on VPN Server
 * 
 * Calls the VPN server's list-peers endpoint (requires auth)
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
    
    // Get API secret for this server
    $adminDb = Database::getInstance('admin');
    $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
    $stmt->bindValue(':key', 'server_api_secret_' . $serverId, SQLITE3_TEXT);
    $result = $stmt->execute();
    $secretRow = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$secretRow) {
        // Fallback to global secret
        $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'vpn_server_api_secret'");
        $result = $stmt->execute();
        $secretRow = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    $apiSecret = $secretRow['setting_value'] ?? 'TRUEVAULT_API_SECRET_2026';
    $apiPort = $server['api_port'] ?? 8443;
    
    // Call VPN server list-peers endpoint with auth
    $url = "http://{$server['ip_address']}:{$apiPort}/api/list-peers";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Authorization: Bearer ' . $apiSecret,
                'Content-Type: application/json'
            ],
            'timeout' => 15,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to connect to VPN server',
            'server_id' => $serverId
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid response from server'
        ]);
        exit;
    }
    
    if (!isset($data['success']) || !$data['success']) {
        echo json_encode([
            'success' => false,
            'error' => $data['error'] ?? 'Failed to get peer list'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'server_id' => $serverId,
        'server_name' => $server['name'],
        'peer_count' => $data['peer_count'] ?? 0,
        'peers' => $data['peers'] ?? []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
