<?php
require_once 'config.php';

$slug = $_GET['slug'] ?? null;
$lessonNum = $_GET['lesson'] ?? 1;

if (!$slug) {
    header('Location: /tutorials/');
    exit;
}

$tutorial = getTutorialBySlug($slug);

if (!$tutorial) {
    header('Location: /tutorials/');
    exit;
}

incrementViews($tutorial['id']);

$lessons = getLessons($tutorial['id']);
$currentLesson = $lessons[$lessonNum - 1] ?? $lessons[0];
$rating = getTutorialRating($tutorial['id']);

// Simple session check (replace with real auth)
$userId = $_SESSION['user_id'] ?? 1; // Demo user
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tutorial['title']) ?> - Tutorials</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0f0f1a, #1a1a2e); color: #fff; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; display: grid; grid-template-columns: 280px 1fr; gap: 2rem; }
        .sidebar { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; height: fit-content; position: sticky; top: 2rem; }
        .sidebar h3 { margin-bottom: 1rem; font-size: 1.1rem; }
        .lesson-list { list-style: none; }
        .lesson-item { padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 0.75rem; }
        .lesson-item:hover { background: rgba(0,217,255,0.2); }
        .lesson-item.active { background: rgba(0,217,255,0.3); border-left: 3px solid #00d9ff; }
        .lesson-number { width: 24px; height: 24px; background: rgba(0,217,255,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; }
        .lesson-item.completed .lesson-number { background: rgba(0,255,136,0.3); color: #00ff88; }
        .main-content { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
        .tutorial-header { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid rgba(255,255,255,0.1); }
        .tutorial-title { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .tutorial-meta { display: flex; gap: 1.5rem; color: #888; font-size: 0.9rem; }
        .lesson-content { line-height: 1.8; }
        .lesson-content h1 { font-size: 2rem; margin: 1.5rem 0 1rem; color: #00d9ff; }
        .lesson-content h2 { font-size: 1.5rem; margin: 1.5rem 0 1rem; color: #00d9ff; }
        .lesson-content h3 { font-size: 1.2rem; margin: 1rem 0 0.5rem; }
        .lesson-content p { margin-bottom: 1rem; }
        .lesson-content ul, .lesson-content ol { margin: 1rem 0 1rem 2rem; }
        .lesson-content li { margin-bottom: 0.5rem; }
        .lesson-content strong { color: #00ff88; }
        .lesson-content code { background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 4px; font-family: monospace; }
        .lesson-content pre { background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; overflow-x: auto; margin: 1rem 0; }
        .video-container { background: rgba(0,0,0,0.5); border-radius: 12px; padding: 3rem; text-align: center; margin: 2rem 0; }
        .navigation { display: flex; justify-content: space-between; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid rgba(255,255,255,0.1); }
        .nav-btn { padding: 1rem 2rem; background: linear-gradient(90deg, #00d9ff, #00ff88); color: #000; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .nav-btn.secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .progress-bar { height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; margin: 1rem 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #00d9ff, #00ff88); border-radius: 3px; transition: width 0.3s; }
        .back-btn { padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; text-decoration: none; display: inline-block; margin-bottom: 2rem; }
        .rating-section { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; margin-top: 2rem; }
        .stars { font-size: 1.5rem; cursor: pointer; }
        .star { color: #444; transition: 0.2s; }
        .star.active { color: #ffb84d; }
    </style>
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <a href="/tutorials/" class="back-btn" style="display: block; text-align: center; margin-bottom: 1.5rem;">← All Tutorials</a>
        
        <h3>📋 Lessons (<?= count($lessons) ?>)</h3>
        <div class="progress-bar">
            <div class="progress-fill" style="width: 0%"></div>
        </div>
        
        <ul class="lesson-list">
            <?php foreach ($lessons as $i => $lesson): ?>
                <li class="lesson-item <?= $lesson['id'] === $currentLesson['id'] ? 'active' : '' ?>" 
                    onclick="window.location.href='/tutorials/view.php?slug=<?= $slug ?>&lesson=<?= $i + 1 ?>'">
                    <span class="lesson-number"><?= $i + 1 ?></span>
                    <span><?= htmlspecialchars($lesson['title']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="main-content">
        <div class="tutorial-header">
            <div class="tutorial-title"><?= htmlspecialchars($tutorial['title']) ?></div>
            <div class="tutorial-meta">
                <span>📂 <?= htmlspecialchars($tutorial['category_name']) ?></span>
                <span>📊 <?= ucfirst($tutorial['difficulty']) ?></span>
                <span>⏱️ <?= $tutorial['duration'] ?> minutes</span>
                <span>👁️ <?= number_format($tutorial['views']) ?> views</span>
                <?php if ($rating['total_ratings'] > 0): ?>
                    <span>⭐ <?= number_format($rating['avg_rating'], 1) ?> (<?= $rating['total_ratings'] ?> ratings)</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($currentLesson['video_url']): ?>
            <div class="video-container">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎥</div>
                <p style="color: #888;">Video: <?= htmlspecialchars($currentLesson['video_url']) ?></p>
            </div>
        <?php endif; ?>

        <div class="lesson-content">
            <?php
            // Convert markdown-style content to HTML
            $content = $currentLesson['content'];
            $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
            $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
            $content = preg_replace('/^### (.+)$/m', '<h3>$3</h3>', $content);
            $content = preg_replace('/\*\*(.+?)\*\*/m', '<strong>$1</strong>', $content);
            $content = nl2br($content);
            echo $content;
            ?>
        </div>

        <div class="navigation">
            <?php if ($lessonNum > 1): ?>
                <a href="/tutorials/view.php?slug=<?= $slug ?>&lesson=<?= $lessonNum - 1 ?>" class="nav-btn secondary">
                    ← Previous Lesson
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            
            <?php if ($lessonNum < count($lessons)): ?>
                <a href="/tutorials/view.php?slug=<?= $slug ?>&lesson=<?= $lessonNum + 1 ?>" class="nav-btn">
                    Next Lesson →
                </a>
            <?php else: ?>
                <button class="nav-btn" onclick="completeTutorial()">✓ Complete Tutorial</button>
            <?php endif; ?>
        </div>

        <div class="rating-section">
            <h3 style="margin-bottom: 1rem;">Rate This Tutorial</h3>
            <div class="stars" id="stars">
                <span class="star" data-rating="1">★</span>
                <span class="star" data-rating="2">★</span>
                <span class="star" data-rating="3">★</span>
                <span class="star" data-rating="4">★</span>
                <span class="star" data-rating="5">★</span>
            </div>
        </div>
    </main>
</div>

<script>
const stars = document.querySelectorAll('.star');
let selectedRating = 0;

stars.forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        updateStars();
        submitRating();
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        stars.forEach((s, i) => {
            s.classList.toggle('active', i < rating);
        });
    });
});

document.querySelector('.stars').addEventListener('mouseleave', updateStars);

function updateStars() {
    stars.forEach((s, i) => {
        s.classList.toggle('active', i < selectedRating);
    });
}

function submitRating() {
    fetch('/tutorials/api.php?action=rate', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `tutorial_id=<?= $tutorial['id'] ?>&rating=${selectedRating}&user_id=<?= $userId ?>`
    }).then(() => {
        alert('Thank you for rating!');
    });
}

function completeTutorial() {
    fetch('/tutorials/api.php?action=complete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tutorial_id=<?= $tutorial['id'] ?>&user_id=<?= $userId ?>'
    }).then(() => {
        alert('Congratulations! Tutorial completed! 🎉');
        window.location.href = '/tutorials/';
    });
}
</script>
</body>
</html>
