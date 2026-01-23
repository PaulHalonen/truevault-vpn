<?php
require_once __DIR__ . '/../includes/header.php';
requireAuth();
$pageTitle = "Parental Controls - Schedule Calendar";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - TrueVault VPN</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <style>
        .parental-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-header h2 { margin: 0; color: var(--text-primary); }
        
        /* Quick Actions */
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 30px; }
        .quick-btn { padding: 15px; border: none; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s; }
        .quick-btn .icon { font-size: 24px; }
        .quick-btn.danger { background: rgba(255,80,80,0.15); color: #ff5050; border: 1px solid rgba(255,80,80,0.3); }
        .quick-btn.warning { background: rgba(255,180,0,0.15); color: #ffb400; border: 1px solid rgba(255,180,0,0.3); }
        .quick-btn.success { background: rgba(0,255,136,0.15); color: #00ff88; border: 1px solid rgba(0,255,136,0.3); }
        .quick-btn.primary { background: rgba(0,217,255,0.15); color: #00d9ff; border: 1px solid rgba(0,217,255,0.3); }
        .quick-btn:hover { transform: translateY(-2px); }
        
        /* Calendar */
        .calendar-container { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 30px; }
        .calendar-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-nav h3 { margin: 0; font-size: 1.3rem; }
        .calendar-nav button { background: var(--primary); color: #0f0f1a; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .calendar-day-header { text-align: center; padding: 10px; font-weight: 600; color: var(--text-secondary); font-size: 12px; }
        .calendar-day { min-height: 80px; background: rgba(255,255,255,0.03); border-radius: 8px; padding: 8px; cursor: pointer; transition: all 0.2s; position: relative; }
        .calendar-day:hover { background: rgba(255,255,255,0.08); }
        .calendar-day.today { border: 2px solid var(--primary); }
        .calendar-day.other-month { opacity: 0.3; }
        .calendar-day .day-num { font-weight: 600; margin-bottom: 5px; }
        .calendar-day .day-status { font-size: 10px; padding: 2px 6px; border-radius: 4px; display: inline-block; }
        .calendar-day .day-status.full { background: rgba(0,255,136,0.2); color: #00ff88; }
        .calendar-day .day-status.restricted { background: rgba(255,180,0,0.2); color: #ffb400; }
        .calendar-day .day-status.blocked { background: rgba(255,80,80,0.2); color: #ff5050; }
        
        /* Status Cards */
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .status-card { background: var(--card-bg); border-radius: 12px; padding: 20px; }
        .status-card h4 { margin: 0 0 10px 0; color: var(--text-secondary); font-size: 12px; text-transform: uppercase; }
        .status-card .value { font-size: 24px; font-weight: 700; color: var(--primary); }
        .status-card .sub { font-size: 12px; color: var(--text-secondary); }
        
        /* Gaming Controls */
        .gaming-panel { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 30px; }
        .gaming-toggle { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px; margin-bottom: 10px; }
        .gaming-toggle .label { display: flex; align-items: center; gap: 10px; }
        .gaming-toggle .label .icon { font-size: 24px; }
        .toggle-switch { position: relative; width: 50px; height: 26px; }
        .toggle-switch input { display: none; }
        .toggle-slider { position: absolute; inset: 0; background: #444; border-radius: 13px; cursor: pointer; transition: 0.3s; }
        .toggle-slider:before { content: ''; position: absolute; width: 20px; height: 20px; background: white; border-radius: 50%; top: 3px; left: 3px; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
        
        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--card-bg); border-radius: 16px; padding: 25px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal h3 { margin: 0 0 20px 0; }
        .modal-close { float: right; background: none; border: none; color: var(--text-secondary); font-size: 20px; cursor: pointer; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-secondary); font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text-primary); }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); outline: none; }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: var(--text-primary); }
        .btn:hover { transform: translateY(-1px); }
        
        /* Lists */
        .list-section { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .list-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 8px; }
        .list-item .domain { font-family: monospace; color: var(--primary); }
        .list-item .remove { background: none; border: none; color: #ff5050; cursor: pointer; font-size: 18px; }
        
        /* Time Window Visual */
        .time-windows { margin-top: 15px; }
        .time-bar { height: 30px; background: #333; border-radius: 5px; position: relative; margin-bottom: 10px; overflow: hidden; }
        .time-segment { position: absolute; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600; }
        .time-segment.full { background: rgba(0,255,136,0.5); }
        .time-segment.restricted { background: rgba(255,180,0,0.5); }
        .time-segment.blocked { background: rgba(255,80,80,0.5); }
        
        .time-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>
    
    <div class="parental-container">
        <div class="section-header">
            <h2>🛡️ Parental Controls</h2>
            <div>
                <select id="deviceSelect" class="form-control" style="padding: 8px 15px; background: var(--card-bg); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white;">
                    <option value="">All Devices</option>
                </select>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="quick-btn danger" onclick="quickAction('block_gaming')">
                <span class="icon">🎮</span>
                <span>Block Gaming</span>
            </button>
            <button class="quick-btn warning" onclick="quickAction('homework_mode')">
                <span class="icon">📚</span>
                <span>Homework Mode</span>
            </button>
            <button class="quick-btn primary" onclick="quickAction('extend_time')">
                <span class="icon">⏰</span>
                <span>+1 Hour Free</span>
            </button>
            <button class="quick-btn danger" onclick="quickAction('emergency_block')">
                <span class="icon">🛑</span>
                <span>Block All</span>
            </button>
            <button class="quick-btn success" onclick="quickAction('restore_normal')">
                <span class="icon">✅</span>
                <span>Restore Normal</span>
            </button>
            <button class="quick-btn warning" onclick="quickAction('bedtime_now')">
                <span class="icon">🌙</span>
                <span>Bedtime Now</span>
            </button>
        </div>
        
        <!-- Status Cards -->
        <div class="status-grid">
            <div class="status-card">
                <h4>Today's Screen Time</h4>
                <div class="value" id="screenTime">0h 0m</div>
                <div class="sub">vs yesterday: <span id="screenChange">--</span></div>
            </div>
            <div class="status-card">
                <h4>Gaming Time</h4>
                <div class="value" id="gamingTime">0h 0m</div>
                <div class="sub">Limit: <span id="gamingLimit">None</span></div>
            </div>
            <div class="status-card">
                <h4>Blocked Requests</h4>
                <div class="value" id="blockedCount">0</div>
                <div class="sub">Today</div>
            </div>
            <div class="status-card">
                <h4>Current Status</h4>
                <div class="value" id="currentStatus">Normal</div>
                <div class="sub" id="statusUntil"></div>
            </div>
        </div>
        
        <!-- Calendar -->
        <div class="calendar-container">
            <div class="calendar-nav">
                <button onclick="changeMonth(-1)">← Previous</button>
                <h3 id="calendarTitle">January 2026</h3>
                <button onclick="changeMonth(1)">Next →</button>
            </div>
            <div class="calendar-grid" id="calendarGrid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
            </div>
        </div>
        
        <!-- Gaming Controls -->
        <div class="gaming-panel">
            <h3>🎮 Gaming Controls</h3>
            <div class="gaming-toggle">
                <div class="label"><span class="icon">🎮</span> All Gaming</div>
                <label class="toggle-switch">
                    <input type="checkbox" id="gamingAll" checked onchange="toggleGaming('all', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="gaming-toggle">
                <div class="label"><span class="icon">🟢</span> Xbox Live</div>
                <label class="toggle-switch">
                    <input type="checkbox" id="gamingXbox" checked onchange="toggleGaming('xbox', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="gaming-toggle">
                <div class="label"><span class="icon">🔵</span> PlayStation</div>
                <label class="toggle-switch">
                    <input type="checkbox" id="gamingPS" checked onchange="toggleGaming('playstation', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="gaming-toggle">
                <div class="label"><span class="icon">⬛</span> Steam</div>
                <label class="toggle-switch">
                    <input type="checkbox" id="gamingSteam" checked onchange="toggleGaming('steam', this.checked)">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <!-- Whitelist/Blacklist -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="list-section">
                <h3>✅ Whitelist (Always Allowed)</h3>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="text" id="whitelistDomain" placeholder="domain.com">
                    <button class="btn btn-primary" onclick="addToList('whitelist')">Add</button>
                </div>
                <div id="whitelistItems"></div>
            </div>
            <div class="list-section">
                <h3>🚫 Blacklist (Always Blocked)</h3>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="text" id="blacklistDomain" placeholder="domain.com">
                    <button class="btn btn-primary" onclick="addToList('blacklist')">Add</button>
                </div>
                <div id="blacklistItems"></div>
            </div>
        </div>
    </div>
    
    <!-- Day Editor Modal -->
    <div class="modal-overlay" id="dayModal">
        <div class="modal">
            <button class="modal-close" onclick="closeDayModal()">×</button>
            <h3 id="modalTitle">Edit Schedule</h3>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
    const API = '/api/parental';
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let schedules = [];
    
    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        loadDevices();
        renderCalendar();
        loadStatistics();
        loadGamingStatus();
        loadLists();
        loadOverrideStatus();
    });
    
    async function loadDevices() {
        try {
            const res = await fetch('/api/devices.php');
            const data = await res.json();
            if (data.success) {
                const select = document.getElementById('deviceSelect');
                data.devices.forEach(d => {
                    select.innerHTML += `<option value="${d.id}">${d.device_name}</option>`;
                });
            }
        } catch (e) { console.error(e); }
    }
    
    function renderCalendar() {
        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = `
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
        `;
        
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const today = new Date();
        
        document.getElementById('calendarTitle').textContent = 
            new Date(currentYear, currentMonth).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        
        // Previous month days
        const prevDays = new Date(currentYear, currentMonth, 0).getDate();
        for (let i = firstDay - 1; i >= 0; i--) {
            grid.innerHTML += `<div class="calendar-day other-month"><div class="day-num">${prevDays - i}</div></div>`;
        }
        
        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = today.getDate() === day && today.getMonth() === currentMonth && today.getFullYear() === currentYear;
            const dayOfWeek = new Date(currentYear, currentMonth, day).getDay();
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            
            grid.innerHTML += `
                <div class="calendar-day ${isToday ? 'today' : ''}" onclick="openDayModal(${day})">
                    <div class="day-num">${day}</div>
                    <span class="day-status ${isWeekend ? 'full' : 'restricted'}">${isWeekend ? 'Free' : 'School'}</span>
                </div>
            `;
        }
        
        // Next month days
        const remaining = 42 - (firstDay + daysInMonth);
        for (let i = 1; i <= remaining; i++) {
            grid.innerHTML += `<div class="calendar-day other-month"><div class="day-num">${i}</div></div>`;
        }
    }
    
    function changeMonth(delta) {
        currentMonth += delta;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar();
    }
    
    async function quickAction(action) {
        const deviceId = document.getElementById('deviceSelect').value;
        try {
            const res = await fetch(`${API}/quick-actions.php?action=${action}&device_id=${deviceId}`, { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showToast(data.message || `${action} activated!`);
                loadOverrideStatus();
                loadStatistics();
            } else {
                showToast(data.error || 'Action failed', 'error');
            }
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function toggleGaming(platform, enabled) {
        const deviceId = document.getElementById('deviceSelect').value;
        try {
            const res = await fetch(`${API}/gaming.php?action=toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ platform, enabled, device_id: deviceId || null })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`Gaming ${enabled ? 'enabled' : 'disabled'}`);
            }
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function loadStatistics() {
        try {
            const res = await fetch(`${API}/statistics.php?action=summary&days=1`);
            const data = await res.json();
            if (data.success && data.summary) {
                const mins = data.summary.total_screen_time || 0;
                document.getElementById('screenTime').textContent = `${Math.floor(mins/60)}h ${mins%60}m`;
                document.getElementById('gamingTime').textContent = `${Math.floor((data.summary.total_gaming||0)/60)}h ${(data.summary.total_gaming||0)%60}m`;
                document.getElementById('blockedCount').textContent = data.summary.total_blocked || 0;
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadGamingStatus() {
        try {
            const res = await fetch(`${API}/gaming.php?action=status`);
            const data = await res.json();
            if (data.success && data.restrictions.length > 0) {
                const r = data.restrictions[0];
                document.getElementById('gamingAll').checked = r.gaming_enabled;
                document.getElementById('gamingXbox').checked = r.xbox_enabled;
                document.getElementById('gamingPS').checked = r.playstation_enabled;
                document.getElementById('gamingSteam').checked = r.steam_enabled;
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadOverrideStatus() {
        try {
            const res = await fetch(`${API}/quick-actions.php?action=status`);
            const data = await res.json();
            if (data.success && data.active_overrides.length > 0) {
                const o = data.active_overrides[0];
                document.getElementById('currentStatus').textContent = o.override_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                document.getElementById('statusUntil').textContent = `Until ${new Date(o.override_until).toLocaleTimeString()}`;
            } else {
                document.getElementById('currentStatus').textContent = 'Normal';
                document.getElementById('statusUntil').textContent = '';
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadLists() {
        // Whitelist
        try {
            const res = await fetch(`${API}/lists.php?type=whitelist`);
            const data = await res.json();
            if (data.success) {
                document.getElementById('whitelistItems').innerHTML = data.whitelist
                    .filter(w => w.user_id != 0)
                    .map(w => `<div class="list-item"><span class="domain">${w.domain}</span><button class="remove" onclick="removeFromList('whitelist', ${w.id})">×</button></div>`)
                    .join('') || '<p style="color: var(--text-secondary)">No custom whitelist entries</p>';
            }
        } catch (e) {}
        
        // Blacklist
        try {
            const res = await fetch(`${API}/lists.php?type=blacklist`);
            const data = await res.json();
            if (data.success) {
                document.getElementById('blacklistItems').innerHTML = data.blacklist
                    .map(b => `<div class="list-item"><span class="domain">${b.domain}</span><button class="remove" onclick="removeFromList('blacklist', ${b.id})">×</button></div>`)
                    .join('') || '<p style="color: var(--text-secondary)">No blacklist entries</p>';
            }
        } catch (e) {}
    }
    
    async function addToList(type) {
        const input = document.getElementById(`${type}Domain`);
        const domain = input.value.trim();
        if (!domain) return;
        
        try {
            const res = await fetch(`${API}/lists.php?type=${type}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ domain })
            });
            const data = await res.json();
            if (data.success) {
                input.value = '';
                loadLists();
                showToast(`Added to ${type}`);
            }
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    async function removeFromList(type, id) {
        try {
            await fetch(`${API}/lists.php?type=${type}&id=${id}`, { method: 'DELETE' });
            loadLists();
            showToast(`Removed from ${type}`);
        } catch (e) { showToast('Error: ' + e.message, 'error'); }
    }
    
    function openDayModal(day) {
        document.getElementById('modalTitle').textContent = `Edit Schedule - ${new Date(currentYear, currentMonth, day).toLocaleDateString()}`;
        document.getElementById('modalContent').innerHTML = `
            <p>Add time windows for this day:</p>
            <div class="form-group">
                <label>Start Time</label>
                <input type="time" id="windowStart" value="09:00">
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="time" id="windowEnd" value="17:00">
            </div>
            <div class="form-group">
                <label>Access Type</label>
                <select id="windowType">
                    <option value="full">Full Access</option>
                    <option value="homework_only">Homework Only</option>
                    <option value="streaming_only">Streaming Only</option>
                    <option value="gaming_only">Gaming Only</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="saveWindow(${day})">Save Window</button>
        `;
        document.getElementById('dayModal').classList.add('active');
    }
    
    function closeDayModal() {
        document.getElementById('dayModal').classList.remove('active');
    }
    
    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = msg;
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:8px;font-weight:600;z-index:9999;' + (type === 'error' ? 'background:#ff5252' : 'background:#00c853');
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>
