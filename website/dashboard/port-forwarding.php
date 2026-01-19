<?php
/**
 * TrueVault VPN - Port Forwarding Dashboard
 * 
 * PURPOSE: Manage port forwarding for home network devices
 * AUTHENTICATION: JWT required
 * 
 * FEATURES:
 * - View discovered devices from network scanner
 * - Enable/disable port forwarding per device
 * - Download network scanner tool
 * - Sync discovered devices
 * - Device categorization (cameras, printers, gaming, etc.)
 * 
 * WORKFLOW:
 * 1. User downloads network scanner tool
 * 2. Runs scanner on home network
 * 3. Scanner discovers devices
 * 4. Scanner syncs devices to TrueVault
 * 5. User manages port forwarding here
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
    $userEmail = $user['email'];
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}

// Get user's discovered devices
$db = Database::getInstance();
$devicesConn = $db->getConnection('devices');

$stmt = $devicesConn->prepare("
    SELECT device_id, device_name, ip_address, mac_address, device_type, 
           vendor, open_ports, hostname, port_forward_enabled, discovered_at
    FROM port_forward_devices
    WHERE user_id = ?
    ORDER BY discovered_at DESC
");
$stmt->execute([$userId]);
$discoveredDevices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Forwarding - TrueVault VPN</title>
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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            color: white;
        }

        .info-card h2 {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .info-card p {
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .info-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-step {
            background: rgba(255, 255, 255, 0.1);
            padding: 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .info-step-num {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .info-step-text {
            font-size: 14px;
            opacity: 0.9;
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

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .device-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }

        .device-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }

        .device-card.forwarding-enabled {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);
        }

        .device-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .device-icon {
            font-size: 32px;
        }

        .device-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .device-info p {
            font-size: 13px;
            color: #64748b;
        }

        .device-details {
            margin: 16px 0;
            padding: 12px;
            background: white;
            border-radius: 8px;
            font-size: 13px;
        }

        .device-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .device-detail:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            color: #1e293b;
            font-weight: 600;
            font-family: monospace;
        }

        .device-ports {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }

        .port-badge {
            padding: 4px 8px;
            background: #e0e7ff;
            color: #3730a3;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .device-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e0;
            transition: 0.3s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #10b981;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
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
                    <h1>🔌 Port Forwarding</h1>
                    <p>Access your home devices remotely through VPN</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="/dashboard/my-devices.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="info-card">
            <h2>🚀 How to Set Up Port Forwarding</h2>
            <p>Use our network scanner tool to automatically discover devices on your home network, then enable port forwarding with one click!</p>
            
            <div class="info-steps">
                <div class="info-step">
                    <div class="info-step-num">1️⃣</div>
                    <div class="info-step-text">Download Scanner Tool</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">2️⃣</div>
                    <div class="info-step-text">Run on Home Network</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">3️⃣</div>
                    <div class="info-step-text">Devices Auto-Sync</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">4️⃣</div>
                    <div class="info-step-text">Enable Forwarding</div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="downloadScanner()">
                    ⬇️ Download Network Scanner
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= count($discoveredDevices) ?></div>
                <div class="stat-label">Discovered Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($discoveredDevices, fn($d) => $d['port_forward_enabled'])) ?></div>
                <div class="stat-label">Port Forwarding Enabled</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count(array_filter($discoveredDevices, fn($d) => $d['device_type'] === 'ip_camera')) ?></div>
                <div class="stat-label">IP Cameras</div>
            </div>
        </div>

        <!-- Discovered Devices -->
        <div class="card">
            <h2 class="card-title">📱 Discovered Devices</h2>
            
            <?php if (count($discoveredDevices) > 0): ?>
                <div class="devices-grid" id="devices-grid">
                    <?php foreach ($discoveredDevices as $device): ?>
                        <div class="device-card <?= $device['port_forward_enabled'] ? 'forwarding-enabled' : '' ?>" id="device-<?= $device['device_id'] ?>">
                            <div class="device-header">
                                <div class="device-icon">
                                    <?php
                                    $icons = [
                                        'ip_camera' => '📷',
                                        'printer' => '🖨️',
                                        'gaming' => '🎮',
                                        'smart_home' => '🏠',
                                        'streaming' => '📺',
                                        'router' => '📶',
                                        'computer' => '💻',
                                        'server' => '🖥️',
                                        'device' => '📱',
                                        'unknown' => '❓'
                                    ];
                                    echo $icons[$device['device_type']] ?? '📱';
                                    ?>
                                </div>
                                <div class="device-info">
                                    <h3><?= htmlspecialchars($device['device_name'] ?? $device['hostname'] ?? 'Unknown Device') ?></h3>
                                    <p><?= htmlspecialchars($device['vendor'] ?? 'Unknown Vendor') ?></p>
                                </div>
                            </div>

                            <div class="device-details">
                                <div class="device-detail">
                                    <span class="detail-label">IP Address:</span>
                                    <span class="detail-value"><?= htmlspecialchars($device['ip_address']) ?></span>
                                </div>
                                <div class="device-detail">
                                    <span class="detail-label">MAC Address:</span>
                                    <span class="detail-value"><?= htmlspecialchars($device['mac_address']) ?></span>
                                </div>
                                <?php if (!empty($device['open_ports'])): ?>
                                    <div class="device-detail">
                                        <span class="detail-label">Open Ports:</span>
                                        <div class="device-ports">
                                            <?php
                                            $ports = json_decode($device['open_ports'], true) ?? [];
                                            foreach ($ports as $port):
                                            ?>
                                                <span class="port-badge"><?= htmlspecialchars($port['port']) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="device-actions">
                                <label class="toggle-switch">
                                    <input 
                                        type="checkbox" 
                                        <?= $device['port_forward_enabled'] ? 'checked' : '' ?>
                                        onchange="togglePortForward(<?= $device['device_id'] ?>, this.checked)"
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                                <span style="flex: 1; color: #64748b; font-size: 14px;">
                                    <?= $device['port_forward_enabled'] ? '✅ Forwarding Enabled' : '🔴 Forwarding Disabled' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>No Devices Discovered Yet</h3>
                    <p>Download and run the network scanner tool to discover devices on your home network</p>
                    <button class="btn btn-primary" onclick="downloadScanner()" style="margin-top: 16px;">
                        ⬇️ Download Scanner
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');

        if (!JWT_TOKEN) {
            window.location.href = '/auth/login.php';
        }

        function downloadScanner() {
            // TODO: Implement scanner download
            alert('Scanner download coming soon! For now, contact support to get the scanner tool.');
        }

        async function togglePortForward(deviceId, enabled) {
            try {
                const response = await fetch('/api/port-forwarding/toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({
                        device_id: deviceId,
                        enabled: enabled
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Update UI
                    const card = document.getElementById('device-' + deviceId);
                    if (enabled) {
                        card.classList.add('forwarding-enabled');
                    } else {
                        card.classList.remove('forwarding-enabled');
                    }
                    
                    // Update stats
                    location.reload();
                } else {
                    alert('❌ Error: ' + (result.error || 'Failed to toggle port forwarding'));
                    // Revert checkbox
                    location.reload();
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
                location.reload();
            }
        }
    </script>
</body>
</html>
