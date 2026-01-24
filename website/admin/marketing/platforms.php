<?php
/**
 * TrueVault VPN - Platform Management
 * Part 15 - Task 15.2
 * Manage 50+ advertising platforms
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_CAMPAIGNS', DB_PATH . 'campaigns.db');

$db = new SQLite3(DB_CAMPAIGNS);
$db->enableExceptions(true);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $db->exec("UPDATE advertising_platforms SET is_active = NOT is_active WHERE id = {$id}");
    } elseif ($action === 'update_key') {
        $id = intval($_POST['id'] ?? 0);
        $key = $_POST['api_key'] ?? '';
        $stmt = $db->prepare("UPDATE advertising_platforms SET api_key = ? WHERE id = ?");
        $stmt->bindValue(1, $key, SQLITE3_TEXT);
        $stmt->bindValue(2, $id, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    header('Location: platforms.php');
    exit;
}

$type = $_GET['type'] ?? '';

// Get platforms
$where = $type ? "WHERE platform_type = '" . $db->escapeString($type) . "'" : "";
$result = $db->query("SELECT * FROM advertising_platforms {$where} ORDER BY platform_type, platform_name");

$platforms = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $platforms[] = $row;
}

// Get type counts
$typeResult = $db->query("SELECT platform_type, COUNT(*) as cnt FROM advertising_platforms GROUP BY platform_type");
$typeCounts = [];
while ($row = $typeResult->fetchArray(SQLITE3_ASSOC)) {
    $typeCounts[$row['platform_type']] = $row['cnt'];
}

$db->close();

$typeNames = [
    'social' => ['name' => 'Social Media', 'icon' => '📱'],
    'press_release' => ['name' => 'Press Releases', 'icon' => '📰'],
    'classified' => ['name' => 'Classifieds', 'icon' => '📋'],
    'directory' => ['name' => 'Directories', 'icon' => '📍'],
    'content' => ['name' => 'Content Platforms', 'icon' => '✍️'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platforms - Marketing Automation</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .container { display: flex; min-height: calc(100vh - 70px); }
        .sidebar { width: 220px; background: rgba(0,0,0,0.3); border-right: 1px solid #333; padding: 20px; }
        .sidebar h3 { font-size: 0.85rem; color: #888; margin-bottom: 15px; text-transform: uppercase; }
        .sidebar ul { list-style: none; }
        .sidebar a { display: flex; justify-content: space-between; padding: 10px 12px; border-radius: 8px; color: #ccc; text-decoration: none; margin-bottom: 5px; font-size: 0.9rem; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .sidebar .count { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; }
        .main { flex: 1; padding: 25px; }
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .search { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 300px; }
        .platform-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
        .platform-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: all 0.2s; }
        .platform-card.inactive { opacity: 0.5; }
        .platform-card:hover { border-color: #00d9ff; }
        .platform-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .platform-name { font-size: 1.1rem; font-weight: 600; }
        .platform-type { font-size: 0.75rem; color: #888; background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
        .platform-url { font-size: 0.85rem; color: #00d9ff; margin-bottom: 15px; word-break: break-all; }
        .platform-stats { display: flex; gap: 15px; margin-bottom: 15px; }
        .platform-stat { font-size: 0.85rem; }
        .platform-stat .value { color: #00ff88; font-weight: 600; }
        .platform-stat .label { color: #888; }
        .platform-badges { display: flex; gap: 8px; margin-bottom: 15px; }
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; }
        .badge-api { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .badge-manual { background: rgba(255,183,77,0.15); color: #ffb74d; }
        .badge-freq { background: rgba(255,255,255,0.05); color: #888; }
        .platform-actions { display: flex; gap: 10px; }
        .platform-actions button { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .platform-actions .toggle-on { background: rgba(0,200,83,0.2); color: #00c853; }
        .platform-actions .toggle-off { background: rgba(255,80,80,0.2); color: #ff5050; }
        .platform-actions .settings { background: rgba(255,255,255,0.05); color: #888; }
        .empty { text-align: center; padding: 50px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📱 Advertising Platforms</h1>
        <a href="index.php" class="btn btn-secondary">⬅️ Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h3>Platform Types</h3>
            <ul>
                <li><a href="platforms.php" class="<?= !$type ? 'active' : '' ?>">
                    <span>All Platforms</span>
                    <span class="count"><?= count($platforms) ?></span>
                </a></li>
                <?php foreach ($typeNames as $key => $info): ?>
                <li><a href="?type=<?= $key ?>" class="<?= $type === $key ? 'active' : '' ?>">
                    <span><?= $info['icon'] ?> <?= $info['name'] ?></span>
                    <span class="count"><?= $typeCounts[$key] ?? 0 ?></span>
                </a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="main">
            <div class="toolbar">
                <input type="text" class="search" id="searchInput" placeholder="🔍 Search platforms...">
            </div>
            
            <div class="platform-grid" id="platformGrid">
                <?php foreach ($platforms as $p): ?>
                <div class="platform-card <?= $p['is_active'] ? '' : 'inactive' ?>" data-name="<?= strtolower($p['platform_name']) ?>">
                    <div class="platform-header">
                        <div>
                            <div class="platform-name"><?= htmlspecialchars($p['platform_name']) ?></div>
                        </div>
                        <span class="platform-type"><?= $typeNames[$p['platform_type']]['icon'] ?? '📌' ?> <?= ucwords(str_replace('_', ' ', $p['platform_type'])) ?></span>
                    </div>
                    
                    <div class="platform-url">
                        <a href="<?= htmlspecialchars($p['platform_url']) ?>" target="_blank"><?= htmlspecialchars($p['platform_url']) ?></a>
                    </div>
                    
                    <div class="platform-badges">
                        <?php if ($p['api_available']): ?>
                        <span class="badge badge-api">🔌 API</span>
                        <?php else: ?>
                        <span class="badge badge-manual">✍️ Manual</span>
                        <?php endif; ?>
                        <span class="badge badge-freq"><?= ucfirst($p['posting_frequency']) ?></span>
                    </div>
                    
                    <div class="platform-stats">
                        <div class="platform-stat">
                            <span class="value"><?= $p['success_count'] ?></span>
                            <span class="label">Posts</span>
                        </div>
                        <div class="platform-stat">
                            <span class="value"><?= $p['failure_count'] ?></span>
                            <span class="label">Failed</span>
                        </div>
                        <?php if ($p['last_posted_at']): ?>
                        <div class="platform-stat">
                            <span class="label">Last: <?= date('M j', strtotime($p['last_posted_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="platform-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="<?= $p['is_active'] ? 'toggle-on' : 'toggle-off' ?>">
                                <?= $p['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                            </button>
                        </form>
                        <?php if ($p['api_available']): ?>
                        <button class="settings" onclick="configureAPI(<?= $p['id'] ?>, '<?= htmlspecialchars($p['platform_name']) ?>')">⚙️ API Config</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll('.platform-card').forEach(card => {
                const name = card.dataset.name;
                card.style.display = name.includes(search) ? '' : 'none';
            });
        });
        
        function configureAPI(id, name) {
            const key = prompt(`Enter API key for ${name}:`);
            if (key !== null) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_key">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="api_key" value="${key}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
