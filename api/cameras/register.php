<?php
/**
 * TrueVault VPN - Register Camera API
 * POST /api/cameras/register.php
 */

require_once __DIR__ . '/camera-manager.php';
require_once __DIR__ . '/../devices/device-manager.php';

$user = Auth::requireAuth();
Response::requireMethod('POST');

$input = Response::getJsonInput();

if (empty($input['ip_address'])) {
    Response::error('Camera IP address is required', 400);
}

try {
    $result = DeviceManager::registerCamera($user['id'], [
        'name' => $input['name'] ?? 'IP Camera',
        'type' => $input['type'] ?? 'generic',
        'ip_address' => $input['ip_address'],
        'port' => $input['port'] ?? 554,
        'username' => $input['username'] ?? 'admin',
        'password' => $input['password'] ?? '',
        'server_id' => $input['server_id'] ?? 1
    ]);
    
    if ($result['success']) {
        // Get port forwarding info
        $forwarding = CameraManager::getPortForwardingInfo($user['id'], $result['camera_id']);
        $result['forwarding'] = $forwarding;
        
        Response::success($result, 'Camera registered');
    } else {
        Response::error($result['error'], 400);
    }
} catch (Exception $e) {
    Response::serverError('Failed to register camera: ' . $e->getMessage());
}
