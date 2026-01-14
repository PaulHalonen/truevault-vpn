<?php
/**
 * TrueVault VPN - Cameras API
 * List and manage user's IP cameras
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // List user's cameras using actual column names
            $cameras = Database::query('cameras', "
                SELECT id, user_id, name, local_ip, mac_address, brand, model, 
                       stream_url, port_forward_enabled, port_forward_external, 
                       status, last_seen, created_at 
                FROM ip_cameras 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ", [$user['id']]);
            
            // Map to expected frontend field names
            foreach ($cameras as &$cam) {
                $cam['camera_name'] = $cam['name'];
                $cam['ip_address'] = $cam['local_ip'];
                $cam['is_online'] = ($cam['status'] === 'online') ? 1 : 0;
            }
            
            Response::success(['cameras' => $cameras]);
            break;
            
        case 'POST':
            // Add new camera
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = $input['name'] ?? $input['camera_name'] ?? null;
            $ip = $input['local_ip'] ?? $input['ip_address'] ?? null;
            
            if (empty($name) || empty($ip)) {
                Response::error('Camera name and IP address are required', 400);
            }
            
            $result = Database::execute('cameras', "
                INSERT INTO ip_cameras (user_id, name, local_ip, mac_address, brand, model, stream_url, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'offline', datetime('now'))
            ", [
                $user['id'],
                $name,
                $ip,
                $input['mac_address'] ?? null,
                $input['brand'] ?? $input['vendor'] ?? null,
                $input['model'] ?? null,
                $input['stream_url'] ?? $input['rtsp_path'] ?? null
            ]);
            
            Response::success([
                'message' => 'Camera added successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'PUT':
            // Update camera
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                Response::error('Camera ID is required', 400);
            }
            
            // Verify ownership
            $camera = Database::queryOne('cameras', "SELECT id FROM ip_cameras WHERE id = ? AND user_id = ?", [$input['id'], $user['id']]);
            if (!$camera) {
                Response::error('Camera not found or access denied', 403);
            }
            
            $name = $input['name'] ?? $input['camera_name'] ?? null;
            $ip = $input['local_ip'] ?? $input['ip_address'] ?? null;
            
            Database::execute('cameras', "
                UPDATE ip_cameras 
                SET name = ?, local_ip = ?, brand = ?, model = ?, stream_url = ?
                WHERE id = ? AND user_id = ?
            ", [
                $name,
                $ip,
                $input['brand'] ?? $input['vendor'] ?? null,
                $input['model'] ?? null,
                $input['stream_url'] ?? null,
                $input['id'],
                $user['id']
            ]);
            
            Response::success(['message' => 'Camera updated successfully']);
            break;
            
        case 'DELETE':
            // Remove camera
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                Response::error('Camera ID is required', 400);
            }
            
            // Verify ownership
            $camera = Database::queryOne('cameras', "SELECT id FROM ip_cameras WHERE id = ? AND user_id = ?", [$id, $user['id']]);
            if (!$camera) {
                Response::error('Camera not found or access denied', 403);
            }
            
            Database::execute('cameras', "DELETE FROM ip_cameras WHERE id = ? AND user_id = ?", [$id, $user['id']]);
            
            Response::success(['message' => 'Camera removed successfully']);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Cameras API error: " . $e->getMessage());
    Response::error('Server error', 500);
}
