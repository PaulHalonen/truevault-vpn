<?php
/**
 * TrueVault VPN - Add Camera API
 * Register a new IP camera
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['name']) || empty($input['ip'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and IP address are required']);
    exit;
}

try {
    $db = getDatabase('cameras');
    
    // Add camera
    $stmt = $db->prepare("INSERT INTO cameras (user_id, name, ip_address, port, brand, status, created_at) VALUES (?, ?, ?, ?, ?, 'offline', datetime('now'))");
    $stmt->execute([
        $user['id'],
        $input['name'],
        $input['ip'],
        $input['port'] ?? 80,
        $input['brand'] ?? 'Unknown'
    ]);
    
    $cameraId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'camera' => [
            'id' => $cameraId,
            'name' => $input['name'],
            'ip_address' => $input['ip'],
            'port' => $input['port'] ?? 80,
            'brand' => $input['brand'] ?? 'Unknown',
            'status' => 'offline'
        ],
        'message' => 'Camera added successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
