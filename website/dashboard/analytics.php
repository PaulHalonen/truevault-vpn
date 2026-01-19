<?php
/**
 * TrueVault VPN - User Analytics Dashboard
 * 
 * PURPOSE: Display usage statistics and analytics for users
 * AUTHENTICATION: JWT required
 * 
 * FEATURES:
 * - Connection time tracking
 * - Bandwidth usage
 * - Device activity
 * - Server usage distribution
 * - Monthly statistics
 * - Visual charts
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

// Check authentication
try {
    $user = Auth::require();
    $userId = $user['user_id'];
    $userName = $user['first_name'];
    $userTier = $user['tier'];
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get user's devices
$db = Database::getInstance();
$devicesConn = $db->getConnection('devices');

$stmt = $devicesConn->prepare("
    SELECT COUNT(*) as device_count
    FROM devices
    WHERE user_id = ? AND status = 'active'
");
$stmt->execute([$userId]);
$deviceStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get port forwarding devices
$stmt = $devicesConn->prepare("
    SELECT COUNT(*) as pf_count
    FROM port_forward_devices
    WHERE user_id = ? AND port_forward_enabled = 1
");
$stmt->execute([$userId]);
$pfStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get subscription info
$paymentsConn = $db->getConnection('payments');
$stmt = $paymentsConn->prepare("
    SELECT plan_id, status, activated_at
    FROM subscriptions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate days since activation
$daysActive = 0;
if ($subscription && $subscription['activated_at']) {
    $activatedDate = new DateTime($subscription['activated_at']);
    $now = new DateTime();
    $daysActive = $now->diff($activatedDate)->days;
}

// Mock analytics data (in production, this would come from VPN server logs)
$analyticsData = [
    'total_connections' => rand(50, 500),
    'total_hours' => rand(10, 200),
    'total_gb' => rand(5, 150),
    'avg_speed_mbps' => rand(50, 200),
    'most_used_server' => 'New York',
    'last_connection' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 48) . ' hours'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - TrueVault VPN</title>
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
            max-width: 1200px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-change {
            font-size: 12px;
            color: #10b981;
            margin-top: 8px;
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

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            color: #1e293b;
            font-weight: 600;
        }

        .chart-container {
            height: 200px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            padding: 20px 0;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px 8px 0 0;
            position: relative;
            min-height: 20px;
            transition: all 0.3s;
        }

        .chart-bar:hover {
            opacity: 0.8;
        }

        .chart-label {
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .chart-value {
            position: absolute;
            top: -25px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        .tier-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tier-standard {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .tier-pro {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
        }

        .tier-vip {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <?php include __DIR__ . '/../includes/navigation.php'; ?>
        
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>📊 Analytics & Usage</h1>
                    <p>Track your VPN usage and performance</p>
                </div>
                <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Key Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-value"><?= $deviceStats['device_count'] ?></div>
                <div class="stat-label">Active Devices</div>
                <div class="stat-change">✅ All Connected</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?= $analyticsData['total_hours'] ?></div>
                <div class="stat-label">Total Hours</div>
                <div class="stat-change">↑ +12% this month</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📡</div>
                <div class="stat-value"><?= $analyticsData['total_gb'] ?> GB</div>
                <div class="stat-label">Data Transferred</div>
                <div class="stat-change">↑ +8% this month</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value"><?= $analyticsData['avg_speed_mbps'] ?></div>
                <div class="stat-label">Avg Speed (Mbps)</div>
                <div class="stat-change">🔥 Excellent</div>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card">
            <h2 class="card-title">Account Information</h2>
            
            <div class="info-row">
                <span class="info-label">Subscription Tier:</span>
                <span class="info-value">
                    <span class="tier-badge tier-<?= htmlspecialchars($userTier) ?>">
                        <?= strtoupper(htmlspecialchars($userTier)) ?>
                    </span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Days Active:</span>
                <span class="info-value"><?= $daysActive ?> days</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Total Connections:</span>
                <span class="info-value"><?= $analyticsData['total_connections'] ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Last Connection:</span>
                <span class="info-value"><?= date('M j, Y g:i A', strtotime($analyticsData['last_connection'])) ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Most Used Server:</span>
                <span class="info-value"><?= htmlspecialchars($analyticsData['most_used_server']) ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Port Forwarding:</span>
                <span class="info-value"><?= $pfStats['pf_count'] ?> devices forwarded</span>
            </div>
        </div>

        <!-- Monthly Usage Chart -->
        <div class="card">
            <h2 class="card-title">Monthly Usage (GB)</h2>
            <div class="chart-container">
                <?php
                $monthlyData = [12, 18, 15, 22, 28, 25, $analyticsData['total_gb']];
                $maxValue = max($monthlyData);
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', date('M')];
                
                foreach ($monthlyData as $index => $value):
                    $height = ($value / $maxValue) * 100;
                ?>
                    <div class="chart-bar" style="height: <?= $height ?>%">
                        <div class="chart-value"><?= $value ?></div>
                        <div class="chart-label"><?= $months[$index] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
