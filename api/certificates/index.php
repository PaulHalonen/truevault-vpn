<?php
/**
 * TrueVault VPN - Certificate Management
 * GET/POST /api/certificates/index.php
 * 
 * Personal Certificate Authority - certificates generated on VPN servers
 * FIXED: January 14, 2026 - Changed DatabaseManager to Database class
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();

$method = Response::getMethod();

try {
    switch ($method) {
        case 'GET':
            // Get certificate type filter
            $type = $_GET['type'] ?? null;
            
            // Check if user has a CA
            $ca = Database::queryOne('certificates', 
                "SELECT * FROM certificate_authority WHERE user_id = ? AND is_active = 1", 
                [$user['id']]
            );
            
            if (!$ca) {
                Response::success([
                    'has_ca' => false,
                    'certificates' => [],
                    'message' => 'No Certificate Authority found. Generate one to get started.'
                ]);
            }
            
            // Get certificates
            if ($type) {
                $certificates = Database::query('certificates', "
                    SELECT id, certificate_type, certificate_name, serial_number, issued_at, expires_at, is_revoked, device_id, region_code
                    FROM user_certificates 
                    WHERE user_id = ? AND certificate_type = ?
                    ORDER BY issued_at DESC
                ", [$user['id'], $type]);
            } else {
                $certificates = Database::query('certificates', "
                    SELECT id, certificate_type, certificate_name, serial_number, issued_at, expires_at, is_revoked, device_id, region_code
                    FROM user_certificates 
                    WHERE user_id = ?
                    ORDER BY issued_at DESC
                ", [$user['id']]);
            }
            
            // Add status to each certificate
            foreach ($certificates as &$cert) {
                $expires = strtotime($cert['expires_at']);
                $now = time();
                
                if ($cert['is_revoked']) {
                    $cert['status'] = 'revoked';
                    $cert['status_color'] = 'red';
                } elseif ($expires < $now) {
                    $cert['status'] = 'expired';
                    $cert['status_color'] = 'red';
                } elseif ($expires < $now + 30 * 24 * 60 * 60) {
                    $cert['status'] = 'expiring_soon';
                    $cert['status_color'] = 'yellow';
                } else {
                    $cert['status'] = 'valid';
                    $cert['status_color'] = 'green';
                }
            }
            
            Response::success([
                'has_ca' => true,
                'ca_expires' => $ca['expires_at'],
                'certificates' => $certificates,
                'count' => count($certificates)
            ]);
            break;
            
        case 'POST':
            // Generate new certificate
            $input = Response::getJsonInput();
            
            $validator = Validator::make($input, [
                'type' => 'required|in:root,device,regional,mesh',
                'name' => 'required|min:1|max:100'
            ]);
            
            if ($validator->fails()) {
                Response::validationError($validator->errors());
            }
            
            $certType = $input['type'];
            $certName = trim($input['name']);
            $deviceId = $input['device_id'] ?? null;
            $regionCode = $input['region_code'] ?? null;
            $meshNetworkId = $input['mesh_network_id'] ?? null;
            
            // Check/create CA
            $ca = Database::queryOne('certificates', 
                "SELECT * FROM certificate_authority WHERE user_id = ? AND is_active = 1", 
                [$user['id']]
            );
            
            if (!$ca) {
                // Create CA first
                $ca = createUserCA($user);
                if (!$ca) {
                    Response::serverError('Failed to create Certificate Authority');
                }
            }
            
            // Generate certificate on VPN server
            $certData = generateCertificateOnServer($ca, $certType, $certName);
            
            if (!$certData) {
                Response::serverError('Failed to generate certificate');
            }
            
            // Update CA serial
            $newSerial = $ca['ca_serial'] + 1;
            Database::execute('certificates', 
                "UPDATE certificate_authority SET ca_serial = ? WHERE id = ?", 
                [$newSerial, $ca['id']]
            );
            
            // Store certificate
            $result = Database::execute('certificates', "
                INSERT INTO user_certificates 
                (user_id, ca_id, certificate_type, certificate_name, certificate_data, public_key, serial_number, expires_at, device_id, region_code, mesh_network_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', '+365 days'), ?, ?, ?)
            ", [
                $user['id'],
                $ca['id'],
                $certType,
                $certName,
                $certData['certificate'],
                $certData['public_key'],
                $certData['serial'],
                $deviceId,
                $regionCode,
                $meshNetworkId
            ]);
            
            $certId = $result['lastInsertId'];
            
            Logger::info('Certificate generated', [
                'user_id' => $user['id'],
                'cert_id' => $certId,
                'type' => $certType
            ]);
            
            Response::created([
                'certificate' => [
                    'id' => $certId,
                    'type' => $certType,
                    'name' => $certName,
                    'serial' => $certData['serial'],
                    'certificate_pem' => $certData['certificate'],
                    'public_key' => $certData['public_key']
                ]
            ], 'Certificate generated successfully');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Certificate operation failed: ' . $e->getMessage());
    Response::serverError('Certificate operation failed');
}

/**
 * Create user's personal Certificate Authority
 */
function createUserCA($user) {
    // In production, this would call the VPN server API
    // For now, generate placeholder data
    
    $caPrivateKey = base64_encode(random_bytes(32));
    $caCertificate = "-----BEGIN CERTIFICATE-----\n" . 
        base64_encode("CA Certificate for User {$user['id']} - " . time()) . 
        "\n-----END CERTIFICATE-----";
    
    $encryptedKey = Encryption::encrypt($caPrivateKey);
    
    $result = Database::execute('certificates', "
        INSERT INTO certificate_authority (user_id, ca_certificate, ca_private_key_encrypted, expires_at)
        VALUES (?, ?, ?, datetime('now', '+10 years'))
    ", [$user['id'], $caCertificate, $encryptedKey]);
    
    $caId = $result['lastInsertId'];
    
    return Database::queryOne('certificates', 
        "SELECT * FROM certificate_authority WHERE id = ?", 
        [$caId]
    );
}

/**
 * Generate certificate on VPN server
 * In production, this makes an API call to the VPN server
 */
function generateCertificateOnServer($ca, $type, $name) {
    // In production: callServerAPI('/generate-certificate', ...)
    
    $serial = sprintf('%08X', $ca['ca_serial'] + 1);
    $privateKey = base64_encode(random_bytes(32));
    $publicKey = base64_encode(hash('sha256', base64_decode($privateKey), true));
    
    $certificate = "-----BEGIN CERTIFICATE-----\n" .
        base64_encode("Certificate: $name | Type: $type | Serial: $serial | " . time()) .
        "\n-----END CERTIFICATE-----";
    
    return [
        'serial' => $serial,
        'certificate' => $certificate,
        'public_key' => $publicKey,
        'private_key' => $privateKey
    ];
}
