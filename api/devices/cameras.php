<?php
/**
 * TrueVault VPN - Cameras API
 * GET/POST/PUT/DELETE /api/devices/cameras.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();

$method = Response::getMethod();

try {
    $camerasDb = DatabaseManager::getInstance()->cameras();
    
    switch ($method) {
        case 'GET':
            $cameraId = $_GET['id'] ?? null;
            
            if ($cameraId) {
                // Get single camera with full details
                $stmt = $camerasDb->prepare("SELECT * FROM discovered_cameras WHERE id = ? AND user_id = ?");
                $stmt->execute([$cameraId, $user['id']]);
                $camera = $stmt->fetch();
                
                if (!$camera) {
                    Response::notFound('Camera not found');
                }
                
                // Decrypt password if exists
                if ($camera['password_encrypted']) {
                    $camera['password'] = Encryption::decrypt($camera['password_encrypted']);
                }
                unset($camera['password_encrypted']);
                
                // Get camera settings
                $stmt = $camerasDb->prepare("SELECT setting_key, setting_value FROM camera_settings WHERE camera_id = ?");
                $stmt->execute([$cameraId]);
                $settings = $stmt->fetchAll();
                
                $camera['settings'] = [];
                foreach ($settings as $s) {
                    $camera['settings'][$s['setting_key']] = $s['setting_value'];
                }
                
                // Get recent events
                $stmt = $camerasDb->prepare("
                    SELECT * FROM camera_events 
                    WHERE camera_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 20
                ");
                $stmt->execute([$cameraId]);
                $camera['recent_events'] = $stmt->fetchAll();
                
                Response::success(['camera' => $camera]);
                
            } else {
                // List all cameras
                $stmt = $camerasDb->prepare("
                    SELECT id, device_id, camera_name, ip_address, mac_address, vendor, model, 
                           is_ptz, has_audio, has_two_way_audio, has_night_vision, has_motion_detection, has_floodlight,
                           is_online, last_seen, thumbnail_path, created_at
                    FROM discovered_cameras 
                    WHERE user_id = ?
                    ORDER BY camera_name
                ");
                $stmt->execute([$user['id']]);
                $cameras = $stmt->fetchAll();
                
                // Get event counts
                foreach ($cameras as &$cam) {
                    $stmt = $camerasDb->prepare("
                        SELECT COUNT(*) as count FROM camera_events 
                        WHERE camera_id = ? AND is_viewed = 0
                    ");
                    $stmt->execute([$cam['id']]);
                    $cam['unviewed_events'] = (int) $stmt->fetch()['count'];
                }
                
                Response::success([
                    'cameras' => $cameras,
                    'count' => count($cameras)
                ]);
            }
            break;
            
        case 'POST':
            // Add new camera
            $input = Response::getJsonInput();
            
            $validator = Validator::make($input, [
                'ip_address' => 'required|ip',
                'camera_name' => 'required|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->errors());
            }
            
            // Check if camera already exists
            $stmt = $camerasDb->prepare("SELECT id FROM discovered_cameras WHERE user_id = ? AND ip_address = ?");
            $stmt->execute([$user['id'], $input['ip_address']]);
            
            if ($stmt->fetch()) {
                Response::error('Camera with this IP already exists', 409);
            }
            
            // Encrypt password if provided
            $passwordEncrypted = null;
            if (!empty($input['password'])) {
                $passwordEncrypted = Encryption::encrypt($input['password']);
            }
            
            // Insert camera
            $stmt = $camerasDb->prepare("
                INSERT INTO discovered_cameras 
                (user_id, device_id, camera_name, ip_address, mac_address, vendor, model, 
                 rtsp_port, http_port, username, password_encrypted, 
                 stream_url_main, stream_url_sub, snapshot_url,
                 is_ptz, has_audio, has_two_way_audio, has_night_vision, has_motion_detection, has_floodlight)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $deviceId = 'cam_' . Encryption::generateUUID();
            
            $stmt->execute([
                $user['id'],
                $deviceId,
                trim($input['camera_name']),
                $input['ip_address'],
                $input['mac_address'] ?? null,
                $input['vendor'] ?? null,
                $input['model'] ?? null,
                $input['rtsp_port'] ?? 554,
                $input['http_port'] ?? 80,
                $input['username'] ?? null,
                $passwordEncrypted,
                $input['stream_url_main'] ?? null,
                $input['stream_url_sub'] ?? null,
                $input['snapshot_url'] ?? null,
                $input['is_ptz'] ?? 0,
                $input['has_audio'] ?? 0,
                $input['has_two_way_audio'] ?? 0,
                $input['has_night_vision'] ?? 1,
                $input['has_motion_detection'] ?? 1,
                $input['has_floodlight'] ?? 0
            ]);
            
            $cameraId = $camerasDb->lastInsertId();
            
            Logger::info('Camera added', ['user_id' => $user['id'], 'camera_id' => $cameraId]);
            
            // Get the camera
            $stmt = $camerasDb->prepare("SELECT * FROM discovered_cameras WHERE id = ?");
            $stmt->execute([$cameraId]);
            $camera = $stmt->fetch();
            unset($camera['password_encrypted']);
            
            Response::created(['camera' => $camera], 'Camera added successfully');
            break;
            
        case 'PUT':
            // Update camera
            $cameraId = $_GET['id'] ?? null;
            
            if (!$cameraId) {
                Response::error('Camera ID required', 400);
            }
            
            // Verify camera belongs to user
            $stmt = $camerasDb->prepare("SELECT * FROM discovered_cameras WHERE id = ? AND user_id = ?");
            $stmt->execute([$cameraId, $user['id']]);
            $camera = $stmt->fetch();
            
            if (!$camera) {
                Response::notFound('Camera not found');
            }
            
            $input = Response::getJsonInput();
            
            $updates = [];
            $params = [];
            
            $allowedFields = ['camera_name', 'username', 'rtsp_port', 'http_port', 
                             'stream_url_main', 'stream_url_sub', 'snapshot_url',
                             'is_ptz', 'has_audio', 'has_two_way_audio', 'has_night_vision',
                             'has_motion_detection', 'has_floodlight'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            // Handle password separately
            if (isset($input['password'])) {
                $updates[] = "password_encrypted = ?";
                $params[] = Encryption::encrypt($input['password']);
            }
            
            if (empty($updates)) {
                Response::error('No fields to update', 400);
            }
            
            $updates[] = "updated_at = datetime('now')";
            $params[] = $cameraId;
            
            $sql = "UPDATE discovered_cameras SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $camerasDb->prepare($sql);
            $stmt->execute($params);
            
            Logger::info('Camera updated', ['user_id' => $user['id'], 'camera_id' => $cameraId]);
            
            // Get updated camera
            $stmt = $camerasDb->prepare("SELECT * FROM discovered_cameras WHERE id = ?");
            $stmt->execute([$cameraId]);
            $camera = $stmt->fetch();
            unset($camera['password_encrypted']);
            
            Response::success(['camera' => $camera], 'Camera updated');
            break;
            
        case 'DELETE':
            $cameraId = $_GET['id'] ?? null;
            
            if (!$cameraId) {
                Response::error('Camera ID required', 400);
            }
            
            // Verify camera belongs to user
            $stmt = $camerasDb->prepare("SELECT id FROM discovered_cameras WHERE id = ? AND user_id = ?");
            $stmt->execute([$cameraId, $user['id']]);
            
            if (!$stmt->fetch()) {
                Response::notFound('Camera not found');
            }
            
            // Delete camera (cascades to settings and events)
            $stmt = $camerasDb->prepare("DELETE FROM discovered_cameras WHERE id = ?");
            $stmt->execute([$cameraId]);
            
            Logger::info('Camera deleted', ['user_id' => $user['id'], 'camera_id' => $cameraId]);
            
            Response::success(null, 'Camera deleted');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Camera operation failed: ' . $e->getMessage());
    Response::serverError('Camera operation failed');
}
