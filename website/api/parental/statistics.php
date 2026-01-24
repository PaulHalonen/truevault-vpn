<?php
/**
 * TrueVault VPN - Parental Statistics API
 * Part 11 - Task 11.8
 * Usage stats and weekly reports
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Helper for fetchAll
function fetchAllAssoc($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $rows[] = $row; }
    return $rows;
}

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? 'daily';
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    switch ($action) {
        case 'daily':
            if ($deviceId) {
                $stmt = $db->prepare("SELECT * FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-' || ? || ' days') AND device_id = ? ORDER BY stat_date DESC");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $days, SQLITE3_INTEGER);
                $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
            } else {
                $stmt = $db->prepare("SELECT * FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-' || ? || ' days') ORDER BY stat_date DESC");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $days, SQLITE3_INTEGER);
            }
            $result = $stmt->execute();
            echo json_encode(['success' => true, 'statistics' => fetchAllAssoc($result)]);
            break;
            
        case 'summary':
            $stmt = $db->prepare("SELECT SUM(total_minutes) as total_screen_time, SUM(gaming_minutes) as total_gaming, SUM(streaming_minutes) as total_streaming, SUM(social_minutes) as total_social, SUM(educational_minutes) as total_educational, SUM(blocked_requests) as total_blocked, COUNT(DISTINCT stat_date) as days_tracked FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-' || ? || ' days')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $days, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $summary = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($summary['days_tracked'] > 0) {
                $summary['avg_daily_screen_time'] = round($summary['total_screen_time'] / $summary['days_tracked']);
                $summary['avg_daily_gaming'] = round($summary['total_gaming'] / $summary['days_tracked']);
            }
            
            echo json_encode(['success' => true, 'summary' => $summary, 'period_days' => $days]);
            break;
            
        case 'blocked':
            $stmt = $db->prepare("SELECT domain, COUNT(*) as count, MAX(blocked_at) as last_blocked FROM blocked_requests WHERE user_id = ? AND blocked_at >= datetime('now', '-' || ? || ' days') GROUP BY domain ORDER BY count DESC LIMIT 20");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $days, SQLITE3_INTEGER);
            $result = $stmt->execute();
            echo json_encode(['success' => true, 'blocked_sites' => fetchAllAssoc($result)]);
            break;
            
        case 'activity':
            $stmt = $db->prepare("SELECT * FROM parental_activity_log WHERE user_id = ? ORDER BY performed_at DESC LIMIT 50");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            echo json_encode(['success' => true, 'activity' => fetchAllAssoc($result)]);
            break;
            
        case 'weekly_report':
            $report = generateWeeklyReport($db, $user['id']);
            echo json_encode(['success' => true, 'report' => $report]);
            break;
            
        case 'compare':
            $stmt = $db->prepare("SELECT SUM(total_minutes) as total FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-7 days')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $tw = $row['total'] ?? 0;
            
            $stmt = $db->prepare("SELECT SUM(total_minutes) as total FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-14 days') AND stat_date < date('now', '-7 days')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $lw = $row['total'] ?? 0;
            
            $change = $lw > 0 ? round((($tw - $lw) / $lw) * 100, 1) : 0;
            echo json_encode(['success' => true, 'this_week' => $tw, 'last_week' => $lw, 'change_percent' => $change]);
            break;
            
        case 'record':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO parental_statistics (user_id, device_id, stat_date, total_minutes, gaming_minutes, streaming_minutes, social_minutes, educational_minutes, blocked_requests) VALUES (?, ?, date('now'), ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id, device_id, stat_date) DO UPDATE SET total_minutes = total_minutes + ?, gaming_minutes = gaming_minutes + ?, streaming_minutes = streaming_minutes + ?, social_minutes = social_minutes + ?, educational_minutes = educational_minutes + ?, blocked_requests = blocked_requests + ?, updated_at = CURRENT_TIMESTAMP");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $input['device_id'] ?? null, SQLITE3_INTEGER);
            $stmt->bindValue(3, $input['total_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(4, $input['gaming_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(5, $input['streaming_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(6, $input['social_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(7, $input['educational_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(8, $input['blocked_requests'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(9, $input['total_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(10, $input['gaming_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(11, $input['streaming_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(12, $input['social_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(13, $input['educational_minutes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(14, $input['blocked_requests'] ?? 0, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateWeeklyReport($db, $userId) {
    $stmt = $db->prepare("SELECT SUM(total_minutes) as screen_time, SUM(gaming_minutes) as gaming, SUM(streaming_minutes) as streaming, SUM(social_minutes) as social, SUM(educational_minutes) as educational, SUM(blocked_requests) as blocked FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-7 days')");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $stats = $result->fetchArray(SQLITE3_ASSOC);
    
    $stmt = $db->prepare("SELECT domain, COUNT(*) as count FROM blocked_requests WHERE user_id = ? AND blocked_at >= datetime('now', '-7 days') GROUP BY domain ORDER BY count DESC LIMIT 5");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $topBlocked = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $topBlocked[] = $row; }
    
    $stmt = $db->prepare("SELECT stat_date, total_minutes, gaming_minutes, streaming_minutes FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-7 days') ORDER BY stat_date");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $daily = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $daily[] = $row; }
    
    return [
        'period' => ['start' => date('Y-m-d', strtotime('-7 days')), 'end' => date('Y-m-d')],
        'summary' => $stats,
        'top_blocked' => $topBlocked,
        'daily_breakdown' => $daily,
        'generated_at' => date('Y-m-d H:i:s')
    ];
}
?>
