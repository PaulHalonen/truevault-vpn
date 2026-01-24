<?php
/**
 * TrueVault VPN - Manual Posting Queue
 * Part 15 - Manual Queue Management
 * For platforms without API access
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

$db = new SQLite3(DB_CAMPAIGNS);
$db->enableExceptions(true);

// Handle mark as posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete'])) {
    $id = intval($_POST['id']);
    $stmt = $db->prepare("UPDATE scheduled_posts SET status = 'posted', posted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Update platform success count
    $stmt = $db->prepare("SELECT platform_id FROM scheduled_posts WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $stmt = $db->prepare("UPDATE advertising_platforms SET success_count = success_count + 1, last_posted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bindValue(1, $row['platform_id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    header('Location: manual-queue.php');
    exit;
}

// Get manual queue
$result = $db->query("SELECT sp.*, cc.post_title, cc.post_content as full_content, cc.post_type, ap.platform_name, ap.platform_url 
    FROM scheduled_posts sp 
    JOIN content_calendar cc ON sp.calendar_id = cc.id 
    JOIN advertising_platforms ap ON sp.platform_id = ap.id 
    WHERE sp.status = 'manual' 
    ORDER BY sp.scheduled_for DESC");

$queue = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $queue[] = $row;
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Queue - Marketing</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-decoration: none; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-success { background: #00c853; color: #fff; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .container { padding: 25px; max-width: 1200px; margin: 0 auto; }
        .stats { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat { background: rgba(255,255,255,0.03); padding: 15px 25px; border-radius: 10px; }
        .stat .num { font-size: 2rem; font-weight: 700; color: #ffb74d; }
        .stat .label { font-size: 0.85rem; color: #888; }
        .queue-list { display: flex; flex-direction: column; gap: 15px; }
        .queue-item { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; }
        .queue-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .platform-info { display: flex; align-items: center; gap: 12px; }
        .platform-icon { width: 40px; height: 40px; background: rgba(0,217,255,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .platform-name { font-weight: 600; font-size: 1.1rem; }
        .platform-url { font-size: 0.85rem; color: #00d9ff; }
        .post-type { padding: 4px 10px; background: rgba(255,255,255,0.05); border-radius: 4px; font-size: 0.8rem; color: #888; }
        .content-preview { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }
        .queue-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .queue-actions button, .queue-actions a { padding: 10px 18px; font-size: 0.9rem; }
        .empty { text-align: center; padding: 60px 20px; color: #555; }
        .empty .icon { font-size: 4rem; margin-bottom: 15px; }
        .copy-btn { position: relative; }
        .copy-btn.copied::after { content: 'Copied!'; position: absolute; top: -25px; left: 50%; transform: translateX(-50%); background: #00c853; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✍️ Manual Posting Queue</h1>
        <a href="index.php" class="btn btn-secondary">⬅️ Dashboard</a>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat">
                <div class="num"><?= count($queue) ?></div>
                <div class="label">Pending Manual Posts</div>
            </div>
        </div>
        
        <?php if (empty($queue)): ?>
        <div class="empty">
            <div class="icon">✅</div>
            <h3>All Caught Up!</h3>
            <p>No manual posts pending. Great job!</p>
        </div>
        <?php else: ?>
        <div class="queue-list">
            <?php foreach ($queue as $item): ?>
            <div class="queue-item">
                <div class="queue-header">
                    <div class="platform-info">
                        <div class="platform-icon">📱</div>
                        <div>
                            <div class="platform-name"><?= htmlspecialchars($item['platform_name']) ?></div>
                            <a href="<?= htmlspecialchars($item['platform_url']) ?>" target="_blank" class="platform-url"><?= htmlspecialchars($item['platform_url']) ?> ↗</a>
                        </div>
                    </div>
                    <span class="post-type"><?= ucfirst($item['post_type']) ?> • <?= date('M j, g:i A', strtotime($item['scheduled_for'])) ?></span>
                </div>
                
                <div class="content-preview" id="content-<?= $item['id'] ?>"><?= htmlspecialchars($item['full_content']) ?></div>
                
                <div class="queue-actions">
                    <button class="btn btn-secondary copy-btn" onclick="copyContent(<?= $item['id'] ?>, this)">📋 Copy Text</button>
                    <a href="<?= htmlspecialchars($item['platform_url']) ?>" target="_blank" class="btn btn-primary">🔗 Open Platform</a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" name="complete" class="btn btn-success">✓ Mark as Posted</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyContent(id, btn) {
            const content = document.getElementById('content-' + id).textContent;
            navigator.clipboard.writeText(content).then(() => {
                btn.classList.add('copied');
                setTimeout(() => btn.classList.remove('copied'), 2000);
            });
        }
    </script>
</body>
</html>
