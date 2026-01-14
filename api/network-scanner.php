<?php
/**
 * TruthVault Network Scanner API
 * Receives scanned devices from desktop scanner application
 * 
 * Processes discovered devices and provides server recommendations
 * based on device type and bandwidth requirements
 */

require_once '../config/database.php';
require_once '../helpers/auth.php';
require_once '../helpers/response.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require authentication
Auth::require();
$user = Auth::getUser();
$userId = $user['id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    handleDeviceSync($userId);
} else if ($method === 'GET') {
    handleGetDiscoveredDevices($userId);
} else {
    Response::error('Method not allowed', 405);
}

/**
 * Handle syncing devices from scanner
 */
function handleDeviceSync($userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['devices'])) {
        Response::error('Invalid request data', 400);
    }
    
    $devices = $data['devices'];
    
    if (!is_array($devices) || empty($devices)) {
        Response::error('No devices provided', 400);
    }
    
    // Get user's subscription and device limits
    $subscription = Database::queryOne('subscriptions',
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND status = 'active'
         ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    if (!$subscription) {
        Response::error('No active subscription found', 403);
    }
    
    // Get current device counts
    $counts = getCurrentDeviceCounts($userId);
    
    $limits = getDeviceLimits($subscription['plan_type']);
    
    // Process each device
    $devicesAdded = 0;
    $devicesSkipped = 0;
    $recommendations = [];
    
    foreach ($devices as $device) {
        // Determine device category
        $category = getDeviceCategory($device['type']);
        
        // Check limits
        if ($category === 'home_network') {
            if ($counts['home_network'] >= $limits['home_network']) {
                $devicesSkipped++;
                continue;
            }
        } else {
            if ($counts['personal'] >= $limits['personal']) {
                $devicesSkipped++;
                continue;
            }
        }
        
        // Store in discovered_devices table
        $deviceId = $device['id'] ?? 'auto_' . str_replace('.', '_', $device['ip']);
        
        Database::execute('devices',
            "INSERT OR REPLACE INTO discovered_devices 
             (device_id, user_id, ip, mac, hostname, vendor, type, type_name, icon, category, discovered_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))",
            [
                $deviceId, $userId, $device['ip'], $device['mac'],
                $device['hostname'] ?? null, $device['vendor'] ?? 'Unknown',
                $device['type'], $device['type_name'], $device['icon'] ?? '❓',
                $category
            ]
        );
        
        $devicesAdded++;
        
        // Update counts
        if ($category === 'home_network') {
            $counts['home_network']++;
        } else {
            $counts['personal']++;
        }
        
        // Get server recommendation
        $serverRec = getServerRecommendation($device['type'], $subscription['plan_type']);
        $recommendations[] = [
            'device_id' => $deviceId,
            'device_name' => $device['type_name'],
            'device_type' => $device['type'],
            'category' => $category,
            'recommended_server' => $serverRec['name'],
            'reason' => $serverRec['reason']
        ];
    }
    
    Response::success([
        'devices_received' => count($devices),
        'devices_added' => $devicesAdded,
        'devices_skipped' => $devicesSkipped,
        'recommendations' => $recommendations,
        'limits' => [
            'home_network_devices' => [
                'current' => $counts['home_network'],
                'max' => $limits['home_network'],
                'remaining' => max(0, $limits['home_network'] - $counts['home_network'])
            ],
            'personal_devices' => [
                'current' => $counts['personal'],
                'max' => $limits['personal'],
                'remaining' => max(0, $limits['personal'] - $counts['personal'])
            ]
        ]
    ]);
}

/**
 * Get discovered devices for user
 */
function handleGetDiscoveredDevices($userId) {
    $devices = Database::query('devices',
        "SELECT * FROM discovered_devices 
         WHERE user_id = ? 
         ORDER BY discovered_at DESC",
        [$userId]
    );
    
    Response::success([
        'devices' => $devices ?? []
    ]);
}

/**
 * Get current device counts by category
 */
function getCurrentDeviceCounts($userId) {
    // Count home network devices
    $homeCount = Database::queryOne('devices',
        "SELECT COUNT(*) as count FROM discovered_devices 
         WHERE user_id = ? AND category = 'home_network'",
        [$userId]
    );
    
    // Count personal devices  
    $personalCount = Database::queryOne('devices',
        "SELECT COUNT(*) as count FROM discovered_devices 
         WHERE user_id = ? AND category = 'personal'",
        [$userId]
    );
    
    return [
        'home_network' => (int)($homeCount['count'] ?? 0),
        'personal' => (int)($personalCount['count'] ?? 0)
    ];
}

/**
 * Get device limits by plan type
 */
function getDeviceLimits($planType) {
    $limits = [
        'basic' => [
            'home_network' => 3,
            'personal' => 3,
            'total' => 6
        ],
        'family' => [
            'home_network' => 5,
            'personal' => 5,
            'total' => 10
        ],
        'dedicated' => [
            'home_network' => 9999,
            'personal' => 9999,
            'total' => 9999
        ],
        'vip_basic' => [
            'home_network' => 10,
            'personal' => 9999,
            'total' => 9999
        ],
        'vip_dedicated' => [
            'home_network' => 9999,
            'personal' => 9999,
            'total' => 9999
        ]
    ];
    
    return $limits[$planType] ?? $limits['basic'];
}

/**
 * Determine device category (home_network vs personal)
 */
function getDeviceCategory($deviceType) {
    $homeNetworkDevices = [
        'ip_camera', 'camera',
        'gaming_console', 'xbox', 'playstation', 'nintendo',
        'smart_tv', 'streaming',
        'printer', 'nas',
        'router', 'smart_home'
    ];
    
    return in_array($deviceType, $homeNetworkDevices) ? 'home_network' : 'personal';
}

/**
 * Get server recommendation based on device type
 */
function getServerRecommendation($deviceType, $planType) {
    $highBandwidthDevices = ['ip_camera', 'camera', 'gaming_console', 'xbox', 'playstation', 'nintendo'];
    
    // High-bandwidth devices need NY server or dedicated
    if (in_array($deviceType, $highBandwidthDevices)) {
        if ($planType === 'dedicated' || $planType === 'vip_dedicated') {
            return [
                'name' => 'Your Dedicated Server',
                'reason' => 'High-bandwidth device - using your exclusive server'
            ];
        }
        
        return [
            'name' => 'New York',
            'reason' => 'High-bandwidth device requires unlimited bandwidth server'
        ];
    }
    
    // Streaming devices can use Dallas or Toronto
    if ($deviceType === 'smart_tv' || $deviceType === 'streaming') {
        return [
            'name' => 'Dallas',
            'reason' => 'Optimized for streaming, not flagged by Netflix'
        ];
    }
    
    // Personal devices can use any server
    return [
        'name' => 'New York',
        'reason' => 'All-purpose server recommended for best performance'
    ];
}

/**
 * Validate device can access server (bandwidth check)
 */
function validateDeviceServerAccess($deviceType, $serverId) {
    $highBandwidthDevices = ['ip_camera', 'camera', 'gaming_console', 'xbox', 'playstation', 'nintendo'];
    $limitedServers = [3, 4]; // Dallas, Toronto
    
    if (in_array($deviceType, $highBandwidthDevices) && in_array($serverId, $limitedServers)) {
        return [
            'allowed' => false,
            'reason' => 'High-bandwidth devices require New York server or dedicated server. Dallas and Toronto have limited bandwidth.'
        ];
    }
    
    return ['allowed' => true];
}
