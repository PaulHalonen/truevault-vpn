<?php
/**
 * Admin Dashboard - SQLITE3 VERSION
 * 
 * PURPOSE: Admin control panel with statistics
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

session_start();

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Get statistics
try {
    $usersDb = Database::getInstance('users');
    $devicesDb = Database::getInstance('devices');
    $billingDb = Database::getInstance('billing');
    $serversDb = Database::getInstance('servers');
    
    // User stats
    $totalUsers = $usersDb->querySingle("SELECT COUNT(*) FROM users");
    $activeUsers = $usersDb->querySingle("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $vipUsers = $usersDb->querySingle("SELECT COUNT(*) FROM users WHERE tier = 'vip'");
    $newUsersToday = $usersDb->querySingle("SELECT COUNT(*) FROM users WHERE date(created_at) = date('now')");
    
    // Device stats
    $totalDevices = $devicesDb->querySingle("SELECT COUNT(*) FROM devices");
    $activeDevices = $devicesDb->querySingle("SELECT COUNT(*) FROM devices WHERE status = 'active'");
    
    // Billing stats
    $activeSubscriptions = $billingDb->querySingle("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
    $monthlyRevenue = $billingDb->querySingle("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid' AND date(paid_at) >= date('now', 'start of month')");
    
    // Server stats
    $totalServers = $serversDb->querySingle("SELECT COUNT(*) FROM servers");
    $activeServers = $serversDb->querySingle("SELECT COUNT(*) FROM servers WHERE status = 'active'");
    
    // Recent users
    $recentUsersResult = $usersDb->query("SELECT id, email, first_name, last_name, tier, status, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $recentUsers = [];
    while ($row = $recentUsersResult->fetchArray(SQLITE3_ASSOC)) {
        $recentUsers[] = $row;
    }
    
} catch (Exception $e) {
    logError('Dashboard error: ' . $e->getMessage());
    $totalUsers = $activeUsers = $vipUsers = $newUsersToday = 0;
    $totalDevices = $activeDevices = 0;
    $activeSubscriptions = $monthlyRevenue = 0;
    $totalServers = $activeServers = 0;
    $recentUsers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1a;
            color: #fff;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: rgba(255,255,255,0.03);
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 20px;
        }
        .logo { color: #00d9ff; font-size: 20px; font-weight: bold; margin-bottom: 30px; }
        .nav-item {
            display: block;
            padding: 12px 15px;
            color: #888;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: rgba(0,217,255,0.1); color: #00d9ff; }
        .nav-item.logout { color: #ff6464; margin-top: 20px; }
        .nav-item.logout:hover { background: rgba(255,100,100,0.1); }
        .main {
            margin-left: 250px;
            padding: 30px;
        }
        h1 { font-size: 28px; margin-bottom: 10px; }
        .welcome { color: #888; margin-bottom: 30px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
        }
        .stat-label { color: #888; font-size: 14px; margin-bottom: 5px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #00d9ff; }
        .stat-value.green { color: #00ff88; }
        .stat-value.yellow { color: #ffd93d; }
        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card h2 { font-size: 18px; margin-bottom: 15px; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #888; font-weight: 500; }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-vip { background: rgba(255,215,0,0.2); color: #ffd700; }
        .badge-pro { background: rgba(0,217,255,0.2); color: #00d9ff; }
        .badge-standard { background: rgba(136,136,136,0.2); color: #888; }
        .badge-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .badge-inactive { background: rgba(255,100,100,0.2); color: #ff6464; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">🛡️ TrueVault Admin</div>
        <a href="/admin/dashboard.php" class="nav-item active">📊 Dashboard</a>
        <a href="/admin/users.php" class="nav-item">👥 Users</a>
        <a href="/admin/servers.php" class="nav-item">🖥️ Servers</a>
        <a href="/admin/billing.php" class="nav-item">💳 Billing</a>
        <a href="/admin/settings.php" class="nav-item">⚙️ Settings</a>
        <a href="/admin/logout.php" class="nav-item logout">🚪 Logout</a>
    </div>
    
    <div class="main">
        <h1>Dashboard</h1>
        <p class="welcome">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_email']); ?></p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Users</div>
                <div class="stat-value green"><?php echo number_format($activeUsers); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">VIP Users</div>
                <div class="stat-value yellow"><?php echo number_format($vipUsers); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">New Today</div>
                <div class="stat-value"><?php echo number_format($newUsersToday); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Devices</div>
                <div class="stat-value"><?php echo number_format($activeDevices); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Subscriptions</div>
                <div class="stat-value green"><?php echo number_format($activeSubscriptions); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Monthly Revenue</div>
                <div class="stat-value green">$<?php echo number_format($monthlyRevenue, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Servers</div>
                <div class="stat-value"><?php echo $activeServers; ?>/<?php echo $totalServers; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Tier</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><span class="badge badge-<?php echo $user['tier']; ?>"><?php echo strtoupper($user['tier']); ?></span></td>
                        <td><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentUsers)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#666;">No users yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
