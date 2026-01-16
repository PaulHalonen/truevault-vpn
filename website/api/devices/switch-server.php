<?php
/**
 * TrueVault VPN - Switch Server API
 * 
 * PURPOSE: Move device to a different server
 * METHOD: POST
 * ENDPOINT: /api/devices/switch-server.php
 * 
 * INPUT (JSON):
 * {
 *   "device_id": 123,
 *   "server_id": 2
 * }
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $userEmail = $user['email'];
    $accountType = $user['account_type'];
    
    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $deviceId = $input['device_id'] ?? null;
    $newServerId = $input['server_id'] ?? null;
    
    if (!$deviceId || !is_numeric($deviceId)) {
        throw new Exception('Device ID is required');
    }
    if (!$newServerId || !is_numeric($newServerId)) {
        throw new Exception('Server ID is required');
    }
    
    $deviceId = (int)$deviceId;
    $newServerId = (int)$newServerId;
    
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
    
    // Check if already on this server
    if ($device['current_server_id'] == $newServerId) {
        throw new Exception('Device is already on this server');
    }
    
    // Get new server
    $serversDb = Database::getInstance('servers');
    
    $newServer = $serversDb->queryOne("SELECT * FROM servers WHERE id = {$newServerId} AND status = 'active'");
    
    if (!$newServer) {
        throw new Exception('Server not found or unavailable');
    }
    
    // Check if server is VIP-only
    if ($newServer['vip_only'] && $accountType !== 'vip' && $accountType !== 'admin') {
        throw new Exception('This server is only available for VIP users');
    }
    
    // Check if server is dedicated to someone else
    if (!empty($newServer['dedicated_user_email']) && strtolower($newServer['dedicated_user_email']) !== strtolower($userEmail)) {
        throw new Exception('This server is not available to you');
    }
    
    // Allocate new IP on new server
    function allocateIP($serverId, $poolStart, $poolEnd, $devicesDb) {
        $startParts = explode('.', $poolStart);
        $endParts = explode('.', $poolEnd);
        $startOctet = (int)$startParts[3];
        $endOctet = (int)$endParts[3];
        $baseIP = implode('.', array_slice($startParts, 0, 3));
        
        $usedIPs = $devicesDb->queryAll("SELECT ipv4_address FROM devices WHERE current_server_id = {$serverId} AND status = 'active'");
        $usedList = array_column($usedIPs, 'ipv4_address');
        
        for ($i = $startOctet; $i <= $endOctet; $i++) {
            $testIP = "{$baseIP}.{$i}";
            if (!in_array($testIP, $usedList)) {
                return $testIP;
            }
        }
        
        throw new Exception('Server IP pool exhausted');
    }
    
    $newIP = allocateIP($newServerId, $newServer['ip_pool_start'], $newServer['ip_pool_end'], $devicesDb);
    
    // Generate new preshared key for the new server
    $newPresharedKey = base64_encode(random_bytes(32));
    
    // Get old server ID for updating count
    $oldServerId = $device['current_server_id'];
    
    // Update device
    $devicesDb->update('devices', [
        'current_server_id' => $newServerId,
        'ipv4_address' => $newIP,
        'preshared_key' => $newPresharedKey,
        'updated_at' => date('Y-m-d H:i:s')
    ], "id = {$deviceId}");
    
    // Update server client counts
    $serversDb->exec("UPDATE servers SET current_clients = MAX(0, current_clients - 1) WHERE id = {$oldServerId}");
    $serversDb->exec("UPDATE servers SET current_clients = current_clients + 1, ip_pool_current = '{$newIP}' WHERE id = {$newServerId}");
    
    // Generate new config
    $config = "[Interface]\n";
    $config .= "# TrueVault VPN - {$device['device_name']}\n";
    $config .= "# Server: {$newServer['name']}\n";
    $config .= "# Switched: " . date('Y-m-d H:i:s') . "\n";
    $config .= "PrivateKey = [YOUR_PRIVATE_KEY]\n";
    $config .= "Address = {$newIP}/32\n";
    $config .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $config .= "\n";
    $config .= "[Peer]\n";
    $config .= "# TrueVault - {$newServer['name']}\n";
    $config .= "PublicKey = {$newServer['public_key']}\n";
    $config .= "PresharedKey = {$newPresharedKey}\n";
    $config .= "Endpoint = {$newServer['endpoint']}\n";
    $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $config .= "PersistentKeepalive = 25\n";
    
    // Log event
    try {
        $logsDb = Database::getInstance('logs');
        $logsDb->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'device_server_switched',
            'entity_type' => 'device',
            'entity_id' => $deviceId,
            'details' => json_encode([
                'from_server' => $oldServerId,
                'to_server' => $newServerId,
                'new_ip' => $newIP
            ]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Log failure not critical
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Device switched to {$newServer['name']}",
        'device' => [
            'id' => $deviceId,
            'name' => $device['device_name'],
            'new_ip' => $newIP
        ],
        'server' => [
            'id' => $newServerId,
            'name' => $newServer['name'],
            'location' => $newServer['location']
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
