<?php
/**
 * TrueVault VPN - Camera Dashboard
 * Lists all discovered IP cameras with port forwarding and quick setup
 */

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Database connections
$mainDb = new SQLite3(__DIR__ . '/../databases/main.db');
$discoveredDb = new SQLite3(__DIR__ . '/../databases/port_forwards.db');

// Get user data
$stmt = $mainDb->prepare('SELECT * FROM users WHERE id = :id');
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Get all discovered cameras (type = 'ip_camera')
$camerasQuery = $discoveredDb->query("
    SELECT * FROM discovered_devices 
    WHERE user_id = $user_id AND type = 'ip_camera'
    ORDER BY discovered_at DESC
");

$cameras = [];
while ($row = $camerasQuery->fetchArray(SQLITE3_ASSOC)) {
    $cameras[] = $row;
}

// Get port forwarding rules for cameras
$portForwardQuery = $discoveredDb->query("
    SELECT * FROM port_forwarding_rules 
    WHERE user_id = $user_id
");

$portForwards = [];
while ($row = $portForwardQuery->fetchArray(SQLITE3_ASSOC)) {
    $portForwards[$row['device_id']] = $row;
}

// Count cameras by vendor
$vendorCounts = [];
foreach ($cameras as $camera) {
    $vendor = $camera['vendor'] ?: 'Unknown';
    $vendorCounts[$vendor] = ($vendorCounts[$vendor] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Dashboard - TrueVault VPN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: #00d9ff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            background: rgba(0, 217, 255, 0.1);
            transition: 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(0, 217, 255, 0.2);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: #888;
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        
        .cameras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
        }
        
        .camera-card {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid #ff6b6b;
            border-radius: 12px;
            padding: 18px;
            transition: 0.3s;
        }
        
        .camera-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: #00d9ff;
        }
        
        .camera-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .camera-icon {
            font-size: 2.5rem;
        }
        
        .camera-info h3 {
            font-size: 1rem;
            color: #fff;
        }
        
        .camera-ip {
            font-family: monospace;
            color: #00d9ff;
            font-size: 0.9rem;
        }
        
        .camera-details {
            margin-top: 12px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.85rem;
        }
        
        .detail-label {
            color: #888;
        }
        
        .detail-value {
            color: #fff;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-forwarded {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
        }
        
        .status-not-forwarded {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
        }
        
        .camera-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            flex: 1;
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
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .vendor-breakdown {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .vendor-tag {
            padding: 6px 12px;
            background: rgba(0, 217, 255, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>📷 Camera Dashboard</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">🏠 Dashboard</a>
                <a href="discover-devices.php">🔍 Scan Network</a>
                <a href="port-forwarding.php">🔌 Port Forwarding</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </header>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($cameras) ?></div>
                <div class="stat-label">Total Cameras</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count(array_filter($cameras, fn($c) => isset($portForwards[$c['id']]))) ?></div>
                <div class="stat-label">Port Forwarded</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($vendorCounts) ?></div>
                <div class="stat-label">Camera Brands</div>
            </div>
        </div>

        <!-- Vendor Breakdown -->
        <?php if (!empty($vendorCounts)): ?>
        <div class="section">
            <h2>📊 Camera Brands</h2>
            <div class="vendor-breakdown">
                <?php foreach ($vendorCounts as $vendor => $count): ?>
                    <div class="vendor-tag">
                        <?= htmlspecialchars($vendor) ?>: <?= $count ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cameras Grid -->
        <div class="section">
            <h2>📷 Discovered Cameras</h2>
            
            <?php if (empty($cameras)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>No Cameras Found</h3>
                    <p>Run a network scan to discover IP cameras on your network</p>
                    <a href="discover-devices.php" class="btn btn-primary" style="display: inline-block; margin-top: 15px;">
                        Scan Network Now
                    </a>
                </div>
            <?php else: ?>
                <div class="cameras-grid">
                    <?php foreach ($cameras as $camera): ?>
                        <?php
                        $hasPortForward = isset($portForwards[$camera['id']]);
                        $portRule = $hasPortForward ? $portForwards[$camera['id']] : null;
                        ?>
                        <div class="camera-card">
                            <div class="camera-header">
                                <div class="camera-icon"><?= htmlspecialchars($camera['icon']) ?></div>
                                <div class="camera-info">
                                    <h3><?= htmlspecialchars($camera['hostname'] ?: $camera['type_name']) ?></h3>
                                    <div class="camera-ip"><?= htmlspecialchars($camera['ip']) ?></div>
                                </div>
                            </div>
                            
                            <div class="camera-details">
                                <div class="detail-row">
                                    <span class="detail-label">Vendor:</span>
                                    <span class="detail-value"><?= htmlspecialchars($camera['vendor']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">MAC Address:</span>
                                    <span class="detail-value"><?= htmlspecialchars($camera['mac']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Port Forwarding:</span>
                                    <span class="detail-value">
                                        <?php if ($hasPortForward): ?>
                                            <span class="status-badge status-forwarded">
                                                ✓ Port <?= $portRule['external_port'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-not-forwarded">Not Setup</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if (!empty($camera['open_ports'])): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Open Ports:</span>
                                        <span class="detail-value">
                                            <?php
                                            $ports = json_decode($camera['open_ports'], true);
                                            echo implode(', ', array_column($ports, 'port'));
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="camera-actions">
                                <?php if (!$hasPortForward): ?>
                                    <button class="btn btn-primary" onclick="setupPortForward('<?= $camera['id'] ?>', '<?= htmlspecialchars($camera['ip']) ?>')">
                                        🔌 Setup Access
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" onclick="testConnection('<?= htmlspecialchars($camera['ip']) ?>')">
                                        🔍 Test
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function setupPortForward(deviceId, ip) {
            // Redirect to port forwarding page with device pre-selected
            window.location.href = `port-forwarding.php?device=${deviceId}`;
        }

        function testConnection(ip) {
            // Simple connection test
            alert(`Testing connection to ${ip}...\n\nThis feature will check if the camera is accessible through the VPN.`);
            
            // TODO: Implement actual connection test via AJAX
            // fetch(`/api/test-connection.php?ip=${ip}`)
            //     .then(r => r.json())
            //     .then(data => {
            //         if (data.success) {
            //             alert('✅ Camera is accessible!');
            //         } else {
            //             alert('❌ Cannot reach camera');
            //         }
            //     });
        }
    </script>
</body>
</html>
