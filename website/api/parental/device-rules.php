<?php
/**
 * TrueVault VPN - Device Rules API
 * Part 11 - Task 11.4
 * Per-device parental rule management
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
$action = $_GET['action'] ?? 'list';
$deviceId = isset($_GET['device_id']) ? intval($_GET['device_id']) : null;

try {
    $db = getDatabase();
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all devices with their rules
                $stmt = $db->prepare("
                    SELECT 
                        d.id, d.device_name, d.device_type, d.last_seen,
                        dr.schedule_id, dr.override_enabled, dr.override_type, dr.override_until,
                        ps.schedule_name,
                        gr.gaming_enabled, gr.daily_limit_minutes, gr.minutes_used_today
                    FROM devices d
                    LEFT JOIN device_rules dr ON d.id = dr.device_id AND dr.user_id = ?
                    LEFT JOIN parental_schedules ps ON dr.schedule_id = ps.id
                    LEFT JOIN gaming_restrictions gr ON d.id = gr.device_id AND gr.user_id = ?
                    WHERE d.user_id = ?
                    ORDER BY d.device_name
                ");
                $stmt->execute([$user['id'], $user['id'], $user['id']]);
                echo json_encode(['success' => true, 'devices' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                
            } elseif ($action === 'get' && $deviceId) {
                // Get single device with full rules
                $stmt = $db->prepare("
                    SELECT 
                        d.*,
                        dr.schedule_id, dr.override_enabled, dr.override_type, dr.override_until, dr.notes as rule_notes,
                        ps.schedule_name, ps.description as schedule_description,
                        gr.gaming_enabled, gr.xbox_enabled, gr.playstation_enabled, gr.steam_enabled, gr.nintendo_enabled,
                        gr.daily_limit_minutes, gr.minutes_used_today
                    FROM devices d
                    LEFT JOIN device_rules dr ON d.id = dr.device_id AND dr.user_id = ?
                    LEFT JOIN parental_schedules ps ON dr.schedule_id = ps.id
                    LEFT JOIN gaming_restrictions gr ON d.id = gr.device_id AND gr.user_id = ?
                    WHERE d.id = ? AND d.user_id = ?
                ");
                $stmt->execute([$user['id'], $user['id'], $deviceId, $user['id']]);
                $device = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$device) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Device not found']);
                    exit;
                }
                
                // Get available schedules
                $schedules = $db->prepare("SELECT id, schedule_name FROM parental_schedules WHERE user_id = ? AND is_template = 0 ORDER BY schedule_name");
                $schedules->execute([$user['id']]);
                $device['available_schedules'] = $schedules->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'device' => $device]);
                
            } elseif ($action === 'groups') {
                // Get device groups
                $stmt = $db->prepare("
                    SELECT dg.*, 
                           (SELECT COUNT(*) FROM device_group_members WHERE group_id = dg.id) as member_count
                    FROM device_groups dg
                    WHERE dg.user_id = ?
                    ORDER BY dg.group_name
                ");
                $stmt->execute([$user['id']]);
                echo json_encode(['success' => true, 'groups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'assign_schedule') {
                // Assign schedule to device
                $devId = $input['device_id'];
                $scheduleId = $input['schedule_id'] ?: null;
                
                $stmt = $db->prepare("
                    INSERT INTO device_rules (user_id, device_id, schedule_id)
                    VALUES (?, ?, ?)
                    ON CONFLICT(user_id, device_id) DO UPDATE SET schedule_id = ?, updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$user['id'], $devId, $scheduleId, $scheduleId]);
                echo json_encode(['success' => true, 'message' => 'Schedule assigned']);
                
            } elseif ($action === 'create_group') {
                // Create device group
                $stmt = $db->prepare("INSERT INTO device_groups (user_id, group_name, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $input['name'], $input['description'] ?? '', $input['icon'] ?? '📱']);
                echo json_encode(['success' => true, 'group_id' => $db->lastInsertId()]);
                
            } elseif ($action === 'add_to_group') {
                // Add device to group
                $stmt = $db->prepare("INSERT OR IGNORE INTO device_group_members (group_id, device_id) VALUES (?, ?)");
                $stmt->execute([$input['group_id'], $input['device_id']]);
                echo json_encode(['success' => true]);
                
            } elseif ($action === 'apply_to_all') {
                // Apply schedule to all devices
                $scheduleId = $input['schedule_id'];
                $devices = $db->prepare("SELECT id FROM devices WHERE user_id = ?");
                $devices->execute([$user['id']]);
                
                $stmt = $db->prepare("
                    INSERT INTO device_rules (user_id, device_id, schedule_id)
                    VALUES (?, ?, ?)
                    ON CONFLICT(user_id, device_id) DO UPDATE SET schedule_id = ?, updated_at = CURRENT_TIMESTAMP
                ");
                
                $count = 0;
                while ($dev = $devices->fetch()) {
                    $stmt->execute([$user['id'], $dev['id'], $scheduleId, $scheduleId]);
                    $count++;
                }
                echo json_encode(['success' => true, 'devices_updated' => $count]);
                
            } elseif ($action === 'set_gaming') {
                // Set gaming restrictions for device
                $devId = $input['device_id'];
                $stmt = $db->prepare("
                    INSERT INTO gaming_restrictions (user_id, device_id, gaming_enabled, xbox_enabled, playstation_enabled, steam_enabled, nintendo_enabled, daily_limit_minutes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT(user_id, device_id) DO UPDATE SET 
                        gaming_enabled = ?, xbox_enabled = ?, playstation_enabled = ?, steam_enabled = ?, nintendo_enabled = ?, daily_limit_minutes = ?, last_toggled_at = CURRENT_TIMESTAMP
                ");
                $params = [
                    $user['id'], $devId,
                    $input['gaming_enabled'] ?? 1, $input['xbox_enabled'] ?? 1, $input['playstation_enabled'] ?? 1,
                    $input['steam_enabled'] ?? 1, $input['nintendo_enabled'] ?? 1, $input['daily_limit_minutes'] ?? null,
                    // Duplicate for UPDATE
                    $input['gaming_enabled'] ?? 1, $input['xbox_enabled'] ?? 1, $input['playstation_enabled'] ?? 1,
                    $input['steam_enabled'] ?? 1, $input['nintendo_enabled'] ?? 1, $input['daily_limit_minutes'] ?? null
                ];
                $stmt->execute($params);
                echo json_encode(['success' => true]);
            }
            break;
            
        case 'DELETE':
            if ($action === 'remove_from_group') {
                $stmt = $db->prepare("DELETE FROM device_group_members WHERE group_id = ? AND device_id = ?");
                $stmt->execute([$_GET['group_id'], $_GET['device_id']]);
                echo json_encode(['success' => true]);
                
            } elseif ($action === 'delete_group' && isset($_GET['group_id'])) {
                $stmt = $db->prepare("DELETE FROM device_groups WHERE id = ? AND user_id = ?");
                $stmt->execute([$_GET['group_id'], $user['id']]);
                echo json_encode(['success' => true]);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
