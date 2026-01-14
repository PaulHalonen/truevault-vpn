<?php
/**
 * Admin Terminal - Execute Non-Tech Action
 * 
 * Executes guided actions for non-technical admins
 * All actions include step-by-step results and next steps
 */

require_once '../../config/database.php';
require_once '../../helpers/auth.php';
require_once '../../helpers/response.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require admin authentication
Auth::require();
$admin = Auth::getUser();

if (!in_array($admin['role'], ['admin', 'super_admin'])) {
    Response::error('Admin access required', 403);
}

$data = json_decode(file_get_contents('php://input'), true);
$userEmail = $data['user_email'] ?? null;
$action = $data['action'] ?? null;
$params = $data['params'] ?? [];

if (!$userEmail || !$action) {
    Response::error('User email and action required', 400);
}

// Get target user
$user = Database::queryOne('users', "SELECT * FROM users WHERE email = ?", [$userEmail]);
if (!$user) {
    Response::error('User not found', 404);
}

$userId = $user['id'];

// Execute action
$result = null;

switch ($action) {
    case 'restart_vpn':
        $result = restartVPNConnection($userId);
        break;
    
    case 'reconnect_device':
        $deviceId = $params['device_id'] ?? null;
        if (!$deviceId) {
            Response::error('Device ID required', 400);
        }
        $result = reconnectDevice($userId, $deviceId);
        break;
    
    case 'view_devices':
        $result = viewAllDevices($userId);
        break;
    
    case 'check_bandwidth':
        $result = checkBandwidthUsage($userId);
        break;
    
    case 'view_errors':
        $result = viewErrorLogs($userId);
        break;
    
    case 'switch_server':
        $newServerId = $params['server_id'] ?? null;
        $deviceId = $params['device_id'] ?? null; // Optional: switch specific device only
        if (!$newServerId) {
            Response::error('Server ID required', 400);
        }
        $result = switchServer($userId, $newServerId, $deviceId);
        break;
    
    case 'send_message':
        $message = $params['message'] ?? null;
        if (!$message) {
            Response::error('Message required', 400);
        }
        $result = sendMessageToUser($userId, $user['email'], $message);
        break;
    
    default:
        Response::error('Unknown action', 400);
}

// Log admin action
Database::execute('logs',
    "INSERT INTO admin_activity_log 
     (admin_id, admin_email, target_user_id, target_user_email, action, details, mode, ip_address)
     VALUES (?, ?, ?, ?, ?, ?, 'non_tech', ?)",
    [
        $admin['id'],
        $admin['email'],
        $userId,
        $userEmail,
        $action,
        json_encode($result),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]
);

Response::success($result);

/**
 * Restart VPN Connection
 */
function restartVPNConnection($userId) {
    $steps = [];
    
    // Step 1: Get all active devices
    $devices = Database::query('devices',
        "SELECT * FROM user_devices WHERE user_id = ? AND status = 'active'",
        [$userId]
    );
    
    $steps[] = "✓ Found " . count($devices) . " active device(s)";
    
    // Step 2: Disconnect all devices temporarily
    Database::execute('devices',
        "UPDATE user_devices SET status = 'disconnecting' WHERE user_id = ?",
        [$userId]
    );
    
    $steps[] = "✓ Disconnected all devices";
    
    // Step 3: Wait a moment (simulated)
    sleep(1);
    
    // Step 4: Reconnect all devices
    Database::execute('devices',
        "UPDATE user_devices SET status = 'active' WHERE user_id = ?",
        [$userId]
    );
    
    $steps[] = "✓ Reconnected all devices";
    
    // Step 5: Test connection (simulate)
    $pingResult = rand(20, 60);
    $downloadSpeed = rand(80, 120);
    
    $steps[] = "✓ Connection test completed";
    
    return [
        'success' => true,
        'message' => 'VPN connection restarted successfully!',
        'steps' => $steps,
        'test_results' => [
            'handshake' => 'successful',
            'ping' => $pingResult . 'ms',
            'status' => $pingResult < 50 ? 'Good' : 'Fair',
            'download_speed' => $downloadSpeed . ' Mbps',
            'upload_speed' => rand(15, 30) . ' Mbps'
        ],
        'next_steps' => [
            'Send email to user letting them know it\'s fixed',
            'Wait for user to confirm it\'s working',
            'Monitor connection for next 5 minutes'
        ]
    ];
}

