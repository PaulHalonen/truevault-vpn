<?php
// Tutorial System Configuration
// USES: SQLite3 class (NOT PDO)
define('TUTORIALS_DB_PATH', __DIR__ . '/../databases/tutorials.db');

function getTutorialsDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new SQLite3(TUTORIALS_DB_PATH);
            $db->enableExceptions(true);
            $db->busyTimeout(5000);
        } catch (Exception $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $db;
}

// Get all categories
function getCategories() {
    $db = getTutorialsDB();
    $result = $db->query("SELECT * FROM tutorial_categories WHERE is_active = 1 ORDER BY sort_order");
    
    $categories = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $categories[] = $row;
    }
    return $categories;
}

// Get tutorials by category
function getTutorials($categoryId = null, $featured = false) {
    $db = getTutorialsDB();
    
    $where = ['is_published = 1'];
    $params = [];
    
    if ($categoryId) {
        $where[] = 'category_id = :category_id';
        $params[':category_id'] = [$categoryId, SQLITE3_INTEGER];
    }
    
    if ($featured) {
        $where[] = 'is_featured = 1';
    }
    
    $whereClause = implode(' AND ', $where);
    
    $stmt = $db->prepare("
        SELECT t.*, c.category_name, c.icon as category_icon
        FROM tutorials t
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE $whereClause
        ORDER BY t.created_at DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value[0], $value[1]);
    }
    
    $result = $stmt->execute();
    $tutorials = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tutorials[] = $row;
    }
    return $tutorials;
}

// Get single tutorial
function getTutorial($id) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT t.*, c.category_name, c.icon as category_icon
        FROM tutorials t
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE t.id = :id
    ");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Get tutorial by slug
function getTutorialBySlug($slug) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT t.*, c.category_name, c.icon as category_icon
        FROM tutorials t
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE t.slug = :slug
    ");
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Get tutorial lessons
function getLessons($tutorialId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT * FROM tutorial_lessons
        WHERE tutorial_id = :id
        ORDER BY sort_order, lesson_number
    ");
    $stmt->bindValue(':id', $tutorialId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $lessons = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $lessons[] = $row;
    }
    return $lessons;
}

