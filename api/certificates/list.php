<?php
/**
 * TrueVault VPN - List User Certificates
 * GET /api/certificates/list.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';

// Only allow GET
Response::requireMethod('GET');

// Require authentication
$user = Auth::requireAuth();
if (!$user) exit;

try {
    // Get filter parameters
    $type = $_GET['type'] ?? null;
    $includeRevoked = ($_GET['include_revoked'] ?? '0') === '1';
    
    // Build query using actual column names
    $sql = "
        SELECT id, user_id, name, type, fingerprint, status, expires_at, created_at
        FROM user_certificates
        WHERE user_id = ?
    ";
    $params = [$user['id']];
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    if (!$includeRevoked) {
        $sql .= " AND status != 'revoked'";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $certificates = Database::query('certificates', $sql, $params);
    
    // Add status and expiration info
    $now = time();
    foreach ($certificates as &$cert) {
        $expiresAt = $cert['expires_at'] ? strtotime($cert['expires_at']) : null;
        $daysUntilExpiry = $expiresAt ? floor(($expiresAt - $now) / 86400) : 365;
        
        if ($cert['status'] === 'revoked') {
            $cert['status_display'] = 'revoked';
            $cert['status_color'] = 'red';
        } elseif ($expiresAt && $daysUntilExpiry < 0) {
            $cert['status_display'] = 'expired';
            $cert['status_color'] = 'red';
        } elseif ($expiresAt && $daysUntilExpiry < 30) {
            $cert['status_display'] = 'expiring_soon';
            $cert['status_color'] = 'yellow';
        } else {
            $cert['status_display'] = 'valid';
            $cert['status_color'] = 'green';
        }
        
        $cert['days_until_expiry'] = max(0, $daysUntilExpiry);
        
        // Map to expected frontend fields
        $cert['device_name'] = $cert['name'];
        $cert['certificate_type'] = $cert['type'];
    }
    
    // Group by type
    $grouped = [
        'device' => [],
        'regional' => [],
        'mesh' => [],
        'root' => []
    ];
    
    foreach ($certificates as $cert) {
        $certType = $cert['type'] ?? 'device';
        if (!isset($grouped[$certType])) {
            $grouped['device'][] = $cert;
        } else {
            $grouped[$certType][] = $cert;
        }
    }
    
    Response::success([
        'certificates' => $certificates,
        'grouped' => $grouped,
        'counts' => [
            'total' => count($certificates),
            'device' => count($grouped['device']),
            'regional' => count($grouped['regional']),
            'mesh' => count($grouped['mesh']),
            'root' => count($grouped['root'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Certificate list error: ' . $e->getMessage());
    Response::error('Failed to get certificates', 500);
}
