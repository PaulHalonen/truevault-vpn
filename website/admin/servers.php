<?php
/**
 * TrueVault VPN - Server Management
 * 
 * PURPOSE: Admin page to manage VPN servers
 * AUTHENTICATION: Admin or VIP tier required
 * 
 * FEATURES:
 * - List all servers
 * - Add new servers
 * - Edit server details
 * - Delete servers
 * - Monitor server status
 * - View server load
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

// Check authentication and admin access
try {
    $user = Auth::require();
    $userTier = $user['tier'] ?? 'standard';
    
    if (!in_array($userTier, ['admin', 'vip'])) {
        header('Location: /dashboard/my-devices.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: /auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Management - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .servers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .server-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }

        .server-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .server-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }

        .server-flag {
            font-size: 48px;
        }

        .server-status {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-online {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-offline {
            background: #fee2e2;
            color: #dc2626;
        }

        .server-name {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .server-location {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .server-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .server-detail {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
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

        .server-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .load-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .load-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s;
        }

        .load-fill.high {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .load-fill.critical {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #475569;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>🌐 Server Management</h1>
                    <p>Manage VPN servers and monitor performance</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn btn-success" onclick="showAddModal()">
                        ➕ Add Server
                    </button>
                    <a href="/admin/dashboard.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading servers...</p>
        </div>

        <!-- Servers Grid -->
        <div id="servers-grid" class="servers-grid" style="display: none;">
            <!-- Servers loaded via JavaScript -->
        </div>
    </div>

    <!-- Add/Edit Server Modal -->
    <div id="server-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modal-title">Add Server</div>
            <form id="server-form">
                <input type="hidden" id="server-id">
                
                <div class="form-group">
                    <label class="form-label">Server Name</label>
                    <input type="text" id="server-name" class="form-input" required placeholder="USA (Dallas)">
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" id="server-country" class="form-input" required placeholder="United States">
                </div>

                <div class="form-group">
                    <label class="form-label">Country Code</label>
                    <input type="text" id="server-country-code" class="form-input" required placeholder="US" maxlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label">Region</label>
                    <input type="text" id="server-region" class="form-input" required placeholder="Texas">
                </div>

                <div class="form-group">
                    <label class="form-label">Endpoint (IP:Port)</label>
                    <input type="text" id="server-endpoint" class="form-input" required placeholder="66.241.124.4:51820">
                </div>

                <div class="form-group">
                    <label class="form-label">Provider</label>
                    <input type="text" id="server-provider" class="form-input" required placeholder="fly.io">
                </div>

                <div class="form-group">
                    <label class="form-label">Max Users</label>
                    <input type="number" id="server-max-users" class="form-input" required placeholder="50" min="1">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="server-status" class="form-select">
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" id="server-dedicated">
                        <span>Dedicated Server (VIP Only)</span>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Server</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Delete Server</div>
            <p style="margin-bottom: 20px; color: #64748b;">
                Are you sure you want to delete <strong id="delete-server-name"></strong>?
            </p>
            <p style="color: #ef4444; font-size: 14px; margin-bottom: 20px;">
                ⚠️ This will affect all devices currently using this server.
            </p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-delete" onclick="confirmDelete()">Delete Server</button>
            </div>
        </div>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');
        let servers = [];
        let currentServerId = null;

        document.addEventListener('DOMContentLoaded', () => {
            if (!JWT_TOKEN) {
                window.location.href = '/auth/login.php';
                return;
            }
            loadServers();
        });

        async function loadServers() {
            try {
                const response = await fetch('/api/admin/servers/list.php', {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const data = await response.json();

                if (data.success) {
                    servers = data.servers;
                    renderServers();
                } else {
                    showError(data.error || 'Failed to load servers');
                }
            } catch (error) {
                showError('Error loading servers: ' + error.message);
            }
        }

        function renderServers() {
            const loading = document.getElementById('loading');
            const grid = document.getElementById('servers-grid');

            loading.style.display = 'none';
            grid.style.display = 'grid';

            const flags = {
                'US': '🇺🇸',
                'CA': '🇨🇦',
                'GB': '🇬🇧',
                'DE': '🇩🇪',
                'FR': '🇫🇷',
                'NL': '🇳🇱',
                'SG': '🇸🇬',
                'JP': '🇯🇵',
                'AU': '🇦🇺'
            };

            grid.innerHTML = servers.map(server => {
                const loadPercent = Math.round((server.current_load / server.max_users) * 100);
                let loadClass = '';
                if (loadPercent > 80) loadClass = 'critical';
                else if (loadPercent > 60) loadClass = 'high';

                return `
                    <div class="server-card">
                        <div class="server-header">
                            <div class="server-flag">${flags[server.country_code] || '🌐'}</div>
                            <span class="server-status status-${server.status}">
                                ${server.status === 'online' ? '🟢' : '🔴'} ${server.status}
                            </span>
                        </div>

                        <div class="server-name">${escapeHtml(server.name)}</div>
                        <div class="server-location">
                            ${escapeHtml(server.region)}, ${escapeHtml(server.country)}
                            ${server.is_dedicated ? ' · VIP Dedicated' : ''}
                        </div>

                        <div class="server-details">
                            <div class="server-detail">
                                <span class="detail-label">Endpoint:</span>
                                <span class="detail-value">${escapeHtml(server.endpoint)}</span>
                            </div>
                            <div class="server-detail">
                                <span class="detail-label">Provider:</span>
                                <span class="detail-value">${escapeHtml(server.provider)}</span>
                            </div>
                            <div class="server-detail">
                                <span class="detail-label">Load:</span>
                                <span class="detail-value">${server.current_load}/${server.max_users} (${loadPercent}%)</span>
                            </div>
                            <div class="load-bar">
                                <div class="load-fill ${loadClass}" style="width: ${loadPercent}%"></div>
                            </div>
                        </div>

                        <div class="server-actions">
                            <button class="action-btn btn-edit" onclick="showEditModal(${server.server_id})">
                                ✏️ Edit
                            </button>
                            <button class="action-btn btn-delete" onclick="showDeleteModal(${server.server_id}, '${escapeHtml(server.name)}')">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function showAddModal() {
            document.getElementById('modal-title').textContent = 'Add Server';
            document.getElementById('server-form').reset();
            document.getElementById('server-id').value = '';
            document.getElementById('server-modal').classList.add('active');
        }

        function showEditModal(serverId) {
            const server = servers.find(s => s.server_id === serverId);
            if (!server) return;

            document.getElementById('modal-title').textContent = 'Edit Server';
            document.getElementById('server-id').value = server.server_id;
            document.getElementById('server-name').value = server.name;
            document.getElementById('server-country').value = server.country;
            document.getElementById('server-country-code').value = server.country_code;
            document.getElementById('server-region').value = server.region;
            document.getElementById('server-endpoint').value = server.endpoint;
            document.getElementById('server-provider').value = server.provider;
            document.getElementById('server-max-users').value = server.max_users;
            document.getElementById('server-status').value = server.status;
            document.getElementById('server-dedicated').checked = server.is_dedicated === 1;

            document.getElementById('server-modal').classList.add('active');
        }

        document.getElementById('server-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const serverId = document.getElementById('server-id').value;
            const isEdit = serverId !== '';

            const data = {
                name: document.getElementById('server-name').value,
                country: document.getElementById('server-country').value,
                country_code: document.getElementById('server-country-code').value.toUpperCase(),
                region: document.getElementById('server-region').value,
                endpoint: document.getElementById('server-endpoint').value,
                provider: document.getElementById('server-provider').value,
                max_users: parseInt(document.getElementById('server-max-users').value),
                status: document.getElementById('server-status').value,
                is_dedicated: document.getElementById('server-dedicated').checked ? 1 : 0
            };

            if (isEdit) {
                data.server_id = parseInt(serverId);
            }

            const url = isEdit ? '/api/admin/servers/update.php' : '/api/admin/servers/create.php';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    closeModal();
                    showSuccess(isEdit ? 'Server updated!' : 'Server created!');
                    loadServers();
                } else {
                    showError(result.error || 'Operation failed');
                }
            } catch (error) {
                showError('Error: ' + error.message);
            }
        });

        function showDeleteModal(serverId, serverName) {
            currentServerId = serverId;
            document.getElementById('delete-server-name').textContent = serverName;
            document.getElementById('delete-modal').classList.add('active');
        }

        async function confirmDelete() {
            try {
                const response = await fetch('/api/admin/servers/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({ server_id: currentServerId })
                });

                const result = await response.json();

                if (result.success) {
                    closeModal();
                    showSuccess('Server deleted!');
                    loadServers();
                } else {
                    showError(result.error || 'Failed to delete server');
                }
            } catch (error) {
                showError('Error: ' + error.message);
            }
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            currentServerId = null;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showSuccess(message) {
            alert('✅ ' + message);
        }

        function showError(message) {
            alert('❌ ' + message);
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
