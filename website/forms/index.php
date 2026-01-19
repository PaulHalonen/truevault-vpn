<?php
require_once 'config.php';

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$templates = getFormTemplates($category);
$categories = getTemplateCategories();

// Filter by search
if ($search) {
    $templates = array_filter($templates, function($t) use ($search) {
        return stripos($t['template_name'], $search) !== false || 
               stripos($t['description'], $search) !== false;
    });
}

// Group templates by category for display
$grouped = [];
foreach ($templates as $template) {
    $grouped[$template['category']][] = $template;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Library - 50+ Templates</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .header p { color: #888; font-size: 1.2rem; }
        .search-bar { max-width: 600px; margin: 2rem auto; }
        .search-bar input { width: 100%; padding: 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: #fff; font-size: 1rem; }
        .categories { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }
        .category-tag { padding: 0.5rem 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; text-decoration: none; color: #fff; transition: 0.3s; }
        .category-tag:hover, .category-tag.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .category-section { margin-bottom: 3rem; }
        .category-title { font-size: 1.8rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #00d9ff; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .template-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; transition: 0.3s; cursor: pointer; }
        .template-card:hover { transform: translateY(-5px); border: 1px solid #00d9ff; }
        .template-icon { font-size: 2.5rem; margin-bottom: 1rem; }
        .template-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .template-desc { color: #888; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5; }
        .template-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; color: #666; }
        .btn-use { padding: 0.5rem 1rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .stats-bar { display: flex; gap: 2rem; justify-content: center; margin-bottom: 3rem; padding: 2rem; background: rgba(255,255,255,0.05); border-radius: 12px; }
        .stat { text-align: center; }
        .stat-value { font-size: 2.5rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.9rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Form Library</h1>
        <p>50+ Professional Form Templates • Ready to Use</p>
    </div>

    <div class="stats-bar">
        <div class="stat">
            <div class="stat-value"><?= count($templates) ?></div>
            <div class="stat-label">Form Templates</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?= count($categories) ?></div>
            <div class="stat-label">Categories</div>
        </div>
        <div class="stat">
            <div class="stat-value">100%</div>
            <div class="stat-label">Customizable</div>
        </div>
    </div>

    <div class="search-bar">
        <input type="text" placeholder="🔍 Search templates..." value="<?= htmlspecialchars($search ?? '') ?>" onkeyup="handleSearch(event)">
    </div>

    <div class="categories">
        <a href="/forms/" class="category-tag <?= !$category ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/forms/?category=<?= urlencode($cat) ?>" class="category-tag <?= $category === $cat ? 'active' : '' ?>">
                <?= ucfirst($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($grouped)): ?>
        <div style="text-align: center; padding: 4rem; color: #666;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
            <p>No templates found</p>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $cat => $temps): ?>
            <div class="category-section">
                <h2 class="category-title"><?= ucfirst($cat) ?> Forms (<?= count($temps) ?>)</h2>
                <div class="templates-grid">
                    <?php foreach ($temps as $template): ?>
                        <div class="template-card" onclick="viewTemplate(<?= $template['id'] ?>)">
                            <div class="template-icon">📝</div>
                            <div class="template-name"><?= htmlspecialchars($template['template_name']) ?></div>
                            <div class="template-desc"><?= htmlspecialchars($template['description']) ?></div>
                            <div class="template-meta">
                                <span>🔥 <?= number_format($template['usage_count']) ?> uses</span>
                                <button class="btn-use" onclick="useTemplate(<?= $template['id'] ?>); event.stopPropagation();">Use Template</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
let searchTimeout;
function handleSearch(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (e.key === 'Enter' || e.target.value.length > 2 || e.target.value.length === 0) {
            window.location.href = '/forms/?search=' + encodeURIComponent(e.target.value);
        }
    }, 500);
}

function viewTemplate(id) {
    window.location.href = '/forms/view.php?id=' + id;
}

function useTemplate(id) {
    const name = prompt('Enter a name for your new form:');
    if (name) {
        window.location.href = '/forms/builder.php?template=' + id + '&name=' + encodeURIComponent(name);
    }
}
</script>
</body>
</html>
