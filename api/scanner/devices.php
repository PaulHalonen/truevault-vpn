<?php
/**
 * TrueVault VPN - Scanner Devices API
 * Sync discovered devices from network scanner
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

// Verify user token
$user = verifyToken();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = getDatabase('scanner');
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // List previously scanned devices
        $stmt = $db->prepare("SELECT * FROM scanned_devices WHERE user_id = ? ORDER BY discovered_at DESC");
        $stmt->execute([$user['id']]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $devices
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sync new devices from scanner
        $input = json_decode(file_get_contents('php://input'), true);
        $devices = $input['devices'] ?? [];
        
        if (empty($devices)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No devices to sync']);
            exit;
        }
        
        $synced = 0;
        
        foreach ($devices as $device) {
            // Check if device already exists
            $stmt = $db->prepare("SELECT id FROM scanned_devices WHERE user_id = ? AND mac_address = ?");
            $stmt->execute([$user['id'], $device['mac'] ?? '']);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing device
                $stmt = $db->prepare("UPDATE scanned_devices SET ip_address = ?, hostname = ?, vendor = ?, type = ?, last_seen = datetime('now') WHERE id = ?");
                $stmt->execute([
                    $device['ip'],
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? 'Unknown',
                    $device['type'] ?? 'unknown',
                    $existing['id']
                ]);
            } else {
                // Insert new device
                $stmt = $db->prepare("INSERT INTO scanned_devices (user_id, ip_address, mac_address, hostname, vendor, type, discovered_at, last_seen) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                $stmt->execute([
                    $user['id'],
                    $device['ip'],
                    $device['mac'] ?? '',
                    $device['hostname'] ?? null,
                    $device['vendor'] ?? 'Unknown',
                    $device['type'] ?? 'unknown'
                ]);
            }
            $synced++;
        }
        
        echo json_encode([
            'success' => true,
            'synced' => $synced,
            'message' => "{$synced} devices synced successfully"
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
