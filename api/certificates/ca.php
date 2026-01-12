<?php
/**
 * TrueVault VPN - Certificate Authority Management
 * GET/POST /api/certificates/ca.php
 * 
 * Creates and manages user's personal Certificate Authority
 * IMPORTANT: Keys are generated on the VPN server, not here
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/encryption.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

// Require authentication
$user = Auth::requireAuth();

$method = Response::getMethod();

try {
    $certsDb = DatabaseManager::getInstance()->certificates();
    
    switch ($method) {
        case 'GET':
            // Get user's CA
            $stmt = $certsDb->prepare("
                SELECT id, user_id, created_at, expires_at, is_active, ca_serial
                FROM certificate_authority 
                WHERE user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $ca = $stmt->fetch();
            
            if (!$ca) {
                Response::success([
                    'has_ca' => false,
                    'ca' => null,
                    'message' => 'No certificate authority found. Create one to get started.'
                ]);
            }
            
            // Get certificate counts
            $stmt = $certsDb->prepare("
                SELECT certificate_type, COUNT(*) as count
                FROM user_certificates
                WHERE user_id = ? AND is_revoked = 0
                GROUP BY certificate_type
            ");
            $stmt->execute([$user['id']]);
            $counts = $stmt->fetchAll();
            
            $certCounts = [];
            foreach ($counts as $c) {
                $certCounts[$c['certificate_type']] = (int) $c['count'];
            }
            
            Response::success([
                'has_ca' => true,
                'ca' => [
                    'id' => $ca['id'],
                    'created_at' => $ca['created_at'],
                    'expires_at' => $ca['expires_at'],
                    'is_active' => (bool) $ca['is_active'],
                    'certificates_issued' => $ca['ca_serial'] - 1
                ],
                'certificate_counts' => $certCounts
            ]);
            break;
            
        case 'POST':
            // Check if CA already exists
            $stmt = $certsDb->prepare("SELECT id FROM certificate_authority WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            if ($stmt->fetch()) {
                Response::error('Certificate Authority already exists. Use regenerate if you need a new one.', 409);
            }
            
            // Generate CA certificate
            // In production, this would call the VPN server API
            // For now, we'll create placeholder data
            
            $caConfig = [
                'country' => 'US',
                'state' => 'California',
                'organization' => 'TrueVault VPN',
                'common_name' => "TrueVault Personal CA - {$user['email']}",
                'validity_days' => 3650 // 10 years
            ];
            
            // Generate placeholder keys (real ones come from server)
            $caCertificate = "-----BEGIN CERTIFICATE-----\n" . 
                chunk_split(base64_encode("PLACEHOLDER_CA_CERTIFICATE_FOR_{$user['id']}"), 64, "\n") .
                "-----END CERTIFICATE-----";
            
            $caPrivateKey = Encryption::encrypt(
                "-----BEGIN PRIVATE KEY-----\n" .
                chunk_split(base64_encode(random_bytes(256)), 64, "\n") .
                "-----END PRIVATE KEY-----"
            );
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 years'));
            
            // Insert CA
            $stmt = $certsDb->prepare("
                INSERT INTO certificate_authority (user_id, ca_certificate, ca_private_key_encrypted, expires_at, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$user['id'], $caCertificate, $caPrivateKey, $expiresAt]);
            
            $caId = $certsDb->lastInsertId();
            
            // Create root certificate record
            $serialNumber = Encryption::generateUUID();
            $stmt = $certsDb->prepare("
                INSERT INTO user_certificates (user_id, ca_id, certificate_type, certificate_name, certificate_data, serial_number, expires_at)
                VALUES (?, ?, 'root', 'Root Certificate', ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $caId, $caCertificate, $serialNumber, $expiresAt]);
            
            // Update CA serial
            $stmt = $certsDb->prepare("UPDATE certificate_authority SET ca_serial = 2 WHERE id = ?");
            $stmt->execute([$caId]);
            
            Logger::info('Certificate Authority created', ['user_id' => $user['id'], 'ca_id' => $caId]);
            
            Response::created([
                'ca' => [
                    'id' => $caId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt,
                    'is_active' => true
                ],
                'root_certificate' => [
                    'serial_number' => $serialNumber,
                    'certificate' => $caCertificate
                ]
            ], 'Certificate Authority created successfully');
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('CA operation failed: ' . $e->getMessage());
    Response::serverError('Certificate Authority operation failed');
}
