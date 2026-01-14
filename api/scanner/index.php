<?php
/**
 * Scanner API
 * TrueVault VPN - Network Scanner Integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
$action = $_GET['action'] ?? 'token';
$db = getDatabase('scanner');

// Ensure scanner tables exist
$db->exec("CREATE TABLE IF NOT EXISTS scanner_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    token TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    expires_at TEXT
)");

$db->exec("CREATE TABLE IF NOT EXISTS scanned_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ip TEXT NOT NULL,
    mac TEXT,
    hostname TEXT,
    vendor TEXT,
    device_type TEXT,
    open_ports TEXT,
    last_scanned TEXT DEFAULT CURRENT_TIMESTAMP,
    synced INTEGER DEFAULT 0
)");

try {
    switch ($action) {
        case 'token':
            // Get or generate scanner auth token
            $stmt = $db->prepare("SELECT token, expires_at FROM scanner_tokens WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing && strtotime($existing['expires_at']) > time()) {
                echo json_encode([
                    'success' => true,
                    'token' => $existing['token'],
                    'expires_at' => $existing['expires_at']
                ]);
            } else {
                // Generate new token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $stmt = $db->prepare("
                    INSERT OR REPLACE INTO scanner_tokens (user_id, token, expires_at, created_at)
                    VALUES (?, ?, ?, datetime('now'))
                ");
                $stmt->execute([$user['id'], $token, $expiresAt]);
                
                echo json_encode([
                    'success' => true,
                    'token' => $token,
                    'expires_at' => $expiresAt
                ]);
            }
            break;
            
        case 'devices':
            if ($method === 'GET') {
                // List scanned devices
                $stmt = $db->prepare("SELECT * FROM scanned_devices WHERE user_id = ? ORDER BY last_scanned DESC");
                $stmt->execute([$user['id']]);
                $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Parse open_ports JSON
                foreach ($devices as &$device) {
                    $device['open_ports'] = json_decode($device['open_ports'], true) ?? [];
                }
                
                echo json_encode(['success' => true, 'data' => $devices]);
            } elseif ($method === 'POST') {
                // Sync devices from scanner
                $input = json_decode(file_get_contents('php://input'), true);
                $devices = $input['devices'] ?? [];
                
                $synced = 0;
                foreach ($devices as $device) {
                    // Check if device exists
                    $stmt = $db->prepare("SELECT id FROM scanned_devices WHERE user_id = ? AND mac = ?");
                    $stmt->execute([$user['id'], $device['mac'] ?? '']);
                    $existing = $stmt->fetch();
                    
                    $openPorts = json_encode($device['open_ports'] ?? []);
                    
                    if ($existing) {
                        // Update
                        $stmt = $db->prepare("
                            UPDATE scanned_devices SET 
                                ip = ?, hostname = ?, vendor = ?, device_type = ?, 
                                open_ports = ?, last_scanned = datetime('now'), synced = 1
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $device['ip'],
                            $device['hostname'] ?? null,
                            $device['vendor'] ?? null,
                            $device['type'] ?? $device['device_type'] ?? 'unknown',
                            $openPorts,
                            $existing['id']
                        ]);
                    } else {
                        // Insert
                        $stmt = $db->prepare("
                            INSERT INTO scanned_devices (user_id, ip, mac, hostname, vendor, device_type, open_ports, synced)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            $user['id'],
                            $device['ip'],
                            $device['mac'] ?? null,
                            $device['hostname'] ?? null,
                            $device['vendor'] ?? null,
                            $device['type'] ?? $device['device_type'] ?? 'unknown',
                            $openPorts
                        ]);
                    }
                    $synced++;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => "Synced $synced devices",
                    'synced' => $synced
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
