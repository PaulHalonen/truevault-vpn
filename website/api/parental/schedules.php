<?php
/**
 * TrueVault VPN - Parental Schedules API
 * 
 * Manages calendar-based schedules for parental controls
 * 
 * Endpoints:
 * GET    /api/parental/schedules.php - List all schedules
 * GET    /api/parental/schedules.php?id=X - Get schedule details
 * POST   /api/parental/schedules.php - Create new schedule
 * PUT    /api/parental/schedules.php - Update schedule
 * DELETE /api/parental/schedules.php - Delete schedule
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
    if (isset($_GET['id'])) {
        $scheduleId = (int)$_GET['id'];
        
        $stmt = $conn->prepare("
            SELECT * FROM parental_schedules 
            WHERE id = ? AND (user_id = ? OR user_id = 0)
        ");
        $stmt->execute([$scheduleId, $userId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Schedule not found']);
            return;
        }
        
        $stmt = $conn->prepare("
            SELECT * FROM schedule_windows 
            WHERE schedule_id = ? 
            ORDER BY day_of_week, start_time
        ");
        $stmt->execute([$scheduleId]);
        $schedule['windows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'schedule' => $schedule
        ]);
        
    } else {
        $stmt = $conn->prepare("
            SELECT s.*, COUNT(w.id) as window_count
            FROM parental_schedules s
            LEFT JOIN schedule_windows w ON s.id = w.schedule_id
            WHERE s.user_id = ? OR s.is_template = 1
            GROUP BY s.id
            ORDER BY s.is_template DESC, s.created_at DESC
        ");
        $stmt->execute([$userId]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'schedules' => $schedules
        ]);
    }
}

function handlePost($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['schedule_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Schedule name required']);
        return;
    }
    
    if (isset($input['clone_template_id'])) {
        cloneTemplate($conn, $userId, $input['clone_template_id'], $input['schedule_name']);
        return;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO parental_schedules 
        (user_id, device_id, schedule_name, description, is_active)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $input['device_id'] ?? null,
        $input['schedule_name'],
        $input['description'] ?? null,
        $input['is_active'] ?? 1
    ]);
    
    $scheduleId = $conn->lastInsertId();
    
    if (isset($input['windows']) && is_array($input['windows'])) {
        foreach ($input['windows'] as $window) {
            addTimeWindow($conn, $scheduleId, $window);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule created successfully',
        'schedule_id' => $scheduleId
    ]);
}

function handlePut($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Schedule ID required']);
        return;
    }
    
    $scheduleId = (int)$input['id'];
    
    $stmt = $conn->prepare("SELECT user_id FROM parental_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule || $schedule['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    $stmt = $conn->prepare("
        UPDATE parental_schedules 
        SET schedule_name = ?, 
            description = ?, 
            is_active = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([
        $input['schedule_name'] ?? null,
        $input['description'] ?? null,
        $input['is_active'] ?? 1,
        $scheduleId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule updated successfully'
    ]);
}

function handleDelete($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Schedule ID required']);
        return;
    }
    
    $scheduleId = (int)$input['id'];
    
    $stmt = $conn->prepare("
        SELECT user_id, is_template FROM parental_schedules WHERE id = ?
    ");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Schedule not found']);
        return;
    }
    
    if ($schedule['is_template']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cannot delete templates']);
        return;
    }
    
    if ($schedule['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM parental_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule deleted successfully'
    ]);
}

function cloneTemplate($conn, $userId, $templateId, $scheduleName) {
    $stmt = $conn->prepare("
        SELECT * FROM parental_schedules 
        WHERE id = ? AND is_template = 1
    ");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Template not found']);
        return;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO parental_schedules 
        (user_id, schedule_name, description, is_active)
        VALUES (?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $userId,
        $scheduleName,
        'Cloned from: ' . $template['schedule_name']
    ]);
    
    $newScheduleId = $conn->lastInsertId();
    
    $stmt = $conn->prepare("
        SELECT * FROM schedule_windows WHERE schedule_id = ?
    ");
    $stmt->execute([$templateId]);
    $windows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($windows as $window) {
        $stmt = $conn->prepare("
            INSERT INTO schedule_windows 
            (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $newScheduleId,
            $window['day_of_week'],
            $window['specific_date'],
            $window['start_time'],
            $window['end_time'],
            $window['access_type'],
            $window['notes']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Template cloned successfully',
        'schedule_id' => $newScheduleId
    ]);
}

function addTimeWindow($conn, $scheduleId, $window) {
    $stmt = $conn->prepare("
        INSERT INTO schedule_windows 
        (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $scheduleId,
        $window['day_of_week'] ?? null,
        $window['specific_date'] ?? null,
        $window['start_time'],
        $window['end_time'],
        $window['access_type'],
        $window['notes'] ?? null
    ]);
}
?>
