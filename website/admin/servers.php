<?php
/**
 * Admin Server Management Dashboard
 * 
 * Features:
 * - View all VPN servers with status
 * - Monitor health, clients, bandwidth
 * - Test server API connectivity
 * - Restart servers (via API)
 * 
 * @created January 22, 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

// Check admin auth
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php');
    exit;
}

// Get theme
$themesDb = Database::getInstance('themes');
$result = $themesDb->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
$theme = $result->fetchArray(SQLITE3_ASSOC);
if (!$theme) {
    $theme = ['primary_color' => '#6366f1', 'secondary_color' => '#8b5cf6', 'background_color' => '#0f172a', 'text_color' => '#f8fafc'];
}

// Get servers
$serversDb = Database::getInstance('servers');
$servers = [];
$result = $serversDb->query("SELECT * FROM servers ORDER BY id");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $servers[] = $row;
}

// Calculate totals
$totalClients = 0;
$totalCost = 0;
$activeServers = 0;
foreach ($servers as $s) {
    $totalClients += $s['current_clients'] ?? 0;
    $totalCost += $s['monthly_cost'] ?? 0;
    if ($s['status'] === 'active') $activeServers++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?= htmlspecialchars($theme['background_color']) ?>;
            color: <?= htmlspecialchars($theme['text_color']) ?>;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        h1 {
            font-size: 1.8rem;
            background: linear-gradient(90deg, <?= htmlspecialchars($theme['primary_color']) ?>, <?= htmlspecialchars($theme['secondary_color']) ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .back-link {
            color: <?= htmlspecialchars($theme['primary_color']) ?>;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        
        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: <?= htmlspecialchars($theme['primary_color']) ?>;
        }
        .stat-label { color: #94a3b8; font-size: 0.9rem; margin-top: 5px; }
        
        /* Server Grid */
        .servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        .server-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        .server-card:hover {
            border-color: <?= htmlspecialchars($theme['primary_color']) ?>;
            transform: translateY(-2px);
        }
        .server-card.vip {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .server-name {
            font-size: 1.2rem;
            font-weight: 600;
        }
        .server-location {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-offline { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .status-checking { background: rgba(234, 179, 8, 0.2); color: #eab308; }
        
        .server-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label { color: #64748b; font-size: 0.75rem; }
        .info-value { color: #e2e8f0; font-family: monospace; }
        
        .server-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: <?= htmlspecialchars($theme['primary_color']) ?>;
            color: white;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.15); }
        .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .vip-badge {
            background: linear-gradient(90deg, #f59e0b, #d97706);
            color: #000;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 8px;
        }
        
        .api-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8rem;
            display: none;
        }
        .api-result.success { background: rgba(34, 197, 94, 0.1); color: #22c55e; display: block; }
        .api-result.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; display: block; }
        
        /* Progress Bar */
        .progress-bar {
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-fill {
            height: 100%;
            background: <?= htmlspecialchars($theme['primary_color']) ?>;
            transition: width 0.3s;
        }
        .progress-fill.high { background: #ef4444; }
        .progress-fill.medium { background: #eab308; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>🖥️ Server Management</h1>
                <a href="/admin/dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
            <div>
                <button class="btn btn-primary" onclick="refreshAll()">🔄 Refresh All</button>
            </div>
        </header>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= $activeServers ?>/<?= count($servers) ?></div>
                <div class="stat-label">Active Servers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $totalClients ?></div>
                <div class="stat-label">Total Connected Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format($totalCost, 2) ?></div>
                <div class="stat-label">Monthly Server Cost</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="avg-latency">--</div>
                <div class="stat-label">Avg API Latency</div>
            </div>
        </div>
        
        <!-- Server Grid -->
        <div class="servers-grid">
            <?php foreach ($servers as $server): ?>
            <div class="server-card <?= $server['vip_only'] ? 'vip' : '' ?>" id="server-<?= $server['id'] ?>">
                <div class="server-header">
                    <div>
                        <div class="server-name">
                            <?= htmlspecialchars($server['name']) ?>
                            <?php if ($server['vip_only']): ?>
                                <span class="vip-badge">VIP</span>
                            <?php endif; ?>
                        </div>
                        <div class="server-location"><?= htmlspecialchars($server['location']) ?></div>
                    </div>
                    <span class="status-badge status-checking" id="status-<?= $server['id'] ?>">Checking...</span>
                </div>
                
                <div class="server-info">
                    <div class="info-item">
                        <span class="info-label">IP Address</span>
                        <span class="info-value"><?= htmlspecialchars($server['ip_address']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Provider</span>
                        <span class="info-value"><?= htmlspecialchars($server['provider']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">WireGuard Port</span>
                        <span class="info-value"><?= $server['listen_port'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">API Port</span>
                        <span class="info-value"><?= $server['api_port'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Connected Clients</span>
                        <span class="info-value" id="clients-<?= $server['id'] ?>"><?= $server['current_clients'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Monthly Cost</span>
                        <span class="info-value">$<?= number_format($server['monthly_cost'], 2) ?></span>
                    </div>
                </div>
                
                <?php if ($server['dedicated_user_email']): ?>
                <div style="background: rgba(245,158,11,0.1); padding: 8px; border-radius: 6px; margin: 10px 0; font-size: 0.85rem;">
                    🔒 Dedicated to: <strong><?= htmlspecialchars($server['dedicated_user_email']) ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="info-item" style="margin-top: 10px;">
                    <span class="info-label">Load</span>
                    <div class="progress-bar">
                        <div class="progress-fill" id="load-<?= $server['id'] ?>" style="width: <?= $server['load_percentage'] ?>%"></div>
                    </div>
                </div>
                
                <div class="server-actions">
                    <button class="btn btn-primary" onclick="testApi(<?= $server['id'] ?>)">🔍 Test API</button>
                    <button class="btn btn-secondary" onclick="viewPeers(<?= $server['id'] ?>)">👥 Peers</button>
                    <button class="btn btn-secondary" onclick="copyPublicKey(<?= $server['id'] ?>)">🔑 Copy Key</button>
                </div>
                
                <div class="api-result" id="result-<?= $server['id'] ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        const servers = <?= json_encode($servers) ?>;
        
        // Test API connectivity for a server
        async function testApi(serverId) {
            const server = servers.find(s => s.id == serverId);
            const resultDiv = document.getElementById(`result-${serverId}`);
            const statusBadge = document.getElementById(`status-${serverId}`);
            
            resultDiv.className = 'api-result';
            resultDiv.textContent = 'Testing...';
            resultDiv.style.display = 'block';
            statusBadge.className = 'status-badge status-checking';
            statusBadge.textContent = 'Testing...';
            
            const startTime = Date.now();
            
            try {
                const response = await fetch(`/api/servers/test-api.php?server_id=${serverId}`);
                const data = await response.json();
                const latency = Date.now() - startTime;
                
                if (data.success) {
                    resultDiv.className = 'api-result success';
                    resultDiv.textContent = `✓ API Online (${latency}ms) - ${data.server_name || 'OK'}`;
                    statusBadge.className = 'status-badge status-active';
                    statusBadge.textContent = 'Online';
                    
                    if (data.peer_count !== undefined) {
                        document.getElementById(`clients-${serverId}`).textContent = data.peer_count;
                    }
                } else {
                    resultDiv.className = 'api-result error';
                    resultDiv.textContent = `✗ ${data.error || 'API Offline'}`;
                    statusBadge.className = 'status-badge status-offline';
                    statusBadge.textContent = 'Offline';
                }
            } catch (e) {
                resultDiv.className = 'api-result error';
                resultDiv.textContent = `✗ Connection failed: ${e.message}`;
                statusBadge.className = 'status-badge status-offline';
                statusBadge.textContent = 'Error';
            }
        }
        
        // View peers on server
        async function viewPeers(serverId) {
            try {
                const response = await fetch(`/api/servers/list-peers.php?server_id=${serverId}`);
                const data = await response.json();
                
                if (data.success) {
                    alert(`Server has ${data.peer_count} peers connected.\n\n${data.peers.map(p => p.allowed_ips).join('\n')}`);
                } else {
                    alert('Failed to get peers: ' + (data.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }
        
        // Copy server public key
        function copyPublicKey(serverId) {
            const server = servers.find(s => s.id == serverId);
            if (server && server.public_key) {
                navigator.clipboard.writeText(server.public_key);
                alert('Public key copied to clipboard!');
            } else {
                alert('No public key available for this server');
            }
        }
        
        // Refresh all servers
        function refreshAll() {
            servers.forEach(s => testApi(s.id));
        }
        
        // Auto-check all servers on load
        setTimeout(refreshAll, 500);
    </script>
</body>
</html>
