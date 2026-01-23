<?php
require_once __DIR__ . '/../includes/header.php';
requireAuth();
$pageTitle = "Device Rules";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - TrueVault VPN</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .device-rules-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { margin: 0; }
        
        .device-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        
        .device-card { background: var(--card-bg); border-radius: 16px; padding: 20px; transition: 0.2s; }
        .device-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .device-card.gaming-off { border-left: 4px solid #ff5050; }
        .device-card.override-active { border-left: 4px solid #ffb400; }
        
        .device-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .device-icon { font-size: 36px; }
        .device-info h3 { margin: 0; font-size: 1.1rem; }
        .device-info .type { color: var(--text-secondary); font-size: 12px; }
        .device-info .last-seen { color: var(--text-secondary); font-size: 11px; }
        
        .device-status { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-badge.gaming-on { background: rgba(0,255,136,0.15); color: #00ff88; }
        .status-badge.gaming-off { background: rgba(255,80,80,0.15); color: #ff5050; }
        .status-badge.schedule { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .status-badge.override { background: rgba(255,180,0,0.15); color: #ffb400; }
        
        .device-controls { display: flex; flex-direction: column; gap: 12px; }
        .control-row { display: flex; justify-content: space-between; align-items: center; }
        .control-row label { font-size: 14px; color: var(--text-secondary); }
        
        .schedule-select { padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; width: 100%; }
        .schedule-select:focus { border-color: var(--primary); outline: none; }
        
        .toggle-switch { position: relative; width: 44px; height: 24px; }
        .toggle-switch input { display: none; }
        .toggle-slider { position: absolute; inset: 0; background: #444; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .toggle-slider:before { content: ''; position: absolute; width: 18px; height: 18px; background: white; border-radius: 50%; top: 3px; left: 3px; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
        
        .btn { padding: 10px 16px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 13px; }
        .btn-primary { background: var(--primary); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: var(--text-primary); }
        .btn-danger { background: rgba(255,80,80,0.15); color: #ff5050; border: 1px solid rgba(255,80,80,0.3); }
        .btn:hover { transform: translateY(-1px); }
        
        .apply-all-bar { background: var(--card-bg); border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .apply-all-bar span { color: var(--text-secondary); font-size: 14px; }
        
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--card-bg); border-radius: 16px; padding: 25px; max-width: 450px; width: 90%; }
        .modal h3 { margin: 0 0 20px 0; }
        .modal-close { float: right; background: none; border: none; color: var(--text-secondary); font-size: 20px; cursor: pointer; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-secondary); font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text-primary); }
        
        .gaming-limits { background: rgba(255,255,255,0.03); border-radius: 10px; padding: 12px; margin-top: 10px; }
        .gaming-limits .limit-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .gaming-limits .limit-row:last-child { margin-bottom: 0; }
        .time-input { width: 80px; padding: 5px 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; text-align: center; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state .icon { font-size: 48px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>
    
    <div class="device-rules-container">
        <div class="page-header">
            <h2>📱 Device Rules</h2>
            <a href="/dashboard/parental-controls.php" class="btn btn-secondary">← Back to Parental Controls</a>
        </div>
        
        <!-- Apply to All Bar -->
        <div class="apply-all-bar">
            <span>Apply schedule to all devices:</span>
            <select id="applyAllSchedule" class="schedule-select" style="width: auto; min-width: 200px;">
                <option value="">Select schedule...</option>
            </select>
            <button class="btn btn-primary" onclick="applyToAll()">Apply to All</button>
        </div>
        
        <!-- Device Grid -->
        <div class="device-grid" id="deviceGrid">
            <div class="empty-state">
                <div class="icon">📱</div>
                <p>Loading devices...</p>
            </div>
        </div>
    </div>
    
    <!-- Device Edit Modal -->
    <div class="modal-overlay" id="deviceModal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()">×</button>
            <h3 id="modalTitle">Edit Device Rules</h3>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
    const API = '/api/parental/device-rules.php';
    let devices = [];
    let schedules = [];
    
    document.addEventListener('DOMContentLoaded', () => {
        loadDevices();
        loadSchedules();
    });
    
    async function loadDevices() {
        try {
            const res = await fetch(`${API}?action=list`);
            const data = await res.json();
            if (data.success) {
                devices = data.devices;
                renderDevices();
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadSchedules() {
        try {
            const res = await fetch('/api/parental/schedules.php');
            const data = await res.json();
            if (data.success) {
                schedules = data.schedules;
                populateScheduleDropdowns();
            }
        } catch (e) { console.error(e); }
    }
    
    function populateScheduleDropdowns() {
        const options = '<option value="">No schedule</option>' + 
            schedules.map(s => `<option value="${s.id}">${s.schedule_name}</option>`).join('');
        document.getElementById('applyAllSchedule').innerHTML = options;
    }
    
    function renderDevices() {
        const grid = document.getElementById('deviceGrid');
        
        if (devices.length === 0) {
            grid.innerHTML = `<div class="empty-state"><div class="icon">📱</div><p>No devices found. Add devices in Device Management.</p></div>`;
            return;
        }
        
        grid.innerHTML = devices.map(d => {
            const icon = getDeviceIcon(d.device_type);
            const gamingOn = d.gaming_enabled !== 0;
            const hasOverride = d.override_enabled == 1;
            
            return `
                <div class="device-card ${!gamingOn ? 'gaming-off' : ''} ${hasOverride ? 'override-active' : ''}">
                    <div class="device-header">
                        <span class="device-icon">${icon}</span>
                        <div class="device-info">
                            <h3>${d.device_name || 'Unnamed Device'}</h3>
                            <div class="type">${d.device_type || 'Unknown'}</div>
                            <div class="last-seen">${d.last_seen ? 'Last seen: ' + formatDate(d.last_seen) : 'Never connected'}</div>
                        </div>
                    </div>
                    
                    <div class="device-status">
                        <span class="status-badge ${gamingOn ? 'gaming-on' : 'gaming-off'}">
                            🎮 Gaming ${gamingOn ? 'ON' : 'OFF'}
                        </span>
                        ${d.schedule_name ? `<span class="status-badge schedule">📅 ${d.schedule_name}</span>` : ''}
                        ${hasOverride ? `<span class="status-badge override">⚠️ ${d.override_type}</span>` : ''}
                    </div>
                    
                    <div class="device-controls">
                        <div class="control-row">
                            <label>Schedule</label>
                            <select class="schedule-select" style="width: 150px;" onchange="assignSchedule(${d.id}, this.value)">
                                <option value="">None</option>
                                ${schedules.map(s => `<option value="${s.id}" ${s.id == d.schedule_id ? 'selected' : ''}>${s.schedule_name}</option>`).join('')}
                            </select>
                        </div>
                        
                        <div class="control-row">
                            <label>🎮 Gaming Enabled</label>
                            <label class="toggle-switch">
                                <input type="checkbox" ${gamingOn ? 'checked' : ''} onchange="toggleGaming(${d.id}, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        ${d.daily_limit_minutes ? `
                        <div class="control-row">
                            <label>Daily Limit</label>
                            <span>${Math.floor(d.daily_limit_minutes / 60)}h ${d.daily_limit_minutes % 60}m (${d.minutes_used_today || 0}m used)</span>
                        </div>
                        ` : ''}
                        
                        <button class="btn btn-secondary" onclick="editDevice(${d.id})" style="margin-top: 5px;">⚙️ Advanced Settings</button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function getDeviceIcon(type) {
        const icons = {
            'phone': '📱', 'tablet': '📱', 'laptop': '💻', 'desktop': '🖥️',
            'gaming': '🎮', 'console': '🎮', 'xbox': '🎮', 'playstation': '🎮',
            'tv': '📺', 'streaming': '📺', 'iot': '🏠'
        };
        return icons[type?.toLowerCase()] || '📱';
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return 'Unknown';
        return new Date(dateStr).toLocaleString();
    }
    
    async function assignSchedule(deviceId, scheduleId) {
        try {
            await fetch(`${API}?action=assign_schedule`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ device_id: deviceId, schedule_id: scheduleId || null })
            });
            showToast('Schedule assigned');
            loadDevices();
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function toggleGaming(deviceId, enabled) {
        try {
            await fetch(`${API}?action=set_gaming`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ device_id: deviceId, gaming_enabled: enabled ? 1 : 0 })
            });
            showToast(enabled ? 'Gaming enabled' : 'Gaming disabled');
            loadDevices();
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function applyToAll() {
        const scheduleId = document.getElementById('applyAllSchedule').value;
        if (!scheduleId) { showToast('Select a schedule first', 'error'); return; }
        
        if (!confirm('Apply this schedule to ALL devices?')) return;
        
        try {
            const res = await fetch(`${API}?action=apply_to_all`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ schedule_id: scheduleId })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`Schedule applied to ${data.devices_updated} devices`);
                loadDevices();
            }
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function editDevice(deviceId) {
        try {
            const res = await fetch(`${API}?action=get&device_id=${deviceId}`);
            const data = await res.json();
            if (data.success) {
                showDeviceModal(data.device);
            }
        } catch (e) { showToast('Error loading device', 'error'); }
    }
    
    function showDeviceModal(device) {
        document.getElementById('modalTitle').textContent = `⚙️ ${device.device_name} Settings`;
        document.getElementById('modalContent').innerHTML = `
            <div class="form-group">
                <label>Assigned Schedule</label>
                <select id="modal_schedule" class="schedule-select">
                    <option value="">No schedule</option>
                    ${device.available_schedules.map(s => `<option value="${s.id}" ${s.id == device.schedule_id ? 'selected' : ''}>${s.schedule_name}</option>`).join('')}
                </select>
            </div>
            
            <div class="gaming-limits">
                <h4 style="margin: 0 0 12px 0; font-size: 14px;">🎮 Gaming Controls</h4>
                
                <div class="limit-row">
                    <span>All Gaming</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="modal_gaming" ${device.gaming_enabled != 0 ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="limit-row">
                    <span>Xbox Live</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="modal_xbox" ${device.xbox_enabled != 0 ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="limit-row">
                    <span>PlayStation</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="modal_ps" ${device.playstation_enabled != 0 ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="limit-row">
                    <span>Steam</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="modal_steam" ${device.steam_enabled != 0 ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="limit-row">
                    <span>Daily Limit (minutes)</span>
                    <input type="number" id="modal_limit" class="time-input" value="${device.daily_limit_minutes || ''}" placeholder="None">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn btn-primary" onclick="saveDeviceSettings(${device.id})">Save Changes</button>
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        `;
        document.getElementById('deviceModal').classList.add('active');
    }
    
    async function saveDeviceSettings(deviceId) {
        const scheduleId = document.getElementById('modal_schedule').value;
        
        // Save schedule
        await fetch(`${API}?action=assign_schedule`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ device_id: deviceId, schedule_id: scheduleId || null })
        });
        
        // Save gaming settings
        await fetch(`${API}?action=set_gaming`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                device_id: deviceId,
                gaming_enabled: document.getElementById('modal_gaming').checked ? 1 : 0,
                xbox_enabled: document.getElementById('modal_xbox').checked ? 1 : 0,
                playstation_enabled: document.getElementById('modal_ps').checked ? 1 : 0,
                steam_enabled: document.getElementById('modal_steam').checked ? 1 : 0,
                daily_limit_minutes: document.getElementById('modal_limit').value || null
            })
        });
        
        closeModal();
        showToast('Settings saved');
        loadDevices();
    }
    
    function closeModal() {
        document.getElementById('deviceModal').classList.remove('active');
    }
    
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.style.cssText = `position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:8px;font-weight:600;z-index:9999;background:${type === 'error' ? '#ff5252' : '#00c853'};color:white;`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>
