<?php
/**
 * TrueVault VPN - Device Rules API
 * Part 11 - Task 11.4
 * Per-device parental rule management
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
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

try {
    $db = new SQLite3(DB_USERS);
    $db->enableExceptions(true);
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $stmt = $db->prepare("SELECT d.id, d.device_name, d.device_type, d.last_seen, dr.schedule_id, dr.override_enabled, dr.override_type, dr.override_until, ps.schedule_name, gr.gaming_enabled, gr.daily_limit_minutes, gr.minutes_used_today FROM devices d LEFT JOIN device_rules dr ON d.id = dr.device_id AND dr.user_id = ? LEFT JOIN parental_schedules ps ON dr.schedule_id = ps.id LEFT JOIN gaming_restrictions gr ON d.id = gr.device_id AND gr.user_id = ? WHERE d.user_id = ? ORDER BY d.device_name");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(3, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'devices' => fetchAllAssoc($result)]);
                
            } elseif ($action === 'get' && $deviceId) {
                $stmt = $db->prepare("SELECT d.*, dr.schedule_id, dr.override_enabled, dr.override_type, dr.override_until, dr.notes as rule_notes, ps.schedule_name, ps.description as schedule_description, gr.gaming_enabled, gr.xbox_enabled, gr.playstation_enabled, gr.steam_enabled, gr.nintendo_enabled, gr.daily_limit_minutes, gr.minutes_used_today FROM devices d LEFT JOIN device_rules dr ON d.id = dr.device_id AND dr.user_id = ? LEFT JOIN parental_schedules ps ON dr.schedule_id = ps.id LEFT JOIN gaming_restrictions gr ON d.id = gr.device_id AND gr.user_id = ? WHERE d.id = ? AND d.user_id = ?");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(3, $deviceId, SQLITE3_INTEGER);
                $stmt->bindValue(4, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                $device = $result->fetchArray(SQLITE3_ASSOC);
                
                if (!$device) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Device not found']);
                    exit;
                }
                
                $schedStmt = $db->prepare("SELECT id, schedule_name FROM parental_schedules WHERE user_id = ? AND is_template = 0 ORDER BY schedule_name");
                $schedStmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $schedResult = $schedStmt->execute();
                $device['available_schedules'] = fetchAllAssoc($schedResult);
                
                echo json_encode(['success' => true, 'device' => $device]);
                
            } elseif ($action === 'groups') {
                $stmt = $db->prepare("SELECT dg.*, (SELECT COUNT(*) FROM device_group_members WHERE group_id = dg.id) as member_count FROM device_groups dg WHERE dg.user_id = ? ORDER BY dg.group_name");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
                echo json_encode(['success' => true, 'groups' => fetchAllAssoc($result)]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'assign_schedule') {
                $devId = $input['device_id'];
                $scheduleId = $input['schedule_id'] ?: null;
                
                $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, schedule_id) VALUES (?, ?, ?) ON CONFLICT(user_id, device_id) DO UPDATE SET schedule_id = ?, updated_at = CURRENT_TIMESTAMP");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $devId, SQLITE3_INTEGER);
                $stmt->bindValue(3, $scheduleId, SQLITE3_INTEGER);
                $stmt->bindValue(4, $scheduleId, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Schedule assigned']);
                
            } elseif ($action === 'create_group') {
                $stmt = $db->prepare("INSERT INTO device_groups (user_id, group_name, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['name'], SQLITE3_TEXT);
                $stmt->bindValue(3, $input['description'] ?? '', SQLITE3_TEXT);
                $stmt->bindValue(4, $input['icon'] ?? '📱', SQLITE3_TEXT);
                $stmt->execute();
                echo json_encode(['success' => true, 'group_id' => $db->lastInsertRowID()]);
                
            } elseif ($action === 'add_to_group') {
                $stmt = $db->prepare("INSERT OR IGNORE INTO device_group_members (group_id, device_id) VALUES (?, ?)");
                $stmt->bindValue(1, $input['group_id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $input['device_id'], SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
                
            } elseif ($action === 'apply_to_all') {
                $scheduleId = $input['schedule_id'];
                $devStmt = $db->prepare("SELECT id FROM devices WHERE user_id = ?");
                $devStmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $devices = $devStmt->execute();
                
                $count = 0;
                while ($dev = $devices->fetchArray(SQLITE3_ASSOC)) {
                    $stmt = $db->prepare("INSERT INTO device_rules (user_id, device_id, schedule_id) VALUES (?, ?, ?) ON CONFLICT(user_id, device_id) DO UPDATE SET schedule_id = ?, updated_at = CURRENT_TIMESTAMP");
                    $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                    $stmt->bindValue(2, $dev['id'], SQLITE3_INTEGER);
                    $stmt->bindValue(3, $scheduleId, SQLITE3_INTEGER);
                    $stmt->bindValue(4, $scheduleId, SQLITE3_INTEGER);
                    $stmt->execute();
                    $count++;
                }
                echo json_encode(['success' => true, 'devices_updated' => $count]);
                
            } elseif ($action === 'set_gaming') {
                $devId = $input['device_id'];
                $stmt = $db->prepare("INSERT INTO gaming_restrictions (user_id, device_id, gaming_enabled, xbox_enabled, playstation_enabled, steam_enabled, nintendo_enabled, daily_limit_minutes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id, device_id) DO UPDATE SET gaming_enabled = ?, xbox_enabled = ?, playstation_enabled = ?, steam_enabled = ?, nintendo_enabled = ?, daily_limit_minutes = ?, last_toggled_at = CURRENT_TIMESTAMP");
                $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $devId, SQLITE3_INTEGER);
                $stmt->bindValue(3, $input['gaming_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(4, $input['xbox_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(5, $input['playstation_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(6, $input['steam_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(7, $input['nintendo_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(8, $input['daily_limit_minutes'] ?? null, SQLITE3_INTEGER);
                $stmt->bindValue(9, $input['gaming_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(10, $input['xbox_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(11, $input['playstation_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(12, $input['steam_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(13, $input['nintendo_enabled'] ?? 1, SQLITE3_INTEGER);
                $stmt->bindValue(14, $input['daily_limit_minutes'] ?? null, SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'DELETE':
            if ($action === 'remove_from_group') {
                $stmt = $db->prepare("DELETE FROM device_group_members WHERE group_id = ? AND device_id = ?");
                $stmt->bindValue(1, $_GET['group_id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $_GET['device_id'], SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
                
            } elseif ($action === 'delete_group' && isset($_GET['group_id'])) {
                $stmt = $db->prepare("DELETE FROM device_groups WHERE id = ? AND user_id = ?");
                $stmt->bindValue(1, $_GET['group_id'], SQLITE3_INTEGER);
                $stmt->bindValue(2, $user['id'], SQLITE3_INTEGER);
                $stmt->execute();
                echo json_encode(['success' => true]);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
