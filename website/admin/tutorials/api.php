<?php
/**
 * TrueVault VPN - Tutorial API
 * Task 16.6: API endpoints for tutorials and help bubbles
 * Created: January 24, 2026
 */

header('Content-Type: application/json');

session_start();

// Check authentication for protected endpoints
$publicActions = ['get_bubbles']; // Actions that don't require auth
$action = $_GET['action'] ?? '';

if (!in_array($action, $publicActions)) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

$userId = $_SESSION['admin_id'] ?? 1;

// Database connection
$tutorialsDb = new SQLite3(__DIR__ . '/databases/tutorials.db');

// Response helper
function respond($data) {
    echo json_encode($data);
    exit;
}

// Handle actions
switch ($action) {
    
    // Get help bubbles for a specific page
    case 'get_bubbles':
        $page = $_GET['page'] ?? '';
        
        $stmt = $tutorialsDb->prepare("
            SELECT * FROM help_bubbles 
            WHERE is_active = 1 
            AND (page_url = :page OR page_url LIKE :pageWild OR page_url = '*')
            ORDER BY priority ASC
        ");
        $stmt->bindValue(':page', $page, SQLITE3_TEXT);
        $stmt->bindValue(':pageWild', '%' . basename($page) . '%', SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $bubbles = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $bubbles[] = $row;
        }
        
        respond(['success' => true, 'bubbles' => $bubbles]);
        break;
    
    // Get all tutorials grouped by category
    case 'get_tutorials':
        $result = $tutorialsDb->query("
            SELECT l.*, 
                COALESCE(p.status, 'not_started') as user_status,
                COALESCE(p.current_step, 0) as user_step
            FROM tutorial_lessons l
            LEFT JOIN user_tutorial_progress p ON l.id = p.lesson_id AND p.user_id = $userId
            WHERE l.is_active = 1
            ORDER BY l.category, l.lesson_number
        ");
        
        $tutorials = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $category = $row['category'];
            if (!isset($tutorials[$category])) {
                $tutorials[$category] = [];
            }
            $tutorials[$category][] = $row;
        }
        
        respond(['success' => true, 'tutorials' => $tutorials]);
        break;
    
    // Get single lesson details
    case 'get_lesson':
        $lessonId = (int)($_GET['lesson_id'] ?? 0);
        
        $stmt = $tutorialsDb->prepare("SELECT * FROM tutorial_lessons WHERE id = :id AND is_active = 1");
        $stmt->bindValue(':id', $lessonId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $lesson = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($lesson) {
            $lesson['lesson_content'] = json_decode($lesson['lesson_content'], true);
            respond(['success' => true, 'lesson' => $lesson]);
        } else {
            respond(['success' => false, 'error' => 'Lesson not found']);
        }
        break;
    
    // Get user progress summary
    case 'get_progress':
        // Total lessons
        $totalLessons = $tutorialsDb->querySingle("SELECT COUNT(*) FROM tutorial_lessons WHERE is_active = 1");
        
        // Completed
        $completed = $tutorialsDb->querySingle("SELECT COUNT(*) FROM user_tutorial_progress WHERE user_id = $userId AND status = 'completed'");
        
        // In progress
        $inProgress = $tutorialsDb->querySingle("SELECT COUNT(*) FROM user_tutorial_progress WHERE user_id = $userId AND status = 'in_progress'");
        
        // By category
        $categories = [];
        $result = $tutorialsDb->query("
            SELECT l.category, 
                COUNT(*) as total,
                SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM tutorial_lessons l
            LEFT JOIN user_tutorial_progress p ON l.id = p.lesson_id AND p.user_id = $userId
            WHERE l.is_active = 1
            GROUP BY l.category
        ");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $categories[$row['category']] = [
                'total' => (int)$row['total'],
                'completed' => (int)$row['completed']
            ];
        }
        
        respond([
            'success' => true,
            'progress' => [
                'total' => $totalLessons,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'percent' => $totalLessons > 0 ? round(($completed / $totalLessons) * 100) : 0,
                'categories' => $categories
            ]
        ]);
        break;
    
    // Update lesson progress
    case 'update_progress':
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $step = (int)($_POST['step'] ?? 1);
        $status = $_POST['status'] ?? 'in_progress';
        
        // Check if progress record exists
        $stmt = $tutorialsDb->prepare("SELECT id FROM user_tutorial_progress WHERE user_id = :user_id AND lesson_id = :lesson_id");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($existing) {
            $stmt = $tutorialsDb->prepare("
                UPDATE user_tutorial_progress 
                SET current_step = :step, status = :status, 
                    completed_at = CASE WHEN :status = 'completed' THEN datetime('now') ELSE completed_at END
                WHERE user_id = :user_id AND lesson_id = :lesson_id
            ");
        } else {
            $stmt = $tutorialsDb->prepare("
                INSERT INTO user_tutorial_progress (user_id, lesson_id, current_step, status, started_at)
                VALUES (:user_id, :lesson_id, :step, :status, datetime('now'))
            ");
        }
        
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
        $stmt->bindValue(':step', $step, SQLITE3_INTEGER);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->execute();
        
        respond(['success' => true]);
        break;
    
    // Get guided tour steps
    case 'get_tour':
        $tourId = $_GET['tour_id'] ?? '';
        
        // Predefined tours
        $tours = [
            'database-builder' => [
                ['selector' => '#createTableBtn', 'title' => 'Create Table', 'content' => 'Click here to create a new database table.', 'position' => 'bottom'],
                ['selector' => '.table-list', 'title' => 'Your Tables', 'content' => 'All your tables will appear here.', 'position' => 'right'],
                ['selector' => '#fieldTypeSelect', 'title' => 'Field Types', 'content' => 'Choose the right field type for your data.', 'position' => 'bottom'],
            ],
            'form-builder' => [
                ['selector' => '#createFormBtn', 'title' => 'Create Form', 'content' => 'Start building a new form.', 'position' => 'bottom'],
                ['selector' => '.field-palette', 'title' => 'Field Types', 'content' => 'Drag these onto your form.', 'position' => 'right'],
                ['selector' => '.form-canvas', 'title' => 'Form Canvas', 'content' => 'Your form fields will appear here.', 'position' => 'left'],
            ],
            'marketing' => [
                ['selector' => '#runAutomation', 'title' => 'Run Automation', 'content' => 'Click to process scheduled posts.', 'position' => 'bottom'],
                ['selector' => '.platform-list', 'title' => 'Platforms', 'content' => 'Manage your 50+ marketing platforms.', 'position' => 'right'],
                ['selector' => '.calendar-view', 'title' => 'Content Calendar', 'content' => 'View your 365-day content plan.', 'position' => 'top'],
            ]
        ];
        
        if (isset($tours[$tourId])) {
            respond(['success' => true, 'steps' => $tours[$tourId]]);
        } else {
            respond(['success' => false, 'error' => 'Tour not found']);
        }
        break;
    
    // Search tutorials
    case 'search':
        $query = $_GET['q'] ?? '';
        
        $stmt = $tutorialsDb->prepare("
            SELECT * FROM tutorial_lessons 
            WHERE is_active = 1 
            AND (title LIKE :query OR description LIKE :query OR category LIKE :query)
            ORDER BY lesson_number
            LIMIT 10
        ");
        $stmt->bindValue(':query', '%' . $query . '%', SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $results = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            unset($row['lesson_content']); // Don't send full content
            $results[] = $row;
        }
        
        respond(['success' => true, 'results' => $results]);
        break;
    
    default:
        respond(['success' => false, 'error' => 'Invalid action']);
}

$tutorialsDb->close();
