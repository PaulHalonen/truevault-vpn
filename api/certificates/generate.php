<?php
/**
 * TrueVault VPN - Generate Certificate
 * POST /api/certificates/generate.php
 * 
 * Generate device, regional, or mesh certificates
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
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
    'type' => 'required|in:device,regional,mesh',
    'name' => 'required|min:1|max:100'
]);

if ($validator->fails()) {
    Response::validationError($validator->errors());
}

$type = $input['type'];
$name = trim($input['name']);
$deviceId = $input['device_id'] ?? null;
$regionCode = $input['region_code'] ?? null;
$meshNetworkId = $input['mesh_network_id'] ?? null;

try {
    $certsDb = DatabaseManager::getInstance()->certificates();
    
    // Check if user has a CA
    $stmt = $certsDb->prepare("SELECT * FROM certificate_authority WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$user['id']]);
    $ca = $stmt->fetch();
    
    if (!$ca) {
        Response::error('You must create a Certificate Authority first', 400);
    }
    
    // Validate type-specific requirements
    switch ($type) {
        case 'device':
            if (!$deviceId) {
                Response::validationError(['device_id' => ['Device ID is required for device certificates']]);
            }
            
            // Verify device belongs to user
            $usersDb = DatabaseManager::getInstance()->users();
            $stmt = $usersDb->prepare("SELECT * FROM user_devices WHERE id = ? AND user_id = ?");
            $stmt->execute([$deviceId, $user['id']]);
            if (!$stmt->fetch()) {
                Response::notFound('Device not found');
            }
            break;
            
        case 'regional':
            if (!$regionCode) {
                Response::validationError(['region_code' => ['Region code is required for regional certificates']]);
            }
            
            // Verify region exists
            $identitiesDb = DatabaseManager::getInstance()->identities();
            $stmt = $identitiesDb->prepare("SELECT * FROM regions WHERE region_code = ? AND is_available = 1");
            $stmt->execute([$regionCode]);
            if (!$stmt->fetch()) {
                Response::error('Invalid region code', 400);
            }
            
            // Check identity limit based on plan
            $limit = Auth::checkPlanLimit($user, 'identities');
            $stmt = $certsDb->prepare("SELECT COUNT(*) as count FROM user_certificates WHERE user_id = ? AND certificate_type = 'regional' AND is_revoked = 0");
            $stmt->execute([$user['id']]);
            $count = $stmt->fetch()['count'];
            
            if ($count >= $limit) {
                Response::error("Regional identity limit reached ($limit). Upgrade your plan for more.", 403);
            }
            break;
            
        case 'mesh':
            if (!$meshNetworkId) {
                Response::validationError(['mesh_network_id' => ['Mesh network ID is required for mesh certificates']]);
            }
            
            // Verify user is part of mesh network
            $meshDb = DatabaseManager::getInstance()->meshNetwork();
            $stmt = $meshDb->prepare("SELECT * FROM mesh_members WHERE network_id = ? AND user_id = ? AND is_active = 1");
            $stmt->execute([$meshNetworkId, $user['id']]);
            if (!$stmt->fetch()) {
                Response::forbidden('You are not a member of this mesh network');
            }
            break;
    }
    
    // Generate certificate (in production, this calls VPN server)
    $serialNumber = Encryption::generateUUID();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    // Generate placeholder certificate
    $certificate = "-----BEGIN CERTIFICATE-----\n" .
        chunk_split(base64_encode("CERTIFICATE_{$type}_{$user['id']}_{$serialNumber}"), 64, "\n") .
        "-----END CERTIFICATE-----";
    
    $privateKey = Encryption::encrypt(
        "-----BEGIN PRIVATE KEY-----\n" .
        chunk_split(base64_encode(random_bytes(256)), 64, "\n") .
        "-----END PRIVATE KEY-----"
    );
    
    $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split(base64_encode(random_bytes(256)), 64, "\n") .
        "-----END PUBLIC KEY-----";
    
    // Insert certificate
    $stmt = $certsDb->prepare("
        INSERT INTO user_certificates (user_id, ca_id, certificate_type, certificate_name, certificate_data, private_key_encrypted, public_key, serial_number, expires_at, device_id, region_code, mesh_network_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $ca['id'],
        $type,
        $name,
        $certificate,
        $privateKey,
        $publicKey,
        $serialNumber,
        $expiresAt,
        $deviceId,
        $regionCode,
        $meshNetworkId
    ]);
    
    $certId = $certsDb->lastInsertId();
    
    // Update CA serial
    $stmt = $certsDb->prepare("UPDATE certificate_authority SET ca_serial = ca_serial + 1 WHERE id = ?");
    $stmt->execute([$ca['id']]);
    
    // If regional certificate, create/update regional identity
    if ($type === 'regional') {
        $identitiesDb = DatabaseManager::getInstance()->identities();
        
        // Get region info
        $stmt = $identitiesDb->prepare("SELECT * FROM regions WHERE region_code = ?");
        $stmt->execute([$regionCode]);
        $region = $stmt->fetch();
        
        // Check if identity exists
        $stmt = $identitiesDb->prepare("SELECT * FROM regional_identities WHERE user_id = ? AND region_code = ?");
        $stmt->execute([$user['id'], $regionCode]);
        $existingIdentity = $stmt->fetch();
        
        if (!$existingIdentity) {
            $stmt = $identitiesDb->prepare("
                INSERT INTO regional_identities (user_id, region_code, region_name, timezone, locale)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                $regionCode,
                $region['region_name'],
                $region['timezone'],
                $region['locale']
            ]);
        }
    }
    
    Logger::info('Certificate generated', [
        'user_id' => $user['id'],
        'cert_id' => $certId,
        'type' => $type
    ]);
    
    Response::created([
        'certificate' => [
            'id' => $certId,
            'type' => $type,
            'name' => $name,
            'serial_number' => $serialNumber,
            'expires_at' => $expiresAt,
            'certificate_pem' => $certificate,
            'public_key_pem' => $publicKey
        ]
    ], 'Certificate generated successfully');
    
} catch (Exception $e) {
    Logger::error('Certificate generation failed: ' . $e->getMessage());
    Response::serverError('Failed to generate certificate');
}
