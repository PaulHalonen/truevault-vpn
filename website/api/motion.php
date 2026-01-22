<?php
/**
 * Motion Detection System API - Task 6A.11
 * Server-side motion detection with frame differencing
 * 
 * Endpoints:
 * GET  ?action=status&camera_id=xxx - Get motion detection status
 * POST ?action=enable - Enable motion detection for camera
 * POST ?action=disable - Disable motion detection
 * POST ?action=configure - Set sensitivity, zones, schedule
 * GET  ?action=events&camera_id=xxx - Get motion events
 * POST ?action=clear_events - Clear old events
 * GET  ?action=zones&camera_id=xxx - Get detection zones
 * POST ?action=set_zones - Set detection zones
 * POST ?action=test - Test motion detection (single frame compare)
 */

define('TRUEVAULT_INIT', true);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database path
$dbPath = __DIR__ . '/../db/truevault.db';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

$db = new SQLite3($dbPath);

// Authentication
session_start();
$userId = $_SESSION['user_id'] ?? null;

// Check JWT if no session
if (!$userId) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // Simple JWT decode (payload is base64)
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if ($payload && isset($payload['user_id']) && $payload['exp'] > time()) {
                $userId = $payload['user_id'];
            }
        }
    }
}

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Paths
$snapshotDir = __DIR__ . '/../motion/snapshots';
$thumbnailDir = __DIR__ . '/../motion/thumbnails';
$pidDir = '/tmp';

// Ensure directories exist
foreach ([$snapshotDir, $thumbnailDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Motion detection configuration defaults
$defaultConfig = [
    'sensitivity' => 25,        // 1-100, lower = more sensitive
    'min_area' => 500,          // Minimum pixel area to trigger
    'cooldown' => 10,           // Seconds between events
    'record_duration' => 30,    // Seconds to record after motion
    'snapshot_enabled' => true,
    'recording_enabled' => true,
    'notification_enabled' => true,
    'schedule_enabled' => false,
    'schedule' => []            // Array of {day, start_time, end_time}
];

switch ($action) {
    case 'status':
        getMotionStatus();
        break;
    case 'enable':
        enableMotionDetection();
        break;
    case 'disable':
        disableMotionDetection();
        break;
    case 'configure':
        configureMotion();
        break;
    case 'events':
        getMotionEvents();
        break;
    case 'clear_events':
        clearMotionEvents();
        break;
    case 'zones':
        getDetectionZones();
        break;
    case 'set_zones':
        setDetectionZones();
        break;
    case 'test':
        testMotionDetection();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Get motion detection status for a camera
 */
function getMotionStatus() {
    global $db, $userId, $pidDir;
    
    $cameraId = $_GET['camera_id'] ?? '';
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify camera ownership
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $camera = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$camera) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Get motion config
    $stmt = $db->prepare("SELECT * FROM motion_config WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $config = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    // Check if detector process is running
    $pidFile = $pidDir . "/motion_{$cameraId}.pid";
    $isRunning = false;
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        // Check if process exists (cross-platform)
        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
            $isRunning = count($output) > 1;
        } else {
            $isRunning = file_exists("/proc/$pid");
        }
    }
    
    // Get recent event count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM motion_events WHERE camera_id = :cid AND detected_at > datetime('now', '-24 hours')");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $eventCount = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'camera_id' => $cameraId,
        'enabled' => (bool)($camera['motion_detection'] ?? false),
        'running' => $isRunning,
        'config' => $config ? json_decode($config['config'], true) : getDefaultConfig(),
        'events_24h' => (int)$eventCount,
        'last_event' => getLastMotionEvent($cameraId)
    ]);
}

/**
 * Enable motion detection for a camera
 */
