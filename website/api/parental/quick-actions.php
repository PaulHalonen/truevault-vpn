<?php
/**
 * TrueVault VPN - Quick Actions API
 * Part 11 - Task 11.7
 * Parent quick action buttons
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

$action = $_GET['action'] ?? '';
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    switch ($action) {
        case 'block_gaming':
            $stmt = $db->prepare("INSERT INTO gaming_restrictions (user_id, device_id, gaming_enabled, last_toggled_at, toggled_by) VALUES (?, ?, 0, CURRENT_TIMESTAMP, 'parent') ON CONFLICT(user_id, device_id) DO UPDATE SET gaming_enabled = 0, last_toggled_at = CURRENT_TIMESTAMP");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            logAction($db, $user['id'], 'quick_action', 'block_gaming', $deviceId);
            echo json_encode(['success' => true, 'action' => 'block_gaming', 'message' => 'Gaming blocked']);
            break;
            
        case 'homework_mode':
            $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, override_enabled, override_type, override_until) VALUES (?, ?, 1, 'homework_mode', datetime('now', '+4 hours')) ON CONFLICT(user_id, device_id) DO UPDATE SET override_enabled = 1, override_type = 'homework_mode', override_until = datetime('now', '+4 hours')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            logAction($db, $user['id'], 'quick_action', 'homework_mode', $deviceId);
            echo json_encode(['success' => true, 'action' => 'homework_mode', 'until' => date('Y-m-d H:i:s', strtotime('+4 hours'))]);
            break;
            
        case 'extend_time':
            $input = json_decode(file_get_contents('php://input'), true);
            $minutes = intval($input['minutes'] ?? 60);
            $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, override_enabled, override_type, override_until) VALUES (?, ?, 1, 'extended_time', datetime('now', '+' || ? || ' minutes')) ON CONFLICT(user_id, device_id) DO UPDATE SET override_enabled = 1, override_type = 'extended_time', override_until = datetime('now', '+' || ? || ' minutes')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->bindValue(3, $minutes, SQLITE3_INTEGER);
            $stmt->bindValue(4, $minutes, SQLITE3_INTEGER);
            $stmt->execute();
            logAction($db, $user['id'], 'quick_action', 'extend_time', $deviceId, ['minutes' => $minutes]);
            echo json_encode(['success' => true, 'action' => 'extend_time', 'minutes' => $minutes]);
            break;
            
        case 'emergency_block':
            $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, override_enabled, override_type, override_until) VALUES (?, ?, 1, 'emergency_block', datetime('now', '+24 hours')) ON CONFLICT(user_id, device_id) DO UPDATE SET override_enabled = 1, override_type = 'emergency_block', override_until = datetime('now', '+24 hours')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $stmt = $db->prepare("UPDATE gaming_restrictions SET gaming_enabled = 0 WHERE user_id = ? AND (device_id = ? OR ? IS NULL)");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            
            logAction($db, $user['id'], 'quick_action', 'emergency_block', $deviceId);
            echo json_encode(['success' => true, 'action' => 'emergency_block', 'message' => 'All access blocked for 24 hours']);
            break;
            
        case 'restore_normal':
            $stmt = $db->prepare("UPDATE device_rules SET override_enabled = 0, override_type = NULL, override_until = NULL WHERE user_id = ? AND (device_id = ? OR ? IS NULL)");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $stmt = $db->prepare("UPDATE gaming_restrictions SET gaming_enabled = 1 WHERE user_id = ? AND (device_id = ? OR ? IS NULL)");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            
            logAction($db, $user['id'], 'quick_action', 'restore_normal', $deviceId);
            echo json_encode(['success' => true, 'action' => 'restore_normal', 'message' => 'Normal schedule restored']);
            break;
            
        case 'bedtime_now':
            $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, override_enabled, override_type, override_until) VALUES (?, ?, 1, 'bedtime', datetime('now', '+12 hours')) ON CONFLICT(user_id, device_id) DO UPDATE SET override_enabled = 1, override_type = 'bedtime', override_until = datetime('now', '+12 hours')");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $deviceId, SQLITE3_INTEGER);
            $stmt->execute();
            logAction($db, $user['id'], 'quick_action', 'bedtime_now', $deviceId);
            echo json_encode(['success' => true, 'action' => 'bedtime_now', 'message' => 'Bedtime mode activated']);
            break;
            
        case 'status':
            $stmt = $db->prepare("SELECT dr.*, d.device_name, gr.gaming_enabled FROM device_rules dr LEFT JOIN devices d ON dr.device_id = d.id LEFT JOIN gaming_restrictions gr ON dr.user_id = gr.user_id AND (dr.device_id = gr.device_id OR (dr.device_id IS NULL AND gr.device_id IS NULL)) WHERE dr.user_id = ? AND dr.override_enabled = 1");
            $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            echo json_encode(['success' => true, 'active_overrides' => fetchAllAssoc($result)]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action', 'available_actions' => [
                'block_gaming', 'homework_mode', 'extend_time', 'emergency_block', 'restore_normal', 'bedtime_now', 'status'
            ]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function logAction($db, $userId, $type, $action, $targetId = null, $details = null) {
    try {
        $stmt = $db->prepare("INSERT INTO parental_activity_log (user_id, action_type, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $type, SQLITE3_TEXT);
        $stmt->bindValue(3, $action, SQLITE3_TEXT);
        $stmt->bindValue(4, $targetId, SQLITE3_INTEGER);
        $stmt->bindValue(5, $details ? json_encode($details) : null, SQLITE3_TEXT);
        $stmt->execute();
    } catch (Exception $e) {}
}
?>
