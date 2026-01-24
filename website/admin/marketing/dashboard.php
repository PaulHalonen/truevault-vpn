<?php
/**
 * TrueVault VPN - Marketing Automation Dashboard
 * Part 15 - Task 15.7
 * Main control center for marketing automation
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

// Check if database exists
if (!file_exists(DB_CAMPAIGNS)) {
    header('Location: setup-campaigns.php');
    exit;
}

$db = new SQLite3(DB_CAMPAIGNS);
$db->enableExceptions(true);

// Get settings
$settings = [];
$result = $db->query("SELECT setting_key, setting_value FROM automation_settings");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get stats
$stats = [
    'total_platforms' => $db->querySingle("SELECT COUNT(*) FROM advertising_platforms WHERE is_active = 1"),
    'api_platforms' => $db->querySingle("SELECT COUNT(*) FROM advertising_platforms WHERE api_available = 1 AND is_active = 1"),
    'total_content' => $db->querySingle("SELECT COUNT(*) FROM content_calendar"),
    'posts_today' => $db->querySingle("SELECT COUNT(*) FROM scheduled_posts WHERE DATE(scheduled_for) = DATE('now')"),
    'pending_posts' => $db->querySingle("SELECT COUNT(*) FROM scheduled_posts WHERE status = 'pending'"),
    'manual_queue' => $db->querySingle("SELECT COUNT(*) FROM manual_post_queue WHERE status = 'pending'"),
    'total_clicks' => $db->querySingle("SELECT SUM(clicks) FROM marketing_analytics") ?? 0,
    'total_impressions' => $db->querySingle("SELECT SUM(impressions) FROM marketing_analytics") ?? 0,
];

// Get today's scheduled posts
$todayPosts = [];
$result = $db->query("
    SELECT sp.*, ap.platform_name, ap.platform_type 
    FROM scheduled_posts sp 
    JOIN advertising_platforms ap ON sp.platform_id = ap.id 
    WHERE DATE(sp.scheduled_for) = DATE('now')
    ORDER BY sp.scheduled_for
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $todayPosts[] = $row;
}

// Get manual queue
$manualQueue = [];
$result = $db->query("
    SELECT mq.*, ap.platform_name 
    FROM manual_post_queue mq 
    JOIN advertising_platforms ap ON mq.platform_id = ap.id 
    WHERE mq.status = 'pending'
    ORDER BY mq.created_at DESC
    LIMIT 10
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $manualQueue[] = $row;
}

// Get recent activity
$recentActivity = [];
$result = $db->query("
    SELECT sp.*, ap.platform_name 
    FROM scheduled_posts sp 
    JOIN advertising_platforms ap ON sp.platform_id = ap.id 
    WHERE sp.status IN ('posted', 'failed')
    ORDER BY sp.posted_at DESC
    LIMIT 10
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recentActivity[] = $row;
}

$db->close();

$automationEnabled = ($settings['automation_enabled'] ?? '1') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Automation - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.4rem; display: flex; align-items: center; gap: 10px; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-danger { background: rgba(255,80,80,0.2); color: #ff5050; }
        .btn-success { background: rgba(0,200,83,0.2); color: #00c853; }
        .btn:hover { transform: translateY(-2px); }
        .container { padding: 25px; max-width: 1400px; margin: 0 auto; }
        
        .status-banner { padding: 15px 25px; border-radius: 10px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .status-banner.active { background: rgba(0,200,83,0.15); border: 1px solid rgba(0,200,83,0.3); }
        .status-banner.inactive { background: rgba(255,193,7,0.15); border: 1px solid rgba(255,193,7,0.3); }
        .status-banner .status-text { display: flex; align-items: center; gap: 12px; }
        .status-indicator { width: 12px; height: 12px; border-radius: 50%; animation: pulse 2s infinite; }
        .status-indicator.active { background: #00c853; }
        .status-indicator.inactive { background: #ffc107; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .number { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card .label { font-size: 0.85rem; color: #888; margin-top: 5px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media (max-width: 1000px) { .grid-2 { grid-template-columns: 1fr; } }
        
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { font-size: 1.1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .card h2 .badge { font-size: 0.75rem; padding: 3px 10px; background: rgba(0,217,255,0.2); border-radius: 10px; color: #00d9ff; }
        
        .post-list { display: flex; flex-direction: column; gap: 10px; }
        .post-item { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; }
        .post-item .info { flex: 1; }
        .post-item .platform { font-size: 0.8rem; color: #888; margin-bottom: 3px; }
        .post-item .title { font-size: 0.9rem; }
        .post-item .status { padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; }
        .status-posted { background: rgba(0,200,83,0.2); color: #00c853; }
        .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .status-failed { background: rgba(255,80,80,0.2); color: #ff5050; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .quick-actions .btn { justify-content: center; padding: 15px; }
        
        .empty { text-align: center; padding: 30px; color: #555; }
        .empty .icon { font-size: 2.5rem; margin-bottom: 10px; }
        
        .manual-item { background: rgba(0,0,0,0.2); border-radius: 8px; padding: 15px; margin-bottom: 10px; }
        .manual-item .header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .manual-item .platform { font-weight: 600; color: #00d9ff; }
        .manual-item .content { font-size: 0.85rem; color: #aaa; margin-bottom: 10px; white-space: pre-wrap; max-height: 100px; overflow-y: auto; }
        .manual-item .actions { display: flex; gap: 8px; }
        .manual-item .btn { padding: 6px 12px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Marketing Automation</h1>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">⬅️ Admin</a>
            <a href="platforms.php" class="btn btn-secondary">📱 Platforms</a>
            <a href="calendar.php" class="btn btn-secondary">📅 Calendar</a>
            <a href="analytics.php" class="btn btn-primary">📊 Analytics</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Status Banner -->
        <div class="status-banner <?= $automationEnabled ? 'active' : 'inactive' ?>">
            <div class="status-text">
                <div class="status-indicator <?= $automationEnabled ? 'active' : 'inactive' ?>"></div>
                <div>
                    <strong>Automation Status:</strong> 
                    <?= $automationEnabled ? 'ACTIVE - Posting daily at ' . ($settings['post_time_hour'] ?? '9') . ':' . str_pad($settings['post_time_minute'] ?? '0', 2, '0', STR_PAD_LEFT) : 'PAUSED' ?>
                </div>
            </div>
            <div>
                <?php if ($automationEnabled): ?>
                <button class="btn btn-danger" onclick="toggleAutomation(false)">⏸️ Pause</button>
                <?php else: ?>
                <button class="btn btn-success" onclick="toggleAutomation(true)">▶️ Resume</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $stats['total_platforms'] ?></div>
                <div class="label">Active Platforms</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['api_platforms'] ?></div>
                <div class="label">API Integrations</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['total_content'] ?></div>
                <div class="label">Calendar Days</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $stats['pending_posts'] ?></div>
                <div class="label">Pending Posts</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= number_format($stats['total_clicks']) ?></div>
                <div class="label">Total Clicks</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= number_format($stats['total_impressions']) ?></div>
                <div class="label">Impressions</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <div class="quick-actions">
                <button class="btn btn-primary" onclick="runNow()">▶️ Post Now</button>
                <a href="calendar-generator.php" class="btn btn-secondary">📅 Generate Calendar</a>
                <a href="create-post.php" class="btn btn-secondary">✏️ Create Post</a>
                <a href="templates.php" class="btn btn-secondary">📝 Templates</a>
                <a href="settings.php" class="btn btn-secondary">⚙️ Settings</a>
                <a href="export.php" class="btn btn-secondary">📤 Export Data</a>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Today's Posts -->
            <div class="card">
                <h2>📅 Today's Posts <span class="badge"><?= count($todayPosts) ?></span></h2>
                <?php if (empty($todayPosts)): ?>
                <div class="empty">
                    <div class="icon">📭</div>
                    <p>No posts scheduled for today</p>
                </div>
                <?php else: ?>
                <div class="post-list">
                    <?php foreach ($todayPosts as $post): ?>
                    <div class="post-item">
                        <div class="info">
                            <div class="platform"><?= htmlspecialchars($post['platform_name']) ?></div>
                            <div class="title"><?= htmlspecialchars(substr($post['post_title'] ?? $post['post_content'], 0, 50)) ?>...</div>
                        </div>
                        <span class="status status-<?= $post['status'] ?>"><?= $post['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <h2>📊 Recent Activity</h2>
                <?php if (empty($recentActivity)): ?>
                <div class="empty">
                    <div class="icon">📈</div>
                    <p>No recent activity</p>
                </div>
                <?php else: ?>
                <div class="post-list">
                    <?php foreach ($recentActivity as $post): ?>
                    <div class="post-item">
                        <div class="info">
                            <div class="platform"><?= htmlspecialchars($post['platform_name']) ?> • <?= date('M j', strtotime($post['posted_at'])) ?></div>
                            <div class="title"><?= htmlspecialchars(substr($post['post_title'] ?? $post['post_content'], 0, 40)) ?>...</div>
                        </div>
                        <span class="status status-<?= $post['status'] ?>"><?= $post['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Manual Queue -->
        <div class="card">
            <h2>📋 Manual Post Queue <span class="badge"><?= count($manualQueue) ?></span></h2>
            <p style="color:#888;font-size:0.85rem;margin-bottom:15px;">These platforms don't have APIs - copy and paste manually</p>
            
            <?php if (empty($manualQueue)): ?>
            <div class="empty">
                <div class="icon">✅</div>
                <p>No manual posts pending</p>
            </div>
            <?php else: ?>
            <?php foreach ($manualQueue as $item): ?>
            <div class="manual-item">
                <div class="header">
                    <span class="platform"><?= htmlspecialchars($item['platform_name']) ?></span>
                    <span style="color:#666;font-size:0.8rem;"><?= date('M j, g:i A', strtotime($item['created_at'])) ?></span>
                </div>
                <div class="content"><?= htmlspecialchars($item['post_content']) ?></div>
                <div class="actions">
                    <button class="btn btn-primary" onclick="copyContent(this)" data-content="<?= htmlspecialchars($item['post_content']) ?>">📋 Copy</button>
                    <button class="btn btn-success" onclick="markComplete(<?= $item['id'] ?>)">✅ Done</button>
                    <button class="btn btn-secondary" onclick="skipPost(<?= $item['id'] ?>)">⏭️ Skip</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleAutomation(enable) {
            fetch('api/settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({setting_key: 'automation_enabled', setting_value: enable ? '1' : '0'})
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function runNow() {
            if (confirm('Run the daily posting automation now?')) {
                fetch('api/run-automation.php', {method: 'POST'})
                    .then(r => r.json())
                    .then(data => {
                        alert(data.message || 'Automation triggered!');
                        location.reload();
                    });
            }
        }
        
        function copyContent(btn) {
            const content = btn.dataset.content;
            navigator.clipboard.writeText(content).then(() => {
                btn.textContent = '✅ Copied!';
                setTimeout(() => btn.textContent = '📋 Copy', 2000);
            });
        }
        
        function markComplete(id) {
            fetch('api/manual-queue.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, action: 'complete'})
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
        
        function skipPost(id) {
            fetch('api/manual-queue.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, action: 'skip'})
            })
            .then(r => r.json())
            .then(() => location.reload());
        }
    </script>
</body>
</html>
