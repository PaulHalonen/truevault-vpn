<?php
/**
 * TrueVault VPN - Certificate Backup API
 * Export CA and certificates for backup
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
    // Get user's CA
    $ca = Database::queryOne('certificates', 
        "SELECT * FROM ca_certificates WHERE user_id = ?", 
        [$user['id']]
    );
    
    if (!$ca) {
        Response::error('No Certificate Authority found. Create one first.', 404);
    }
    
    // Get all user certificates
    $certificates = Database::query('certificates', "
        SELECT id, ca_id, device_name, common_name, serial_number, 
               not_before, not_after, status, created_at
        FROM user_certificates 
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ", [$user['id']]);
    
    // Build backup package
    $backup = [
        'version' => '1.0',
        'exported_at' => date('c'),
        'user_id' => $user['id'],
        'ca' => [
            'name' => $ca['name'],
            'common_name' => $ca['common_name'],
            'organization' => $ca['organization'],
            'country' => $ca['country'],
            'valid_from' => $ca['valid_from'],
            'valid_until' => $ca['valid_until'],
            'certificate_pem' => $ca['certificate_pem'],
            // Private key encrypted for security
            'private_key_encrypted' => base64_encode(openssl_encrypt(
                $ca['private_key_pem'] ?? '',
                'aes-256-cbc',
                hash('sha256', $user['id'] . $ca['id']),
                0,
                substr(hash('sha256', $ca['created_at']), 0, 16)
            ))
        ],
        'certificates' => array_map(function($cert) {
            return [
                'device_name' => $cert['device_name'],
                'common_name' => $cert['common_name'],
                'serial_number' => $cert['serial_number'],
                'not_before' => $cert['not_before'],
                'not_after' => $cert['not_after'],
                'status' => $cert['status'],
                'created_at' => $cert['created_at']
            ];
        }, $certificates),
        'certificate_count' => count($certificates)
    ];
    
    // Set headers for file download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="truevault-ca-backup-' . date('Y-m-d') . '.json"');
    header('Cache-Control: no-cache, must-revalidate');
    
    echo json_encode($backup, JSON_PRETTY_PRINT);
    exit;
    
} catch (Exception $e) {
    error_log("Certificate backup error: " . $e->getMessage());
    Response::error('Failed to create backup', 500);
}
