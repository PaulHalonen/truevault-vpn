<?php
/**
 * TrueVault VPN - Marketing Dashboard
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

// Get stats
$stats = [
    'platforms' => $db->querySingle("SELECT COUNT(*) FROM advertising_platforms WHERE is_active = 1"),
    'calendar_days' => $db->querySingle("SELECT COUNT(*) FROM content_calendar"),
    'posts_today' => $db->querySingle("SELECT COUNT(*) FROM content_calendar WHERE calendar_date = date('now')"),
    'scheduled' => $db->querySingle("SELECT COUNT(*) FROM scheduled_posts WHERE status = 'pending'"),
    'posted_today' => $db->querySingle("SELECT COUNT(*) FROM scheduled_posts WHERE status = 'posted' AND date(posted_at) = date('now')"),
];

// Get today's content
$todayResult = $db->query("SELECT * FROM content_calendar WHERE calendar_date = date('now')");
$todayContent = [];
while ($row = $todayResult->fetchArray(SQLITE3_ASSOC)) {
    $todayContent[] = $row;
}

// Get upcoming holidays
$holidayResult = $db->query("SELECT * FROM content_calendar WHERE is_holiday = 1 AND calendar_date >= date('now') ORDER BY calendar_date LIMIT 5");
$upcomingHolidays = [];
while ($row = $holidayResult->fetchArray(SQLITE3_ASSOC)) {
    $upcomingHolidays[] = $row;
}

// Get platform stats by type
$platformStats = [];
$platformResult = $db->query("SELECT platform_type, COUNT(*) as cnt, SUM(success_count) as successes FROM advertising_platforms GROUP BY platform_type");
while ($row = $platformResult->fetchArray(SQLITE3_ASSOC)) {
    $platformStats[$row['platform_type']] = $row;
}

// Get recent activity
$recentResult = $db->query("SELECT sp.*, cc.post_title, ap.platform_name FROM scheduled_posts sp 
    JOIN content_calendar cc ON sp.calendar_id = cc.id 
    JOIN advertising_platforms ap ON sp.platform_id = ap.id 
    ORDER BY sp.id DESC LIMIT 10");
$recentActivity = [];
while ($row = $recentResult->fetchArray(SQLITE3_ASSOC)) {
    $recentActivity[] = $row;
}

$db->close();

$typeIcons = [
    'social' => '📱',
    'press_release' => '📰',
    'classified' => '📋',
    'directory' => '📍',
    'content' => '✍️',
];
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
        .header h1 { font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-success { background: #00c853; color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .container { padding: 25px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .num { font-size: 2.2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card .label { font-size: 0.85rem; color: #888; margin-top: 5px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; }
        .card h2 { font-size: 1.1rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
        .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .quick-actions a { padding: 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; text-decoration: none; color: #fff; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s; }
        .quick-actions a:hover { background: rgba(0,217,255,0.1); border-color: #00d9ff; transform: translateY(-3px); }
        .quick-actions .icon { font-size: 1.8rem; }
        .quick-actions .name { font-size: 0.9rem; font-weight: 500; }
        .today-content { list-style: none; }
        .today-content li { padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; margin-bottom: 10px; }
        .today-content .title { font-weight: 600; margin-bottom: 5px; }
        .today-content .meta { font-size: 0.8rem; color: #888; display: flex; gap: 15px; }
        .today-content .type { color: #00d9ff; }
        .platform-list { display: flex; flex-direction: column; gap: 8px; }
        .platform-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: rgba(255,255,255,0.02); border-radius: 8px; }
        .platform-item .name { display: flex; align-items: center; gap: 8px; }
        .platform-item .count { color: #00ff88; font-weight: 600; }
        .holiday-list { list-style: none; }
        .holiday-list li { padding: 12px; background: rgba(255,183,77,0.08); border-left: 3px solid #ffb74d; border-radius: 0 8px 8px 0; margin-bottom: 10px; }
        .holiday-list .date { font-size: 0.8rem; color: #888; }
        .holiday-list .name { font-weight: 600; color: #ffb74d; }
        .activity-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .activity-table th, .activity-table td { padding: 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .activity-table th { color: #888; font-weight: 500; }
        .status { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; }
        .status-pending { background: rgba(255,183,77,0.2); color: #ffb74d; }
        .status-posted { background: rgba(0,200,83,0.2); color: #00c853; }
        .status-failed { background: rgba(255,80,80,0.2); color: #ff5050; }
        .empty { text-align: center; padding: 30px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Marketing Automation</h1>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">⬅️ Admin Home</a>
            <a href="automation-engine.php?action=run" class="btn btn-success">▶️ Run Now</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="num"><?= $stats['platforms'] ?></div>
                <div class="label">Active Platforms</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $stats['calendar_days'] ?></div>
                <div class="label">Days of Content</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $stats['scheduled'] ?></div>
                <div class="label">Scheduled Posts</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $stats['posted_today'] ?></div>
                <div class="label">Posted Today</div>
            </div>
        </div>
        
        <div class="grid">
            <div class="card">
                <h2>⚡ Quick Actions</h2>
                <div class="quick-actions">
                    <a href="platforms.php">
                        <span class="icon">📱</span>
                        <span class="name">Platforms (<?= $stats['platforms'] ?>)</span>
                    </a>
                    <a href="calendar.php">
                        <span class="icon">📅</span>
                        <span class="name">Content Calendar</span>
                    </a>
                    <a href="calendar-generator.php">
                        <span class="icon">🔄</span>
                        <span class="name">Generate Calendar</span>
                    </a>
                    <a href="analytics.php">
                        <span class="icon">📊</span>
                        <span class="name">Analytics</span>
                    </a>
                    <a href="templates.php">
                        <span class="icon">📝</span>
                        <span class="name">Templates</span>
                    </a>
                    <a href="manual-queue.php">
                        <span class="icon">✍️</span>
                        <span class="name">Manual Queue</span>
                    </a>
                </div>
            </div>
            
            <div class="card">
                <h2>📰 Today's Content</h2>
                <?php if (empty($todayContent)): ?>
                <div class="empty">No content scheduled for today</div>
                <?php else: ?>
                <ul class="today-content">
                    <?php foreach ($todayContent as $content): ?>
                    <li>
                        <div class="title"><?= htmlspecialchars($content['post_title']) ?></div>
                        <div class="meta">
                            <span class="type"><?= ucfirst($content['post_type']) ?></span>
                            <span><?= count(json_decode($content['platforms'], true) ?: []) ?> platforms</span>
                            <span><?= $content['is_posted'] ? '✅ Posted' : '⏳ Pending' ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>📍 Platforms by Type</h2>
                <div class="platform-list">
                    <?php foreach ($platformStats as $type => $data): ?>
                    <div class="platform-item">
                        <span class="name">
                            <span><?= $typeIcons[$type] ?? '📌' ?></span>
                            <span><?= ucwords(str_replace('_', ' ', $type)) ?></span>
                        </span>
                        <span class="count"><?= $data['cnt'] ?> platforms</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>🎉 Upcoming Holidays</h2>
                <?php if (empty($upcomingHolidays)): ?>
                <div class="empty">No upcoming holidays</div>
                <?php else: ?>
                <ul class="holiday-list">
                    <?php foreach ($upcomingHolidays as $holiday): ?>
                    <li>
                        <div class="date"><?= date('M j, Y', strtotime($holiday['calendar_date'])) ?></div>
                        <div class="name"><?= htmlspecialchars($holiday['holiday_name']) ?></div>
                        <?php if ($holiday['pricing_override']): 
                            $pricing = json_decode($holiday['pricing_override'], true);
                        ?>
                        <div style="font-size:0.85rem;color:#00ff88;"><?= $pricing['discount'] ?>% OFF • Code: <?= $pricing['code'] ?></div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card" style="margin-top:20px;">
            <h2>📋 Recent Activity</h2>
            <?php if (empty($recentActivity)): ?>
            <div class="empty">No recent activity</div>
            <?php else: ?>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivity as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['platform_name']) ?></td>
                        <td><?= htmlspecialchars(substr($activity['post_title'] ?? 'Post', 0, 40)) ?>...</td>
                        <td><span class="status status-<?= $activity['status'] ?>"><?= $activity['status'] ?></span></td>
                        <td><?= date('M j, g:i A', strtotime($activity['scheduled_for'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
