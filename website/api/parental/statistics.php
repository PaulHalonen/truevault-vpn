<?php
/**
 * TrueVault VPN - Parental Statistics API
 * Part 11 - Task 11.8
 * Usage stats and weekly reports
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$action = $_GET['action'] ?? 'daily';
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;

try {
    $db = getDatabase();
    
    switch ($action) {
        case 'daily':
            // Get daily statistics
            $sql = "SELECT * FROM parental_statistics WHERE user_id = ? AND stat_date >= date('now', '-$days days')";
            $params = [$user['id']];
            if ($deviceId) { $sql .= " AND device_id = ?"; $params[] = $deviceId; }
            $sql .= " ORDER BY stat_date DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'statistics' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
            
        case 'summary':
            // Get summary for period
            $stmt = $db->prepare("
                SELECT 
                    SUM(total_minutes) as total_screen_time,
                    SUM(gaming_minutes) as total_gaming,
                    SUM(streaming_minutes) as total_streaming,
                    SUM(social_minutes) as total_social,
                    SUM(educational_minutes) as total_educational,
                    SUM(blocked_requests) as total_blocked,
                    COUNT(DISTINCT stat_date) as days_tracked
                FROM parental_statistics 
                WHERE user_id = ? AND stat_date >= date('now', '-$days days')
            ");
            $stmt->execute([$user['id']]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate averages
            if ($summary['days_tracked'] > 0) {
                $summary['avg_daily_screen_time'] = round($summary['total_screen_time'] / $summary['days_tracked']);
                $summary['avg_daily_gaming'] = round($summary['total_gaming'] / $summary['days_tracked']);
            }
            
            echo json_encode(['success' => true, 'summary' => $summary, 'period_days' => $days]);
            break;
            
        case 'blocked':
            // Get blocked requests log
            $stmt = $db->prepare("
                SELECT domain, COUNT(*) as count, MAX(blocked_at) as last_blocked
                FROM blocked_requests 
                WHERE user_id = ? AND blocked_at >= datetime('now', '-$days days')
                GROUP BY domain ORDER BY count DESC LIMIT 20
            ");
            $stmt->execute([$user['id']]);
            echo json_encode(['success' => true, 'blocked_sites' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
            
        case 'activity':
            // Get parental activity log
            $stmt = $db->prepare("
                SELECT * FROM parental_activity_log 
                WHERE user_id = ? 
                ORDER BY performed_at DESC LIMIT 50
            ");
            $stmt->execute([$user['id']]);
            echo json_encode(['success' => true, 'activity' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
            
        case 'weekly_report':
            // Generate weekly report data
            $report = generateWeeklyReport($db, $user['id']);
            echo json_encode(['success' => true, 'report' => $report]);
            break;
            
        case 'compare':
            // Compare this week vs last week
            $thisWeek = $db->prepare("
                SELECT SUM(total_minutes) as total FROM parental_statistics 
                WHERE user_id = ? AND stat_date >= date('now', '-7 days')
            ");
            $thisWeek->execute([$user['id']]);
            $tw = $thisWeek->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $lastWeek = $db->prepare("
                SELECT SUM(total_minutes) as total FROM parental_statistics 
                WHERE user_id = ? AND stat_date >= date('now', '-14 days') AND stat_date < date('now', '-7 days')
            ");
            $lastWeek->execute([$user['id']]);
            $lw = $lastWeek->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $change = $lw > 0 ? round((($tw - $lw) / $lw) * 100, 1) : 0;
            
            echo json_encode(['success' => true, 'this_week' => $tw, 'last_week' => $lw, 'change_percent' => $change]);
            break;
            
        case 'record':
            // Record new statistics (called by enforcement system)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                INSERT INTO parental_statistics (user_id, device_id, stat_date, total_minutes, gaming_minutes, streaming_minutes, social_minutes, educational_minutes, blocked_requests)
                VALUES (?, ?, date('now'), ?, ?, ?, ?, ?, ?)
                ON CONFLICT(user_id, device_id, stat_date) DO UPDATE SET
                    total_minutes = total_minutes + ?,
                    gaming_minutes = gaming_minutes + ?,
                    streaming_minutes = streaming_minutes + ?,
                    social_minutes = social_minutes + ?,
                    educational_minutes = educational_minutes + ?,
                    blocked_requests = blocked_requests + ?,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $params = [
                $user['id'], $input['device_id'] ?? null,
                $input['total_minutes'] ?? 0, $input['gaming_minutes'] ?? 0,
                $input['streaming_minutes'] ?? 0, $input['social_minutes'] ?? 0,
                $input['educational_minutes'] ?? 0, $input['blocked_requests'] ?? 0,
                // Duplicate values for UPDATE
                $input['total_minutes'] ?? 0, $input['gaming_minutes'] ?? 0,
                $input['streaming_minutes'] ?? 0, $input['social_minutes'] ?? 0,
                $input['educational_minutes'] ?? 0, $input['blocked_requests'] ?? 0
            ];
            $stmt->execute($params);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateWeeklyReport($db, $userId) {
    // Get summary stats
    $stmt = $db->prepare("
        SELECT 
            SUM(total_minutes) as screen_time,
            SUM(gaming_minutes) as gaming,
            SUM(streaming_minutes) as streaming,
            SUM(social_minutes) as social,
            SUM(educational_minutes) as educational,
            SUM(blocked_requests) as blocked
        FROM parental_statistics 
        WHERE user_id = ? AND stat_date >= date('now', '-7 days')
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top blocked sites
    $stmt = $db->prepare("
        SELECT domain, COUNT(*) as count FROM blocked_requests 
        WHERE user_id = ? AND blocked_at >= datetime('now', '-7 days')
        GROUP BY domain ORDER BY count DESC LIMIT 5
    ");
    $stmt->execute([$userId]);
    $topBlocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get daily breakdown
    $stmt = $db->prepare("
        SELECT stat_date, total_minutes, gaming_minutes, streaming_minutes
        FROM parental_statistics 
        WHERE user_id = ? AND stat_date >= date('now', '-7 days')
        ORDER BY stat_date
    ");
    $stmt->execute([$userId]);
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'period' => ['start' => date('Y-m-d', strtotime('-7 days')), 'end' => date('Y-m-d')],
        'summary' => $stats,
        'top_blocked' => $topBlocked,
        'daily_breakdown' => $daily,
        'generated_at' => date('Y-m-d H:i:s')
    ];
}
?>
