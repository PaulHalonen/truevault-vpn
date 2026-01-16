<?php
/**
 * TrueVault VPN - Admin Dashboard Stats API
 * 
 * GET - Get dashboard statistics
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify admin token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Get stats from all databases
$usersDb = Database::getInstance('users');
$devicesDb = Database::getInstance('devices');
$serversDb = Database::getInstance('servers');
$billingDb = Database::getInstance('billing');
$mainDb = Database::getInstance('main');

// User stats
$totalUsers = $usersDb->queryValue("SELECT COUNT(*) FROM users") ?? 0;
$activeUsers = $usersDb->queryValue("SELECT COUNT(*) FROM users WHERE status = 'active'") ?? 0;
$trialUsers = $usersDb->queryValue("SELECT COUNT(*) FROM users WHERE status = 'trial'") ?? 0;
$newUsersToday = $usersDb->queryValue(
    "SELECT COUNT(*) FROM users WHERE DATE(created_at) = DATE('now')"
) ?? 0;
$newUsersWeek = $usersDb->queryValue(
    "SELECT COUNT(*) FROM users WHERE created_at >= DATE('now', '-7 days')"
) ?? 0;

// Device stats
$totalDevices = $devicesDb->queryValue("SELECT COUNT(*) FROM devices") ?? 0;
$activeDevices = $devicesDb->queryValue("SELECT COUNT(*) FROM devices WHERE status = 'active'") ?? 0;
$onlineDevices = $devicesDb->queryValue("SELECT COUNT(*) FROM devices WHERE is_online = 1") ?? 0;

// Server stats
$totalServers = $serversDb->queryValue("SELECT COUNT(*) FROM servers") ?? 0;
$activeServers = $serversDb->queryValue("SELECT COUNT(*) FROM servers WHERE status = 'active'") ?? 0;
$serverLoad = $serversDb->queryAll(
    "SELECT id, name, display_name, location, country_code, status, current_clients, max_clients, is_vip_only FROM servers ORDER BY priority"
);

// Billing stats
$activeSubscriptions = $billingDb->queryValue("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'") ?? 0;
$monthlyRevenue = $billingDb->queryValue(
    "SELECT SUM(amount) FROM payments WHERE status = 'completed' AND created_at >= DATE('now', '-30 days')"
) ?? 0;
$todayRevenue = $billingDb->queryValue(
    "SELECT SUM(amount) FROM payments WHERE status = 'completed' AND DATE(created_at) = DATE('now')"
) ?? 0;

// Plan breakdown
$planCounts = $billingDb->queryAll(
    "SELECT plan, COUNT(*) as count FROM subscriptions WHERE status = 'active' GROUP BY plan"
);
$planBreakdown = [];
foreach ($planCounts as $p) {
    $planBreakdown[$p['plan']] = (int)$p['count'];
}

// VIP users
$vipUsers = $mainDb->queryValue("SELECT COUNT(*) FROM vip_users WHERE is_active = 1") ?? 0;

// Recent activity
$recentActivity = Database::getInstance('logs')->queryAll(
    "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10"
);

// Format activity
$activity = array_map(function($a) {
    return [
        'id' => $a['id'],
        'action' => $a['action'],
        'details' => $a['details'],
        'ip' => $a['ip_address'],
        'time' => $a['created_at']
    ];
}, $recentActivity);

echo json_encode([
    'success' => true,
    'stats' => [
        'users' => [
            'total' => (int)$totalUsers,
            'active' => (int)$activeUsers,
            'trial' => (int)$trialUsers,
            'new_today' => (int)$newUsersToday,
            'new_week' => (int)$newUsersWeek,
            'vip' => (int)$vipUsers
        ],
        'devices' => [
            'total' => (int)$totalDevices,
            'active' => (int)$activeDevices,
            'online' => (int)$onlineDevices
        ],
        'servers' => [
            'total' => (int)$totalServers,
            'active' => (int)$activeServers,
            'list' => $serverLoad
        ],
        'billing' => [
            'active_subscriptions' => (int)$activeSubscriptions,
            'monthly_revenue' => round((float)$monthlyRevenue, 2),
            'today_revenue' => round((float)$todayRevenue, 2),
            'plans' => $planBreakdown
        ],
        'recent_activity' => $activity
    ],
    'generated_at' => date('Y-m-d H:i:s')
]);
