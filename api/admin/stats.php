<?php
/**
 * Admin Statistics API
 * TrueVault VPN - Admin Dashboard Stats
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verify admin token
$user = verifyAdminToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDatabase('users');
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    // Active users (logged in last 30 days)
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE last_login > datetime('now', '-30 days')");
    $activeUsers = $stmt->fetchColumn();
    
    // VIP users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_vip = 1");
    $vipUsers = $stmt->fetchColumn();
    
    // Get billing stats
    $billingDb = getDatabase('billing');
    
    // Monthly revenue
    $stmt = $billingDb->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed' AND created_at > datetime('now', '-30 days')");
    $monthlyRevenue = $stmt->fetchColumn();
    
    // Active subscriptions
    $stmt = $billingDb->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $activeSubscriptions = $stmt->fetchColumn();
    
    // Recent users
    $stmt = $db->query("SELECT id, email, first_name, last_name, plan, status, is_vip, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Server stats (placeholder - would connect to actual servers)
    $servers = [
        ['id' => 1, 'name' => 'US-East', 'location' => 'New York', 'ip' => '66.94.103.91', 'status' => 'online', 'load' => 45, 'users' => 12],
        ['id' => 2, 'name' => 'US-Central VIP', 'location' => 'St. Louis', 'ip' => '144.126.133.253', 'status' => 'online', 'load' => 12, 'users' => 1],
        ['id' => 3, 'name' => 'US-South', 'location' => 'Dallas', 'ip' => '66.241.124.4', 'status' => 'online', 'load' => 38, 'users' => 8],
        ['id' => 4, 'name' => 'Canada', 'location' => 'Toronto', 'ip' => '66.241.125.247', 'status' => 'online', 'load' => 52, 'users' => 15]
    ];
    
    // Recent activity
    $activityDb = getDatabase('logs');
    $stmt = $activityDb->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'total_users' => (int)$totalUsers,
                'active_users' => (int)$activeUsers,
                'vip_users' => (int)$vipUsers,
                'monthly_revenue' => number_format((float)$monthlyRevenue, 2),
                'active_subscriptions' => (int)$activeSubscriptions
            ],
            'recent_users' => $recentUsers,
            'servers' => $servers,
            'recent_activity' => $recentActivity
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
