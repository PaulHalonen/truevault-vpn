<?php
/**
 * Main User Dashboard
 * Central hub for TrueVault VPN management
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
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
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            color: #fff;
        }
        .header {
            background: rgba(0,0,0,0.3);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .logo { font-size: 24px; font-weight: 700; color: #00d9ff; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-tier { padding: 5px 12px; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .logout-btn { color: #888; text-decoration: none; }
        .logout-btn:hover { color: #ff6464; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        .welcome { margin-bottom: 30px; }
        .welcome h1 { font-size: 28px; margin-bottom: 5px; }
        .welcome p { color: #888; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        .stat-card .value { font-size: 32px; font-weight: 700; color: #00d9ff; }
        .stat-card .label { color: #888; font-size: 14px; margin-top: 5px; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .action-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .action-card:hover {
            border-color: #00d9ff;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,217,255,0.2);
        }
        .action-card .icon { font-size: 40px; margin-bottom: 15px; }
        .action-card h3 { margin-bottom: 10px; }
        .action-card p { color: #888; font-size: 14px; }
        .devices-list { margin-top: 30px; }
        .devices-list h2 { margin-bottom: 20px; }
        .device-item {
            background: rgba(255,255,255,0.03);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .device-item:hover { border-color: rgba(255,255,255,0.1); }
        .device-info { display: flex; align-items: center; gap: 15px; }
        .device-info .icon { font-size: 24px; }
        .device-info .name { font-weight: 600; }
        .device-info .ip { color: #00d9ff; font-family: monospace; font-size: 13px; }
        .device-status { padding: 5px 10px; border-radius: 5px; font-size: 12px; }
        .device-status.active { background: rgba(0,255,136,0.15); color: #00ff88; }
        .device-status.inactive { background: rgba(255,100,100,0.15); color: #ff6464; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state .icon { font-size: 60px; margin-bottom: 15px; }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #0f0f1a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🛡️ TrueVault VPN</div>
        <div class="user-info">
            <span id="userEmail">Loading...</span>
            <span class="user-tier" id="userTier">-</span>
            <a href="#" class="logout-btn" onclick="logout()">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h1>Welcome back!</h1>
            <p>Manage your VPN devices and settings</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📱</div>
                <div class="value" id="deviceCount">0</div>
                <div class="label">Active Devices</div>
            </div>
            <div class="stat-card">
                <div class="icon">🌐</div>
                <div class="value" id="serverCount">4</div>
                <div class="label">Available Servers</div>
            </div>
            <div class="stat-card">
                <div class="icon">🔒</div>
                <div class="value" id="rulesCount">0</div>
                <div class="label">Port Forwards</div>
            </div>
            <div class="stat-card">
                <div class="icon">💳</div>
                <div class="value" id="subscriptionStatus">-</div>
                <div class="label">Subscription</div>
            </div>
        </div>
        
        <div class="actions-grid">
            <a href="/dashboard/setup-device.php" class="action-card">
                <div class="icon">➕</div>
                <h3>Add New Device</h3>
                <p>Setup a new phone, laptop, or router with 1-click configuration</p>
            </a>
            <a href="/dashboard/port-forwarding.php" class="action-card">
                <div class="icon">🔀</div>
                <h3>Port Forwarding</h3>
                <p>Access your cameras, servers, and devices remotely</p>
            </a>
            <a href="/dashboard/discover-devices.php" class="action-card">
                <div class="icon">📡</div>
                <h3>Network Scanner</h3>
                <p>Discover cameras and devices on your home network</p>
            </a>
            <a href="/dashboard/account.php" class="action-card">
                <div class="icon">⚙️</div>
                <h3>Account Settings</h3>
                <p>Manage your subscription, password, and preferences</p>
            </a>
        </div>
        
        <div class="devices-list">
            <h2>Your Devices</h2>
            <div id="devicesList">
                <div class="empty-state">
                    <div class="icon">📱</div>
                    <p>No devices yet</p>
                    <br>
                    <a href="/dashboard/setup-device.php" class="btn">Setup Your First Device</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const token = localStorage.getItem('truevault_token');
        
        if (!token) {
            window.location.href = '/login.php';
        }
        
        async function loadDashboard() {
            try {
                // Load user info
                const meRes = await fetch('/api/auth/me.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const meData = await meRes.json();
                
                if (!meData.success) {
                    localStorage.removeItem('truevault_token');
                    window.location.href = '/login.php';
                    return;
                }
                
                document.getElementById('userEmail').textContent = meData.user.email;
                document.getElementById('userTier').textContent = meData.user.tier.toUpperCase();
                
                // Load devices
                const devRes = await fetch('/api/devices/list.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const devData = await devRes.json();
                
                if (devData.success) {
                    document.getElementById('deviceCount').textContent = devData.count;
                    
                    if (devData.devices.length > 0) {
                        const deviceIcons = { mobile: '📱', desktop: '💻', tablet: '📲', router: '🌐', other: '❓' };
                        document.getElementById('devicesList').innerHTML = devData.devices.map(d => `
                            <div class="device-item">
                                <div class="device-info">
                                    <span class="icon">${deviceIcons[d.type] || '📱'}</span>
                                    <div>
                                        <div class="name">${d.name}</div>
                                        <div class="ip">${d.ip_address} • ${d.server}</div>
                                    </div>
                                </div>
                                <span class="device-status ${d.status}">${d.status}</span>
                            </div>
                        `).join('');
                    }
                }
                
                // Load billing status
                const billRes = await fetch('/api/billing/status.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const billData = await billRes.json();
                
                if (billData.success) {
                    const status = billData.subscription?.status || billData.tier || 'free';
                    document.getElementById('subscriptionStatus').textContent = status.toUpperCase();
                }
                
                // Load port forwarding rules
                const pfRes = await fetch('/api/port-forwarding/list.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const pfData = await pfRes.json();
                
                if (pfData.success) {
                    document.getElementById('rulesCount').textContent = pfData.count;
                }
                
            } catch (error) {
                console.error('Dashboard load error:', error);
            }
        }
        
        function logout() {
            localStorage.removeItem('truevault_token');
            window.location.href = '/login.php';
        }
        
        loadDashboard();
    </script>
</body>
</html>
