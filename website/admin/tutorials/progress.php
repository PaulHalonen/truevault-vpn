<?php
/**
 * TrueVault VPN - Tutorial Progress Dashboard
 * Task 16.5: Progress tracking with achievements
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

// Get total lessons count
$totalLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM tutorial_lessons WHERE is_active = 1");

// Get user's completed lessons
$completedLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM user_tutorial_progress WHERE user_id = $userId AND status = 'completed'");

// Get user's in-progress lessons
$inProgressLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM user_tutorial_progress WHERE user_id = $userId AND status = 'in_progress'");

// Calculate overall progress
$overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

// Get progress by category
$categoryProgress = [];
$categories = ['Getting Started', 'Database Builder', 'Form Builder', 'Marketing Automation'];
foreach ($categories as $category) {
    $total = $tutorialsDb->querySingle("SELECT COUNT(*) FROM tutorial_lessons WHERE category = '$category' AND is_active = 1");
    $completed = $tutorialsDb->querySingle("
        SELECT COUNT(*) FROM user_tutorial_progress p 
        JOIN tutorial_lessons l ON p.lesson_id = l.id 
        WHERE p.user_id = $userId AND p.status = 'completed' AND l.category = '$category'
    ");
    $categoryProgress[$category] = [
        'total' => $total,
        'completed' => $completed,
        'percent' => $total > 0 ? round(($completed / $total) * 100) : 0
    ];
}

// Get next recommended lesson
$nextLesson = null;
$result = $tutorialsDb->query("
    SELECT l.* FROM tutorial_lessons l
    LEFT JOIN user_tutorial_progress p ON l.id = p.lesson_id AND p.user_id = $userId
    WHERE l.is_active = 1 AND (p.status IS NULL OR p.status = 'in_progress')
    ORDER BY l.lesson_number ASC
    LIMIT 1
");
$nextLesson = $result->fetchArray(SQLITE3_ASSOC);

// Get recently completed lessons
$recentCompleted = [];
$result = $tutorialsDb->query("
    SELECT l.*, p.completed_at, p.time_spent_minutes 
    FROM user_tutorial_progress p
    JOIN tutorial_lessons l ON p.lesson_id = l.id
    WHERE p.user_id = $userId AND p.status = 'completed'
    ORDER BY p.completed_at DESC
    LIMIT 5
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $recentCompleted[] = $row;
}

// Calculate total time spent
$totalTimeSpent = $tutorialsDb->querySingle("SELECT SUM(time_spent_minutes) FROM user_tutorial_progress WHERE user_id = $userId") ?? 0;

// Determine achievements
$achievements = [];

// Achievement: First Steps - Complete 1 lesson
if ($completedLessons >= 1) {
    $achievements[] = ['icon' => 'fa-baby', 'name' => 'First Steps', 'desc' => 'Completed your first lesson'];
}

// Achievement: Quick Learner - Complete 5 lessons
if ($completedLessons >= 5) {
    $achievements[] = ['icon' => 'fa-bolt', 'name' => 'Quick Learner', 'desc' => 'Completed 5 lessons'];
}

// Achievement: Database Pro - Complete all Database Builder lessons
if ($categoryProgress['Database Builder']['percent'] == 100) {
    $achievements[] = ['icon' => 'fa-database', 'name' => 'Database Pro', 'desc' => 'Mastered the Database Builder'];
}

// Achievement: Form Master - Complete all Form Builder lessons
if ($categoryProgress['Form Builder']['percent'] == 100) {
    $achievements[] = ['icon' => 'fa-wpforms', 'name' => 'Form Master', 'desc' => 'Mastered the Form Builder'];
}

// Achievement: Marketing Guru - Complete all Marketing lessons
if ($categoryProgress['Marketing Automation']['percent'] == 100) {
    $achievements[] = ['icon' => 'fa-bullhorn', 'name' => 'Marketing Guru', 'desc' => 'Mastered Marketing Automation'];
}

// Achievement: Tutorial Champion - Complete all lessons
if ($overallProgress == 100) {
    $achievements[] = ['icon' => 'fa-trophy', 'name' => 'Tutorial Champion', 'desc' => 'Completed ALL tutorials!'];
}

// Achievement: Dedicated Learner - Spend 1+ hour learning
if ($totalTimeSpent >= 60) {
    $achievements[] = ['icon' => 'fa-clock', 'name' => 'Dedicated Learner', 'desc' => 'Spent 1+ hour learning'];
}

$tutorialsDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Progress - TrueVault Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .back-link {
            color: <?php echo $primaryColor; ?>;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link:hover { opacity: 0.8; }
        
        /* Overall Progress Card */
        .progress-hero {
            background: linear-gradient(135deg, <?php echo $cardBg; ?>, rgba(0,212,255,0.05));
            border: 1px solid rgba(0,212,255,0.2);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .progress-circle {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 20px;
        }
        
        .progress-circle svg {
            transform: rotate(-90deg);
        }
        
        .progress-circle-bg {
            fill: none;
            stroke: rgba(255,255,255,0.1);
            stroke-width: 12;
        }
        
        .progress-circle-fill {
            fill: none;
            stroke: url(#progressGradient);
            stroke-width: 12;
            stroke-linecap: round;
            stroke-dasharray: 502;
            stroke-dashoffset: <?php echo 502 - (502 * $overallProgress / 100); ?>;
            transition: stroke-dashoffset 1s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .progress-percent {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .progress-label { color: #888; font-size: 0.9rem; }
        
        .progress-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        
        .stat-item { text-align: center; }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: <?php echo $primaryColor; ?>;
        }
        .stat-label { color: #888; font-size: 0.85rem; }
        
        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Cards */
        .card {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: <?php echo $primaryColor; ?>;
        }
        
        /* Category Progress */
        .category-item {
            margin-bottom: 20px;
        }
        
        .category-item:last-child { margin-bottom: 0; }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .category-name { font-weight: 500; }
        .category-count { color: #888; font-size: 0.85rem; }
        
        .category-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .category-fill {
            height: 100%;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* Next Lesson */
        .next-lesson {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: rgba(0,212,255,0.05);
            border: 1px solid rgba(0,212,255,0.2);
            border-radius: 12px;
            margin-top: 15px;
        }
        
        .next-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 12px;
            font-size: 1.5rem;
        }
        
        .next-info { flex: 1; }
        .next-label { color: #888; font-size: 0.85rem; margin-bottom: 5px; }
        .next-title { font-weight: 600; font-size: 1.1rem; }
        .next-meta { color: #888; font-size: 0.85rem; margin-top: 5px; }
        
        .btn-start {
            padding: 12px 24px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        
        .btn-start:hover {
            transform: translateY(-2px);
        }
        
        /* Achievements */
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .achievement {
            text-align: center;
            padding: 20px 15px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            transition: all 0.2s;
        }
        
        .achievement.earned {
            border-color: rgba(0,212,255,0.3);
            background: rgba(0,212,255,0.05);
        }
        
        .achievement.locked {
            opacity: 0.4;
        }
        
        .achievement-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>40, <?php echo $secondaryColor; ?>40);
            border-radius: 50%;
            font-size: 1.3rem;
        }
        
        .achievement.earned .achievement-icon {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
        }
        
        .achievement-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .achievement-desc {
            font-size: 0.75rem;
            color: #888;
        }
        
        /* Recent Activity */
        .activity-list { list-style: none; }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .activity-item:last-child { border-bottom: none; }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,200,100,0.1);
            color: #00c864;
            border-radius: 10px;
        }
        
        .activity-info { flex: 1; }
        .activity-title { font-weight: 500; }
        .activity-meta { color: #888; font-size: 0.85rem; }
        
        /* Certificate Button */
        .certificate-section {
            text-align: center;
            padding: 30px;
            margin-top: 20px;
        }
        
        .btn-certificate {
            padding: 15px 40px;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-certificate:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255,215,0,0.3);
        }
        
        .btn-certificate:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .progress-stats { flex-direction: column; gap: 20px; }
            .grid-2 { grid-template-columns: 1fr; }
            .next-lesson { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">Your Learning Progress</h1>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Tutorials
            </a>
        </div>
        
        <!-- Overall Progress -->
        <div class="progress-hero">
            <div class="progress-circle">
                <svg width="180" height="180" viewBox="0 0 180 180">
                    <defs>
                        <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="<?php echo $primaryColor; ?>" />
                            <stop offset="100%" stop-color="<?php echo $secondaryColor; ?>" />
                        </linearGradient>
                    </defs>
                    <circle class="progress-circle-bg" cx="90" cy="90" r="80" />
                    <circle class="progress-circle-fill" cx="90" cy="90" r="80" />
                </svg>
                <div class="progress-text">
                    <div class="progress-percent"><?php echo $overallProgress; ?>%</div>
                    <div class="progress-label">Complete</div>
                </div>
            </div>
            
            <div class="progress-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $completedLessons; ?></div>
                    <div class="stat-label">Lessons Completed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $inProgressLessons; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalTimeSpent; ?></div>
                    <div class="stat-label">Minutes Spent</div>
                </div>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Category Progress -->
            <div class="card">
                <h2 class="card-title"><i class="fas fa-chart-pie"></i> Progress by Category</h2>
                
                <?php foreach ($categoryProgress as $cat => $data): ?>
                <div class="category-item">
                    <div class="category-header">
                        <span class="category-name"><?php echo htmlspecialchars($cat); ?></span>
                        <span class="category-count"><?php echo $data['completed']; ?>/<?php echo $data['total']; ?> (<?php echo $data['percent']; ?>%)</span>
                    </div>
                    <div class="category-bar">
                        <div class="category-fill" style="width: <?php echo $data['percent']; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Next Recommended Lesson -->
                <?php if ($nextLesson): ?>
                <div class="next-lesson">
                    <div class="next-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="next-info">
                        <div class="next-label">Up Next</div>
                        <div class="next-title"><?php echo htmlspecialchars($nextLesson['title']); ?></div>
                        <div class="next-meta">
                            <?php echo $nextLesson['duration_minutes']; ?> min · <?php echo ucfirst($nextLesson['difficulty']); ?>
                        </div>
                    </div>
                    <a href="engine.php?lesson=<?php echo $nextLesson['id']; ?>" class="btn-start">
                        Continue <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Achievements -->
            <div class="card">
                <h2 class="card-title"><i class="fas fa-award"></i> Achievements</h2>
                
                <div class="achievements-grid">
                    <?php
                    // All possible achievements
                    $allAchievements = [
                        ['icon' => 'fa-baby', 'name' => 'First Steps', 'desc' => 'Complete 1 lesson'],
                        ['icon' => 'fa-bolt', 'name' => 'Quick Learner', 'desc' => 'Complete 5 lessons'],
                        ['icon' => 'fa-database', 'name' => 'Database Pro', 'desc' => 'Master Database Builder'],
                        ['icon' => 'fa-wpforms', 'name' => 'Form Master', 'desc' => 'Master Form Builder'],
                        ['icon' => 'fa-bullhorn', 'name' => 'Marketing Guru', 'desc' => 'Master Marketing'],
                        ['icon' => 'fa-trophy', 'name' => 'Tutorial Champion', 'desc' => 'Complete ALL tutorials'],
                        ['icon' => 'fa-clock', 'name' => 'Dedicated Learner', 'desc' => 'Spend 1+ hour learning'],
                    ];
                    
                    $earnedNames = array_column($achievements, 'name');
                    
                    foreach ($allAchievements as $ach):
                        $earned = in_array($ach['name'], $earnedNames);
                    ?>
                    <div class="achievement <?php echo $earned ? 'earned' : 'locked'; ?>">
                        <div class="achievement-icon">
                            <i class="fas <?php echo $ach['icon']; ?>"></i>
                        </div>
                        <div class="achievement-name"><?php echo $ach['name']; ?></div>
                        <div class="achievement-desc"><?php echo $ach['desc']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-history"></i> Recent Completions</h2>
            
            <?php if (empty($recentCompleted)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>No lessons completed yet. Start learning!</p>
            </div>
            <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($recentCompleted as $lesson): ?>
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-info">
                        <div class="activity-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                        <div class="activity-meta">
                            <?php echo $lesson['category']; ?> · 
                            Completed <?php echo date('M j, Y', strtotime($lesson['completed_at'])); ?>
                            <?php if ($lesson['time_spent_minutes']): ?>
                            · <?php echo $lesson['time_spent_minutes']; ?> min
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        
        <!-- Certificate Section -->
        <?php if ($overallProgress == 100): ?>
        <div class="certificate-section">
            <button class="btn-certificate" onclick="generateCertificate()">
                <i class="fas fa-certificate"></i>
                Download Completion Certificate
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function generateCertificate() {
            // In a real implementation, this would generate a PDF certificate
            alert('🎉 Congratulations!\n\nYour completion certificate is being generated.\nThis feature will download a personalized PDF certificate.');
            
            // Would call: window.location.href = 'generate-certificate.php';
        }
    </script>
</body>
</html>
