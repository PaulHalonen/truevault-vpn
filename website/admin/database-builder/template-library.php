<?php
/**
 * TrueVault VPN - Template Library Browser
 * Part 13 - Task 13.12
 * Browse and select templates with 3 style variants
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_TEMPLATES', DB_PATH . 'templates.db');

// Check if database exists
if (!file_exists(DB_TEMPLATES)) {
    header('Location: templates/setup-templates.php');
    exit;
}

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$style = $_GET['style'] ?? 'basic';

$db = new SQLite3(DB_TEMPLATES);
$db->enableExceptions(true);

// Get categories
$categories = [
    'all' => ['name' => 'All Templates', 'icon' => '📚'],
    'marketing' => ['name' => 'Marketing', 'icon' => '📢'],
    'email' => ['name' => 'Email', 'icon' => '📧'],
    'vpn' => ['name' => 'VPN', 'icon' => '🛡️'],
    'forms' => ['name' => 'Forms', 'icon' => '📝'],
];

// Build query
$where = [];
$params = [];
if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}
if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ? OR tags LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql = "SELECT * FROM dataforge_templates";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY category, name";

$stmt = $db->prepare($sql);
foreach ($params as $i => $p) {
    $stmt->bindValue($i + 1, $p, SQLITE3_TEXT);
}
$result = $stmt->execute();

$templates = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $templates[] = $row;
}

// Get counts by category
$counts = ['all' => count($templates)];
$countResult = $db->query("SELECT category, COUNT(*) as cnt FROM dataforge_templates GROUP BY category");
while ($row = $countResult->fetchArray(SQLITE3_ASSOC)) {
    $counts[$row['category']] = $row['cnt'];
}

$db->close();
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
        .container { display: flex; min-height: calc(100vh - 70px); }
        .sidebar { width: 250px; background: rgba(0,0,0,0.3); border-right: 1px solid #333; padding: 20px; }
        .sidebar h3 { font-size: 0.9rem; color: #888; margin-bottom: 15px; text-transform: uppercase; }
        .sidebar ul { list-style: none; }
        .sidebar li { margin-bottom: 5px; }
        .sidebar a { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; color: #fff; text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); }
        .sidebar a.active { background: rgba(0,217,255,0.1); color: #00d9ff; }
        .sidebar .count { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        .main { flex: 1; padding: 25px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 300px; }
        .style-selector { display: flex; gap: 5px; }
        .style-btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; background: rgba(255,255,255,0.05); color: #888; font-weight: 500; transition: all 0.2s; }
        .style-btn.active { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .style-btn:hover:not(.active) { background: rgba(255,255,255,0.1); color: #fff; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .template-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: all 0.2s; cursor: pointer; }
        .template-card:hover { border-color: #00d9ff; transform: translateY(-2px); }
        .template-card h4 { margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .template-card .category { font-size: 0.75rem; color: #888; text-transform: uppercase; margin-bottom: 10px; }
        .template-card p { font-size: 0.9rem; color: #aaa; margin-bottom: 15px; }
        .template-card .tags { display: flex; flex-wrap: wrap; gap: 5px; }
        .template-card .tag { padding: 3px 8px; background: rgba(255,255,255,0.05); border-radius: 4px; font-size: 0.75rem; color: #666; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1a2e; border-radius: 16px; width: 800px; max-width: 95%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 1.2rem; }
        .modal-close { background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer; }
        .modal-body { flex: 1; overflow-y: auto; padding: 25px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .preview-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .preview-tab { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; background: rgba(255,255,255,0.05); color: #888; }
        .preview-tab.active { background: #00d9ff; color: #0f0f1a; }
        .preview-content { background: #0f0f1a; border: 1px solid #333; border-radius: 8px; padding: 20px; white-space: pre-wrap; font-family: monospace; font-size: 0.9rem; min-height: 200px; }
        .variables-list { margin-top: 20px; }
        .variables-list h4 { font-size: 0.9rem; color: #888; margin-bottom: 10px; }
        .variables-list .var { display: inline-block; margin: 3px; padding: 4px 10px; background: rgba(0,217,255,0.1); border: 1px solid rgba(0,217,255,0.3); border-radius: 4px; font-family: monospace; font-size: 0.8rem; color: #00d9ff; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .empty { text-align: center; padding: 60px 20px; color: #555; }
        .empty .icon { font-size: 4rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-link">⬅️ Back to Dashboard</a>
        <h2>📚 Template Library</h2>
        <div></div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h3>Categories</h3>
            <ul>
                <?php foreach ($categories as $key => $cat): ?>
                <li>
                    <a href="?category=<?= $key ?>&style=<?= $style ?>" class="<?= $category === $key ? 'active' : '' ?>">
                        <span><?= $cat['icon'] ?> <?= $cat['name'] ?></span>
                        <span class="count"><?= $counts[$key] ?? 0 ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="main">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Search templates..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-secondary" onclick="searchTemplates()">Search</button>
                </div>
                <div class="style-selector">
                    <button class="style-btn <?= $style === 'basic' ? 'active' : '' ?>" onclick="setStyle('basic')">Basic</button>
                    <button class="style-btn <?= $style === 'formal' ? 'active' : '' ?>" onclick="setStyle('formal')">Formal</button>
                    <button class="style-btn <?= $style === 'executive' ? 'active' : '' ?>" onclick="setStyle('executive')">Executive</button>
                </div>
            </div>
            
            <?php if (empty($templates)): ?>
            <div class="empty">
                <div class="icon">📭</div>
                <h3>No Templates Found</h3>
                <p>Try a different search or category.</p>
            </div>
            <?php else: ?>
            <div class="templates-grid">
                <?php foreach ($templates as $t): ?>
                <div class="template-card" onclick="openTemplate(<?= htmlspecialchars(json_encode($t)) ?>)">
                    <div class="category"><?= $categories[$t['category']]['icon'] ?? '📋' ?> <?= htmlspecialchars($t['category']) ?> / <?= htmlspecialchars($t['subcategory']) ?></div>
                    <h4><?= htmlspecialchars($t['name']) ?></h4>
                    <p><?= htmlspecialchars($t['description']) ?></p>
                    <div class="tags">
                        <?php foreach (explode(',', $t['tags'] ?? '') as $tag): ?>
                        <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Template Preview Modal -->
    <div class="modal" id="templateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Template Preview</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="preview-tabs">
                    <button class="preview-tab active" data-style="basic" onclick="showStyle('basic')">Basic</button>
                    <button class="preview-tab" data-style="formal" onclick="showStyle('formal')">Formal</button>
                    <button class="preview-tab" data-style="executive" onclick="showStyle('executive')">Executive</button>
                </div>
                <div class="preview-content" id="previewContent"></div>
                <div class="variables-list">
                    <h4>📝 Variables (replace with your data):</h4>
                    <div id="variablesList"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button class="btn btn-primary" onclick="useTemplate()">✅ Use This Template</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentTemplate = null;
        let currentStyle = '<?= $style ?>';
        
        function searchTemplates() {
            const search = document.getElementById('searchInput').value;
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            window.location = url;
        }
        
        function setStyle(style) {
            const url = new URL(window.location);
            url.searchParams.set('style', style);
            window.location = url;
        }
        
        function openTemplate(template) {
            currentTemplate = template;
            document.getElementById('modalTitle').textContent = template.name;
            showStyle(currentStyle);
            
            // Show variables
            const vars = (template.variables || '').split(',');
            document.getElementById('variablesList').innerHTML = vars.map(v => 
                `<span class="var">{${v.trim()}}</span>`
            ).join('');
            
            document.getElementById('templateModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('templateModal').classList.remove('active');
        }
        
        function showStyle(style) {
            currentStyle = style;
            document.querySelectorAll('.preview-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.preview-tab[data-style="${style}"]`).classList.add('active');
            
            const content = currentTemplate['style_' + style] || currentTemplate.style_basic || '';
            document.getElementById('previewContent').textContent = content;
        }
        
        function useTemplate() {
            const content = currentTemplate['style_' + currentStyle] || '';
            // Copy to clipboard
            navigator.clipboard.writeText(content).then(() => {
                alert('Template copied to clipboard! Paste it where you need it.');
                closeModal();
            });
        }
        
        document.getElementById('searchInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') searchTemplates();
        });
    </script>
</body>
</html>
