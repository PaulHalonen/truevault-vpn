<?php
/**
 * TrueVault VPN - Admin Devices API
 * 
 * GET              - List all devices
 * GET ?id=X        - Get single device
 * DELETE ?id=X     - Delete device
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Verify admin token
Auth::init(JWT_SECRET);

$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$payload = Auth::verifyToken($token);
if (!$payload || ($payload['type'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$devicesDb = Database::getInstance('devices');
$usersDb = Database::getInstance('users');
$serversDb = Database::getInstance('servers');

$deviceId = $_GET['id'] ?? null;
$userId = $_GET['user_id'] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($deviceId) {
            // Get single device
            $device = $devicesDb->queryOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
            
            if (!$device) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Device not found']);
                exit;
            }
            
            // Get user info
            $user = $usersDb->queryOne(
                "SELECT id, email, first_name, last_name FROM users WHERE id = ?",
                [$device['user_id']]
            );
            
            // Get server info
            $server = $serversDb->queryOne(
                "SELECT id, display_name, location FROM servers WHERE id = ?",
                [$device['current_server_id']]
            );
            
            $device['user'] = $user;
            $device['server'] = $server;
            
            echo json_encode(['success' => true, 'device' => $device]);
            
        } else {
            // List devices with pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($userId) {
                $where[] = "user_id = ?";
                $params[] = $userId;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $total = $devicesDb->queryValue(
                "SELECT COUNT(*) FROM devices {$whereClause}",
                $params
            );
            
            $devices = $devicesDb->queryAll(
                "SELECT d.*, 
                        (SELECT email FROM users WHERE id = d.user_id) as user_email,
                        (SELECT display_name FROM servers WHERE id = d.current_server_id) as server_name
                 FROM devices d
                 {$whereClause}
                 ORDER BY d.created_at DESC
                 LIMIT {$limit} OFFSET {$offset}",
                $params
            );
            
            echo json_encode([
                'success' => true,
                'devices' => $devices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'DELETE':
        if (!$deviceId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID required']);
            exit;
        }
        
        // Get device to release IP
        $device = $devicesDb->queryOne("SELECT assigned_ip FROM devices WHERE id = ?", [$deviceId]);
        
        if (!$device) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Device not found']);
            exit;
        }
        
        // Release IP back to pool
        $devicesDb->query(
            "UPDATE ip_pool SET is_available = 1, device_id = NULL, assigned_at = NULL WHERE ip_address = ?",
            [$device['assigned_ip']]
        );
        
        // Delete device configs
        $devicesDb->query("DELETE FROM device_configs WHERE device_id = ?", [$deviceId]);
        
        // Delete device
        $devicesDb->query("DELETE FROM devices WHERE id = ?", [$deviceId]);
        
        echo json_encode(['success' => true, 'message' => 'Device deleted']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
