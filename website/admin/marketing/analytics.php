<?php
/**
 * TrueVault VPN - Marketing Analytics Dashboard
 * Part 15 - Task 15.5
 * Performance tracking and reporting
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

$db = new SQLite3(DB_CAMPAIGNS);
$db->enableExceptions(true);

$days = intval($_GET['days'] ?? 30);

// Overall stats
$stats = $db->querySingle("SELECT 
    COUNT(*) as total_posts,
    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    SUM(clicks) as clicks,
    SUM(impressions) as impressions
    FROM scheduled_posts 
    WHERE datetime(scheduled_for) >= datetime('now', '-{$days} days')", true);

// Platform performance
$platformResult = $db->query("SELECT ap.platform_name, ap.platform_type, 
    COUNT(sp.id) as posts,
    SUM(CASE WHEN sp.status = 'posted' THEN 1 ELSE 0 END) as successful,
    SUM(sp.clicks) as clicks,
    SUM(sp.impressions) as impressions
    FROM advertising_platforms ap
    LEFT JOIN scheduled_posts sp ON ap.id = sp.platform_id AND datetime(sp.scheduled_for) >= datetime('now', '-{$days} days')
    GROUP BY ap.id
    HAVING posts > 0
    ORDER BY clicks DESC
    LIMIT 10");

$topPlatforms = [];
while ($row = $platformResult->fetchArray(SQLITE3_ASSOC)) {
    $topPlatforms[] = $row;
}

// Content type performance
$contentResult = $db->query("SELECT cc.post_type, 
    COUNT(*) as posts,
    SUM(sp.clicks) as clicks,
    SUM(sp.impressions) as impressions
    FROM content_calendar cc
    JOIN scheduled_posts sp ON cc.id = sp.calendar_id
    WHERE datetime(sp.scheduled_for) >= datetime('now', '-{$days} days')
    GROUP BY cc.post_type
    ORDER BY clicks DESC");

$contentTypes = [];
while ($row = $contentResult->fetchArray(SQLITE3_ASSOC)) {
    $contentTypes[] = $row;
}

// Daily posting trend
$trendResult = $db->query("SELECT date(scheduled_for) as date, 
    COUNT(*) as posts,
    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as successful,
    SUM(clicks) as clicks
    FROM scheduled_posts 
    WHERE datetime(scheduled_for) >= datetime('now', '-{$days} days')
    GROUP BY date(scheduled_for)
    ORDER BY date DESC
    LIMIT 14");

$dailyTrend = [];
while ($row = $trendResult->fetchArray(SQLITE3_ASSOC)) {
    $dailyTrend[] = $row;
}
$dailyTrend = array_reverse($dailyTrend);

// Recent posts
$recentResult = $db->query("SELECT sp.*, cc.post_title, cc.post_type, ap.platform_name
    FROM scheduled_posts sp
    JOIN content_calendar cc ON sp.calendar_id = cc.id
    JOIN advertising_platforms ap ON sp.platform_id = ap.id
    ORDER BY sp.scheduled_for DESC
    LIMIT 20");

$recentPosts = [];
while ($row = $recentResult->fetchArray(SQLITE3_ASSOC)) {
    $recentPosts[] = $row;
}

$db->close();

// Calculate CTR
$ctr = $stats['impressions'] > 0 ? round(($stats['clicks'] / $stats['impressions']) * 100, 2) : 0;
$successRate = $stats['total_posts'] > 0 ? round(($stats['posted'] / $stats['total_posts']) * 100, 1) : 0;

$typeIcons = [
    'tip' => '💡',
    'news' => '📰',
    'testimonial' => '⭐',
    'feature' => '✨',
    'promo' => '🔥',
    'promotion' => '🎉',
    'fact' => '📊',
    'roundup' => '📋',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Marketing Automation</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-decoration: none; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        select { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; }
        .container { padding: 25px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .stat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; text-align: center; }
        .stat-card .num { font-size: 2.2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card .label { font-size: 0.85rem; color: #888; margin-top: 5px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; }
        .card h2 { font-size: 1.1rem; margin-bottom: 18px; }
        .chart-container { height: 200px; display: flex; align-items: flex-end; gap: 8px; padding: 10px 0; }
        .chart-bar { background: linear-gradient(180deg, #00d9ff, #00ff88); border-radius: 4px 4px 0 0; min-width: 30px; position: relative; transition: all 0.2s; }
        .chart-bar:hover { opacity: 0.8; }
        .chart-bar .label { position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 0.7rem; color: #888; white-space: nowrap; }
        .chart-bar .value { position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 0.75rem; color: #00d9ff; }
        .platform-list { list-style: none; }
        .platform-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 8px; margin-bottom: 8px; }
        .platform-item .rank { width: 28px; height: 28px; background: rgba(0,217,255,0.15); color: #00d9ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; margin-right: 12px; }
        .platform-item .name { flex: 1; }
        .platform-item .stats { display: flex; gap: 20px; font-size: 0.85rem; }
        .platform-item .stats span { color: #888; }
        .platform-item .stats strong { color: #00ff88; }
        .content-list { list-style: none; }
        .content-item { display: flex; align-items: center; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px; margin-bottom: 8px; }
        .content-item .icon { font-size: 1.5rem; margin-right: 12px; }
        .content-item .info { flex: 1; }
        .content-item .type { font-weight: 500; }
        .content-item .meta { font-size: 0.8rem; color: #888; }
        .content-item .clicks { color: #00ff88; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: #888; font-weight: 500; }
        .status { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; }
        .status-posted { background: rgba(0,200,83,0.2); color: #00c853; }
        .status-failed { background: rgba(255,80,80,0.2); color: #ff5050; }
        .status-pending { background: rgba(255,183,77,0.2); color: #ffb74d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Marketing Analytics</h1>
        <div class="header-actions">
            <select onchange="window.location='?days='+this.value">
                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>Last 7 days</option>
                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Last 30 days</option>
                <option value="90" <?= $days == 90 ? 'selected' : '' ?>>Last 90 days</option>
                <option value="365" <?= $days == 365 ? 'selected' : '' ?>>Last year</option>
            </select>
            <a href="index.php" class="btn btn-secondary">⬅️ Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="num"><?= number_format($stats['total_posts'] ?? 0) ?></div>
                <div class="label">Total Posts</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= number_format($stats['impressions'] ?? 0) ?></div>
                <div class="label">Impressions</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= number_format($stats['clicks'] ?? 0) ?></div>
                <div class="label">Clicks</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $ctr ?>%</div>
                <div class="label">Click-Through Rate</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $successRate ?>%</div>
                <div class="label">Success Rate</div>
            </div>
        </div>
        
        <div class="grid">
            <div class="card">
                <h2>📈 Daily Posting Trend</h2>
                <div class="chart-container">
                    <?php 
                    $maxPosts = max(array_column($dailyTrend, 'posts') ?: [1]);
                    foreach ($dailyTrend as $day): 
                        $height = $maxPosts > 0 ? ($day['posts'] / $maxPosts) * 160 : 0;
                    ?>
                    <div class="chart-bar" style="height: <?= max($height, 5) ?>px; flex: 1;">
                        <span class="value"><?= $day['posts'] ?></span>
                        <span class="label"><?= date('M j', strtotime($day['date'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($dailyTrend)): ?>
                    <div style="text-align:center;width:100%;color:#555;padding:50px;">No data available</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>🏆 Top Platforms (by Clicks)</h2>
                <ul class="platform-list">
                    <?php foreach ($topPlatforms as $i => $platform): ?>
                    <li class="platform-item">
                        <span class="rank"><?= $i + 1 ?></span>
                        <span class="name"><?= htmlspecialchars($platform['platform_name']) ?></span>
                        <div class="stats">
                            <span><strong><?= $platform['clicks'] ?? 0 ?></strong> clicks</span>
                            <span><strong><?= $platform['successful'] ?></strong>/<?= $platform['posts'] ?> posts</span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($topPlatforms)): ?>
                    <li style="text-align:center;padding:30px;color:#555;">No data available</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="card">
                <h2>📝 Content Type Performance</h2>
                <ul class="content-list">
                    <?php foreach ($contentTypes as $ct): ?>
                    <li class="content-item">
                        <span class="icon"><?= $typeIcons[$ct['post_type']] ?? '📌' ?></span>
                        <div class="info">
                            <div class="type"><?= ucfirst($ct['post_type']) ?></div>
                            <div class="meta"><?= $ct['posts'] ?> posts</div>
                        </div>
                        <span class="clicks"><?= $ct['clicks'] ?? 0 ?> clicks</span>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($contentTypes)): ?>
                    <li style="text-align:center;padding:30px;color:#555;">No data available</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Recent Posts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Platform</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $post): ?>
                    <tr>
                        <td><?= date('M j, g:i A', strtotime($post['scheduled_for'])) ?></td>
                        <td><?= htmlspecialchars($post['platform_name']) ?></td>
                        <td><?= htmlspecialchars(substr($post['post_title'], 0, 40)) ?>...</td>
                        <td><span class="status status-<?= $post['status'] ?>"><?= $post['status'] ?></span></td>
                        <td><?= $post['clicks'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentPosts)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#555;">No posts yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
