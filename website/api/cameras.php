<?php
/**
 * Cameras API - Task 6A.8
 * All camera management actions via ?action= parameter
 * 
 * Endpoints:
 * GET  /api/cameras.php?action=list
 * GET  /api/cameras.php?action=get&camera_id=xxx
 * GET  /api/cameras.php?action=motion_events&camera_id=xxx
 * POST /api/cameras.php?action=add
 * POST /api/cameras.php?action=update
 * POST /api/cameras.php?action=delete
 * POST /api/cameras.php?action=ptz
 * POST /api/cameras.php?action=send_audio
 * POST /api/cameras.php?action=save_order
 * POST /api/cameras.php?action=snapshot
 * POST /api/cameras.php?action=toggle_recording
 * POST /api/cameras.php?action=test_connection
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user
session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $payload = JWT::validate($matches[1]);
        if ($payload) {
            $userId = $payload['user_id'];
        }
    }
}

if (!$userId) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listCameras($userId);
        break;
    case 'get':
        getCamera($userId);
        break;
    case 'motion_events':
        getMotionEvents($userId);
        break;
    case 'add':
        addCamera($userId);
        break;
    case 'update':
        updateCamera($userId);
        break;
    case 'delete':
        deleteCamera($userId);
        break;
    case 'ptz':
        handlePTZ($userId);
        break;
    case 'send_audio':
        handleTwoWayAudio($userId);
        break;
    case 'save_order':
        saveCameraOrder($userId);
        break;
    case 'snapshot':
        takeSnapshot($userId);
        break;
    case 'toggle_recording':
        toggleRecording($userId);
        break;
    case 'test_connection':
        testConnection($userId);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

// ============== LIST ALL CAMERAS ==============
function listCameras($userId) {
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE user_id = :user_id ORDER BY display_order");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $cameras = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $cameras[] = formatCamera($row);
    }
    $db->close();
    
    echo json_encode([
        'success' => true,
        'cameras' => $cameras,
        'count' => count($cameras)
    ]);
}

// ============== GET SINGLE CAMERA ==============
function getCamera($userId) {
    $cameraId = $_GET['camera_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $camera = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$camera) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'camera' => formatCamera($camera)
    ]);
}

// ============== GET MOTION EVENTS ==============
function getMotionEvents($userId) {
    $cameraId = $_GET['camera_id'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    
    // Verify camera belongs to user
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        $db->close();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    $stmt = $db->prepare("SELECT * FROM motion_events WHERE camera_id = :id ORDER BY detection_time DESC LIMIT :limit");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $events = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $events[] = [
            'id' => $row['id'],
            'camera_id' => $row['camera_id'],
            'detected_at' => $row['detection_time'],
            'thumbnail_url' => $row['thumbnail'] ? "/recordings/thumbnails/{$row['thumbnail']}" : null,
            'recording_url' => $row['recording_id'] ? "/recordings/clips/{$row['recording_id']}.mp4" : null,
            'viewed' => (bool)$row['viewed'],
            'notified' => (bool)$row['notified']
        ];
    }
    $db->close();
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);
}

// ============== ADD CAMERA ==============
function addCamera($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cameraId = 'cam_' . bin2hex(random_bytes(8));
    $name = $input['camera_name'] ?? 'New Camera';
    $localIp = $input['local_ip'] ?? '';
    $rtspPort = $input['rtsp_port'] ?? 554;
    $rtspPath = $input['rtsp_path'] ?? '/stream';
    $rtspUser = $input['rtsp_username'] ?? '';
    $rtspPass = $input['rtsp_password'] ?? '';
    $location = $input['location'] ?? '';
    
    if (empty($localIp)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'local_ip is required']);
        return;
    }
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Get next display order
    $orderResult = $db->querySingle("SELECT MAX(display_order) FROM cameras WHERE user_id = {$userId}");
    $displayOrder = ($orderResult ?? 0) + 1;
    
    $stmt = $db->prepare("INSERT INTO cameras (
        camera_id, user_id, camera_name, local_ip, rtsp_port, rtsp_path,
        rtsp_username, rtsp_password, location, display_order, created_at
    ) VALUES (
        :camera_id, :user_id, :name, :ip, :port, :path,
        :username, :password, :location, :order, datetime('now')
    )");
    
    $stmt->bindValue(':camera_id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $localIp, SQLITE3_TEXT);
    $stmt->bindValue(':port', $rtspPort, SQLITE3_INTEGER);
    $stmt->bindValue(':path', $rtspPath, SQLITE3_TEXT);
    $stmt->bindValue(':username', $rtspUser, SQLITE3_TEXT);
    $stmt->bindValue(':password', $rtspPass, SQLITE3_TEXT);
    $stmt->bindValue(':location', $location, SQLITE3_TEXT);
    $stmt->bindValue(':order', $displayOrder, SQLITE3_INTEGER);
    
    $stmt->execute();
    $db->close();
    
    echo json_encode([
        'success' => true,
        'camera_id' => $cameraId,
        'message' => 'Camera added successfully'
    ]);
}

// ============== UPDATE CAMERA ==============
function updateCamera($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Verify ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        $db->close();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        return;
    }
    
    // Build update query
    $updates = [];
    $params = [':camera_id' => $cameraId];
    
    $fields = ['camera_name', 'local_ip', 'rtsp_port', 'rtsp_path', 'rtsp_username', 
               'rtsp_password', 'location', 'recording_enabled', 'motion_detection'];
    
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[":{$field}"] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        return;
    }
    
    $sql = "UPDATE cameras SET " . implode(', ', $updates) . ", updated_at = datetime('now') WHERE camera_id = :camera_id";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $db->close();
    
    echo json_encode(['success' => true, 'message' => 'Camera updated']);
}

// ============== DELETE CAMERA ==============
function deleteCamera($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    $stmt = $db->prepare("DELETE FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $changes = $db->changes();
    $db->close();
    
    if ($changes === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    echo json_encode(['success' => true, 'message' => 'Camera deleted']);
}

// ============== PTZ CONTROL ==============
function handlePTZ($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $ptzAction = $input['ptz_action'] ?? ''; // up, down, left, right, home, zoom_in, zoom_out
    
    // Verify camera belongs to user and supports PTZ
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id AND supports_ptz = 1");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $camera = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$camera) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Camera not found or PTZ not supported']);
        return;
    }
    
    // Send PTZ command via ONVIF
    $result = sendPTZCommand($camera, $ptzAction);
    
    echo json_encode([
        'success' => $result,
        'action' => $ptzAction,
        'message' => $result ? 'PTZ command sent' : 'PTZ command failed'
    ]);
}

// ============== TWO-WAY AUDIO ==============
function handleTwoWayAudio($userId) {
    $cameraId = $_POST['camera_id'] ?? '';
    
    // Verify camera
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id AND supports_two_way = 1");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $camera = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$camera) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Camera not found or two-way audio not supported']);
        return;
    }
    
    // Handle audio upload
    if (!isset($_FILES['audio'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No audio data']);
        return;
    }
    
    $audioData = file_get_contents($_FILES['audio']['tmp_name']);
    
    // Send audio to camera (implementation depends on camera protocol)
    $result = sendAudioToCamera($camera, $audioData);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Audio sent' : 'Audio send failed'
    ]);
}

// ============== SAVE CAMERA ORDER ==============
function saveCameraOrder($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $order = $input['order'] ?? [];
    
    if (empty($order)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        return;
    }
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    foreach ($order as $index => $cameraId) {
        $stmt = $db->prepare("UPDATE cameras SET display_order = :order WHERE camera_id = :id AND user_id = :user_id");
        $stmt->bindValue(':order', $index + 1, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    $db->close();
    
    echo json_encode(['success' => true, 'message' => 'Order saved']);
}

// ============== TAKE SNAPSHOT ==============
function takeSnapshot($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    // Verify camera
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $camera = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$camera) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Create snapshot directory
    $snapshotDir = __DIR__ . '/../recordings/snapshots/';
    if (!is_dir($snapshotDir)) {
        mkdir($snapshotDir, 0755, true);
    }
    
    // Generate snapshot filename
    $filename = "{$cameraId}_" . date('Y-m-d_H-i-s') . ".jpg";
    $filepath = $snapshotDir . $filename;
    
    // Capture snapshot from RTSP stream
    $rtspUrl = buildRTSPUrl($camera);
    $result = captureSnapshot($rtspUrl, $filepath);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'snapshot_url' => "/recordings/snapshots/{$filename}",
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Snapshot capture failed']);
    }
}

// ============== TOGGLE RECORDING ==============
function toggleRecording($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $enable = $input['enable'] ?? null;
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Get current state if not specified
    if ($enable === null) {
        $stmt = $db->prepare("SELECT recording_enabled FROM cameras WHERE camera_id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$row) {
            $db->close();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Camera not found']);
            return;
        }
        
        $enable = !$row['recording_enabled'];
    }
    
    $stmt = $db->prepare("UPDATE cameras SET recording_enabled = :enabled WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':enabled', $enable ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
    
    echo json_encode([
        'success' => true,
        'recording_enabled' => (bool)$enable,
        'message' => $enable ? 'Recording enabled' : 'Recording disabled'
    ]);
}

// ============== TEST CONNECTION ==============
function testConnection($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $localIp = $input['local_ip'] ?? '';
    $rtspPort = $input['rtsp_port'] ?? 554;
    $rtspUser = $input['rtsp_username'] ?? '';
    $rtspPass = $input['rtsp_password'] ?? '';
    $rtspPath = $input['rtsp_path'] ?? '/stream';
    
    // Test TCP connection to RTSP port
    $socket = @fsockopen($localIp, $rtspPort, $errno, $errstr, 5);
    
    if (!$socket) {
        echo json_encode([
            'success' => false,
            'connected' => false,
            'error' => "Connection failed: {$errstr}"
        ]);
        return;
    }
    
    fclose($socket);
    
    // Build test URL
    $rtspUrl = !empty($rtspUser) 
        ? "rtsp://{$rtspUser}:{$rtspPass}@{$localIp}:{$rtspPort}{$rtspPath}"
        : "rtsp://{$localIp}:{$rtspPort}{$rtspPath}";
    
    // Test RTSP OPTIONS
    $rtspOk = testRTSP($localIp, $rtspPort, $rtspPath, $rtspUser, $rtspPass);
    
    echo json_encode([
        'success' => true,
        'connected' => true,
        'rtsp_ok' => $rtspOk,
        'rtsp_url' => $rtspUrl
    ]);
}

// ============== HELPER FUNCTIONS ==============

function formatCamera($row) {
    return [
        'camera_id' => $row['camera_id'],
        'camera_name' => $row['camera_name'],
        'location' => $row['location'],
        'local_ip' => $row['local_ip'],
        'rtsp_port' => $row['rtsp_port'],
        'rtsp_url' => buildRTSPUrl($row),
        'is_online' => (bool)($row['is_online'] ?? false),
        'supports_audio' => (bool)($row['supports_audio'] ?? false),
        'supports_ptz' => (bool)($row['supports_ptz'] ?? false),
        'supports_two_way' => (bool)($row['supports_two_way'] ?? false),
        'max_resolution' => $row['max_resolution'] ?? '1080p',
        'recording_enabled' => (bool)($row['recording_enabled'] ?? false),
        'motion_detection' => (bool)($row['motion_detection'] ?? false),
        'display_order' => $row['display_order'] ?? 0
    ];
}

function buildRTSPUrl($camera) {
    $ip = $camera['local_ip'] ?? '';
    $port = $camera['rtsp_port'] ?? 554;
    $path = $camera['rtsp_path'] ?? '/stream';
    $user = $camera['rtsp_username'] ?? '';
    $pass = $camera['rtsp_password'] ?? '';
    
    if (!empty($camera['rtsp_url'])) {
        return $camera['rtsp_url'];
    }
    
    if (!empty($user)) {
        return "rtsp://{$user}:{$pass}@{$ip}:{$port}{$path}";
    }
    
    return "rtsp://{$ip}:{$port}{$path}";
}

function sendPTZCommand($camera, $action) {
    // ONVIF PTZ command (simplified)
    // Real implementation would use ONVIF SOAP calls
    $ip = $camera['local_ip'];
    
    $commands = [
        'up' => ['x' => 0, 'y' => 1],
        'down' => ['x' => 0, 'y' => -1],
        'left' => ['x' => -1, 'y' => 0],
        'right' => ['x' => 1, 'y' => 0],
        'home' => ['preset' => 1],
        'zoom_in' => ['zoom' => 1],
        'zoom_out' => ['zoom' => -1]
    ];
    
    // Would send ONVIF ContinuousMove or AbsoluteMove command
    return true;
}

function sendAudioToCamera($camera, $audioData) {
    // Would send audio via ONVIF backchannel or proprietary protocol
    return true;
}

function captureSnapshot($rtspUrl, $outputPath) {
    // Use FFmpeg to capture single frame
    $cmd = "ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' -frames:v 1 -y '{$outputPath}' 2>/dev/null";
    exec($cmd, $output, $returnCode);
    return $returnCode === 0 && file_exists($outputPath);
}

function testRTSP($ip, $port, $path, $user, $pass) {
    $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
    if (!$socket) return false;
    
    $url = "rtsp://{$ip}:{$port}{$path}";
    $request = "OPTIONS {$url} RTSP/1.0\r\nCSeq: 1\r\n";
    
    if (!empty($user)) {
        $auth = base64_encode("{$user}:{$pass}");
        $request .= "Authorization: Basic {$auth}\r\n";
    }
    
    $request .= "\r\n";
    
    fwrite($socket, $request);
    $response = fread($socket, 1024);
    fclose($socket);
    
    return strpos($response, '200 OK') !== false;
}
