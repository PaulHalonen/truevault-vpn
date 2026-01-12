<?php
/**
 * TrueVault VPN - VIP Status API
 * GET /api/vip/status.php
 * 
 * Returns VIP status and privileges for authenticated user
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/vip.php';

// Only allow GET
Response::requireMethod('GET');

// Require authentication
$user = JWTManager::requireAuth();

try {
    $email = $user['email'];
    
    // Check VIP status
    $vipDetails = VIPManager::getVIPDetails($email);
    
    if ($vipDetails) {
        // User is VIP
        $serverAccess = VIPManager::getServerAccess($email);
        
        Response::success([
            'is_vip' => true,
            'tier' => $vipDetails['tier'],
            'tier_name' => $vipDetails['tier_name'],
            'badge' => VIPManager::getVIPBadge($email),
            'max_devices' => $vipDetails['max_devices'],
            'max_cameras' => $vipDetails['max_cameras'],
            'has_dedicated_server' => $vipDetails['has_dedicated_server'],
            'dedicated_server_ip' => $vipDetails['dedicated_server_ip'],
            'free_forever' => $vipDetails['free_forever'],
            'bypass_payment' => true,
            'server_access' => $serverAccess,
            'features' => [
                'Device swapping' => true,
                'Network scanner' => true,
                'Camera integration' => true,
                'All shared servers' => true,
                'Dedicated server' => $vipDetails['has_dedicated_server'],
                'Port forwarding' => $vipDetails['has_dedicated_server'],
                'Terminal access' => $vipDetails['has_dedicated_server']
            ]
        ]);
    } else {
        // Not VIP
        Response::success([
            'is_vip' => false,
            'tier' => null,
            'badge' => null,
            'message' => 'Standard account'
        ]);
    }
    
} catch (Exception $e) {
    Response::serverError('Failed to check VIP status: ' . $e->getMessage());
}