function enableMotionDetection() {
    global $db, $userId, $pidDir;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify camera ownership and get details
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $camera = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$camera) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Update camera setting
    $stmt = $db->prepare("UPDATE cameras SET motion_detection = 1 WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Ensure config exists
    $stmt = $db->prepare("SELECT id FROM motion_config WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $exists = $stmt->execute()->fetchArray();
    
    if (!$exists) {
        $stmt = $db->prepare("INSERT INTO motion_config (camera_id, config, created_at) VALUES (:cid, :config, datetime('now'))");
        $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
        $stmt->bindValue(':config', json_encode(getDefaultConfig()), SQLITE3_TEXT);
        $stmt->execute();
    }
    
    // Start motion detector process
    $rtspUrl = buildRTSPUrl($camera);
    $result = startMotionDetector($cameraId, $rtspUrl);
    
    echo json_encode([
        'success' => true,
        'message' => 'Motion detection enabled',
        'camera_id' => $cameraId,
        'detector_started' => $result
    ]);
}

/**
 * Disable motion detection
 */
function disableMotionDetection() {
    global $db, $userId, $pidDir;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Update camera setting
    $stmt = $db->prepare("UPDATE cameras SET motion_detection = 0 WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Stop motion detector
    stopMotionDetector($cameraId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Motion detection disabled',
        'camera_id' => $cameraId
    ]);
}

/**
 * Configure motion detection settings
 */
function configureMotion() {
    global $db, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Build config
    $config = [
        'sensitivity' => max(1, min(100, (int)($input['sensitivity'] ?? 25))),
        'min_area' => max(100, (int)($input['min_area'] ?? 500)),
        'cooldown' => max(1, (int)($input['cooldown'] ?? 10)),
        'record_duration' => max(5, min(300, (int)($input['record_duration'] ?? 30))),
        'snapshot_enabled' => (bool)($input['snapshot_enabled'] ?? true),
        'recording_enabled' => (bool)($input['recording_enabled'] ?? true),
        'notification_enabled' => (bool)($input['notification_enabled'] ?? true),
        'schedule_enabled' => (bool)($input['schedule_enabled'] ?? false),
        'schedule' => $input['schedule'] ?? []
    ];
    
    // Update or insert config
    $stmt = $db->prepare("SELECT id FROM motion_config WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $exists = $stmt->execute()->fetchArray();
    
    if ($exists) {
        $stmt = $db->prepare("UPDATE motion_config SET config = :config, updated_at = datetime('now') WHERE camera_id = :cid");
    } else {
        $stmt = $db->prepare("INSERT INTO motion_config (camera_id, config, created_at) VALUES (:cid, :config, datetime('now'))");
    }
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':config', json_encode($config), SQLITE3_TEXT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Configuration updated',
        'config' => $config
    ]);
}

/**
 * Get motion events for a camera
 */
function getMotionEvents() {
    global $db, $userId;
    
    $cameraId = $_GET['camera_id'] ?? '';
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    
    // Build query
    $sql = "SELECT me.*, c.camera_name, c.location 
            FROM motion_events me 
            JOIN cameras c ON me.camera_id = c.camera_id 
            WHERE c.user_id = :uid";
    
    $params = [':uid' => $userId];
    
    if ($cameraId) {
        $sql .= " AND me.camera_id = :cid";
        $params[':cid'] = $cameraId;
    }
    
    if ($dateFrom) {
        $sql .= " AND me.detected_at >= :from";
        $params[':from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND me.detected_at <= :to";
        $params[':to'] = $dateTo;
    }
    
    $sql .= " ORDER BY me.detected_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $events = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $events[] = formatMotionEvent($row);
    }
    
    // Get total count
    $countSql = str_replace("SELECT me.*, c.camera_name, c.location", "SELECT COUNT(*) as total", $sql);
    $countSql = preg_replace('/LIMIT.+$/', '', $countSql);
    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $total = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * Clear old motion events
 */
function clearMotionEvents() {
    global $db, $userId, $snapshotDir, $thumbnailDir;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? null;
    $daysOld = max(1, (int)($input['days_old'] ?? 30));
    
    // Get events to delete (for file cleanup)
    $sql = "SELECT me.id, me.snapshot_path, me.thumbnail_path 
            FROM motion_events me 
            JOIN cameras c ON me.camera_id = c.camera_id 
            WHERE c.user_id = :uid 
            AND me.detected_at < datetime('now', '-' || :days || ' days')";
    
    if ($cameraId) {
        $sql .= " AND me.camera_id = :cid";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':days', $daysOld, SQLITE3_INTEGER);
    if ($cameraId) {
        $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    }
    
    $result = $stmt->execute();
    $deletedCount = 0;
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Delete snapshot file
        if ($row['snapshot_path'] && file_exists($snapshotDir . '/' . $row['snapshot_path'])) {
            unlink($snapshotDir . '/' . $row['snapshot_path']);
        }
        // Delete thumbnail file
        if ($row['thumbnail_path'] && file_exists($thumbnailDir . '/' . $row['thumbnail_path'])) {
            unlink($thumbnailDir . '/' . $row['thumbnail_path']);
        }
        $deletedCount++;
    }
    
    // Delete from database
    $sql = "DELETE FROM motion_events WHERE id IN (
                SELECT me.id FROM motion_events me 
                JOIN cameras c ON me.camera_id = c.camera_id 
                WHERE c.user_id = :uid 
                AND me.detected_at < datetime('now', '-' || :days || ' days')";
    if ($cameraId) {
        $sql .= " AND me.camera_id = :cid";
    }
    $sql .= ")";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':days', $daysOld, SQLITE3_INTEGER);
    if ($cameraId) {
        $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    }
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'message' => "Deleted $deletedCount events older than $daysOld days"
    ]);
}

