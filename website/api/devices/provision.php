<?php
/**
 * TrueVault VPN - Device Provisioning API
 * 
 * PURPOSE: Provision new device with WireGuard configuration
 * METHOD: POST
 * ENDPOINT: /api/devices/provision.php
 * 
 * INPUT (JSON):
 * {
 *   "device_name": "iPhone",
 *   "device_type": "mobile",
 *   "public_key": "base64_public_key_here"
 * }
 * 
 * OUTPUT: WireGuard config file content
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

// Only POST
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
    
    // ==========================================
    // STEP 1: AUTHENTICATE USER
    // ==========================================
    
    $user = Auth::requireAuth();
    $userId = $user['id'];
    $userEmail = $user['email'];
    $maxDevices = $user['max_devices'];
    
    // ==========================================
    // STEP 2: PARSE AND VALIDATE INPUT
    // ==========================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    $deviceName = trim($data['device_name'] ?? '');
    $deviceType = $data['device_type'] ?? 'other';
    $publicKey = trim($data['public_key'] ?? '');
    
    // Validate device name
    if (empty($deviceName)) {
        throw new Exception('Device name is required');
    }
    if (strlen($deviceName) > 50) {
        throw new Exception('Device name too long (max 50 characters)');
    }
    
    // Validate public key format (base64, 44 characters including =)
    if (empty($publicKey)) {
        throw new Exception('Public key is required');
    }
    if (!preg_match('/^[A-Za-z0-9+\/]{43}=$/', $publicKey)) {
        throw new Exception('Invalid public key format');
    }
    
    // Validate device type
    $validTypes = ['mobile', 'desktop', 'tablet', 'router', 'other'];
    if (!in_array($deviceType, $validTypes)) {
        $deviceType = 'other';
    }
    
    // ==========================================
    // STEP 3: CHECK DEVICE LIMIT
    // ==========================================
    
    $devicesDb = Database::getInstance('devices');
    
    $deviceCount = $devicesDb->count('devices', "user_id = {$userId} AND status = 'active'");
    
    if ($deviceCount >= $maxDevices) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => "Device limit reached ({$deviceCount}/{$maxDevices}). Remove a device or upgrade your plan."
        ]);
        exit;
    }
    
    // ==========================================
    // STEP 4: CHECK FOR DUPLICATE PUBLIC KEY
    // ==========================================
    
    $escapedKey = trim($devicesDb->escape($publicKey), "'");
    $existingDevice = $devicesDb->queryOne("SELECT id FROM devices WHERE public_key = '{$escapedKey}'");
    
    if ($existingDevice) {
        throw new Exception('This public key is already registered');
    }
    
    // ==========================================
    // STEP 5: SELECT SERVER FOR DEVICE
    // ==========================================
    
    $serversDb = Database::getInstance('servers');
    $mainDb = Database::getInstance('main');
    
    // Check if user has dedicated VIP server
    $emailEsc = trim($mainDb->escape($userEmail), "'");
    $vipInfo = $mainDb->queryOne("SELECT dedicated_server_id FROM vip_users WHERE LOWER(email) = LOWER('{$emailEsc}') AND is_active = 1");
    
    $server = null;
    
    if ($vipInfo && !empty($vipInfo['dedicated_server_id'])) {
        // Use dedicated server
        $server = $serversDb->queryOne("SELECT * FROM servers WHERE id = {$vipInfo['dedicated_server_id']} AND status = 'active'");
    }
    
    if (!$server) {
        // Select server with lowest load (non-VIP-only)
        $server = $serversDb->queryOne("SELECT * FROM servers WHERE status = 'active' AND vip_only = 0 ORDER BY current_clients ASC LIMIT 1");
    }
    
    if (!$server) {
        throw new Exception('No available servers. Please contact support.');
    }
    
    // ==========================================
    // STEP 6: ALLOCATE IP ADDRESS
    // ==========================================
    
    /**
     * Get next available IP from server's pool
     */
    function allocateIP($serverId, $poolStart, $poolEnd, $poolCurrent, $devicesDb) {
        // Parse pool bounds
        $startParts = explode('.', $poolStart);
        $endParts = explode('.', $poolEnd);
        $startOctet = (int)$startParts[3];
        $endOctet = (int)$endParts[3];
        $baseIP = implode('.', array_slice($startParts, 0, 3));
        
        // Get all used IPs for this server
        $usedIPs = $devicesDb->queryAll("SELECT ipv4_address FROM devices WHERE current_server_id = {$serverId} AND status = 'active'");
        $usedList = array_column($usedIPs, 'ipv4_address');
        
        // Find first available IP
        for ($i = $startOctet; $i <= $endOctet; $i++) {
            $testIP = "{$baseIP}.{$i}";
            if (!in_array($testIP, $usedList)) {
                return $testIP;
            }
        }
        
        throw new Exception('Server IP pool exhausted');
    }
    
    $allocatedIP = allocateIP(
        $server['id'],
        $server['ip_pool_start'],
        $server['ip_pool_end'],
        $server['ip_pool_current'],
        $devicesDb
    );
    
    // ==========================================
    // STEP 7: GENERATE PRESHARED KEY
    // ==========================================
    
    $presharedKey = base64_encode(random_bytes(32));
    
    // ==========================================
    // STEP 8: INSERT DEVICE INTO DATABASE
    // ==========================================
    
    $devicesDb->beginTransaction();
    
    try {
        // Insert device
        $deviceId = $devicesDb->insert('devices', [
            'user_id' => $userId,
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'public_key' => $publicKey,
            'private_key_encrypted' => 'USER_MANAGED',
            'preshared_key' => $presharedKey,
            'ipv4_address' => $allocatedIP,
            'current_server_id' => $server['id'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Update server's current clients count
        $serversDb->exec("UPDATE servers SET current_clients = current_clients + 1, ip_pool_current = '{$allocatedIP}' WHERE id = {$server['id']}");
        
        $devicesDb->commit();
        
    } catch (Exception $e) {
        $devicesDb->rollback();
        throw $e;
    }
    
    // ==========================================
    // STEP 9: GENERATE WIREGUARD CONFIG
    // ==========================================
    
    // The config uses [YOUR_PRIVATE_KEY] as placeholder - frontend replaces it
    $config = "[Interface]\n";
    $config .= "# TrueVault VPN - {$deviceName}\n";
    $config .= "# Server: {$server['name']}\n";
    $config .= "# Created: " . date('Y-m-d H:i:s') . "\n";
    $config .= "PrivateKey = [YOUR_PRIVATE_KEY]\n";
    $config .= "Address = {$allocatedIP}/32\n";
    $config .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $config .= "\n";
    $config .= "[Peer]\n";
    $config .= "# TrueVault - {$server['name']}\n";
    $config .= "PublicKey = {$server['public_key']}\n";
    $config .= "PresharedKey = {$presharedKey}\n";
    $config .= "Endpoint = {$server['endpoint']}\n";
    $config .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $config .= "PersistentKeepalive = 25\n";
    
    // ==========================================
    // STEP 10: LOG EVENT
    // ==========================================
    
    try {
        $logsDb = Database::getInstance('logs');
        $logsDb->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'device_provisioned',
            'entity_type' => 'device',
            'entity_id' => $deviceId,
            'details' => json_encode([
                'device_name' => $deviceName,
                'device_type' => $deviceType,
                'server' => $server['name'],
                'ip' => $allocatedIP
            ]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Logging failure shouldn't break provisioning
    }
    
    // ==========================================
    // STEP 11: RETURN SUCCESS
    // ==========================================
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Device provisioned successfully!',
        'device' => [
            'id' => $deviceId,
            'name' => $deviceName,
            'type' => $deviceType,
            'ipv4_address' => $allocatedIP,
            'server' => $server['name'],
            'server_location' => $server['location'] ?? 'Unknown'
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
