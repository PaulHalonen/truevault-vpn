<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Rate tutorial
if ($action === 'rate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutorialId = $_POST['tutorial_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $userId = $_POST['user_id'] ?? null;
    $review = $_POST['review'] ?? null;
    
    if (!$tutorialId || !$rating || !$userId) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $result = rateTutorial($userId, $tutorialId, $rating, $review);
    jsonResponse(['success' => $result]);
}

// Track progress
if ($action === 'progress' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $tutorialId = $_POST['tutorial_id'] ?? null;
    $lessonId = $_POST['lesson_id'] ?? null;
    $status = $_POST['status'] ?? 'in_progress';
    
    if (!$userId || !$tutorialId || !$lessonId) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    trackProgress($userId, $tutorialId, $lessonId, $status);
    jsonResponse(['success' => true]);
}

// Complete tutorial
if ($action === 'complete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $tutorialId = $_POST['tutorial_id'] ?? null;
    
    if (!$userId || !$tutorialId) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $lessons = getLessons($tutorialId);
    foreach ($lessons as $lesson) {
        trackProgress($userId, $tutorialId, $lesson['id'], 'completed');
    }
    
    jsonResponse(['success' => true]);
}

// Add bookmark
if ($action === 'bookmark' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $tutorialId = $_POST['tutorial_id'] ?? null;
    
    if (!$userId || !$tutorialId) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    $result = addBookmark($userId, $tutorialId);
    jsonResponse(['success' => $result]);
}

// Remove bookmark
if ($action === 'remove_bookmark' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $tutorialId = $_POST['tutorial_id'] ?? null;
    
    if (!$userId || !$tutorialId) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
    }
    
    removeBookmark($userId, $tutorialId);
    jsonResponse(['success' => true]);
}

// Get user progress
if ($action === 'get_progress') {
    $userId = $_GET['user_id'] ?? null;
    $tutorialId = $_GET['tutorial_id'] ?? null;
    
    if (!$userId) {
        jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
    }
    
    $progress = getUserProgress($userId, $tutorialId);
    jsonResponse(['success' => true, 'progress' => $progress]);
}

// Get user bookmarks
if ($action === 'get_bookmarks') {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
    }
    
    $bookmarks = getUserBookmarks($userId);
    jsonResponse(['success' => true, 'bookmarks' => $bookmarks]);
}

// Search tutorials
if ($action === 'search') {
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        jsonResponse(['success' => false, 'error' => 'Search query required'], 400);
    }
    
    $results = searchTutorials($query);
    jsonResponse(['success' => true, 'results' => $results]);
}

// Get popular tutorials
if ($action === 'popular') {
    $limit = $_GET['limit'] ?? 5;
    $tutorials = getPopularTutorials($limit);
    jsonResponse(['success' => true, 'tutorials' => $tutorials]);
}

// Default response
jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
?>
