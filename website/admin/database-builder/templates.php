<?php
/**
 * TrueVault VPN - Template Library
 * Part 13 - Task 13.9
 * Browse and use 150+ templates with 3 style variants
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_BUILDER', DB_PATH . 'builder.db');

$db = new SQLite3(DB_BUILDER);
$db->enableExceptions(true);

// Get categories
$categories = ['marketing', 'email', 'vpn', 'forms'];
$activeCategory = isset($_GET['category']) ? $_GET['category'] : 'marketing';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get templates
$sql = "SELECT * FROM dataforge_templates WHERE category = ?";
$params = [$activeCategory];

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR tags LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY name";
$stmt = $db->prepare($sql);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param, SQLITE3_TEXT);
}
$result = $stmt->execute();

$templates = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $templates[] = $row;
}

// Get template counts per category
$counts = [];
foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM dataforge_templates WHERE category = ?");
    $stmt->bindValue(1, $cat, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $counts[$cat] = $row['count'];
}

$db->close();

$categoryIcons = [
    'marketing' => '📣',
    'email' => '📧',
    'vpn' => '🛡️',
    'forms' => '📋'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Library - Database Builder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .back-link { color: #00d9ff; text-decoration: none; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab { padding: 12px 24px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #888; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .tab:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .tab.active { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; border-color: transparent; }
        .tab .count { font-size: 0.75rem; padding: 2px 8px; background: rgba(0,0,0,0.2); border-radius: 10px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box input { padding: 12px 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 300px; }
        .search-box input:focus { outline: none; border-color: #00d9ff; }
        .template-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .template-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; transition: all 0.2s; cursor: pointer; }
        .template-card:hover { border-color: #00d9ff; transform: translateY(-4px); }
        .template-preview { height: 150px; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .template-info { padding: 20px; }
        .template-info h3 { font-size: 1rem; margin-bottom: 8px; }
        .template-info p { color: #888; font-size: 0.85rem; margin-bottom: 12px; line-height: 1.4; }
        .template-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .template-tags .tag { padding: 3px 8px; background: rgba(255,255,255,0.08); border-radius: 4px; font-size: 0.7rem; color: #666; }
        .style-selector { display: flex; gap: 5px; }
        .style-btn { flex: 1; padding: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: #888; font-size: 0.75rem; cursor: pointer; text-align: center; }
        .style-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .style-btn.active { background: #00d9ff; color: #0f0f1a; border-color: transparent; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .empty-state { text-align: center; padding: 60px; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 20px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 16px; width: 800px; max-width: 90%; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .modal-close { background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .modal-body { flex: 1; overflow-y: auto; padding: 20px; }
        .modal-footer { padding: 20px; border-top: 1px solid #333; display: flex; justify-content: flex-end; gap: 10px; }
        .preview-box { background: #fff; color: #333; padding: 30px; border-radius: 8px; min-height: 200px; font-family: Georgia, serif; line-height: 1.6; }
        .preview-box.basic { font-family: system-ui; }
        .preview-box.executive { background: linear-gradient(135deg, #1a1a2e, #2d2d44); color: #fff; border: 2px solid #c9a227; }
        .modal-style-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .modal-style-tabs button { padding: 10px 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #888; cursor: pointer; }
        .modal-style-tabs button.active { background: #00d9ff; color: #0f0f1a; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2>📚 Template Library</h2>
        <a href="seed-templates.php" class="btn btn-primary" style="font-size:0.8rem">🔄 Reload Templates</a>
    </div>
    
    <div class="container">
        <div class="tabs">
            <?php foreach ($categories as $cat): ?>
            <a href="?category=<?= $cat ?>" class="tab <?= $activeCategory === $cat ? 'active' : '' ?>">
                <span><?= $categoryIcons[$cat] ?></span>
                <?= ucfirst($cat) ?>
                <span class="count"><?= $counts[$cat] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="toolbar">
            <form class="search-box" method="GET">
                <input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory) ?>">
                <input type="text" name="search" placeholder="🔍 Search templates..." value="<?= htmlspecialchars($search) ?>">
            </form>
            <div>
                <span style="color:#888"><?= count($templates) ?> templates</span>
            </div>
        </div>
        
        <?php if (empty($templates)): ?>
        <div class="empty-state">
            <div class="icon">📋</div>
            <h3>No Templates Found</h3>
            <p style="color:#888">Run the template seeder to populate templates.</p>
            <a href="seed-templates.php" class="btn btn-primary">🔄 Load Templates</a>
        </div>
        <?php else: ?>
        <div class="template-grid">
            <?php foreach ($templates as $tpl): 
                $tags = json_decode($tpl['tags'] ?? '[]', true) ?: [];
            ?>
            <div class="template-card" onclick="openPreview(<?= $tpl['id'] ?>)">
                <div class="template-preview"><?= $categoryIcons[$tpl['category']] ?></div>
                <div class="template-info">
                    <h3><?= htmlspecialchars($tpl['name']) ?></h3>
                    <p><?= htmlspecialchars($tpl['description']) ?></p>
                    <div class="template-tags">
                        <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                        <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="style-selector">
                        <span class="style-btn">Basic</span>
                        <span class="style-btn">Formal</span>
                        <span class="style-btn">Executive</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="previewTitle">Template Preview</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-style-tabs">
                    <button class="active" onclick="switchStyle('basic')">Basic</button>
                    <button onclick="switchStyle('formal')">Formal</button>
                    <button onclick="switchStyle('executive')">Executive</button>
                </div>
                <div id="previewContent" class="preview-box basic"></div>
            </div>
            <div class="modal-footer">
                <button class="btn" style="background:rgba(255,255,255,0.1);color:#fff" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="useTemplate()">Use Template</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentTemplate = null;
        let currentStyle = 'basic';
        
        function openPreview(id) {
            fetch('api/templates.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.template) {
                        currentTemplate = data.template;
                        document.getElementById('previewTitle').textContent = data.template.name;
                        switchStyle('basic');
                        document.getElementById('previewModal').classList.add('active');
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('previewModal').classList.remove('active');
        }
        
        function switchStyle(style) {
            currentStyle = style;
            const styles = JSON.parse(currentTemplate.styles || '{}');
            const content = styles[style]?.content || 'No content available';
            
            document.querySelectorAll('.modal-style-tabs button').forEach(b => b.classList.remove('active'));
            event.target?.classList.add('active');
            
            const box = document.getElementById('previewContent');
            box.className = 'preview-box ' + style;
            box.innerHTML = content.replace(/\n/g, '<br>');
        }
        
        function useTemplate() {
            const styles = JSON.parse(currentTemplate.styles || '{}');
            const content = styles[currentStyle]?.content || '';
            
            // Copy to clipboard
            navigator.clipboard.writeText(content).then(() => {
                alert('Template copied to clipboard!');
                closeModal();
            });
        }
        
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
