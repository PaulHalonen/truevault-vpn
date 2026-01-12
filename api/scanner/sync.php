<?php
/**
 * TrueVault VPN - Network Scanner Sync API
 * POST /api/scanner/sync.php
 * 
 * Receives discovered devices from the network scanner
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Require authentication
$user = Auth::requireAuth();

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'devices' => 'required|array'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$devices = $input['devices'];

try {
    $discoveredDb = DatabaseManager::getInstance()->discovered();
    $camerasDb = DatabaseManager::getInstance()->cameras();
    
    $stats = [
        'total' => count($devices),
        'new_devices' => 0,
        'updated_devices' => 0,
        'new_cameras' => 0
    ];
    
    $discoveredDb->beginTransaction();
    
    foreach ($devices as $device) {
        // Validate device has required fields
        if (empty($device['ip']) || empty($device['mac'])) {
            continue;
        }
        
        $mac = strtoupper($device['mac']);
        $ip = $device['ip'];
        
        // Check if device already exists
        $stmt = $discoveredDb->prepare("SELECT id FROM discovered_devices WHERE user_id = ? AND mac_address = ?");
        $stmt->execute([$user['id'], $mac]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing device
            $stmt = $discoveredDb->prepare("
                UPDATE discovered_devices 
                SET ip_address = ?, hostname = ?, vendor = ?, device_type = ?, 
                    type_name = ?, device_icon = ?, is_online = 1, last_seen = datetime('now'), updated_at = datetime('now')
                WHERE id = ?
            ");
            $stmt->execute([
                $ip,
                $device['hostname'] ?? null,
                $device['vendor'] ?? null,
                $device['type'] ?? 'unknown',
                $device['type_name'] ?? 'Unknown Device',
                $device['icon'] ?? '❓',
                $existing['id']
            ]);
            $stats['updated_devices']++;
            
            $deviceId = $existing['id'];
        } else {
            // Insert new device
            $stmt = $discoveredDb->prepare("
                INSERT INTO discovered_devices 
                (user_id, device_id, ip_address, mac_address, hostname, vendor, device_type, type_name, device_icon, is_local, first_seen, last_seen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            
            $deviceUuid = 'dev_' . str_replace(':', '', $mac) . '_' . time();
            
            $stmt->execute([
                $user['id'],
                $deviceUuid,
                $ip,
                $mac,
                $device['hostname'] ?? null,
                $device['vendor'] ?? null,
                $device['type'] ?? 'unknown',
                $device['type_name'] ?? 'Unknown Device',
                $device['icon'] ?? '❓',
                $device['is_local'] ?? 0
            ]);
            
            $deviceId = $discoveredDb->lastInsertId();
            $stats['new_devices']++;
        }
        
        // Update open ports
        if (!empty($device['open_ports'])) {
            // Clear existing ports
            $stmt = $discoveredDb->prepare("DELETE FROM device_ports WHERE device_id = ?");
            $stmt->execute([$deviceId]);
            
            // Insert new ports
            $stmt = $discoveredDb->prepare("
                INSERT INTO device_ports (device_id, port_number, service_name)
                VALUES (?, ?, ?)
            ");
            
            foreach ($device['open_ports'] as $port) {
                $portNum = is_array($port) ? $port['port'] : $port;
                $service = is_array($port) ? ($port['service'] ?? null) : null;
                $stmt->execute([$deviceId, $portNum, $service]);
            }
        }
        
        // If this is a camera, add to cameras table
        if (($device['type'] ?? '') === 'ip_camera') {
            // Check if camera already exists
            $stmt = $camerasDb->prepare("SELECT id FROM discovered_cameras WHERE user_id = ? AND mac_address = ?");
            $stmt->execute([$user['id'], $mac]);
            
            if (!$stmt->fetch()) {
                // Add as new camera
                $cameraName = $device['hostname'] ?? ($device['vendor'] ?? 'IP') . ' Camera';
                
                $stmt = $camerasDb->prepare("
                    INSERT INTO discovered_cameras 
                    (user_id, device_id, camera_name, ip_address, mac_address, vendor, is_online, last_seen)
                    VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))
                ");
                
                $cameraDeviceId = 'cam_' . str_replace(':', '', $mac);
                
                $stmt->execute([
                    $user['id'],
                    $cameraDeviceId,
                    $cameraName,
                    $ip,
                    $mac,
                    $device['vendor'] ?? null
                ]);
                
                $stats['new_cameras']++;
            } else {
                // Update existing camera
                $stmt = $camerasDb->prepare("
                    UPDATE discovered_cameras 
                    SET ip_address = ?, is_online = 1, last_seen = datetime('now'), updated_at = datetime('now')
                    WHERE user_id = ? AND mac_address = ?
                ");
                $stmt->execute([$ip, $user['id'], $mac]);
            }
        }
    }
    
    $discoveredDb->commit();
    
    // Mark devices not in scan as offline (if they haven't been seen in this scan)
    $scannedMacs = array_map(function($d) { return strtoupper($d['mac'] ?? ''); }, $devices);
    $scannedMacs = array_filter($scannedMacs);
    
    if (!empty($scannedMacs)) {
        $placeholders = implode(',', array_fill(0, count($scannedMacs), '?'));
        $params = array_merge([$user['id']], $scannedMacs);
        
        $stmt = $discoveredDb->prepare("
            UPDATE discovered_devices 
            SET is_online = 0 
            WHERE user_id = ? AND mac_address NOT IN ($placeholders)
        ");
        $stmt->execute($params);
        
        // Also update cameras
        $stmt = $camerasDb->prepare("
            UPDATE discovered_cameras 
            SET is_online = 0 
            WHERE user_id = ? AND mac_address NOT IN ($placeholders)
        ");
        $stmt->execute($params);
    }
    
    Logger::info('Scanner sync completed', [
        'user_id' => $user['id'],
        'stats' => $stats
    ]);
    
    Response::success([
        'synced' => true,
        'stats' => $stats
    ], 'Devices synced successfully');
    
} catch (Exception $e) {
    if (isset($discoveredDb)) {
        $discoveredDb->rollBack();
    }
    Logger::error('Scanner sync failed: ' . $e->getMessage());
    Response::serverError('Sync failed');
}
