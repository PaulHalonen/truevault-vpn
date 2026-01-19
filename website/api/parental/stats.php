<?php
/**
 * TrueVault VPN - Parental Statistics API
 * 
 * Generates weekly usage reports for parents
 * 
 * Endpoints:
 * GET /api/parental/stats.php - Get current week stats
 * GET /api/parental/stats.php?week=YYYY-MM-DD - Get specific week
 * POST /api/parental/stats.php?action=generate - Generate report
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = Auth::authenticate();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $auth['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection('parental');
    
    if ($method === 'GET') {
        handleGet($conn, $userId);
    } elseif ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'generate') {
        generateWeeklyReport($conn, $userId);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function handleGet($conn, $userId) {
    // Get week start (Monday of requested week)
    if (isset($_GET['week'])) {
        $weekStart = $_GET['week'];
    } else {
        // Current week
        $weekStart = date('Y-m-d', strtotime('monday this week'));
    }
    
    // Check if report exists
    $stmt = $conn->prepare("
        SELECT * FROM weekly_stats 
        WHERE user_id = ? AND week_start = ?
    ");
    $stmt->execute([$userId, $weekStart]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        // Generate on-the-fly
        $report = generateReport($conn, $userId, $weekStart);
    }
    
    // Get blocked requests breakdown by category
    $stmt = $conn->prepare("
        SELECT category, COUNT(*) as count 
        FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ?
        AND DATE(blocked_at) < DATE(?, '+7 days')
        GROUP BY category
        ORDER BY count DESC
    ");
    $stmt->execute([$userId, $weekStart, $weekStart]);
    $categoryBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get daily breakdown
    $stmt = $conn->prepare("
        SELECT DATE(blocked_at) as date, COUNT(*) as count
        FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ?
        AND DATE(blocked_at) < DATE(?, '+7 days')
        GROUP BY DATE(blocked_at)
        ORDER BY date
    ");
    $stmt->execute([$userId, $weekStart, $weekStart]);
    $dailyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'week_start' => $weekStart,
        'week_end' => date('Y-m-d', strtotime($weekStart . ' +6 days')),
        'summary' => $report,
        'by_category' => $categoryBreakdown,
        'by_day' => $dailyBreakdown
    ]);
}

function generateReport($conn, $userId, $weekStart) {
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    
    // Count blocked requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ? 
        AND DATE(blocked_at) <= ?
    ");
    $stmt->execute([$userId, $weekStart, $weekEnd]);
    $blockedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Most blocked domain
    $stmt = $conn->prepare("
        SELECT domain, COUNT(*) as count 
        FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ? 
        AND DATE(blocked_at) <= ?
        GROUP BY domain 
        ORDER BY count DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $weekStart, $weekEnd]);
    $mostBlocked = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Most blocked category
    $stmt = $conn->prepare("
        SELECT category, COUNT(*) as count 
        FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ? 
        AND DATE(blocked_at) <= ?
        GROUP BY category 
        ORDER BY count DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $weekStart, $weekEnd]);
    $mostBlockedCat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Peak usage hour
    $stmt = $conn->prepare("
        SELECT CAST(strftime('%H', blocked_at) AS INTEGER) as hour, COUNT(*) as count 
        FROM blocked_requests 
        WHERE user_id = ? 
        AND DATE(blocked_at) >= ? 
        AND DATE(blocked_at) <= ?
        GROUP BY hour 
        ORDER BY count DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $weekStart, $weekEnd]);
    $peakHour = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $report = [
        'user_id' => $userId,
        'week_start' => $weekStart,
        'total_blocked_requests' => $blockedCount,
        'total_allowed_requests' => 0, // TODO: Track allowed requests
        'most_blocked_domain' => $mostBlocked['domain'] ?? null,
        'most_blocked_category' => $mostBlockedCat['category'] ?? null,
        'peak_usage_hour' => $peakHour['hour'] ?? null
    ];
    
    return $report;
}

function generateWeeklyReport($conn, $userId) {
    $weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
    
    $report = generateReport($conn, $userId, $weekStart);
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT OR REPLACE INTO weekly_stats 
        (user_id, week_start, total_blocked_requests, total_allowed_requests, 
         most_blocked_domain, most_blocked_category, peak_usage_hour)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $report['week_start'],
        $report['total_blocked_requests'],
        $report['total_allowed_requests'],
        $report['most_blocked_domain'],
        $report['most_blocked_category'],
        $report['peak_usage_hour']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Weekly report generated',
        'report' => $report
    ]);
}
?>
