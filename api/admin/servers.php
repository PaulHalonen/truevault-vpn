<?php
/**
 * TrueVault VPN - Admin Servers API
 * Manages VPN server configurations and monitoring
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require admin authentication
$admin = Auth::requireAdmin();
if (!$admin) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getConnection('vpn');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all servers with stats
                $stmt = $db->query("SELECT * FROM vpn_servers ORDER BY id");
                $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add connection counts
                foreach ($servers as &$server) {
                    $connStmt = $db->prepare("
                        SELECT COUNT(*) FROM vpn_connections 
                        WHERE server_id = ? AND status = 'active'
                    ");
                    $connStmt->execute([$server['id']]);
                    $server['active_connections'] = $connStmt->fetchColumn();
                }
                
                // Get overall stats
                $totalConnections = $db->query("SELECT COUNT(*) FROM vpn_connections WHERE status = 'active'")->fetchColumn();
                $totalBandwidth = $db->query("SELECT SUM(data_transfer) FROM vpn_connections WHERE status = 'active'")->fetchColumn();
                
                Response::json([
                    'success' => true,
                    'servers' => $servers,
                    'stats' => [
                        'total_servers' => count($servers),
                        'online_servers' => count(array_filter($servers, fn($s) => $s['status'] === 'online')),
                        'total_connections' => (int)$totalConnections,
                        'total_bandwidth' => (float)$totalBandwidth
                    ]
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single server with detailed stats
                $stmt = $db->prepare("SELECT * FROM vpn_servers WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $server = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$server) {
                    Response::error('Server not found', 404);
                }
                
                // Get recent connections
                $connStmt = $db->prepare("
                    SELECT vc.*, u.email, u.first_name, u.last_name
                    FROM vpn_connections vc
                    JOIN users u ON vc.user_id = u.id
                    WHERE vc.server_id = ?
                    ORDER BY vc.connected_at DESC
                    LIMIT 50
                ");
                $connStmt->execute([$server['id']]);
                $connections = $connStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get hourly stats for last 24 hours
                $statsStmt = $db->prepare("
                    SELECT 
                        strftime('%Y-%m-%d %H:00', connected_at) as hour,
                        COUNT(*) as connections,
                        SUM(data_transfer) as bandwidth
                    FROM vpn_connections
                    WHERE server_id = ? AND connected_at >= datetime('now', '-24 hours')
                    GROUP BY hour
                    ORDER BY hour
                ");
                $statsStmt->execute([$server['id']]);
                $hourlyStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                Response::json([
                    'success' => true,
                    'server' => $server,
                    'connections' => $connections,
                    'hourly_stats' => $hourlyStats
                ]);
                
            } elseif ($action === 'health') {
                // Get server health status (simulated)
                $servers = [
                    ['id' => 1, 'name' => 'US-East', 'cpu' => rand(20, 60), 'memory' => rand(30, 70), 'network' => rand(10, 50)],
                    ['id' => 2, 'name' => 'US-Central VIP', 'cpu' => rand(5, 20), 'memory' => rand(10, 30), 'network' => rand(5, 15)],
                    ['id' => 3, 'name' => 'Dallas', 'cpu' => rand(25, 55), 'memory' => rand(35, 65), 'network' => rand(15, 45)],
                    ['id' => 4, 'name' => 'Canada', 'cpu' => rand(30, 60), 'memory' => rand(40, 70), 'network' => rand(20, 50)]
                ];
                
                Response::json(['success' => true, 'health' => $servers]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // Create new server
                $required = ['name', 'ip_address', 'location', 'region'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        Response::error("$field is required", 400);
                    }
                }
                
                $stmt = $db->prepare("
                    INSERT INTO vpn_servers 
                    (name, ip_address, location, region, port, max_connections, provider, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'offline', datetime('now'))
                ");
                $stmt->execute([
                    $data['name'],
                    $data['ip_address'],
                    $data['location'],
                    $data['region'],
                    $data['port'] ?? 51820,
                    $data['max_connections'] ?? 50,
                    $data['provider'] ?? 'unknown'
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Server created',
                    'server_id' => $db->lastInsertId()
                ]);
                
            } elseif ($action === 'restart' && isset($data['id'])) {
                // Restart server (simulated - log the action)
                $logDb = Database::getConnection('logs');
                $stmt = $logDb->prepare("
                    INSERT INTO system_log (level, category, message, details, created_at)
                    VALUES ('info', 'server', 'Server restart initiated', ?, datetime('now'))
                ");
                $stmt->execute([json_encode(['server_id' => $data['id'], 'admin_id' => $admin['id']])]);
                
                Response::json(['success' => true, 'message' => 'Server restart initiated']);
                
            } elseif ($action === 'toggle-status' && isset($data['id'])) {
                // Toggle server online/offline
                $stmt = $db->prepare("SELECT status FROM vpn_servers WHERE id = ?");
                $stmt->execute([$data['id']]);
                $current = $stmt->fetchColumn();
                
                $newStatus = ($current === 'online') ? 'offline' : 'online';
                
                $stmt = $db->prepare("UPDATE vpn_servers SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $data['id']]);
                
                Response::json(['success' => true, 'new_status' => $newStatus]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                Response::error('Server ID required', 400);
            }
            
            // Update server configuration
            $fields = [];
            $values = [];
            
            $allowedFields = ['name', 'ip_address', 'location', 'region', 'port', 'max_connections', 'status', 'is_vip'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                Response::error('No fields to update', 400);
            }
            
            $values[] = $data['id'];
            $stmt = $db->prepare("UPDATE vpn_servers SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($values);
            
            Response::json(['success' => true, 'message' => 'Server updated']);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                Response::error('Server ID required', 400);
            }
            
            // Check for active connections
            $stmt = $db->prepare("SELECT COUNT(*) FROM vpn_connections WHERE server_id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $activeConns = $stmt->fetchColumn();
            
            if ($activeConns > 0) {
                Response::error("Cannot delete server with $activeConns active connections", 400);
            }
            
            $stmt = $db->prepare("DELETE FROM vpn_servers WHERE id = ?");
            $stmt->execute([$id]);
            
            Response::json(['success' => true, 'message' => 'Server deleted']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Admin Servers API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
