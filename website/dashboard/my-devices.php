<?php
/**
 * TrueVault VPN - My Devices Dashboard
 * 
 * PURPOSE: User dashboard to view and manage all VPN devices
 * FEATURES:
 * - View all registered devices
 * - Connection status (online/offline)
 * - Switch servers on the fly
 * - Delete devices
 * - Download config files
 * - Add new devices
 * - Device statistics
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
    $userName = $user['first_name'] ?? 'User';
    $userEmail = $user['email'];
    $userTier = $user['tier'] ?? 'standard';
} catch (Exception $e) {
    // Redirect to login if not authenticated
    header('Location: /auth/login.php');
    exit;
}

// Get device limits
$deviceLimits = [
    'standard' => 3,
    'pro' => 5,
    'vip' => 999,
    'admin' => 999
];
$maxDevices = $deviceLimits[$userTier] ?? 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Devices - TrueVault VPN</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .header-title p {
            color: #718096;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
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
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            font-weight: 500;
        }

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .device-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
            position: relative;
        }

        .device-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .device-icon {
            font-size: 32px;
            margin-right: 12px;
        }

        .device-info h3 {
            font-size: 18px;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .device-type {
            color: #718096;
            font-size: 13px;
            text-transform: capitalize;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-connected {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-disconnected {
            background: #fed7d7;
            color: #742a2a;
        }

        .device-details {
            margin: 16px 0;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }

        .detail-label {
            color: #718096;
            font-weight: 500;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .server-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: #edf2f7;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .server-flag {
            font-size: 24px;
        }

        .server-name {
            font-weight: 600;
            color: #2d3748;
        }

        .device-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-switch {
            background: #4299e1;
            color: white;
        }

        .btn-switch:hover {
            background: #3182ce;
        }

        .btn-download {
            background: #48bb78;
            color: white;
        }

        .btn-download:hover {
            background: #38a169;
        }

        .btn-delete {
            background: #f56565;
            color: white;
            grid-column: 1 / -1;
        }

        .btn-delete:hover {
            background: #e53e3e;
        }

        .empty-state {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state h2 {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #718096;
            margin-bottom: 24px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: white;
            font-size: 18px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

        /* Modal styles */
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
            max-width: 400px;
            width: 90%;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #2d3748;
        }

        .modal-body {
            margin-bottom: 20px;
            color: #4a5568;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
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
                    <h1>👋 Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
                    <p>Manage your VPN devices and connections</p>
                </div>
                <div class="header-actions">
                    <a href="/dashboard/setup-device.php" class="btn btn-primary">
                        ➕ Add New Device
                    </a>
                    <button onclick="refreshDevices()" class="btn btn-secondary">
                        🔄 Refresh
                    </button>
                    <a href="/auth/logout.php" class="btn btn-secondary">
                        🚪 Logout
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value" id="total-devices">0</div>
                    <div class="stat-label">Total Devices</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="connected-devices">0</div>
                    <div class="stat-label">Connected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="device-limit"><?= $maxDevices ?></div>
                    <div class="stat-label">Device Limit (<?= ucfirst($userTier) ?>)</div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading your devices...</p>
        </div>

        <!-- Devices Grid -->
        <div id="devices-container" class="devices-grid" style="display: none;">
            <!-- Devices will be loaded here via JavaScript -->
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <div class="empty-icon">📱</div>
            <h2>No Devices Yet</h2>
            <p>Get started by adding your first VPN device</p>
            <a href="/dashboard/setup-device.php" class="btn btn-primary">
                ➕ Add Your First Device
            </a>
        </div>
    </div>

    <!-- Switch Server Modal -->
    <div id="switch-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Switch Server</div>
            <div class="modal-body">
                <p>Select a new server for <strong id="switch-device-name"></strong>:</p>
                <select id="server-select">
                    <option value="">Loading servers...</option>
                </select>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="confirmSwitch()">Switch Server</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Delete Device</div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete-device-name"></strong>?</p>
                <p style="color: #e53e3e; margin-top: 8px;">This action cannot be undone.</p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-delete" onclick="confirmDelete()">Delete Device</button>
            </div>
        </div>
    </div>

    <script>
        const JWT_TOKEN = localStorage.getItem('vpn_token');
        let devices = [];
        let availableServers = [];
        let currentDeviceId = null;

        // Device type icons
        const DEVICE_ICONS = {
            mobile: '📱',
            desktop: '💻',
            tablet: '📱',
            router: '🌐',
            other: '🔷'
        };

        // Country flags
        const COUNTRY_FLAGS = {
            usa: '🇺🇸',
            canada: '🇨🇦',
            uk: '🇬🇧',
            germany: '🇩🇪'
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            if (!JWT_TOKEN) {
                window.location.href = '/auth/login.php';
                return;
            }
            loadDevices();
            loadServers();
        });

        async function loadDevices() {
            try {
                const response = await fetch('/api/devices/list.php', {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const data = await response.json();

                if (data.success) {
                    devices = data.devices;
                    renderDevices();
                    updateStats();
                } else {
                    showError('Failed to load devices: ' + data.error);
                }
            } catch (error) {
                showError('Error loading devices: ' + error.message);
            }
        }

        async function loadServers() {
            try {
                const response = await fetch('/api/servers/list.php', {
                    headers: {
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    }
                });

                const data = await response.json();

                if (data.success) {
                    availableServers = data.servers;
                }
            } catch (error) {
                console.error('Error loading servers:', error);
            }
        }

        function renderDevices() {
            const container = document.getElementById('devices-container');
            const loading = document.getElementById('loading');
            const emptyState = document.getElementById('empty-state');

            loading.style.display = 'none';

            if (devices.length === 0) {
                emptyState.style.display = 'block';
                container.style.display = 'none';
                return;
            }

            emptyState.style.display = 'none';
            container.style.display = 'grid';

            container.innerHTML = devices.map(device => `
                <div class="device-card">
                    <div class="device-header">
                        <div style="display: flex; align-items: center;">
                            <div class="device-icon">${DEVICE_ICONS[device.type] || '🔷'}</div>
                            <div class="device-info">
                                <h3>${escapeHtml(device.name)}</h3>
                                <div class="device-type">${device.type}</div>
                            </div>
                        </div>
                        <span class="status-badge ${device.is_connected ? 'status-connected' : 'status-disconnected'}">
                            ${device.is_connected ? '🟢 Connected' : '⚫ Offline'}
                        </span>
                    </div>

                    <div class="server-info">
                        <span class="server-flag">${COUNTRY_FLAGS[device.server_country] || '🌍'}</span>
                        <span class="server-name">${escapeHtml(device.server)}</span>
                    </div>

                    <div class="device-details">
                        <div class="detail-row">
                            <span class="detail-label">IP Address:</span>
                            <span class="detail-value">${device.ipv4_address}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Created:</span>
                            <span class="detail-value">${formatDate(device.created_at)}</span>
                        </div>
                        ${device.last_handshake ? `
                        <div class="detail-row">
                            <span class="detail-label">Last Seen:</span>
                            <span class="detail-value">${formatDate(device.last_handshake)}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="device-actions">
                        <button class="btn-action btn-switch" onclick="showSwitchModal('${device.device_id}', '${escapeHtml(device.name)}')">
                            🔄 Switch Server
                        </button>
                        <button class="btn-action btn-download" onclick="downloadConfig('${device.device_id}')">
                            ⬇️ Download
                        </button>
                        <button class="btn-action btn-delete" onclick="showDeleteModal('${device.device_id}', '${escapeHtml(device.name)}')">
                            🗑️ Delete Device
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function updateStats() {
            document.getElementById('total-devices').textContent = devices.length;
            document.getElementById('connected-devices').textContent = devices.filter(d => d.is_connected).length;
        }

        function showSwitchModal(deviceId, deviceName) {
            currentDeviceId = deviceId;
            document.getElementById('switch-device-name').textContent = deviceName;

            const select = document.getElementById('server-select');
            select.innerHTML = availableServers.map(server => `
                <option value="${server.id}">${COUNTRY_FLAGS[server.country] || '🌍'} ${escapeHtml(server.name)}</option>
            `).join('');

            document.getElementById('switch-modal').classList.add('active');
        }

        async function confirmSwitch() {
            const serverId = document.getElementById('server-select').value;

            if (!serverId) {
                alert('Please select a server');
                return;
            }

            try {
                const response = await fetch('/api/devices/switch-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({
                        device_id: currentDeviceId,
                        server_id: parseInt(serverId)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeModal();
                    showSuccess('Server switched successfully!');
                    loadDevices();
                } else {
                    showError(data.error || 'Failed to switch server');
                }
            } catch (error) {
                showError('Error switching server: ' + error.message);
            }
        }

        function showDeleteModal(deviceId, deviceName) {
            currentDeviceId = deviceId;
            document.getElementById('delete-device-name').textContent = deviceName;
            document.getElementById('delete-modal').classList.add('active');
        }

        async function confirmDelete() {
            try {
                const response = await fetch('/api/devices/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JWT_TOKEN}`
                    },
                    body: JSON.stringify({
                        device_id: currentDeviceId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeModal();
                    showSuccess(data.message);
                    loadDevices();
                } else {
                    showError(data.error || 'Failed to delete device');
                }
            } catch (error) {
                showError('Error deleting device: ' + error.message);
            }
        }

        async function downloadConfig(deviceId) {
            // For now, show a message. In production, this would regenerate and download the config
            showSuccess('Config download feature coming soon! For now, use the original config you downloaded during setup.');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            currentDeviceId = null;
        }

        function refreshDevices() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('devices-container').style.display = 'none';
            document.getElementById('empty-state').style.display = 'none';
            loadDevices();
        }

        function formatDate(dateString) {
            if (!dateString) return 'Never';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
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

        // Close modal when clicking outside
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
