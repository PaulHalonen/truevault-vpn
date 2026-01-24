<?php
/**
 * TrueVault VPN - Interactive Tutorial Engine
 * Task 16.3: Tutorial player with step progression
 * Created: January 24, 2026
 */

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['admin_id'] ?? 1;
$lessonId = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 1;

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

// Get lesson details
$stmt = $tutorialsDb->prepare("SELECT * FROM tutorial_lessons WHERE id = :id AND is_active = 1");
$stmt->bindValue(':id', $lessonId, SQLITE3_INTEGER);
$result = $stmt->execute();
$lesson = $result->fetchArray(SQLITE3_ASSOC);

if (!$lesson) {
    header('Location: index.php?error=lesson_not_found');
    exit;
}

// Get user progress for this lesson
$stmt = $tutorialsDb->prepare("SELECT * FROM user_tutorial_progress WHERE user_id = :user_id AND lesson_id = :lesson_id");
$stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
$stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
$result = $stmt->execute();
$progress = $result->fetchArray(SQLITE3_ASSOC);

// Create progress record if doesn't exist
if (!$progress) {
    $stmt = $tutorialsDb->prepare("INSERT INTO user_tutorial_progress (user_id, lesson_id, status, current_step, started_at) VALUES (:user_id, :lesson_id, 'in_progress', 1, datetime('now'))");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
    $stmt->execute();
    $currentStep = 1;
    $status = 'in_progress';
} else {
    $currentStep = $progress['current_step'] ?? 1;
    $status = $progress['status'];
}

// Parse lesson content
$lessonContent = json_decode($lesson['lesson_content'], true) ?? [];
$totalSteps = count($lessonContent);

// Get next and previous lessons
$stmt = $tutorialsDb->prepare("SELECT id, title FROM tutorial_lessons WHERE lesson_number = :num AND is_active = 1");
$stmt->bindValue(':num', $lesson['lesson_number'] - 1, SQLITE3_INTEGER);
$result = $stmt->execute();
$prevLesson = $result->fetchArray(SQLITE3_ASSOC);

$stmt = $tutorialsDb->prepare("SELECT id, title FROM tutorial_lessons WHERE lesson_number = :num AND is_active = 1");
$stmt->bindValue(':num', $lesson['lesson_number'] + 1, SQLITE3_INTEGER);
$result = $stmt->execute();
$nextLesson = $result->fetchArray(SQLITE3_ASSOC);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'update_progress':
            $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
            $stmt = $tutorialsDb->prepare("UPDATE user_tutorial_progress SET current_step = :step WHERE user_id = :user_id AND lesson_id = :lesson_id");
            $stmt->bindValue(':step', $step, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true, 'step' => $step]);
            exit;
            
        case 'complete_lesson':
            $stmt = $tutorialsDb->prepare("UPDATE user_tutorial_progress SET status = 'completed', completed_at = datetime('now') WHERE user_id = :user_id AND lesson_id = :lesson_id");
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true, 'next_lesson' => $nextLesson['id'] ?? null]);
            exit;
            
        case 'skip_lesson':
            $stmt = $tutorialsDb->prepare("UPDATE user_tutorial_progress SET status = 'skipped' WHERE user_id = :user_id AND lesson_id = :lesson_id");
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true, 'next_lesson' => $nextLesson['id'] ?? null]);
            exit;
            
        case 'get_step':
            $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
            if ($step > 0 && $step <= $totalSteps) {
                echo json_encode(['success' => true, 'step' => $lessonContent[$step - 1], 'total' => $totalSteps]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid step']);
            }
            exit;
    }
}

