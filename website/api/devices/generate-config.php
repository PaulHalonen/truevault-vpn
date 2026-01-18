<?php
/**
 * TrueVault VPN - Device Config Generation API
 * 
 * PURPOSE: Generate VPN config with SERVER-SIDE WireGuard key generation
 * METHOD: POST
 * ENDPOINT: /api/devices/generate-config.php
 * AUTHENTICATION: Required (JWT)
 * 
 * ARCHITECTURE: SERVER-SIDE key generation (NOT browser-side)
 * 
 * INPUT (JSON):
 * {
 *   "device_name": "iPhone",
 *   "device_type": "mobile",
 *   "server_id": 1
 * }
 * 
 * OUTPUT (JSON):
 * {
 *   "success": true,
 *   "device": {
 *     "id": 1,
 *     "name": "iPhone",
 *     "ipv4_address": "10.8.0.2"
 *   },
 *   "config": "[Interface]\nPrivateKey=...\n[Peer]\n..."
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration and dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validator.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    // ============================================
    // STEP 1: AUTHENTICATE USER
    // ============================================
    
    $user = Auth::require();
    $userId = $user['user_id'];
    $userEmail = $user['email'];
    $userTier = $user['tier'] ?? 'standard';
    
    // ============================================
    // STEP 2: GET AND VALIDATE INPUT
    // ============================================
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $validator = new Validator();
    $validator->required($data['device_name'] ?? '', 'device_name');
    $validator->required($data['device_type'] ?? '', 'device_type');
    $validator->required($data['server_id'] ?? '', 'server_id');
    
    if ($validator->hasErrors()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'errors' => $validator->getErrors()
        ]);
        exit;
    }
    
    $deviceName = Validator::sanitize($data['device_name']);
    $deviceType = Validator::sanitize($data['device_type']);
    $serverId = (int)$data['server_id'];
    
    // Validate device name length
    if (strlen($deviceName) > 50) {
        throw new Exception('Device name too long (max 50 characters)');
    }
    
    // Validate device type
    $allowedTypes = ['mobile', 'desktop', 'tablet', 'router', 'other'];
    if (!in_array($deviceType, $allowedTypes)) {
        throw new Exception('Invalid device type');
    }
    
    // ============================================
    // STEP 3: CHECK DEVICE LIMIT
    // ============================================
    
    // Get device limits by tier
    $deviceLimits = [
        'standard' => 3,
        'pro' => 5,
        'vip' => 999,
        'admin' => 999
    ];
    
    $maxDevices = $deviceLimits[$userTier] ?? 3;
    
    // Count existing active devices
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    
    $stmt = $devicesConn->prepare(
        "SELECT COUNT(*) as count FROM devices 
        WHERE user_id = ? AND status = 'active'"
    );
    $stmt->execute([$userId]);
    $deviceCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deviceCount['count'] >= $maxDevices) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => "Device limit reached. Your $userTier plan allows $maxDevices devices."
        ]);
        exit;
    }
    
    // ============================================
    // STEP 4: VERIFY SERVER EXISTS AND IS AVAILABLE
    // ============================================
    
    $serversConn = $db->getConnection('servers');
    
    $stmt = $serversConn->prepare(
        "SELECT id, name, country, region, endpoint, public_key,
                ip_pool_start, ip_pool_end, ip_pool_current,
                is_dedicated, dedicated_user_email, status
        FROM servers 
        WHERE id = ?"
    );
    $stmt->execute([$serverId]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        throw new Exception('Server not found');
    }
    
    if ($server['status'] !== 'online') {
        throw new Exception('Server is not currently available');
    }
    
    // Check if dedicated server belongs to this user
    if ($server['is_dedicated'] == 1) {
        if ($server['dedicated_user_email'] !== $userEmail) {
            throw new Exception('This server is not available for your account');
        }
    }
    
    // ============================================
    // STEP 5: GENERATE WIREGUARD KEYPAIR (SERVER-SIDE)
    // ============================================
    
    /**
     * Generate WireGuard keypair using PHP sodium extension
     * This is MUCH simpler and more secure than browser-side generation
     */
    if (!function_exists('sodium_crypto_box_keypair')) {
        throw new Exception('Sodium extension not available. Please install php-sodium.');
    }
    
    // Generate Curve25519 keypair for WireGuard
    $keypair = sodium_crypto_box_keypair();
    $privateKey = sodium_crypto_box_secretkey($keypair);
    $publicKey = sodium_crypto_box_publickey($keypair);
    
    // Encode to base64 (WireGuard format)
    $privateKeyBase64 = base64_encode($privateKey);
    $publicKeyBase64 = base64_encode($publicKey);
    
    // Clean up sensitive data from memory
    sodium_memzero($keypair);
    sodium_memzero($privateKey);
    
    // ============================================
    // STEP 6: ALLOCATE IP ADDRESS
    // ============================================
    
    /**
     * Allocate next available IP from server's pool
     * Pool format: 10.8.0.2 to 10.8.0.254
     */
    $poolStart = $server['ip_pool_start'];
    $poolEnd = $server['ip_pool_end'];
    $poolCurrent = $server['ip_pool_current'];
    
    // If no current IP, start from pool start
    if (!$poolCurrent) {
        $allocatedIP = $poolStart;
    } else {
        // Parse current IP and increment
        $parts = explode('.', $poolCurrent);
        $lastOctet = (int)$parts[3];
        $lastOctet++;
        
        // Check if we've exceeded pool
        $poolEndOctet = (int)explode('.', $poolEnd)[3];
        
        if ($lastOctet > $poolEndOctet) {
            // Pool exhausted - find gaps from deleted devices
            $stmt = $devicesConn->prepare(
                "SELECT ipv4_address FROM devices 
                WHERE current_server_id = ? 
                ORDER BY ipv4_address"
            );
            $stmt->execute([$serverId]);
            $usedIPs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Find first gap
            $baseIP = implode('.', array_slice(explode('.', $poolStart), 0, 3));
            $startOctet = (int)explode('.', $poolStart)[3];
            
            $allocatedIP = null;
            for ($i = $startOctet; $i <= $poolEndOctet; $i++) {
                $testIP = "$baseIP.$i";
                if (!in_array($testIP, $usedIPs)) {
                    $allocatedIP = $testIP;
                    break;
                }
            }
            
            if (!$allocatedIP) {
                throw new Exception('Server IP pool exhausted. Please contact support.');
            }
        } else {
            // Build new IP
            $parts[3] = $lastOctet;
            $allocatedIP = implode('.', $parts);
        }
    }
    
    // Update server's current IP pointer
    $stmt = $serversConn->prepare(
        "UPDATE servers SET ip_pool_current = ? WHERE id = ?"
    );
    $stmt->execute([$allocatedIP, $serverId]);
    
    // ============================================
    // STEP 7: STORE DEVICE IN DATABASE
    // ============================================
    
    $deviceId = uniqid('dev_', true);
    $currentTimestamp = date('Y-m-d H:i:s');
    
    $stmt = $devicesConn->prepare(
        "INSERT INTO devices (
            device_id, user_id, device_name, device_type,
            ipv4_address, public_key, current_server_id,
            status, created_at, last_handshake
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NULL)"
    );
    
    $stmt->execute([
        $deviceId,
        $userId,
        $deviceName,
        $deviceType,
        $allocatedIP,
        $publicKeyBase64,
        $serverId,
        $currentTimestamp
    ]);
    
    $insertedId = $devicesConn->lastInsertId();
    
    // ============================================
    // STEP 8: GENERATE WIREGUARD CONFIG FILE
    // ============================================
    
    /**
     * Generate complete WireGuard configuration file
     * 
     * Format:
     * [Interface]
     * PrivateKey = device_private_key
     * Address = allocated_ip/32
     * DNS = 1.1.1.1, 1.0.0.1
     * 
     * [Peer]
     * PublicKey = server_public_key
     * Endpoint = server_endpoint
     * AllowedIPs = 0.0.0.0/0, ::/0
     * PersistentKeepalive = 25
     */
    
    $configContent = "[Interface]\n";
    $configContent .= "PrivateKey = " . $privateKeyBase64 . "\n";
    $configContent .= "Address = " . $allocatedIP . "/32\n";
    $configContent .= "DNS = 1.1.1.1, 1.0.0.1\n";
    $configContent .= "\n";
    $configContent .= "[Peer]\n";
    $configContent .= "PublicKey = " . $server['public_key'] . "\n";
    $configContent .= "Endpoint = " . $server['endpoint'] . "\n";
    $configContent .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $configContent .= "PersistentKeepalive = 25\n";
    
    // Clean up private key from memory
    sodium_memzero($privateKeyBase64);
    
    // ============================================
    // STEP 9: RETURN SUCCESS RESPONSE
    // ============================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'device' => [
            'id' => $insertedId,
            'device_id' => $deviceId,
            'name' => $deviceName,
            'type' => $deviceType,
            'ipv4_address' => $allocatedIP,
            'server' => $server['name'],
            'created_at' => $currentTimestamp
        ],
        'config' => $configContent
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    
    error_log("Device Generate Config API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
