<?php
/**
 * TrueVault VPN - Admin Servers API
 * 
 * GET              - List all servers
 * GET ?id=X        - Get single server
 * POST             - Create server
 * PUT ?id=X        - Update server
 * DELETE ?id=X     - Delete server
 * 
 * @created January 2026
 */

define('TRUEVAULT_INIT', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

$serversDb = Database::getInstance('servers');
$devicesDb = Database::getInstance('devices');

$serverId = $_GET['id'] ?? null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($serverId) {
            // Get single server with details
            $server = $serversDb->queryOne("SELECT * FROM servers WHERE id = ?", [$serverId]);
            
            if (!$server) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Server not found']);
                exit;
            }
            
            // Get connected devices
            $connectedDevices = $devicesDb->queryAll(
                "SELECT d.*, u.email as user_email 
                 FROM devices d 
                 LEFT JOIN (SELECT id, email FROM users) u ON d.user_id = u.id 
                 WHERE d.current_server_id = ?",
                [$serverId]
            );
            
            // Get health history
            $healthHistory = $serversDb->queryAll(
                "SELECT * FROM server_health WHERE server_id = ? ORDER BY timestamp DESC LIMIT 24",
                [$serverId]
            );
            
            $server['connected_devices'] = $connectedDevices;
            $server['device_count'] = count($connectedDevices);
            $server['health_history'] = $healthHistory;
            
            echo json_encode(['success' => true, 'server' => $server]);
        } else {
            // List all servers
            $servers = $serversDb->queryAll("SELECT * FROM servers ORDER BY priority, id");
            
            // Add device counts
            foreach ($servers as &$server) {
                $server['device_count'] = $devicesDb->queryValue(
                    "SELECT COUNT(*) FROM devices WHERE current_server_id = ?",
                    [$server['id']]
                ) ?? 0;
            }
            
            echo json_encode(['success' => true, 'servers' => $servers]);
        }
        break;
        
    case 'POST':
        // Create new server
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'display_name', 'location', 'country', 'ip_address', 'public_key'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '{$field}' is required"]);
                exit;
            }
        }
        
        $newServerId = $serversDb->insert('servers', [
            'name' => $input['name'],
            'display_name' => $input['display_name'],
            'location' => $input['location'],
            'country' => $input['country'],
            'country_code' => $input['country_code'] ?? '',
            'ip_address' => $input['ip_address'],
            'port' => $input['port'] ?? 51820,
            'public_key' => $input['public_key'],
            'endpoint' => $input['endpoint'] ?? $input['ip_address'] . ':' . ($input['port'] ?? 51820),
            'dns' => $input['dns'] ?? '1.1.1.1, 8.8.8.8',
            'allowed_ips' => $input['allowed_ips'] ?? '0.0.0.0/0',
            'status' => $input['status'] ?? 'active',
            'is_vip_only' => $input['is_vip_only'] ?? 0,
            'dedicated_user_email' => $input['dedicated_user_email'] ?? null,
            'max_clients' => $input['max_clients'] ?? 100,
            'bandwidth_limit' => $input['bandwidth_limit'] ?? 0,
            'provider' => $input['provider'] ?? '',
            'monthly_cost' => $input['monthly_cost'] ?? 0,
            'priority' => $input['priority'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'server_id' => $newServerId,
            'message' => 'Server created successfully'
        ]);
        break;
        
    case 'PUT':
        // Update server
        if (!$serverId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Server ID required']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Fields that can be updated
        $allowedFields = [
            'name', 'display_name', 'location', 'country', 'country_code',
            'ip_address', 'port', 'public_key', 'endpoint', 'dns', 'allowed_ips',
            'status', 'is_vip_only', 'dedicated_user_email', 'max_clients',
            'bandwidth_limit', 'provider', 'monthly_cost', 'priority'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        $serversDb->update('servers', $updateData, 'id = ?', [$serverId]);
        
        echo json_encode(['success' => true, 'message' => 'Server updated successfully']);
        break;
        
    case 'DELETE':
        // Delete server
        if (!$serverId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Server ID required']);
            exit;
        }
        
        // Check if any devices are using this server
        $deviceCount = $devicesDb->queryValue(
            "SELECT COUNT(*) FROM devices WHERE current_server_id = ?",
            [$serverId]
        );
        
        if ($deviceCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => "Cannot delete server with {$deviceCount} connected devices. Migrate devices first."
            ]);
            exit;
        }
        
        // Delete health records
        $serversDb->query("DELETE FROM server_health WHERE server_id = ?", [$serverId]);
        
        // Delete server
        $serversDb->query("DELETE FROM servers WHERE id = ?", [$serverId]);
        
        echo json_encode(['success' => true, 'message' => 'Server deleted successfully']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
