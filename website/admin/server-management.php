<?php
/**
 * TrueVault VPN - Server Management Dashboard
 * 
 * Admin interface for managing all VPN servers:
 * - View server list with stats
 * - Health monitoring
 * - Bandwidth tracking
 * - Server configuration
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/ServerManager.php';

// Check admin authentication
if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
    header('Location: /login.php');
    exit;
}

// Get all servers with statistics
$serversWithStats = ServerManager::getAllServersWithStats();

$pageTitle = 'Server Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - TrueVault Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }
        
        .servers-grid {
            display: grid;
            gap: 20px;
        }
        
        .server-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .server-card.vip {
            border: 3px solid #fbbf24;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .server-title {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }
        
        .server-location {
            font-size: 14px;
            color: #666;
            margin-top: 3px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-online {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-offline {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-unknown {
            background: #e5e7eb;
            color: #374151;
        }
        
        .server-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s;
        }
        
        .progress-fill.high {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        
        .server-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .vip-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #fbbf24;
            color: #78350f;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🖥️ Server Management</h1>
                <p style="color: #666; margin-top: 5px;"><?= count($serversWithStats) ?> servers configured</p>
            </div>
            <a href="/admin/" class="btn btn-primary">← Back to Admin</a>
        </div>

        <div class="stats-grid">
            <?php
            $totalUsers = 0;
            $onlineServers = 0;
            $totalBandwidth = 0;
            $avgUptime = 0;
            
            foreach ($serversWithStats as $stat) {
                $totalUsers += $stat['server']['current_users'];
                if ($stat['server']['health_status'] === 'online') $onlineServers++;
                $totalBandwidth += $stat['bandwidth_30d'];
                $avgUptime += $stat['server']['uptime_percentage'];
            }
            
            $avgUptime = count($serversWithStats) > 0 ? round($avgUptime / count($serversWithStats), 1) : 0;
            $bandwidthGB = round($totalBandwidth / 1024 / 1024 / 1024, 2);
            ?>
            
            <div class="stat-card">
                <div class="stat-label">Total Servers</div>
                <div class="stat-value"><?= count($serversWithStats) ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Online Servers</div>
                <div class="stat-value" style="color: #10b981;"><?= $onlineServers ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Active Users</div>
                <div class="stat-value" style="color: #667eea;"><?= $totalUsers ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Bandwidth (30d)</div>
                <div class="stat-value" style="font-size: 24px;"><?= $bandwidthGB ?> GB</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Avg Uptime</div>
                <div class="stat-value" style="font-size: 28px;"><?= $avgUptime ?>%</div>
            </div>
        </div>

        <div class="servers-grid">
            <?php foreach ($serversWithStats as $stat): 
                $server = $stat['server'];
                $isVIP = $server['access_level'] === 'vip';
                $statusClass = 'status-' . ($server['health_status'] ?? 'unknown');
                $loadClass = $stat['load_percentage'] > 80 ? 'high' : '';
            ?>
            <div class="server-card <?= $isVIP ? 'vip' : '' ?>">
                <div class="server-header">
                    <div>
                        <div class="server-title">
                            <?= htmlspecialchars($server['name']) ?>
                            <?php if ($isVIP): ?>
                                <span class="vip-badge">⭐ VIP</span>
                            <?php endif; ?>
                        </div>
                        <div class="server-location"><?= htmlspecialchars($server['location']) ?></div>
                    </div>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= strtoupper($server['health_status'] ?? 'unknown') ?>
                    </span>
                </div>
                
                <div class="server-info">
                    <div class="info-item">
                        <div class="info-label">IP Address</div>
                        <div class="info-value" style="font-family: monospace;"><?= htmlspecialchars($server['ip_address']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Provider</div>
                        <div class="info-value"><?= htmlspecialchars($server['provider']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Monthly Cost</div>
                        <div class="info-value">$<?= number_format($server['monthly_cost'], 2) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Uptime</div>
                        <div class="info-value"><?= $server['uptime_percentage'] ?>%</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Active Users</div>
                        <div class="info-value"><?= $server['current_users'] ?> / <?= $server['max_users'] ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $loadClass ?>" style="width: <?= $stat['load_percentage'] ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($isVIP && $server['vip_email']): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">VIP User</div>
                        <div class="info-value" style="font-size: 14px;"><?= htmlspecialchars($server['vip_email']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="server-actions">
                    <button class="btn btn-sm btn-success" onclick="healthCheck(<?= $server['id'] ?>)">
                        🔄 Health Check
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="viewLogs(<?= $server['id'] ?>)">
                        📊 View Logs
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editServer(<?= $server['id'] ?>)">
                        ⚙️ Configure
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Health check
        function healthCheck(serverId) {
            if (!confirm('Perform health check on this server?')) return;
            
            fetch('/api/servers/health-check.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
                },
                body: JSON.stringify({ server_id: serverId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Health Check Complete\n\nStatus: ${data.status}\nResponse Time: ${data.response_time}ms\n\n${data.details}`);
                    location.reload();
                } else {
                    alert('Health check failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
            });
        }
        
        // View logs
        function viewLogs(serverId) {
            window.location.href = '/admin/server-logs.php?server_id=' + serverId;
        }
        
        // Edit server
        function editServer(serverId) {
            window.location.href = '/admin/edit-server.php?id=' + serverId;
        }
    </script>
</body>
</html>
