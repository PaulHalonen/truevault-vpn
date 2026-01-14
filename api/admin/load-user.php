<?php
/**
 * Admin Terminal - Load User System
 * 
 * Loads complete user VPN system information for admin troubleshooting
 * Requires admin authentication
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

// Verify admin role
if (!in_array($admin['role'], ['admin', 'super_admin'])) {
    Response::error('Admin access required', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$userEmail = $data['email'] ?? null;

if (!$userEmail) {
    Response::error('User email required', 400);
}

// Load target user
$user = Database::queryOne('users',
    "SELECT id, email, status, role, created_at, last_login 
     FROM users 
     WHERE email = ?",
    [$userEmail]
);

if (!$user) {
    Response::error('User not found', 404);
}

$userId = $user['id'];

// Get subscription
$subscription = Database::queryOne('subscriptions',
    "SELECT * FROM subscriptions 
     WHERE user_id = ? AND status = 'active'
     ORDER BY created_at DESC LIMIT 1",
    [$userId]
);

// Get all devices
$devices = Database::query('devices',
    "SELECT d.*, ud.peer_id, ud.vpn_ip, ud.status as device_status,
            s.name as server_name, s.location as server_location
     FROM user_devices d
     LEFT JOIN user_peers ud ON d.device_id = ud.device_id
     LEFT JOIN vpn_servers s ON ud.server_id = s.id
     WHERE d.user_id = ?
     ORDER BY d.added_at DESC",
    [$userId]
);

// Get discovered devices (from network scanner)
$discoveredDevices = Database::query('devices',
    "SELECT * FROM discovered_devices
     WHERE user_id = ?
     ORDER BY discovered_at DESC",
    [$userId]
);

// Get user's WireGuard keys
$certificates = Database::queryOne('certificates',
    "SELECT public_key, private_key, created_at 
     FROM user_certificates 
     WHERE user_id = ?
     ORDER BY created_at DESC LIMIT 1",
    [$userId]
);

// Get current server
$currentServer = null;
if ($subscription && count($devices) > 0) {
    // Get most common server from devices
    $serverCounts = [];
    foreach ($devices as $device) {
        $serverName = $device['server_name'] ?? 'None';
        $serverCounts[$serverName] = ($serverCounts[$serverName] ?? 0) + 1;
    }
    arsort($serverCounts);
    $primaryServerName = array_key_first($serverCounts);
    
    $currentServer = Database::queryOne('servers',
        "SELECT * FROM vpn_servers WHERE name = ?",
        [$primaryServerName]
    );
}

// Get recent connection logs
$connectionLogs = Database::query('logs',
    "SELECT * FROM activity_log
     WHERE user_id = ? AND activity_type LIKE '%connection%'
     ORDER BY created_at DESC LIMIT 50",
    [$userId]
);

// Get recent error logs
$errorLogs = Database::query('logs',
    "SELECT * FROM activity_log
     WHERE user_id = ? AND (activity_type LIKE '%error%' OR activity_type LIKE '%failed%')
     ORDER BY created_at DESC LIMIT 20",
    [$userId]
);

// Get bandwidth usage (if available)
$bandwidthUsage = Database::queryOne('usage',
    "SELECT 
        SUM(bytes_sent) as total_sent,
        SUM(bytes_received) as total_received,
        COUNT(*) as connection_count
     FROM connection_stats
     WHERE user_id = ? AND date >= date('now', '-30 days')",
    [$userId]
);

// Get device limits
$deviceLimits = getDeviceLimits($subscription['plan_type'] ?? 'basic');
$currentDeviceCounts = [
    'home_network' => count(array_filter($devices, fn($d) => ($d['category'] ?? 'personal') === 'home_network')),
    'personal' => count(array_filter($devices, fn($d) => ($d['category'] ?? 'personal') === 'personal')),
    'total' => count($devices)
];

// Get port forwards
$portForwards = Database::query('forwards',
    "SELECT * FROM port_forwards
     WHERE user_id = ? AND status = 'active'
     ORDER BY created_at DESC",
    [$userId]
);

// Log admin access
Database::execute('logs',
    "INSERT INTO admin_activity_log 
     (admin_id, admin_email, target_user_id, target_user_email, action, ip_address)
     VALUES (?, ?, ?, ?, 'load_user_system', ?)",
    [
        $admin['id'],
        $admin['email'],
        $userId,
        $userEmail,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]
);

// Return complete user system
Response::success([
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'status' => $user['status'],
        'role' => $user['role'],
        'created_at' => $user['created_at'],
        'last_login' => $user['last_login']
    ],
    'subscription' => $subscription ? [
        'id' => $subscription['id'],
        'plan_type' => $subscription['plan_type'],
        'plan_name' => ucfirst($subscription['plan_type']),
        'status' => $subscription['status'],
        'price' => $subscription['price'],
        'billing_cycle' => $subscription['billing_cycle'],
        'next_billing' => $subscription['next_billing_date'],
        'max_devices' => $deviceLimits['total']
    ] : null,
    'devices' => [
        'active' => $devices,
        'discovered' => $discoveredDevices,
        'counts' => $currentDeviceCounts,
        'limits' => $deviceLimits
    ],
    'server' => $currentServer ? [
        'id' => $currentServer['id'],
        'name' => $currentServer['name'],
        'location' => $currentServer['location'],
        'status' => $currentServer['status'],
        'ip' => $currentServer['ip'],
        'bandwidth_limit' => $currentServer['bandwidth_limit']
    ] : null,
    'certificates' => $certificates ? [
        'public_key' => $certificates['public_key'],
        'has_private_key' => !empty($certificates['private_key']),
        'created_at' => $certificates['created_at']
    ] : null,
    'logs' => [
        'connections' => $connectionLogs ?? [],
        'errors' => $errorLogs ?? []
    ],
    'bandwidth' => $bandwidthUsage ? [
        'sent' => formatBytes($bandwidthUsage['total_sent'] ?? 0),
        'received' => formatBytes($bandwidthUsage['total_received'] ?? 0),
        'total' => formatBytes(($bandwidthUsage['total_sent'] ?? 0) + ($bandwidthUsage['total_received'] ?? 0)),
        'connections' => $bandwidthUsage['connection_count'] ?? 0
    ] : null,
    'port_forwards' => $portForwards ?? [],
    'loaded_at' => date('Y-m-d H:i:s')
]);

/**
 * Get device limits by plan type
 */
function getDeviceLimits($planType) {
    $limits = [
        'basic' => ['home_network' => 3, 'personal' => 3, 'total' => 6],
        'family' => ['home_network' => 5, 'personal' => 5, 'total' => 10],
        'dedicated' => ['home_network' => 9999, 'personal' => 9999, 'total' => 9999],
        'vip_basic' => ['home_network' => 10, 'personal' => 9999, 'total' => 9999],
        'vip_dedicated' => ['home_network' => 9999, 'personal' => 9999, 'total' => 9999]
    ];
    
    return $limits[$planType] ?? $limits['basic'];
}

/**
 * Format bytes to human-readable
 */
function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}
