<?php
/**
 * Port Forwarding Dashboard - SQLITE3 VERSION
 * 
 * PURPOSE: Manage port forwarding rules for VPN devices
 * FEATURES: Add/edit/delete rules, auto-detect devices
 * 
 * @created January 2026
 * @version 1.0.0
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Forwarding - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            padding: 20px;
            color: #fff;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 24px; margin-bottom: 10px; color: #00d9ff; }
        .subtitle { color: #888; margin-bottom: 30px; }
        
        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .card h2 { color: #00ff88; margin-bottom: 15px; font-size: 18px; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 150px; }
        label { display: block; color: #888; margin-bottom: 5px; font-size: 14px; }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-size: 14px;
        }
        input:focus, select:focus { outline: none; border-color: #00d9ff; }
        select option { background: #1a1a2e; }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,217,255,0.3); }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; border: 1px solid #ff5050; }
        .btn-sm { padding: 8px 16px; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { color: #888; font-weight: 500; font-size: 13px; }
        td { font-size: 14px; }
        .status-active { color: #00ff88; }
        .status-inactive { color: #ff6464; }
        
        .empty { text-align: center; padding: 40px; color: #666; }
        .empty-icon { font-size: 48px; margin-bottom: 10px; }
        
        .device-icon { font-size: 20px; margin-right: 8px; }
        .toast {
            position: fixed; bottom: 20px; right: 20px; padding: 15px 25px;
            border-radius: 10px; font-weight: 600; z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        .toast.success { background: #00c853; color: #fff; }
        .toast.error { background: #ff5252; color: #fff; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } }
        
        .back-link { margin-top: 20px; }
        .back-link a { color: #00d9ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔀 Port Forwarding</h1>
        <p class="subtitle">Forward ports to access your devices remotely</p>
        
        <!-- Add New Rule -->
        <div class="card">
            <h2>➕ Add Port Forwarding Rule</h2>
            <form id="addRuleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Device</label>
                        <select id="deviceSelect" required>
                            <option value="">Select a device...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rule Name</label>
                        <input type="text" id="ruleName" placeholder="e.g., Camera Access" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>External Port</label>
                        <input type="number" id="externalPort" placeholder="8080" min="1" max="65535" required>
                    </div>
                    <div class="form-group">
                        <label>Internal Port</label>
                        <input type="number" id="internalPort" placeholder="80" min="1" max="65535" required>
                    </div>
                    <div class="form-group">
                        <label>Protocol</label>
                        <select id="protocol">
                            <option value="tcp">TCP</option>
                            <option value="udp">UDP</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Rule</button>
            </form>
        </div>
        
        <!-- Current Rules -->
        <div class="card">
            <h2>📋 Current Rules</h2>
            <div id="rulesTable">
                <div class="empty">
                    <div class="empty-icon">📭</div>
                    <p>No port forwarding rules yet</p>
                </div>
            </div>
        </div>
        
        <div class="back-link">
            <a href="/dashboard/">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        const token = localStorage.getItem('truevault_token');
        
        function toast(message, type = 'success') {
            const t = document.createElement('div');
            t.className = 'toast ' + type;
            t.textContent = message;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }
        
        async function loadDevices() {
            if (!token) return;
            try {
                const resp = await fetch('/api/devices/list.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const data = await resp.json();
                const select = document.getElementById('deviceSelect');
                select.innerHTML = '<option value="">Select a device...</option>';
                data.devices.forEach(d => {
                    select.innerHTML += `<option value="${d.id}">${d.name} (${d.ip_address})</option>`;
                });
            } catch (e) { console.error(e); }
        }
        
        async function loadRules() {
            if (!token) return;
            try {
                const resp = await fetch('/api/port-forwarding/list.php', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const data = await resp.json();
                const container = document.getElementById('rulesTable');
                
                if (!data.rules || data.rules.length === 0) {
                    container.innerHTML = '<div class="empty"><div class="empty-icon">📭</div><p>No port forwarding rules yet</p></div>';
                    return;
                }
                
                let html = '<table><thead><tr><th>Name</th><th>Device</th><th>External</th><th>Internal</th><th>Protocol</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                data.rules.forEach(r => {
                    html += `<tr>
                        <td>${r.rule_name}</td>
                        <td>${r.device_name || 'Unknown'}</td>
                        <td>${r.external_port}</td>
                        <td>${r.internal_port}</td>
                        <td>${r.protocol.toUpperCase()}</td>
                        <td class="status-${r.status === 'active' ? 'active' : 'inactive'}">${r.status}</td>
                        <td><button class="btn btn-danger btn-sm" onclick="deleteRule(${r.id})">Delete</button></td>
                    </tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (e) { console.error(e); }
        }
        
        document.getElementById('addRuleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const resp = await fetch('/api/port-forwarding/add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        device_id: document.getElementById('deviceSelect').value,
                        rule_name: document.getElementById('ruleName').value,
                        external_port: document.getElementById('externalPort').value,
                        internal_port: document.getElementById('internalPort').value,
                        protocol: document.getElementById('protocol').value
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    toast('Rule added successfully');
                    this.reset();
                    loadRules();
                } else {
                    toast(data.error || 'Failed to add rule', 'error');
                }
            } catch (e) {
                toast('Error adding rule', 'error');
            }
        });
        
        async function deleteRule(id) {
            if (!confirm('Delete this rule?')) return;
            try {
                const resp = await fetch('/api/port-forwarding/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ rule_id: id })
                });
                const data = await resp.json();
                if (data.success) {
                    toast('Rule deleted');
                    loadRules();
                } else {
                    toast(data.error || 'Failed to delete', 'error');
                }
            } catch (e) {
                toast('Error deleting rule', 'error');
            }
        }
        
        // Load on page load
        loadDevices();
        loadRules();
    </script>
</body>
</html>
