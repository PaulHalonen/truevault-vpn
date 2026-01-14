<?php
/**
 * TrueVault VPN - Database Table Checker
 * Checks all required tables exist in all databases
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Go up from /api/debug/ to root, then to /data/
$basePath = __DIR__ . '/../../data';

$results = [
    'status' => 'checking',
    'basePath' => $basePath,
    'realPath' => realpath($basePath),
    'databases' => []
];

// Define required tables for each database
$requiredTables = [
    'users' => ['users', 'user_devices', 'user_settings'],
    'vpn' => ['vpn_servers', 'active_connections', 'connection_history'],
    'devices' => ['discovered_devices', 'device_ports', 'user_devices'],
    'cameras' => ['discovered_cameras', 'camera_settings', 'camera_events'],
    'certificates' => ['certificate_authority', 'user_certificates'],
    'logs' => ['system_log', 'activity_log', 'daily_usage'],
    'settings' => ['settings']
];

foreach ($requiredTables as $dbName => $tables) {
    $dbPath = "$basePath/$dbName.db";
    
    if (!file_exists($dbPath)) {
        $results['databases'][$dbName] = [
            'exists' => false,
            'path' => $dbPath,
            'error' => 'Database file not found'
        ];
        continue;
    }
    
    try {
        $db = new SQLite3($dbPath);
        $results['databases'][$dbName] = [
            'exists' => true,
            'tables' => []
        ];
        
        foreach ($tables as $table) {
            // Check if table exists
            $check = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            
            if ($check) {
                // Get row count
                $count = $db->querySingle("SELECT COUNT(*) FROM $table");
                $results['databases'][$dbName]['tables'][$table] = [
                    'exists' => true,
                    'rows' => $count
                ];
            } else {
                $results['databases'][$dbName]['tables'][$table] = [
                    'exists' => false,
                    'error' => 'Table not found'
                ];
            }
        }
        
        $db->close();
        
    } catch (Exception $e) {
        $results['databases'][$dbName] = [
            'exists' => true,
            'error' => $e->getMessage()
        ];
    }
}

$results['status'] = 'complete';

echo json_encode($results, JSON_PRETTY_PRINT);
