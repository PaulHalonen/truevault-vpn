<?php
/**
 * TrueVault VPN - Scanner Sync API
 * 
 * PURPOSE: Receive discovered devices from network scanner
 * METHOD: POST
 * AUTHENTICATION: JWT required (from scanner via token)
 * 
 * REQUEST BODY:
 * {
 *   "devices": [
 *     {
 *       "id": "auto_192_168_1_100",
 *       "ip": "192.168.1.100",
 *       "mac": "AA:BB:CC:DD:EE:FF",
 *       "hostname": "MyCamera",
 *       "vendor": "Geeni",
 *       "type": "ip_camera",
 *       "type_name": "Geeni Camera",
 *       "icon": "📷",
 *       "open_ports": [{"port": 80, "service": "http"}],
 *       "discovered_at": "2026-01-18T10:30:00"
 *     }
 *   ]
 * }
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "synced": 5,
 *   "message": "Synced 5 devices"
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userId = $user['user_id'];
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['devices']) || !is_array($input['devices'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required field: devices (array)'
        ]);
        exit;
    }
    
    $devices = $input['devices'];
    
    // Get database connection
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    
    // Begin transaction
    $devicesConn->beginTransaction();
    
    $syncedCount = 0;
    
    foreach ($devices as $device) {
        // Validate required fields
        if (empty($device['ip']) || empty($device['mac'])) {
            continue; // Skip invalid devices
        }
        
        // Check if device already exists for this user
        $stmt = $devicesConn->prepare("
            SELECT device_id
            FROM port_forward_devices
            WHERE user_id = ? AND mac_address = ?
        ");
        $stmt->execute([$userId, $device['mac']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing device
            $stmt = $devicesConn->prepare("
                UPDATE port_forward_devices
                SET device_name = ?,
                    ip_address = ?,
                    device_type = ?,
                    vendor = ?,
                    hostname = ?,
                    open_ports = ?,
                    discovered_at = datetime('now')
                WHERE device_id = ?
            ");
            
            $stmt->execute([
                $device['type_name'] ?? $device['hostname'] ?? 'Unknown Device',
                $device['ip'],
                $device['type'] ?? 'unknown',
                $device['vendor'] ?? 'Unknown',
                $device['hostname'] ?? null,
                json_encode($device['open_ports'] ?? []),
                $existing['device_id']
            ]);
        } else {
            // Insert new device
            $stmt = $devicesConn->prepare("
                INSERT INTO port_forward_devices (
                    user_id,
                    device_name,
                    ip_address,
                    mac_address,
                    device_type,
                    vendor,
                    hostname,
                    open_ports,
                    port_forward_enabled,
                    discovered_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, datetime('now'))
            ");
            
            $stmt->execute([
                $userId,
                $device['type_name'] ?? $device['hostname'] ?? 'Unknown Device',
                $device['ip'],
                $device['mac'],
                $device['type'] ?? 'unknown',
                $device['vendor'] ?? 'Unknown',
                $device['hostname'] ?? null,
                json_encode($device['open_ports'] ?? [])
            ]);
        }
        
        $syncedCount++;
    }
    
    // Commit transaction
    $devicesConn->commit();
    
    // Log sync
    error_log("Scanner sync: $syncedCount devices synced for user $userId");
    
    // Return success
    echo json_encode([
        'success' => true,
        'synced' => $syncedCount,
        'message' => "Synced $syncedCount devices"
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($devicesConn) && $devicesConn->inTransaction()) {
        $devicesConn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('Scanner Sync Error: ' . $e->getMessage());
}