/**
 * Get detection zones for a camera
 */
function getDetectionZones() {
    global $db, $userId;
    
    $cameraId = $_GET['camera_id'] ?? '';
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Get zones
    $stmt = $db->prepare("SELECT * FROM motion_zones WHERE camera_id = :cid ORDER BY zone_name");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $zones = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $zones[] = [
            'id' => $row['id'],
            'zone_name' => $row['zone_name'],
            'coordinates' => json_decode($row['coordinates'], true),
            'enabled' => (bool)$row['enabled'],
            'sensitivity' => (int)$row['sensitivity']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'camera_id' => $cameraId,
        'zones' => $zones
    ]);
}

/**
 * Set detection zones
 */
function setDetectionZones() {
    global $db, $userId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $zones = $input['zones'] ?? [];
    
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Clear existing zones
    $stmt = $db->prepare("DELETE FROM motion_zones WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Insert new zones
    $insertStmt = $db->prepare("INSERT INTO motion_zones (camera_id, zone_name, coordinates, enabled, sensitivity) 
                                VALUES (:cid, :name, :coords, :enabled, :sens)");
    
    foreach ($zones as $zone) {
        $insertStmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
        $insertStmt->bindValue(':name', $zone['zone_name'] ?? 'Zone', SQLITE3_TEXT);
        $insertStmt->bindValue(':coords', json_encode($zone['coordinates'] ?? []), SQLITE3_TEXT);
        $insertStmt->bindValue(':enabled', ($zone['enabled'] ?? true) ? 1 : 0, SQLITE3_INTEGER);
        $insertStmt->bindValue(':sens', (int)($zone['sensitivity'] ?? 25), SQLITE3_INTEGER);
        $insertStmt->execute();
        $insertStmt->reset();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Zones updated',
        'zone_count' => count($zones)
    ]);
}

/**
 * Test motion detection on a single frame
 */
function testMotionDetection() {
    global $db, $userId, $snapshotDir;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    
    if (!$cameraId) {
        echo json_encode(['success' => false, 'error' => 'Camera ID required']);
        return;
    }
    
    // Get camera
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :cid AND user_id = :uid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $camera = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$camera) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    $rtspUrl = buildRTSPUrl($camera);
    
    // Capture two frames 1 second apart
    $frame1 = $snapshotDir . "/test_{$cameraId}_1.jpg";
    $frame2 = $snapshotDir . "/test_{$cameraId}_2.jpg";
    
    // Capture first frame
    $cmd1 = "ffmpeg -rtsp_transport tcp -i '$rtspUrl' -frames:v 1 -y '$frame1' 2>/dev/null";
    exec($cmd1);
    
    sleep(1);
    
    // Capture second frame
    $cmd2 = "ffmpeg -rtsp_transport tcp -i '$rtspUrl' -frames:v 1 -y '$frame2' 2>/dev/null";
    exec($cmd2);
    
    if (!file_exists($frame1) || !file_exists($frame2)) {
        echo json_encode(['success' => false, 'error' => 'Failed to capture frames']);
        return;
    }
    
    // Compare frames using ImageMagick if available, or simple PHP comparison
    $motionDetected = false;
    $changePercent = 0;
    
    if (function_exists('imagecreatefromjpeg')) {
        $img1 = imagecreatefromjpeg($frame1);
        $img2 = imagecreatefromjpeg($frame2);
        
        if ($img1 && $img2) {
            $width = imagesx($img1);
            $height = imagesy($img1);
            $totalPixels = $width * $height;
            $changedPixels = 0;
            
            // Sample pixels (every 10th pixel for speed)
            for ($y = 0; $y < $height; $y += 10) {
                for ($x = 0; $x < $width; $x += 10) {
                    $rgb1 = imagecolorat($img1, $x, $y);
                    $rgb2 = imagecolorat($img2, $x, $y);
                    
                    $r1 = ($rgb1 >> 16) & 0xFF;
                    $g1 = ($rgb1 >> 8) & 0xFF;
                    $b1 = $rgb1 & 0xFF;
                    
                    $r2 = ($rgb2 >> 16) & 0xFF;
                    $g2 = ($rgb2 >> 8) & 0xFF;
                    $b2 = $rgb2 & 0xFF;
                    
                    // Calculate difference
                    $diff = abs($r1 - $r2) + abs($g1 - $g2) + abs($b1 - $b2);
                    if ($diff > 50) { // Threshold
                        $changedPixels++;
                    }
                }
            }
            
            $sampledPixels = ($width / 10) * ($height / 10);
            $changePercent = round(($changedPixels / $sampledPixels) * 100, 2);
            $motionDetected = $changePercent > 5; // 5% change threshold
            
            imagedestroy($img1);
            imagedestroy($img2);
        }
    }
    
    // Clean up test frames
    @unlink($frame1);
    @unlink($frame2);
    
    echo json_encode([
        'success' => true,
        'motion_detected' => $motionDetected,
        'change_percent' => $changePercent,
        'message' => $motionDetected ? 'Motion detected!' : 'No motion detected'
    ]);
}

