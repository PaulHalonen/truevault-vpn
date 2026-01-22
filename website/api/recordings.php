<?php
/**
 * Recording System API - Task 6A.9
 * Manages camera recordings with multiple modes
 * 
 * Endpoints:
 * GET  /api/recordings.php?action=list&camera_id=xxx
 * GET  /api/recordings.php?action=get&recording_id=xxx
 * POST /api/recordings.php?action=start
 * POST /api/recordings.php?action=stop
 * POST /api/recordings.php?action=delete
 * GET  /api/recordings.php?action=storage_stats
 * POST /api/recordings.php?action=set_mode
 * POST /api/recordings.php?action=set_schedule
 * GET  /api/recordings.php?action=download&recording_id=xxx
 * POST /api/recordings.php?action=share
 * GET  /api/recordings.php?action=shared&token=xxx
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Recording modes
define('RECORDING_MODE_MANUAL', 'manual');
define('RECORDING_MODE_CONTINUOUS', 'continuous');
define('RECORDING_MODE_MOTION', 'motion');
define('RECORDING_MODE_SCHEDULED', 'scheduled');

// Storage settings
define('RECORDINGS_DIR', __DIR__ . '/../recordings/');
define('CLIPS_DIR', RECORDINGS_DIR . 'clips/');
define('THUMBNAILS_DIR', RECORDINGS_DIR . 'thumbnails/');
define('MAX_STORAGE_GB', 50); // Default max storage per user

// Ensure directories exist
if (!is_dir(CLIPS_DIR)) mkdir(CLIPS_DIR, 0755, true);
if (!is_dir(THUMBNAILS_DIR)) mkdir(THUMBNAILS_DIR, 0755, true);

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

// Allow shared link access without auth
$action = $_GET['action'] ?? '';
if ($action !== 'shared' && !$userId) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

switch ($action) {
    case 'list':
        listRecordings($userId);
        break;
    case 'get':
        getRecording($userId);
        break;
    case 'start':
        startRecording($userId);
        break;
    case 'stop':
        stopRecording($userId);
        break;
    case 'delete':
        deleteRecording($userId);
        break;
    case 'storage_stats':
        getStorageStats($userId);
        break;
    case 'set_mode':
        setRecordingMode($userId);
        break;
    case 'set_schedule':
        setRecordingSchedule($userId);
        break;
    case 'download':
        downloadRecording($userId);
        break;
    case 'share':
        shareRecording($userId);
        break;
    case 'shared':
        getSharedRecording();
        break;
    case 'cleanup':
        cleanupOldRecordings($userId);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

// ============== LIST RECORDINGS ==============
function listRecordings($userId) {
    $cameraId = $_GET['camera_id'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $mode = $_GET['mode'] ?? null;
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    $sql = "SELECT r.*, c.camera_name, c.location 
            FROM camera_recordings r 
            JOIN cameras c ON r.camera_id = c.camera_id 
            WHERE r.user_id = :user_id";
    $params = [':user_id' => $userId];
    
    if ($cameraId) {
        $sql .= " AND r.camera_id = :camera_id";
        $params[':camera_id'] = $cameraId;
    }
    
    if ($dateFrom) {
        $sql .= " AND r.start_time >= :date_from";
        $params[':date_from'] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND r.start_time <= :date_to";
        $params[':date_to'] = $dateTo;
    }
    
    if ($mode) {
        $sql .= " AND r.recording_mode = :mode";
        $params[':mode'] = $mode;
    }
    
    $sql .= " ORDER BY r.start_time DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    
    $result = $stmt->execute();
    $recordings = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $recordings[] = formatRecording($row);
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM camera_recordings WHERE user_id = :user_id";
    if ($cameraId) $countSql .= " AND camera_id = '{$cameraId}'";
    $total = $db->querySingle($countSql);
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'recordings' => $recordings,
        'count' => count($recordings),
        'total' => $total,
        'offset' => $offset,
        'limit' => $limit
    ]);
}

// ============== GET SINGLE RECORDING ==============
function getRecording($userId) {
    $recordingId = $_GET['recording_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT r.*, c.camera_name, c.location 
                          FROM camera_recordings r 
                          JOIN cameras c ON r.camera_id = c.camera_id 
                          WHERE r.id = :id AND r.user_id = :user_id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $recording = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$recording) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recording not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'recording' => formatRecording($recording)
    ]);
}

// ============== START RECORDING ==============
function startRecording($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $mode = $input['mode'] ?? RECORDING_MODE_MANUAL;
    $duration = $input['duration'] ?? null; // Max duration in seconds
    
    // Verify camera ownership
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $camera = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$camera) {
        $db->close();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Check if already recording
    $stmt = $db->prepare("SELECT id FROM camera_recordings WHERE camera_id = :id AND end_time IS NULL");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result->fetchArray()) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Recording already in progress']);
        return;
    }
    
    // Check storage quota
    $usedStorage = getUserStorageUsed($userId);
    if ($usedStorage >= MAX_STORAGE_GB * 1024 * 1024 * 1024) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Storage quota exceeded']);
        return;
    }
    
    // Generate filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "{$cameraId}_{$timestamp}.mp4";
    $filepath = CLIPS_DIR . $filename;
    $thumbnailName = "{$cameraId}_{$timestamp}.jpg";
    
    // Build RTSP URL
    $rtspUrl = buildRTSPUrl($camera);
    
    // Start FFmpeg recording process
    $durationArg = $duration ? "-t {$duration}" : "";
    $cmd = "ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' {$durationArg} " .
           "-c:v copy -c:a aac -movflags +faststart " .
           "'{$filepath}' > /dev/null 2>&1 & echo $!";
    
    $pid = trim(shell_exec($cmd));
    
    if (!$pid || !is_numeric($pid)) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Failed to start recording']);
        return;
    }
    
    // Store PID for later stopping
    $pidFile = "/tmp/recording_{$cameraId}.pid";
    file_put_contents($pidFile, $pid);
    
    // Insert recording record
    $stmt = $db->prepare("INSERT INTO camera_recordings (
        camera_id, user_id, filename, thumbnail, recording_mode, start_time, created_at
    ) VALUES (
        :camera_id, :user_id, :filename, :thumbnail, :mode, datetime('now'), datetime('now')
    )");
    $stmt->bindValue(':camera_id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
    $stmt->bindValue(':thumbnail', $thumbnailName, SQLITE3_TEXT);
    $stmt->bindValue(':mode', $mode, SQLITE3_TEXT);
    $stmt->execute();
    
    $recordingId = $db->lastInsertRowID();
    $db->close();
    
    // Generate thumbnail after short delay
    $thumbCmd = "sleep 3 && ffmpeg -rtsp_transport tcp -i '{$rtspUrl}' -frames:v 1 -y '" . THUMBNAILS_DIR . "{$thumbnailName}' > /dev/null 2>&1 &";
    shell_exec($thumbCmd);
    
    echo json_encode([
        'success' => true,
        'recording_id' => $recordingId,
        'filename' => $filename,
        'message' => 'Recording started'
    ]);
}

// ============== STOP RECORDING ==============
function stopRecording($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $recordingId = $input['recording_id'] ?? null;
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Find active recording
    if ($recordingId) {
        $stmt = $db->prepare("SELECT * FROM camera_recordings WHERE id = :id AND user_id = :user_id AND end_time IS NULL");
        $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    } else {
        $stmt = $db->prepare("SELECT * FROM camera_recordings WHERE camera_id = :camera_id AND user_id = :user_id AND end_time IS NULL");
        $stmt->bindValue(':camera_id', $cameraId, SQLITE3_TEXT);
    }
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $recording = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$recording) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'No active recording found']);
        return;
    }
    
    // Stop FFmpeg process
    $pidFile = "/tmp/recording_{$recording['camera_id']}.pid";
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid && is_numeric($pid)) {
            // Send SIGTERM for graceful stop
            posix_kill((int)$pid, SIGTERM);
            sleep(1);
            // Force kill if still running
            if (posix_kill((int)$pid, 0)) {
                posix_kill((int)$pid, SIGKILL);
            }
        }
        unlink($pidFile);
    }
    
    // Calculate file size and duration
    $filepath = CLIPS_DIR . $recording['filename'];
    $fileSize = file_exists($filepath) ? filesize($filepath) : 0;
    $duration = getVideoDuration($filepath);
    
    // Update recording record
    $stmt = $db->prepare("UPDATE camera_recordings SET 
        end_time = datetime('now'), 
        file_size = :size, 
        duration = :duration 
        WHERE id = :id");
    $stmt->bindValue(':size', $fileSize, SQLITE3_INTEGER);
    $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $recording['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'recording_id' => $recording['id'],
        'filename' => $recording['filename'],
        'file_size' => $fileSize,
        'duration' => $duration,
        'message' => 'Recording stopped'
    ]);
}

// ============== DELETE RECORDING ==============
function deleteRecording($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $recordingId = $input['recording_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Get recording info
    $stmt = $db->prepare("SELECT * FROM camera_recordings WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $recording = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$recording) {
        $db->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recording not found']);
        return;
    }
    
    // Delete files
    $clipPath = CLIPS_DIR . $recording['filename'];
    $thumbPath = THUMBNAILS_DIR . $recording['thumbnail'];
    
    if (file_exists($clipPath)) unlink($clipPath);
    if (file_exists($thumbPath)) unlink($thumbPath);
    
    // Delete database record
    $stmt = $db->prepare("DELETE FROM camera_recordings WHERE id = :id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->execute();
    
    // Also delete any share links
    $stmt = $db->prepare("DELETE FROM recording_shares WHERE recording_id = :id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->close();
    
    echo json_encode(['success' => true, 'message' => 'Recording deleted']);
}

// ============== GET STORAGE STATS ==============
function getStorageStats($userId) {
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Total used storage
    $stmt = $db->prepare("SELECT SUM(file_size) as total_size, COUNT(*) as total_count FROM camera_recordings WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $stats = $result->fetchArray(SQLITE3_ASSOC);
    
    // Per camera storage
    $stmt = $db->prepare("SELECT camera_id, SUM(file_size) as size, COUNT(*) as count 
                          FROM camera_recordings WHERE user_id = :user_id 
                          GROUP BY camera_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $perCamera = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $perCamera[$row['camera_id']] = [
            'size' => (int)$row['size'],
            'count' => (int)$row['count']
        ];
    }
    
    $db->close();
    
    $usedBytes = (int)($stats['total_size'] ?? 0);
    $maxBytes = MAX_STORAGE_GB * 1024 * 1024 * 1024;
    
    echo json_encode([
        'success' => true,
        'storage' => [
            'used_bytes' => $usedBytes,
            'used_gb' => round($usedBytes / (1024 * 1024 * 1024), 2),
            'max_bytes' => $maxBytes,
            'max_gb' => MAX_STORAGE_GB,
            'percentage_used' => round(($usedBytes / $maxBytes) * 100, 1),
            'total_recordings' => (int)($stats['total_count'] ?? 0),
            'per_camera' => $perCamera
        ]
    ]);
}

// ============== SET RECORDING MODE ==============
function setRecordingMode($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $mode = $input['mode'] ?? RECORDING_MODE_MANUAL;
    
    $validModes = [RECORDING_MODE_MANUAL, RECORDING_MODE_CONTINUOUS, RECORDING_MODE_MOTION, RECORDING_MODE_SCHEDULED];
    if (!in_array($mode, $validModes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid recording mode']);
        return;
    }
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    $stmt = $db->prepare("UPDATE cameras SET recording_mode = :mode WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':mode', $mode, SQLITE3_TEXT);
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $changes = $db->changes();
    $db->close();
    
    if ($changes === 0) {
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'mode' => $mode,
        'message' => "Recording mode set to {$mode}"
    ]);
}

// ============== SET RECORDING SCHEDULE ==============
function setRecordingSchedule($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $cameraId = $input['camera_id'] ?? '';
    $schedule = $input['schedule'] ?? []; // Array of {day, start_time, end_time}
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Verify camera ownership
    $stmt = $db->prepare("SELECT camera_id FROM cameras WHERE camera_id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        $db->close();
        echo json_encode(['success' => false, 'error' => 'Camera not found']);
        return;
    }
    
    // Delete existing schedule
    $stmt = $db->prepare("DELETE FROM recording_schedules WHERE camera_id = :id");
    $stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
    $stmt->execute();
    
    // Insert new schedule
    foreach ($schedule as $slot) {
        $stmt = $db->prepare("INSERT INTO recording_schedules (camera_id, day_of_week, start_time, end_time) 
                              VALUES (:camera_id, :day, :start, :end)");
        $stmt->bindValue(':camera_id', $cameraId, SQLITE3_TEXT);
        $stmt->bindValue(':day', $slot['day'], SQLITE3_INTEGER);
        $stmt->bindValue(':start', $slot['start_time'], SQLITE3_TEXT);
        $stmt->bindValue(':end', $slot['end_time'], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule updated',
        'schedule' => $schedule
    ]);
}

// ============== DOWNLOAD RECORDING ==============
function downloadRecording($userId) {
    $recordingId = $_GET['recording_id'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT * FROM camera_recordings WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $recording = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$recording) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recording not found']);
        return;
    }
    
    $filepath = CLIPS_DIR . $recording['filename'];
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'File not found']);
        return;
    }
    
    // Stream file download
    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . $recording['filename'] . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    
    readfile($filepath);
    exit;
}

// ============== SHARE RECORDING ==============
function shareRecording($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $recordingId = $input['recording_id'] ?? '';
    $expiresIn = $input['expires_in'] ?? 24; // Hours
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Verify recording ownership
    $stmt = $db->prepare("SELECT * FROM camera_recordings WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $recordingId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $recording = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$recording) {
        $db->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recording not found']);
        return;
    }
    
    // Generate share token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresIn} hours"));
    
    $stmt = $db->prepare("INSERT INTO recording_shares (recording_id, share_token, expires_at, created_at) 
                          VALUES (:recording_id, :token, :expires, datetime('now'))");
    $stmt->bindValue(':recording_id', $recordingId, SQLITE3_INTEGER);
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->bindValue(':expires', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
    
    $db->close();
    
    $shareUrl = "https://vpn.the-truth-publishing.com/api/recordings.php?action=shared&token={$token}";
    
    echo json_encode([
        'success' => true,
        'share_url' => $shareUrl,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
}

// ============== GET SHARED RECORDING ==============
function getSharedRecording() {
    $token = $_GET['token'] ?? '';
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT s.*, r.filename, r.camera_id, r.duration 
                          FROM recording_shares s 
                          JOIN camera_recordings r ON s.recording_id = r.id 
                          WHERE s.share_token = :token AND s.expires_at > datetime('now')");
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $result = $stmt->execute();
    $share = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
    
    if (!$share) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Share link invalid or expired']);
        return;
    }
    
    $filepath = CLIPS_DIR . $share['filename'];
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recording not found']);
        return;
    }
    
    // Stream video
    header('Content-Type: video/mp4');
    header('Content-Length: ' . filesize($filepath));
    header('Accept-Ranges: bytes');
    
    readfile($filepath);
    exit;
}

// ============== CLEANUP OLD RECORDINGS ==============
function cleanupOldRecordings($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $daysOld = $input['days_old'] ?? 30;
    
    $db = new SQLite3(DB_PATH . '/devices.db');
    
    // Get old recordings
    $stmt = $db->prepare("SELECT * FROM camera_recordings 
                          WHERE user_id = :user_id 
                          AND created_at < datetime('now', :days)");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':days', "-{$daysOld} days", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $deleted = 0;
    $freedBytes = 0;
    
    while ($recording = $result->fetchArray(SQLITE3_ASSOC)) {
        $clipPath = CLIPS_DIR . $recording['filename'];
        $thumbPath = THUMBNAILS_DIR . $recording['thumbnail'];
        
        if (file_exists($clipPath)) {
            $freedBytes += filesize($clipPath);
            unlink($clipPath);
        }
        if (file_exists($thumbPath)) unlink($thumbPath);
        
        $deleted++;
    }
    
    // Delete database records
    $stmt = $db->prepare("DELETE FROM camera_recordings 
                          WHERE user_id = :user_id 
                          AND created_at < datetime('now', :days)");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':days', "-{$daysOld} days", SQLITE3_TEXT);
    $stmt->execute();
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'deleted_count' => $deleted,
        'freed_bytes' => $freedBytes,
        'freed_gb' => round($freedBytes / (1024 * 1024 * 1024), 2),
        'message' => "Deleted {$deleted} recordings older than {$daysOld} days"
    ]);
}

// ============== HELPER FUNCTIONS ==============

function formatRecording($row) {
    return [
        'id' => $row['id'],
        'camera_id' => $row['camera_id'],
        'camera_name' => $row['camera_name'] ?? null,
        'location' => $row['location'] ?? null,
        'filename' => $row['filename'],
        'video_url' => "/recordings/clips/{$row['filename']}",
        'thumbnail_url' => $row['thumbnail'] ? "/recordings/thumbnails/{$row['thumbnail']}" : null,
        'file_size' => (int)($row['file_size'] ?? 0),
        'file_size_mb' => round(($row['file_size'] ?? 0) / (1024 * 1024), 2),
        'duration' => (int)($row['duration'] ?? 0),
        'duration_formatted' => formatDuration($row['duration'] ?? 0),
        'recording_mode' => $row['recording_mode'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'is_recording' => empty($row['end_time']),
        'created_at' => $row['created_at']
    ];
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%d:%02d', $minutes, $secs);
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

function getVideoDuration($filepath) {
    if (!file_exists($filepath)) return 0;
    
    $cmd = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 '{$filepath}' 2>/dev/null";
    $output = trim(shell_exec($cmd));
    
    return $output ? (int)round((float)$output) : 0;
}

function getUserStorageUsed($userId) {
    $db = new SQLite3(DB_PATH . '/devices.db');
    $stmt = $db->prepare("SELECT SUM(file_size) FROM camera_recordings WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $total = $result->fetchArray()[0] ?? 0;
    $db->close();
    return (int)$total;
}
