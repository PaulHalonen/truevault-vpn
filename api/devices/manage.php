<?php
/**
 * TrueVault VPN - Device Management API
 * Handles adding devices, generating WireGuard configs
 * 
 * CONFIG FILE NAMING:
 * - SrvrNY.conf (New York)
 * - SrvrTX.conf (Dallas/Texas)
 * - SrvrCDN.conf (Canada)
 * - SrvrSTL.conf (St. Louis - VIP Dedicated only)
 * 
 * ANDROID WORKAROUNDS:
 * - Force download with application/octet-stream
 * - WireGuard tunnel:// deep link
 * - Direct file import instructions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

// Server short names mapping
$SERVER_NAMES = [
    1 => 'SrvrNY',    // New York
    2 => 'SrvrSTL',   // St. Louis (VIP Dedicated)
    3 => 'SrvrTX',    // Dallas/Texas
    4 => 'SrvrCDN'    // Canada
];

$SERVER_INFO = [
    1 => [
        'name' => 'SrvrNY',
        'display' => 'New York (US East)',
        'ip' => '66.94.103.91',
        'location' => 'New York',
        'features' => ['Xbox/Gaming', 'Torrents', 'Streaming', 'Home Devices'],
        'restrictions' => [],
        'recommended_for' => 'Home devices, gaming consoles, high bandwidth activities',
        'vip_only' => false
    ],
    2 => [
        'name' => 'SrvrSTL',
        'display' => 'St. Louis (US Central)',
        'ip' => '144.126.133.253',
        'location' => 'St. Louis',
        'features' => ['Unlimited Bandwidth', 'Xbox/Gaming', 'Torrents', 'Streaming'],
        'restrictions' => [],
        'recommended_for' => 'VIP Dedicated - All activities',
        'vip_only' => true,
        'dedicated_to' => 'seige235@yahoo.com'
    ],
    3 => [
        'name' => 'SrvrTX',
        'display' => 'Dallas (US Central)',
        'ip' => '66.241.124.4',
        'location' => 'Dallas',
        'features' => ['Netflix', 'Streaming', 'Browsing'],
        'restrictions' => ['NO Torrents', 'NO Xbox/Gaming', 'Limited Bandwidth'],
        'recommended_for' => 'Streaming Netflix, general browsing',
        'vip_only' => false
    ],
    4 => [
        'name' => 'SrvrCDN',
        'display' => 'Toronto (Canada)',
        'ip' => '66.241.125.247',
        'location' => 'Toronto',
        'features' => ['Netflix', 'Streaming', 'Browsing', 'Canadian Content'],
        'restrictions' => ['NO Torrents', 'NO Xbox/Gaming', 'Limited Bandwidth'],
        'recommended_for' => 'Canadian Netflix, streaming, browsing',
        'vip_only' => false
    ]
];

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_servers':
        listAvailableServers();
        break;
    case 'add_device':
        addDevice();
        break;
    case 'get_config':
        getDeviceConfig();
        break;
    case 'download_config':
        downloadConfig();
        break;
    case 'get_devices':
        getUserDevices();
        break;
    case 'delete_device':
        deleteDevice();
        break;
    case 'switch_server':
        switchServer();
        break;
    default:
        Response::error('Invalid action', 400);
}

/**
 * List available servers for user
 */
function listAvailableServers() {
    global $SERVER_INFO;
    
    $user = Auth::requireAuth();
    $vipInfo = getVipInfo($user['email']);
    
    $servers = [];
    
    foreach ($SERVER_INFO as $id => $server) {
        // Skip VIP-only servers unless user has access
        if ($server['vip_only']) {
            if (!$vipInfo) continue;
            if (isset($server['dedicated_to']) && $server['dedicated_to'] !== strtolower($user['email'])) {
                continue;
            }
        }
        
        $servers[] = [
            'id' => $id,
            'name' => $server['name'],
            'display' => $server['display'],
            'location' => $server['location'],
            'features' => $server['features'],
            'restrictions' => $server['restrictions'],
            'recommended_for' => $server['recommended_for'],
            'is_dedicated' => $server['vip_only'] ?? false
        ];
    }
    
    Response::success(['servers' => $servers]);
}

/**
 * Add a new device
 */
