<?php
/**
 * TrueVault VPN - Register Device API
 * POST /api/devices/register.php
 */

require_once __DIR__ . '/device-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['name'])) {
    Response::error('Device name is required', 400);
}

try {
    $result = DeviceManager::registerDevice($user['id'], [
        'name' => $input['name'],
        'type' => $input['type'] ?? 'unknown',
        'mac_address' => $input['mac_address'] ?? null,
        'ip_address' => $input['ip_address'] ?? null
    ]);
    
    if ($result['success']) {
        Response::success($result, 'Device registered');
    } else {
        Response::error($result['error'], 400, $result);
    }
} catch (Exception $e) {
    Response::serverError('Failed to register device: ' . $e->getMessage());
}
