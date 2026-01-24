<?php
/**
 * TrueVault VPN - Tutorial Center Index
 * Main tutorial listing page
 * Created: January 24, 2026
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['admin_id'] ?? 1;

// Database connections
$tutorialsDb = new SQLite3(__DIR__ . '/databases/tutorials.db');
$settingsDb = new SQLite3(__DIR__ . '/../databases/settings.db');

// Get theme settings
$themeResult = $settingsDb->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'theme_%'");
$theme = [];
while ($row = $themeResult->fetchArray(SQLITE3_ASSOC)) {
    $theme[$row['setting_key']] = $row['setting_value'];
}

$primaryColor = $theme['theme_primary_color'] ?? '#00d4ff';
$secondaryColor = $theme['theme_secondary_color'] ?? '#7b2cbf';
$bgColor = $theme['theme_bg_color'] ?? '#0a0a0f';
$cardBg = $theme['theme_card_bg'] ?? 'rgba(255,255,255,0.03)';
$textColor = $theme['theme_text_color'] ?? '#ffffff';

// Get tutorials grouped by category with user progress
$tutorials = [];
$result = $tutorialsDb->query("
    SELECT l.*, 
        COALESCE(p.status, 'not_started') as user_status,
        COALESCE(p.current_step, 0) as user_step
    FROM tutorial_lessons l
    LEFT JOIN user_tutorial_progress p ON l.id = p.lesson_id AND p.user_id = $userId
    WHERE l.is_active = 1
    ORDER BY l.lesson_number
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $category = $row['category'];
    if (!isset($tutorials[$category])) {
        $tutorials[$category] = [];
    }
    $tutorials[$category][] = $row;
}

// Get overall progress
$totalLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM tutorial_lessons WHERE is_active = 1");
$completedLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM user_tutorial_progress WHERE user_id = $userId AND status = 'completed'");
$overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

// Category icons
$categoryIcons = [
    'Getting Started' => 'fa-rocket',
    'Database Builder' => 'fa-database',
    'Form Builder' => 'fa-wpforms',
    'Marketing Automation' => 'fa-bullhorn'
];

$tutorialsDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Center - TrueVault Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: <?php echo $textColor; ?>;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: <?php echo $primaryColor; ?>;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.3);
        }
        
        .btn-secondary {
            background: <?php echo $cardBg; ?>;
            color: <?php echo $textColor; ?>;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
        }
        
        /* Progress Overview */
        .progress-overview {
            background: linear-gradient(135deg, <?php echo $cardBg; ?>, rgba(0,212,255,0.05));
            border: 1px solid rgba(0,212,255,0.2);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .progress-circle {
            position: relative;
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }
        
        .progress-circle svg { transform: rotate(-90deg); }
        
        .progress-bg {
            fill: none;
            stroke: rgba(255,255,255,0.1);
            stroke-width: 8;
        }
        
        .progress-fill {
            fill: none;
            stroke: url(#progressGrad);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 251;
            stroke-dashoffset: <?php echo 251 - (251 * $overallProgress / 100); ?>;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.4rem;
            font-weight: 700;
            color: <?php echo $primaryColor; ?>;
        }
        
        .progress-info { flex: 1; }
        
        .progress-info h2 {
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        
        .progress-info p {
            color: #888;
            margin-bottom: 15px;
        }
        
        .progress-bar-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 4px;
        }
        
        .progress-count {
            color: #888;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        /* Search */
        .search-box {
            position: relative;
            margin-bottom: 30px;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: <?php echo $textColor; ?>;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
            box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
        }
        
        .search-box input::placeholder { color: #666; }
        
        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        /* Category Sections */
        .category-section {
            margin-bottom: 40px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .category-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>30, <?php echo $secondaryColor; ?>30);
            border-radius: 12px;
            font-size: 1.2rem;
            color: <?php echo $primaryColor; ?>;
        }
        
        .category-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .category-count {
            color: #888;
            font-size: 0.9rem;
            margin-left: auto;
        }
        
        /* Tutorial Cards Grid */
        .tutorials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .tutorial-card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }
        
        .tutorial-card:hover {
            transform: translateY(-5px);
            border-color: <?php echo $primaryColor; ?>;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .tutorial-card.completed {
            border-color: rgba(0,200,100,0.3);
        }
        
        .tutorial-card.in-progress {
            border-color: rgba(255,180,0,0.3);
        }
        
        .card-status {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
        }
        
        .status-completed {
            background: rgba(0,200,100,0.2);
            color: #00c864;
        }
        
        .status-progress {
            background: rgba(255,180,0,0.2);
            color: #ffb400;
        }
        
        .status-locked {
            background: rgba(255,255,255,0.05);
            color: #666;
        }
        
        .card-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }
        
        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .card-description {
            color: #888;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .card-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .badge-beginner { background: rgba(0,200,100,0.2); color: #00c864; }
        .badge-intermediate { background: rgba(255,180,0,0.2); color: #ffb400; }
        .badge-advanced { background: rgba(255,80,80,0.2); color: #ff5050; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #888;
        }
        
        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .progress-overview { flex-direction: column; text-align: center; }
            .tutorials-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="../" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="page-title">Tutorial Center</h1>
            </div>
            <div class="header-actions">
                <a href="progress.php" class="btn btn-secondary">
                    <i class="fas fa-chart-line"></i>
                    View Progress
                </a>
            </div>
        </div>
        
        <!-- Progress Overview -->
        <div class="progress-overview">
            <div class="progress-circle">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="progressGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="<?php echo $primaryColor; ?>" />
                            <stop offset="100%" stop-color="<?php echo $secondaryColor; ?>" />
                        </linearGradient>
                    </defs>
                    <circle class="progress-bg" cx="50" cy="50" r="40" />
                    <circle class="progress-fill" cx="50" cy="50" r="40" />
                </svg>
                <div class="progress-text"><?php echo $overallProgress; ?>%</div>
            </div>
            <div class="progress-info">
                <h2>Your Learning Journey</h2>
                <p>
                    <?php if ($overallProgress == 0): ?>
                        Start your journey to becoming a TrueVault expert!
                    <?php elseif ($overallProgress < 50): ?>
                        Great start! Keep going to unlock all features.
                    <?php elseif ($overallProgress < 100): ?>
                        You're making excellent progress!
                    <?php else: ?>
                        🎉 Congratulations! You've completed all tutorials!
                    <?php endif; ?>
                </p>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?php echo $overallProgress; ?>%"></div>
                    </div>
                    <span class="progress-count"><?php echo $completedLessons; ?>/<?php echo $totalLessons; ?> lessons</span>
                </div>
            </div>
        </div>
        
        <!-- Search -->
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search tutorials..." onkeyup="filterTutorials(this.value)">
        </div>
        
        <!-- Tutorial Categories -->
        <?php foreach ($tutorials as $category => $lessons): ?>
        <div class="category-section" data-category="<?php echo htmlspecialchars($category); ?>">
            <div class="category-header">
                <div class="category-icon">
                    <i class="fas <?php echo $categoryIcons[$category] ?? 'fa-book'; ?>"></i>
                </div>
                <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                <span class="category-count"><?php echo count($lessons); ?> lessons</span>
            </div>
            
            <div class="tutorials-grid">
                <?php foreach ($lessons as $lesson): ?>
                <a href="engine.php?lesson=<?php echo $lesson['id']; ?>" 
                   class="tutorial-card <?php echo $lesson['user_status']; ?>"
                   data-title="<?php echo htmlspecialchars(strtolower($lesson['title'])); ?>"
                   data-desc="<?php echo htmlspecialchars(strtolower($lesson['description'])); ?>">
                    
                    <?php if ($lesson['user_status'] == 'completed'): ?>
                    <div class="card-status status-completed">
                        <i class="fas fa-check"></i>
                    </div>
                    <?php elseif ($lesson['user_status'] == 'in_progress'): ?>
                    <div class="card-status status-progress">
                        <i class="fas fa-play"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-number"><?php echo $lesson['lesson_number']; ?></div>
                    <h3 class="card-title"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                    <p class="card-description"><?php echo htmlspecialchars($lesson['description']); ?></p>
                    
                    <div class="card-meta">
                        <span><i class="fas fa-clock"></i> <?php echo $lesson['duration_minutes']; ?> min</span>
                        <span class="badge badge-<?php echo $lesson['difficulty']; ?>">
                            <?php echo ucfirst($lesson['difficulty']); ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($tutorials)): ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No Tutorials Available</h3>
            <p>Check back soon for new learning content!</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function filterTutorials(query) {
            query = query.toLowerCase().trim();
            const cards = document.querySelectorAll('.tutorial-card');
            const sections = document.querySelectorAll('.category-section');
            
            cards.forEach(card => {
                const title = card.dataset.title || '';
                const desc = card.dataset.desc || '';
                const matches = title.includes(query) || desc.includes(query);
                card.style.display = matches || !query ? 'block' : 'none';
            });
            
            // Hide empty sections
            sections.forEach(section => {
                const visibleCards = section.querySelectorAll('.tutorial-card[style="display: block"], .tutorial-card:not([style])');
                let hasVisible = false;
                visibleCards.forEach(c => {
                    if (c.style.display !== 'none') hasVisible = true;
                });
                section.style.display = hasVisible ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
