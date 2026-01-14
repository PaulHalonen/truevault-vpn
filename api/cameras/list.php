<?php
/**
 * Cameras API
 * TrueVault VPN - IP Camera Management
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

// Verify user token
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = getDatabase('cameras');

// Ensure cameras table exists
$db->exec("CREATE TABLE IF NOT EXISTS cameras (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    local_ip TEXT NOT NULL,
    port INTEGER DEFAULT 80,
    brand TEXT,
    model TEXT,
    rtsp_path TEXT,
    username TEXT,
    password TEXT,
    status TEXT DEFAULT 'offline',
    last_seen TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

try {
    switch ($method) {
        case 'GET':
            // List user's cameras
            $stmt = $db->prepare("SELECT id, user_id, name, local_ip, port, brand, model, status, last_seen, created_at FROM cameras WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user['id']]);
            $cameras = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $cameras]);
            break;
            
        case 'POST':
            // Add new camera
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['name']) || empty($input['local_ip'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Camera name and IP required']);
                exit;
            }
            
            $stmt = $db->prepare("
                INSERT INTO cameras (user_id, name, local_ip, port, brand, model, rtsp_path, username, password, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->execute([
                $user['id'],
                $input['name'],
                $input['local_ip'],
                $input['port'] ?? 80,
                $input['brand'] ?? null,
                $input['model'] ?? null,
                $input['rtsp_path'] ?? null,
                $input['username'] ?? null,
                $input['password'] ?? null
            ]);
            
            $newId = $db->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => 'Camera added', 'id' => $newId]);
            break;
            
        case 'PUT':
            // Update camera
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Camera ID required']);
                exit;
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM cameras WHERE id = ? AND user_id = ?");
            $stmt->execute([$input['id'], $user['id']]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Camera not found or access denied']);
                exit;
            }
            
            $stmt = $db->prepare("
                UPDATE cameras SET name = ?, local_ip = ?, port = ?, brand = ?, updated_at = datetime('now')
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $input['name'],
                $input['local_ip'],
                $input['port'] ?? 80,
                $input['brand'] ?? null,
                $input['id'],
                $user['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Camera updated']);
            break;
            
        case 'DELETE':
            // Remove camera
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Camera ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM cameras WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Camera removed']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
