<?php
require_once 'config.php';

$campaignId = $_GET['campaign_id'] ?? null;
$db = getMarketingDB();

// Get campaign details
if ($campaignId) {
    $stmt = $db->prepare("SELECT * FROM marketing_campaigns WHERE id = ?");
    $stmt->execute([$campaignId]);
    $campaign = $stmt->fetch();
    
    // Get analytics
    $analytics = getCampaignAnalytics($campaignId);
    
    // Get campaign messages
    $stmt = $db->prepare("
        SELECT cm.*, mp.platform_name, mp.icon
        FROM campaign_messages cm
        LEFT JOIN marketing_platforms mp ON cm.platform_id = mp.id
        WHERE cm.campaign_id = ?
        ORDER BY cm.created_at DESC
    ");
    $stmt->execute([$campaignId]);
    $messages = $stmt->fetchAll();
}

// Aggregate analytics by metric
$metricsSummary = [];
if (!empty($analytics)) {
    foreach ($analytics as $analytic) {
        $metric = $analytic['metric_name'];
        if (!isset($metricsSummary[$metric])) {
            $metricsSummary[$metric] = 0;
        }
        $metricsSummary[$metric] += $analytic['metric_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Analytics - Marketing</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; }
        header { margin-bottom: 2rem; }
        .campaign-info { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .campaign-name { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .campaign-meta { color: #888; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .metric-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; text-align: center; }
        .metric-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .metric-value { font-size: 2.5rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .metric-label { color: #888; font-size: 0.85rem; margin-top: 0.5rem; }
        .section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
        .section h2 { margin-bottom: 1.5rem; }
        .messages-table { width: 100%; border-collapse: collapse; }
        .messages-table th { text-align: left; padding: 1rem; border-bottom: 2px solid #00d9ff; }
        .messages-table td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; display: inline-block; }
        .status-sent { background: rgba(0,255,136,0.2); color: #00ff88; }
        .status-pending { background: rgba(255,200,100,0.2); color: #ffb84d; }
        .status-failed { background: rgba(255,100,100,0.2); color: #ff6464; }
        .platform-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; background: rgba(0,217,255,0.2); border-radius: 6px; font-size: 0.85rem; }
        .empty-state { text-align: center; padding: 3rem; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <a href="/marketing/campaigns.php" class="back-btn">← Back to Campaigns</a>
    </header>

    <?php if ($campaign): ?>
        <div class="campaign-info">
            <div class="campaign-name"><?= htmlspecialchars($campaign['campaign_name']) ?></div>
            <div class="campaign-meta">
                <?= ucfirst($campaign['campaign_type']) ?> Campaign • 
                Status: <?= ucfirst($campaign['status']) ?> • 
                Created: <?= date('M j, Y', strtotime($campaign['created_at'])) ?>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">👁️</div>
                <div class="metric-value"><?= number_format($metricsSummary['impressions'] ?? 0) ?></div>
                <div class="metric-label">Impressions</div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">🖱️</div>
                <div class="metric-value"><?= number_format($metricsSummary['clicks'] ?? 0) ?></div>
                <div class="metric-label">Clicks</div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">📈</div>
                <div class="metric-value">
                    <?php
                    $impressions = $metricsSummary['impressions'] ?? 0;
                    $clicks = $metricsSummary['clicks'] ?? 0;
                    $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
                    echo $ctr . '%';
                    ?>
                </div>
                <div class="metric-label">Click Rate</div>
            </div>
            <div class="metric-card">
                <div class="metric-icon">✅</div>
                <div class="metric-value"><?= number_format($metricsSummary['conversions'] ?? 0) ?></div>
                <div class="metric-label">Conversions</div>
            </div>
        </div>

        <!-- Campaign Messages -->
        <div class="section">
            <h2>Campaign Messages</h2>
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📭</div>
                    <p>No messages sent yet</p>
                </div>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Message Type</th>
                            <th>Subject/Content</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td>
                                    <span class="platform-badge">
                                        <span><?= $message['icon'] ?></span>
                                        <span><?= $message['platform_name'] ?></span>
                                    </span>
                                </td>
                                <td><?= ucfirst($message['message_type']) ?></td>
                                <td>
                                    <?php if ($message['subject']): ?>
                                        <strong><?= htmlspecialchars($message['subject']) ?></strong><br>
                                    <?php endif; ?>
                                    <span style="color: #888; font-size: 0.9rem;">
                                        <?= htmlspecialchars(substr($message['body'], 0, 80)) ?>...
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $message['status'] ?>">
                                        <?= ucfirst($message['status']) ?>
                                    </span>
                                </td>
                                <td><?= $message['scheduled_time'] ? date('M j, Y g:i A', strtotime($message['scheduled_time'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Platform Performance -->
        <div class="section">
            <h2>Platform Performance</h2>
            <?php if (empty($analytics)): ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📊</div>
                    <p>No analytics data available yet</p>
                </div>
            <?php else: ?>
                <table class="messages-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($analytics, 0, 20) as $analytic): ?>
                            <tr>
                                <td>
                                    <span class="platform-badge">
                                        <span><?= $analytic['icon'] ?></span>
                                        <span><?= $analytic['platform_name'] ?></span>
                                    </span>
                                </td>
                                <td><?= ucfirst(str_replace('_', ' ', $analytic['metric_name'])) ?></td>
                                <td><strong><?= number_format($analytic['metric_value']) ?></strong></td>
                                <td><?= date('M j, Y g:i A', strtotime($analytic['metric_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <div style="font-size: 4rem; margin-bottom: 1rem;">❌</div>
            <p>Campaign not found</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
