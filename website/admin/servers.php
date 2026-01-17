<?php
/**
 * TrueVault VPN - Admin Server Management
 * Monitor and manage VPN servers
 */

session_start();
require_once __DIR__ . '/../includes/Database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $serversDb = new Database('servers');
    
    switch ($_POST['action']) {
        case 'get_servers':
            $servers = $serversDb->query("SELECT * FROM servers ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrich with device count from devices.db
            $devicesDb = new Database('devices');
            foreach ($servers as &$server) {
                $stmt = $devicesDb->prepare("SELECT COUNT(*) FROM devices WHERE server_id = ? AND is_active = 1");
                $stmt->execute([$server['id']]);
                $server['active_devices'] = $stmt->fetchColumn();
            }
            
            echo json_encode(['success' => true, 'servers' => $servers]);
            exit;
            
        case 'update_status':
            $serverId = (int)$_POST['server_id'];
            $isActive = (int)$_POST['is_active'];
            
            $stmt = $serversDb->prepare("UPDATE servers SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $serverId]);
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'check_server':
            $serverId = (int)$_POST['server_id'];
            $stmt = $serversDb->prepare("SELECT * FROM servers WHERE id = ?");
            $stmt->execute([$serverId]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$server) {
                echo json_encode(['success' => false, 'error' => 'Server not found']);
                exit;
            }
            
            // Simple ping check
            $ip = $server['ip'];
            $port = $server['port'] ?? 51820;
            
            $pingResult = false;
            $portResult = false;
            
            // Ping check
            $pingOutput = [];
            exec("ping -n 1 -w 2000 " . escapeshellarg($ip), $pingOutput, $pingReturn);
            $pingResult = ($pingReturn === 0);
            
            // Port check
            $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
            if ($socket) {
                $portResult = true;
                fclose($socket);
            }
            
            // Update status
            $status = ($pingResult && $portResult) ? 'online' : 'offline';
            $stmt = $serversDb->prepare("UPDATE servers SET last_status = ?, last_check = datetime('now') WHERE id = ?");
            $stmt->execute([$status, $serverId]);
            
            // Log check
            $logsDb = new Database('logs');
            $logsDb->exec("CREATE TABLE IF NOT EXISTS server_health_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                server_id INTEGER,
                status TEXT,
                ping_ok INTEGER,
                port_ok INTEGER,
                checked_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $stmt = $logsDb->prepare("INSERT INTO server_health_log (server_id, status, ping_ok, port_ok) VALUES (?, ?, ?, ?)");
            $stmt->execute([$serverId, $status, $pingResult ? 1 : 0, $portResult ? 1 : 0]);
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'ping' => $pingResult,
                'port' => $portResult
            ]);
            exit;
            
        case 'get_logs':
            $serverId = (int)$_POST['server_id'];
            $logsDb = new Database('logs');
            
            try {
                $stmt = $logsDb->prepare("SELECT * FROM server_health_log WHERE server_id = ? ORDER BY checked_at DESC LIMIT 50");
                $stmt->execute([$serverId]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'logs' => $logs]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'logs' => []]);
            }
            exit;
            
        case 'save_server':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'ip' => trim($_POST['ip'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'country' => trim($_POST['country'] ?? ''),
                'port' => (int)($_POST['port'] ?? 51820),
                'public_key' => trim($_POST['public_key'] ?? ''),
                'type' => trim($_POST['type'] ?? 'shared'),
                'provider' => trim($_POST['provider'] ?? ''),
                'monthly_cost' => (float)($_POST['monthly_cost'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            if (empty($data['name']) || empty($data['ip'])) {
                echo json_encode(['success' => false, 'error' => 'Name and IP are required']);
                exit;
            }
            
            if ($id) {
                // Update
                $sql = "UPDATE servers SET name=?, ip=?, location=?, country=?, port=?, public_key=?, type=?, provider=?, monthly_cost=?, is_active=? WHERE id=?";
                $stmt = $serversDb->prepare($sql);
                $stmt->execute([
                    $data['name'], $data['ip'], $data['location'], $data['country'],
                    $data['port'], $data['public_key'], $data['type'], $data['provider'],
                    $data['monthly_cost'], $data['is_active'], $id
                ]);
            } else {
                // Insert
                $sql = "INSERT INTO servers (name, ip, location, country, port, public_key, type, provider, monthly_cost, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $serversDb->prepare($sql);
                $stmt->execute([
                    $data['name'], $data['ip'], $data['location'], $data['country'],
                    $data['port'], $data['public_key'], $data['type'], $data['provider'],
                    $data['monthly_cost'], $data['is_active']
                ]);
            }
            
            echo json_encode(['success' => true]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Initial data
$serversDb = new Database('servers');
$servers = $serversDb->query("SELECT * FROM servers ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$totalCost = array_sum(array_column($servers, 'monthly_cost'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management - Admin - TrueVault VPN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .admin-header { background: linear-gradient(90deg, #1a1a2e, #16213e); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 1.5rem; }
        .admin-header a { color: #00d9ff; text-decoration: none; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.08); }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 13px; margin-top: 5px; }
        
        .actions-bar { display: flex; gap: 15px; margin-bottom: 25px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn:hover { opacity: 0.9; }
        
        .servers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        
        .server-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 25px; position: relative; }
        .server-card.offline { border-color: rgba(255,107,107,0.5); }
        .server-card.vip { border-color: rgba(139,92,246,0.5); background: rgba(139,92,246,0.05); }
        
        .server-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .server-name { font-size: 1.2rem; font-weight: 600; }
        .server-location { color: #888; font-size: 13px; margin-top: 4px; }
        
        .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-online { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-offline { background: rgba(255,107,107,0.2); color: #ff6b6b; }
        .status-unknown { background: rgba(255,193,7,0.2); color: #ffc107; }
        
        .server-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-item { }
        .info-label { font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { font-family: monospace; font-size: 13px; }
        
        .server-stats { display: flex; gap: 20px; padding: 15px 0; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 15px; }
        .server-stat { text-align: center; flex: 1; }
        .server-stat-value { font-size: 1.3rem; font-weight: 600; color: #00d9ff; }
        .server-stat-label { font-size: 11px; color: #666; }
        
        .server-actions { display: flex; gap: 10px; }
        .server-actions .btn { flex: 1; }
        
        .vip-badge { position: absolute; top: 15px; right: 15px; background: linear-gradient(135deg, #8b5cf6, #ec4899); color: white; padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; }
        
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1a1a2e; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal h2 { margin-bottom: 20px; font-size: 1.3rem; }
        .modal-close { position: absolute; top: 15px; right: 15px; background: none; border: none; color: #888; font-size: 24px; cursor: pointer; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; color: #888; margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #00d9ff; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .logs-list { max-height: 300px; overflow-y: auto; }
        .log-item { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; display: flex; justify-content: space-between; }
        .log-item .time { color: #666; }
        .log-item.online { color: #00ff88; }
        .log-item.offline { color: #ff6b6b; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🖥️ Server Management</h1>
        <a href="/admin/">← Back to Admin</a>
    </div>
    
    <div class="container">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value" id="totalServers"><?= count($servers) ?></div>
                <div class="stat-label">Total Servers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="onlineServers">-</div>
                <div class="stat-label">Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalUsers">-</div>
                <div class="stat-label">Connected Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$<?= number_format($totalCost, 2) ?></div>
                <div class="stat-label">Monthly Cost</div>
            </div>
        </div>
        
        <div class="actions-bar">
            <button class="btn btn-primary" onclick="openAddModal()">➕ Add Server</button>
            <button class="btn btn-secondary" onclick="checkAllServers()">🔄 Check All</button>
            <button class="btn btn-secondary" onclick="loadServers()">↻ Refresh</button>
        </div>
        
        <div class="servers-grid" id="serversGrid">
            <!-- Servers loaded here -->
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="serverModal">
        <div class="modal">
            <h2 id="modalTitle">Add Server</h2>
            <form id="serverForm" onsubmit="saveServer(event)">
                <input type="hidden" id="serverId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Server Name</label>
                        <input type="text" id="serverName" required placeholder="e.g., New York">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" id="serverLocation" placeholder="e.g., USA East">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>IP Address</label>
                        <input type="text" id="serverIp" required placeholder="e.g., 66.94.103.91">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="number" id="serverPort" value="51820">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Country Code</label>
                        <input type="text" id="serverCountry" placeholder="e.g., US, CA">
                    </div>
                    <div class="form-group">
                        <label>Provider</label>
                        <select id="serverProvider">
                            <option value="contabo">Contabo</option>
                            <option value="flyio">Fly.io</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Type</label>
                        <select id="serverType">
                            <option value="shared">Shared</option>
                            <option value="vip">VIP Only</option>
                            <option value="dedicated">Dedicated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monthly Cost ($)</label>
                        <input type="number" id="serverCost" step="0.01" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>WireGuard Public Key</label>
                    <input type="text" id="serverPublicKey" placeholder="Server public key (optional)">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="serverActive" checked> Active
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Server</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Modal -->
    <div class="modal-overlay" id="logsModal">
        <div class="modal">
            <h2>Server Health Logs</h2>
            <div class="logs-list" id="logsList">
                Loading...
            </div>
            <button class="btn btn-secondary" onclick="closeLogsModal()" style="margin-top: 20px; width: 100%;">Close</button>
        </div>
    </div>
    
    <script>
        let servers = [];
        
        loadServers();
        
        async function loadServers() {
            const formData = new FormData();
            formData.append('action', 'get_servers');
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                servers = data.servers;
                renderServers();
                updateStats();
            }
        }
        
        function renderServers() {
            const grid = document.getElementById('serversGrid');
            
            if (servers.length === 0) {
                grid.innerHTML = '<div style="text-align:center; padding:40px; color:#666;">No servers configured</div>';
                return;
            }
            
            grid.innerHTML = servers.map(s => `
                <div class="server-card ${s.last_status === 'offline' ? 'offline' : ''} ${s.type === 'vip' ? 'vip' : ''}">
                    ${s.type === 'vip' ? '<div class="vip-badge">VIP ONLY</div>' : ''}
                    <div class="server-header">
                        <div>
                            <div class="server-name">${getFlag(s.country)} ${escapeHtml(s.name)}</div>
                            <div class="server-location">${escapeHtml(s.location || 'Unknown location')}</div>
                        </div>
                        <span class="status-badge status-${s.last_status || 'unknown'}">${s.last_status || 'Unknown'}</span>
                    </div>
                    
                    <div class="server-info">
                        <div class="info-item">
                            <div class="info-label">IP Address</div>
                            <div class="info-value">${escapeHtml(s.ip)}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Port</div>
                            <div class="info-value">${s.port || 51820}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Provider</div>
                            <div class="info-value">${escapeHtml(s.provider || 'Unknown')}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Cost</div>
                            <div class="info-value">$${parseFloat(s.monthly_cost || 0).toFixed(2)}/mo</div>
                        </div>
                    </div>
                    
                    <div class="server-stats">
                        <div class="server-stat">
                            <div class="server-stat-value">${s.active_devices || 0}</div>
                            <div class="server-stat-label">Devices</div>
                        </div>
                        <div class="server-stat">
                            <div class="server-stat-value">${s.is_active ? 'Yes' : 'No'}</div>
                            <div class="server-stat-label">Active</div>
                        </div>
                    </div>
                    
                    <div class="server-actions">
                        <button class="btn btn-secondary btn-sm" onclick="checkServer(${s.id})">🔍 Check</button>
                        <button class="btn btn-secondary btn-sm" onclick="viewLogs(${s.id})">📋 Logs</button>
                        <button class="btn btn-secondary btn-sm" onclick="editServer(${s.id})">✏️ Edit</button>
                    </div>
                </div>
            `).join('');
        }
        
        function updateStats() {
            const online = servers.filter(s => s.last_status === 'online').length;
            const totalDevices = servers.reduce((sum, s) => sum + (s.active_devices || 0), 0);
            
            document.getElementById('onlineServers').textContent = online;
            document.getElementById('totalUsers').textContent = totalDevices;
        }
        
        function getFlag(country) {
            const flags = { 'US': '🇺🇸', 'CA': '🇨🇦', 'UK': '🇬🇧', 'DE': '🇩🇪', 'NL': '🇳🇱' };
            return flags[country] || '🌐';
        }
        
        async function checkServer(id) {
            const formData = new FormData();
            formData.append('action', 'check_server');
            formData.append('server_id', id);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                alert(`Server Status: ${data.status.toUpperCase()}\nPing: ${data.ping ? 'OK' : 'Failed'}\nPort: ${data.port ? 'OK' : 'Failed'}`);
                loadServers();
            }
        }
        
        async function checkAllServers() {
            for (const s of servers) {
                await checkServer(s.id);
            }
        }
        
        async function viewLogs(id) {
            document.getElementById('logsModal').classList.add('active');
            document.getElementById('logsList').innerHTML = 'Loading...';
            
            const formData = new FormData();
            formData.append('action', 'get_logs');
            formData.append('server_id', id);
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success && data.logs.length > 0) {
                document.getElementById('logsList').innerHTML = data.logs.map(log => `
                    <div class="log-item ${log.status}">
                        <span>${log.status.toUpperCase()} - Ping: ${log.ping_ok ? '✓' : '✗'}, Port: ${log.port_ok ? '✓' : '✗'}</span>
                        <span class="time">${new Date(log.checked_at).toLocaleString()}</span>
                    </div>
                `).join('');
            } else {
                document.getElementById('logsList').innerHTML = '<div style="padding:20px; text-align:center; color:#666;">No logs available</div>';
            }
        }
        
        function closeLogsModal() {
            document.getElementById('logsModal').classList.remove('active');
        }
        
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Server';
            document.getElementById('serverId').value = '';
            document.getElementById('serverForm').reset();
            document.getElementById('serverModal').classList.add('active');
        }
        
        function editServer(id) {
            const server = servers.find(s => s.id === id);
            if (!server) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Server';
            document.getElementById('serverId').value = server.id;
            document.getElementById('serverName').value = server.name || '';
            document.getElementById('serverIp').value = server.ip || '';
            document.getElementById('serverLocation').value = server.location || '';
            document.getElementById('serverCountry').value = server.country || '';
            document.getElementById('serverPort').value = server.port || 51820;
            document.getElementById('serverProvider').value = server.provider || 'other';
            document.getElementById('serverType').value = server.type || 'shared';
            document.getElementById('serverCost').value = server.monthly_cost || 0;
            document.getElementById('serverPublicKey').value = server.public_key || '';
            document.getElementById('serverActive').checked = server.is_active == 1;
            
            document.getElementById('serverModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('serverModal').classList.remove('active');
        }
        
        async function saveServer(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'save_server');
            
            const id = document.getElementById('serverId').value;
            if (id) formData.append('id', id);
            
            formData.append('name', document.getElementById('serverName').value);
            formData.append('ip', document.getElementById('serverIp').value);
            formData.append('location', document.getElementById('serverLocation').value);
            formData.append('country', document.getElementById('serverCountry').value);
            formData.append('port', document.getElementById('serverPort').value);
            formData.append('provider', document.getElementById('serverProvider').value);
            formData.append('type', document.getElementById('serverType').value);
            formData.append('monthly_cost', document.getElementById('serverCost').value);
            formData.append('public_key', document.getElementById('serverPublicKey').value);
            if (document.getElementById('serverActive').checked) {
                formData.append('is_active', '1');
            }
            
            const response = await fetch('', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                closeModal();
                loadServers();
            } else {
                alert('Error: ' + (data.error || 'Failed to save'));
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    </script>
</body>
</html>
