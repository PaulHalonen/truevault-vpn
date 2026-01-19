<?php
require_once 'config.php';

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$articleId = $_GET['id'] ?? null;

$db = getSupportDB();

// View single article
if ($articleId) {
    $stmt = $db->prepare("SELECT * FROM knowledge_base WHERE id = ? AND is_published = 1");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    
    if ($article) {
        // Increment view count
        $stmt = $db->prepare("UPDATE knowledge_base SET views = views + 1 WHERE id = ?");
        $stmt->execute([$articleId]);
    }
} else {
    $articles = getKBArticles($category, $search);
}

// Get categories
$stmt = $db->query("SELECT DISTINCT category FROM knowledge_base WHERE is_published = 1");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Base - TrueVault VPN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 2.5rem; background: linear-gradient(90deg, #00d9ff, #00ff88); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .search-box { max-width: 600px; margin: 2rem auto; }
        .search-box input { width: 100%; padding: 1rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: #fff; font-size: 1rem; }
        .categories { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3rem; }
        .category-tag { padding: 0.5rem 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; transition: 0.3s; text-decoration: none; color: #fff; }
        .category-tag:hover, .category-tag.active { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
        .articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .article-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: 0.3s; text-decoration: none; color: inherit; display: block; }
        .article-card:hover { transform: translateY(-3px); border: 1px solid #00d9ff; }
        .article-category { display: inline-block; padding: 0.25rem 0.75rem; background: rgba(0,217,255,0.2); color: #00d9ff; border-radius: 6px; font-size: 0.75rem; margin-bottom: 1rem; }
        .article-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .article-preview { color: #888; font-size: 0.9rem; line-height: 1.5; }
        .article-meta { display: flex; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; color: #666; }
        .article-content { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .article-content h2 { color: #00d9ff; margin: 1.5rem 0 1rem; }
        .article-content ul, .article-content ol { margin: 1rem 0 1rem 2rem; }
        .article-content li { margin-bottom: 0.5rem; }
        .back-btn { display: inline-block; padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; margin-bottom: 2rem; }
        .helpful-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; margin-top: 2rem; text-align: center; }
        .helpful-btns { display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; }
        .helpful-btns button { padding: 0.75rem 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .helpful-btns button:hover { background: rgba(0,217,255,0.2); border-color: #00d9ff; }
    </style>
</head>
<body>
<div class="container">
    <?php if ($articleId && $article): ?>
        <!-- Single Article View -->
        <a href="/support/kb.php" class="back-btn">← Back to Knowledge Base</a>
        
        <div class="article-content">
            <span class="article-category"><?= ucwords(str_replace('_', ' ', $article['category'])) ?></span>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?= htmlspecialchars($article['title']) ?></h1>
            <div class="article-meta">
                <span>👁️ <?= number_format($article['views']) ?> views</span>
                <span>👍 <?= $article['helpful_count'] ?> helpful</span>
            </div>
            <div style="margin-top: 2rem; line-height: 1.8;">
                <?= $article['content'] ?>
            </div>
        </div>
        
        <div class="helpful-section">
            <h3 style="margin-bottom: 1rem;">Was this article helpful?</h3>
            <div class="helpful-btns">
                <button onclick="rateArticle(<?= $article['id'] ?>, true)">👍 Yes, it helped</button>
                <button onclick="rateArticle(<?= $article['id'] ?>, false)">👎 No, still need help</button>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Article List View -->
        <div class="header">
            <h1>💡 Knowledge Base</h1>
            <p style="color: #888; font-size: 1.1rem;">Find answers to common questions</p>
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="🔍 Search articles..." value="<?= htmlspecialchars($search ?? '') ?>" onkeyup="handleSearch(event)">
        </div>
        
        <div class="categories">
            <a href="/support/kb.php" class="category-tag <?= !$category ? 'active' : '' ?>">All Articles</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/support/kb.php?category=<?= urlencode($cat) ?>" class="category-tag <?= $category === $cat ? 'active' : '' ?>">
                    <?= ucwords(str_replace('_', ' ', $cat)) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($articles)): ?>
            <div style="text-align: center; padding: 4rem; color: #666;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                <p>No articles found</p>
            </div>
        <?php else: ?>
            <div class="articles-grid">
                <?php foreach ($articles as $art): ?>
                    <a href="/support/kb.php?id=<?= $art['id'] ?>" class="article-card">
                        <span class="article-category"><?= ucwords(str_replace('_', ' ', $art['category'])) ?></span>
                        <div class="article-title"><?= htmlspecialchars($art['title']) ?></div>
                        <div class="article-preview">
                            <?= htmlspecialchars(substr(strip_tags($art['content']), 0, 120)) ?>...
                        </div>
                        <div class="article-meta">
                            <span>👁️ <?= number_format($art['views']) ?></span>
                            <span>👍 <?= $art['helpful_count'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
let searchTimeout;
function handleSearch(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (e.key === 'Enter' || e.target.value.length > 2 || e.target.value.length === 0) {
            window.location.href = '/support/kb.php?search=' + encodeURIComponent(e.target.value);
        }
    }, 500);
}

function rateArticle(articleId, helpful) {
    fetch('/support/api.php?action=rate_article', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'article_id=' + articleId + '&helpful=' + (helpful ? 1 : 0)
    }).then(() => {
        alert(helpful ? 'Thank you for your feedback!' : 'We\'re sorry this didn\'t help. Please submit a support ticket.');
        if (!helpful) {
            window.location.href = '/support/submit.php';
        }
    });
}
</script>
</body>
</html>
