<?php
/**
 * TrueVault VPN - List User Certificates
 * GET /api/certificates/list.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow GET
Response::requireMethod('GET');

// Require authentication
$user = Auth::requireAuth();

try {
    $certsDb = DatabaseManager::getInstance()->certificates();
    
    // Get filter parameters
    $type = $_GET['type'] ?? null; // root, device, regional, mesh
    $includeRevoked = ($_GET['include_revoked'] ?? '0') === '1';
    
    // Build query
    $sql = "
        SELECT uc.id, uc.certificate_type, uc.certificate_name, uc.serial_number, 
               uc.issued_at, uc.expires_at, uc.is_revoked, uc.revoked_at, 
               uc.device_id, uc.region_code, uc.mesh_network_id
        FROM user_certificates uc
        WHERE uc.user_id = ?
    ";
    $params = [$user['id']];
    
    if ($type) {
        $sql .= " AND uc.certificate_type = ?";
        $params[] = $type;
    }
    
    if (!$includeRevoked) {
        $sql .= " AND uc.is_revoked = 0";
    }
    
    $sql .= " ORDER BY uc.issued_at DESC";
    
    $stmt = $certsDb->prepare($sql);
    $stmt->execute($params);
    $certificates = $stmt->fetchAll();
    
    // Add status and expiration info
    $now = time();
    foreach ($certificates as &$cert) {
        $expiresAt = strtotime($cert['expires_at']);
        $daysUntilExpiry = floor(($expiresAt - $now) / 86400);
        
        if ($cert['is_revoked']) {
            $cert['status'] = 'revoked';
            $cert['status_color'] = 'red';
        } elseif ($daysUntilExpiry < 0) {
            $cert['status'] = 'expired';
            $cert['status_color'] = 'red';
        } elseif ($daysUntilExpiry < 30) {
            $cert['status'] = 'expiring_soon';
            $cert['status_color'] = 'yellow';
        } else {
            $cert['status'] = 'valid';
            $cert['status_color'] = 'green';
        }
        
        $cert['days_until_expiry'] = max(0, $daysUntilExpiry);
    }
    
    // Group by type
    $grouped = [
        'root' => [],
        'device' => [],
        'regional' => [],
        'mesh' => []
    ];
    
    foreach ($certificates as $cert) {
        $grouped[$cert['certificate_type']][] = $cert;
    }
    
    Response::success([
        'certificates' => $certificates,
        'grouped' => $grouped,
        'counts' => [
            'total' => count($certificates),
            'root' => count($grouped['root']),
            'device' => count($grouped['device']),
            'regional' => count($grouped['regional']),
            'mesh' => count($grouped['mesh'])
        ]
    ]);
    
} catch (Exception $e) {
    Logger::error('Certificate list failed: ' . $e->getMessage());
    Response::serverError('Failed to get certificates');
}
