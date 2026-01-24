<?php
/**
 * TrueVault VPN - Form Library Dashboard
 * Part 14 - Task 14.4
 * Browse and manage form templates
 * 
 * USES SQLite3 (NOT PDO!)
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../../configs/config.php';

define('DB_FORMS', DB_PATH . 'forms.db');

// Check if database exists
if (!file_exists(DB_FORMS)) {
    header('Location: setup-forms.php');
    exit;
}

$category = $_GET['category'] ?? 'all';
$style = $_GET['style'] ?? 'all';
$search = $_GET['search'] ?? '';

$db = new SQLite3(DB_FORMS);
$db->enableExceptions(true);

// Categories
$categories = [
    'all' => ['name' => 'All Forms', 'icon' => '📋'],
    'customer' => ['name' => 'Customer', 'icon' => '👤'],
    'support' => ['name' => 'Support', 'icon' => '🎫'],
    'payment' => ['name' => 'Payment', 'icon' => '💳'],
    'registration' => ['name' => 'Registration', 'icon' => '📝'],
    'survey' => ['name' => 'Survey', 'icon' => '📊'],
    'lead' => ['name' => 'Lead Gen', 'icon' => '🎯'],
    'hr' => ['name' => 'HR', 'icon' => '👔'],
];

// Build query
$where = ["is_template = 1"];
$params = [];

if ($category !== 'all') {
    $where[] = "category = ?";
    $params[] = $category;
}
if ($style !== 'all') {
    $where[] = "style = ?";
    $params[] = $style;
}
if ($search) {
    $where[] = "(display_name LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql = "SELECT * FROM forms WHERE " . implode(" AND ", $where) . " ORDER BY category, display_name";
$stmt = $db->prepare($sql);
foreach ($params as $i => $p) {
    $stmt->bindValue($i + 1, $p, SQLITE3_TEXT);
}
$result = $stmt->execute();

$templates = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $templates[] = $row;
}

// Get custom forms
$customResult = $db->query("SELECT * FROM forms WHERE is_template = 0 ORDER BY display_name");
$customForms = [];
while ($row = $customResult->fetchArray(SQLITE3_ASSOC)) {
    $customForms[] = $row;
}

// Get category counts
$countResult = $db->query("SELECT category, COUNT(*) as cnt FROM forms WHERE is_template = 1 GROUP BY category");
$counts = ['all' => 0];
while ($row = $countResult->fetchArray(SQLITE3_ASSOC)) {
    $counts[$row['category']] = $row['cnt'];
    $counts['all'] += $row['cnt'];
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Library - TrueVault Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0f0f1a; color: #fff; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .header h1 { font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: linear-gradient(90deg, #00d9ff, #00ff88); color: #0f0f1a; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn:hover { transform: translateY(-2px); }
        .container { display: flex; min-height: calc(100vh - 70px); }
        .sidebar { width: 220px; background: rgba(0,0,0,0.3); border-right: 1px solid #333; padding: 20px; }
        .sidebar h3 { font-size: 0.85rem; color: #888; margin-bottom: 15px; text-transform: uppercase; }
        .sidebar ul { list-style: none; margin-bottom: 25px; }
        .sidebar li { margin-bottom: 3px; }
        .sidebar a { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; color: #ccc; text-decoration: none; transition: all 0.2s; font-size: 0.9rem; }
        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: rgba(0,217,255,0.15); color: #00d9ff; }
        .sidebar .count { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; }
        .main { flex: 1; padding: 25px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; width: 280px; }
        .style-filter { display: flex; gap: 5px; }
        .style-btn { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; background: rgba(255,255,255,0.05); color: #888; font-size: 0.85rem; transition: all 0.2s; }
        .style-btn.active { background: #00d9ff; color: #0f0f1a; }
        .style-btn:hover:not(.active) { background: rgba(255,255,255,0.1); color: #fff; }
        .section-title { font-size: 1rem; color: #888; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .forms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; margin-bottom: 35px; }
        .form-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; transition: all 0.2s; }
        .form-card:hover { border-color: #00d9ff; transform: translateY(-3px); }
        .form-card .category { font-size: 0.7rem; color: #888; text-transform: uppercase; margin-bottom: 8px; }
        .form-card h3 { font-size: 1rem; margin-bottom: 8px; }
        .form-card p { font-size: 0.85rem; color: #888; margin-bottom: 15px; line-height: 1.4; }
        .form-card .meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .form-card .style-badge { padding: 3px 10px; border-radius: 4px; font-size: 0.7rem; text-transform: uppercase; }
        .style-casual { background: rgba(255,183,77,0.2); color: #ffb74d; }
        .style-business { background: rgba(33,150,243,0.2); color: #2196f3; }
        .style-corporate { background: rgba(156,39,176,0.2); color: #9c27b0; }
        .form-card .submissions { font-size: 0.8rem; color: #666; }
        .form-card .actions { display: flex; gap: 8px; }
        .form-card .actions .btn { padding: 8px 14px; font-size: 0.8rem; }
        .empty { text-align: center; padding: 50px 20px; color: #555; }
        .empty .icon { font-size: 3.5rem; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📝 Form Library</h1>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary">⬅️ Admin Home</a>
            <a href="builder.php" class="btn btn-primary">+ Create Form</a>
        </div>
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
            
            <h3>Quick Links</h3>
            <ul>
                <li><a href="submissions.php">📥 Submissions</a></li>
                <li><a href="builder.php">🔧 Form Builder</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
            </ul>
        </div>
        
        <div class="main">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Search forms..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-secondary" onclick="searchForms()">Search</button>
                </div>
                <div class="style-filter">
                    <button class="style-btn <?= $style === 'all' ? 'active' : '' ?>" onclick="setStyle('all')">All Styles</button>
                    <button class="style-btn <?= $style === 'casual' ? 'active' : '' ?>" onclick="setStyle('casual')">Casual</button>
                    <button class="style-btn <?= $style === 'business' ? 'active' : '' ?>" onclick="setStyle('business')">Business</button>
                    <button class="style-btn <?= $style === 'corporate' ? 'active' : '' ?>" onclick="setStyle('corporate')">Corporate</button>
                </div>
            </div>
            
            <?php if (!empty($customForms)): ?>
            <div class="section-title">📁 My Custom Forms (<?= count($customForms) ?>)</div>
            <div class="forms-grid">
                <?php foreach ($customForms as $form): ?>
                <div class="form-card">
                    <div class="category"><?= $categories[$form['category']]['icon'] ?? '📋' ?> <?= htmlspecialchars($form['category']) ?></div>
                    <h3><?= htmlspecialchars($form['display_name']) ?></h3>
                    <p><?= htmlspecialchars($form['description'] ?: 'No description') ?></p>
                    <div class="meta">
                        <span class="style-badge style-<?= $form['style'] ?>"><?= $form['style'] ?></span>
                        <span class="submissions"><?= $form['submission_count'] ?> submissions</span>
                    </div>
                    <div class="actions">
                        <a href="builder.php?id=<?= $form['id'] ?>" class="btn btn-secondary">Edit</a>
                        <a href="preview.php?id=<?= $form['id'] ?>" class="btn btn-secondary">Preview</a>
                        <a href="submissions.php?form=<?= $form['id'] ?>" class="btn btn-primary">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="section-title">📚 Templates (<?= count($templates) ?>)</div>
            <?php if (empty($templates)): ?>
            <div class="empty">
                <div class="icon">📭</div>
                <h3>No Forms Found</h3>
                <p>Try a different search or category.</p>
            </div>
            <?php else: ?>
            <div class="forms-grid">
                <?php foreach ($templates as $form): ?>
                <div class="form-card">
                    <div class="category"><?= $categories[$form['category']]['icon'] ?? '📋' ?> <?= htmlspecialchars($form['category']) ?></div>
                    <h3><?= htmlspecialchars($form['display_name']) ?></h3>
                    <p><?= htmlspecialchars($form['description'] ?: 'No description') ?></p>
                    <div class="meta">
                        <span class="style-badge style-<?= $form['style'] ?>"><?= $form['style'] ?></span>
                        <span class="submissions"><?= $form['submission_count'] ?> uses</span>
                    </div>
                    <div class="actions">
                        <a href="preview.php?id=<?= $form['id'] ?>" class="btn btn-secondary">Preview</a>
                        <a href="builder.php?template=<?= $form['id'] ?>" class="btn btn-primary">Use</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function searchForms() {
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
        
        document.getElementById('searchInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') searchForms();
        });
    </script>
</body>
</html>
