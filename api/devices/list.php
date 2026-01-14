<?php
/**
 * TrueVault VPN - Devices List/Sync
 * GET/POST /api/devices/list.php
 * 
 * FIXED: January 14, 2026 - Changed DatabaseManager to Database class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

Response::requireMethods(['GET', 'POST']);

$user = Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $type = $_GET['type'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $where = "WHERE user_id = ?";
        $params = [$user['id']];
        
        if ($type) {
            $where .= " AND device_type = ?";
            $params[] = $type;
        }
        
        // Get total count
        $countResult = Database::queryOne('devices', 
            "SELECT COUNT(*) as total FROM discovered_devices $where", 
            $params
        );
        $total = $countResult['total'] ?? 0;
        
        // Get devices with ports - use separate queries since GROUP_CONCAT may differ
        $devicesParams = array_merge($params, [$limit, $offset]);
        $devices = Database::query('devices', "
            SELECT * FROM discovered_devices 
            $where
            ORDER BY device_type, ip_address
            LIMIT ? OFFSET ?
        ", $devicesParams);
        
        // Get ports for each device
        foreach ($devices as &$device) {
            $device['open_ports'] = [];
            $ports = Database::query('devices', 
                "SELECT port_number, service_name FROM device_ports WHERE device_id = ?", 
                [$device['id']]
            );
            foreach ($ports as $port) {
                $device['open_ports'][] = [
                    'port' => (int) $port['port_number'], 
                    'service' => $port['service_name']
                ];
            }
        }
        
        Response::success([
            'devices' => $devices,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        
    } catch (Exception $e) {
        Response::serverError('Failed to get devices: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sync devices from scanner
    $input = Response::getJsonInput();
    $devices = $input['devices'] ?? [];
    
    if (empty($devices)) {
        Response::error('No devices provided', 400);
    }
    
    try {
        $added = 0;
        $updated = 0;
        $cameras = 0;
        
        foreach ($devices as $device) {
            $macAddress = strtoupper($device['mac'] ?? $device['mac_address'] ?? '');
            $ipAddress = $device['ip'] ?? $device['ip_address'] ?? '';
            
            if (!$ipAddress) continue;
            
            // Check if device exists
            $existing = Database::queryOne('devices', 
                "SELECT id FROM discovered_devices WHERE user_id = ? AND (mac_address = ? OR ip_address = ?)", 
                [$user['id'], $macAddress, $ipAddress]
            );
            
            if ($existing) {
                // Update existing device
                Database::execute('devices', "
                    UPDATE discovered_devices SET
                        ip_address = ?, hostname = ?, vendor = ?, device_type = ?,
                        device_icon = ?, type_name = ?, is_online = 1,
                        last_seen = datetime('now'), updated_at = datetime('now')
                    WHERE id = ?
                ", [
                    $ipAddress,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? null,
                    $device['type'] ?? 'unknown',
                    $device['icon'] ?? '?',
                    $device['type_name'] ?? 'Unknown',
                    $existing['id']
                ]);
                $updated++;
                $deviceId = $existing['id'];
            } else {
                // Insert new device
                $deviceUuid = $device['id'] ?? uniqid('dev_');
                $result = Database::execute('devices', "
                    INSERT INTO discovered_devices 
                    (user_id, device_id, ip_address, mac_address, hostname, vendor, device_type, device_icon, type_name, is_online, last_seen)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime('now'))
                ", [
                    $user['id'],
                    $deviceUuid,
                    $ipAddress,
                    $macAddress,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? null,
                    $device['type'] ?? 'unknown',
                    $device['icon'] ?? '?',
                    $device['type_name'] ?? 'Unknown'
                ]);
                $added++;
                $deviceId = $result['lastInsertId'];
            }
            
            // Sync ports
            if (!empty($device['open_ports'])) {
                foreach ($device['open_ports'] as $port) {
                    $portNum = $port['port'] ?? $port;
                    $service = $port['service'] ?? 'unknown';
                    
                    Database::execute('devices', "
                        INSERT OR REPLACE INTO device_ports (device_id, port_number, service_name, is_open)
                        VALUES (?, ?, ?, 1)
                    ", [$deviceId, $portNum, $service]);
                }
            }
            
            // Check if camera - add to cameras database
            if ($device['type'] === 'ip_camera') {
                $cameras++;
                
                $existingCam = Database::queryOne('cameras', 
                    "SELECT id FROM discovered_cameras WHERE user_id = ? AND mac_address = ?", 
                    [$user['id'], $macAddress]
                );
                
                if (!$existingCam) {
                    Database::execute('cameras', "
                        INSERT INTO discovered_cameras 
                        (user_id, device_id, camera_name, ip_address, mac_address, vendor, is_online, last_seen)
                        VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))
                    ", [
                        $user['id'],
                        $deviceUuid ?? uniqid('cam_'),
                        $device['hostname'] ?? $device['type_name'] ?? 'Camera',
                        $ipAddress,
                        $macAddress,
                        $device['vendor'] ?? null
                    ]);
                }
            }
        }
        
        Response::success([
            'added' => $added,
            'updated' => $updated,
            'cameras_found' => $cameras,
            'total_synced' => count($devices)
        ], 'Devices synced successfully');
        
    } catch (Exception $e) {
        Response::serverError('Failed to sync devices: ' . $e->getMessage());
    }
}