/**
 * Helper: Get default configuration
 */
function getDefaultConfig() {
    global $defaultConfig;
    return $defaultConfig;
}

/**
 * Helper: Build RTSP URL from camera data
 */
function buildRTSPUrl($camera) {
    $ip = $camera['local_ip'];
    $port = $camera['rtsp_port'] ?? 554;
    $path = $camera['rtsp_path'] ?? '/live/ch0';
    $user = $camera['rtsp_user'] ?? '';
    $pass = $camera['rtsp_pass'] ?? '';
    
    if ($user && $pass) {
        return "rtsp://{$user}:{$pass}@{$ip}:{$port}{$path}";
    } elseif ($user) {
        return "rtsp://{$user}@{$ip}:{$port}{$path}";
    }
    return "rtsp://{$ip}:{$port}{$path}";
}

/**
 * Helper: Format motion event for output
 */
function formatMotionEvent($row) {
    return [
        'id' => (int)$row['id'],
        'camera_id' => $row['camera_id'],
        'camera_name' => $row['camera_name'] ?? null,
        'location' => $row['location'] ?? null,
        'detected_at' => $row['detected_at'],
        'confidence' => (float)($row['confidence'] ?? 0),
        'snapshot_url' => $row['snapshot_path'] ? '/motion/snapshots/' . $row['snapshot_path'] : null,
        'thumbnail_url' => $row['thumbnail_path'] ? '/motion/thumbnails/' . $row['thumbnail_path'] : null,
        'recording_id' => $row['recording_id'] ?? null,
        'zone_name' => $row['zone_name'] ?? null,
        'bounding_box' => $row['bounding_box'] ? json_decode($row['bounding_box'], true) : null
    ];
}

/**
 * Helper: Get last motion event for a camera
 */
function getLastMotionEvent($cameraId) {
    global $db;
    
    $stmt = $db->prepare("SELECT * FROM motion_events WHERE camera_id = :cid ORDER BY detected_at DESC LIMIT 1");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    return $row ? formatMotionEvent($row) : null;
}

/**
 * Helper: Start motion detector background process
 */
function startMotionDetector($cameraId, $rtspUrl) {
    global $pidDir;
    
    // Check if already running
    $pidFile = $pidDir . "/motion_{$cameraId}.pid";
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        if (PHP_OS_FAMILY !== 'Windows' && file_exists("/proc/$pid")) {
            return true; // Already running
        }
    }
    
    // Build detector command (Python script or ffmpeg-based)
    // For now, we'll use a simple PHP-based polling approach
    // In production, this would be a dedicated Python motion detector
    
    $scriptPath = __DIR__ . '/motion-detector.php';
    $logFile = $pidDir . "/motion_{$cameraId}.log";
    
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows: Start background PHP process
        $cmd = "start /B php \"$scriptPath\" \"$cameraId\" \"$rtspUrl\" > \"$logFile\" 2>&1";
        pclose(popen($cmd, 'r'));
    } else {
        // Unix: Start background process
        $cmd = "nohup php '$scriptPath' '$cameraId' '$rtspUrl' > '$logFile' 2>&1 & echo $!";
        $pid = trim(shell_exec($cmd));
        if ($pid) {
            file_put_contents($pidFile, $pid);
        }
    }
    
    return true;
}

/**
 * Helper: Stop motion detector process
 */
function stopMotionDetector($cameraId) {
    global $pidDir;
    
    $pidFile = $pidDir . "/motion_{$cameraId}.pid";
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /PID $pid /F 2>NUL");
        } else {
            posix_kill($pid, SIGTERM);
            usleep(500000); // 0.5 second
            if (file_exists("/proc/$pid")) {
                posix_kill($pid, SIGKILL);
            }
        }
        
        @unlink($pidFile);
    }
    
    return true;
}
