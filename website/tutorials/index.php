<?php
require_once 'config.php';

$categorySlug = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$categories = getCategories();
$categoryId = null;

if ($categorySlug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $categorySlug) {
            $categoryId = $cat['id'];
            break;
        }
    }
}

if ($search) {
    $tutorials = searchTutorials($search);
} else {
    $tutorials = getTutorials($categoryId);
    $featured = getTutorials(null, true);
}

$stats = getTutorialStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorials - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 3rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .header p { color: #888; font-size: 1.2rem; }
        .stats-bar { display: flex; gap: 2rem; justify-content: center; margin-bottom: 3rem; padding: 2rem; background: rgba(255,255,255,0.05); border-radius: 12px; }
        .stat { text-align: center; }
        .stat-value { font-size: 2.5rem; font-weight: 700; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #888; font-size: 0.9rem; margin-top: 0.5rem; }
        .search-bar { max-width: 600px; margin: 2rem auto; }
        .search-bar input { width: 100%; padding: 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: #fff; font-size: 1rem; }
        .categories { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }
        .category-tag { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; text-decoration: none; color: #fff; transition: 0.3s; display: flex; align-items: center; gap: 0.5rem; }
        .category-tag:hover, .category-tag.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .featured-section { margin-bottom: 3rem; }
        .section-title { font-size: 2rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #00d9ff; }
        .tutorials-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .tutorial-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: 0.3s; text-decoration: none; color: inherit; display: block; }
        .tutorial-card:hover { transform: translateY(-5px); border: 1px solid #00d9ff; }
        .tutorial-thumbnail { width: 100%; height: 180px; background: rgba(0,0,0,0.3); border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .tutorial-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; }
        .tutorial-desc { color: #888; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5; }
        .tutorial-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; }
        .difficulty-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.75rem; }
        .difficulty-beginner { background: rgba(0,255,136,0.2); color: #00ff88; }
        .difficulty-intermediate { background: rgba(255,200,100,0.2); color: #ffb84d; }
        .difficulty-advanced { background: rgba(255,100,100,0.2); color: #ff6464; }
        .empty-state { text-align: center; padding: 4rem; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📚 Tutorial Library</h1>
        <p>Learn everything about TrueVault VPN</p>
    </div>

    <div class="stats-bar">
        <div class="stat">
            <div class="stat-value"><?= $stats['total_tutorials'] ?></div>
            <div class="stat-label">Tutorials</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?= $stats['total_categories'] ?></div>
            <div class="stat-label">Categories</div>
        </div>
        <div class="stat">
            <div class="stat-value"><?= number_format($stats['total_views']) ?></div>
            <div class="stat-label">Total Views</div>
        </div>
    </div>

    <div class="search-bar">
        <input type="text" placeholder="🔍 Search tutorials..." value="<?= htmlspecialchars($search ?? '') ?>" onkeyup="handleSearch(event)">
    </div>

    <div class="categories">
        <a href="/tutorials/" class="category-tag <?= !$categorySlug ? 'active' : '' ?>">All Tutorials</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/tutorials/?category=<?= $cat['slug'] ?>" class="category-tag <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
                <span><?= $cat['icon'] ?></span>
                <span><?= htmlspecialchars($cat['category_name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$search && !empty($featured)): ?>
        <div class="featured-section">
            <h2 class="section-title">⭐ Featured Tutorials</h2>
            <div class="tutorials-grid">
                <?php foreach ($featured as $tutorial): ?>
                    <a href="/tutorials/view.php?slug=<?= $tutorial['slug'] ?>" class="tutorial-card">
                        <div class="tutorial-thumbnail"><?= $tutorial['category_icon'] ?></div>
                        <div class="tutorial-title"><?= htmlspecialchars($tutorial['title']) ?></div>
                        <div class="tutorial-desc"><?= htmlspecialchars($tutorial['description']) ?></div>
                        <div class="tutorial-meta">
                            <span class="difficulty-badge difficulty-<?= $tutorial['difficulty'] ?>">
                                <?= ucfirst($tutorial['difficulty']) ?>
                            </span>
                            <span>⏱️ <?= $tutorial['duration'] ?> min</span>
                            <span>👁️ <?= number_format($tutorial['views']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top: 3rem;">
        <h2 class="section-title">
            <?php if ($search): ?>
                Search Results for "<?= htmlspecialchars($search) ?>"
            <?php elseif ($categorySlug): ?>
                <?php 
                foreach ($categories as $cat) {
                    if ($cat['slug'] === $categorySlug) {
                        echo $cat['icon'] . ' ' . htmlspecialchars($cat['category_name']);
                        break;
                    }
                }
                ?>
            <?php else: ?>
                All Tutorials
            <?php endif; ?>
        </h2>
        
        <?php if (empty($tutorials)): ?>
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                <p>No tutorials found</p>
            </div>
        <?php else: ?>
            <div class="tutorials-grid">
                <?php foreach ($tutorials as $tutorial): ?>
                    <a href="/tutorials/view.php?slug=<?= $tutorial['slug'] ?>" class="tutorial-card">
                        <div class="tutorial-thumbnail"><?= $tutorial['category_icon'] ?></div>
                        <div class="tutorial-title"><?= htmlspecialchars($tutorial['title']) ?></div>
                        <div class="tutorial-desc"><?= htmlspecialchars($tutorial['description']) ?></div>
                        <div class="tutorial-meta">
                            <span class="difficulty-badge difficulty-<?= $tutorial['difficulty'] ?>">
                                <?= ucfirst($tutorial['difficulty']) ?>
                            </span>
                            <span>⏱️ <?= $tutorial['duration'] ?> min</span>
                            <span>👁️ <?= number_format($tutorial['views']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let searchTimeout;
function handleSearch(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (e.key === 'Enter' || e.target.value.length > 2 || e.target.value.length === 0) {
            window.location.href = '/tutorials/?search=' + encodeURIComponent(e.target.value);
        }
    }, 500);
}
</script>
</body>
</html>
