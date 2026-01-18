<?php
/**
 * TrueVault VPN - List Port Forward Devices API
 * 
 * PURPOSE: Get all discovered devices for port forwarding
 * METHOD: GET
 * AUTHENTICATION: JWT required
 * 
 * RETURNS:
 * {
 *   "success": true,
 *   "devices": [
 *     {
 *       "device_id": 123,
 *       "device_name": "MyCamera",
 *       "ip_address": "192.168.1.100",
 *       "mac_address": "AA:BB:CC:DD:EE:FF",
 *       "device_type": "ip_camera",
 *       "vendor": "Geeni",
 *       "hostname": "MyCamera",
 *       "open_ports": "[{\"port\":80,\"service\":\"http\"}]",
 *       "port_forward_enabled": true,
 *       "discovered_at": "2026-01-18 10:30:00"
 *     }
 *   ]
 * }
 * 
 * @created January 18, 2026
 * @version 1.0.0
 */

// Define initialization constant
define('TRUEVAULT_INIT', true);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../configs/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/JWT.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {
    // Authenticate user
    $user = Auth::require();
    $userId = $user['user_id'];
    
    // Get database connection
    $db = Database::getInstance();
    $devicesConn = $db->getConnection('devices');
    
    // Get all devices for user
    $stmt = $devicesConn->prepare("
        SELECT device_id, device_name, ip_address, mac_address, device_type,
               vendor, hostname, open_ports, port_forward_enabled, discovered_at
        FROM port_forward_devices
        WHERE user_id = ?
        ORDER BY discovered_at DESC
    ");
    $stmt->execute([$userId]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert port_forward_enabled to boolean
    foreach ($devices as &$device) {
        $device['port_forward_enabled'] = (bool)$device['port_forward_enabled'];
    }
    
    // Return devices
    echo json_encode([
        'success' => true,
        'devices' => $devices
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    
    // Log error
    error_log('List Port Forward Devices Error: ' . $e->getMessage());
}
