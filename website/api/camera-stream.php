<?php
/**
 * Camera Stream API - Task 6A.7
 * URL: /api/camera-stream.php?camera_id=xxx
 * 
 * Converts RTSP to HLS for browser playback
 * Uses FFmpeg to transcode RTSP streams to HLS segments
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';

// Verify authentication
session_start();
$userId = $_SESSION['user_id'] ?? null;

// Also accept JWT token
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
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized']));
}

$cameraId = $_GET['camera_id'] ?? '';
$quality = $_GET['quality'] ?? '720'; // 1080, 720, 480

if (empty($cameraId)) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Missing camera_id parameter']));
}

// Get camera from database
$db = new SQLite3(DB_PATH . '/devices.db');
$stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :id AND user_id = :user_id");
$stmt->bindValue(':id', $cameraId, SQLITE3_TEXT);
$stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
$result = $stmt->execute();
$camera = $result->fetchArray(SQLITE3_ASSOC);
$db->close();

if (!$camera) {
    http_response_code(404);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Camera not found or access denied']));
}

// Build RTSP URL
$rtspUrl = buildRTSPUrl($camera);

// Output directory for HLS segments
$streamDir = sys_get_temp_dir() . "/truevault_streams/{$userId}/{$cameraId}/";
if (!is_dir($streamDir)) {
    mkdir($streamDir, 0755, true);
}

$playlist = "{$streamDir}stream.m3u8";
$pidFile = "{$streamDir}ffmpeg.pid";

// Check if FFmpeg process is already running
$isRunning = false;
if (file_exists($pidFile)) {
    $pid = trim(file_get_contents($pidFile));
    if ($pid && isProcessRunning($pid)) {
        $isRunning = true;
    }
}

// Start FFmpeg if not running
if (!$isRunning) {
    // Clean old segments
    array_map('unlink', glob("{$streamDir}*.ts"));
    if (file_exists($playlist)) unlink($playlist);
    
    // Quality settings
    $qualityParams = getQualityParams($quality);
    
    // Build FFmpeg command
    $cmd = buildFFmpegCommand($rtspUrl, $playlist, $qualityParams);
    
    // Execute FFmpeg in background
    $pid = exec($cmd);
    if ($pid) {
        file_put_contents($pidFile, $pid);
    }
    
    // Wait for playlist to be created (max 5 seconds)
    $waited = 0;
    while (!file_exists($playlist) && $waited < 5000) {
        usleep(100000); // 100ms
        $waited += 100;
    }
}

// Serve the playlist or segment
$requestedFile = $_GET['file'] ?? 'stream.m3u8';

if ($requestedFile === 'stream.m3u8') {
    // Serve HLS playlist
    if (file_exists($playlist)) {
        header('Content-Type: application/vnd.apple.mpegurl');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Access-Control-Allow-Origin: *');
        
        // Rewrite segment URLs to include camera_id
        $content = file_get_contents($playlist);
        $content = preg_replace('/^(stream\d+\.ts)$/m', 
            "/api/camera-stream.php?camera_id={$cameraId}&file=$1", 
            $content);
        echo $content;
    } else {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Stream not ready', 'retry' => true]);
    }
} else {
    // Serve HLS segment
    $segmentPath = $streamDir . basename($requestedFile);
    
    if (file_exists($segmentPath) && preg_match('/^stream\d+\.ts$/', basename($requestedFile))) {
        header('Content-Type: video/mp2t');
        header('Cache-Control: max-age=3600');
        header('Access-Control-Allow-Origin: *');
        readfile($segmentPath);
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Segment not found']);
    }
}

// ============== HELPER FUNCTIONS ==============

function buildRTSPUrl($camera) {
    $username = $camera['rtsp_username'] ?? '';
    $password = $camera['rtsp_password'] ?? '';
    $ip = $camera['local_ip'] ?? '';
    $port = $camera['rtsp_port'] ?? 554;
    $path = $camera['rtsp_path'] ?? '/stream';
    
    // Handle stored RTSP URL
    if (!empty($camera['rtsp_url'])) {
        return $camera['rtsp_url'];
    }
    
    // Build URL with credentials
    if (!empty($username)) {
        return "rtsp://{$username}:{$password}@{$ip}:{$port}{$path}";
    }
    
    return "rtsp://{$ip}:{$port}{$path}";
}

function getQualityParams($quality) {
    $params = [
        '1080' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '4000k',
            'fps' => 30
        ],
        '720' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2500k',
            'fps' => 30
        ],
        '480' => [
            'width' => 854,
            'height' => 480,
            'bitrate' => '1000k',
            'fps' => 25
        ],
        '360' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => '600k',
            'fps' => 20
        ]
    ];
    
    return $params[$quality] ?? $params['720'];
}

function buildFFmpegCommand($rtspUrl, $playlist, $quality) {
    $w = $quality['width'];
    $h = $quality['height'];
    $bitrate = $quality['bitrate'];
    $fps = $quality['fps'];
    
    // FFmpeg command for RTSP to HLS conversion
    // -rtsp_transport tcp: Use TCP for more reliable streaming
    // -fflags nobuffer: Reduce latency
    // -flags low_delay: Low latency mode
    // -hls_time 2: 2-second segments
    // -hls_list_size 3: Keep only 3 segments in playlist
    // -hls_flags delete_segments: Clean up old segments
    
    $cmd = "ffmpeg -rtsp_transport tcp -fflags nobuffer -flags low_delay " .
           "-i '{$rtspUrl}' " .
           "-vf 'scale={$w}:{$h}' " .
           "-c:v libx264 -preset ultrafast -tune zerolatency " .
           "-b:v {$bitrate} -maxrate {$bitrate} -bufsize 1000k " .
           "-r {$fps} " .
           "-c:a aac -b:a 128k " .
           "-f hls -hls_time 2 -hls_list_size 3 " .
           "-hls_flags delete_segments+append_list " .
           "-hls_segment_filename '" . dirname($playlist) . "/stream%d.ts' " .
           "'{$playlist}' " .
           "> /dev/null 2>&1 & echo $!";
    
    return $cmd;
}

function isProcessRunning($pid) {
    if (empty($pid)) return false;
    
    // Check if process is running (cross-platform)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $result = shell_exec("tasklist /FI \"PID eq {$pid}\" 2>NUL");
        return strpos($result, (string)$pid) !== false;
    } else {
        return file_exists("/proc/{$pid}");
    }
}

// ============== STREAM MANAGEMENT ==============

// Stop stream endpoint
if (isset($_GET['action']) && $_GET['action'] === 'stop') {
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /PID {$pid}");
            } else {
                exec("kill {$pid}");
            }
        }
        unlink($pidFile);
    }
    
    // Clean up segments
    array_map('unlink', glob("{$streamDir}*.ts"));
    if (file_exists($playlist)) unlink($playlist);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Stream stopped']);
    exit;
}

// Stream status endpoint
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    $status = [
        'camera_id' => $cameraId,
        'running' => false,
        'playlist_ready' => file_exists($playlist),
        'segments' => 0
    ];
    
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        $status['running'] = isProcessRunning($pid);
        $status['pid'] = $pid;
    }
    
    $status['segments'] = count(glob("{$streamDir}*.ts"));
    
    header('Content-Type: application/json');
    echo json_encode($status);
    exit;
}
