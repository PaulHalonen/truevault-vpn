<?php
/**
 * Motion Detector Worker - Background Process
 * Continuously monitors camera stream for motion
 * 
 * Usage: php motion-detector.php CAMERA_ID RTSP_URL
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

if ($argc < 3) {
    die("Usage: php motion-detector.php CAMERA_ID RTSP_URL\n");
}

$cameraId = $argv[1];
$rtspUrl = $argv[2];

// Configuration
$checkInterval = 2;      // Seconds between checks
$sensitivity = 25;       // Default sensitivity (1-100)
$minArea = 500;          // Minimum changed pixels
$cooldown = 10;          // Seconds between events
$maxRuntime = 86400;     // Max runtime (24 hours)

// Paths
$dbPath = __DIR__ . '/../db/truevault.db';
$snapshotDir = __DIR__ . '/../motion/snapshots';
$thumbnailDir = __DIR__ . '/../motion/thumbnails';
$pidFile = "/tmp/motion_{$cameraId}.pid";

// Store PID
file_put_contents($pidFile, getmypid());

// Ensure directories
foreach ([$snapshotDir, $thumbnailDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Database connection
$db = new SQLite3($dbPath);

// Load configuration
function loadConfig() {
    global $db, $cameraId, $sensitivity, $minArea, $cooldown;
    
    $stmt = $db->prepare("SELECT config FROM motion_config WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if ($result && $result['config']) {
        $config = json_decode($result['config'], true);
        $sensitivity = $config['sensitivity'] ?? 25;
        $minArea = $config['min_area'] ?? 500;
        $cooldown = $config['cooldown'] ?? 10;
        return $config;
    }
    
    return null;
}

// Check if motion detection is still enabled
function isEnabled() {
    global $db, $cameraId;
    
    $stmt = $db->prepare("SELECT motion_detection FROM cameras WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    return $result && $result['motion_detection'];
}

// Capture frame from RTSP stream
function captureFrame($rtspUrl, $outputPath) {
    $cmd = "ffmpeg -rtsp_transport tcp -i '$rtspUrl' -frames:v 1 -y '$outputPath' 2>/dev/null";
    exec($cmd, $output, $returnCode);
    return $returnCode === 0 && file_exists($outputPath);
}

// Compare two frames and detect motion
function detectMotion($frame1Path, $frame2Path, $sensitivity) {
    if (!file_exists($frame1Path) || !file_exists($frame2Path)) {
        return ['detected' => false, 'percent' => 0];
    }
    
    if (!function_exists('imagecreatefromjpeg')) {
        // GD not available, use ImageMagick if possible
        $cmd = "compare -metric RMSE '$frame1Path' '$frame2Path' null: 2>&1";
        $output = shell_exec($cmd);
        if (preg_match('/\(([\d.]+)\)/', $output, $matches)) {
            $diff = (float)$matches[1] * 100;
            $threshold = (100 - $sensitivity) / 10; // Convert sensitivity to threshold
            return [
                'detected' => $diff > $threshold,
                'percent' => round($diff, 2)
            ];
        }
        return ['detected' => false, 'percent' => 0];
    }
    
    // Use GD library
    $img1 = @imagecreatefromjpeg($frame1Path);
    $img2 = @imagecreatefromjpeg($frame2Path);
    
    if (!$img1 || !$img2) {
        return ['detected' => false, 'percent' => 0];
    }
    
    $width = imagesx($img1);
    $height = imagesy($img1);
    $changedPixels = 0;
    $threshold = 50 + (100 - $sensitivity); // Higher sensitivity = lower threshold
    
    // Sample every 5th pixel for speed
    $step = 5;
    for ($y = 0; $y < $height; $y += $step) {
        for ($x = 0; $x < $width; $x += $step) {
            $rgb1 = imagecolorat($img1, $x, $y);
            $rgb2 = imagecolorat($img2, $x, $y);
            
            $r1 = ($rgb1 >> 16) & 0xFF;
            $g1 = ($rgb1 >> 8) & 0xFF;
            $b1 = $rgb1 & 0xFF;
            
            $r2 = ($rgb2 >> 16) & 0xFF;
            $g2 = ($rgb2 >> 8) & 0xFF;
            $b2 = $rgb2 & 0xFF;
            
            $diff = abs($r1 - $r2) + abs($g1 - $g2) + abs($b1 - $b2);
            if ($diff > $threshold) {
                $changedPixels++;
            }
        }
    }
    
    imagedestroy($img1);
    imagedestroy($img2);
    
    $sampledPixels = ($width / $step) * ($height / $step);
    $changePercent = ($changedPixels / $sampledPixels) * 100;
    
    // Motion threshold: 2% of pixels changed = motion
    $motionThreshold = 2 + (100 - $sensitivity) / 20; // Adjust based on sensitivity
    
    return [
        'detected' => $changePercent > $motionThreshold,
        'percent' => round($changePercent, 2),
        'changed_pixels' => $changedPixels
    ];
}

// Log motion event to database
function logMotionEvent($cameraId, $confidence, $snapshotPath, $thumbnailPath) {
    global $db;
    
    $stmt = $db->prepare("INSERT INTO motion_events 
        (camera_id, detected_at, confidence, snapshot_path, thumbnail_path) 
        VALUES (:cid, datetime('now'), :conf, :snap, :thumb)");
    
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':conf', $confidence, SQLITE3_FLOAT);
    $stmt->bindValue(':snap', $snapshotPath, SQLITE3_TEXT);
    $stmt->bindValue(':thumb', $thumbnailPath, SQLITE3_TEXT);
    
    return $stmt->execute();
}

// Create thumbnail from snapshot
function createThumbnail($sourcePath, $thumbPath, $maxWidth = 320) {
    if (!function_exists('imagecreatefromjpeg')) {
        // Fallback: use ffmpeg
        $cmd = "ffmpeg -i '$sourcePath' -vf 'scale=$maxWidth:-1' -y '$thumbPath' 2>/dev/null";
        exec($cmd);
        return file_exists($thumbPath);
    }
    
    $source = @imagecreatefromjpeg($sourcePath);
    if (!$source) return false;
    
    $width = imagesx($source);
    $height = imagesy($source);
    $newHeight = (int)($height * ($maxWidth / $width));
    
    $thumb = imagecreatetruecolor($maxWidth, $newHeight);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
    
    $result = imagejpeg($thumb, $thumbPath, 85);
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $result;
}

// Trigger recording on motion
function triggerRecording($cameraId, $duration) {
    global $db;
    
    // Get camera info
    $stmt = $db->prepare("SELECT * FROM cameras WHERE camera_id = :cid");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $camera = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if (!$camera) return null;
    
    // Build RTSP URL
    $ip = $camera['local_ip'];
    $port = $camera['rtsp_port'] ?? 554;
    $path = $camera['rtsp_path'] ?? '/live/ch0';
    $user = $camera['rtsp_user'] ?? '';
    $pass = $camera['rtsp_pass'] ?? '';
    
    if ($user && $pass) {
        $rtspUrl = "rtsp://{$user}:{$pass}@{$ip}:{$port}{$path}";
    } else {
        $rtspUrl = "rtsp://{$ip}:{$port}{$path}";
    }
    
    // Start recording
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "{$cameraId}_{$timestamp}_motion.mp4";
    $outputPath = __DIR__ . "/../recordings/clips/$filename";
    
    $cmd = "ffmpeg -rtsp_transport tcp -i '$rtspUrl' -t $duration -c:v copy -c:a aac -movflags +faststart '$outputPath' > /dev/null 2>&1 &";
    exec($cmd);
    
    // Log recording
    $stmt = $db->prepare("INSERT INTO recordings 
        (camera_id, filename, file_path, recording_mode, start_time, status) 
        VALUES (:cid, :fname, :fpath, 'motion', datetime('now'), 'recording')");
    $stmt->bindValue(':cid', $cameraId, SQLITE3_TEXT);
    $stmt->bindValue(':fname', $filename, SQLITE3_TEXT);
    $stmt->bindValue(':fpath', "clips/$filename", SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

// Main detection loop
function runDetector() {
    global $cameraId, $rtspUrl, $checkInterval, $cooldown, $maxRuntime;
    global $snapshotDir, $thumbnailDir;
    
    $startTime = time();
    $lastEventTime = 0;
    $prevFramePath = null;
    $frameCounter = 0;
    
    echo "[" . date('Y-m-d H:i:s') . "] Motion detector started for camera: $cameraId\n";
    echo "[" . date('Y-m-d H:i:s') . "] RTSP URL: $rtspUrl\n";
    
    while (true) {
        // Check runtime limit
        if (time() - $startTime > $maxRuntime) {
            echo "[" . date('Y-m-d H:i:s') . "] Max runtime reached, exiting\n";
            break;
        }
        
        // Check if still enabled
        if (!isEnabled()) {
            echo "[" . date('Y-m-d H:i:s') . "] Motion detection disabled, exiting\n";
            break;
        }
        
        // Reload config periodically
        if ($frameCounter % 30 === 0) {
            loadConfig();
        }
        
        // Capture current frame
        $frameCounter++;
        $currentFramePath = "/tmp/motion_{$cameraId}_current.jpg";
        
        if (!captureFrame($rtspUrl, $currentFramePath)) {
            echo "[" . date('Y-m-d H:i:s') . "] Failed to capture frame, retrying...\n";
            sleep($checkInterval);
            continue;
        }
        
        // Compare with previous frame
        if ($prevFramePath && file_exists($prevFramePath)) {
            global $sensitivity;
            $result = detectMotion($prevFramePath, $currentFramePath, $sensitivity);
            
            if ($result['detected']) {
                $now = time();
                
                // Check cooldown
                if ($now - $lastEventTime >= $cooldown) {
                    echo "[" . date('Y-m-d H:i:s') . "] MOTION DETECTED! Change: {$result['percent']}%\n";
                    
                    // Save snapshot
                    $timestamp = date('Y-m-d_H-i-s');
                    $snapshotName = "{$cameraId}_{$timestamp}.jpg";
                    $thumbnailName = "{$cameraId}_{$timestamp}_thumb.jpg";
                    
                    copy($currentFramePath, "$snapshotDir/$snapshotName");
                    createThumbnail("$snapshotDir/$snapshotName", "$thumbnailDir/$thumbnailName");
                    
                    // Log event
                    logMotionEvent($cameraId, $result['percent'], $snapshotName, $thumbnailName);
                    
                    // Trigger recording if enabled
                    $config = loadConfig();
                    if ($config && ($config['recording_enabled'] ?? true)) {
                        $duration = $config['record_duration'] ?? 30;
                        triggerRecording($cameraId, $duration);
                    }
                    
                    $lastEventTime = $now;
                }
            }
        }
        
        // Rotate frames
        if ($prevFramePath && file_exists($prevFramePath)) {
            @unlink($prevFramePath);
        }
        $prevFramePath = $currentFramePath;
        
        // Wait for next check
        sleep($checkInterval);
    }
    
    // Cleanup
    if ($prevFramePath && file_exists($prevFramePath)) {
        @unlink($prevFramePath);
    }
    
    global $pidFile;
    @unlink($pidFile);
    
    echo "[" . date('Y-m-d H:i:s') . "] Motion detector stopped\n";
}

// Handle signals for graceful shutdown
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function() {
        global $pidFile;
        @unlink($pidFile);
        exit(0);
    });
    pcntl_signal(SIGINT, function() {
        global $pidFile;
        @unlink($pidFile);
        exit(0);
    });
}

// Run the detector
runDetector();
