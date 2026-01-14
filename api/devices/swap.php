<?php
/**
 * TrueVault VPN - Swap Device API
 * POST /api/devices/swap.php
 */

require_once __DIR__ . '/device-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['old_device_id'])) {
    Response::error('Old device ID is required', 400);
}

if (empty($input['new_device'])) {
    Response::error('New device data is required', 400);
}

try {
    $result = DeviceManager::swapDevice($user['id'], $input['old_device_id'], $input['new_device']);
    
    if ($result['success']) {
        Response::success($result, 'Device swapped');
    } else {
        Response::error($result['error'], 400);
    }
} catch (Exception $e) {
    Response::serverError('Failed to swap device: ' . $e->getMessage());
}
