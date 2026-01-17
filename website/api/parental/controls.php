<?php
/**
 * TrueVault VPN - Parental Controls API
 * Handles schedules, rules, and blocking
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

$auth = new Auth();
$user = $auth->validateRequest();
if (!$user) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$db = new Database('main');

// Ensure tables
$db->exec("CREATE TABLE IF NOT EXISTS parental_schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER,
    name TEXT NOT NULL,
    days TEXT DEFAULT '1,2,3,4,5,6,7',
    start_time TEXT DEFAULT '00:00',
    end_time TEXT DEFAULT '23:59',
    action TEXT DEFAULT 'block',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS parental_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id INTEGER,
    rule_type TEXT NOT NULL,
    rule_value TEXT NOT NULL,
    action TEXT DEFAULT 'block',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_schedules':
        $stmt = $db->prepare("SELECT * FROM parental_schedules WHERE user_id = ? ORDER BY start_time");
        $stmt->execute([$user['id']]);
        echo json_encode(['success' => true, 'schedules' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'add_schedule':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("INSERT INTO parental_schedules (user_id, device_id, name, days, start_time, end_time, action) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $data['device_id'] ?? null, $data['name'], $data['days'], $data['start_time'], $data['end_time'], $data['action'] ?? 'block']);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        break;
        
    case 'delete_schedule':
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM parental_schedules WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);
        echo json_encode(['success' => true]);
        break;
        
    case 'get_rules':
        $stmt = $db->prepare("SELECT * FROM parental_rules WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        echo json_encode(['success' => true, 'rules' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'add_rule':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("INSERT INTO parental_rules (user_id, device_id, rule_type, rule_value, action) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $data['device_id'] ?? null, $data['rule_type'], $data['rule_value'], $data['action'] ?? 'block']);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        break;
        
    case 'delete_rule':
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM parental_rules WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user['id']]);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