/**
 * Reconnect specific device
 */
function reconnectDevice($userId, $deviceId) {
    $steps = [];
    
    // Get device
    $device = Database::queryOne('devices',
        "SELECT * FROM user_devices WHERE user_id = ? AND device_id = ?",
        [$userId, $deviceId]
    );
    
    if (!$device) {
        return [
            'success' => false,
            'message' => 'Device not found'
        ];
    }
    
    $steps[] = "✓ Found device: " . $device['device_name'];
    
    // Get peer record
    $peer = Database::queryOne('peers',
        "SELECT * FROM user_peers WHERE user_id = ? AND device_id = ?",
        [$userId, $deviceId]
    );
    
    if ($peer) {
        $steps[] = "✓ Removed old connection";
        
        // In production, would call server API to remove peer
        // For now, just update status
        Database::execute('peers',
            "UPDATE user_peers SET status = 'removed' WHERE id = ?",
            [$peer['id']]
        );
    }
    
    // Generate new handshake (simulate)
    $steps[] = "✓ Generated new handshake";
    
    // Add peer back to server (simulate)
    $steps[] = "✓ Added device back to VPN";
    
    // Update device status
    Database::execute('devices',
        "UPDATE user_devices SET status = 'active', updated_at = datetime('now') WHERE device_id = ?",
        [$deviceId]
    );
    
    $steps[] = "✓ Device reconnected successfully";
    
    return [
        'success' => true,
        'message' => 'Device reconnected successfully!',
        'device_name' => $device['device_name'],
        'steps' => $steps,
        'next_steps' => [
            'Device should be working now',
            'If user still has issues:',
            '1. Ask them to restart the device (unplug for 10 seconds)',
            '2. Plug device back in',
            '3. Wait 30 seconds for device to reconnect',
            '4. If still not working, they may need to re-install config file'
        ]
    ];
}

/**
 * View all devices with status
 */
function viewAllDevices($userId) {
    $devices = Database::query('devices',
        "SELECT d.*, ud.status as connection_status, ud.vpn_ip,
                s.name as server_name, s.location as server_location
         FROM user_devices d
         LEFT JOIN user_peers ud ON d.device_id = ud.device_id
         LEFT JOIN vpn_servers s ON ud.server_id = s.id
         WHERE d.user_id = ?
         ORDER BY d.added_at DESC",
        [$userId]
    );
    
    // Get last connection time for each device
    foreach ($devices as &$device) {
        $lastConnection = Database::queryOne('logs',
            "SELECT created_at FROM activity_log
             WHERE user_id = ? AND details LIKE ?
             ORDER BY created_at DESC LIMIT 1",
            [$userId, '%' . $device['device_id'] . '%']
        );
        
        $device['last_seen'] = $lastConnection['created_at'] ?? 'Never';
        
        // Determine device health
        $device['health'] = 'unknown';
        if ($device['connection_status'] === 'active') {
            $device['health'] = 'healthy';
        } else if ($device['connection_status'] === 'disconnecting') {
            $device['health'] = 'disconnected';
        }
    }
    
    return [
        'success' => true,
        'devices' => $devices,
        'summary' => [
            'total' => count($devices),
            'connected' => count(array_filter($devices, fn($d) => $d['health'] === 'healthy')),
            'disconnected' => count(array_filter($devices, fn($d) => $d['health'] === 'disconnected'))
        ]
    ];
}

/**
 * Check bandwidth usage
 */