function addDevice() {
    global $SERVER_INFO, $SERVER_NAMES;
    
    Response::requireMethod('POST');
    $user = Auth::requireAuth();
    $input = Response::getJsonInput();
    
    $deviceName = trim($input['device_name'] ?? '');
    $serverId = intval($input['server_id'] ?? 1);
    $deviceType = $input['device_type'] ?? 'other';
    
    if (empty($deviceName)) {
        Response::error('Device name is required', 400);
    }
    
    // Validate server access
    $server = $SERVER_INFO[$serverId] ?? null;
    if (!$server) {
        Response::error('Invalid server', 400);
    }
    
    $vipInfo = getVipInfo($user['email']);
    
    // Check VIP server access
    if ($server['vip_only']) {
        if (!$vipInfo) {
            Response::error('This server requires VIP access', 403);
        }
        if (isset($server['dedicated_to']) && $server['dedicated_to'] !== strtolower($user['email'])) {
            Response::error('This is a dedicated server for another user', 403);
        }
    }
    
    // Check device limits
    $deviceCount = countUserDevices($user['id']);
    $maxDevices = $vipInfo ? $vipInfo['max_devices'] : 5;
    
    if ($deviceType === 'camera') {
        $cameraCount = countUserDevices($user['id'], 'camera');
        $maxCameras = $vipInfo ? $vipInfo['max_cameras'] : 0;
        if ($cameraCount >= $maxCameras) {
            Response::error("Camera limit reached ($maxCameras max)", 403);
        }
    } else {
        if ($deviceCount >= $maxDevices) {
            Response::error("Device limit reached ($maxDevices max)", 403);
        }
    }
    
    // Generate WireGuard keys
    $privateKey = generateWgKey();
    $publicKey = generateWgPublicKey($privateKey);
    
    // Get next available IP for this server
    $clientIp = getNextClientIp($serverId);
    
    // Save device to database
    $db = getDatabase('devices');
    $stmt = $db->prepare("INSERT INTO user_devices 
        (user_id, device_name, device_type, server_id, server_name, private_key, public_key, client_ip, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");
    $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $deviceName, SQLITE3_TEXT);
    $stmt->bindValue(3, $deviceType, SQLITE3_TEXT);
    $stmt->bindValue(4, $serverId, SQLITE3_INTEGER);
    $stmt->bindValue(5, $SERVER_NAMES[$serverId], SQLITE3_TEXT);
    $stmt->bindValue(6, $privateKey, SQLITE3_TEXT);
    $stmt->bindValue(7, $publicKey, SQLITE3_TEXT);
    $stmt->bindValue(8, $clientIp, SQLITE3_TEXT);
    $stmt->execute();
    
    $deviceId = $db->lastInsertRowID();
    
    // Register peer on VPN server
    registerPeerOnServer($serverId, $publicKey, $clientIp);
    
    // Generate config content
    $config = generateWgConfig($serverId, $privateKey, $clientIp);
    $configName = $SERVER_NAMES[$serverId] . '.conf';
    
    Response::success([
        'device_id' => $deviceId,
        'device_name' => $deviceName,
        'server_name' => $SERVER_NAMES[$serverId],
        'config_filename' => $configName,
        'config_content' => $config,
        'client_ip' => $clientIp,
        'server_info' => [
            'display' => $server['display'],
            'features' => $server['features'],
            'restrictions' => $server['restrictions']
        ],
        'instructions' => getSetupInstructions($deviceType)
    ], 'Device added successfully');
}

/**
 * Generate WireGuard config
 */
function generateWgConfig($serverId, $privateKey, $clientIp) {
    global $SERVER_INFO;
    
    $server = $SERVER_INFO[$serverId];
    $serverPublicKeys = [
        1 => 'lbriy+env0wv6VmEJscnjoREswmiQdn7D+1KGai9n3s=',  // NY
        2 => 'qs6zminmBmqHfYzqvQ71xURDVGdC3aBLJsWjrevJHAM=',  // STL
        3 => 'dFEz/d9TKfddkOZ6aMNO3uO+jOGgQwXSR/+Ay+IXXmk=',  // TX
        4 => 'O3wtZKY+62QGZArL7W8vicyZecjN1IBDjHTvdnon1mk='   // CDN
    ];
    
    $config = "[Interface]\n";
    $config .= "PrivateKey = $privateKey\n";
    $config .= "Address = $clientIp/32\n";
    $config .= "DNS = 1.1.1.1, 8.8.8.8\n\n";
    $config .= "[Peer]\n";
    $config .= "PublicKey = {$serverPublicKeys[$serverId]}\n";
    $config .= "AllowedIPs = 0.0.0.0/0\n";
    $config .= "Endpoint = {$server['ip']}:51820\n";
    $config .= "PersistentKeepalive = 25\n";
    
    return $config;
}

/**
 * Download config file with Android-safe headers
 */
function downloadConfig() {
    global $SERVER_NAMES;
    
    $user = Auth::requireAuth();
    $deviceId = intval($_GET['device_id'] ?? 0);
    
    $db = getDatabase('devices');
    $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
    $stmt->bindValue(1, $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $device = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$device) {
        Response::error('Device not found', 404);
    }
    
    $config = generateWgConfig($device['server_id'], $device['private_key'], $device['client_ip']);
    $filename = $device['server_name'] . '.conf';
    
    // Android-safe headers - force download without .txt extension
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($config));
    header('Cache-Control: no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    
    echo $config;
    exit;
}

/**
 * Get setup instructions based on device type
 */
function getSetupInstructions($deviceType) {
    $instructions = [
        'android' => [
            'title' => 'Android Setup',
            'steps' => [
                '1. Download the config file using the button below',
                '2. Open WireGuard app',
                '3. Tap the + button',
                '4. Select "Import from file or archive"',
                '5. Navigate to Downloads folder',
                '6. Select the .conf file',
                '7. Tap the toggle to connect'
            ],
            'important' => [
                'If the file downloads as .conf.txt, use a file manager to rename it to .conf',
                'Or use the "Import via File Manager" option in WireGuard',
                'Make sure to allow WireGuard to create a VPN connection when prompted'
            ],
            'alternative' => 'Use "Email Config" to send the file to yourself and open from email'
        ],
        'iphone' => [
            'title' => 'iPhone/iPad Setup',
            'steps' => [
                '1. Download the config file',
                '2. Tap "Open in WireGuard" when prompted',
                '3. Tap "Allow" to add the VPN configuration',
                '4. Toggle the connection ON'
            ],
            'important' => [
                'You may need to enter your device passcode',
                'Allow VPN configuration when iOS prompts you'
            ]
        ],
        'windows' => [
            'title' => 'Windows Setup',
            'steps' => [
                '1. Download and install WireGuard from wireguard.com',
                '2. Download the config file',
                '3. Open WireGuard',
                '4. Click "Import tunnel(s) from file"',
                '5. Select the downloaded .conf file',
                '6. Click "Activate" to connect'
            ]
        ],
        'mac' => [
            'title' => 'Mac Setup',
            'steps' => [
                '1. Install WireGuard from the App Store',
                '2. Download the config file',
                '3. Open WireGuard',
                '4. Click "Import tunnel(s) from file"',
                '5. Select the .conf file',
                '6. Click "Activate"'
            ]
        ],
        'linux' => [
            'title' => 'Linux Setup',
            'steps' => [
                '1. Install WireGuard: sudo apt install wireguard',
                '2. Download the config file',
                '3. Copy to: sudo cp SrvrXX.conf /etc/wireguard/',
                '4. Connect: sudo wg-quick up SrvrXX',
                '5. To disconnect: sudo wg-quick down SrvrXX'
            ]
        ],
        'other' => [
            'title' => 'General Setup',
            'steps' => [
                '1. Install WireGuard for your platform',
                '2. Download the config file below',
                '3. Import the .conf file into WireGuard',
                '4. Activate the tunnel to connect'
            ]
        ]
    ];
    
    return $instructions[$deviceType] ?? $instructions['other'];
}

/**
 * Get VIP info for user
 */
function getVipInfo($email) {
    $email = strtolower($email);
    try {
        $db = new SQLite3(__DIR__ . '/../../data/vip.db');
        $stmt = $db->prepare("SELECT * FROM vip_users WHERE email = ?");
        $stmt->bindValue(1, $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Count user devices
 */
function countUserDevices($userId, $type = null) {
    $db = getDatabase('devices');
    if ($type) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND device_type = ?");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $type, SQLITE3_TEXT);
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_devices WHERE user_id = ?");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    }
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'] ?? 0;
}

/**
 * Get next available client IP
 */
function getNextClientIp($serverId) {
    $networks = [
        1 => '10.0.0',   // NY
        2 => '10.0.1',   // STL
        3 => '10.0.2',   // TX
        4 => '10.10.0'   // CDN
    ];
    
    $db = getDatabase('devices');
    $stmt = $db->prepare("SELECT client_ip FROM user_devices WHERE server_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bindValue(1, $serverId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($row) {
        $parts = explode('.', $row['client_ip']);
        $lastOctet = intval($parts[3]) + 1;
        if ($lastOctet > 254) $lastOctet = 2;
        return $networks[$serverId] . '.' . $lastOctet;
    }
    
    return $networks[$serverId] . '.2';
}

/**
 * Generate WireGuard private key
 */
function generateWgKey() {
    // Generate 32 random bytes and base64 encode
    $bytes = random_bytes(32);
    // Set the appropriate bits for Curve25519
    $bytes[0] = chr(ord($bytes[0]) & 248);
    $bytes[31] = chr((ord($bytes[31]) & 127) | 64);
    return base64_encode($bytes);
}

/**
 * Generate public key from private key
 * Note: This is a placeholder - real implementation needs sodium
 */
function generateWgPublicKey($privateKey) {
    // Try using sodium if available
    if (function_exists('sodium_crypto_box_publickey_from_secretkey')) {
        $privateBytes = base64_decode($privateKey);
        $publicBytes = sodium_crypto_scalarmult_base($privateBytes);
        return base64_encode($publicBytes);
    }
    
    // Fallback: generate another key pair
    // In production, should call wg command or use proper crypto
    $bytes = random_bytes(32);
    return base64_encode($bytes);
}

/**
 * Register peer on VPN server
 */
function registerPeerOnServer($serverId, $publicKey, $clientIp) {
    // This will call the peer API on the server
    // Implementation depends on your server setup
    // For now, log the request
    error_log("Register peer: Server $serverId, Key: $publicKey, IP: $clientIp");
    return true;
}

/**
 * Get user's devices
 */
function getUserDevices() {
    $user = Auth::requireAuth();
    
    $db = getDatabase('devices');
    $stmt = $db->prepare("SELECT id, device_name, device_type, server_id, server_name, client_ip, created_at FROM user_devices WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $devices = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $devices[] = $row;
    }
    
    Response::success(['devices' => $devices]);
}

/**
 * Delete a device
 */
function deleteDevice() {
    Response::requireMethod('POST');
    $user = Auth::requireAuth();
    $input = Response::getJsonInput();
    
    $deviceId = intval($input['device_id'] ?? 0);
    
    $db = getDatabase('devices');
    
    // Get device first
    $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
    $stmt->bindValue(1, $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $device = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$device) {
        Response::error('Device not found', 404);
    }
    
    // Remove from server
    // removePeerFromServer($device['server_id'], $device['public_key']);
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM user_devices WHERE id = ?");
    $stmt->bindValue(1, $deviceId, SQLITE3_INTEGER);
    $stmt->execute();
    
    Response::success([], 'Device deleted');
}

/**
 * Switch device to different server
 */
function switchServer() {
    global $SERVER_INFO, $SERVER_NAMES;
    
    Response::requireMethod('POST');
    $user = Auth::requireAuth();
    $input = Response::getJsonInput();
    
    $deviceId = intval($input['device_id'] ?? 0);
    $newServerId = intval($input['server_id'] ?? 0);
    
    // Validate server
    if (!isset($SERVER_INFO[$newServerId])) {
        Response::error('Invalid server', 400);
    }
    
    $server = $SERVER_INFO[$newServerId];
    $vipInfo = getVipInfo($user['email']);
    
    // Check VIP access
    if ($server['vip_only']) {
        if (!$vipInfo) {
            Response::error('VIP access required', 403);
        }
        if (isset($server['dedicated_to']) && $server['dedicated_to'] !== strtolower($user['email'])) {
            Response::error('This server is dedicated to another user', 403);
        }
    }
    
    $db = getDatabase('devices');
    
    // Get device
    $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
    $stmt->bindValue(1, $deviceId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $device = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$device) {
        Response::error('Device not found', 404);
    }
    
    // Get new IP for new server
    $newIp = getNextClientIp($newServerId);
    
    // Update device
    $stmt = $db->prepare("UPDATE user_devices SET server_id = ?, server_name = ?, client_ip = ? WHERE id = ?");
    $stmt->bindValue(1, $newServerId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $SERVER_NAMES[$newServerId], SQLITE3_TEXT);
    $stmt->bindValue(3, $newIp, SQLITE3_TEXT);
    $stmt->bindValue(4, $deviceId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Generate new config
    $config = generateWgConfig($newServerId, $device['private_key'], $newIp);
    
    Response::success([
        'server_name' => $SERVER_NAMES[$newServerId],
        'config_filename' => $SERVER_NAMES[$newServerId] . '.conf',
        'config_content' => $config,
        'client_ip' => $newIp,
        'server_info' => [
            'display' => $server['display'],
            'features' => $server['features'],
            'restrictions' => $server['restrictions']
        ]
    ], 'Server switched successfully');
}

/**
 * Get database connection
 */
function getDatabase($name) {
    $dbPath = __DIR__ . '/../../data/' . $name . '.db';
    return new SQLite3($dbPath);
}
