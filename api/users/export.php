<?php
/**
 * TrueVault VPN - User Data Export API
 * Export all user data for GDPR compliance
 * 
 * FIXED: January 14, 2026 - Use Database static methods
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/response.php';

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    $export = [
        'export_date' => date('c'),
        'user_id' => $user['id'],
        'account' => [
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'plan_type' => $user['plan_type'],
            'status' => $user['status'],
            'created_at' => $user['created_at']
        ]
    ];
    
    // Get devices
    $export['devices'] = Database::query('devices', 
        "SELECT device_name, device_type, os_type, last_ip, last_connected, created_at FROM user_devices WHERE user_id = ?", 
        [$user['id']]
    );
    
    // Get identities
    $export['identities'] = Database::query('identities', 
        "SELECT identity_name, region, browser_fingerprint, timezone, language, created_at FROM regional_identities WHERE user_id = ?", 
        [$user['id']]
    );
    
    // Get certificates (without private keys)
    $export['certificates'] = Database::query('certificates', 
        "SELECT device_name, common_name, serial_number, not_before, not_after, status, created_at FROM user_certificates WHERE user_id = ?", 
        [$user['id']]
    );
    
    // Get cameras
    $export['cameras'] = Database::query('cameras', 
        "SELECT camera_name, ip_address, vendor, model, is_online, created_at FROM ip_cameras WHERE user_id = ?", 
        [$user['id']]
    );
    
    // Get VPN connection history (last 30 days)
    $export['connection_history'] = Database::query('vpn', "
        SELECT vs.name as server_name, vs.location, vc.connected_at, vc.disconnected_at, vc.bytes_sent, vc.bytes_received
        FROM vpn_connections vc
        LEFT JOIN vpn_servers vs ON vc.server_id = vs.id
        WHERE vc.user_id = ? AND vc.connected_at >= datetime('now', '-30 days')
        ORDER BY vc.connected_at DESC
    ", [$user['id']]);
    
    // Get payments (if any)
    $export['payments'] = Database::query('payments', 
        "SELECT amount, currency, payment_method, status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", 
        [$user['id']]
    );
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="truevault-data-export-' . date('Y-m-d') . '.json"');
    
    echo json_encode($export, JSON_PRETTY_PRINT);
    exit;
    
} catch (Exception $e) {
    error_log("Data export error: " . $e->getMessage());
    Response::error('Failed to export data', 500);
}
