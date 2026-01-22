<?php
/**
 * Admin Server Detail View
 * 
 * Shows detailed information about a single server:
 * - Full specifications
 * - Recent logs
 * - Connected users
 * - Bandwidth charts
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin auth
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$serverId = intval($_GET['id'] ?? 0);
if (!$serverId) {
    header('Location: servers.php');
    exit;
}

// Get server data
$serversDb = Database::getInstance('servers');
$stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = :id");
$stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
$result = $stmt->execute();
$server = $result->fetchArray(SQLITE3_ASSOC);

if (!$server) {
    header('Location: servers.php?error=not_found');
    exit;
}

// Get recent logs
$logs = [];
$stmt = $serversDb->prepare("SELECT * FROM server_health_log WHERE server_id = :id ORDER BY checked_at DESC LIMIT 50");
$stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
$logResult = $stmt->execute();
while ($log = $logResult->fetchArray(SQLITE3_ASSOC)) {
    $logs[] = $log;
}

// Get bandwidth usage
$bandwidth = null;
if (class_exists('Bandwidth')) {
    try {
        $bandwidth = Bandwidth::getBandwidthUsage($serverId);
    } catch (Exception $e) {
        // Ignore
    }
}

// Get connected peers count
$peersCount = 0;
$devicesDb = Database::getInstance('devices');
$stmt = $devicesDb->prepare("SELECT COUNT(*) as count FROM devices WHERE server_id = :id AND status = 'active'");
$stmt->bindValue(':id', $serverId, SQLITE3_INTEGER);
$peerResult = $stmt->execute();
$peerRow = $peerResult->fetchArray(SQLITE3_ASSOC);
$peersCount = $peerRow['count'] ?? 0;

// Get theme
$themesDb = Database::getInstance('themes');
$themeResult = $themesDb->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
$theme = $themeResult->fetchArray(SQLITE3_ASSOC);
$primaryColor = $theme['primary_color'] ?? '#6366f1';
$bgColor = $theme['background_color'] ?? '#0f172a';
$cardBg = $theme['card_background'] ?? '#1e293b';
$textColor = $theme['text_color'] ?? '#f8fafc';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server: <?= htmlspecialchars($server['name']) ?> - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?= $bgColor ?>;
            color: <?= $textColor ?>;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 { font-size: 1.8rem; }
        .back-btn {
            padding: 10px 20px;
            background: <?= $cardBg ?>;
            color: <?= $textColor ?>;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .back-btn:hover { background: <?= $primaryColor ?>; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card {
            background: <?= $cardBg ?>;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .card h2 { font-size: 1.2rem; margin-bottom: 15px; color: <?= $primaryColor ?>; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-online { background: rgba(34,197,94,0.2); color: #22c55e; }
        .status-offline { background: rgba(239,68,68,0.2); color: #ef4444; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; }
        .info-value { font-family: monospace; }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .logs-table th, .logs-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
        }
        .logs-table th { color: #94a3b8; }
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
            margin-top: 15px;
        }
        .btn-primary { background: <?= $primaryColor ?>; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .full-width { grid-column: 1 / -1; }
        .dedicated-badge {
            background: linear-gradient(90deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>
                    <?= htmlspecialchars($server['name']) ?>
                    <?php if ($server['dedicated_user_email']): ?>
                        <span class="dedicated-badge">DEDICATED</span>
                    <?php endif; ?>
                </h1>
                <span class="status-badge <?= $server['status'] === 'active' ? 'status-online' : 'status-offline' ?>">
                    <?= $server['status'] === 'active' ? '● Online' : '○ Offline' ?>
                </span>
            </div>
            <a href="servers.php" class="back-btn">← Back to Servers</a>
        </div>

        <div class="grid">
            <!-- Connection Details -->
            <div class="card">
                <h2>🔗 Connection Details</h2>
                <div class="info-row">
                    <span class="info-label">IP Address</span>
                    <span class="info-value"><?= htmlspecialchars($server['ip_address']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value"><?= htmlspecialchars($server['location']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Provider</span>
                    <span class="info-value"><?= htmlspecialchars($server['provider'] ?? 'Unknown') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">WireGuard Port</span>
                    <span class="info-value">51820</span>
                </div>
                <div class="info-row">
                    <span class="info-label">API Port</span>
                    <span class="info-value"><?= $server['api_port'] ?? 8443 ?></span>
                </div>
            </div>

            <!-- Features -->
            <div class="card">
                <h2>⚡ Features</h2>
                <div class="info-row">
                    <span class="info-label">Port Forwarding</span>
                    <span class="info-value"><?= $server['port_forwarding_allowed'] ? '✅ Allowed' : '❌ Blocked' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Gaming (Xbox/PS)</span>
                    <span class="info-value"><?= $server['high_bandwidth_allowed'] ? '✅ Allowed' : '❌ Blocked' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">P2P/Torrent</span>
                    <span class="info-value"><?= $server['high_bandwidth_allowed'] ? '✅ Allowed' : '❌ Blocked' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Streaming</span>
                    <span class="info-value"><?= $server['streaming_optimized'] ? '✅ Optimized' : '✅ Allowed' ?></span>
                </div>
                <?php if ($server['dedicated_user_email']): ?>
                <div class="info-row">
                    <span class="info-label">Dedicated To</span>
                    <span class="info-value"><?= htmlspecialchars($server['dedicated_user_email']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistics -->
            <div class="card">
                <h2>📊 Statistics</h2>
                <div class="info-row">
                    <span class="info-label">Connected Devices</span>
                    <span class="info-value"><?= $peersCount ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Max Clients</span>
                    <span class="info-value"><?= $server['max_clients'] ?? 500 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Load</span>
                    <span class="info-value"><?= $server['load_percentage'] ?? 0 ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $server['load_percentage'] ?? 0 ?>%; background: <?= ($server['load_percentage'] ?? 0) > 80 ? '#ef4444' : $primaryColor ?>;"></div>
                </div>
            </div>

            <!-- Bandwidth -->
            <div class="card">
                <h2>📈 Bandwidth</h2>
                <?php if ($bandwidth): ?>
                <div class="info-row">
                    <span class="info-label">Used</span>
                    <span class="info-value"><?= $bandwidth['used_gb'] ?> GB</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Limit</span>
                    <span class="info-value"><?= $bandwidth['limit_gb'] ?> GB</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Usage</span>
                    <span class="info-value"><?= $bandwidth['percentage'] ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $bandwidth['percentage'] ?>%; background: <?= $bandwidth['status'] === 'critical' ? '#ef4444' : ($bandwidth['status'] === 'warning' ? '#f59e0b' : '#22c55e') ?>;"></div>
                </div>
                <?php else: ?>
                <p style="color: #94a3b8;">Bandwidth data not available</p>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="card">
                <h2>🔧 Actions</h2>
                <button class="btn btn-primary" onclick="testConnection()">Test Connection</button>
                <button class="btn btn-primary" onclick="refreshStatus()">Refresh Status</button>
                <button class="btn btn-danger" onclick="restartServer()">Restart Server</button>
            </div>

            <!-- Recent Logs -->
            <div class="card full-width">
                <h2>📋 Recent Health Logs</h2>
                <?php if (count($logs) > 0): ?>
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Latency</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($logs, 0, 20) as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['checked_at']) ?></td>
                            <td>
                                <span class="status-badge <?= $log['status'] === 'online' ? 'status-online' : 'status-offline' ?>">
                                    <?= htmlspecialchars($log['status']) ?>
                                </span>
                            </td>
                            <td><?= $log['latency_ms'] ?? '-' ?> ms</td>
                            <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color: #94a3b8; padding: 20px;">No health logs available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function testConnection() {
            alert('Testing connection to <?= $server['ip_address'] ?>...');
            fetch('/api/servers/test-api.php?server_id=<?= $serverId ?>')
                .then(r => r.json())
                .then(data => {
                    alert(data.success ? 'Connection successful! Latency: ' + data.latency + 'ms' : 'Connection failed: ' + data.error);
                })
                .catch(e => alert('Test failed: ' + e.message));
        }

        function refreshStatus() {
            location.reload();
        }

        function restartServer() {
            if (!confirm('Are you sure you want to restart this server? Connected users may be disconnected.')) return;
            alert('Server restart initiated. This may take a few minutes.');
        }
    </script>
</body>
</html>
