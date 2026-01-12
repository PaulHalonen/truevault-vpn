<?php
/**
 * TrueVault VPN - Device Management API
 * Manages user devices for VPN connections
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getConnection('devices');
    
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Get all devices for user
                $stmt = $db->prepare("
                    SELECT * FROM user_devices 
                    WHERE user_id = ? 
                    ORDER BY last_active DESC
                ");
                $stmt->execute([$user['id']]);
                $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get device limit based on plan
                $maxDevices = 3; // personal
                if ($user['plan_type'] === 'family') $maxDevices = 10;
                if ($user['plan_type'] === 'business') $maxDevices = 50;
                if ($user['plan_type'] === 'vip') $maxDevices = 100;
                
                Response::json([
                    'success' => true,
                    'devices' => $devices,
                    'device_count' => count($devices),
                    'device_limit' => $maxDevices
                ]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                // Get single device
                $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
                $stmt->execute([$_GET['id'], $user['id']]);
                $device = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$device) {
                    Response::error('Device not found', 404);
                }
                
                Response::json(['success' => true, 'device' => $device]);
                
            } elseif ($action === 'config' && isset($_GET['id'])) {
                // Generate WireGuard config for device
                $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
                $stmt->execute([$_GET['id'], $user['id']]);
                $device = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$device) {
                    Response::error('Device not found', 404);
                }
                
                // Get user's certificate
                $certDb = Database::getConnection('certificates');
                $certStmt = $certDb->prepare("
                    SELECT * FROM user_certificates 
                    WHERE user_id = ? AND status = 'active'
                    ORDER BY created_at DESC LIMIT 1
                ");
                $certStmt->execute([$user['id']]);
                $cert = $certStmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate config
                $privateKey = $cert ? $cert['private_key'] : base64_encode(random_bytes(32));
                $vpnIp = '10.8.0.' . ($device['id'] + 10);
                
                $config = "[Interface]
PrivateKey = {$privateKey}
Address = {$vpnIp}/24
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = SERVER_PUBLIC_KEY_HERE
AllowedIPs = 0.0.0.0/0
Endpoint = 66.94.103.91:51820
PersistentKeepalive = 25";
                
                Response::json([
                    'success' => true,
                    'config' => $config,
                    'device' => $device
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'register') {
                // Register new device
                if (empty($data['name'])) {
                    Response::error('Device name is required', 400);
                }
                
                // Check device limit
                $maxDevices = 3;
                if ($user['plan_type'] === 'family') $maxDevices = 10;
                if ($user['plan_type'] === 'business') $maxDevices = 50;
                if ($user['plan_type'] === 'vip') $maxDevices = 100;
                
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_devices WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count >= $maxDevices) {
                    Response::error("Device limit reached ($maxDevices for your plan)", 403);
                }
                
                // Detect device type from user agent
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $deviceType = 'desktop';
                $os = 'Unknown';
                
                if (preg_match('/iPhone|iPad/i', $userAgent)) {
                    $deviceType = 'mobile';
                    $os = 'iOS';
                } elseif (preg_match('/Android/i', $userAgent)) {
                    $deviceType = 'mobile';
                    $os = 'Android';
                } elseif (preg_match('/Windows/i', $userAgent)) {
                    $os = 'Windows';
                } elseif (preg_match('/Mac/i', $userAgent)) {
                    $os = 'macOS';
                } elseif (preg_match('/Linux/i', $userAgent)) {
                    $os = 'Linux';
                }
                
                // Generate device keys
                $publicKey = base64_encode(random_bytes(32));
                
                $stmt = $db->prepare("
                    INSERT INTO user_devices 
                    (user_id, name, type, os, public_key, ip_address, last_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                $stmt->execute([
                    $user['id'],
                    $data['name'],
                    $data['type'] ?? $deviceType,
                    $data['os'] ?? $os,
                    $publicKey,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                $deviceId = $db->lastInsertId();
                
                // Log device registration
                $logDb = Database::getConnection('logs');
                $logStmt = $logDb->prepare("
                    INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
                    VALUES (?, 'device_registered', ?, ?, datetime('now'))
                ");
                $logStmt->execute([
                    $user['id'],
                    json_encode(['device_id' => $deviceId, 'name' => $data['name']]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Device registered',
                    'device_id' => $deviceId,
                    'public_key' => $publicKey
                ]);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                Response::error('Device ID required', 400);
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['id'], $user['id']]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                Response::error('Device not found', 404);
            }
            
            // Update device name
            if (!empty($data['name'])) {
                $stmt = $db->prepare("UPDATE user_devices SET name = ? WHERE id = ?");
                $stmt->execute([$data['name'], $data['id']]);
            }
            
            Response::json(['success' => true, 'message' => 'Device updated']);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                Response::error('Device ID required', 400);
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$device) {
                Response::error('Device not found', 404);
            }
            
            // Delete device
            $stmt = $db->prepare("DELETE FROM user_devices WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log deletion
            $logDb = Database::getConnection('logs');
            $logStmt = $logDb->prepare("
                INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
                VALUES (?, 'device_removed', ?, ?, datetime('now'))
            ");
            $logStmt->execute([
                $user['id'],
                json_encode(['device_id' => $id, 'name' => $device['name']]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Response::json(['success' => true, 'message' => 'Device removed']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("Devices API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
