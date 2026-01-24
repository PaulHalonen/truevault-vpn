<?php
/**
 * TrueVault VPN - Parental Schedule API
 * Part 11 - Task 11.2
 * CRUD operations for parental schedules
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            if ($action === 'templates') {
                $result = $db->query("SELECT s.*, (SELECT COUNT(*) FROM schedule_windows WHERE schedule_id = s.id) as window_count FROM parental_schedules s WHERE s.is_template = 1 AND s.user_id = 0 ORDER BY s.schedule_name");
                echo json_encode(['success' => true, 'templates' => fetchAllAssoc($result)]);
            } elseif ($id) {
                $stmt = $db->prepare("SELECT * FROM parental_schedules WHERE id = ? AND (user_id = ? OR user_id = 0)");
                $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                $schedule = $result->fetchArray(SQLITE3_ASSOC);
                if (!$schedule) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Not found']); exit; }
                
                $wStmt = $db->prepare("SELECT * FROM schedule_windows WHERE schedule_id = ? ORDER BY day_of_week, start_time");
                $wStmt->bindValue(1, $id, SQLITE3_INTEGER);
                $wResult = $wStmt->execute();
                $schedule['windows'] = fetchAllAssoc($wResult);
                echo json_encode(['success' => true, 'schedule' => $schedule]);
            } else {
                $stmt = $db->prepare("SELECT s.*, d.device_name, (SELECT COUNT(*) FROM schedule_windows WHERE schedule_id = s.id) as window_count FROM parental_schedules s LEFT JOIN devices d ON s.device_id = d.id WHERE s.user_id = ? AND s.is_template = 0 ORDER BY s.schedule_name");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'schedules' => fetchAllAssoc($result)]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($action === 'clone') {
                $templateId = $input['template_id'] ?? null;
                $stmt = $db->prepare("SELECT * FROM parental_schedules WHERE id = ? AND is_template = 1");
                $stmt->bindValue(1, $templateId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                $template = $result->fetchArray(SQLITE3_ASSOC);
                if (!$template) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Template not found']); exit; }
                
                $stmt = $db->prepare("INSERT INTO parental_schedules (user_id, device_id, schedule_name, description, is_template, is_active) VALUES (?, ?, ?, ?, 0, 1)");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['device_id'] ?? null, SQLITE3_INTEGER);
                $stmt->bindValue(3, $input['name'] ?? $template['schedule_name'] . ' (Copy)', SQLITE3_TEXT);
                $stmt->bindValue(4, $template['description'], SQLITE3_TEXT);
                $stmt->execute();
                $newId = $db->lastInsertRowID();
                
                $stmt = $db->prepare("INSERT INTO schedule_windows (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes) SELECT ?, day_of_week, specific_date, start_time, end_time, access_type, notes FROM schedule_windows WHERE schedule_id = ?");
                $stmt->bindValue(1, $newId, SQLITE3_INTEGER);
                $stmt->bindValue(2, $templateId, SQLITE3_INTEGER);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'schedule_id' => $newId]);
            } else {
                $stmt = $db->prepare("INSERT INTO parental_schedules (user_id, device_id, schedule_name, description) VALUES (?, ?, ?, ?)");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['device_id'] ?? null, SQLITE3_INTEGER);
                $stmt->bindValue(3, $input['name'], SQLITE3_TEXT);
                $stmt->bindValue(4, $input['description'] ?? '', SQLITE3_TEXT);
                $stmt->execute();
                echo json_encode(['success' => true, 'schedule_id' => $db->lastInsertRowID()]);
            }
            break;
            
        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'ID required']); exit; }
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("UPDATE parental_schedules SET schedule_name = ?, description = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
            $stmt->bindValue(1, $input['name'], SQLITE3_TEXT);
            $stmt->bindValue(2, $input['description'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(3, $input['is_active'] ?? 1, SQLITE3_INTEGER);
            $stmt->bindValue(4, $id, SQLITE3_INTEGER);
            $stmt->bindValue(5, $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'ID required']); exit; }
            $stmt = $db->prepare("DELETE FROM parental_schedules WHERE id = ? AND user_id = ?");
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