$tutorialsDb->close();
$settingsDb->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lesson['title']); ?> - TrueVault Tutorials</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            min-height: 100vh;
        }
        
        .tutorial-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .tutorial-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: <?php echo $cardBg; ?>;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: <?php echo $primaryColor; ?>;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-btn:hover { opacity: 0.8; }
        
        .lesson-meta {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .meta-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-beginner { background: rgba(0,200,100,0.2); color: #00c864; }
        .badge-intermediate { background: rgba(255,180,0,0.2); color: #ffb400; }
        .badge-advanced { background: rgba(255,80,80,0.2); color: #ff5050; }
        
        .meta-time {
            color: #888;
            font-size: 0.9rem;
        }
        
        /* Progress Bar */
        .progress-container {
            background: <?php echo $cardBg; ?>;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .progress-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .progress-steps {
            color: #888;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* Main Content Area */
        .tutorial-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        /* Video Section */
        .video-section {
            background: <?php echo $cardBg; ?>;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            background: #000;
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .no-video {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-video i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Step Content */
        .step-card {
            background: <?php echo $cardBg; ?>;
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
            min-height: 300px;
        }
        
        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .step-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            background: linear-gradient(90deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .step-text {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .step-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            font-size: 0.9rem;
            color: #888;
        }
        
        .step-action i {
            color: <?php echo $primaryColor; ?>;
        }
        
        .step-target {
            font-family: monospace;
            background: rgba(0,0,0,0.3);
            padding: 2px 8px;
            border-radius: 4px;
            color: <?php echo $primaryColor; ?>;
        }
        
        /* Navigation */
        .tutorial-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-prev {
            background: rgba(255,255,255,0.1);
            color: <?php echo $textColor; ?>;
        }
        
        .btn-prev:hover:not(:disabled) {
            background: rgba(255,255,255,0.15);
        }
        
        .btn-next {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .btn-next:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.3);
        }
        
        .nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .btn-skip {
            background: transparent;
            color: #666;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-skip:hover {
            color: #888;
            border-color: rgba(255,255,255,0.2);
        }
        
        /* Completion Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show { display: flex; }
        
        .modal-content {
            background: <?php echo $cardBg; ?>;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            animation: modalIn 0.3s ease;
        }
        
        @keyframes modalIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .modal-icon.success { color: #00c864; }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .modal-text {
            color: #888;
            margin-bottom: 25px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .modal-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            color: #fff;
        }
        
        .modal-btn-secondary {
            background: rgba(255,255,255,0.1);
            color: <?php echo $textColor; ?>;
        }
        
        /* Animated Pointer */
        .pointer-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 999;
        }
        
        .pointer {
            position: absolute;
            width: 60px;
            height: 60px;
            animation: pointerPulse 1.5s ease-in-out infinite;
        }
        
        .pointer svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 10px <?php echo $primaryColor; ?>);
        }
        
        @keyframes pointerPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .tutorial-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .tutorial-nav {
                flex-direction: column;
            }
            
            .nav-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="tutorial-container">
        <!-- Header -->
        <div class="tutorial-header">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Tutorials
            </a>
            <div class="lesson-meta">
                <span class="meta-badge badge-<?php echo $lesson['difficulty']; ?>">
                    <?php echo ucfirst($lesson['difficulty']); ?>
                </span>
                <span class="meta-time">
                    <i class="fas fa-clock"></i>
                    <?php echo $lesson['duration_minutes']; ?> min
                </span>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-header">
                <span class="progress-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                <span class="progress-steps">Step <span id="currentStepNum"><?php echo $currentStep; ?></span> of <?php echo $totalSteps; ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: <?php echo ($currentStep / $totalSteps) * 100; ?>%"></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="tutorial-content">
            <?php if ($lesson['video_url']): ?>
            <!-- Video Section -->
            <div class="video-section">
                <div class="video-wrapper">
                    <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Step Content -->
            <div class="step-card" id="stepCard">
                <div class="step-number" id="stepNumber"><?php echo $currentStep; ?></div>
                <h2 class="step-title" id="stepTitle">Loading...</h2>
                <p class="step-text" id="stepText">Please wait...</p>
                <div class="step-action" id="stepAction" style="display: none;">
                    <i class="fas fa-hand-pointer"></i>
                    <span>Action: <span id="actionType"></span></span>
                    <span class="step-target" id="actionTarget" style="display: none;"></span>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="tutorial-nav">
            <button class="nav-btn btn-prev" id="btnPrev" <?php echo $currentStep <= 1 ? 'disabled' : ''; ?>>
                <i class="fas fa-chevron-left"></i>
                Previous
            </button>
            <button class="nav-btn btn-skip" id="btnSkip">
                Skip Lesson
            </button>
            <button class="nav-btn btn-next" id="btnNext">
                <?php echo $currentStep >= $totalSteps ? 'Complete' : 'Next'; ?>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <!-- Completion Modal -->
    <div class="modal-overlay" id="completionModal">
        <div class="modal-content">
            <div class="modal-icon success">
                <i class="fas fa-trophy"></i>
            </div>
            <h3 class="modal-title">Lesson Complete!</h3>
            <p class="modal-text">Great job! You've completed "<?php echo htmlspecialchars($lesson['title']); ?>"</p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" onclick="location.href='index.php'">
                    Back to Tutorials
                </button>
                <?php if ($nextLesson): ?>
                <button class="modal-btn modal-btn-primary" onclick="location.href='engine.php?lesson=<?php echo $nextLesson['id']; ?>'">
                    Next Lesson <i class="fas fa-arrow-right"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Animated Pointer -->
    <div class="pointer-overlay" id="pointerOverlay">
        <div class="pointer" id="pointer">
            <svg viewBox="0 0 24 24" fill="<?php echo $primaryColor; ?>">
                <path d="M13.64 21.97C13.14 22.21 12.54 22 12.31 21.5L10.13 16.76L7.62 18.78C7.45 18.92 7.24 19 7.02 19C6.55 19 6.15 18.61 6.15 18.14V5C6.15 4.45 6.6 4 7.15 4C7.37 4 7.59 4.08 7.77 4.21L19.57 13.15C19.97 13.45 20.07 14 19.77 14.4C19.63 14.59 19.43 14.72 19.2 14.77L15.39 15.5L17.57 20.24C17.81 20.74 17.6 21.33 17.1 21.57L13.64 21.97Z"/>
            </svg>
        </div>
    </div>
    
    <script>
        // Tutorial State
        const lessonId = <?php echo $lessonId; ?>;
        const totalSteps = <?php echo $totalSteps; ?>;
        let currentStep = <?php echo $currentStep; ?>;
        const lessonContent = <?php echo json_encode($lessonContent); ?>;
        const nextLessonId = <?php echo $nextLesson ? $nextLesson['id'] : 'null'; ?>;
        
        // DOM Elements
        const stepNumber = document.getElementById('stepNumber');
        const stepTitle = document.getElementById('stepTitle');
        const stepText = document.getElementById('stepText');
        const stepAction = document.getElementById('stepAction');
        const actionType = document.getElementById('actionType');
        const actionTarget = document.getElementById('actionTarget');
        const progressFill = document.getElementById('progressFill');
        const currentStepNum = document.getElementById('currentStepNum');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const completionModal = document.getElementById('completionModal');
        const pointer = document.getElementById('pointer');
        const pointerOverlay = document.getElementById('pointerOverlay');
        
        // Load current step
        function loadStep(step) {
            if (step < 1 || step > totalSteps) return;
            
            const stepData = lessonContent[step - 1];
            if (!stepData) return;
            
            currentStep = step;
            
            // Update UI
            stepNumber.textContent = step;
            stepTitle.textContent = stepData.title || `Step ${step}`;
            stepText.textContent = stepData.text || '';
            currentStepNum.textContent = step;
            progressFill.style.width = `${(step / totalSteps) * 100}%`;
            
            // Update action hint
            if (stepData.action && stepData.action !== 'next') {
                stepAction.style.display = 'inline-flex';
                actionType.textContent = formatAction(stepData.action);
                
                if (stepData.target) {
                    actionTarget.style.display = 'inline';
                    actionTarget.textContent = stepData.target;
                } else {
                    actionTarget.style.display = 'none';
                }
            } else {
                stepAction.style.display = 'none';
            }
            
            // Update navigation buttons
            btnPrev.disabled = step <= 1;
            btnNext.innerHTML = step >= totalSteps 
                ? 'Complete <i class="fas fa-check"></i>'
                : 'Next <i class="fas fa-chevron-right"></i>';
            
            // Save progress
            saveProgress(step);
            
            // Show pointer if target specified
            if (stepData.target) {
                showPointer(stepData.target);
            } else {
                hidePointer();
            }
        }
        
        // Format action type for display
        function formatAction(action) {
            const actions = {
                'click': 'Click the element',
                'input': 'Enter text',
                'drag': 'Drag and drop',
                'guided': 'Follow the guide',
                'complete': 'Complete the task',
                'next': 'Continue'
            };
            return actions[action] || action;
        }
        
        // Save progress to server
        async function saveProgress(step) {
            try {
                const formData = new FormData();
                formData.append('step', step);
                
                await fetch(`engine.php?lesson=${lessonId}&action=update_progress`, {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Failed to save progress:', error);
            }
        }
        
        // Complete lesson
        async function completeLesson() {
            try {
                await fetch(`engine.php?lesson=${lessonId}&action=complete_lesson`, {
                    method: 'POST'
                });
                completionModal.classList.add('show');
            } catch (error) {
                console.error('Failed to complete lesson:', error);
            }
        }
        
        // Skip lesson
        async function skipLesson() {
            if (!confirm('Are you sure you want to skip this lesson?')) return;
            
            try {
                await fetch(`engine.php?lesson=${lessonId}&action=skip_lesson`, {
                    method: 'POST'
                });
                
                if (nextLessonId) {
                    location.href = `engine.php?lesson=${nextLessonId}`;
                } else {
                    location.href = 'index.php';
                }
            } catch (error) {
                console.error('Failed to skip lesson:', error);
            }
        }
        
        // Show animated pointer
        function showPointer(targetSelector) {
            // In a real implementation, this would find the element on the actual page
            // For now, we'll just show the pointer in a default position
            pointerOverlay.style.display = 'block';
            pointer.style.left = '50%';
            pointer.style.top = '50%';
        }
        
        // Hide pointer
        function hidePointer() {
            pointerOverlay.style.display = 'none';
        }
        
        // Event Listeners
        btnPrev.addEventListener('click', () => {
            if (currentStep > 1) {
                loadStep(currentStep - 1);
            }
        });
        
        btnNext.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                loadStep(currentStep + 1);
            } else {
                completeLesson();
            }
        });
        
        document.getElementById('btnSkip').addEventListener('click', skipLesson);
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'Enter') {
                btnNext.click();
            } else if (e.key === 'ArrowLeft') {
                btnPrev.click();
            } else if (e.key === 'Escape') {
                location.href = 'index.php';
            }
        });
        
        // Initialize
        loadStep(currentStep);
    </script>
</body>
</html>
