<?php
require_once __DIR__ . '/../includes/header.php';
requireAuth();
$pageTitle = "Parental Statistics";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - TrueVault VPN</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .stats-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .stats-header h2 { margin: 0; }
        .period-select { display: flex; gap: 10px; }
        .period-btn { padding: 8px 16px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: var(--text-secondary); border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .period-btn.active { background: var(--primary); color: #0f0f1a; border-color: var(--primary); }
        
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-card { background: var(--card-bg); border-radius: 12px; padding: 20px; text-align: center; }
        .summary-card .icon { font-size: 28px; margin-bottom: 10px; }
        .summary-card .value { font-size: 28px; font-weight: 700; color: var(--primary); }
        .summary-card .label { font-size: 12px; color: var(--text-secondary); margin-top: 5px; }
        .summary-card .change { font-size: 12px; margin-top: 5px; }
        .summary-card .change.up { color: #ff5050; }
        .summary-card .change.down { color: #00ff88; }
        
        .chart-container { background: var(--card-bg); border-radius: 16px; padding: 25px; margin-bottom: 25px; }
        .chart-container h3 { margin: 0 0 20px 0; }
        .chart-wrapper { position: relative; height: 300px; }
        
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .two-col { grid-template-columns: 1fr; } }
        
        .blocked-list { background: var(--card-bg); border-radius: 16px; padding: 20px; }
        .blocked-list h3 { margin: 0 0 15px 0; }
        .blocked-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 8px; }
        .blocked-item .domain { font-family: monospace; color: #ff6b6b; }
        .blocked-item .count { background: rgba(255,80,80,0.2); color: #ff5050; padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        
        .category-breakdown { background: var(--card-bg); border-radius: 16px; padding: 20px; }
        .category-breakdown h3 { margin: 0 0 15px 0; }
        .category-bar { margin-bottom: 15px; }
        .category-bar .label { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .category-bar .bar { height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; }
        .category-bar .fill { height: 100%; border-radius: 4px; transition: width 0.5s; }
        .category-bar.gaming .fill { background: linear-gradient(90deg, #ff6b6b, #ff8e8e); }
        .category-bar.streaming .fill { background: linear-gradient(90deg, #a855f7, #c084fc); }
        .category-bar.social .fill { background: linear-gradient(90deg, #00d9ff, #7dd3fc); }
        .category-bar.educational .fill { background: linear-gradient(90deg, #00ff88, #86efac); }
        
        .activity-log { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-top: 25px; }
        .activity-log h3 { margin: 0 0 15px 0; }
        .activity-item { display: flex; gap: 15px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .time { color: var(--text-secondary); font-size: 12px; min-width: 100px; }
        .activity-item .action { flex: 1; }
        .activity-item .action .type { color: var(--primary); font-weight: 600; }
        
        .report-btn { background: var(--primary); color: #0f0f1a; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>
    
    <div class="stats-container">
        <div class="stats-header">
            <h2>📊 Usage Statistics</h2>
            <div class="period-select">
                <button class="period-btn" data-days="1">Today</button>
                <button class="period-btn active" data-days="7">Week</button>
                <button class="period-btn" data-days="30">Month</button>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="icon">📱</div>
                <div class="value" id="totalScreen">0h</div>
                <div class="label">Total Screen Time</div>
                <div class="change" id="screenChange"></div>
            </div>
            <div class="summary-card">
                <div class="icon">🎮</div>
                <div class="value" id="totalGaming">0h</div>
                <div class="label">Gaming</div>
                <div class="change" id="gamingChange"></div>
            </div>
            <div class="summary-card">
                <div class="icon">📺</div>
                <div class="value" id="totalStreaming">0h</div>
                <div class="label">Streaming</div>
            </div>
            <div class="summary-card">
                <div class="icon">📚</div>
                <div class="value" id="totalEducational">0h</div>
                <div class="label">Educational</div>
            </div>
            <div class="summary-card">
                <div class="icon">🚫</div>
                <div class="value" id="totalBlocked">0</div>
                <div class="label">Blocked Requests</div>
            </div>
        </div>
        
        <!-- Daily Chart -->
        <div class="chart-container">
            <h3>📈 Daily Screen Time</h3>
            <div class="chart-wrapper">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="two-col">
            <!-- Category Breakdown -->
            <div class="category-breakdown">
                <h3>📊 Category Breakdown</h3>
                <div class="category-bar gaming">
                    <div class="label"><span>🎮 Gaming</span><span id="gamingPercent">0%</span></div>
                    <div class="bar"><div class="fill" id="gamingBar" style="width: 0%"></div></div>
                </div>
                <div class="category-bar streaming">
                    <div class="label"><span>📺 Streaming</span><span id="streamingPercent">0%</span></div>
                    <div class="bar"><div class="fill" id="streamingBar" style="width: 0%"></div></div>
                </div>
                <div class="category-bar social">
                    <div class="label"><span>💬 Social Media</span><span id="socialPercent">0%</span></div>
                    <div class="bar"><div class="fill" id="socialBar" style="width: 0%"></div></div>
                </div>
                <div class="category-bar educational">
                    <div class="label"><span>📚 Educational</span><span id="educationalPercent">0%</span></div>
                    <div class="bar"><div class="fill" id="educationalBar" style="width: 0%"></div></div>
                </div>
            </div>
            
            <!-- Top Blocked Sites -->
            <div class="blocked-list">
                <h3>🚫 Top Blocked Sites</h3>
                <div id="blockedItems">
                    <p style="color: var(--text-secondary)">No blocked sites this period</p>
                </div>
            </div>
        </div>
        
        <!-- Activity Log -->
        <div class="activity-log">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>📋 Recent Activity</h3>
                <button class="report-btn" onclick="generateReport()">📧 Email Weekly Report</button>
            </div>
            <div id="activityItems">
                <p style="color: var(--text-secondary)">No recent activity</p>
            </div>
        </div>
    </div>
    
    <script>
    const API = '/api/parental/statistics.php';
    let currentDays = 7;
    let dailyChart = null;
    
    document.addEventListener('DOMContentLoaded', () => {
        loadStats(7);
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                loadStats(parseInt(btn.dataset.days));
            });
        });
    });
    
    async function loadStats(days) {
        currentDays = days;
        await Promise.all([loadSummary(days), loadDaily(days), loadBlocked(days), loadActivity(), loadComparison()]);
    }
    
    async function loadSummary(days) {
        try {
            const res = await fetch(`${API}?action=summary&days=${days}`);
            const data = await res.json();
            if (data.success && data.summary) {
                const s = data.summary;
                document.getElementById('totalScreen').textContent = formatHours(s.total_screen_time);
                document.getElementById('totalGaming').textContent = formatHours(s.total_gaming);
                document.getElementById('totalStreaming').textContent = formatHours(s.total_streaming);
                document.getElementById('totalEducational').textContent = formatHours(s.total_educational);
                document.getElementById('totalBlocked').textContent = s.total_blocked || 0;
                
                // Category percentages
                const total = Math.max(s.total_screen_time || 1, 1);
                updateCategory('gaming', s.total_gaming, total);
                updateCategory('streaming', s.total_streaming, total);
                updateCategory('social', s.total_social, total);
                updateCategory('educational', s.total_educational, total);
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadDaily(days) {
        try {
            const res = await fetch(`${API}?action=daily&days=${days}`);
            const data = await res.json();
            if (data.success) {
                renderChart(data.statistics);
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadBlocked(days) {
        try {
            const res = await fetch(`${API}?action=blocked&days=${days}`);
            const data = await res.json();
            if (data.success && data.blocked_sites.length > 0) {
                document.getElementById('blockedItems').innerHTML = data.blocked_sites
                    .slice(0, 10)
                    .map(b => `<div class="blocked-item"><span class="domain">${b.domain}</span><span class="count">${b.count} blocks</span></div>`)
                    .join('');
            } else {
                document.getElementById('blockedItems').innerHTML = '<p style="color: var(--text-secondary)">No blocked sites this period</p>';
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadActivity() {
        try {
            const res = await fetch(`${API}?action=activity`);
            const data = await res.json();
            if (data.success && data.activity.length > 0) {
                document.getElementById('activityItems').innerHTML = data.activity
                    .slice(0, 15)
                    .map(a => `
                        <div class="activity-item">
                            <span class="time">${new Date(a.performed_at).toLocaleString()}</span>
                            <div class="action">
                                <span class="type">${formatActionType(a.action_type)}</span>
                                ${a.details ? ` - ${a.details}` : ''}
                            </div>
                        </div>
                    `).join('');
            }
        } catch (e) { console.error(e); }
    }
    
    async function loadComparison() {
        try {
            const res = await fetch(`${API}?action=compare`);
            const data = await res.json();
            if (data.success) {
                const change = data.change_percent;
                const el = document.getElementById('screenChange');
                if (change !== 0) {
                    el.textContent = `${change > 0 ? '↑' : '↓'} ${Math.abs(change)}% vs last week`;
                    el.className = 'change ' + (change > 0 ? 'up' : 'down');
                }
            }
        } catch (e) { console.error(e); }
    }
    
    function renderChart(stats) {
        const ctx = document.getElementById('dailyChart').getContext('2d');
        
        // Group by date
        const byDate = {};
        stats.forEach(s => {
            byDate[s.stat_date] = (byDate[s.stat_date] || 0) + (s.total_minutes || 0);
        });
        
        const labels = Object.keys(byDate).sort();
        const data = labels.map(d => Math.round(byDate[d] / 60 * 10) / 10);
        
        if (dailyChart) dailyChart.destroy();
        
        dailyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.map(d => new Date(d).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })),
                datasets: [{
                    label: 'Hours',
                    data: data,
                    backgroundColor: 'rgba(0, 217, 255, 0.6)',
                    borderColor: 'rgba(0, 217, 255, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
                    x: { grid: { display: false }, ticks: { color: '#888' } }
                }
            }
        });
    }
    
    function updateCategory(cat, value, total) {
        const percent = Math.round((value || 0) / total * 100);
        document.getElementById(`${cat}Percent`).textContent = `${percent}%`;
        document.getElementById(`${cat}Bar`).style.width = `${percent}%`;
    }
    
    function formatHours(mins) {
        const h = Math.floor((mins || 0) / 60);
        const m = (mins || 0) % 60;
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }
    
    function formatActionType(type) {
        return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    async function generateReport() {
        try {
            const res = await fetch(`${API}?action=weekly_report`);
            const data = await res.json();
            if (data.success) {
                alert('Weekly report generated! Check your email.');
            }
        } catch (e) { alert('Error generating report'); }
    }
    </script>
</body>
</html>
