<?php
/**
 * TrueVault VPN - Main User Dashboard
 * Central hub for account management and quick actions
 */

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Database connections
$mainDb = new SQLite3(__DIR__ . '/../databases/main.db');
$devicesDb = new SQLite3(__DIR__ . '/../databases/devices.db');
$billingDb = new SQLite3(__DIR__ . '/../databases/billing.db');

// Get user data
$stmt = $mainDb->prepare('SELECT * FROM users WHERE id = :id');
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Check VIP status (seige235@yahoo.com)
$isVIP = ($user_email === 'seige235@yahoo.com');

// Get user's devices
$devicesQuery = $devicesDb->query("SELECT * FROM user_devices WHERE user_id = $user_id ORDER BY created_at DESC");
$devices = [];
while ($row = $devicesQuery->fetchArray(SQLITE3_ASSOC)) {
    $devices[] = $row;
}
$deviceCount = count($devices);

// Get subscription info
$subscription = $billingDb->querySingle("SELECT * FROM subscriptions WHERE user_id = $user_id", true);

// Determine plan name and price
$planName = 'Free Trial';
$planPrice = '$0.00';
$planStatus = 'Active';
$daysRemaining = 0;

if ($isVIP) {
    $planName = 'VIP';
    $planPrice = 'FREE';
    $planStatus = 'Active';
    $daysRemaining = '∞';
} elseif ($subscription) {
    $planName = ucfirst($subscription['plan_type']);
    $planPrice = '$' . number_format($subscription['amount'], 2);
    $planStatus = ucfirst($subscription['status']);
    
    if ($subscription['next_billing_date']) {
        $nextBilling = strtotime($subscription['next_billing_date']);
        $today = time();
        $daysRemaining = max(0, ceil(($nextBilling - $today) / 86400));
    }
}

// Available servers
$servers = [
    ['name' => 'New York', 'location' => 'US East', 'ip' => '66.94.103.91', 'latency' => '12ms', 'load' => '34%'],
    ['name' => 'Dallas', 'location' => 'US Central', 'ip' => '66.241.124.4', 'latency' => '28ms', 'load' => '67%'],
    ['name' => 'Toronto', 'location' => 'Canada', 'ip' => '66.241.125.247', 'latency' => '45ms', 'load' => '52%'],
];

if ($isVIP) {
    $servers[] = ['name' => 'St. Louis (Dedicated)', 'location' => 'Your Private Server', 'ip' => '144.126.133.253', 'latency' => '8ms', 'load' => '0%', 'vip' => true];
}

// Current server (mock - would come from VPN connection status)
$currentServer = $servers[0];

