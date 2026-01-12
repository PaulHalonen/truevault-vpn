<?php
/**
 * TrueVault VPN - Devices List/Sync
 * GET/POST /api/devices/list.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

Response::requireMethods(['GET', 'POST']);

$user = Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = DatabaseManager::getInstance()->discovered();
        
        $type = $_GET['type'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE user_id = ?";
        $params = [$user['id']];
        
        if ($type) {
            $where .= " AND device_type = ?";
            $params[] = $type;
        }
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM discovered_devices $where");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Get devices with ports
        $stmt = $db->prepare("
            SELECT d.*, GROUP_CONCAT(p.port_number || ':' || p.service_name) as ports
            FROM discovered_devices d
            LEFT JOIN device_ports p ON d.id = p.device_id
            $where
            GROUP BY d.id
            ORDER BY d.device_type, d.ip_address
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $devices = $stmt->fetchAll();
        
        // Parse ports
        foreach ($devices as &$device) {
            $device['open_ports'] = [];
            if ($device['ports']) {
                foreach (explode(',', $device['ports']) as $port) {
                    list($num, $svc) = explode(':', $port);
                    $device['open_ports'][] = ['port' => intval($num), 'service' => $svc];
                }
            }
            unset($device['ports']);
        }
        
        Response::paginated($devices, $page, $limit, $total);
        
    } catch (Exception $e) {
        Response::serverError('Failed to get devices');
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
        $db = DatabaseManager::getInstance()->discovered();
        $camerasDb = DatabaseManager::getInstance()->cameras();
        
        $added = 0;
        $updated = 0;
        $cameras = 0;
        
        foreach ($devices as $device) {
            $macAddress = strtoupper($device['mac'] ?? $device['mac_address'] ?? '');
            $ipAddress = $device['ip'] ?? $device['ip_address'] ?? '';
            
            if (!$ipAddress) continue;
            
            // Check if exists
            $stmt = $db->prepare("SELECT id FROM discovered_devices WHERE user_id = ? AND (mac_address = ? OR ip_address = ?)");
            $stmt->execute([$user['id'], $macAddress, $ipAddress]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update
                $stmt = $db->prepare("
                    UPDATE discovered_devices SET
                        ip_address = ?, hostname = ?, vendor = ?, device_type = ?,
                        device_icon = ?, type_name = ?, is_online = 1,
                        last_seen = datetime('now'), updated_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([
                    $ipAddress,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? null,
                    $device['type'] ?? 'unknown',
                    $device['icon'] ?? '❓',
                    $device['type_name'] ?? 'Unknown',
                    $existing['id']
                ]);
                $updated++;
                $deviceId = $existing['id'];
            } else {
                // Insert
                $deviceUuid = $device['id'] ?? uniqid('dev_');
                $stmt = $db->prepare("
                    INSERT INTO discovered_devices 
                    (user_id, device_id, ip_address, mac_address, hostname, vendor, device_type, device_icon, type_name, is_online, last_seen)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime('now'))
                ");
                $stmt->execute([
                    $user['id'],
                    $deviceUuid,
                    $ipAddress,
                    $macAddress,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? null,
                    $device['type'] ?? 'unknown',
                    $device['icon'] ?? '❓',
                    $device['type_name'] ?? 'Unknown'
                ]);
                $added++;
                $deviceId = $db->lastInsertId();
            }
            
            // Sync ports
            if (!empty($device['open_ports'])) {
                foreach ($device['open_ports'] as $port) {
                    $portNum = $port['port'] ?? $port;
                    $service = $port['service'] ?? 'unknown';
                    
                    $stmt = $db->prepare("
                        INSERT OR REPLACE INTO device_ports (device_id, port_number, service_name, is_open)
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([$deviceId, $portNum, $service]);
                }
            }
            
            // Check if camera
            if ($device['type'] === 'ip_camera') {
                $cameras++;
                
                // Add to cameras table
                $stmt = $camerasDb->prepare("SELECT id FROM discovered_cameras WHERE user_id = ? AND mac_address = ?");
                $stmt->execute([$user['id'], $macAddress]);
                
                if (!$stmt->fetch()) {
                    $stmt = $camerasDb->prepare("
                        INSERT INTO discovered_cameras 
                        (user_id, device_id, camera_name, ip_address, mac_address, vendor, is_online, last_seen)
                        VALUES (?, ?, ?, ?, ?, ?, 1, datetime('now'))
                    ");
                    $stmt->execute([
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
