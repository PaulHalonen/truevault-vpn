<?php
/**
 * TrueVault VPN - Schedule Windows API
 * 
 * Manages time windows within schedules
 * 
 * Endpoints:
 * GET    /api/parental/windows.php?schedule_id=X - List windows
 * POST   /api/parental/windows.php - Add time window
 * PUT    /api/parental/windows.php - Update window
 * DELETE /api/parental/windows.php - Delete window
 */

define('TRUEVAULT_INIT', true);

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    switch ($method) {
        case 'GET':
            handleGet($conn, $userId);
            break;
        case 'POST':
            handlePost($conn, $userId);
            break;
        case 'PUT':
            handlePut($conn, $userId);
            break;
        case 'DELETE':
            handleDelete($conn, $userId);
            break;
        default:
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
    if (!isset($_GET['schedule_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Schedule ID required']);
        return;
    }
    
    $scheduleId = (int)$_GET['schedule_id'];
    
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT user_id FROM parental_schedules 
        WHERE id = ? AND (user_id = ? OR user_id = 0)
    ");
    $stmt->execute([$scheduleId, $userId]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    // Get windows
    $stmt = $conn->prepare("
        SELECT * FROM schedule_windows 
        WHERE schedule_id = ? 
        ORDER BY 
            COALESCE(day_of_week, 999),
            COALESCE(specific_date, '9999-12-31'),
            start_time
    ");
    $stmt->execute([$scheduleId]);
    $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'windows' => $windows
    ]);
}

function handlePost($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['schedule_id']) || !isset($input['start_time']) || 
        !isset($input['end_time']) || !isset($input['access_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    $scheduleId = (int)$input['schedule_id'];
    
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT user_id FROM parental_schedules WHERE id = ?
    ");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule || $schedule['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    // Check for overlapping windows
    if (hasOverlap($conn, $scheduleId, $input, null)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Time window overlaps with existing window']);
        return;
    }
    
    // Insert window
    $stmt = $conn->prepare("
        INSERT INTO schedule_windows 
        (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $scheduleId,
        $input['day_of_week'] ?? null,
        $input['specific_date'] ?? null,
        $input['start_time'],
        $input['end_time'],
        $input['access_type'],
        $input['notes'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Time window added successfully',
        'window_id' => $conn->lastInsertId()
    ]);
}

function handlePut($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Window ID required']);
        return;
    }
    
    $windowId = (int)$input['id'];
    
    // Get window and verify ownership
    $stmt = $conn->prepare("
        SELECT w.*, s.user_id 
        FROM schedule_windows w
        JOIN parental_schedules s ON w.schedule_id = s.id
        WHERE w.id = ?
    ");
    $stmt->execute([$windowId]);
    $window = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$window || $window['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    // Check for overlaps (excluding this window)
    if (hasOverlap($conn, $window['schedule_id'], $input, $windowId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Time window overlaps']);
        return;
    }
    
    // Update window
    $stmt = $conn->prepare("
        UPDATE schedule_windows 
        SET day_of_week = ?,
            specific_date = ?,
            start_time = ?,
            end_time = ?,
            access_type = ?,
            notes = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['day_of_week'] ?? null,
        $input['specific_date'] ?? null,
        $input['start_time'],
        $input['end_time'],
        $input['access_type'],
        $input['notes'] ?? null,
        $windowId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Time window updated successfully'
    ]);
}

function handleDelete($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Window ID required']);
        return;
    }
    
    $windowId = (int)$input['id'];
    
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT w.*, s.user_id 
        FROM schedule_windows w
        JOIN parental_schedules s ON w.schedule_id = s.id
        WHERE w.id = ?
    ");
    $stmt->execute([$windowId]);
    $window = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$window || $window['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    // Delete window
    $stmt = $conn->prepare("DELETE FROM schedule_windows WHERE id = ?");
    $stmt->execute([$windowId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Time window deleted successfully'
    ]);
}

/**
 * Check if time window overlaps with existing windows
 */
function hasOverlap($conn, $scheduleId, $window, $excludeId = null) {
    $query = "
        SELECT COUNT(*) as count FROM schedule_windows 
        WHERE schedule_id = ?
        AND (
            (day_of_week = ? OR (day_of_week IS NULL AND ? IS NULL))
            OR (specific_date = ? OR (specific_date IS NULL AND ? IS NULL))
        )
        AND (
            (start_time < ? AND end_time > ?)
            OR (start_time < ? AND end_time > ?)
            OR (start_time >= ? AND end_time <= ?)
        )
    ";
    
    if ($excludeId) {
        $query .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($query);
    
    $params = [
        $scheduleId,
        $window['day_of_week'] ?? null,
        $window['day_of_week'] ?? null,
        $window['specific_date'] ?? null,
        $window['specific_date'] ?? null,
        $window['end_time'],
        $window['start_time'],
        $window['end_time'],
        $window['start_time'],
        $window['start_time'],
        $window['end_time']
    ];
    
    if ($excludeId) {
        $params[] = $excludeId;
    }
    
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}
?>