// Recent activity (mock data - would come from logs)
$recentActivity = [
    ['action' => 'Device Added', 'details' => 'iPhone 14', 'time' => '2 hours ago'],
    ['action' => 'Connected', 'details' => 'New York Server', 'time' => '5 hours ago'],
    ['action' => 'Password Changed', 'details' => 'Security update', 'time' => '1 day ago'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo h1 {
            font-size: 2rem;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-email {
            color: #00d9ff;
            font-size: 0.95rem;
        }
        
        .vip-badge {
            background: linear-gradient(90deg, #ffd700, #ffed4e);
            color: #0f0f1a;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .btn-logout {
            background: rgba(255, 80, 80, 0.15);
            color: #ff5050;
            border: 1px solid rgba(255, 80, 80, 0.4);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .btn-logout:hover { background: rgba(255, 80, 80, 0.25); }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 22px;
        }
        
        .stat-label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-subtext {
            color: #666;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 968px) {
            .main-grid { grid-template-columns: 1fr; }
        }
        
        .section {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 25px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .server-card {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(0, 217, 255, 0.3);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
        }
        
        .server-card.vip {
            border-color: rgba(255, 215, 0, 0.5);
            background: rgba(255, 215, 0, 0.05);
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .server-name {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .server-location {
            color: #888;
            font-size: 0.85rem;
        }
        
        .server-badge {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .server-stats {
            display: flex;
            gap: 20px;
            color: #888;
            font-size: 0.85rem;
        }
        
        .devices-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .device-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .device-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .device-icon {
            font-size: 1.8rem;
        }
        
        .device-name {
            font-weight: 600;
        }
        
        .device-type {
            color: #888;
            font-size: 0.85rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            color: #fff;
        }
        
        .action-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: #00d9ff;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .action-label {
            font-size: 0.9rem;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
        }
        
        .activity-action {
            font-weight: 600;
        }
        
        .activity-details {
            color: #888;
            font-size: 0.85rem;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.8rem;
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 217, 255, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>🛡️ TrueVault VPN</h1>
            </div>
            <div class="user-info">
                <span class="user-email"><?= htmlspecialchars($user_email) ?></span>
                <?php if ($isVIP): ?>
                    <span class="vip-badge">⭐ VIP</span>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">🚪 Logout</a>
            </div>
        </header>

        <!-- Account Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Current Plan</div>
                <div class="stat-value"><?= htmlspecialchars($planName) ?></div>
                <div class="stat-subtext"><?= htmlspecialchars($planPrice) ?>/month</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Account Status</div>
                <div class="stat-value"><?= htmlspecialchars($planStatus) ?></div>
                <div class="stat-subtext">
                    <?php if ($daysRemaining === '∞'): ?>
                        Lifetime Access
                    <?php elseif ($daysRemaining > 0): ?>
                        <?= $daysRemaining ?> days remaining
                    <?php else: ?>
                        Renews today
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Devices</div>
                <div class="stat-value"><?= $deviceCount ?></div>
                <div class="stat-subtext">
                    <?php if ($isVIP): ?>
                        Unlimited allowed
                    <?php else: ?>
                        <?= ($planName === 'Personal' ? '5' : '10') ?> device limit
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Data Usage</div>
                <div class="stat-value">0 GB</div>
                <div class="stat-subtext">This month</div>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Column -->
            <div>
                <!-- Current Server -->
                <div class="section">
                    <h2>🌐 Current Server</h2>
                    <div class="server-card">
                        <div class="server-header">
                            <div>
                                <div class="server-name"><?= $currentServer['name'] ?></div>
                                <div class="server-location"><?= $currentServer['location'] ?></div>
                            </div>
                            <span class="server-badge">CONNECTED</span>
                        </div>
                        <div class="server-stats">
                            <span>📡 <?= $currentServer['ip'] ?></span>
                            <span>⚡ <?= $currentServer['latency'] ?></span>
                            <span>📊 Load: <?= $currentServer['load'] ?></span>
                        </div>
                    </div>
                    <a href="servers.php" class="btn btn-secondary">🔄 Change Server</a>
                </div>

                <!-- Quick Actions -->
                <div class="section" style="margin-top: 20px;">
                    <h2>⚡ Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="add-device.php" class="action-card">
                            <div class="action-icon">➕</div>
                            <div class="action-label">Add Device</div>
                        </a>
                        <a href="cameras.php" class="action-card">
                            <div class="action-icon">📷</div>
                            <div class="action-label">Cameras</div>
                        </a>
                        <a href="port-forwarding.php" class="action-card">
                            <div class="action-icon">🔌</div>
                            <div class="action-label">Port Forward</div>
                        </a>
                        <a href="parental-controls.php" class="action-card">
                            <div class="action-icon">🛡️</div>
                            <div class="action-label">Parental</div>
                        </a>
                        <a href="discover-devices.php" class="action-card">
                            <div class="action-icon">🔍</div>
                            <div class="action-label">Scan Network</div>
                        </a>
                        <a href="settings.php" class="action-card">
                            <div class="action-icon">⚙️</div>
                            <div class="action-label">Settings</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Active Devices -->
                <div class="section">
                    <h2>📱 Your Devices</h2>
                    <?php if (empty($devices)): ?>
                        <div class="empty-state">
                            <p>No devices yet</p>
                            <a href="add-device.php" class="btn btn-primary" style="margin-top: 15px;">
                                ➕ Add Your First Device
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="devices-list">
                            <?php foreach (array_slice($devices, 0, 5) as $device): ?>
                                <div class="device-item">
                                    <div class="device-info">
                                        <span class="device-icon">📱</span>
                                        <div>
                                            <div class="device-name"><?= htmlspecialchars($device['device_name']) ?></div>
                                            <div class="device-type"><?= htmlspecialchars($device['device_type'] ?? 'Unknown') ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($deviceCount > 5): ?>
                            <a href="devices.php" class="btn btn-secondary" style="margin-top: 15px; width: 100%;">
                                View All <?= $deviceCount ?> Devices
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="section" style="margin-top: 20px;">
                    <h2>📊 Recent Activity</h2>
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div>
                                    <div class="activity-action"><?= htmlspecialchars($activity['action']) ?></div>
                                    <div class="activity-details"><?= htmlspecialchars($activity['details']) ?></div>
                                </div>
                                <div class="activity-time"><?= htmlspecialchars($activity['time']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
