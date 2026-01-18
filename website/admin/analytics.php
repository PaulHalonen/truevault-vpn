<?php
/**
 * TrueVault VPN - Admin Analytics Dashboard
 * 
 * PURPOSE: System-wide analytics and statistics for admins
 * AUTHENTICATION: Admin JWT required
 * 
 * DISPLAYS:
 * - Total users by tier
 * - Revenue statistics
 * - Device statistics
 * - Server load distribution
 * - Growth charts
 * - Recent signups
 * - Active subscriptions
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

// Check authentication and admin privilege
try {
    $user = Auth::require();
    if ($user['tier'] !== 'admin') {
        throw new Exception('Admin access required');
    }
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get database instance
$db = Database::getInstance();

// Get user statistics
$usersConn = $db->getConnection('users');
$userStats = $usersConn->query("
    SELECT 
        tier,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM users
    GROUP BY tier
")->fetchAll(PDO::FETCH_ASSOC);

$totalUsers = $usersConn->query("SELECT COUNT(*) as total FROM users")->fetch(PDO::FETCH_ASSOC)['total'];
$activeUsers = $usersConn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['total'];

// Get device statistics
$devicesConn = $db->getConnection('devices');
$totalDevices = $devicesConn->query("SELECT COUNT(*) as total FROM devices")->fetch(PDO::FETCH_ASSOC)['total'];
$activeDevices = $devicesConn->query("SELECT COUNT(*) as total FROM devices WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['total'];

// Get server statistics
$serversConn = $db->getConnection('servers');
$servers = $serversConn->query("SELECT * FROM servers WHERE status = 'active' ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);
$totalServers = count($servers);

// Get payment statistics
$paymentsConn = $db->getConnection('payments');
$activeSubscriptions = $paymentsConn->query("
    SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'
")->fetch(PDO::FETCH_ASSOC)['total'];

$monthlyRevenue = $paymentsConn->query("
    SELECT SUM(amount) as total FROM payments 
    WHERE strftime('%Y-%m', payment_date) = strftime('%Y-%m', 'now')
    AND status = 'completed'
")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent signups
$recentSignups = $usersConn->query("
    SELECT first_name, last_name, email, tier, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get port forwarding statistics
$pfDevices = $devicesConn->query("
    SELECT COUNT(*) as total FROM port_forward_devices WHERE port_forward_enabled = 1
")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calculate tier distribution
$tierDistribution = [];
foreach ($userStats as $stat) {
    $tierDistribution[$stat['tier']] = [
        'total' => $stat['count'],
        'active' => $stat['active_count']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .tier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .tier-card {
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }

        .tier-standard {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .tier-pro {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .tier-vip {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #78350f;
        }

        .tier-admin {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .tier-count {
            font-size: 32px;
            font-weight: 700;
            margin: 8px 0;
        }

        .tier-label {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 14px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-standard { background: #dbeafe; color: #1e40af; }
        .badge-pro { background: #f3e8ff; color: #6b21a8; }
        .badge-vip { background: #fef3c7; color: #92400e; }
        .badge-admin { background: #fee2e2; color: #991b1b; }

        .server-list {
            display: grid;
            gap: 12px;
        }

        .server-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .server-name {
            font-weight: 600;
            color: #1e293b;
        }

        .server-location {
            color: #64748b;
            font-size: 14px;
        }

        .server-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>📊 Admin Analytics</h1>
                    <p>System-wide statistics and insights</p>
                </div>
                <a href="/admin/dashboard.php" class="btn btn-secondary">
                    ← Back to Admin
                </a>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= number_format($totalUsers) ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-subtitle"><?= number_format($activeUsers) ?> active</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-value"><?= number_format($totalDevices) ?></div>
                <div class="stat-label">Total Devices</div>
                <div class="stat-subtitle"><?= number_format($activeDevices) ?> active</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🌐</div>
                <div class="stat-value"><?= $totalServers ?></div>
                <div class="stat-label">VPN Servers</div>
                <div class="stat-subtitle">All regions</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">$<?= number_format($monthlyRevenue, 2) ?></div>
                <div class="stat-label">Monthly Revenue</div>
                <div class="stat-subtitle"><?= $activeSubscriptions ?> subscriptions</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔌</div>
                <div class="stat-value"><?= number_format($pfDevices) ?></div>
                <div class="stat-label">Port Forwarding</div>
                <div class="stat-subtitle">Active devices</div>
            </div>
        </div>

        <!-- Tier Distribution -->
        <div class="card">
            <h2 class="card-title">User Distribution by Tier</h2>
            <div class="tier-grid">
                <div class="tier-card tier-standard">
                    <div class="tier-label">Standard</div>
                    <div class="tier-count"><?= $tierDistribution['standard']['total'] ?? 0 ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">
                        <?= $tierDistribution['standard']['active'] ?? 0 ?> active
                    </div>
                </div>
                <div class="tier-card tier-pro">
                    <div class="tier-label">Pro</div>
                    <div class="tier-count"><?= $tierDistribution['pro']['total'] ?? 0 ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">
                        <?= $tierDistribution['pro']['active'] ?? 0 ?> active
                    </div>
                </div>
                <div class="tier-card tier-vip">
                    <div class="tier-label">VIP</div>
                    <div class="tier-count"><?= $tierDistribution['vip']['total'] ?? 0 ?></div>
                    <div style="font-size: 12px; opacity: 0.9;">
                        <?= $tierDistribution['vip']['active'] ?? 0 ?> active
                    </div>
                </div>
                <div class="tier-card tier-admin">
                    <div class="tier-label">Admin</div>
                    <div class="tier-count"><?= $tierDistribution['admin']['total'] ?? 0 ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">
                        <?= $tierDistribution['admin']['active'] ?? 0 ?> active
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Servers -->
        <div class="card">
            <h2 class="card-title">VPN Server Status</h2>
            <div class="server-list">
                <?php foreach ($servers as $server): ?>
                    <div class="server-item">
                        <div>
                            <div class="server-name"><?= htmlspecialchars($server['name']) ?></div>
                            <div class="server-location">
                                <?= htmlspecialchars($server['location']) ?> - 
                                <?= htmlspecialchars($server['endpoint']) ?>
                            </div>
                        </div>
                        <div class="server-status">
                            ✓ Online
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Signups -->
        <div class="card">
            <h2 class="card-title">Recent Signups</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Tier</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSignups as $signup): ?>
                        <tr>
                            <td><?= htmlspecialchars($signup['first_name'] . ' ' . $signup['last_name']) ?></td>
                            <td><?= htmlspecialchars($signup['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($signup['tier']) ?>">
                                    <?= strtoupper(htmlspecialchars($signup['tier'])) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($signup['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
