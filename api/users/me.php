<?php
/**
 * TrueVault VPN - Get Current User
 * GET /api/users/me.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../billing/billing-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('GET');

try {
    // Get VIP status
    $isVip = VIPManager::isVIP($user['email']);
    $vipDetails = $isVip ? VIPManager::getVIPDetails($user['email']) : null;
    $vipLimits = $isVip ? VIPManager::getVIPLimits($user['email']) : null;
    
    // Get subscription if not VIP
    $subscription = null;
    if (!$isVip) {
        $subscription = BillingManager::getCurrentSubscription($user['id']);
    }
    
    // Get device count
    $deviceCount = Database::queryOne('devices',
        "SELECT COUNT(*) as cnt FROM user_devices WHERE user_id = ? AND status = 'active'",
        [$user['id']]
    );
    
    // Get camera count
    $cameraCount = Database::queryOne('cameras',
        "SELECT COUNT(*) as cnt FROM user_cameras WHERE user_id = ? AND status = 'active'",
        [$user['id']]
    );
    
    // Determine limits
    if ($isVip) {
        $maxDevices = $vipLimits['max_devices'];
        $maxCameras = $vipLimits['max_cameras'];
        $planType = $vipDetails['tier'];
    } elseif ($subscription) {
        $maxDevices = $subscription['max_devices'];
        $maxCameras = $subscription['max_cameras'];
        $planType = $subscription['plan_type'];
    } else {
        $maxDevices = 1;
        $maxCameras = 0;
        $planType = 'free';
    }
    
    Response::success([
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'plan_type' => $planType,
        'status' => $user['status'],
        'is_vip' => $isVip,
        'vip_tier' => $vipDetails['tier'] ?? null,
        'vip_badge' => $isVip ? ($vipDetails['tier'] === 'owner' ? '👑 Owner' : ($vipDetails['tier'] === 'vip_dedicated' ? '👑 VIP Dedicated' : '⭐ VIP')) : null,
        'device_count' => $deviceCount['cnt'] ?? 0,
        'camera_count' => $cameraCount['cnt'] ?? 0,
        'max_devices' => $maxDevices,
        'max_cameras' => $maxCameras,
        'subscription' => $subscription ? [
            'plan_type' => $subscription['plan_type'],
            'status' => $subscription['status'],
            'end_date' => $subscription['end_date']
        ] : null
    ]);
    
} catch (Exception $e) {
    Response::serverError('Failed to get user data: ' . $e->getMessage());
}
