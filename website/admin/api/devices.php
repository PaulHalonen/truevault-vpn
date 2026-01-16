<?php
/**
 * TrueVault VPN - Admin Devices API
 * 
 * GET              - List all devices
 * GET ?id=X        - Get single device
 * DELETE ?id=X     - Delete device
 * PUT ?id=X        - Update device (status, server)
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
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
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            // Build query
            $where = [];
            $params = [];
            
            if ($userId) {
                $where[] = "d.user_id = ?";
                $params[] = $userId;
            }
            
            if ($search) {
                $where[] = "(d.device_name LIKE ? OR d.assigned_ip LIKE ?)";
                $searchTerm = "%{$search}%";
                $params = array_merge($params, [$searchTerm, $searchTerm]);
            }
            
            if ($status) {
                $where[] = "d.status = ?";
                $params[] = $status;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get total count
            $total = $devicesDb->queryValue(
                "SELECT COUNT(*) FROM devices d {$whereClause}",
                $params
            );
            
            // Get devices with user email
            $devices = $devicesDb->queryAll(
                "SELECT d.*, u.email as user_email, s.display_name as server_name
                 FROM devices d
                 LEFT JOIN (SELECT id, email FROM users) u ON d.user_id = u.id
                 LEFT JOIN (SELECT id, display_name FROM servers) s ON d.current_server_id = s.id
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
        
    case 'PUT':
        // Update device
        if (!$deviceId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID required']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Fields that can be updated
        if (isset($input['status'])) {
            $updateData['status'] = $input['status'];
        }
        if (isset($input['device_name'])) {
            $updateData['device_name'] = $input['device_name'];
        }
        if (isset($input['current_server_id'])) {
            $updateData['current_server_id'] = $input['current_server_id'];
        }
        
        $devicesDb->update('devices', $updateData, 'id = ?', [$deviceId]);
        
        echo json_encode(['success' => true, 'message' => 'Device updated']);
        break;
        
    case 'DELETE':
        // Delete device
        if (!$deviceId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID required']);
            exit;
        }
        
        // Get device to free IP
        $device = $devicesDb->queryOne("SELECT assigned_ip FROM devices WHERE id = ?", [$deviceId]);
        
        if ($device) {
            // Free the IP
            $devicesDb->update('ip_pool', 
                ['is_available' => 1, 'device_id' => null, 'assigned_at' => null],
                'ip_address = ?',
                [$device['assigned_ip']]
            );
            
            // Delete device configs
            $devicesDb->query("DELETE FROM device_configs WHERE device_id = (SELECT device_id FROM devices WHERE id = ?)", [$deviceId]);
            
            // Delete device
            $devicesDb->query("DELETE FROM devices WHERE id = ?", [$deviceId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Device deleted']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
