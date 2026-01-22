<?php
/**
 * Add/Provision Device API - SERVER-SIDE KEY GENERATION
 * 
 * PURPOSE: Create new device with WireGuard keys generated on VPN SERVER
 * METHOD: POST
 * ENDPOINT: /api/devices/add.php
 * REQUIRES: Bearer token
 * 
 * ARCHITECTURE:
 * 1. User requests device
 * 2. Web server calls VPN server API at port 8443
 * 3. VPN server generates keys and adds peer to WireGuard
 * 4. VPN server returns config
 * 5. Web server stores in database and returns to user
 * 
 * @created January 2026
 * @version 2.0.0 - Now uses VPN server-side key generation
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

/**
 * Call VPN Server API to create peer
 */
function callVpnServerApi($serverIp, $apiPort, $apiSecret, $endpoint, $data = []) {
    $url = "http://{$serverIp}:{$apiPort}{$endpoint}";
    
    $options = [
        'http' => [
            'method' => empty($data) ? 'GET' : 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiSecret
            ],
            'content' => empty($data) ? '' : json_encode($data),
            'timeout' => 30,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to connect to VPN server'];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid response from VPN server'];
    }
    
    return $decoded;
}

try {
    // ============================================
    // STEP 1: AUTHENTICATE USER
    // ============================================
    
    $payload = JWT::requireAuth();
    $userId = $payload['user_id'];
    $userTier = $payload['tier'];
    $userEmail = $payload['email'];
    
    // ============================================
    // STEP 2: VALIDATE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    $validator = new Validator();
    $validator->deviceName($data['device_name'] ?? '', 'device_name');
    $validator->deviceType($data['device_type'] ?? 'other', 'device_type');
    
    if ($validator->hasErrors()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $validator->getFirstError()]);
        exit;
    }
    
    $deviceName = $validator->get('device_name');
    $deviceType = $validator->get('device_type');
    $requestedServerId = $data['server_id'] ?? null;
    
    // ============================================
    // STEP 3: CHECK DEVICE LIMIT
    // ============================================
    
    $maxDevices = ['standard' => 3, 'pro' => 5, 'vip' => 999, 'admin' => 999];
    $limit = $maxDevices[$userTier] ?? 3;
    
    $devicesDb = Database::getInstance('devices');
    $stmt = $devicesDb->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = :user_id AND status = 'active'");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];
    
    if ($count >= $limit) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => "Device limit reached ({$count}/{$limit}). Upgrade your plan or remove a device."
        ]);
        exit;
    }
    
    // ============================================
    // STEP 4: CHECK FOR USER'S VIP SERVER
    // ============================================
    
    $usersDb = Database::getInstance('users');
    $stmt = $usersDb->prepare("SELECT vip_server_id FROM users WHERE id = :id");
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $userRecord = $result->fetchArray(SQLITE3_ASSOC);
    $vipServerId = $userRecord['vip_server_id'] ?? null;
    
    // ============================================
    // STEP 5: SELECT SERVER
    // ============================================
    
    $serversDb = Database::getInstance('servers');
    
    if ($vipServerId) {
        // User has dedicated VIP server - MUST use it
        $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id AND status = 'active'");
        $stmt->bindValue(':id', $vipServerId, SQLITE3_INTEGER);
    } elseif ($requestedServerId) {
        // User requested specific server - verify access
        $stmt = $serversDb->prepare("
            SELECT * FROM servers 
            WHERE id = :id 
            AND status = 'active' 
            AND (vip_only = 0 OR :tier IN ('vip', 'admin'))
            AND dedicated_user_email IS NULL
        ");
        $stmt->bindValue(':id', $requestedServerId, SQLITE3_INTEGER);
        $stmt->bindValue(':tier', $userTier, SQLITE3_TEXT);
    } else {
        // Auto-select server with lowest load
        $stmt = $serversDb->prepare("
            SELECT * FROM servers 
            WHERE status = 'active' 
            AND (vip_only = 0 OR :tier IN ('vip', 'admin'))
            AND dedicated_user_email IS NULL
            ORDER BY load_percentage ASC, current_clients ASC
            LIMIT 1
        ");
        $stmt->bindValue(':tier', $userTier, SQLITE3_TEXT);
    }
    
    $result = $stmt->execute();
    $server = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$server) {
        throw new Exception('No available servers. Please try again later.');
    }
    
    // ============================================
    // STEP 6: GET API SECRET FOR THIS SERVER
    // ============================================
    
    $adminDb = Database::getInstance('admin');
    $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
    $stmt->bindValue(':key', 'server_api_secret_' . $server['id'], SQLITE3_TEXT);
    $result = $stmt->execute();
    $secretRow = $result->fetchArray(SQLITE3_ASSOC);
    
    // Fallback to global API secret if per-server not set
    if (!$secretRow) {
        $stmt = $adminDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'vpn_server_api_secret'");
        $result = $stmt->execute();
        $secretRow = $result->fetchArray(SQLITE3_ASSOC);
    }
    
    $apiSecret = $secretRow['setting_value'] ?? 'TRUEVAULT_API_SECRET_2026';
    $apiPort = $server['api_port'] ?? 8443;
    
    // ============================================
    // STEP 7: CALL VPN SERVER TO CREATE PEER
    // ============================================
    
    $vpnResponse = callVpnServerApi(
        $server['ip_address'],
        $apiPort,
        $apiSecret,
        '/api/create-peer',
        [
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'user_id' => $userId,
            'user_email' => $userEmail
        ]
    );
    
    if (!isset($vpnResponse['success']) || !$vpnResponse['success']) {
        // Log the failure
        $logsDb = Database::getInstance('logs');
        $stmt = $logsDb->prepare("
            INSERT INTO error_log (error_type, message, context, created_at)
            VALUES ('vpn_api', :msg, :ctx, datetime('now'))
        ");
        $stmt->bindValue(':msg', $vpnResponse['error'] ?? 'VPN server API call failed', SQLITE3_TEXT);
        $stmt->bindValue(':ctx', json_encode([
            'server' => $server['name'],
            'server_ip' => $server['ip_address'],
            'user_id' => $userId
        ]), SQLITE3_TEXT);
        $stmt->execute();
        
        throw new Exception('Failed to provision device on VPN server: ' . ($vpnResponse['error'] ?? 'Unknown error'));
    }
    
    // Extract data from VPN server response
    $privateKey = $vpnResponse['private_key'];
    $publicKey = $vpnResponse['public_key'];
    $allocatedIP = $vpnResponse['client_ip'];
    $config = $vpnResponse['config'];
    $presharedKey = $vpnResponse['preshared_key'] ?? null;
    
    // ============================================
    // STEP 8: INSERT DEVICE INTO DATABASE
    // ============================================
    
    $stmt = $devicesDb->prepare("
        INSERT INTO devices (
            user_id, device_name, device_type, public_key, private_key_encrypted,
            preshared_key, ipv4_address, current_server_id, status, created_at, updated_at
        ) VALUES (
            :user_id, :device_name, :device_type, :public_key, :private_key,
            :preshared_key, :ipv4_address, :server_id, 'active', datetime('now'), datetime('now')
        )
    ");
    
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_name', $deviceName, SQLITE3_TEXT);
    $stmt->bindValue(':device_type', $deviceType, SQLITE3_TEXT);
    $stmt->bindValue(':public_key', $publicKey, SQLITE3_TEXT);
    $stmt->bindValue(':private_key', $privateKey, SQLITE3_TEXT);
    $stmt->bindValue(':preshared_key', $presharedKey, SQLITE3_TEXT);
    $stmt->bindValue(':ipv4_address', $allocatedIP, SQLITE3_TEXT);
    $stmt->bindValue(':server_id', $server['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    $deviceId = $devicesDb->lastInsertRowID();
    
    // Update server client count
    $stmt = $serversDb->prepare("UPDATE servers SET current_clients = current_clients + 1 WHERE id = :id");
    $stmt->bindValue(':id', $server['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    // ============================================
    // STEP 9: STORE CONFIG IN DATABASE
    // ============================================
    
    $stmt = $devicesDb->prepare("
        INSERT INTO device_configs (device_id, server_id, config_content, qr_code_data, generated_at)
        VALUES (:device_id, :server_id, :config, :qr_data, datetime('now'))
    ");
    $stmt->bindValue(':device_id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':server_id', $server['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':config', $config, SQLITE3_TEXT);
    $stmt->bindValue(':qr_data', $config, SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 10: LOG EVENT
    // ============================================
    
    $logsDb = Database::getInstance('logs');
    $stmt = $logsDb->prepare("
        INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at)
        VALUES (:user_id, 'device_created', 'device', :device_id, :details, :ip, datetime('now'))
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':device_id', $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':details', json_encode([
        'device_name' => $deviceName,
        'server' => $server['name'],
        'ip' => $allocatedIP,
        'method' => 'vpn_server_api'
    ]), SQLITE3_TEXT);
    $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
    $stmt->execute();
    
    // ============================================
    // STEP 11: RETURN SUCCESS
    // ============================================
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Device created successfully',
        'device' => [
            'id' => $deviceId,
            'name' => $deviceName,
            'type' => $deviceType,
            'ip_address' => $allocatedIP,
            'server' => $server['name'],
            'server_location' => $server['location']
        ],
        'config' => $config,
        'qr_data' => $config
    ]);
    
} catch (Exception $e) {
    logError('Add device failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
