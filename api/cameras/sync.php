<?php
/**
 * TrueVault VPN - Camera/Device Sync API
 * POST /api/cameras/sync.php
 * 
 * Receives devices discovered by the network scanner and saves to database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

// Require authentication
$user = Auth::requireAuth();

// Only POST
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['devices']) || !is_array($input['devices'])) {
    Response::error('No devices provided', 400);
}

$devices = $input['devices'];
$synced = 0;
$errors = [];

try {
    foreach ($devices as $device) {
        // Validate required fields
        if (empty($device['ip']) || empty($device['mac'])) {
            $errors[] = "Device missing IP or MAC address";
            continue;
        }
        
        $ip = filter_var($device['ip'], FILTER_VALIDATE_IP);
        if (!$ip) {
            $errors[] = "Invalid IP: " . ($device['ip'] ?? 'unknown');
            continue;
        }
        
        $mac = strtoupper(preg_replace('/[^A-Fa-f0-9:]/', '', $device['mac']));
        
        // Check if device already exists for this user
        $existing = Database::queryOne('cameras',
            "SELECT id FROM cameras WHERE user_id = ? AND mac_address = ?",
            [$user['id'], $mac]
        );
        
        if ($existing) {
            // Update existing device
            Database::execute('cameras',
                "UPDATE cameras SET 
                    local_ip = ?,
                    hostname = ?,
                    vendor = ?,
                    type = ?,
                    open_ports = ?,
                    last_seen = datetime('now'),
                    updated_at = datetime('now')
                WHERE id = ?",
                [
                    $ip,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? 'Unknown',
                    $device['type'] ?? 'unknown',
                    json_encode($device['open_ports'] ?? []),
                    $existing['id']
                ]
            );
        } else {
            // Insert new device
            Database::execute('cameras',
                "INSERT INTO cameras (user_id, name, local_ip, mac_address, hostname, vendor, brand, type, model, open_ports, status, created_at, updated_at, last_seen)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', datetime('now'), datetime('now'), datetime('now'))",
                [
                    $user['id'],
                    $device['hostname'] ?? $device['type_name'] ?? 'Discovered Device',
                    $ip,
                    $mac,
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? 'Unknown',
                    $device['vendor'] ?? null,
                    $device['type'] ?? 'unknown',
                    $device['type_name'] ?? null,
                    json_encode($device['open_ports'] ?? [])
                ]
            );
        }
        
        $synced++;
    }
    
    // Log the sync activity
    Database::execute('logs',
        "INSERT INTO activity_log (user_id, action, details, ip_address, created_at) 
         VALUES (?, 'device_sync', ?, ?, datetime('now'))",
        [
            $user['id'],
            json_encode(['synced' => $synced, 'total' => count($devices)]),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]
    );
    
    Response::success([
        'synced' => $synced,
        'total' => count($devices),
        'errors' => $errors
    ], "Successfully synced {$synced} devices");

} catch (Exception $e) {
    Response::serverError('Failed to sync devices: ' . $e->getMessage());
}