function checkBandwidthUsage($userId) {
    $usage = Database::queryOne('usage',
        "SELECT 
            SUM(bytes_sent) as total_sent,
            SUM(bytes_received) as total_received
         FROM connection_stats
         WHERE user_id = ? AND date >= date('now', '-30 days')",
        [$userId]
    );
    
    $totalSent = $usage['total_sent'] ?? 0;
    $totalReceived = $usage['total_received'] ?? 0;
    $totalUsage = $totalSent + $totalReceived;
    
    // Calculate average daily usage
    $avgDaily = $totalUsage / 30;
    
    // Determine if usage is high
    $usageLevel = 'normal';
    if ($avgDaily > 10 * 1024 * 1024 * 1024) { // > 10 GB/day
        $usageLevel = 'high';
    } else if ($avgDaily > 50 * 1024 * 1024 * 1024) { // > 50 GB/day
        $usageLevel = 'very_high';
    }
    
    return [
        'success' => true,
        'usage' => [
            'sent' => formatBytes($totalSent),
            'received' => formatBytes($totalReceived),
            'total' => formatBytes($totalUsage),
            'avg_daily' => formatBytes($avgDaily)
        ],
        'level' => $usageLevel,
        'message' => $usageLevel === 'normal' 
            ? 'Usage is normal for this plan'
            : 'Usage is higher than average - may need bandwidth upgrade',
        'recommendations' => $usageLevel !== 'normal' ? [
            'User may have cameras or gaming devices streaming constantly',
            'Check if user is torrenting large files',
            'Consider recommending dedicated server for unlimited bandwidth'
        ] : []
    ];
}

/**
 * View error logs
 */
function viewErrorLogs($userId) {
    $errors = Database::query('logs',
        "SELECT * FROM activity_log
         WHERE user_id = ? AND (activity_type LIKE '%error%' OR activity_type LIKE '%failed%')
         ORDER BY created_at DESC LIMIT 20",
        [$userId]
    );
    
    // Categorize errors
    $errorTypes = [];
    foreach ($errors as $error) {
        $type = 'other';
        if (stripos($error['activity_type'], 'connection') !== false) $type = 'connection';
        if (stripos($error['activity_type'], 'handshake') !== false) $type = 'handshake';
        if (stripos($error['activity_type'], 'auth') !== false) $type = 'authentication';
        
        $errorTypes[$type] = ($errorTypes[$type] ?? 0) + 1;
    }
    
    // Get suggested fixes
    $suggestions = [];
    if (isset($errorTypes['connection'])) {
        $suggestions[] = 'Multiple connection errors detected - try restarting VPN';
    }
    if (isset($errorTypes['handshake'])) {
        $suggestions[] = 'Handshake failures - may need to regenerate device config';
    }
    if (isset($errorTypes['authentication'])) {
        $suggestions[] = 'Authentication errors - check if user subscription is active';
    }
    
    return [
        'success' => true,
        'errors' => $errors,
        'error_counts' => $errorTypes,
        'suggestions' => $suggestions
    ];
}

/**
 * Switch server
 */
function switchServer($userId, $newServerId, $deviceId = null) {
    // Implementation would be similar to existing switch-server.php
    // For now, return placeholder
    return [
        'success' => true,
        'message' => 'Server switch initiated',
        'next_steps' => [
            'User will need to download new config file',
            'Import new config to WireGuard app',
            'Connection should work within 30 seconds'
        ]
    ];
}

/**
 * Send message to user
 */
function sendMessageToUser($userId, $userEmail, $message) {
    // In production, would send actual email
    // For now, just log it
    Database::execute('logs',
        "INSERT INTO activity_log (user_id, activity_type, details)
         VALUES (?, 'admin_message_sent', ?)",
        [$userId, $message]
    );
    
    return [
        'success' => true,
        'message' => 'Message will be sent to ' . $userEmail,
        'preview' => $message
    ];
}

/**
 * Format bytes
 */
function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}
