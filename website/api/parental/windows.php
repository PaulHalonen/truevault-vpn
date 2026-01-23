<?php
/**
 * TrueVault VPN - Schedule Windows API
 * Part 11 - Task 11.2
 * CRUD for schedule time windows
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/database.php';

$user = authenticateRequest();
if (!$user) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$scheduleId = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    $db = getDatabase();
    
    // Verify schedule ownership
    if ($scheduleId) {
        $stmt = $db->prepare("SELECT id FROM parental_schedules WHERE id = ? AND (user_id = ? OR user_id = 0)");
        $stmt->execute([$scheduleId, $user['id']]);
        if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['success' => false, 'error' => 'Access denied']); exit; }
    }
    
    switch ($method) {
        case 'GET':
            if ($scheduleId) {
                $stmt = $db->prepare("SELECT * FROM schedule_windows WHERE schedule_id = ? ORDER BY day_of_week, start_time");
                $stmt->execute([$scheduleId]);
                echo json_encode(['success' => true, 'windows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } elseif ($id) {
                $stmt = $db->prepare("SELECT * FROM schedule_windows WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'window' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $schId = $input['schedule_id'] ?? $scheduleId;
            
            // Check for overlapping windows
            $stmt = $db->prepare("
                SELECT id FROM schedule_windows 
                WHERE schedule_id = ? AND day_of_week = ? 
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))
            ");
            $stmt->execute([$schId, $input['day_of_week'], $input['start_time'], $input['start_time'], $input['end_time'], $input['end_time'], $input['start_time'], $input['end_time']]);
            if ($stmt->fetch()) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Time windows overlap']); exit; }
            
            $stmt = $db->prepare("INSERT INTO schedule_windows (schedule_id, day_of_week, specific_date, start_time, end_time, access_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$schId, $input['day_of_week'] ?? null, $input['specific_date'] ?? null, $input['start_time'], $input['end_time'], $input['access_type'], $input['notes'] ?? null]);
            echo json_encode(['success' => true, 'window_id' => $db->lastInsertId()]);
            break;
            
        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Window ID required']); exit; }
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("UPDATE schedule_windows SET day_of_week = ?, specific_date = ?, start_time = ?, end_time = ?, access_type = ?, notes = ? WHERE id = ?");
            $stmt->execute([$input['day_of_week'] ?? null, $input['specific_date'] ?? null, $input['start_time'], $input['end_time'], $input['access_type'], $input['notes'] ?? null, $id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Window ID required']); exit; }
            $stmt = $db->prepare("DELETE FROM schedule_windows WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
