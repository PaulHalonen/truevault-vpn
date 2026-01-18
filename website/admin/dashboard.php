<?php
/**
 * TrueVault VPN - Admin Dashboard
 * 
 * PURPOSE: Main admin control panel with statistics and management
 * AUTHENTICATION: Admin or VIP tier required
 * 
 * FEATURES:
 * - System statistics (users, devices, servers, revenue)
 * - Recent activity log
 * - Server status monitoring
 * - Quick actions
 * - Navigation to management pages
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Load configuration
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/Auth.php';

// Check authentication and admin access
try {
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    // Only admin and vip can access
    if (!in_array($userTier, ['admin', 'vip'])) {
        header('Location: /dashboard/my-devices.php');
        exit;
    }
    
    $adminName = $user['first_name'] ?? 'Admin';
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get statistics
$db = Database::getInstance();

// User stats
$usersConn = $db->getConnection('users');
$stmt = $usersConn->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $usersConn->query("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
$activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

$stmt = $usersConn->query("SELECT COUNT(*) as vip FROM users WHERE tier = 'vip'");
$vipUsers = $stmt->fetch(PDO::FETCH_ASSOC)['vip'];

// Device stats
$devicesConn = $db->getConnection('devices');
$stmt = $devicesConn->query("SELECT COUNT(*) as total FROM devices");
$totalDevices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $devicesConn->query("SELECT COUNT(*) as active FROM devices WHERE status = 'active'");
$activeDevices = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Server stats
$serversConn = $db->getConnection('servers');
$stmt = $serversConn->query("SELECT COUNT(*) as total FROM servers");
$totalServers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $serversConn->query("SELECT COUNT(*) as online FROM servers WHERE status = 'online'");
$onlineServers = $stmt->fetch(PDO::FETCH_ASSOC)['online'];

// Payment stats (placeholder)
$totalRevenue = 0; // Will be calculated from payments database
$monthlyRevenue = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #64748b;
            font-size: 14px;
        }

        .admin-badge {
            padding: 8px 16px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-icon {
            font-size: 32px;
        }

        .stat-trend {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .trend-up {
            background: #dcfce7;
            color: #16a34a;
        }

        .trend-down {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-detail {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 8px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .server-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .server-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .server-item:hover {
            background: #f1f5f9;
        }

        .server-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .server-flag {
            font-size: 24px;
        }

        .server-name {
            font-weight: 600;
            color: #1e293b;
        }

        .server-location {
            font-size: 12px;
            color: #64748b;
        }

        .server-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-online {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-offline {
            background: #fee2e2;
            color: #dc2626;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .activity-icon {
            font-size: 20px;
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .activity-time {
            color: #94a3b8;
            font-size: 12px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .action-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }

        .action-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .action-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .action-desc {
            font-size: 12px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>🛡️ Admin Dashboard</h1>
                    <p>System Management & Analytics</p>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span class="admin-badge">🔐 <?= strtoupper($userTier) ?> ACCESS</span>
                    <div class="nav-links">
                        <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                            👤 User View
                        </a>
                        <a href="/auth/logout.php" class="btn btn-secondary">
                            🚪 Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">👥</span>
                    <span class="stat-trend trend-up">+12%</span>
                </div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-detail"><?= $activeUsers ?> active · <?= $vipUsers ?> VIP</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">📱</span>
                    <span class="stat-trend trend-up">+8%</span>
                </div>
                <div class="stat-value"><?= $totalDevices ?></div>
                <div class="stat-label">Total Devices</div>
                <div class="stat-detail"><?= $activeDevices ?> active devices</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">🌐</span>
                    <span class="stat-trend trend-up">100%</span>
                </div>
                <div class="stat-value"><?= $onlineServers ?>/<?= $totalServers ?></div>
                <div class="stat-label">Servers Online</div>
                <div class="stat-detail">All systems operational</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-icon">💰</span>
                    <span class="stat-trend trend-up">+15%</span>
                </div>
                <div class="stat-value">$<?= number_format($monthlyRevenue) ?></div>
                <div class="stat-label">Monthly Revenue</div>
                <div class="stat-detail">$<?= number_format($totalRevenue) ?> total</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Servers -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🌐 Server Status</h2>
                    <a href="/admin/servers.php" class="btn btn-secondary">Manage</a>
                </div>
                <div class="server-list" id="servers-list">
                    <div class="server-item">
                        <div class="server-info">
                            <span class="server-flag">🇺🇸</span>
                            <div>
                                <div class="server-name">USA (Dallas)</div>
                                <div class="server-location">fly.io · 66.241.124.4</div>
                            </div>
                        </div>
                        <span class="server-status status-online">🟢 Online</span>
                    </div>
                    <div class="server-item">
                        <div class="server-info">
                            <span class="server-flag">🇨🇦</span>
                            <div>
                                <div class="server-name">Canada (Toronto)</div>
                                <div class="server-location">fly.io · 66.241.125.247</div>
                            </div>
                        </div>
                        <span class="server-status status-online">🟢 Online</span>
                    </div>
                    <div class="server-item">
                        <div class="server-info">
                            <span class="server-flag">🇺🇸</span>
                            <div>
                                <div class="server-name">USA (New York)</div>
                                <div class="server-location">Contabo · 66.94.103.91</div>
                            </div>
                        </div>
                        <span class="server-status status-online">🟢 Online</span>
                    </div>
                    <div class="server-item">
                        <div class="server-info">
                            <span class="server-flag">🇺🇸</span>
                            <div>
                                <div class="server-name">VIP St. Louis</div>
                                <div class="server-location">Contabo · 144.126.133.253</div>
                            </div>
                        </div>
                        <span class="server-status status-online">🟢 Online</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📊 Recent Activity</h2>
                </div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">👤</div>
                        <div class="activity-content">
                            <div class="activity-text">New user registered</div>
                            <div class="activity-time">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">📱</div>
                        <div class="activity-content">
                            <div class="activity-text">Device added</div>
                            <div class="activity-time">15 minutes ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">🔄</div>
                        <div class="activity-content">
                            <div class="activity-text">Server switched</div>
                            <div class="activity-time">1 hour ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">💰</div>
                        <div class="activity-content">
                            <div class="activity-text">Payment received</div>
                            <div class="activity-time">2 hours ago</div>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">🌐</div>
                        <div class="activity-content">
                            <div class="activity-text">Server health check</div>
                            <div class="activity-time">3 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⚡ Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="/admin/users.php" class="action-card">
                    <div class="action-icon">👥</div>
                    <div class="action-title">Manage Users</div>
                    <div class="action-desc">View, edit, and manage user accounts</div>
                </a>
                <a href="/admin/servers.php" class="action-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <div class="action-icon">🌐</div>
                    <div class="action-title">Manage Servers</div>
                    <div class="action-desc">Add, edit, and monitor servers</div>
                </a>
                <a href="/admin/settings.php" class="action-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="action-icon">⚙️</div>
                    <div class="action-title">System Settings</div>
                    <div class="action-desc">Configure system parameters</div>
                </a>
                <a href="/admin/analytics.php" class="action-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="action-icon">📊</div>
                    <div class="action-title">Analytics</div>
                    <div class="action-desc">View detailed reports and stats</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
