<?php
require_once 'config.php';

$stats = getMarketingStats();
$campaigns = getCampaigns();
$platforms = getPlatformsByType();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Automation - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        header h1 { font-size: 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .btn { padding: 0.75rem 1.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn:hover { transform: translateY(-2px); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; }
        .stat-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section h2 { margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .platforms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
        .platform-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; text-align: center; cursor: pointer; transition: 0.3s; }
        .platform-card:hover { border-color: #00d9ff; background: rgba(0,217,255,0.1); }
        .platform-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .platform-name { font-size: 0.85rem; font-weight: 600; }
        .platform-type { font-size: 0.75rem; color: #666; margin-top: 0.25rem; }
        .campaigns-list { list-style: none; }
        .campaign-item { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .campaign-info h3 { margin-bottom: 0.5rem; }
        .campaign-info p { color: #888; font-size: 0.9rem; }
        .campaign-status { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; }
        .status-draft { background: rgba(150,150,150,0.2); color: #999; }
        .status-active { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-paused { background: rgba(255,200,100,0.2); color: #ffb84d; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>📊 Marketing Automation</h1>
        <button class="btn" onclick="window.location.href='/marketing/campaigns.php?action=create'">+ New Campaign</button>
    </header>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-value"><?= $stats['total_campaigns'] ?></div>
            <div class="stat-label">Total Campaigns</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-value"><?= $stats['active_campaigns'] ?></div>
            <div class="stat-label">Active Campaigns</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔗</div>
            <div class="stat-value"><?= $stats['connected_platforms'] ?></div>
            <div class="stat-label">Connected Platforms</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📧</div>
            <div class="stat-value"><?= $stats['messages_today'] ?></div>
            <div class="stat-label">Messages Today</div>
        </div>
    </div>

    <!-- Connected Platforms -->
    <div class="section">
        <h2>🔗 Marketing Platforms (<?= count($platforms) ?>)</h2>
        <div class="platforms-grid">
            <?php foreach ($platforms as $platform): ?>
                <div class="platform-card" onclick="window.location.href='/marketing/platforms.php?id=<?= $platform['id'] ?>'">
                    <div class="platform-icon"><?= $platform['icon'] ?></div>
                    <div class="platform-name"><?= htmlspecialchars($platform['platform_name']) ?></div>
                    <div class="platform-type"><?= ucfirst($platform['platform_type']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Campaigns -->
    <div class="section">
        <h2>📋 Recent Campaigns</h2>
        <?php if (empty($campaigns)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>No campaigns yet. Create your first marketing campaign!</p>
            </div>
        <?php else: ?>
            <ul class="campaigns-list">
                <?php foreach (array_slice($campaigns, 0, 5) as $campaign): ?>
                    <li class="campaign-item" onclick="window.location.href='/marketing/campaigns.php?id=<?= $campaign['id'] ?>'">
                        <div class="campaign-info">
                            <h3><?= htmlspecialchars($campaign['campaign_name']) ?></h3>
                            <p><?= ucfirst($campaign['campaign_type']) ?> Campaign • Created <?= date('M j, Y', strtotime($campaign['created_at'])) ?></p>
                        </div>
                        <span class="campaign-status status-<?= $campaign['status'] ?>">
                            <?= ucfirst($campaign['status']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
