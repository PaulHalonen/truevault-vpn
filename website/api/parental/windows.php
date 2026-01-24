<?php
/**
 * TrueVault VPN - Schedule Windows API
 * Part 11 - Task 11.2
 * CRUD for schedule time windows
 * USES SQLite3 CLASS (NOT PDO!) per Master Checklist
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$scheduleId = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

function fetchAllAssoc($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { $rows[] = $row; }
    return $rows;
}

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    // Verify schedule ownership
    if ($scheduleId) {
        $stmt = $db->prepare("SELECT id FROM parental_schedules WHERE id = ? AND (user_id = ? OR user_id = 0)");
        $stmt->bindValue(1, $scheduleId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        if (!$result->fetchArray()) { http_response_code(403); echo json_encode(['success' => false, 'error' => 'Access denied']); exit; }
    }
    
    switch ($method) {
        case 'GET':
            if ($scheduleId) {
                $stmt = $db->prepare("SELECT * FROM schedule_windows WHERE schedule_id = ? ORDER BY day_of_week, start_time");
                $stmt->bindValue(1, $scheduleId, SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'windows' => fetchAllAssoc($result)]);
            } elseif ($id) {
                $stmt = $db->prepare("SELECT * FROM schedule_windows WHERE id = ?");
                $stmt->bindValue(1, $id, SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'window' => $result->fetchArray(SQLITE3_ASSOC)]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $schId = $input['schedule_id'] ?? $scheduleId;
            
            // Check for overlapping windows
            $stmt = $db->prepare("SELECT id FROM schedule_windows WHERE schedule_id = ? AND day_of_week = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
            $stmt->bindValue(1, $schId, SQLITE3_INTEGER);
            $stmt->bindValue(2, $input['day_of_week'], SQLITE3_INTEGER);
            $stmt->bindValue(3, $input['start_time'], SQLITE3_TEXT);
            $stmt->bindValue(4, $input['start_time'], SQLITE3_TEXT);
            $stmt->bindValue(5, $input['end_time'], SQLITE3_TEXT);
            $stmt->bindValue(6, $input['end_time'], SQLITE3_TEXT);
            $stmt->bindValue(7, $input['start_time'], SQLITE3_TEXT);
            $stmt->bindValue(8, $input['end_time'], SQLITE3_TEXT);
            $result = $stmt->execute();
            if ($result->fetchArray()) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Time windows overlap']); exit; }
            
            $stmt = $db->prepare("INSERT INTO schedule_windows (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $schId, SQLITE3_INTEGER);
            $stmt->bindValue(2, $input['day_of_week'] ?? null, SQLITE3_INTEGER);
            $stmt->bindValue(3, $input['specific_date'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(4, $input['start_time'], SQLITE3_TEXT);
            $stmt->bindValue(5, $input['end_time'], SQLITE3_TEXT);
            $stmt->bindValue(6, $input['access_type'], SQLITE3_TEXT);
            $stmt->bindValue(7, $input['notes'] ?? null, SQLITE3_TEXT);
            $stmt->execute();
            echo json_encode(['success' => true, 'window_id' => $db->lastInsertRowID()]);
            break;
            
        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Window ID required']); exit; }
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("UPDATE schedule_windows SET day_of_week = ?, specific_date = ?, start_time = ?, end_time = ?, access_type = ?, notes = ? WHERE id = ?");
            $stmt->bindValue(1, $input['day_of_week'] ?? null, SQLITE3_INTEGER);
            $stmt->bindValue(2, $input['specific_date'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(3, $input['start_time'], SQLITE3_TEXT);
            $stmt->bindValue(4, $input['end_time'], SQLITE3_TEXT);
            $stmt->bindValue(5, $input['access_type'], SQLITE3_TEXT);
            $stmt->bindValue(6, $input['notes'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(7, $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Window ID required']); exit; }
            $stmt = $db->prepare("DELETE FROM schedule_windows WHERE id = ?");
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $stmt->execute();
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
