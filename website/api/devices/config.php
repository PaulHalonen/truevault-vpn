<?php
/**
 * Get Device Config API - SQLITE3 VERSION
 * 
 * PURPOSE: Download WireGuard config file for a device
 * METHOD: GET
 * ENDPOINT: /api/devices/config.php?device_id=123
 * REQUIRES: Bearer token
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

// Check if download requested
$download = isset($_GET['download']) && $_GET['download'] === '1';

if (!$download) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
}

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
    // Authenticate user
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    
    // Get device ID
    $deviceId = (int)($_GET['device_id'] ?? 0);
    
    if (!$deviceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Device ID required']);
        exit;
    }
    
    // Verify device ownership
    $devicesDb = Database::getInstance('devices');
    
    $stmt = $devicesDb->prepare("
        SELECT d.id, d.device_name, d.ipv4_address, d.public_key, d.private_key_encrypted, 
               d.preshared_key, d.current_server_id
        FROM devices d
        WHERE d.id = :id AND d.user_id = :user_id AND d.status = 'active'
    ");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $device = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }
    
    // Get server info
    $serversDb = Database::getInstance('servers');
    $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
    $stmt->bindValue(':id', $device['current_server_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $server = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$server) {
        throw new Exception('Server not found');
    }
    
    // Get stored config or regenerate
    $stmt = $devicesDb->prepare("SELECT config_content FROM device_configs WHERE device_id = :id ORDER BY generated_at DESC LIMIT 1");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $configRow = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($configRow && $configRow['config_content']) {
        $config = $configRow['config_content'];
    } else {
        // Regenerate config
        $config = "[Interface]\n";
        $config .= "# TrueVault VPN - {$device['device_name']}\n";
        $config .= "# Server: {$server['name']} ({$server['location']})\n";
        $config .= "PrivateKey = {$device['private_key_encrypted']}\n";
        $config .= "Address = {$device['ipv4_address']}/32\n";
        $config .= "DNS = 1.1.1.1, 1.0.0.1\n\n";
        
        $config .= "[Peer]\n";
        $config .= "# TrueVault VPN Server\n";
        $config .= "PublicKey = {$server['public_key']}\n";
        $config .= "PresharedKey = {$device['preshared_key']}\n";
        // Check if endpoint already includes port
        $endpoint = $server['endpoint'];
        if (strpos($endpoint, ':') === false) {
            $endpoint .= ':' . $server['listen_port'];
        }
        $config .= "Endpoint = {$endpoint}\n";
        $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
        $config .= "PersistentKeepalive = 25\n";
    }
    
    // Mark as downloaded
    $stmt = $devicesDb->prepare("UPDATE device_configs SET downloaded = 1, downloaded_at = datetime('now') WHERE device_id = :id");
    $stmt->bindValue(':id', $deviceId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Return as download or JSON
    if ($download) {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $device['device_name']) . '.conf';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($config));
        echo $config;
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'device' => [
            'id' => (int)$device['id'],
            'name' => $device['device_name'],
            'ip_address' => $device['ipv4_address'],
            'server' => $server['name']
        ],
        'config' => $config,
        'qr_data' => $config
    ]);
    
} catch (Exception $e) {
    logError('Get config failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to get config']);
}
