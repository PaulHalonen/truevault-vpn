<?php
/**
 * TrueVault VPN - Certificate Download API
 * Download individual certificate files
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

$certId = $_GET['id'] ?? null;
$format = $_GET['format'] ?? 'pem';

if (!$certId) {
    Response::error('Certificate ID is required', 400);
}

try {
    // Get certificate (must belong to user)
    $cert = Database::queryOne('certificates', "
        SELECT uc.*, ca.certificate_pem as ca_cert, ca.name as ca_name
        FROM user_certificates uc
        JOIN ca_certificates ca ON uc.ca_id = ca.id
        WHERE uc.id = ? AND uc.user_id = ?
    ", [$certId, $user['id']]);
    
    if (!$cert) {
        Response::error('Certificate not found', 404);
    }
    
    if ($cert['status'] !== 'active') {
        Response::error('Certificate has been revoked', 400);
    }
    
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cert['device_name']);
    
    switch ($format) {
        case 'pem':
            header('Content-Type: application/x-pem-file');
            header('Content-Disposition: attachment; filename="' . $filename . '.crt"');
            echo $cert['certificate_pem'];
            break;
            
        case 'key':
            header('Content-Type: application/x-pem-file');
            header('Content-Disposition: attachment; filename="' . $filename . '.key"');
            echo $cert['private_key_pem'];
            break;
            
        case 'ca':
            header('Content-Type: application/x-pem-file');
            header('Content-Disposition: attachment; filename="truevault-ca.crt"');
            echo $cert['ca_cert'];
            break;
            
        case 'bundle':
            $zip = new ZipArchive();
            $zipFile = tempnam(sys_get_temp_dir(), 'cert_');
            
            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFromString($filename . '.crt', $cert['certificate_pem']);
                $zip->addFromString($filename . '.key', $cert['private_key_pem']);
                $zip->addFromString('ca.crt', $cert['ca_cert']);
                
                $readme = "TrueVault VPN Certificate Bundle\n";
                $readme .= "================================\n\n";
                $readme .= "Device: " . $cert['device_name'] . "\n";
                $readme .= "Common Name: " . $cert['common_name'] . "\n";
                $readme .= "Valid Until: " . $cert['not_after'] . "\n\n";
                $readme .= "Files:\n";
                $readme .= "- {$filename}.crt - Your device certificate\n";
                $readme .= "- {$filename}.key - Your private key (KEEP SECRET!)\n";
                $readme .= "- ca.crt - Certificate Authority certificate\n";
                $zip->addFromString('README.txt', $readme);
                
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $filename . '-certificates.zip"');
                header('Content-Length: ' . filesize($zipFile));
                readfile($zipFile);
                unlink($zipFile);
            } else {
                Response::error('Failed to create certificate bundle', 500);
            }
            break;
            
        case 'conf':
            $config = "[Interface]\n";
            $config .= "# Device: " . $cert['device_name'] . "\n";
            $config .= "# Certificate CN: " . $cert['common_name'] . "\n";
            $config .= "# Valid Until: " . $cert['not_after'] . "\n";
            $config .= "PrivateKey = YOUR_WIREGUARD_PRIVATE_KEY\n";
            $config .= "Address = 10.0.0.X/24\n";
            $config .= "DNS = 1.1.1.1, 8.8.8.8\n\n";
            $config .= "[Peer]\n";
            $config .= "# TrueVault VPN Server\n";
            $config .= "PublicKey = SERVER_PUBLIC_KEY\n";
            $config .= "AllowedIPs = 0.0.0.0/0\n";
            $config .= "Endpoint = vpn.truevault.com:51820\n";
            $config .= "PersistentKeepalive = 25\n";
            
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '.conf"');
            echo $config;
            break;
            
        default:
            Response::error('Invalid format. Use: pem, key, ca, bundle, or conf', 400);
    }
    
    exit;
    
} catch (Exception $e) {
    error_log("Certificate download error: " . $e->getMessage());
    Response::error('Failed to download certificate', 500);
}
