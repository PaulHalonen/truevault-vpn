<?php
/**
 * TrueVault VPN - Revoke Certificate
 * POST /api/certificates/revoke.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Only allow POST
Response::requireMethod('POST');

// Require authentication
$user = Auth::requireAuth();

// Get input
$input = Response::getJsonInput();

// Validate input
$validator = Validator::make($input, [
    'certificate_id' => 'required|integer',
    'reason' => 'max:500'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$certId = (int) $input['certificate_id'];
$reason = $input['reason'] ?? 'User requested revocation';

try {
    $certsDb = DatabaseManager::getInstance()->certificates();
    
    // Get certificate
    $stmt = $certsDb->prepare("SELECT * FROM user_certificates WHERE id = ? AND user_id = ?");
    $stmt->execute([$certId, $user['id']]);
    $certificate = $stmt->fetch();
    
    if (!$certificate) {
        Response::notFound('Certificate not found');
    }
    
    if ($certificate['is_revoked']) {
        Response::error('Certificate is already revoked', 400);
    }
    
    // Cannot revoke root certificate unless regenerating CA
    if ($certificate['certificate_type'] === 'root') {
        Response::error('Root certificate cannot be revoked directly. Regenerate your CA instead.', 400);
    }
    
    // Revoke certificate
    $stmt = $certsDb->prepare("
        UPDATE user_certificates 
        SET is_revoked = 1, revoked_at = datetime('now'), revocation_reason = ?
        WHERE id = ?
    ");
    $stmt->execute([$reason, $certId]);
    
    // Add to revocation list
    $stmt = $certsDb->prepare("
        INSERT INTO certificate_revocations (certificate_id, serial_number, reason)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$certId, $certificate['serial_number'], $reason]);
    
    Logger::info('Certificate revoked', [
        'user_id' => $user['id'],
        'cert_id' => $certId,
        'reason' => $reason
    ]);
    
    Response::success([
        'certificate_id' => $certId,
        'serial_number' => $certificate['serial_number'],
        'revoked_at' => date('Y-m-d H:i:s')
    ], 'Certificate revoked successfully');
    
} catch (Exception $e) {
    Logger::error('Certificate revocation failed: ' . $e->getMessage());
    Response::serverError('Failed to revoke certificate');
}
