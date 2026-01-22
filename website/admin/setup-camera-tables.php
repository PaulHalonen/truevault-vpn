<?php
/**
 * Setup Camera Tables - Part 6A Task 6A.15
 * Run ONCE to add camera tables to devices.db
 */

define('TRUEVAULT_INIT', true);

$dbPath = __DIR__ . '/../databases/devices.db';

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    // Table 1: cameras
    $db->exec("
        CREATE TABLE IF NOT EXISTS cameras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            camera_id TEXT UNIQUE NOT NULL,
            camera_name TEXT NOT NULL,
            location TEXT,
            local_ip TEXT NOT NULL,
            rtsp_port INTEGER DEFAULT 554,
            rtsp_username TEXT,
            rtsp_password TEXT,
            rtsp_url TEXT,
            supports_audio INTEGER DEFAULT 0,
            supports_ptz INTEGER DEFAULT 0,
            supports_two_way INTEGER DEFAULT 0,
            max_resolution TEXT DEFAULT '1080p',
            recording_enabled INTEGER DEFAULT 0,
            recording_mode TEXT DEFAULT 'continuous',
            motion_detection INTEGER DEFAULT 0,
            motion_sensitivity INTEGER DEFAULT 50,
            storage_location TEXT,
            retention_days INTEGER DEFAULT 7,
            display_order INTEGER DEFAULT 0,
            is_online INTEGER DEFAULT 1,
            last_seen TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Table 2: motion_events
    $db->exec("
        CREATE TABLE IF NOT EXISTS motion_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            camera_id TEXT NOT NULL,
            detection_time TEXT DEFAULT CURRENT_TIMESTAMP,
            thumbnail TEXT,
            recording_id INTEGER,
            confidence INTEGER DEFAULT 100,
            notified INTEGER DEFAULT 0,
            viewed INTEGER DEFAULT 0
        )
    ");
    
    // Table 3: camera_recordings
    $db->exec("
        CREATE TABLE IF NOT EXISTS camera_recordings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            camera_id TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            filename TEXT NOT NULL,
            file_size INTEGER,
            duration INTEGER,
            start_time TEXT,
            end_time TEXT,
            thumbnail TEXT,
            recording_mode TEXT DEFAULT 'manual',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Table 4: motion_detection_settings
    $db->exec("
        CREATE TABLE IF NOT EXISTS motion_detection_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            camera_id TEXT NOT NULL,
            enabled INTEGER DEFAULT 0,
            sensitivity INTEGER DEFAULT 50,
            detection_zones TEXT,
            alert_email INTEGER DEFAULT 1,
            alert_push INTEGER DEFAULT 0,
            alert_sms INTEGER DEFAULT 0,
            alert_browser INTEGER DEFAULT 1,
            schedule_enabled INTEGER DEFAULT 0,
            schedule_start TEXT,
            schedule_end TEXT,
            auto_record INTEGER DEFAULT 1,
            pre_record_seconds INTEGER DEFAULT 5,
            post_record_seconds INTEGER DEFAULT 30,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Table 5: recording_shares
    $db->exec("
        CREATE TABLE IF NOT EXISTS recording_shares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recording_id INTEGER NOT NULL,
            share_token TEXT UNIQUE NOT NULL,
            created_by INTEGER NOT NULL,
            expires_at TEXT NOT NULL,
            view_count INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create indexes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_cameras_user ON cameras(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_cameras_online ON cameras(is_online)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_motion_camera ON motion_events(camera_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_motion_time ON motion_events(detection_time)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_recordings_camera ON camera_recordings(camera_id)");
    
    $db->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Camera tables created successfully',
        'tables' => ['cameras', 'motion_events', 'camera_recordings', 'motion_detection_settings', 'recording_shares']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
