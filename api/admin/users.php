<?php
/**
 * Admin Users API
 * TrueVault VPN - User Management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verify admin token
$user = verifyAdminToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDatabase('users');

try {
    switch ($method) {
        case 'GET':
            // List all users
            $stmt = $db->query("
                SELECT u.*, 
                       (SELECT COUNT(*) FROM devices d WHERE d.user_id = u.id) as device_count
                FROM users u 
                ORDER BY u.created_at DESC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Remove passwords from response
            foreach ($users as &$u) {
                unset($u['password']);
            }
            
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['email'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email required']);
                exit;
            }
            
            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Email already exists']);
                exit;
            }
            
            $password = !empty($input['password']) ? password_hash($input['password'], PASSWORD_DEFAULT) : password_hash('changeme123', PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (email, password, first_name, last_name, plan, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $input['email'],
                $password,
                $input['first_name'] ?? '',
                $input['last_name'] ?? '',
                $input['plan'] ?? 'basic',
                $input['status'] ?? 'active'
            ]);
            
            $newId = $db->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => 'User created', 'id' => $newId]);
            break;
            
        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            $updates = [];
            $params = [];
            
            if (isset($input['first_name'])) {
                $updates[] = "first_name = ?";
                $params[] = $input['first_name'];
            }
            if (isset($input['last_name'])) {
                $updates[] = "last_name = ?";
                $params[] = $input['last_name'];
            }
            if (isset($input['email'])) {
                $updates[] = "email = ?";
                $params[] = $input['email'];
            }
            if (isset($input['plan'])) {
                $updates[] = "plan = ?";
                $params[] = $input['plan'];
            }
            if (isset($input['status'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];
            }
            if (!empty($input['password'])) {
                $updates[] = "password = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            $updates[] = "updated_at = datetime('now')";
            $params[] = $input['id'];
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'User updated']);
            break;
            
        case 'DELETE':
            // Delete user
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            // Don't allow deleting VIP users
            $stmt = $db->prepare("SELECT is_vip, email FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userToDelete && $userToDelete['is_vip']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot delete VIP users']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'User deleted']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
