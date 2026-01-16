<?php
/**
 * TrueVault VPN - Get Device Config API
 * 
 * PURPOSE: Re-download WireGuard config for existing device
 * METHOD: GET
 * ENDPOINT: /api/devices/get-config.php?id=123
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
    $userId = $user['id'];
    
    // Get device ID
    $deviceId = $_GET['id'] ?? null;
    
    if (!$deviceId || !is_numeric($deviceId)) {
        throw new Exception('Device ID is required');
    }
    
    $deviceId = (int)$deviceId;
    
    // Get device
    $devicesDb = Database::getInstance('devices');
    
    $device = $devicesDb->queryOne(
        "SELECT * FROM devices WHERE id = {$deviceId} AND user_id = {$userId} AND status = 'active'"
    );
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        exit;
    }
    
    // Get server
    $serversDb = Database::getInstance('servers');
    $server = $serversDb->queryOne("SELECT * FROM servers WHERE id = {$device['current_server_id']}");
    
    if (!$server) {
        throw new Exception('Server configuration not found');
    }
    
    // Generate config
    $config = "[Interface]\n";
    $config .= "# TrueVault VPN - {$device['device_name']}\n";
    $config .= "# Server: {$server['name']}\n";
    $config .= "# Regenerated: " . date('Y-m-d H:i:s') . "\n";
    $config .= "PrivateKey = [YOUR_PRIVATE_KEY]\n";
    $config .= "Address = {$device['ipv4_address']}/32\n";
    $config .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $config .= "\n";
    $config .= "[Peer]\n";
    $config .= "# TrueVault - {$server['name']}\n";
    $config .= "PublicKey = {$server['public_key']}\n";
    $config .= "PresharedKey = {$device['preshared_key']}\n";
    $config .= "Endpoint = {$server['endpoint']}\n";
    $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $config .= "PersistentKeepalive = 25\n";
    
    echo json_encode([
        'success' => true,
        'device' => [
            'id' => $device['id'],
            'name' => $device['device_name'],
            'type' => $device['device_type']
        ],
        'server' => [
            'name' => $server['name'],
            'location' => $server['location']
        ],
        'config' => $config
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