// Get single lesson
function getLesson($lessonId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("SELECT * FROM tutorial_lessons WHERE id = :id");
    $stmt->bindValue(':id', $lessonId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Track user progress
function trackProgress($userId, $tutorialId, $lessonId, $status = 'in_progress') {
    $db = getTutorialsDB();
    
    // Calculate progress percentage
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tutorial_lessons WHERE tutorial_id = :id");
    $stmt->bindValue(':id', $tutorialId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $totalLessons = $row['total'];
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as completed 
        FROM tutorial_progress 
        WHERE user_id = :user_id AND tutorial_id = :tutorial_id AND status = 'completed'
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $completedLessons = $row['completed'];
    
    $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
    
    $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $db->prepare("
        INSERT INTO tutorial_progress (user_id, tutorial_id, lesson_id, status, progress_percent, completed_at)
        VALUES (:user_id, :tutorial_id, :lesson_id, :status, :progress, :completed_at)
        ON CONFLICT(user_id, tutorial_id, lesson_id) 
        DO UPDATE SET status = :status2, progress_percent = :progress2, last_accessed = CURRENT_TIMESTAMP, completed_at = :completed_at2
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
    $stmt->bindValue(':lesson_id', $lessonId, SQLITE3_INTEGER);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':progress', $progressPercent, SQLITE3_INTEGER);
    $stmt->bindValue(':completed_at', $completedAt, SQLITE3_TEXT);
    $stmt->bindValue(':status2', $status, SQLITE3_TEXT);
    $stmt->bindValue(':progress2', $progressPercent, SQLITE3_INTEGER);
    $stmt->bindValue(':completed_at2', $completedAt, SQLITE3_TEXT);
    $stmt->execute();
}

// Get user progress
function getUserProgress($userId, $tutorialId = null) {
    $db = getTutorialsDB();
    
    if ($tutorialId) {
        $stmt = $db->prepare("
            SELECT * FROM tutorial_progress
            WHERE user_id = :user_id AND tutorial_id = :tutorial_id
            ORDER BY last_accessed DESC
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT tp.*, t.title, t.thumbnail
            FROM tutorial_progress tp
            LEFT JOIN tutorials t ON tp.tutorial_id = t.id
            WHERE tp.user_id = :user_id
            ORDER BY tp.last_accessed DESC
        ");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
    }
    
    $progress = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $progress[] = $row;
    }
    return $progress;
}

// Add bookmark
function addBookmark($userId, $tutorialId) {
    $db = getTutorialsDB();
    try {
        $stmt = $db->prepare("INSERT INTO tutorial_bookmarks (user_id, tutorial_id) VALUES (:user_id, :tutorial_id)");
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false; // Already bookmarked
    }
}

// Remove bookmark
function removeBookmark($userId, $tutorialId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("DELETE FROM tutorial_bookmarks WHERE user_id = :user_id AND tutorial_id = :tutorial_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
    $stmt->execute();
}

// Get user bookmarks
function getUserBookmarks($userId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT t.*, c.category_name
        FROM tutorial_bookmarks b
        LEFT JOIN tutorials t ON b.tutorial_id = t.id
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE b.user_id = :user_id
        ORDER BY b.created_at DESC
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $bookmarks = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $bookmarks[] = $row;
    }
    return $bookmarks;
}

// Rate tutorial
function rateTutorial($userId, $tutorialId, $rating, $review = null) {
    $db = getTutorialsDB();
    
    if ($rating < 1 || $rating > 5) {
        return false;
    }
    
    $stmt = $db->prepare("
        INSERT INTO tutorial_ratings (user_id, tutorial_id, rating, review)
        VALUES (:user_id, :tutorial_id, :rating, :review)
        ON CONFLICT(user_id, tutorial_id)
        DO UPDATE SET rating = :rating2, review = :review2, created_at = CURRENT_TIMESTAMP
    ");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':tutorial_id', $tutorialId, SQLITE3_INTEGER);
    $stmt->bindValue(':rating', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':review', $review, SQLITE3_TEXT);
    $stmt->bindValue(':rating2', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':review2', $review, SQLITE3_TEXT);
    $stmt->execute();
    return true;
}

// Get tutorial rating
function getTutorialRating($tutorialId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
        FROM tutorial_ratings
        WHERE tutorial_id = :id
    ");
    $stmt->bindValue(':id', $tutorialId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Search tutorials
function searchTutorials($query) {
    $db = getTutorialsDB();
    $searchTerm = "%$query%";
    
    $stmt = $db->prepare("
        SELECT t.*, c.category_name, c.icon as category_icon
        FROM tutorials t
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE t.is_published = 1 
        AND (t.title LIKE :search1 OR t.description LIKE :search2)
        ORDER BY t.views DESC
    ");
    $stmt->bindValue(':search1', $searchTerm, SQLITE3_TEXT);
    $stmt->bindValue(':search2', $searchTerm, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $tutorials = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tutorials[] = $row;
    }
    return $tutorials;
}

// Increment view count
function incrementViews($tutorialId) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("UPDATE tutorials SET views = views + 1 WHERE id = :id");
    $stmt->bindValue(':id', $tutorialId, SQLITE3_INTEGER);
    $stmt->execute();
}

// Get popular tutorials
function getPopularTutorials($limit = 5) {
    $db = getTutorialsDB();
    $stmt = $db->prepare("
        SELECT t.*, c.category_name
        FROM tutorials t
        LEFT JOIN tutorial_categories c ON t.category_id = c.id
        WHERE t.is_published = 1
        ORDER BY t.views DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $tutorials = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tutorials[] = $row;
    }
    return $tutorials;
}

// Get tutorial statistics
function getTutorialStats() {
    $db = getTutorialsDB();
    
    $stats = [];
    
    $result = $db->query("SELECT COUNT(*) as count FROM tutorials WHERE is_published = 1");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_tutorials'] = $row['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM tutorial_categories WHERE is_active = 1");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_categories'] = $row['count'];
    
    $result = $db->query("SELECT SUM(views) as total FROM tutorials");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $stats['total_views'] = $row['total'] ?? 0;
    
    return $stats;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
