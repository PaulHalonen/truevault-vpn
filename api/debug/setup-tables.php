<?php
/**
 * TrueVault VPN - Database Setup Script
 * Creates all missing tables in existing databases
 * Run this once to set up the database schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$basePath = __DIR__ . '/../../data';

$results = [
    'status' => 'running',
    'created' => [],
    'errors' => []
];

// ============================================
// USERS DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/users.db");
    
    // user_devices table
    $db->exec("CREATE TABLE IF NOT EXISTS user_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id TEXT UNIQUE NOT NULL,
        device_name TEXT NOT NULL,
        device_type TEXT DEFAULT 'computer',
        public_key TEXT,
        is_active INTEGER DEFAULT 1,
        last_seen DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $results['created'][] = 'users.db: user_devices';
    
    // user_settings table
    $db->exec("CREATE TABLE IF NOT EXISTS user_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        setting_key TEXT NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, setting_key),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    $results['created'][] = 'users.db: user_settings';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'users.db: ' . $e->getMessage();
}

// ============================================
// VPN DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/vpn.db");
    
    // active_connections table
    $db->exec("CREATE TABLE IF NOT EXISTS active_connections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        device_id TEXT,
        client_ip TEXT,
        assigned_ip TEXT,
        public_key TEXT,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_handshake DATETIME
    )");
    $results['created'][] = 'vpn.db: active_connections';
    
    // connection_history table
    $db->exec("CREATE TABLE IF NOT EXISTS connection_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        server_id INTEGER NOT NULL,
        device_id TEXT,
        client_ip TEXT,
        assigned_ip TEXT,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        connected_at DATETIME,
        disconnected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        duration_seconds INTEGER DEFAULT 0,
        disconnect_reason TEXT
    )");
    $results['created'][] = 'vpn.db: connection_history';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'vpn.db: ' . $e->getMessage();
}

// ============================================
// DEVICES DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/devices.db");
    
    // discovered_devices table
    $db->exec("CREATE TABLE IF NOT EXISTS discovered_devices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id TEXT UNIQUE NOT NULL,
        ip_address TEXT NOT NULL,
        mac_address TEXT,
        hostname TEXT,
        vendor TEXT,
        device_type TEXT DEFAULT 'unknown',
        device_icon TEXT DEFAULT '?',
        type_name TEXT DEFAULT 'Unknown',
        is_online INTEGER DEFAULT 1,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $results['created'][] = 'devices.db: discovered_devices';
    
    // device_ports table
    $db->exec("CREATE TABLE IF NOT EXISTS device_ports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        device_id INTEGER NOT NULL,
        port_number INTEGER NOT NULL,
        service_name TEXT,
        is_open INTEGER DEFAULT 1,
        last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(device_id, port_number)
    )");
    $results['created'][] = 'devices.db: device_ports';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'devices.db: ' . $e->getMessage();
}

// ============================================
// CAMERAS DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/cameras.db");
    
    // discovered_cameras table
    $db->exec("CREATE TABLE IF NOT EXISTS discovered_cameras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        device_id TEXT UNIQUE NOT NULL,
        camera_name TEXT NOT NULL,
        ip_address TEXT NOT NULL,
        mac_address TEXT,
        vendor TEXT,
        model TEXT,
        rtsp_port INTEGER DEFAULT 554,
        http_port INTEGER DEFAULT 80,
        username TEXT,
        password_encrypted TEXT,
        stream_url_main TEXT,
        stream_url_sub TEXT,
        snapshot_url TEXT,
        is_ptz INTEGER DEFAULT 0,
        has_audio INTEGER DEFAULT 0,
        has_two_way_audio INTEGER DEFAULT 0,
        has_night_vision INTEGER DEFAULT 1,
        has_motion_detection INTEGER DEFAULT 1,
        has_floodlight INTEGER DEFAULT 0,
        is_online INTEGER DEFAULT 1,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        thumbnail_path TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $results['created'][] = 'cameras.db: discovered_cameras';
    
    // camera_settings table
    $db->exec("CREATE TABLE IF NOT EXISTS camera_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        camera_id INTEGER NOT NULL,
        setting_key TEXT NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(camera_id, setting_key),
        FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id) ON DELETE CASCADE
    )");
    $results['created'][] = 'cameras.db: camera_settings';
    
    // camera_events table
    $db->exec("CREATE TABLE IF NOT EXISTS camera_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        camera_id INTEGER NOT NULL,
        event_type TEXT NOT NULL,
        event_data TEXT,
        thumbnail_path TEXT,
        is_viewed INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (camera_id) REFERENCES discovered_cameras(id) ON DELETE CASCADE
    )");
    $results['created'][] = 'cameras.db: camera_events';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'cameras.db: ' . $e->getMessage();
}

// ============================================
// CERTIFICATES DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/certificates.db");
    
    // certificate_authority table
    $db->exec("CREATE TABLE IF NOT EXISTS certificate_authority (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        ca_certificate TEXT NOT NULL,
        ca_private_key_encrypted TEXT NOT NULL,
        ca_serial INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME
    )");
    $results['created'][] = 'certificates.db: certificate_authority';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'certificates.db: ' . $e->getMessage();
}

// ============================================
// LOGS DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/logs.db");
    
    // daily_usage table
    $db->exec("CREATE TABLE IF NOT EXISTS daily_usage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        date DATE NOT NULL,
        bytes_sent INTEGER DEFAULT 0,
        bytes_received INTEGER DEFAULT 0,
        total_duration_seconds INTEGER DEFAULT 0,
        connections INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, date)
    )");
    $results['created'][] = 'logs.db: daily_usage';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'logs.db: ' . $e->getMessage();
}

// ============================================
// IDENTITIES DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/identities.db");
    
    // regional_identities table
    $db->exec("CREATE TABLE IF NOT EXISTS regional_identities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        identity_name TEXT NOT NULL,
        region_code TEXT NOT NULL,
        ip_address TEXT,
        browser_fingerprint TEXT,
        timezone TEXT,
        language TEXT,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $results['created'][] = 'identities.db: regional_identities';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'identities.db: ' . $e->getMessage();
}

// ============================================
// MESH DATABASE
// ============================================
try {
    $db = new SQLite3("$basePath/mesh.db");
    
    // mesh_networks table (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS mesh_networks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        network_name TEXT NOT NULL,
        network_key TEXT UNIQUE NOT NULL,
        max_members INTEGER DEFAULT 6,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $results['created'][] = 'mesh.db: mesh_networks';
    
    // mesh_members table (if not exists)
    $db->exec("CREATE TABLE IF NOT EXISTS mesh_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        network_id INTEGER NOT NULL,
        user_id INTEGER,
        member_email TEXT,
        member_name TEXT,
        role TEXT DEFAULT 'member',
        status TEXT DEFAULT 'pending',
        joined_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (network_id) REFERENCES mesh_networks(id) ON DELETE CASCADE
    )");
    $results['created'][] = 'mesh.db: mesh_members';
    
    $db->close();
} catch (Exception $e) {
    $results['errors'][] = 'mesh.db: ' . $e->getMessage();
}

$results['status'] = 'complete';
$results['summary'] = [
    'tables_created' => count($results['created']),
    'errors' => count($results['errors'])
];

echo json_encode($results, JSON_PRETTY_PRINT);
